<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Produtos do Membro (relacionamento N:N)
 */
class MemberProduct extends BaseModel
{
    protected static string $table = 'member_products';
    protected static bool $useUuid = false;
    protected static bool $timestamps = false;
    protected static array $fillable = [
        'member_id', 'product_id', 'granted_by', 'granted_at', 'revoked_at'
    ];

    /**
     * Concede produto a um membro
     */
    public static function grant(int $memberId, int $productId, string $grantedBy = 'webhook'): bool
    {
        // Verifica se já existe (ativo ou revogado)
        $existing = Database::fetch(
            "SELECT id, revoked_at FROM member_products WHERE member_id = ? AND product_id = ?",
            [$memberId, $productId]
        );

        if ($existing) {
            // Se existe mas estava revogado, reativa
            if ($existing['revoked_at'] !== null) {
                Database::query(
                    "UPDATE member_products SET revoked_at = NULL, granted_by = ?, granted_at = NOW() WHERE id = ?",
                    [$grantedBy, $existing['id']]
                );
                self::touchMember($memberId);
                return true;
            }
            // Já tem acesso ativo
            return false;
        }

        // Cria novo
        Database::query(
            "INSERT INTO member_products (member_id, product_id, granted_by, granted_at) VALUES (?, ?, ?, NOW())",
            [$memberId, $productId, $grantedBy]
        );
        self::touchMember($memberId);
        return true;
    }

    /**
     * Revoga produto de um membro (soft delete)
     */
    public static function revoke(int $memberId, int $productId): bool
    {
        $existing = Database::fetch(
            "SELECT id FROM member_products WHERE member_id = ? AND product_id = ? AND revoked_at IS NULL",
            [$memberId, $productId]
        );

        if (!$existing) {
            return false;
        }

        Database::query(
            "UPDATE member_products SET revoked_at = NOW() WHERE id = ?",
            [$existing['id']]
        );
        self::touchMember($memberId);
        return true;
    }

    /**
     * Revoga somente acessos liberados por webhook.
     */
    public static function revokeWebhook(int $memberId, int $productId): bool
    {
        $existing = Database::fetch(
            "SELECT id FROM member_products
             WHERE member_id = ? AND product_id = ? AND revoked_at IS NULL AND granted_by = 'webhook'",
            [$memberId, $productId]
        );

        if (!$existing) {
            return false;
        }

        Database::query(
            "UPDATE member_products SET revoked_at = NOW() WHERE id = ?",
            [$existing['id']]
        );
        self::touchMember($memberId);
        return true;
    }

    private static function touchMember(int $memberId): void
    {
        Database::query("UPDATE members SET updated_at = NOW() WHERE id = ?", [$memberId]);
    }

    /**
     * Retorna produtos ativos de um membro
     */
    public static function getActiveByMember(int $memberId): array
    {
        if (!self::supportsFunnelProducts()) {
            return Database::fetchAll(
                "SELECT mp.*, p.title as product_title, p.funnel_id, f.name as funnel_name
                 FROM member_products mp
                 INNER JOIN products p ON p.id = mp.product_id
                 LEFT JOIN funnels f ON f.id = p.funnel_id
                 WHERE mp.member_id = ? AND mp.revoked_at IS NULL
                 ORDER BY mp.granted_at DESC",
                [$memberId]
            );
        }

        return Database::fetchAll(
            "SELECT mp.*,
                    p.title as product_title,
                    COALESCE(fp.funnel_id, p.funnel_id) AS funnel_id,
                    f.name as funnel_name
             FROM member_products mp
             INNER JOIN members m ON m.id = mp.member_id
             INNER JOIN products p ON p.id = mp.product_id
             LEFT JOIN funnel_products fp ON fp.product_id = p.id AND fp.funnel_id = m.funnel_id
             LEFT JOIN funnels f ON f.id = COALESCE(fp.funnel_id, p.funnel_id)
             WHERE mp.member_id = ? AND mp.revoked_at IS NULL
             ORDER BY mp.granted_at DESC",
            [$memberId]
        );
    }

    /**
     * Retorna membros que possuem um produto
     */
    public static function getMembersByProduct(int $productId): array
    {
        return Database::fetchAll(
            "SELECT m.*, mp.granted_at, mp.granted_by
             FROM members m
             INNER JOIN member_products mp ON mp.member_id = m.id
             WHERE mp.product_id = ? AND mp.revoked_at IS NULL
             ORDER BY mp.granted_at DESC",
            [$productId]
        );
    }

    /**
     * Retorna IDs dos produtos ativos de um membro
     */
    public static function getActiveProductIds(int $memberId): array
    {
        $rows = Database::fetchAll(
            "SELECT product_id FROM member_products WHERE member_id = ? AND revoked_at IS NULL",
            [$memberId]
        );
        return array_column($rows, 'product_id');
    }

    /**
     * Verifica se membro tem acesso a um produto
     */
    public static function hasAccess(int $memberId, int $productId): bool
    {
        $row = Database::fetch(
            "SELECT id FROM member_products WHERE member_id = ? AND product_id = ? AND revoked_at IS NULL",
            [$memberId, $productId]
        );
        if ($row) {
            return true;
        }

        $member = Database::fetch("SELECT funnel_id FROM members WHERE id = ?", [$memberId]);
        if (!$member) {
            return false;
        }

        return Product::isPublicForFunnel($productId, (int) $member['funnel_id']);
    }

    private static function supportsFunnelProducts(): bool
    {
        static $supports = null;

        if ($supports === null) {
            try {
                $supports = (bool) Database::fetch("SHOW TABLES LIKE 'funnel_products'");
            } catch (\Throwable $e) {
                $supports = false;
            }
        }

        return $supports;
    }
}
