<?php

namespace App\Models;

use App\Core\Database;

class SupportTicket extends BaseModel
{
    protected static string $table = 'support_tickets';
    protected static bool $useUuid = false;
    protected static array $fillable = [
        'ticket_number', 'support_contact_id', 'member_id', 'funnel_id', 'subject',
        'status', 'source', 'secure_token', 'last_message_at',
        'last_customer_message_at', 'last_admin_message_at'
    ];

    public const STATUSES = ['open', 'waiting_support', 'waiting_customer', 'closed'];
    public const STATUS_TABS = ['open', 'resolved', 'all'];

    public static function createTicket(
        int $contactId,
        ?int $memberId,
        ?int $funnelId,
        string $subject,
        string $source = 'public'
    ): int {
        $subject = self::cleanSubject($subject);

        return self::create([
            'ticket_number' => self::newTicketNumber(),
            'support_contact_id' => $contactId,
            'member_id' => $memberId ?: null,
            'funnel_id' => $funnelId ?: null,
            'subject' => $subject,
            'status' => 'open',
            'source' => $source,
            'secure_token' => self::newToken(),
            'last_message_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function findWithRelations(int $id): ?array
    {
        return Database::fetch(
            self::baseQuery() . " WHERE st.id = ?",
            [$id]
        );
    }

    public static function findByToken(string $token): ?array
    {
        return Database::fetch(
            self::baseQuery() . " WHERE st.secure_token = ?",
            [$token]
        );
    }

    public static function search(array $filters, int $limit = 20, int $offset = 0): array
    {
        [$where, $params] = self::filterSql($filters);
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);

        return Database::fetchAll(
            self::baseQuery() . " {$where}
             ORDER BY COALESCE(st.last_message_at, st.created_at) DESC, st.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public static function countSearch(array $filters): int
    {
        [$where, $params] = self::filterSql($filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total
             FROM support_tickets st
             INNER JOIN support_contacts sc ON sc.id = st.support_contact_id
             LEFT JOIN funnels f ON f.id = st.funnel_id
             LEFT JOIN members m ON m.id = st.member_id
             {$where}",
            $params
        );

        return (int) ($row['total'] ?? 0);
    }

    public static function getByMember(int $memberId, int $funnelId): array
    {
        return Database::fetchAll(
            self::baseQuery() . "
             WHERE st.member_id = ? AND (st.funnel_id IS NULL OR st.funnel_id = ?)
             ORDER BY COALESCE(st.last_message_at, st.created_at) DESC, st.id DESC",
            [$memberId, $funnelId]
        );
    }

    public static function findRecentDuplicateCustomerTicket(
        int $contactId,
        ?int $memberId,
        ?int $funnelId,
        string $subject,
        string $message,
        string $source = 'public',
        int $seconds = SupportMessage::DUPLICATE_WINDOW_SECONDS
    ): ?array {
        $seconds = max(5, min(300, $seconds));
        $source = in_array($source, ['public', 'member', 'admin'], true) ? $source : 'public';
        $subject = self::cleanSubject($subject);
        $cutoff = date('Y-m-d H:i:s', time() - $seconds);

        $rows = Database::fetchAll(
            "SELECT st.id, sm.message
             FROM support_tickets st
             INNER JOIN support_messages sm ON sm.ticket_id = st.id
             WHERE st.support_contact_id = ?
               AND st.source = ?
               AND st.subject = ?
               AND st.created_at >= ?
               AND sm.sender_type = 'customer'
               AND sm.is_internal = 0
               AND ((? IS NULL AND st.member_id IS NULL) OR st.member_id = ?)
               AND ((? IS NULL AND st.funnel_id IS NULL) OR st.funnel_id = ?)
             ORDER BY st.created_at DESC, st.id DESC, sm.id ASC
             LIMIT 12",
            [$contactId, $source, $subject, $cutoff, $memberId, $memberId, $funnelId, $funnelId]
        );

        foreach ($rows as $row) {
            if (SupportMessage::messagesMatch((string) ($row['message'] ?? ''), $message)) {
                return self::findWithRelations((int) $row['id']);
            }
        }

        return null;
    }

    public static function updateStatus(int $ticketId, string $status): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }

        return self::update($ticketId, ['status' => $status]);
    }

    public static function touchAfterMessage(int $ticketId, string $senderType): void
    {
        $status = $senderType === 'admin' ? 'waiting_customer' : 'waiting_support';
        $field = $senderType === 'admin' ? 'last_admin_message_at' : 'last_customer_message_at';

        Database::query(
            "UPDATE support_tickets
             SET status = IF(status = 'closed', status, ?),
                 last_message_at = NOW(),
                 {$field} = NOW(),
                 updated_at = NOW()
             WHERE id = ?",
            [$status, $ticketId]
        );
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'waiting_support' => 'Aguardando suporte',
            'waiting_customer' => 'Aguardando cliente',
            'closed' => 'Resolvido',
            default => 'Aberto',
        };
    }

    public static function normalizeStatusTab(string $tab): string
    {
        return in_array($tab, self::STATUS_TABS, true) ? $tab : 'open';
    }

    public static function statusTabLabel(string $tab): string
    {
        return match (self::normalizeStatusTab($tab)) {
            'resolved' => 'Resolvidos',
            'all' => 'Todos',
            default => 'Abertos',
        };
    }

    public static function filterTicketsForTab(array $tickets, string $tab): array
    {
        $tab = self::normalizeStatusTab($tab);
        return array_values(array_filter($tickets, fn($ticket) => self::ticketMatchesTab($ticket, $tab)));
    }

    public static function tabCountsForRows(array $tickets): array
    {
        return [
            'open' => count(self::filterTicketsForTab($tickets, 'open')),
            'resolved' => count(self::filterTicketsForTab($tickets, 'resolved')),
            'all' => count($tickets),
        ];
    }

    public static function ticketMatchesTab(array $ticket, string $tab): bool
    {
        $tab = self::normalizeStatusTab($tab);
        $status = (string) ($ticket['status'] ?? 'open');

        return match ($tab) {
            'resolved' => $status === 'closed',
            'all' => true,
            default => $status !== 'closed',
        };
    }

    private static function baseQuery(): string
    {
        return "SELECT st.*,
                       sc.email AS contact_email,
                       sc.name AS contact_name,
                       sc.phone AS contact_phone,
                       f.name AS funnel_name,
                       f.slug AS funnel_slug,
                       m.name AS member_name,
                       m.email AS member_email
                FROM support_tickets st
                INNER JOIN support_contacts sc ON sc.id = st.support_contact_id
                LEFT JOIN funnels f ON f.id = st.funnel_id
                LEFT JOIN members m ON m.id = st.member_id";
    }

    private static function filterSql(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['funnel_id'])) {
            $where[] = 'st.funnel_id = ?';
            $params[] = (int) $filters['funnel_id'];
        }

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $where[] = 'st.status = ?';
            $params[] = $filters['status'];
        } elseif (!empty($filters['tab'])) {
            $tab = self::normalizeStatusTab((string) $filters['tab']);
            if ($tab === 'open') {
                $where[] = "st.status <> 'closed'";
            } elseif ($tab === 'resolved') {
                $where[] = "st.status = 'closed'";
            }
        }

        if (!empty($filters['q'])) {
            $q = '%' . trim((string) $filters['q']) . '%';
            $where[] = "(st.ticket_number LIKE ? OR st.subject LIKE ? OR sc.email LIKE ? OR sc.name LIKE ? OR f.name LIKE ?)";
            array_push($params, $q, $q, $q, $q, $q);
        }

        return [empty($where) ? '' : 'WHERE ' . implode(' AND ', $where), $params];
    }

    private static function cleanSubject(string $subject): string
    {
        $subject = trim($subject);
        return $subject === '' ? 'Atendimento' : substr($subject, 0, 255);
    }

    private static function newTicketNumber(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $number = 'SUP-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            if (!Database::fetch("SELECT id FROM support_tickets WHERE ticket_number = ?", [$number])) {
                return $number;
            }
        }

        return 'SUP-' . date('ymdHis') . '-' . random_int(100, 999);
    }

    private static function newToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (Database::fetch("SELECT id FROM support_tickets WHERE secure_token = ?", [$token]));

        return $token;
    }
}
