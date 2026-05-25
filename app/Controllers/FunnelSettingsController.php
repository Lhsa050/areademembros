<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Security;
use App\Models\Funnel;
use App\Models\Setting;

/**
 * Configuracoes que pertencem a um funil especifico.
 */
class FunnelSettingsController
{
    public function index(string $funnelId): void
    {
        Auth::require();

        $funnel = $this->findFunnelOrRedirect($funnelId);
        $funnelSettingId = (int) $funnel['id'];
        $defaults = \App\Services\EmailService::getDefaultTemplates($funnelSettingId);

        $settings = [
            'smtp_host' => Setting::get('smtp_host', '', $funnelSettingId),
            'smtp_port' => Setting::get('smtp_port', '587', $funnelSettingId),
            'smtp_user' => Setting::get('smtp_user', '', $funnelSettingId),
            'smtp_pass' => Setting::get('smtp_pass', '', $funnelSettingId),
            'smtp_from_email' => Setting::get('smtp_from_email', '', $funnelSettingId),
            'smtp_from_name' => Setting::get('smtp_from_name', '', $funnelSettingId),
            'smtp_encryption' => Setting::get('smtp_encryption', 'tls', $funnelSettingId),
            'support_admin_email' => Setting::get('support_admin_email', '', $funnelSettingId),
            'login_mode' => Setting::get('login_mode', 'email_only', $funnelSettingId),
            'default_password' => Setting::get('default_password', '', $funnelSettingId),
            'app_name' => Setting::get('app_name', 'Area de Membros', $funnelSettingId),
            'plyr_enabled' => Setting::get('plyr_enabled', 'true', $funnelSettingId),
            'email_primary_color' => Setting::get('email_primary_color', '#0ea5e9', $funnelSettingId),
            'email_footer_text' => Setting::get('email_footer_text', '', $funnelSettingId),
            'email_acesso_subject' => Setting::get('email_acesso_subject', $defaults['email_acesso_subject'], $funnelSettingId),
            'email_acesso_body' => Setting::get('email_acesso_body', $defaults['email_acesso_body'], $funnelSettingId),
            'email_compra_aprovada_subject' => Setting::get('email_compra_aprovada_subject', $defaults['email_compra_aprovada_subject'], $funnelSettingId),
            'email_compra_aprovada_body' => Setting::get('email_compra_aprovada_body', $defaults['email_compra_aprovada_body'], $funnelSettingId),
            'email_acesso_removido_subject' => Setting::get('email_acesso_removido_subject', $defaults['email_acesso_removido_subject'], $funnelSettingId),
            'email_acesso_removido_body' => Setting::get('email_acesso_removido_body', $defaults['email_acesso_removido_body'], $funnelSettingId),
        ];

        view('admin.settings', [
            'settings' => $settings,
            'emailVariables' => \App\Services\EmailService::getAvailableVariables(),
            'exampleData' => \App\Services\EmailService::getExampleData($funnelSettingId),
            'funnels' => [],
            'selectedFunnel' => $funnel,
            'selectedFunnelId' => $funnelSettingId,
            'settingsAction' => url('/funnels/' . $funnelSettingId . '/settings'),
            'testEmailAction' => url('/funnels/' . $funnelSettingId . '/settings/test-email'),
            'user' => Auth::user()
        ]);
    }

    public function update(string $funnelId): void
    {
        Auth::require();
        Security::requireCsrf();

        $funnel = $this->findFunnelOrRedirect($funnelId);
        $funnelSettingId = (int) $funnel['id'];

        Setting::set('app_name', trim($_POST['app_name'] ?? 'Area de Membros'), $funnelSettingId);

        $plyrEnabled = isset($_POST['plyr_enabled']) ? 'true' : 'false';
        Setting::set('plyr_enabled', $plyrEnabled, $funnelSettingId);

        Setting::set('smtp_host', trim($_POST['smtp_host'] ?? ''), $funnelSettingId);
        Setting::set('smtp_port', trim($_POST['smtp_port'] ?? '587'), $funnelSettingId);
        Setting::set('smtp_user', trim($_POST['smtp_user'] ?? ''), $funnelSettingId);
        if (!empty($_POST['smtp_pass'])) {
            Setting::set('smtp_pass', $_POST['smtp_pass'], $funnelSettingId);
        }
        Setting::set('smtp_from_email', trim($_POST['smtp_from_email'] ?? ''), $funnelSettingId);
        Setting::set('smtp_from_name', trim($_POST['smtp_from_name'] ?? ''), $funnelSettingId);
        Setting::set('support_admin_email', trim($_POST['support_admin_email'] ?? ''), $funnelSettingId);

        $encryption = $_POST['smtp_encryption'] ?? 'tls';
        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            $encryption = 'tls';
        }
        Setting::set('smtp_encryption', $encryption, $funnelSettingId);

        $loginMode = $_POST['login_mode'] ?? 'email_only';
        $validModes = ['password', 'email_only', 'cpf_only', 'phone_only', 'flexible'];
        if (!in_array($loginMode, $validModes, true)) {
            $loginMode = 'email_only';
        }
        Setting::set('login_mode', $loginMode, $funnelSettingId);
        Setting::set('default_password', trim($_POST['default_password'] ?? ''), $funnelSettingId);

        $primaryColor = trim($_POST['email_primary_color'] ?? '#0ea5e9');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $primaryColor)) {
            $primaryColor = '#0ea5e9';
        }
        Setting::set('email_primary_color', $primaryColor, $funnelSettingId);
        Setting::set('email_footer_text', trim($_POST['email_footer_text'] ?? ''), $funnelSettingId);

        $templateKeys = [
            'email_acesso_subject', 'email_acesso_body',
            'email_compra_aprovada_subject', 'email_compra_aprovada_body',
            'email_acesso_removido_subject', 'email_acesso_removido_body',
        ];
        foreach ($templateKeys as $key) {
            if (isset($_POST[$key])) {
                Setting::set($key, $_POST[$key], $funnelSettingId);
            }
        }

        \App\Services\PageCache::clearAll();

        flash('success', 'Configuracoes do funil salvas com sucesso!');
        redirect(url('/funnels/' . $funnelSettingId . '/settings'));
    }

    public function testEmail(string $funnelId): void
    {
        Auth::require();
        Security::requireCsrf();

        $funnel = $this->findFunnelOrRedirect($funnelId);
        $funnelSettingId = (int) $funnel['id'];
        $redirectUrl = url('/funnels/' . $funnelSettingId . '/settings');

        $to = trim($_POST['test_email'] ?? '');
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Informe um email valido para o teste.');
            redirect($redirectUrl);
            return;
        }

        $emailService = new \App\Services\EmailService($funnelSettingId);
        $result = $emailService->sendTestEmail($to);

        if ($result['success']) {
            flash('success', 'Email de teste enviado com sucesso para ' . $to . '! Verifique sua caixa de entrada.');
        } else {
            flash('error', 'Erro ao enviar email de teste: ' . ($result['error'] ?? 'Erro desconhecido'));
        }

        redirect($redirectUrl);
    }

    private function findFunnelOrRedirect(string $funnelId): array
    {
        $funnel = Funnel::find((int) $funnelId);
        if (!$funnel) {
            flash('error', 'Funil nao encontrado.');
            redirect(url('/funnels'));
        }

        return $funnel;
    }
}
