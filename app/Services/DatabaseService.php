<?php

namespace App\Services;

use App\Core\Database;
use Exception;
use PDO;

/**
 * Serviço de Banco de Dados (Backup e Migração)
 */
class DatabaseService
{
    private string $backupDir;

    public function __construct()
    {
        $this->backupDir = ABSPATH . '/storage/backups';
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Retorna a lista de backups disponíveis
     */
    public function getBackups(): array
    {
        $backups = [];
        $files = glob($this->backupDir . '/*.sql');
        
        if ($files) {
            foreach ($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'path' => $file,
                    'size' => filesize($file),
                    'date' => filemtime($file)
                ];
            }
            // Ordenar por data decrescente (mais recentes primeiro)
            usort($backups, function ($a, $b) {
                return $b['date'] <=> $a['date'];
            });
        }
        
        return $backups;
    }

    /**
     * Cria um backup completo do banco de dados (Apenas PHP)
     */
    public function createBackup(): string
    {
        $db = Database::getInstance();
        $date = date('Y_m_d_His');
        $filename = "backup_vendas_{$date}.sql";
        $filepath = $this->backupDir . '/' . $filename;
        $handle = fopen($filepath, 'wb');

        if ($handle === false) {
            throw new Exception("Não foi possível salvar o arquivo de backup em {$filepath}");
        }

        try {
            $this->writeBackupLine($handle, "-- Backup gerado em " . date('Y-m-d H:i:s') . "\n");
            $this->writeBackupLine($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            $this->writeBackupLine($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
            $this->writeBackupLine($handle, "START TRANSACTION;\n\n");

            // Obter todas as tabelas
            $stmt = $db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $tableName = str_replace('`', '``', (string) $table);

                // Estrutura
                $stmt = $db->query("SHOW CREATE TABLE `{$tableName}`");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->writeBackupLine($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                $this->writeBackupLine($handle, $row['Create Table'] . ";\n\n");

                // Dados
                $stmt = $db->query("SELECT * FROM `{$tableName}`");
                $columnsSql = null;
                $inserted = 0;

                while ($dataRow = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if ($columnsSql === null) {
                        $columns = array_map(function ($column) {
                            return '`' . str_replace('`', '``', (string) $column) . '`';
                        }, array_keys($dataRow));
                        $columnsSql = implode(', ', $columns);
                    }

                    $rowValues = array_map(function ($val) use ($db) {
                        if ($val === null) return 'NULL';
                        return $db->quote((string) $val);
                    }, array_values($dataRow));

                    $this->writeBackupLine(
                        $handle,
                        "INSERT INTO `{$tableName}` ({$columnsSql}) VALUES (" . implode(", ", $rowValues) . ");\n"
                    );
                    $inserted++;

                    if ($inserted % 100 === 0) {
                        fflush($handle);
                    }
                }

                $stmt->closeCursor();
                $this->writeBackupLine($handle, "\n");
            }

            $this->writeBackupLine($handle, "COMMIT;\n");
            $this->writeBackupLine($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
        }

        if (!is_file($filepath) || filesize($filepath) <= 0) {
            @unlink($filepath);
            throw new Exception("Backup do banco de dados ficou vazio ou inválido.");
        }

        return $filename;
    }

    private function writeBackupLine($handle, string $line): void
    {
        if (fwrite($handle, $line) === false) {
            throw new Exception("Não foi possível escrever no arquivo de backup.");
        }
    }

    /**
     * Restaura um backup existente
     */
    public function restoreBackup(string $filename): bool
    {
        $filepath = $this->backupDir . '/' . basename($filename);
        if (!file_exists($filepath)) {
            throw new Exception("Arquivo de backup não encontrado.");
        }

        $sql = file_get_contents($filepath);
        if (empty($sql)) {
            throw new Exception("O arquivo de backup está vazio.");
        }

        $db = Database::getInstance();
        
        try {
            // Em PDO, queries múltiplas podem ser arriscadas se houver erros no meio,
            // mas para dumps gerados pelo sistema é geralmente seguro.
            // Para maior segurança, desabilitamos emulates prepares se possível
            $db->exec($sql);
            return true;
        } catch (Exception $e) {
            throw new Exception("Erro ao restaurar backup: " . $e->getMessage());
        }
    }

    /**
     * Exclui um arquivo de backup
     */
    public function deleteBackup(string $filename): bool
    {
        $filepath = $this->backupDir . '/' . basename($filename);
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }

    /**
     * Lista as migrações definidas e verifica o status
     */
    public function getMigrations(): array
    {
        // Mesmas migrações do migrate.php consolidado
        $migrations = [
            // == MIGRATE_FEATURES.PHP ==
            [
                'description' => 'Adicionar sort_order em products',
                'check' => "SHOW COLUMNS FROM products LIKE 'sort_order'",
                'sql' => "ALTER TABLE products ADD COLUMN sort_order INT NOT NULL DEFAULT 0"
            ],
            [
                'description' => 'Adicionar is_public em products',
                'check' => "SHOW COLUMNS FROM products LIKE 'is_public'",
                'sql' => "ALTER TABLE products ADD COLUMN is_public TINYINT NOT NULL DEFAULT 0 AFTER sort_order"
            ],
            [
                'description' => 'Permitir produtos globais sem funil direto',
                'check' => "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'funnel_id' AND IS_NULLABLE = 'YES'",
                'sql' => "ALTER TABLE products MODIFY COLUMN funnel_id INT NULL"
            ],
            [
                'description' => 'Criar tabela funnel_products',
                'check' => "SHOW TABLES LIKE 'funnel_products'",
                'sql' => "CREATE TABLE funnel_products (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    funnel_id INT NOT NULL,
                    product_id INT NOT NULL,
                    checkout_url VARCHAR(500) NULL,
                    webhook_token VARCHAR(64) NULL UNIQUE,
                    sort_order INT NOT NULL DEFAULT 0,
                    release_days INT NULL DEFAULT NULL,
                    is_public TINYINT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_funnel_product (funnel_id, product_id),
                    INDEX idx_funnel (funnel_id),
                    INDEX idx_product (product_id),
                    INDEX idx_sort (sort_order),
                    INDEX idx_webhook_token (webhook_token)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ],
            [
                'description' => 'Vincular produtos atuais aos seus funis',
                'check' => "SELECT 1 WHERE NOT EXISTS (
                    SELECT 1 FROM products p
                    WHERE p.funnel_id IS NOT NULL
                      AND NOT EXISTS (
                          SELECT 1 FROM funnel_products fp
                          WHERE fp.funnel_id = p.funnel_id AND fp.product_id = p.id
                      )
                )",
                'sql' => "INSERT IGNORE INTO funnel_products
                    (funnel_id, product_id, checkout_url, webhook_token, sort_order, release_days, is_public, created_at, updated_at)
                    SELECT funnel_id, id, checkout_url, webhook_token, sort_order, release_days, is_public, NOW(), NOW()
                    FROM products
                    WHERE funnel_id IS NOT NULL"
            ],
            [
                'description' => 'Converter produtos existentes para globais',
                'check' => "SELECT 1 WHERE NOT EXISTS (SELECT 1 FROM products WHERE funnel_id IS NOT NULL)",
                'sql' => "UPDATE products SET funnel_id = NULL WHERE funnel_id IS NOT NULL"
            ],
            [
                'description' => 'Adicionar webhook unificado em funnels',
                'check' => "SHOW COLUMNS FROM funnels LIKE 'webhook_token'",
                'sql' => "ALTER TABLE funnels ADD COLUMN webhook_token VARCHAR(64) NULL UNIQUE AFTER language"
            ],
            [
                'description' => 'Gerar tokens de webhook para funis existentes',
                'check' => "SELECT 1 WHERE NOT EXISTS (SELECT 1 FROM funnels WHERE webhook_token IS NULL OR webhook_token = '')",
                'sql' => "UPDATE funnels SET webhook_token = CONCAT('funnel_', REPLACE(UUID(), '-', '')) WHERE webhook_token IS NULL OR webhook_token = ''"
            ],
            [
                'description' => 'Adicionar codigo CartPanda global em products',
                'check' => "SHOW COLUMNS FROM products LIKE 'external_product_id'",
                'sql' => "ALTER TABLE products ADD COLUMN external_product_id VARCHAR(191) NULL AFTER webhook_token"
            ],
            [
                'description' => 'Adicionar indice do codigo CartPanda global',
                'check' => "SHOW INDEX FROM products WHERE Key_name = 'idx_external_product_id'",
                'sql' => "ALTER TABLE products ADD INDEX idx_external_product_id (external_product_id)"
            ],
            [
                'description' => 'Adicionar codigo CartPanda legado em funnel_products',
                'check' => "SHOW COLUMNS FROM funnel_products LIKE 'external_product_id'",
                'sql' => "ALTER TABLE funnel_products ADD COLUMN external_product_id VARCHAR(191) NULL AFTER webhook_token"
            ],
            [
                'description' => 'Adicionar indice do codigo CartPanda legado em funnel_products',
                'check' => "SHOW INDEX FROM funnel_products WHERE Key_name = 'idx_external_product'",
                'sql' => "ALTER TABLE funnel_products ADD INDEX idx_external_product (funnel_id, external_product_id)"
            ],
            [
                'description' => 'Adicionar papel do produto dentro do funil',
                'check' => "SHOW COLUMNS FROM funnel_products LIKE 'funnel_role'",
                'sql' => "ALTER TABLE funnel_products ADD COLUMN funnel_role ENUM('principal', 'bonus', 'orderbump') NULL DEFAULT NULL AFTER external_product_id"
            ],
            [
                'description' => 'Migrar codigo CartPanda dos vinculos para o produto global',
                'check' => "SELECT 1 WHERE NOT EXISTS (
                    SELECT 1
                    FROM funnel_products fp
                    INNER JOIN products p ON p.id = fp.product_id
                    WHERE fp.external_product_id IS NOT NULL
                      AND fp.external_product_id <> ''
                      AND (p.external_product_id IS NULL OR p.external_product_id = '')
                )",
                'sql' => "UPDATE products p
                    INNER JOIN (
                        SELECT product_id, MIN(external_product_id) AS external_product_id
                        FROM funnel_products
                        WHERE external_product_id IS NOT NULL AND external_product_id <> ''
                        GROUP BY product_id
                    ) fp ON fp.product_id = p.id
                    SET p.external_product_id = fp.external_product_id
                    WHERE p.external_product_id IS NULL OR p.external_product_id = ''"
            ],
            [
                'description' => 'Migrar checkout dos vinculos para o produto global',
                'check' => "SELECT 1 WHERE NOT EXISTS (
                    SELECT 1
                    FROM funnel_products fp
                    INNER JOIN products p ON p.id = fp.product_id
                    WHERE fp.checkout_url IS NOT NULL
                      AND fp.checkout_url <> ''
                      AND (p.checkout_url IS NULL OR p.checkout_url = '')
                )",
                'sql' => "UPDATE products p
                    INNER JOIN (
                        SELECT product_id, MIN(checkout_url) AS checkout_url
                        FROM funnel_products
                        WHERE checkout_url IS NOT NULL AND checkout_url <> ''
                        GROUP BY product_id
                    ) fp ON fp.product_id = p.id
                    SET p.checkout_url = fp.checkout_url
                    WHERE p.checkout_url IS NULL OR p.checkout_url = ''"
            ],
            [
                'description' => 'Adicionar file_type em product_files',
                'check' => "SHOW COLUMNS FROM product_files LIKE 'file_type'",
                'sql' => "ALTER TABLE product_files ADD COLUMN file_type ENUM('upload','link') NOT NULL DEFAULT 'upload' AFTER file"
            ],
            [
                'description' => 'Adicionar open_in_new_tab em product_files',
                'check' => "SHOW COLUMNS FROM product_files LIKE 'open_in_new_tab'",
                'sql' => "ALTER TABLE product_files ADD COLUMN open_in_new_tab TINYINT NOT NULL DEFAULT 0 AFTER file_type"
            ],
            [
                'description' => 'Adicionar file_type em lesson_files',
                'check' => "SHOW COLUMNS FROM lesson_files LIKE 'file_type'",
                'sql' => "ALTER TABLE lesson_files ADD COLUMN file_type ENUM('upload','link') NOT NULL DEFAULT 'upload' AFTER file"
            ],
            [
                'description' => 'Aumentar campo file para 500 chars em product_files',
                'check' => "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_files' AND COLUMN_NAME = 'file' AND CHARACTER_MAXIMUM_LENGTH >= 500",
                'sql' => "ALTER TABLE product_files MODIFY COLUMN file VARCHAR(500) COLLATE utf8mb4_unicode_ci NOT NULL"
            ],
            [
                'description' => 'Aumentar campo file para 500 chars em lesson_files',
                'check' => "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lesson_files' AND COLUMN_NAME = 'file' AND CHARACTER_MAXIMUM_LENGTH >= 500",
                'sql' => "ALTER TABLE lesson_files MODIFY COLUMN file VARCHAR(500) COLLATE utf8mb4_unicode_ci NOT NULL"
            ],
            [
                'description' => 'Adicionar auto_organize em funnels',
                'check' => "SHOW COLUMNS FROM funnels LIKE 'auto_organize'",
                'sql' => "ALTER TABLE funnels ADD COLUMN auto_organize TINYINT NOT NULL DEFAULT 0"
            ],
            [
                'description' => 'Adicionar language em funnels',
                'check' => "SHOW COLUMNS FROM funnels LIKE 'language'",
                'sql' => "ALTER TABLE funnels ADD COLUMN language VARCHAR(10) NOT NULL DEFAULT 'pt-BR'"
            ],
            [
                'description' => 'Adicionar custom_translations em funnels',
                'check' => "SHOW COLUMNS FROM funnels LIKE 'custom_translations'",
                'sql' => "ALTER TABLE funnels ADD COLUMN custom_translations JSON NULL"
            ],
            [
                'description' => 'Adicionar indice de data em webhook_logs',
                'check' => "SHOW INDEX FROM webhook_logs WHERE Key_name = 'idx_created_at'",
                'sql' => "ALTER TABLE webhook_logs ADD INDEX idx_created_at (created_at)"
            ],
            [
                'description' => 'Criar tabela de compras por pedido dos webhooks',
                'check' => "SHOW TABLES LIKE 'member_product_orders'",
                'sql' => "CREATE TABLE member_product_orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    funnel_id INT NOT NULL,
                    member_id INT NULL,
                    customer_email VARCHAR(191) NOT NULL,
                    product_id INT NOT NULL,
                    course_id INT NULL,
                    order_id VARCHAR(191) NOT NULL,
                    order_number VARCHAR(191) NULL,
                    source_platform VARCHAR(50) NOT NULL DEFAULT 'cartpanda',
                    source_event VARCHAR(100) NULL,
                    external_product_id VARCHAR(191) NULL,
                    payment_method VARCHAR(80) NULL,
                    payment_status ENUM('pending','paid','cancelled','refunded','chargeback') NOT NULL DEFAULT 'pending',
                    access_status ENUM('none','active','revoked') NOT NULL DEFAULT 'none',
                    paid_at DATETIME NULL,
                    refunded_at DATETIME NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_order_product (source_platform, order_id, product_id),
                    INDEX idx_member_product (member_id, product_id),
                    INDEX idx_email_product (customer_email, product_id),
                    INDEX idx_order (order_id),
                    INDEX idx_status (payment_status, access_status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ],

            // == MIGRATE_FEATURES_2.PHP ==
             [
                'description' => 'Criar tabela offers',
                'check' => "SHOW TABLES LIKE 'offers'",
                'sql' => "CREATE TABLE offers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    funnel_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    image VARCHAR(500) NULL,
                    checkout_url VARCHAR(500) NULL,
                    webhook_token VARCHAR(100) NOT NULL,
                    is_active TINYINT NOT NULL DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_funnel (funnel_id),
                    INDEX idx_webhook (webhook_token),
                    INDEX idx_active (funnel_id, is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ],
            [
                'description' => 'Adicionar show_as_popup em offers',
                'check' => "SHOW COLUMNS FROM offers LIKE 'show_as_popup'",
                'sql' => "ALTER TABLE offers ADD COLUMN show_as_popup TINYINT NOT NULL DEFAULT 0 AFTER is_active"
            ],
            [
                'description' => 'Criar tabela offer_products',
                'check' => "SHOW TABLES LIKE 'offer_products'",
                'sql' => "CREATE TABLE offer_products (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    offer_id INT NOT NULL,
                    product_id INT NOT NULL,
                    INDEX idx_offer (offer_id),
                    INDEX idx_product (product_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ],
            [
                'description' => 'Criar tabela push_subscriptions',
                'check' => "SHOW TABLES LIKE 'push_subscriptions'",
                'sql' => "CREATE TABLE push_subscriptions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    member_id INT NOT NULL,
                    funnel_id INT NOT NULL,
                    endpoint TEXT NOT NULL,
                    p256dh VARCHAR(255) NOT NULL,
                    auth_key VARCHAR(255) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_funnel (funnel_id),
                    INDEX idx_member (member_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ],
            [
                'description' => 'Criar tabela push_notifications',
                'check' => "SHOW TABLES LIKE 'push_notifications'",
                'sql' => "CREATE TABLE push_notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    funnel_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    body TEXT NOT NULL,
                    url VARCHAR(500) NULL,
                    sent_count INT NOT NULL DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_funnel (funnel_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ],
            [
                'description' => 'Criar tabela funnel_settings',
                'check' => "SHOW TABLES LIKE 'funnel_settings'",
                'sql' => "CREATE TABLE funnel_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    funnel_id INT NOT NULL,
                    setting_key VARCHAR(100) NOT NULL,
                    setting_value TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_funnel_setting (funnel_id, setting_key),
                    INDEX idx_funnel (funnel_id),
                    INDEX idx_key (setting_key)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ],
            [
                'description' => 'Criar tabela support_contacts',
                'check' => "SHOW TABLES LIKE 'support_contacts'",
                'sql' => "CREATE TABLE support_contacts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(191) NOT NULL UNIQUE,
                    name VARCHAR(200) NULL,
                    phone VARCHAR(30) NULL,
                    member_id INT NULL,
                    last_funnel_id INT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_member (member_id),
                    INDEX idx_last_funnel (last_funnel_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ],
            [
                'description' => 'Criar tabela support_tickets',
                'check' => "SHOW TABLES LIKE 'support_tickets'",
                'sql' => "CREATE TABLE support_tickets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ticket_number VARCHAR(32) NOT NULL UNIQUE,
                    support_contact_id INT NOT NULL,
                    member_id INT NULL,
                    funnel_id INT NULL,
                    subject VARCHAR(255) NOT NULL,
                    status ENUM('open','waiting_support','waiting_customer','closed') NOT NULL DEFAULT 'open',
                    source ENUM('public','member','admin') NOT NULL DEFAULT 'public',
                    secure_token CHAR(64) NOT NULL UNIQUE,
                    last_message_at DATETIME NULL,
                    last_customer_message_at DATETIME NULL,
                    last_admin_message_at DATETIME NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_contact (support_contact_id),
                    INDEX idx_member (member_id),
                    INDEX idx_funnel (funnel_id),
                    INDEX idx_status (status),
                    INDEX idx_last_message (last_message_at),
                    INDEX idx_token (secure_token)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ],
            [
                'description' => 'Criar tabela support_messages',
                'check' => "SHOW TABLES LIKE 'support_messages'",
                'sql' => "CREATE TABLE support_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ticket_id INT NOT NULL,
                    sender_type ENUM('customer','admin') NOT NULL,
                    sender_name VARCHAR(200) NULL,
                    sender_email VARCHAR(191) NULL,
                    admin_id INT NULL,
                    message TEXT NOT NULL,
                    is_internal TINYINT NOT NULL DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_ticket (ticket_id),
                    INDEX idx_sender (sender_type),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ],
            [
                'description' => 'Adicionar campos fiscais em products',
                'check' => "SHOW COLUMNS FROM products LIKE 'fiscal_kind'",
                'sql' => "ALTER TABLE products
                    ADD COLUMN price DECIMAL(12,2) NULL,
                    ADD COLUMN fiscal_kind VARCHAR(20) NULL,
                    ADD COLUMN fiscal_service_code VARCHAR(30) NULL,
                    ADD COLUMN fiscal_service_description TEXT NULL,
                    ADD COLUMN fiscal_nbs_code VARCHAR(30) NULL,
                    ADD COLUMN fiscal_iss_rate DECIMAL(5,2) NULL"
            ],
            [
                'description' => 'Adicionar campos fiscais em offers',
                'check' => "SHOW COLUMNS FROM offers LIKE 'fiscal_kind'",
                'sql' => "ALTER TABLE offers
                    ADD COLUMN price DECIMAL(12,2) NULL,
                    ADD COLUMN fiscal_kind VARCHAR(20) NULL,
                    ADD COLUMN fiscal_service_code VARCHAR(30) NULL,
                    ADD COLUMN fiscal_service_description TEXT NULL,
                    ADD COLUMN fiscal_nbs_code VARCHAR(30) NULL,
                    ADD COLUMN fiscal_iss_rate DECIMAL(5,2) NULL"
            ],
            [
                'description' => 'Criar tabela fiscal_sales',
                'check' => "SHOW TABLES LIKE 'fiscal_sales'",
                'sql' => "CREATE TABLE fiscal_sales (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    uuid CHAR(36) NOT NULL UNIQUE,
                    funnel_id INT NULL,
                    member_id INT NULL,
                    product_id INT NULL,
                    offer_id INT NULL,
                    source_platform VARCHAR(50) NOT NULL,
                    source_event VARCHAR(100) NULL,
                    transaction_id VARCHAR(191) NULL,
                    order_reference VARCHAR(191) NULL,
                    customer_name VARCHAR(200) NULL,
                    customer_email VARCHAR(191) NULL,
                    customer_document VARCHAR(20) NULL,
                    customer_document_type ENUM('cpf','cnpj','foreign','unknown') NOT NULL DEFAULT 'unknown',
                    customer_phone VARCHAR(30) NULL,
                    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                    currency CHAR(3) NOT NULL DEFAULT 'BRL',
                    status ENUM('pending','paid','refunded','canceled') NOT NULL DEFAULT 'paid',
                    invoice_status ENUM('not_issued','pending','issued','error','rejected','canceled') NOT NULL DEFAULT 'not_issued',
                    payload JSON NULL,
                    paid_at DATETIME NULL,
                    refunded_at DATETIME NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_funnel (funnel_id),
                    INDEX idx_member (member_id),
                    INDEX idx_product (product_id),
                    INDEX idx_offer (offer_id),
                    INDEX idx_transaction (source_platform, transaction_id),
                    INDEX idx_status (status),
                    INDEX idx_invoice_status (invoice_status),
                    INDEX idx_paid_at (paid_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ],
            [
                'description' => 'Criar tabela fiscal_invoices',
                'check' => "SHOW TABLES LIKE 'fiscal_invoices'",
                'sql' => "CREATE TABLE fiscal_invoices (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    sale_id INT NOT NULL,
                    provider VARCHAR(50) NOT NULL DEFAULT 'nfse_nacional',
                    environment ENUM('restricted','production') NOT NULL DEFAULT 'restricted',
                    document_type ENUM('nfse') NOT NULL DEFAULT 'nfse',
                    status ENUM('draft','processing','issued','rejected','canceled','cancel_error') NOT NULL DEFAULT 'draft',
                    dps_series VARCHAR(20) NULL,
                    dps_number INT NULL,
                    dps_id VARCHAR(80) NULL,
                    access_key VARCHAR(80) NULL,
                    verification_code VARCHAR(80) NULL,
                    issued_at DATETIME NULL,
                    canceled_at DATETIME NULL,
                    cancel_reason VARCHAR(255) NULL,
                    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                    service_code VARCHAR(30) NULL,
                    service_description TEXT NULL,
                    xml_path VARCHAR(500) NULL,
                    pdf_path VARCHAR(500) NULL,
                    request_payload JSON NULL,
                    response_payload JSON NULL,
                    errors TEXT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_sale (sale_id),
                    INDEX idx_status (status),
                    INDEX idx_access_key (access_key),
                    INDEX idx_dps (dps_series, dps_number)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ],
            [
                'description' => 'Adicionar campos fiscais avancados em products',
                'check' => "SHOW COLUMNS FROM products LIKE 'fiscal_tax_group_id'",
                'sql' => "ALTER TABLE products
                    ADD COLUMN fiscal_tax_group_id INT NULL,
                    ADD COLUMN fiscal_document_model VARCHAR(20) NULL,
                    ADD COLUMN fiscal_issue_policy VARCHAR(30) NULL,
                    ADD COLUMN fiscal_warranty_days INT NULL,
                    ADD COLUMN fiscal_lc116_code VARCHAR(30) NULL,
                    ADD COLUMN fiscal_municipal_service_code VARCHAR(50) NULL,
                    ADD COLUMN fiscal_cnae_code VARCHAR(20) NULL"
            ],
            [
                'description' => 'Adicionar campos fiscais avancados em offers',
                'check' => "SHOW COLUMNS FROM offers LIKE 'fiscal_tax_group_id'",
                'sql' => "ALTER TABLE offers
                    ADD COLUMN fiscal_tax_group_id INT NULL,
                    ADD COLUMN fiscal_document_model VARCHAR(20) NULL,
                    ADD COLUMN fiscal_issue_policy VARCHAR(30) NULL,
                    ADD COLUMN fiscal_warranty_days INT NULL,
                    ADD COLUMN fiscal_lc116_code VARCHAR(30) NULL,
                    ADD COLUMN fiscal_municipal_service_code VARCHAR(50) NULL,
                    ADD COLUMN fiscal_cnae_code VARCHAR(20) NULL"
            ],
            [
                'description' => 'Adicionar endereco fiscal do tomador em fiscal_sales',
                'check' => "SHOW COLUMNS FROM fiscal_sales LIKE 'customer_zip'",
                'sql' => "ALTER TABLE fiscal_sales
                    ADD COLUMN customer_zip VARCHAR(12) NULL,
                    ADD COLUMN customer_street VARCHAR(200) NULL,
                    ADD COLUMN customer_number VARCHAR(30) NULL,
                    ADD COLUMN customer_complement VARCHAR(100) NULL,
                    ADD COLUMN customer_district VARCHAR(100) NULL,
                    ADD COLUMN customer_municipality_code VARCHAR(10) NULL,
                    ADD COLUMN customer_city VARCHAR(100) NULL,
                    ADD COLUMN customer_state CHAR(2) NULL,
                    ADD COLUMN customer_country VARCHAR(2) NULL DEFAULT 'BR'"
            ],
            [
                'description' => 'Adicionar snapshot tributario em fiscal_invoices',
                'check' => "SHOW COLUMNS FROM fiscal_invoices LIKE 'tax_group_id'",
                'sql' => "ALTER TABLE fiscal_invoices
                    ADD COLUMN tax_group_id INT NULL,
                    ADD COLUMN tax_rule_id INT NULL,
                    ADD COLUMN municipal_service_code VARCHAR(50) NULL,
                    ADD COLUMN lc116_code VARCHAR(30) NULL,
                    ADD COLUMN cnae_code VARCHAR(20) NULL,
                    ADD COLUMN nbs_code VARCHAR(30) NULL,
                    ADD COLUMN iss_rate DECIMAL(7,4) NULL,
                    ADD COLUMN tax_snapshot JSON NULL"
            ],
            [
                'description' => 'Criar tabela fiscal_tax_groups',
                'check' => "SHOW TABLES LIKE 'fiscal_tax_groups'",
                'sql' => "CREATE TABLE fiscal_tax_groups (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(120) NOT NULL,
                    document_model ENUM('nfse','nfe') NOT NULL DEFAULT 'nfse',
                    is_default TINYINT NOT NULL DEFAULT 0,
                    settings_json JSON NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_model (document_model),
                    INDEX idx_default (document_model, is_default)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ],
            [
                'description' => 'Criar tabela fiscal_tax_rules',
                'check' => "SHOW TABLES LIKE 'fiscal_tax_rules'",
                'sql' => "CREATE TABLE fiscal_tax_rules (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    tax_group_id INT NOT NULL,
                    name VARCHAR(120) NOT NULL,
                    customer_location ENUM('any','same_city','outside_city','foreign') NOT NULL DEFAULT 'any',
                    customer_type ENUM('any','cpf','cnpj','foreign') NOT NULL DEFAULT 'any',
                    operation_nature VARCHAR(50) NULL,
                    iss_rate DECIMAL(7,4) NULL,
                    pis_rate DECIMAL(7,4) NULL,
                    cofins_rate DECIMAL(7,4) NULL,
                    ir_rate DECIMAL(7,4) NULL,
                    inss_rate DECIMAL(7,4) NULL,
                    csll_rate DECIMAL(7,4) NULL,
                    deduction_rate DECIMAL(7,4) NULL,
                    retain_iss TINYINT NOT NULL DEFAULT 0,
                    service_location ENUM('company_city','customer_city','custom') NOT NULL DEFAULT 'company_city',
                    incidence_municipality_type ENUM('company_city','customer_city','custom') NOT NULL DEFAULT 'company_city',
                    incidence_municipality_code VARCHAR(10) NULL,
                    cst VARCHAR(20) NULL,
                    municipal_benefit VARCHAR(50) NULL,
                    additional_info TEXT NULL,
                    conditions_json JSON NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_group (tax_group_id),
                    INDEX idx_location (customer_location, customer_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ],
            [
                'description' => 'Criar tabela fiscal_closings',
                'check' => "SHOW TABLES LIKE 'fiscal_closings'",
                'sql' => "CREATE TABLE fiscal_closings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    year INT NOT NULL,
                    month TINYINT NOT NULL,
                    status ENUM('draft','generated','sent','error') NOT NULL DEFAULT 'draft',
                    include_xml TINYINT NOT NULL DEFAULT 1,
                    include_pdf TINYINT NOT NULL DEFAULT 0,
                    include_xlsx TINYINT NOT NULL DEFAULT 1,
                    block_after_closing TINYINT NOT NULL DEFAULT 0,
                    email_to VARCHAR(191) NULL,
                    total_authorized INT NOT NULL DEFAULT 0,
                    total_canceled INT NOT NULL DEFAULT 0,
                    total_refunded INT NOT NULL DEFAULT 0,
                    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                    file_path VARCHAR(500) NULL,
                    generated_at DATETIME NULL,
                    notes TEXT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_period (year, month),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ],
        ];

        // Verificar o status atual
        $pending = [];
        $applied = [];

        foreach ($migrations as $m) {
            $isApplied = false;
            
            if ($m['check']) {
                try {
                    $exists = Database::fetch($m['check']);
                    if ($exists) {
                        $isApplied = true;
                    }
                } catch (Exception $e) {
                    // Se der erro no check da tabela (ex: SHOW COLUMNS duma tabela q não existe), consideramos q não existe
                    $isApplied = false;
                }
            } else {
                // Se não tem check, só roda se alguma anterior pendente exigiu
                // Hack: para alter col sem erro fatal, podemos checar o varchar lenght no information_schema, 
                // mas para manter simples, vamos considerar `applied` se a primeira pendente já passou
                // Aqui vamos pular "Aumentar campo file" se 'file_type' já existe e não deu erro (assumindo q rodou junto)
                $isApplied = count($pending) === 0;
            }

            if ($isApplied) {
                $applied[] = $m;
            } else {
                $pending[] = $m;
            }
        }

        return [
            'pending' => $pending,
            'applied' => $applied,
            'has_updates' => count($pending) > 0
        ];
    }

    /**
     * Executa todas as migrações pendentes
     */
    public function runMigrations(): array
    {
        $status = $this->getMigrations();
        $results = [
            'success' => true,
            'log' => [],
            'executed' => 0
        ];

        foreach ($status['pending'] as $migration) {
            try {
                Database::query($migration['sql']);
                $results['log'][] = "✓ OK: {$migration['description']}";
                $results['executed']++;
            } catch (Exception $e) {
                $results['success'] = false;
                $results['log'][] = "✗ ERRO ({$migration['description']}): " . $e->getMessage();
                break; // Para no primeiro erro para não quebrar a integridade
            }
        }

        return $results;
    }
}
