<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Setting;

class SupportNotificationService
{
    public static function notifyAdminTicketOpened(array $ticket, array $message): bool
    {
        return self::notifyAdmin(
            $ticket,
            'Novo ticket de suporte: ' . ($ticket['subject'] ?? ''),
            'Novo ticket aberto',
            $message['message'] ?? ''
        );
    }

    public static function notifyAdminCustomerReply(array $ticket, array $message): bool
    {
        return self::notifyAdmin(
            $ticket,
            'Nova resposta no ticket ' . ($ticket['ticket_number'] ?? ''),
            'Cliente respondeu',
            $message['message'] ?? ''
        );
    }

    public static function notifyCustomerTicketCreated(array $ticket, string $ticketUrl): bool
    {
        $email = $ticket['contact_email'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $body = self::layout(
            'Recebemos sua solicitacao',
            '<p>Seu ticket <strong>' . e($ticket['ticket_number'] ?? '') . '</strong> foi aberto com sucesso.</p>
             <p>Voce pode acompanhar a conversa pelo botao abaixo.</p>' .
             self::button($ticketUrl, 'Abrir conversa')
        );

        return (new EmailService(self::funnelId($ticket)))
            ->sendRawEmail($email, $ticket['contact_name'] ?: $email, 'Seu ticket foi aberto', $body);
    }

    public static function notifyCustomerAdminReply(array $ticket, array $message, string $ticketUrl): bool
    {
        $email = $ticket['contact_email'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $excerpt = nl2br(e(self::excerpt((string) ($message['message'] ?? ''))));
        $body = self::layout(
            'Nova resposta do suporte',
            '<p>O suporte respondeu ao ticket <strong>' . e($ticket['ticket_number'] ?? '') . '</strong>.</p>
             <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin:18px 0;color:#334155;line-height:1.6;">' . $excerpt . '</div>' .
             self::button($ticketUrl, 'Responder conversa')
        );

        return (new EmailService(self::funnelId($ticket)))
            ->sendRawEmail($email, $ticket['contact_name'] ?: $email, 'Nova resposta do suporte', $body);
    }

    private static function notifyAdmin(array $ticket, string $subject, string $title, string $message): bool
    {
        $recipient = self::adminRecipient(self::funnelId($ticket));
        if (!$recipient) {
            return false;
        }

        $ticketUrl = url('/support/tickets/' . (int) ($ticket['id'] ?? 0));
        $customer = trim(($ticket['contact_name'] ?? '') . ' <' . ($ticket['contact_email'] ?? '') . '>');
        $funnel = $ticket['funnel_name'] ?? 'Geral';

        $body = self::layout(
            $title,
            '<p><strong>Ticket:</strong> ' . e($ticket['ticket_number'] ?? '') . '</p>
             <p><strong>Cliente:</strong> ' . e($customer) . '</p>
             <p><strong>Funil:</strong> ' . e($funnel) . '</p>
             <p><strong>Assunto:</strong> ' . e($ticket['subject'] ?? '') . '</p>
             <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin:18px 0;color:#334155;line-height:1.6;">' . nl2br(e(self::excerpt($message))) . '</div>' .
             self::button($ticketUrl, 'Responder no painel')
        );

        return (new EmailService(self::funnelId($ticket)))
            ->sendRawEmail($recipient['email'], $recipient['name'], $subject, $body);
    }

    private static function adminRecipient(?int $funnelId): ?array
    {
        $configured = trim((string) Setting::get('support_admin_email', '', $funnelId));
        if (filter_var($configured, FILTER_VALIDATE_EMAIL)) {
            return ['email' => $configured, 'name' => 'Suporte'];
        }

        $admin = Database::fetch(
            "SELECT name, email
             FROM admins
             WHERE status = 'active'
             ORDER BY CASE WHEN role = 'super_admin' THEN 0 ELSE 1 END, id ASC
             LIMIT 1"
        );

        if ($admin && filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
            return ['email' => $admin['email'], 'name' => $admin['name'] ?: 'Admin'];
        }

        $from = trim((string) Setting::get('smtp_from_email', '', $funnelId));
        if (filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return ['email' => $from, 'name' => 'Suporte'];
        }

        return null;
    }

    private static function layout(string $title, string $content): string
    {
        return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,sans-serif;color:#334155;">
<div style="max-width:620px;margin:0 auto;padding:32px 18px;">
<div style="background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">
<div style="background:#0f172a;padding:28px 32px;">
<h1 style="margin:0;color:#ffffff;font-size:22px;">' . e($title) . '</h1>
</div>
<div style="padding:32px;font-size:15px;line-height:1.7;">
' . $content . '
</div>
</div>
</div>
</body>
</html>';
    }

    private static function button(string $url, string $label): string
    {
        return '<p style="margin:28px 0 0;text-align:center;"><a href="' . e($url) . '" style="display:inline-block;background:#0ea5e9;color:#ffffff;text-decoration:none;font-weight:700;padding:14px 24px;border-radius:10px;">' . e($label) . '</a></p>';
    }

    private static function excerpt(string $message): string
    {
        $message = trim($message);
        return strlen($message) > 1200 ? substr($message, 0, 1200) . '...' : $message;
    }

    private static function funnelId(array $ticket): ?int
    {
        return !empty($ticket['funnel_id']) ? (int) $ticket['funnel_id'] : null;
    }
}
