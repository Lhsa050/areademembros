<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Arquivos do Produto
 */
class ProductFile extends BaseModel
{
    protected static string $table = 'product_files';
    protected static bool $useUuid = false;
    protected static bool $timestamps = false;
    protected static array $fillable = [
        'product_id', 'title', 'file', 'file_type', 'open_in_new_tab', 'sort_order', 'release_days'
    ];

    /**
     * Retorna arquivos de um produto ordenados
     */
    public static function getByProduct(int $productId): array
    {
        $orderBy = self::orderByClause();
        return Database::fetchAll(
            "SELECT * FROM product_files WHERE product_id = ? ORDER BY {$orderBy}",
            [$productId]
        );
    }

    /**
     * Retorna arquivos agrupados por produto.
     */
    public static function getByProducts(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $orderBy = self::orderByClause();
        $files = Database::fetchAll(
            "SELECT * FROM product_files WHERE product_id IN ({$placeholders}) ORDER BY product_id ASC, {$orderBy}",
            $productIds
        );

        $grouped = [];
        foreach ($files as $file) {
            $grouped[(int) $file['product_id']][] = $file;
        }

        return $grouped;
    }

    /**
     * Deleta todos os arquivos de um produto
     */
    public static function deleteByProduct(int $productId): bool
    {
        Database::query("DELETE FROM product_files WHERE product_id = ?", [$productId]);
        return true;
    }

    public static function supportsOpenInNewTab(): bool
    {
        return self::hasColumn('open_in_new_tab');
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
                $columns[$column] = (bool) Database::fetch("SHOW COLUMNS FROM product_files LIKE ?", [$column]);
            } catch (\Throwable $e) {
                $columns[$column] = false;
            }
        }

        return $columns[$column];
    }
}
