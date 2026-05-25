<?php

namespace App\Core;

use App\Models\Member;
use App\Models\MemberProduct;
use App\Models\Product;
use App\Models\Setting;

/**
 * Autenticação de Membros — escopado por funil
 * Otimizado: cacheia dados do membro e produtos na sessão
 */
class MemberAuth
{
    /**
     * Tenta login por identificador (sem senha) dentro de um funil
     */
    public static function attempt(string $identifier, int $funnelId, string $funnelSlug): bool
    {
        $loginMode = Setting::get('login_mode', 'email_only', $funnelId);

        $member = null;

        if ($loginMode === 'flexible') {
            // Auto-detecta tipo do identificador
            $member = self::findByFlexibleIdentifier($identifier, $funnelId);
        } else {
            $member = match ($loginMode) {
                'cpf_only' => Member::findByCpf(self::cleanCpf($identifier), $funnelId),
                'phone_only' => Member::findByPhone(self::cleanPhone($identifier), $funnelId),
                default => Member::findByEmail(trim($identifier), $funnelId)
            };
        }

        if (!$member || $member['status'] !== 'active') {
            return false;
        }

        self::loginMember($member, $funnelSlug);
        return true;
    }

    /**
     * Busca membro por identificador flexível (email, CPF ou telefone)
     */
    private static function findByFlexibleIdentifier(string $identifier, int $funnelId): ?array
    {
        $cleaned = trim($identifier);

        // Se contém @, tenta como email primeiro
        if (str_contains($cleaned, '@')) {
            $member = Member::findByEmail($cleaned, $funnelId);
            if ($member) return $member;
        }

        // Limpa para dígitos
        $digits = preg_replace('/[^0-9]/', '', $cleaned);

        // Se tem 11 dígitos, pode ser CPF ou telefone
        if (strlen($digits) === 11) {
            // Tenta CPF primeiro
            $member = Member::findByCpf($digits, $funnelId);
            if ($member) return $member;

            // Tenta telefone
            $member = Member::findByPhone($digits, $funnelId);
            if ($member) return $member;
        }

        // Se tem 10 dígitos, pode ser telefone fixo
        if (strlen($digits) === 10) {
            $member = Member::findByPhone($digits, $funnelId);
            if ($member) return $member;
        }

        // Se tem dígitos mas não bateu com nada, tenta CPF e telefone genérico
        if (!empty($digits)) {
            $member = Member::findByCpf($digits, $funnelId);
            if ($member) return $member;

            $member = Member::findByPhone($digits, $funnelId);
            if ($member) return $member;
        }

        // Última tentativa: email
        $member = Member::findByEmail($cleaned, $funnelId);
        return $member;
    }

    /**
     * Tenta login com email + senha dentro de um funil
     */
    public static function attemptWithPassword(string $email, string $password, int $funnelId, string $funnelSlug): bool
    {
        $member = Member::findByEmail(trim($email), $funnelId);

        if (!$member || $member['status'] !== 'active') {
            return false;
        }

        if (empty($member['password'])) {
            return false;
        }

        if (!password_verify($password, $member['password'])) {
            return false;
        }

        self::loginMember($member, $funnelSlug);
        return true;
    }

    /**
     * Faz o login do membro na sessão — cacheia tudo
     */
    private static function loginMember(array $member, string $funnelSlug): void
    {
        session_regenerate_id(true);

        // Dados básicos
        $_SESSION['member_id'] = $member['id'];
        $_SESSION['member_uuid'] = $member['uuid'];
        $_SESSION['member_name'] = $member['name'];
        $_SESSION['member_email'] = $member['email'];
        $_SESSION['member_funnel_id'] = $member['funnel_id'];
        $_SESSION['member_funnel_slug'] = $funnelSlug;

        // Cache: dados completos do membro (evita query Member::find a cada page view)
        $_SESSION['member_data'] = $member;

        // Cache: IDs dos produtos que o membro tem acesso (evita query a cada page view)
        $_SESSION['member_product_ids'] = MemberProduct::getActiveProductIds($member['id']);
        self::refreshPublicProductCache((int) $member['funnel_id']);

        // Cache: datas de concessão por produto (para drip content, evita query por produto)
        $activeProducts = \App\Core\Database::fetchAll(
            "SELECT product_id, granted_at FROM member_products WHERE member_id = ? AND revoked_at IS NULL",
            [$member['id']]
        );
        $grantedMap = [];
        foreach ($activeProducts as $ap) {
            $grantedMap[$ap['product_id']] = $ap['granted_at'];
        }
        $_SESSION['member_product_granted_at'] = $grantedMap;

        // Timestamp do cache (para invalidação por TTL)
        $_SESSION['member_cache_at'] = time();
        $_SESSION['member_login_time'] = time();
        $_SESSION['member_last_check'] = time();

        // Atualiza last_login (query única no login, não a cada page)
        Member::update($member['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }

    /**
     * Verifica se há membro logado no escopo do funil atual
     * Verifica validade da sessão a cada 30s
     */
    public static function check(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['member_id']) || !isset($_SESSION['member_funnel_slug'])) {
            return false;
        }

        // Verifica se precisa validar sessão (a cada 15 seg)
        if (time() - ($_SESSION['member_last_check'] ?? 0) > 15) {
            $member = Member::find($_SESSION['member_id']);
            
            // Se membro foi deletado ou inativado
            if (!$member || $member['status'] !== 'active') {
                self::logout();
                return false;
            }

            // Se dados mudaram (ex: novos produtos adicionados pelo admin)
            if (strtotime($member['updated_at']) > ($_SESSION['member_login_time'] ?? 0)) {
                self::loginMember($member, $_SESSION['member_funnel_slug']);
            } else {
                self::refreshPublicProductCache((int) $_SESSION['member_funnel_id']);
                $_SESSION['member_last_check'] = time();
            }
        }

        return true;
    }

    /**
     * Retorna dados do membro logado
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return $_SESSION['member_data'] ?? null;
    }

    /**
     * ID do membro logado
     */
    public static function id(): ?int
    {
        return $_SESSION['member_id'] ?? null;
    }

    /**
     * IDs dos produtos que o membro tem acesso (da sessão)
     */
    public static function productIds(): array
    {
        $memberProductIds = array_map('intval', $_SESSION['member_product_ids'] ?? []);
        $publicProductIds = array_map('intval', $_SESSION['member_public_product_ids'] ?? []);

        return array_values(array_unique(array_merge($memberProductIds, $publicProductIds)));
    }

    /**
     * Verifica se membro tem acesso a um produto (da sessão, sem query)
     */
    public static function hasProductAccess(int $productId): bool
    {
        if (in_array($productId, self::productIds(), true)) {
            return true;
        }

        $funnelId = self::funnelId();
        if (!$funnelId) {
            return false;
        }

        if (Product::isPublicForFunnel($productId, (int) $funnelId)) {
            $publicProductIds = array_map('intval', $_SESSION['member_public_product_ids'] ?? []);
            $publicProductIds[] = $productId;
            $_SESSION['member_public_product_ids'] = array_values(array_unique($publicProductIds));
            $_SESSION['member_public_products_cache_at'] = time();
            return true;
        }

        return false;
    }

    /**
     * Retorna a data de concessão de um produto (da sessão, sem query)
     */
    public static function productGrantedAt(int $productId): ?string
    {
        $map = $_SESSION['member_product_granted_at'] ?? [];
        return $map[$productId] ?? null;
    }

    /**
     * Recarrega o cache de produtos do banco (chamado quando webhook concede acesso)
     */
    public static function refreshCache(): void
    {
        if (!self::check()) return;

        $member = Member::find($_SESSION['member_id']);
        if ($member) {
            $_SESSION['member_data'] = $member;
            $_SESSION['member_product_ids'] = MemberProduct::getActiveProductIds($member['id']);
            self::refreshPublicProductCache((int) $member['funnel_id']);

            // Refresh granted_at map
            $activeProducts = \App\Core\Database::fetchAll(
                "SELECT product_id, granted_at FROM member_products WHERE member_id = ? AND revoked_at IS NULL",
                [$member['id']]
            );
            $grantedMap = [];
            foreach ($activeProducts as $ap) {
                $grantedMap[$ap['product_id']] = $ap['granted_at'];
            }
            $_SESSION['member_product_granted_at'] = $grantedMap;

            $_SESSION['member_cache_at'] = time();
        }
    }

    /**
     * ID do funil do membro logado
     */
    public static function funnelId(): ?int
    {
        return $_SESSION['member_funnel_id'] ?? null;
    }

    /**
     * Slug do funil do membro logado
     */
    public static function funnelSlug(): ?string
    {
        return $_SESSION['member_funnel_slug'] ?? null;
    }

    /**
     * Faz logout do membro
     */
    public static function logout(): void
    {
        unset(
            $_SESSION['member_id'],
            $_SESSION['member_uuid'],
            $_SESSION['member_name'],
            $_SESSION['member_email'],
            $_SESSION['member_funnel_id'],
            $_SESSION['member_funnel_slug'],
            $_SESSION['member_data'],
            $_SESSION['member_product_ids'],
            $_SESSION['member_public_product_ids'],
            $_SESSION['member_public_products_cache_at'],
            $_SESSION['member_product_granted_at'],
            $_SESSION['member_cache_at'],
            $_SESSION['member_login_time'],
            $_SESSION['member_last_check']
        );
        session_regenerate_id(true);
    }

    /**
     * Requer autenticação de membro — redireciona para login do funil
     */
    public static function require(?string $slug = null): void
    {
        if (!self::check()) {
            $funnelSlug = $slug ?? self::funnelSlug() ?? 'login';
            redirect(url('/m/' . $funnelSlug . '/login'));
        }
    }

    /**
     * Limpa CPF (remove . e -)
     */
    private static function cleanCpf(string $cpf): string
    {
        return preg_replace('/[^0-9]/', '', $cpf);
    }

    private static function refreshPublicProductCache(?int $funnelId = null): void
    {
        $funnelId = $funnelId ?? ($_SESSION['member_funnel_id'] ?? null);
        if (!$funnelId) {
            $_SESSION['member_public_product_ids'] = [];
            return;
        }

        $_SESSION['member_public_product_ids'] = Product::getPublicProductIdsByFunnel((int) $funnelId);
        $_SESSION['member_public_products_cache_at'] = time();
    }

    /**
     * Limpa telefone
     */
    private static function cleanPhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}
