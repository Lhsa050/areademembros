<?php
/**
 * Migração: Features 1, 2, 3
 * - Tabela offers (Popup de Oferta / Upsell)
 * - Tabela offer_products (produtos vinculados à oferta)
 * - Tabela push_subscriptions (subscriptions de notificações push)
 * - Tabela push_notifications (histórico de notificações enviadas)
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;

echo "=== Migração: Features 1, 2, 3 (Ofertas, PWA, Push) ===\n\n";

$migrations = [
    // Feature 4: sort_order em products (drag & drop)
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
    // Feature 1: Tabela offers
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
    // Feature 1: Tabela offer_products
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
    // Feature 3: Tabela push_subscriptions
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
    // Feature 3: Tabela push_notifications
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
];

foreach ($migrations as $migration) {
    echo "→ {$migration['description']}... ";
    
    try {
        if ($migration['check']) {
            $exists = Database::fetch($migration['check']);
            if ($exists) {
                echo "JÁ EXISTE (pulando)\n";
                continue;
            }
        }
        
        Database::query($migration['sql']);
        echo "OK ✓\n";
    } catch (\Exception $e) {
        echo "ERRO: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Migração concluída! ===\n";
