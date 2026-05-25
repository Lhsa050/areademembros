<?php

namespace App\Models;

use App\Core\Database;

class FiscalTaxGroup extends BaseModel
{
    protected static string $table = 'fiscal_tax_groups';
    protected static bool $useUuid = false;
    protected static array $fillable = [
        'name', 'document_model', 'is_default', 'settings_json'
    ];

    public static function forModel(string $documentModel = 'nfse'): array
    {
        try {
            if (!Database::fetch("SHOW TABLES LIKE 'fiscal_tax_groups'")) {
                return [];
            }

            return Database::fetchAll(
                "SELECT *
                 FROM fiscal_tax_groups
                 WHERE document_model = ?
                 ORDER BY is_default DESC, name ASC, id ASC",
                [$documentModel]
            );
        } catch (\Throwable $e) {
            return [];
        }
    }
}
