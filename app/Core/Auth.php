<?php

namespace App\Core;

use App\Models\Admin;

/**
 * Classe de Autenticação
 */
class Auth
{
    /**
     * Tenta fazer login
     */
    public static function attempt(string $email, string $password): bool
    {
        $admin = Admin::findByEmail($email);
        
        if (!$admin) {
            return false;
        }

        if ($admin['status'] !== 'active') {
            return false;
        }

        if (!password_verify($password, $admin['password'])) {
            return false;
        }

        // Login bem-sucedido
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_uuid'] = $admin['uuid'];
        $_SESSION['admin_role'] = $admin['role'];

        // Atualiza último login
        Admin::update($admin['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        return true;
    }

    /**
     * Faz logout
     */
    public static function logout(): void
    {
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_uuid']);
        unset($_SESSION['admin_role']);
        session_regenerate_id(true);
    }

    /**
     * Verifica se está logado
     */
    public static function check(): bool
    {
        return isset($_SESSION['admin_id']);
    }

    /**
     * Obtém usuário logado
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return Admin::find($_SESSION['admin_id']);
    }

    /**
     * Obtém ID do usuário logado
     */
    public static function id(): ?int
    {
        return $_SESSION['admin_id'] ?? null;
    }

    /**
     * Verifica se é super admin
     */
    public static function isSuperAdmin(): bool
    {
        return ($_SESSION['admin_role'] ?? '') === 'super_admin';
    }

    /**
     * Requer autenticação (redireciona se não logado)
     */
    public static function require(): void
    {
        if (!self::check()) {
            flash('error', 'Você precisa estar logado para acessar.');
            redirect(url('/login'));
        }
    }

    /**
     * Requer ser super admin
     */
    public static function requireSuperAdmin(): void
    {
        self::require();
        
        if (!self::isSuperAdmin()) {
            flash('error', 'Acesso negado.');
            redirect(url('/dashboard'));
        }
    }
}
