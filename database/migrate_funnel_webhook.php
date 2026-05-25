<?php
/**
 * Migration: Webhook Unificado por Funil
 * 
 * Adiciona:
 * 1. Coluna webhook_token à tabela funnels
 * 2. Coluna external_product_id à tabela funnel_products
 * 3. Gera tokens automaticamente para funis existentes
 */

define('ABSPATH', dirname(__DIR__));
require_once ABSPATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ABSPATH);
if (file_exists(ABSPATH . '/.env')) {
    $dotenv->load();
}

use App\Core\Database;

echo "<h2>Migration: Webhook Unificado por Funil</h2><pre>\n";

try {
    // 1. Adicionar webhook_token à tabela funnels
    $hasColumn = Database::fetch("SHOW COLUMNS FROM funnels LIKE 'webhook_token'");
    if (!$hasColumn) {
        Database::query("ALTER TABLE funnels ADD COLUMN webhook_token VARCHAR(64) NULL UNIQUE AFTER language");
        echo "✓ Coluna 'webhook_token' adicionada à tabela 'funnels'\n";
    } else {
        echo "→ Coluna 'webhook_token' já existe em 'funnels'\n";
    }

    // 2. Adicionar external_product_id à tabela funnel_products
    $hasFpTable = Database::fetch("SHOW TABLES LIKE 'funnel_products'");
    if ($hasFpTable) {
        $hasExtCol = Database::fetch("SHOW COLUMNS FROM funnel_products LIKE 'external_product_id'");
        if (!$hasExtCol) {
            Database::query("ALTER TABLE funnel_products ADD COLUMN external_product_id VARCHAR(191) NULL AFTER webhook_token");
            Database::query("CREATE INDEX idx_external_product ON funnel_products (funnel_id, external_product_id)");
            echo "✓ Coluna 'external_product_id' adicionada à tabela 'funnel_products'\n";
        } else {
            echo "→ Coluna 'external_product_id' já existe em 'funnel_products'\n";
        }
    } else {
        echo "⚠ Tabela 'funnel_products' não encontrada. Execute as migrações base primeiro.\n";
    }

    // 3. Gerar tokens para funis existentes que não possuem
    $funnelsWithoutToken = Database::fetchAll(
        "SELECT id FROM funnels WHERE webhook_token IS NULL OR webhook_token = ''"
    );

    $generated = 0;
    foreach ($funnelsWithoutToken as $funnel) {
        $token = 'funnel_' . bin2hex(random_bytes(20));
        Database::query(
            "UPDATE funnels SET webhook_token = ? WHERE id = ?",
            [$token, $funnel['id']]
        );
        $generated++;
    }

    if ($generated > 0) {
        echo "✓ Tokens gerados para {$generated} funil(is) existente(s)\n";
    } else {
        echo "→ Todos os funis já possuem webhook_token\n";
    }

    // 4. Adicionar funnel_role à tabela funnel_products
    $hasFpTable2 = Database::fetch("SHOW TABLES LIKE 'funnel_products'");
    if ($hasFpTable2) {
        $hasRoleCol = Database::fetch("SHOW COLUMNS FROM funnel_products LIKE 'funnel_role'");
        if (!$hasRoleCol) {
            Database::query("ALTER TABLE funnel_products ADD COLUMN funnel_role ENUM('principal', 'bonus', 'orderbump') NULL DEFAULT NULL AFTER external_product_id");
            echo "✓ Coluna 'funnel_role' adicionada à tabela 'funnel_products'\n";
        } else {
            echo "→ Coluna 'funnel_role' já existe em 'funnel_products'\n";
        }
    }

    echo "\n✅ Migration concluída com sucesso!\n";

} catch (\Exception $e) {
    echo "\n❌ Erro: " . $e->getMessage() . "\n";
}

echo "</pre>";
