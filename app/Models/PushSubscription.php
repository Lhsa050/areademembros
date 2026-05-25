<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Subscriptions Push
 */
class PushSubscription extends BaseModel
{
    protected static string $table = 'push_subscriptions';
    protected static bool $useUuid = false;
    protected static array $fillable = [
        'member_id', 'funnel_id', 'endpoint', 'p256dh', 'auth_key'
    ];

    /**
     * Busca subscriptions de um funil
     */
    public static function getByFunnel(int $funnelId): array
    {
        return Database::fetchAll(
            "SELECT * FROM push_subscriptions WHERE funnel_id = ?",
            [$funnelId]
        );
    }

    /**
     * Busca por endpoint (para evitar duplicatas)
     */
    public static function findByEndpoint(string $endpoint, int $funnelId): ?array
    {
        return Database::fetch(
            "SELECT * FROM push_subscriptions WHERE endpoint = ? AND funnel_id = ?",
            [$endpoint, $funnelId]
        );
    }

    /**
     * Remove subscriptions expiradas/inválidas
     */
    public static function removeByEndpoint(string $endpoint): void
    {
        Database::query("DELETE FROM push_subscriptions WHERE endpoint = ?", [$endpoint]);
    }
}
