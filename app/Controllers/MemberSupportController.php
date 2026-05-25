<?php

namespace App\Controllers;

use App\Core\MemberAuth;
use App\Core\Security;
use App\Models\Funnel;
use App\Models\SupportContact;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Services\SupportNotificationService;

class MemberSupportController
{
    public function index(string $slug): void
    {
        [$funnel, $member] = $this->requireMember($slug);
        SupportContact::linkMemberByEmail($member['email'], (int) $member['id'], (int) $funnel['id']);
        $activeTab = SupportTicket::normalizeStatusTab((string) ($_GET['tab'] ?? 'open'));
        $allTickets = SupportTicket::getByMember((int) $member['id'], (int) $funnel['id']);

        view('member.support.index', [
            'member' => $member,
            'funnel' => $funnel,
            'slug' => $slug,
            'appName' => $funnel['site_name'] ?: $funnel['name'],
            'tickets' => SupportTicket::filterTicketsForTab($allTickets, $activeTab),
            'activeTab' => $activeTab,
            'ticketCounts' => SupportTicket::tabCountsForRows($allTickets),
        ]);
    }

    public function store(string $slug): void
    {
        [$funnel, $member] = $this->requireMember($slug);
        Security::requireCsrf();

        $subject = trim((string) ($_POST['subject'] ?? ''));
        $messageText = trim((string) ($_POST['message'] ?? ''));

        if ($subject === '' || $messageText === '') {
            flash('error', 'Preencha assunto e mensagem.');
            redirect(url('/m/' . $slug . '/support'));
        }

        $contact = SupportContact::findOrCreate([
            'email' => $member['email'],
            'name' => $member['name'],
            'phone' => $member['phone'] ?? null,
            'member_id' => $member['id'],
            'last_funnel_id' => $funnel['id'],
        ]);

        SupportContact::linkMemberByEmail($member['email'], (int) $member['id'], (int) $funnel['id']);

        $duplicateTicket = SupportTicket::findRecentDuplicateCustomerTicket(
            (int) $contact['id'],
            (int) $member['id'],
            (int) $funnel['id'],
            $subject,
            $messageText,
            'member'
        );

        if ($duplicateTicket) {
            flash('success', 'Esse ticket ja tinha sido aberto. Voce foi levado para a conversa existente.');
            redirect(url('/m/' . $slug . '/support/' . (int) $duplicateTicket['id']));
        }

        $ticketId = SupportTicket::createTicket(
            (int) $contact['id'],
            (int) $member['id'],
            (int) $funnel['id'],
            $subject,
            'member'
        );

        $messageResult = SupportMessage::createMessageOnce(
            $ticketId,
            'customer',
            $messageText,
            $member['name'],
            $member['email']
        );

        $ticket = SupportTicket::findWithRelations($ticketId);
        SupportNotificationService::notifyAdminTicketOpened($ticket, SupportMessage::find((int) $messageResult['id']));

        flash('success', 'Ticket aberto com sucesso.');
        redirect(url('/m/' . $slug . '/support/' . $ticketId));
    }

    public function show(string $slug, string $id): void
    {
        [$funnel, $member] = $this->requireMember($slug);
        SupportContact::linkMemberByEmail($member['email'], (int) $member['id'], (int) $funnel['id']);

        $ticket = SupportTicket::findWithRelations((int) $id);
        if (!$this->memberCanView($ticket, $member, (int) $funnel['id'])) {
            flash('error', 'Ticket nao encontrado.');
            redirect(url('/m/' . $slug . '/support'));
        }

        view('member.support.show', [
            'member' => $member,
            'funnel' => $funnel,
            'slug' => $slug,
            'appName' => $funnel['site_name'] ?: $funnel['name'],
            'ticket' => $ticket,
            'messages' => SupportMessage::getByTicket((int) $ticket['id']),
        ]);
    }

    public function message(string $slug, string $id): void
    {
        [$funnel, $member] = $this->requireMember($slug);
        Security::requireCsrf();

        $ticket = SupportTicket::findWithRelations((int) $id);
        if (!$this->memberCanView($ticket, $member, (int) $funnel['id'])) {
            flash('error', 'Ticket nao encontrado.');
            redirect(url('/m/' . $slug . '/support'));
        }

        if (($ticket['status'] ?? '') === 'closed') {
            flash('error', 'Este ticket esta fechado.');
            redirect(url('/m/' . $slug . '/support/' . (int) $ticket['id']));
        }

        $messageText = trim((string) ($_POST['message'] ?? ''));
        if ($messageText === '') {
            flash('error', 'Escreva uma mensagem antes de enviar.');
            redirect(url('/m/' . $slug . '/support/' . (int) $ticket['id']));
        }

        $messageResult = SupportMessage::createMessageOnce(
            (int) $ticket['id'],
            'customer',
            $messageText,
            $member['name'],
            $member['email']
        );

        if ($messageResult['created']) {
            $ticket = SupportTicket::findWithRelations((int) $ticket['id']);
            SupportNotificationService::notifyAdminCustomerReply($ticket, SupportMessage::find((int) $messageResult['id']));
            flash('success', 'Mensagem enviada.');
        } else {
            flash('success', 'Essa mensagem ja tinha sido enviada.');
        }

        redirect(url('/m/' . $slug . '/support/' . (int) $ticket['id']));
    }

    private function requireMember(string $slug): array
    {
        $funnel = Funnel::findBySlug($slug);
        if (!$funnel) {
            http_response_code(404);
            echo '<h1>Area de membros nao encontrada</h1>';
            exit;
        }

        MemberAuth::require($slug);
        if (MemberAuth::funnelSlug() !== $slug) {
            redirect(url('/m/' . $slug . '/login'));
        }

        return [$funnel, MemberAuth::user()];
    }

    private function memberCanView(?array $ticket, array $member, int $funnelId): bool
    {
        if (!$ticket) {
            return false;
        }

        if ((int) ($ticket['member_id'] ?? 0) === (int) $member['id']) {
            return empty($ticket['funnel_id']) || (int) $ticket['funnel_id'] === $funnelId;
        }

        return strtolower((string) ($ticket['contact_email'] ?? '')) === strtolower((string) $member['email'])
            && (empty($ticket['funnel_id']) || (int) $ticket['funnel_id'] === $funnelId);
    }
}
