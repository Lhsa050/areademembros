<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Níveis de Acesso
 */
class AccessLevel extends BaseModel
{
    protected static string $table = 'access_levels';
    protected static bool $useUuid = false;
    protected static array $fillable = [
        'funnel_id', 'name', 'uuid_key', 'password'
    ];

    /**
     * Retorna níveis de um funil
     */
    public static function getByFunnel(int $funnelId): array
    {
        return Database::fetchAll(
            "SELECT * FROM access_levels WHERE funnel_id = ? ORDER BY id ASC",
            [$funnelId]
        );
    }

    /**
     * Gera UUID de 16 caracteres
     */
    public static function generateUuidKey(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $key = '';
        for ($i = 0; $i < 16; $i++) {
            $key .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $key;
    }

    /**
     * Busca por uuid_key
     */
    public static function findByKey(string $key): ?array
    {
        return Database::fetch(
            "SELECT * FROM access_levels WHERE uuid_key = ?",
            [$key]
        );
    }

    /**
     * Retorna nível com produtos
     */
    public static function findWithProducts(int $id): ?array
    {
        $level = self::find($id);
        
        if (!$level) {
            return null;
        }

        $level['products'] = Product::getByAccessLevel($id);
        
        return $level;
    }

    /**
     * Sincroniza produtos do nível
     */
    public static function syncProducts(int $levelId, array $productIds): void
    {
        // Remove todos os vínculos
        Database::query(
            "DELETE FROM access_level_products WHERE access_level_id = ?",
            [$levelId]
        );

        // Adiciona novos vínculos
        foreach ($productIds as $productId) {
            Database::query(
                "INSERT INTO access_level_products (access_level_id, product_id) VALUES (?, ?)",
                [$levelId, $productId]
            );
        }
    }

    /**
     * Retorna IDs de produtos vinculados
     */
    public static function getProductIds(int $levelId): array
    {
        $rows = Database::fetchAll(
            "SELECT product_id FROM access_level_products WHERE access_level_id = ?",
            [$levelId]
        );
        return array_column($rows, 'product_id');
    }

    /**
     * Retorna todos os níveis com produtos completos para geração
     */
    public static function allWithFullProducts(): array
    {
        $levels = self::all('id', 'ASC');
        
        foreach ($levels as &$level) {
            $level['products'] = [];
            $products = Product::getByAccessLevel($level['id']);
            
            foreach ($products as $product) {
                if ($product['type'] === 'video') {
                    $product = Product::findWithModules($product['id']);
                }
                $level['products'][] = $product;
            }
        }
        
        return $levels;
    }
}
