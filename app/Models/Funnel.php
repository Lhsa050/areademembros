<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Funis
 */
class Funnel extends BaseModel
{
    protected static string $table = 'funnels';
    protected static bool $useUuid = true;
    protected static array $fillable = [
        'uuid', 'name', 'slug', 'description', 'theme', 'site_name', 'auto_organize', 'language', 'webhook_token', 'custom_translations'
    ];

    /**
     * Retorna todos os funis ordenados
     */
    public static function allOrdered(): array
    {
        return Database::fetchAll(
            "SELECT * FROM funnels ORDER BY name ASC"
        );
    }

    /**
     * Busca funil pelo slug
     */
    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch(
            "SELECT * FROM funnels WHERE slug = ?",
            [$slug]
        );
    }

    /**
     * Gera slug a partir do nome
     */
    public static function generateSlug(string $name, ?int $excludeId = null): string
    {
        // Remove acentos e caracteres especiais
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $slug = preg_replace('/[^a-zA-Z0-9\s-]/', '', $slug);
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[\s_]+/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // Verifica se já existe (com try-catch caso coluna slug não exista)
        try {
            $baseSlug = $slug;
            $counter = 1;
            
            while (true) {
                $query = "SELECT id FROM funnels WHERE slug = ?";
                $params = [$slug];
                
                if ($excludeId) {
                    $query .= " AND id != ?";
                    $params[] = $excludeId;
                }
                
                $existing = Database::fetch($query, $params);
                
                if (!$existing) {
                    break;
                }
                
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
        } catch (\Exception $e) {
            // Coluna slug não existe ainda, retorna slug gerado
        }

        return $slug;
    }

    /**
     * Retorna funil com contagens
     */
    public static function findWithCounts(int $id): ?array
    {
        $funnel = self::find($id);
        
        if (!$funnel) {
            return null;
        }

        $funnel['product_count'] = count(Product::getByFunnel($id));
        $funnel['access_level_count'] = AccessLevel::count('funnel_id = ?', [$id]);
        
        return $funnel;
    }

    /**
     * Retorna funil completo para geração
     */
    public static function findForGeneration(int $id): ?array
    {
        $funnel = self::find($id);
        
        if (!$funnel) {
            return null;
        }

        // Busca níveis de acesso com produtos
        $funnel['access_levels'] = Database::fetchAll(
            "SELECT * FROM access_levels WHERE funnel_id = ? ORDER BY id ASC",
            [$id]
        );

        foreach ($funnel['access_levels'] as &$level) {
            $level['products'] = [];
            $products = Product::getByAccessLevel($level['id']);
            
            foreach ($products as $product) {
                if ($product['type'] === 'video') {
                    $product = Product::findWithModules($product['id']);
                }
                $level['products'][] = $product;
            }
        }

        return $funnel;
    }
    /**
     * Busca funil pelo token de webhook unificado
     */
    public static function findByWebhookToken(string $token): ?array
    {
        return Database::fetch(
            "SELECT * FROM funnels WHERE webhook_token = ?",
            [$token]
        );
    }

    /**
     * Gera token único para webhook do funil
     */
    public static function generateWebhookToken(): string
    {
        do {
            $token = 'funnel_' . bin2hex(random_bytes(20));
            $exists = Database::fetch(
                "SELECT id FROM funnels WHERE webhook_token = ?",
                [$token]
            );
        } while ($exists);

        return $token;
    }
}
