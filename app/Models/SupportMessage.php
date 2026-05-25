<?php

namespace App\Models;

use App\Core\Database;

class SupportMessage extends BaseModel
{
    protected static string $table = 'support_messages';
    protected static bool $useUuid = false;
    protected static bool $timestamps = false;
    protected static array $fillable = [
        'ticket_id', 'sender_type', 'sender_name', 'sender_email',
        'admin_id', 'message', 'is_internal', 'created_at'
    ];

    public const DUPLICATE_WINDOW_SECONDS = 60;

    public static function createMessageOnce(
        int $ticketId,
        string $senderType,
        string $message,
        ?string $senderName = null,
        ?string $senderEmail = null,
        ?int $adminId = null
    ): array {
        $senderType = $senderType === 'admin' ? 'admin' : 'customer';
        $duplicate = self::findRecentDuplicate($ticketId, $senderType, $message, $senderEmail, $adminId);

        if ($duplicate) {
            return ['id' => (int) $duplicate['id'], 'created' => false];
        }

        return [
            'id' => self::createMessage($ticketId, $senderType, $message, $senderName, $senderEmail, $adminId),
            'created' => true,
        ];
    }

    public static function createMessage(
        int $ticketId,
        string $senderType,
        string $message,
        ?string $senderName = null,
        ?string $senderEmail = null,
        ?int $adminId = null
    ): int {
        $senderType = $senderType === 'admin' ? 'admin' : 'customer';

        $id = self::create([
            'ticket_id' => $ticketId,
            'sender_type' => $senderType,
            'sender_name' => $senderName,
            'sender_email' => $senderEmail,
            'admin_id' => $adminId,
            'message' => trim($message),
            'is_internal' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        SupportTicket::touchAfterMessage($ticketId, $senderType);
        return $id;
    }

    public static function findRecentDuplicate(
        int $ticketId,
        string $senderType,
        string $message,
        ?string $senderEmail = null,
        ?int $adminId = null,
        int $seconds = self::DUPLICATE_WINDOW_SECONDS
    ): ?array {
        $senderType = $senderType === 'admin' ? 'admin' : 'customer';
        $seconds = max(5, min(300, $seconds));
        $cutoff = date('Y-m-d H:i:s', time() - $seconds);
        $email = strtolower(trim((string) $senderEmail));

        $rows = Database::fetchAll(
            "SELECT id, sender_email, admin_id, message
             FROM support_messages
             WHERE ticket_id = ?
               AND sender_type = ?
               AND is_internal = 0
               AND created_at >= ?
             ORDER BY id DESC
             LIMIT 12",
            [$ticketId, $senderType, $cutoff]
        );

        foreach ($rows as $row) {
            $rowEmail = strtolower(trim((string) ($row['sender_email'] ?? '')));
            if ($email !== '' && $rowEmail !== '' && $rowEmail !== $email) {
                continue;
            }

            if ($senderType === 'admin' && $adminId && (int) ($row['admin_id'] ?? 0) !== $adminId) {
                continue;
            }

            if (self::messagesMatch((string) ($row['message'] ?? ''), $message)) {
                return $row;
            }
        }

        return null;
    }

    public static function getByTicket(int $ticketId): array
    {
        return Database::fetchAll(
            "SELECT sm.*, a.name AS admin_name
             FROM support_messages sm
             LEFT JOIN admins a ON a.id = sm.admin_id
             WHERE sm.ticket_id = ? AND sm.is_internal = 0
             ORDER BY sm.created_at ASC, sm.id ASC",
            [$ticketId]
        );
    }

    public static function messagesMatch(string $first, string $second): bool
    {
        return self::normalizeForDuplicate($first) === self::normalizeForDuplicate($second);
    }

    public static function normalizeForDuplicate(string $text): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($text)) ?: '';
        return function_exists('mb_strtolower')
            ? mb_strtolower($normalized, 'UTF-8')
            : strtolower($normalized);
    }
}
