<?php
/**
 * Migração para adicionar suporte a links externos nos arquivos
 * Adiciona coluna file_type em product_files e lesson_files
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;

echo "=== Migração: Links Externos em Entregáveis ===\n\n";

$migrations = [
    // Feature 6: file_type em product_files
    [
        'description' => 'Adicionar file_type em product_files',
        'check' => "SHOW COLUMNS FROM product_files LIKE 'file_type'",
        'sql' => "ALTER TABLE product_files ADD COLUMN file_type ENUM('upload','link') NOT NULL DEFAULT 'upload' AFTER file"
    ],
    // Feature 6: file_type em lesson_files
    [
        'description' => 'Adicionar file_type em lesson_files',
        'check' => "SHOW COLUMNS FROM lesson_files LIKE 'file_type'",
        'sql' => "ALTER TABLE lesson_files ADD COLUMN file_type ENUM('upload','link') NOT NULL DEFAULT 'upload' AFTER file"
    ],
    // Feature 6: Aumentar file para VARCHAR(500) em product_files (suportar URLs longas)
    [
        'description' => 'Aumentar campo file para 500 chars em product_files',
        'check' => null,
        'sql' => "ALTER TABLE product_files MODIFY COLUMN file VARCHAR(500) NOT NULL"
    ],
    // Feature 5: auto_organize em funnels
    [
        'description' => 'Adicionar auto_organize em funnels',
        'check' => "SHOW COLUMNS FROM funnels LIKE 'auto_organize'",
        'sql' => "ALTER TABLE funnels ADD COLUMN auto_organize TINYINT NOT NULL DEFAULT 0"
    ],
    // Feature 7: language e custom_translations em funnels
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
];

foreach ($migrations as $migration) {
    echo "→ {$migration['description']}... ";
    
    try {
        // Verificar se já existe
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
