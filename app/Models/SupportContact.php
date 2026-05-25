<?php

namespace App\Models;

use App\Core\Database;

/**
 * Contato de suporte centralizado por email.
 *
 * O contato pode nascer sem acesso a funil nenhum. Quando esse mesmo email
 * vira membro depois, os tickets ficam vinculados ao acesso novo.
 */
class SupportContact extends BaseModel
{
    protected static string $table = 'support_contacts';
    protected static bool $useUuid = false;
    protected static array $fillable = [
        'email', 'name', 'phone', 'member_id', 'last_funnel_id'
    ];

    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetch(
            "SELECT * FROM support_contacts WHERE email = ?",
            [self::normalizeEmail($email)]
        );
    }

    public static function findOrCreate(array $data): array
    {
        $email = self::normalizeEmail((string) ($data['email'] ?? ''));
        if ($email === '') {
            throw new \InvalidArgumentException('Email do contato de suporte vazio.');
        }

        $existing = self::findByEmail($email);
        if ($existing) {
            $updates = [];

            if (!empty($data['name']) && empty($existing['name'])) {
                $updates['name'] = trim((string) $data['name']);
            }
            if (!empty($data['phone']) && empty($existing['phone'])) {
                $updates['phone'] = preg_replace('/[^0-9+]/', '', (string) $data['phone']);
            }
            if (!empty($data['member_id'])) {
                $updates['member_id'] = (int) $data['member_id'];
            }
            if (!empty($data['last_funnel_id'])) {
                $updates['last_funnel_id'] = (int) $data['last_funnel_id'];
            }

            if (!empty($updates)) {
                self::update((int) $existing['id'], $updates);
                $existing = self::find((int) $existing['id']);
            }

            return $existing;
        }

        $id = self::create([
            'email' => $email,
            'name' => !empty($data['name']) ? trim((string) $data['name']) : null,
            'phone' => !empty($data['phone']) ? preg_replace('/[^0-9+]/', '', (string) $data['phone']) : null,
            'member_id' => !empty($data['member_id']) ? (int) $data['member_id'] : null,
            'last_funnel_id' => !empty($data['last_funnel_id']) ? (int) $data['last_funnel_id'] : null,
        ]);

        return self::find($id);
    }

    public static function linkMemberByEmail(string $email, int $memberId, int $funnelId): void
    {
        $email = self::normalizeEmail($email);
        if ($email === '' || $memberId <= 0 || $funnelId <= 0) {
            return;
        }

        $contact = self::findByEmail($email);
        if (!$contact) {
            return;
        }

        self::fillMemberFromContact($memberId, $contact);

        Database::query(
            "UPDATE support_contacts
             SET member_id = ?, last_funnel_id = ?, updated_at = NOW()
             WHERE id = ?",
            [$memberId, $funnelId, $contact['id']]
        );

        Database::query(
            "UPDATE support_tickets
             SET member_id = COALESCE(member_id, ?),
                 funnel_id = IF(funnel_id IS NULL, ?, funnel_id),
                 updated_at = NOW()
             WHERE support_contact_id = ?
               AND (funnel_id IS NULL OR funnel_id = ?)",
            [$memberId, $funnelId, $contact['id'], $funnelId]
        );
    }

    public static function linkFunnelMembersByEmails(int $funnelId, array $emails): void
    {
        $emails = array_values(array_unique(array_filter(array_map([self::class, 'normalizeEmail'], $emails))));
        if ($funnelId <= 0 || empty($emails)) {
            return;
        }

        foreach (array_chunk($emails, 500) as $chunk) {
            $placeholders = implode(', ', array_fill(0, count($chunk), '?'));

            Database::query(
                "UPDATE members m
                 INNER JOIN support_contacts sc ON sc.email = LOWER(m.email)
                 SET m.name = IF((m.name IS NULL OR m.name = '' OR m.name = 'Importado') AND sc.name IS NOT NULL AND sc.name <> '', sc.name, m.name),
                     m.phone = IF((m.phone IS NULL OR m.phone = '') AND sc.phone IS NOT NULL AND sc.phone <> '', sc.phone, m.phone),
                     m.updated_at = NOW()
                 WHERE m.funnel_id = ? AND sc.email IN ({$placeholders})",
                array_merge([$funnelId], $chunk)
            );

            Database::query(
                "UPDATE support_contacts sc
                 INNER JOIN members m ON LOWER(m.email) = sc.email AND m.funnel_id = ?
                 SET sc.member_id = m.id,
                     sc.last_funnel_id = ?,
                     sc.name = COALESCE(NULLIF(sc.name, ''), m.name),
                     sc.phone = COALESCE(NULLIF(sc.phone, ''), m.phone),
                     sc.updated_at = NOW()
                 WHERE sc.email IN ({$placeholders})",
                array_merge([$funnelId, $funnelId], $chunk)
            );

            Database::query(
                "UPDATE support_tickets st
                 INNER JOIN support_contacts sc ON sc.id = st.support_contact_id
                 INNER JOIN members m ON LOWER(m.email) = sc.email AND m.funnel_id = ?
                 SET st.member_id = COALESCE(st.member_id, m.id),
                     st.funnel_id = IF(st.funnel_id IS NULL, ?, st.funnel_id),
                     st.updated_at = NOW()
                 WHERE sc.email IN ({$placeholders})
                   AND (st.funnel_id IS NULL OR st.funnel_id = ?)",
                array_merge([$funnelId, $funnelId], $chunk, [$funnelId])
            );
        }
    }

    private static function fillMemberFromContact(int $memberId, array $contact): void
    {
        $member = Member::find($memberId);
        if (!$member) {
            return;
        }

        $updates = [];
        if (!empty($contact['name']) && (empty($member['name']) || $member['name'] === 'Importado')) {
            $updates['name'] = $contact['name'];
        }
        if (!empty($contact['phone']) && empty($member['phone'])) {
            $updates['phone'] = $contact['phone'];
        }

        if (!empty($updates)) {
            Member::update($memberId, $updates);
        }
    }
}
