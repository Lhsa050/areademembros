<?php

namespace App\Controllers;

use App\Core\Security;
use App\Models\Funnel;
use App\Models\Member;
use App\Models\SupportContact;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Services\SupportNotificationService;

class PublicSupportController
{
    public function index(?string $slug = null): void
    {
        $funnel = $slug ? Funnel::findBySlug($slug) : null;
        if ($slug && !$funnel) {
            http_response_code(404);
            echo '<h1>Suporte nao encontrado</h1>';
            exit;
        }

        view('support.index', [
            'funnel' => $funnel,
            'funnels' => $funnel ? [] : Funnel::allOrdered(),
            'slug' => $slug,
            'errors' => $_SESSION['_validation_errors'] ?? [],
        ]);

        unset($_SESSION['_validation_errors']);
    }

    public function start(?string $slug = null): void
    {
        Security::requireCsrf();

        if (!empty($_POST['website'] ?? '')) {
            redirect(url($slug ? '/suporte/' . $slug : '/suporte'));
        }

        $fixedFunnel = $slug ? Funnel::findBySlug($slug) : null;
        if ($slug && !$fixedFunnel) {
            http_response_code(404);
            echo '<h1>Suporte nao encontrado</h1>';
            exit;
        }

        $email = SupportContact::normalizeEmail((string) ($_POST['email'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = preg_replace('/[^0-9+]/', '', (string) ($_POST['phone'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $messageText = trim((string) ($_POST['message'] ?? ''));

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Informe um email valido.';
        }
        if ($subject === '') {
            $errors['subject'] = 'Informe o assunto.';
        }
        if ($messageText === '') {
            $errors['message'] = 'Escreva sua duvida.';
        }

        if (!empty($errors)) {
            $_SESSION['_validation_errors'] = $errors;
            save_old_input();
            redirect(url($slug ? '/suporte/' . $slug : '/suporte'));
        }

        [$member, $funnelId] = $this->resolveMemberAndFunnel($email, $fixedFunnel);
        $contact = SupportContact::findOrCreate([
            'email' => $email,
            'name' => $name ?: ($member['name'] ?? null),
            'phone' => $phone ?: ($member['phone'] ?? null),
            'member_id' => $member['id'] ?? null,
            'last_funnel_id' => $funnelId,
        ]);

        if ($member && $funnelId) {
            SupportContact::linkMemberByEmail($email, (int) $member['id'], (int) $funnelId);
        }

        $duplicateTicket = SupportTicket::findRecentDuplicateCustomerTicket(
            (int) $contact['id'],
            $member ? (int) $member['id'] : null,
            $funnelId,
            $subject,
            $messageText,
            'public'
        );

        if ($duplicateTicket) {
            clear_old_input();
            flash('success', 'Seu ticket ja tinha sido aberto. Voce foi levado para a conversa existente.');
            redirect(url('/suporte/t/' . $duplicateTicket['secure_token']));
        }

        $ticketId = SupportTicket::createTicket(
            (int) $contact['id'],
            $member ? (int) $member['id'] : null,
            $funnelId,
            $subject,
            'public'
        );

        $messageResult = SupportMessage::createMessageOnce(
            $ticketId,
            'customer',
            $messageText,
            $name ?: ($member['name'] ?? $email),
            $email
        );

        $ticket = SupportTicket::findWithRelations($ticketId);
        $message = SupportMessage::find((int) $messageResult['id']);
        SupportNotificationService::notifyAdminTicketOpened($ticket, $message);
        SupportNotificationService::notifyCustomerTicketCreated($ticket, url('/suporte/t/' . $ticket['secure_token']));

        clear_old_input();
        flash('success', 'Seu ticket foi aberto. Voce pode acompanhar a conversa por aqui.');
        redirect(url('/suporte/t/' . $ticket['secure_token']));
    }

    public function ticket(string $token): void
    {
        $ticket = SupportTicket::findByToken($token);
        if (!$ticket) {
            http_response_code(404);
            echo '<h1>Ticket nao encontrado</h1>';
            exit;
        }

        view('support.ticket', [
            'ticket' => $ticket,
            'messages' => SupportMessage::getByTicket((int) $ticket['id']),
        ]);
    }

    public function message(string $token): void
    {
        Security::requireCsrf();

        $ticket = SupportTicket::findByToken($token);
        if (!$ticket) {
            http_response_code(404);
            echo '<h1>Ticket nao encontrado</h1>';
            exit;
        }

        if (($ticket['status'] ?? '') === 'closed') {
            flash('error', 'Este ticket esta fechado.');
            redirect(url('/suporte/t/' . $token));
        }

        $messageText = trim((string) ($_POST['message'] ?? ''));
        if ($messageText === '') {
            flash('error', 'Escreva uma mensagem antes de enviar.');
            redirect(url('/suporte/t/' . $token));
        }

        $messageResult = SupportMessage::createMessageOnce(
            (int) $ticket['id'],
            'customer',
            $messageText,
            $ticket['contact_name'] ?: $ticket['contact_email'],
            $ticket['contact_email']
        );

        if ($messageResult['created']) {
            $ticket = SupportTicket::findWithRelations((int) $ticket['id']);
            SupportNotificationService::notifyAdminCustomerReply($ticket, SupportMessage::find((int) $messageResult['id']));
            flash('success', 'Mensagem enviada.');
        } else {
            flash('success', 'Essa mensagem ja tinha sido enviada.');
        }

        redirect(url('/suporte/t/' . $token));
    }

    private function resolveMemberAndFunnel(string $email, ?array $fixedFunnel): array
    {
        if ($fixedFunnel) {
            $member = Member::findByEmail($email, (int) $fixedFunnel['id']);
            return [$member, (int) $fixedFunnel['id']];
        }

        $postedFunnelId = (int) ($_POST['funnel_id'] ?? 0);
        if ($postedFunnelId > 0 && Funnel::find($postedFunnelId)) {
            return [Member::findByEmail($email, $postedFunnelId), $postedFunnelId];
        }

        $members = Member::findAllByEmail($email);
        if (count($members) === 1) {
            return [$members[0], (int) $members[0]['funnel_id']];
        }

        return [null, null];
    }
}
