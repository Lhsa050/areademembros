<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Módulos
 */
class Module extends BaseModel
{
    protected static string $table = 'modules';
    protected static bool $useUuid = false;
    protected static bool $timestamps = false;
    protected static array $fillable = [
        'product_id', 'title', 'sort_order', 'release_days'
    ];

    /**
     * Retorna módulos de um produto
     */
    public static function getByProduct(int $productId): array
    {
        $orderBy = self::orderByClause();
        return Database::fetchAll(
            "SELECT * FROM modules WHERE product_id = ? ORDER BY {$orderBy}",
            [$productId]
        );
    }

    /**
     * Deleta módulo e suas aulas
     */
    public static function deleteWithLessons(int $id): bool
    {
        // Deleta aulas primeiro
        Database::query("DELETE FROM lessons WHERE module_id = ?", [$id]);
        // Deleta módulo
        return self::delete($id);
    }

    private static function orderByClause(): string
    {
        if (self::hasColumn('sort_order')) {
            return 'sort_order ASC, id ASC';
        }

        return 'id ASC';
    }

    private static function hasColumn(string $column): bool
    {
        static $columns = [];

        if (!array_key_exists($column, $columns)) {
            try {
                $columns[$column] = (bool) Database::fetch("SHOW COLUMNS FROM modules LIKE ?", [$column]);
            } catch (\Throwable $e) {
                $columns[$column] = false;
            }
        }

        return $columns[$column];
    }
}
