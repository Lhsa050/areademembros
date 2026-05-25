<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Funnel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Serviço de Email via SMTP
 */
class EmailService
{
    private ?PHPMailer $mailer = null;
    private ?int $funnelId = null;

    public function __construct(?int $funnelId = null)
    {
        $this->funnelId = $funnelId ?: null;
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default, $this->funnelId);
    }

    private function useFunnel(?int $funnelId): void
    {
        if ($funnelId && $this->funnelId !== $funnelId) {
            $this->funnelId = $funnelId;
            $this->mailer = null;
        }
    }

    private function resolveFunnelId(?string $funnelSlug = null, ?array $product = null, ?array $member = null): ?int
    {
        if (!empty($product['funnel_id'])) {
            return (int) $product['funnel_id'];
        }

        if (!empty($member['funnel_id'])) {
            return (int) $member['funnel_id'];
        }

        if ($funnelSlug) {
            $funnel = Funnel::findBySlug($funnelSlug);
            return $funnel ? (int) $funnel['id'] : null;
        }

        return null;
    }

    /**
     * Variáveis disponíveis para templates
     */
    public static function getAvailableVariables(): array
    {
        return [
            'email_acesso' => [
                '{{nome_do_comprador}}' => 'Nome do membro',
                '{{email_do_comprador}}' => 'Email do membro',
                '{{cpf_do_comprador}}' => 'CPF do membro',
                '{{telefone_do_comprador}}' => 'Telefone do membro',
                '{{senha}}' => 'Senha (se configurada)',
                '{{link_de_acesso}}' => 'Link para login na área de membros',
                '{{nome_do_app}}' => 'Nome da aplicação',
            ],
            'email_compra_aprovada' => [
                '{{nome_do_comprador}}' => 'Nome do membro',
                '{{email_do_comprador}}' => 'Email do membro',
                '{{produto_comprado}}' => 'Nome do produto comprado',
                '{{link_de_acesso}}' => 'Link para login na área de membros',
                '{{nome_do_app}}' => 'Nome da aplicação',
            ],
            'email_acesso_removido' => [
                '{{nome_do_comprador}}' => 'Nome do membro',
                '{{email_do_comprador}}' => 'Email do membro',
                '{{produto_comprado}}' => 'Nome do produto removido',
                '{{nome_do_app}}' => 'Nome da aplicação',
            ],
        ];
    }

    /**
     * Dados de exemplo para preview
     */
    public static function getExampleData(?int $funnelId = null): array
    {
        $appName = Setting::get('app_name', 'Area de Membros', $funnelId);
        return [
            '{{nome_do_comprador}}' => 'João Silva',
            '{{email_do_comprador}}' => 'joao@email.com',
            '{{cpf_do_comprador}}' => '123.456.789-00',
            '{{telefone_do_comprador}}' => '(11) 99999-8888',
            '{{senha}}' => 'abc123',
            '{{link_de_acesso}}' => rtrim(env('APP_URL', '#'), '/') . '/m/exemplo/login',
            '{{nome_do_app}}' => $appName,
            '{{produto_comprado}}' => 'Curso Completo de Marketing Digital',
        ];
    }

    /**
     * Templates padrão — HTML completo pré-montado
     */
    public static function getDefaultTemplates(?int $funnelId = null): array
    {
        $primaryColor = Setting::get('email_primary_color', '#0ea5e9', $funnelId);

        return [
            'email_acesso_subject' => 'Bem-vindo(a) à {{nome_do_app}} - Dados de Acesso',
            'email_acesso_body' => '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;">
<div style="max-width:600px;margin:0 auto;padding:40px 20px;">
<div style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

<!-- Header -->
<div style="background:linear-gradient(135deg,' . $primaryColor . ',#1e40af);padding:40px 40px 30px;text-align:center;">
<h1 style="color:#ffffff;font-size:24px;margin:0 0 8px;">Bem-vindo(a)! 🎉</h1>
<p style="color:rgba(255,255,255,0.85);font-size:14px;margin:0;">Sua conta foi criada com sucesso</p>
</div>

<!-- Conteúdo -->
<div style="padding:40px;">
<p style="color:#334155;font-size:16px;line-height:1.7;margin:0 0 20px;">
Olá, <strong>{{nome_do_comprador}}</strong>!
</p>
<p style="color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px;">
Estamos muito felizes em ter você na <strong>{{nome_do_app}}</strong>. Abaixo estão seus dados de acesso:
</p>

<!-- Dados de Acesso -->
<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:28px;">
<table style="width:100%;border-collapse:collapse;">
<tr>
<td style="padding:8px 12px;color:#64748b;font-size:13px;font-weight:600;width:100px;">📧 Email</td>
<td style="padding:8px 12px;color:#1e293b;font-size:14px;">{{email_do_comprador}}</td>
</tr>
<tr>
<td style="padding:8px 12px;color:#64748b;font-size:13px;font-weight:600;">🔑 Senha</td>
<td style="padding:8px 12px;color:#1e293b;font-size:14px;font-family:monospace;">{{senha}}</td>
</tr>
</table>
</div>

<!-- Botão -->
<div style="text-align:center;margin:32px 0;">
<a href="{{link_de_acesso}}" style="background:' . $primaryColor . ';color:#ffffff;padding:16px 40px;border-radius:10px;text-decoration:none;font-weight:700;font-size:16px;display:inline-block;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
Acessar Minha Conta →
</a>
</div>

<p style="color:#94a3b8;font-size:13px;text-align:center;margin:0;">
Se o botão não funcionar, copie e cole este link: {{link_de_acesso}}
</p>
</div>

<!-- Rodapé -->
<div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:24px 40px;text-align:center;">
<p style="color:#94a3b8;font-size:12px;margin:0;">© 2024 {{nome_do_app}} — Todos os direitos reservados</p>
</div>

</div>
</div>
</body>
</html>',

            'email_compra_aprovada_subject' => 'Compra Aprovada - {{produto_comprado}} | {{nome_do_app}}',
            'email_compra_aprovada_body' => '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;">
<div style="max-width:600px;margin:0 auto;padding:40px 20px;">
<div style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

<!-- Header -->
<div style="background:linear-gradient(135deg,#10b981,#059669);padding:40px 40px 30px;text-align:center;">
<h1 style="color:#ffffff;font-size:24px;margin:0 0 8px;">Compra Aprovada! ✅</h1>
<p style="color:rgba(255,255,255,0.85);font-size:14px;margin:0;">Seu produto já está disponível</p>
</div>

<!-- Conteúdo -->
<div style="padding:40px;">
<p style="color:#334155;font-size:16px;line-height:1.7;margin:0 0 20px;">
Olá, <strong>{{nome_do_comprador}}</strong>!
</p>
<p style="color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px;">
Sua compra foi confirmada e o produto já está liberado na sua área de membros.
</p>

<!-- Produto -->
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:20px;margin-bottom:28px;text-align:center;">
<p style="color:#64748b;font-size:12px;font-weight:600;text-transform:uppercase;margin:0 0 4px;">Produto Liberado</p>
<p style="color:#166534;font-size:18px;font-weight:700;margin:0;">{{produto_comprado}}</p>
</div>

<!-- Botão -->
<div style="text-align:center;margin:32px 0;">
<a href="{{link_de_acesso}}" style="background:#10b981;color:#ffffff;padding:16px 40px;border-radius:10px;text-decoration:none;font-weight:700;font-size:16px;display:inline-block;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
Acessar Meu Conteúdo →
</a>
</div>
</div>

<!-- Rodapé -->
<div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:24px 40px;text-align:center;">
<p style="color:#94a3b8;font-size:12px;margin:0;">© 2024 {{nome_do_app}} — Todos os direitos reservados</p>
</div>

</div>
</div>
</body>
</html>',

            'email_acesso_removido_subject' => 'Acesso Removido - {{produto_comprado}} | {{nome_do_app}}',
            'email_acesso_removido_body' => '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;">
<div style="max-width:600px;margin:0 auto;padding:40px 20px;">
<div style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

<!-- Header -->
<div style="background:linear-gradient(135deg,#ef4444,#dc2626);padding:40px 40px 30px;text-align:center;">
<h1 style="color:#ffffff;font-size:24px;margin:0 0 8px;">Acesso Removido</h1>
<p style="color:rgba(255,255,255,0.85);font-size:14px;margin:0;">Reembolso processado</p>
</div>

<!-- Conteúdo -->
<div style="padding:40px;">
<p style="color:#334155;font-size:16px;line-height:1.7;margin:0 0 20px;">
Olá, <strong>{{nome_do_comprador}}</strong>.
</p>
<p style="color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px;">
Informamos que o acesso ao produto abaixo foi removido devido ao reembolso da compra.
</p>

<!-- Produto -->
<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:20px;margin-bottom:28px;text-align:center;">
<p style="color:#64748b;font-size:12px;font-weight:600;text-transform:uppercase;margin:0 0 4px;">Produto Removido</p>
<p style="color:#991b1b;font-size:18px;font-weight:700;margin:0;">{{produto_comprado}}</p>
</div>

<p style="color:#475569;font-size:14px;line-height:1.7;margin:0;">
Se você tiver alguma dúvida sobre este processo, entre em contato com nosso suporte.
</p>
</div>

<!-- Rodapé -->
<div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:24px 40px;text-align:center;">
<p style="color:#94a3b8;font-size:12px;margin:0;">© 2024 {{nome_do_app}} — Todos os direitos reservados</p>
</div>

</div>
</div>
</body>
</html>',
        ];
    }

    /**
     * Substitui variáveis no template
     */
    private function replaceVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace($key, $value ?? '', $template);
        }
        return $template;
    }

    /**
     * Inicializa o PHPMailer com configs SMTP do banco
     */
    private function getMailer(): PHPMailer
    {
        if ($this->mailer !== null) {
            return $this->mailer;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->SMTPAuth = true;

        $encryption = $this->setting('smtp_encryption', 'tls');
        switch ($encryption) {
            case 'ssl':
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                break;
            case 'none':
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
                break;
            case 'tls':
            default:
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                break;
        }

        $mail->Host = $this->setting('smtp_host', '');
        $mail->Port = (int) $this->setting('smtp_port', 587);
        $mail->Username = $this->setting('smtp_user', '');
        $mail->Password = $this->setting('smtp_pass', '');

        $fromEmail = $this->setting('smtp_from_email', '');
        $fromName = $this->setting('smtp_from_name', 'Area de Membros');

        if ($fromEmail) {
            $mail->setFrom($fromEmail, $fromName);
        }

        $this->mailer = $mail;
        return $mail;
    }

    /**
     * Verifica se o SMTP está configurado
     */
    public function isConfigured(): bool
    {
        $host = $this->setting('smtp_host', '');
        $user = $this->setting('smtp_user', '');
        return !empty($host) && !empty($user);
    }

    /**
     * Envia email
     */
    private function send(string $to, string $toName, string $subject, string $bodyHtml): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $mail = clone $this->getMailer();
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</td>'], "\n", $bodyHtml));
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("EmailService Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envia um email HTML simples usando o SMTP do funil atual.
     */
    public function sendRawEmail(string $to, string $toName, string $subject, string $bodyHtml): bool
    {
        return $this->send($to, $toName, $subject, $bodyHtml);
    }

    /**
     * Envia email de teste
     */
    public function sendTestEmail(string $to): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'SMTP não configurado. Preencha os campos de Host e Usuário.'];
        }

        try {
            $mail = clone $this->getMailer();
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = 'Email de Teste - ' . $this->setting('app_name', 'Area de Membros');

            $primaryColor = $this->setting('email_primary_color', '#0ea5e9');
        $appName = $this->setting('app_name', 'Area de Membros');

            $html = '<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Segoe UI,Arial,sans-serif;">
<div style="max-width:600px;margin:0 auto;padding:40px 20px;">
<div style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
<div style="background:linear-gradient(135deg,' . $primaryColor . ',#1e40af);padding:40px;text-align:center;">
<h1 style="color:#fff;font-size:24px;margin:0;">Email de Teste ✅</h1>
</div>
<div style="padding:40px;">
<p style="color:#334155;font-size:16px;line-height:1.7;">Seu SMTP está funcionando corretamente!</p>
<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin:20px 0;">
<p style="color:#64748b;font-size:13px;margin:0;"><strong>Host:</strong> ' . $this->setting('smtp_host', '') . '<br>
<strong>Porta:</strong> ' . $this->setting('smtp_port', 587) . '<br>
<strong>Criptografia:</strong> ' . strtoupper($this->setting('smtp_encryption', 'tls')) . '</p>
</div>
</div>
<div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:24px 40px;text-align:center;">
<p style="color:#94a3b8;font-size:12px;margin:0;">© ' . date('Y') . ' ' . $appName . '</p>
</div>
</div>
</div>
</body></html>';

            $mail->Body = $html;
            $mail->AltBody = 'Email de teste - SMTP funcionando corretamente!';
            $mail->send();

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Monta URL de login do funil
     */
    private function buildLoginUrl(?string $funnelSlug = null): string
    {
        $appUrl = rtrim(env('APP_URL', ''), '/');
        if ($funnelSlug) {
            return $appUrl . '/m/' . $funnelSlug . '/login';
        }
        return $appUrl;
    }

    /**
     * Email de acesso (novo membro)
     */
    public function sendAccessEmail(array $member, ?string $password = null, ?string $funnelSlug = null): bool
    {
        $this->useFunnel($this->resolveFunnelId($funnelSlug, null, $member));

        $appName = $this->setting('app_name', 'Area de Membros');
        $loginUrl = $this->buildLoginUrl($funnelSlug);
        $defaults = self::getDefaultTemplates($this->funnelId);

        $variables = [
            '{{nome_do_comprador}}' => $member['name'] ?? '',
            '{{email_do_comprador}}' => $member['email'] ?? '',
            '{{cpf_do_comprador}}' => $member['cpf'] ?? '',
            '{{telefone_do_comprador}}' => $member['phone'] ?? '',
            '{{senha}}' => $password ?? '(login sem senha)',
            '{{link_de_acesso}}' => $loginUrl,
            '{{nome_do_app}}' => $appName,
        ];

        $subject = $this->setting('email_acesso_subject', $defaults['email_acesso_subject']);
        $body = $this->setting('email_acesso_body', $defaults['email_acesso_body']);

        $subject = $this->replaceVariables($subject, $variables);
        $body = $this->replaceVariables($body, $variables);

        return $this->send($member['email'], $member['name'], $subject, $body);
    }

    /**
     * Email de compra aprovada
     */
    public function sendPurchaseApprovedEmail(array $member, array $product, ?string $funnelSlug = null): bool
    {
        $this->useFunnel($this->resolveFunnelId($funnelSlug, $product, $member));

        $appName = $this->setting('app_name', 'Area de Membros');
        $loginUrl = $this->buildLoginUrl($funnelSlug);
        $defaults = self::getDefaultTemplates($this->funnelId);

        $variables = [
            '{{nome_do_comprador}}' => $member['name'] ?? '',
            '{{email_do_comprador}}' => $member['email'] ?? '',
            '{{produto_comprado}}' => $product['title'] ?? '',
            '{{link_de_acesso}}' => $loginUrl,
            '{{nome_do_app}}' => $appName,
        ];

        $subject = $this->setting('email_compra_aprovada_subject', $defaults['email_compra_aprovada_subject']);
        $body = $this->setting('email_compra_aprovada_body', $defaults['email_compra_aprovada_body']);

        $subject = $this->replaceVariables($subject, $variables);
        $body = $this->replaceVariables($body, $variables);

        return $this->send($member['email'], $member['name'], $subject, $body);
    }

    /**
     * Email de produto revogado (reembolso)
     */
    public function sendProductRevokedEmail(array $member, array $product): bool
    {
        $this->useFunnel($this->resolveFunnelId(null, $product, $member));

        $appName = $this->setting('app_name', 'Area de Membros');
        $defaults = self::getDefaultTemplates($this->funnelId);

        $variables = [
            '{{nome_do_comprador}}' => $member['name'] ?? '',
            '{{email_do_comprador}}' => $member['email'] ?? '',
            '{{produto_comprado}}' => $product['title'] ?? '',
            '{{nome_do_app}}' => $appName,
        ];

        $subject = $this->setting('email_acesso_removido_subject', $defaults['email_acesso_removido_subject']);
        $body = $this->setting('email_acesso_removido_body', $defaults['email_acesso_removido_body']);

        $subject = $this->replaceVariables($subject, $variables);
        $body = $this->replaceVariables($body, $variables);

        return $this->send($member['email'], $member['name'], $subject, $body);
    }
}
