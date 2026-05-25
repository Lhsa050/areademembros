<?php

namespace App\Controllers;

use App\Core\MemberAuth;
use App\Core\Security;
use App\Models\Funnel;
use App\Models\Setting;

/**
 * Controller de Autenticação do Membro (escopado por funil)
 */
class MemberAuthController
{
    /**
     * API: Retorna dados do usuário atual (para frontend com cache)
     */
    public function me(string $slug): void
    {
        header('Content-Type: application/json');
        
        if (!MemberAuth::check() || MemberAuth::funnelSlug() !== $slug) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $user = MemberAuth::user();
        echo json_encode([
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ]);
        exit;
    }

    /**
     * Redireciona para login ou dashboard baseado na sessão
     */
    public function index(string $slug): void
    {
        // Se verificação passar E slug bater, vai pro dashboard
        if (MemberAuth::check() && MemberAuth::funnelSlug() === $slug) {
            redirect(url('/m/' . $slug . '/dashboard'));
        }

        // Senão, vai pro login
        redirect(url('/m/' . $slug . '/login'));
    }

    /**
     * Exibe tela de login do funil
     */
    public function showLogin(string $slug): void
    {
        // Se já logado neste funil, redireciona
        if (MemberAuth::check() && MemberAuth::funnelSlug() === $slug) {
            redirect(url('/m/' . $slug . '/dashboard'));
        }

        $funnel = Funnel::findBySlug($slug);
        if (!$funnel) {
            http_response_code(404);
            echo '<h1>Área de membros não encontrada</h1>';
            exit;
        }

        $loginMode = Setting::get('login_mode', 'email_only', (int) $funnel['id']);
        $appName = $funnel['site_name'] ?: $funnel['name'];

        view('member.login', [
            'loginMode' => $loginMode,
            'appName' => $appName,
            'funnel' => $funnel,
            'slug' => $slug,
            'errors' => $_SESSION['_validation_errors'] ?? [],
            'error' => flash('error'),
            'success' => flash('success')
        ]);

        unset($_SESSION['_validation_errors']);
    }

    /**
     * Processa login
     */
    public function login(string $slug): void
    {
        Security::requireCsrf();

        $funnel = Funnel::findBySlug($slug);
        if (!$funnel) {
            http_response_code(404);
            echo '<h1>Área de membros não encontrada</h1>';
            exit;
        }

        $loginMode = Setting::get('login_mode', 'email_only', (int) $funnel['id']);
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($identifier)) {
            flash('error', 'Preencha o campo de login.');
            redirect(url('/m/' . $slug . '/login'));
        }

        $success = false;

        if ($loginMode === 'password') {
            if (empty($password)) {
                flash('error', 'Preencha a senha.');
                redirect(url('/m/' . $slug . '/login'));
            }
            $success = MemberAuth::attemptWithPassword($identifier, $password, $funnel['id'], $slug);
        } else {
            $success = MemberAuth::attempt($identifier, $funnel['id'], $slug);
        }

        if (!$success) {
            flash('error', 'Credenciais inválidas ou conta desativada.');
            redirect(url('/m/' . $slug . '/login'));
        }

        redirect(url('/m/' . $slug . '/dashboard'));
    }

    /**
     * Logout
     */
    public function logout(string $slug): void
    {
        MemberAuth::logout();
        redirect(url('/m/' . $slug . '/login'));
    }
}
