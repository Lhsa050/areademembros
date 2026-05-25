<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Notificações Push enviadas
 */
class PushNotification extends BaseModel
{
    protected static string $table = 'push_notifications';
    protected static bool $useUuid = false;
    protected static array $fillable = [
        'funnel_id', 'title', 'body', 'url', 'sent_count'
    ];

    /**
     * Busca notificações de um funil
     */
    public static function getByFunnel(int $funnelId): array
    {
        return Database::fetchAll(
            "SELECT * FROM push_notifications WHERE funnel_id = ? ORDER BY created_at DESC",
            [$funnelId]
        );
    }
}
