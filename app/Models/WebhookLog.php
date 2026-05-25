<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Logs de Webhook
 */
class WebhookLog extends BaseModel
{
    protected static string $table = 'webhook_logs';
    protected static bool $useUuid = false;
    protected static bool $timestamps = false;
    private const RETENTION_DAYS = 5;
    private const CLEANUP_INTERVAL_SECONDS = 3600;
    protected static array $fillable = [
        'product_id', 'event_type', 'payload', 'ip', 'processed', 'error'
    ];

    /**
     * Registra um log de webhook
     */
    public static function log(
        ?int $productId,
        ?string $eventType,
        array $payload,
        ?string $ip
    ): int {
        self::pruneOld();

        Database::query(
            "INSERT INTO webhook_logs (product_id, event_type, payload, ip, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$productId, $eventType, self::compactPayload($payload), $ip]
        );
        return (int) Database::lastInsertId();
    }

    /**
     * Mantem apenas uma janela curta de logs, sem atrapalhar o recebimento do webhook.
     */
    public static function pruneOld(): void
    {
        try {
            $flagDir = ABSPATH . '/storage/cache';
            if (!is_dir($flagDir)) {
                @mkdir($flagDir, 0755, true);
            }

            $flagPath = $flagDir . '/webhook_logs_pruned_at';
            if (is_file($flagPath) && (time() - (int) filemtime($flagPath)) < self::CLEANUP_INTERVAL_SECONDS) {
                return;
            }

            Database::query(
                'DELETE FROM webhook_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ' . self::RETENTION_DAYS . ' DAY)'
            );
            @touch($flagPath);
        } catch (\Throwable $e) {
            error_log('[webhook_logs] prune failed: ' . $e->getMessage());
        }
    }

    /**
     * Guarda apenas um resumo util para diagnostico, evitando JSONs enormes de plataformas externas.
     */
    private static function compactPayload(array $payload): string
    {
        $summary = [
            '_compact' => true,
            'event' => $payload['event'] ?? null,
            'source_event' => $payload['source_event'] ?? null,
            'order_id' => $payload['order']['id'] ?? $payload['order']['order_id'] ?? $payload['order']['token'] ?? null,
            'order_number' => $payload['order']['order_number'] ?? $payload['order']['number'] ?? $payload['order']['name'] ?? null,
            'transaction' => $payload['data']['purchase']['transaction']
                ?? $payload['data']['purchase']['order_id']
                ?? $payload['order']['transaction_id']
                ?? null,
            'customer_email' => $payload['order']['customer']['email'] ?? $payload['data']['buyer']['email'] ?? null,
            'customer_name' => $payload['order']['customer']['full_name']
                ?? $payload['data']['buyer']['name']
                ?? null,
            'status' => $payload['order']['status'] ?? $payload['order']['status_id'] ?? $payload['data']['purchase']['status'] ?? null,
            'cancel_reason' => $payload['order']['cancel_reason'] ?? null,
            'has_refunds' => !empty($payload['order']['refunds']),
            'refunded_at' => $payload['order']['payment']['refunded_at'] ?? null,
        ];

        $summary = array_filter($summary, static fn($value) => $value !== null && $value !== '');
        $encoded = json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded !== false ? $encoded : '{"_compact":true}';
    }

    /**
     * Marca como processado
     */
    public static function markProcessed(int $id): void
    {
        Database::query(
            "UPDATE webhook_logs SET processed = 1 WHERE id = ?",
            [$id]
        );
    }

    /**
     * Marca como erro
     */
    public static function markError(int $id, string $error): void
    {
        Database::query(
            "UPDATE webhook_logs SET processed = 1, error = ? WHERE id = ?",
            [$error, $id]
        );
    }

    /**
     * Retorna logs recentes
     */
    public static function recent(int $limit = 50): array
    {
        return Database::fetchAll(
            "SELECT wl.*, p.title as product_title
             FROM webhook_logs wl
             LEFT JOIN products p ON p.id = wl.product_id
             ORDER BY wl.created_at DESC LIMIT {$limit}"
        );
    }
}
