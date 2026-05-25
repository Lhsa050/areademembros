<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Membros (Usuários/Compradores) — escopado por funil
 */
class Member extends BaseModel
{
    protected static string $table = 'members';
    protected static bool $useUuid = true;
    protected static array $fillable = [
        'uuid', 'funnel_id', 'name', 'email', 'cpf', 'phone', 'password', 'status',
        'last_login_at', 'last_login_ip'
    ];

    /**
     * Busca membro por email dentro de um funil
     */
    public static function findByEmail(string $email, int $funnelId): ?array
    {
        return Database::fetch(
            "SELECT * FROM members WHERE email = ? AND funnel_id = ?",
            [strtolower(trim($email)), $funnelId]
        );
    }

    /**
     * Busca todos os membros com o mesmo email em qualquer funil.
     */
    public static function findAllByEmail(string $email): array
    {
        return Database::fetchAll(
            "SELECT m.*, f.name AS funnel_name, f.slug AS funnel_slug
             FROM members m
             LEFT JOIN funnels f ON f.id = m.funnel_id
             WHERE m.email = ?
             ORDER BY m.created_at DESC",
            [strtolower(trim($email))]
        );
    }

    /**
     * Busca membro por CPF dentro de um funil
     */
    public static function findByCpf(string $cpf, int $funnelId): ?array
    {
        $cleanCpf = preg_replace('/[^0-9]/', '', $cpf);
        return Database::fetch(
            "SELECT * FROM members WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ? AND funnel_id = ?",
            [$cleanCpf, $funnelId]
        );
    }

    /**
     * Busca membro por telefone dentro de um funil
     * O banco armazena com +55, então precisamos normalizar
     */
    public static function findByPhone(string $phone, int $funnelId): ?array
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        // Se o telefone tem 10-11 dígitos (DDD + número), adiciona o código do Brasil (55)
        if (strlen($cleanPhone) === 10 || strlen($cleanPhone) === 11) {
            $cleanPhone = '55' . $cleanPhone;
        }

        return Database::fetch(
            "SELECT * FROM members WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '(', ''), ')', ''), '-', ''), ' ', ''), '+', '') = ? AND funnel_id = ?",
            [$cleanPhone, $funnelId]
        );
    }

    /**
     * Retorna produtos ativos do membro
     */
    public static function getProducts(int $memberId): array
    {
        return Database::fetchAll(
            "SELECT p.*, mp.granted_at, mp.granted_by
             FROM products p
             INNER JOIN member_products mp ON mp.product_id = p.id
             WHERE mp.member_id = ? AND mp.revoked_at IS NULL
             ORDER BY p.title ASC",
            [$memberId]
        );
    }

    /**
     * Verifica se membro tem acesso a um produto
     */
    public static function hasProduct(int $memberId, int $productId): bool
    {
        $result = Database::fetch(
            "SELECT id FROM member_products
             WHERE member_id = ? AND product_id = ? AND revoked_at IS NULL",
            [$memberId, $productId]
        );
        if ($result !== null) {
            return true;
        }

        $member = self::find($memberId);
        if (!$member) {
            return false;
        }

        return Product::isPublicForFunnel($productId, (int) $member['funnel_id']);
    }

    /**
     * Lista membros de um funil com busca opcional
     */
    public static function search(?string $query = null, int $limit = 50, int $offset = 0, ?int $funnelId = null): array
    {
        $where = [];
        $params = [];

        if ($funnelId) {
            $where[] = "funnel_id = ?";
            $params[] = $funnelId;
        }

        if ($query) {
            $search = "%{$query}%";
            $where[] = "(name LIKE ? OR email LIKE ? OR cpf LIKE ? OR phone LIKE ?)";
            $params = array_merge($params, [$search, $search, $search, $search]);
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        return Database::fetchAll(
            "SELECT * FROM members {$whereClause}
             ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    /**
     * Conta membros com busca opcional (escopado por funil)
     */
    public static function countSearch(?string $query = null, ?int $funnelId = null): int
    {
        $where = [];
        $params = [];

        if ($funnelId) {
            $where[] = "funnel_id = ?";
            $params[] = $funnelId;
        }

        if ($query) {
            $search = "%{$query}%";
            $where[] = "(name LIKE ? OR email LIKE ? OR cpf LIKE ? OR phone LIKE ?)";
            $params = array_merge($params, [$search, $search, $search, $search]);
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $result = Database::fetch(
            "SELECT COUNT(*) as total FROM members {$whereClause}",
            $params
        );

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Conta membros de um funil
     */
    public static function countByFunnel(int $funnelId): int
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as total FROM members WHERE funnel_id = ?",
            [$funnelId]
        );
        return (int) ($result['total'] ?? 0);
    }
}
