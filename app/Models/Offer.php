<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Ofertas (Upsell)
 */
class Offer extends BaseModel
{
    protected static string $table = 'offers';
    protected static bool $useUuid = false;
    protected static array $fillable = [
        'funnel_id', 'title', 'description', 'image', 'checkout_url',
        'webhook_token', 'is_active', 'show_as_popup',
        'price', 'fiscal_kind', 'fiscal_service_code', 'fiscal_service_description', 'fiscal_nbs_code', 'fiscal_iss_rate'
    ];

    /**
     * Busca ofertas de um funil
     */
    public static function getByFunnel(int $funnelId): array
    {
        return Database::fetchAll(
            "SELECT * FROM offers WHERE funnel_id = ? ORDER BY created_at DESC",
            [$funnelId]
        );
    }

    /**
     * Busca ofertas ativas de um funil
     */
    public static function getActiveByFunnel(int $funnelId): array
    {
        return Database::fetchAll(
            "SELECT * FROM offers WHERE funnel_id = ? AND is_active = 1 ORDER BY id DESC",
            [$funnelId]
        );
    }

    /**
     * Busca oferta pelo token de webhook
     */
    public static function findByWebhookToken(string $token): ?array
    {
        return Database::fetch(
            "SELECT * FROM offers WHERE webhook_token = ?",
            [$token]
        );
    }

    /**
     * Retorna IDs dos produtos vinculados
     */
    public static function getProductIds(int $offerId): array
    {
        $rows = Database::fetchAll(
            "SELECT product_id FROM offer_products WHERE offer_id = ?",
            [$offerId]
        );
        return array_column($rows, 'product_id');
    }

    /**
     * Vincula produtos à oferta
     */
    public static function syncProducts(int $offerId, array $productIds): void
    {
        Database::query("DELETE FROM offer_products WHERE offer_id = ?", [$offerId]);
        foreach ($productIds as $pid) {
            Database::query(
                "INSERT INTO offer_products (offer_id, product_id) VALUES (?, ?)",
                [$offerId, (int) $pid]
            );
        }
    }

    /**
     * Gera token único para webhook
     */
    public static function generateWebhookToken(): string
    {
        return 'offer_' . bin2hex(random_bytes(20));
    }
}
