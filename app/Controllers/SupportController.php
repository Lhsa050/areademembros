<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Security;
use App\Models\Funnel;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Services\SupportNotificationService;

class SupportController
{
    public function index(): void
    {
        Auth::require();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $requestedStatus = trim((string) ($_GET['status'] ?? ''));
        if ($requestedStatus !== '' && !in_array($requestedStatus, SupportTicket::STATUSES, true)) {
            $requestedStatus = '';
        }
        $activeTab = SupportTicket::normalizeStatusTab((string) (
            $_GET['tab'] ?? ($requestedStatus === 'closed' ? 'resolved' : 'open')
        ));

        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => $requestedStatus,
            'tab' => $activeTab,
            'funnel_id' => (int) ($_GET['funnel_id'] ?? 0),
        ];

        if ($filters['funnel_id'] <= 0) {
            unset($filters['funnel_id']);
        }

        $tickets = SupportTicket::search($filters, $perPage, ($page - 1) * $perPage);
        $total = SupportTicket::countSearch($filters);
        $countFilters = $filters;
        $countFilters['status'] = '';
        $tabCounts = [];
        foreach (SupportTicket::STATUS_TABS as $tab) {
            $countFilters['tab'] = $tab;
            $tabCounts[$tab] = SupportTicket::countSearch($countFilters);
        }

        view('admin.support.index', [
            'tickets' => $tickets,
            'funnels' => Funnel::allOrdered(),
            'filters' => $filters,
            'activeTab' => $activeTab,
            'tabCounts' => $tabCounts,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
            'user' => Auth::user(),
        ]);
    }

    public function show(string $id): void
    {
        Auth::require();

        $ticket = SupportTicket::findWithRelations((int) $id);
        if (!$ticket) {
            flash('error', 'Ticket nao encontrado.');
            redirect(url('/support'));
        }

        view('admin.support.show', [
            'ticket' => $ticket,
            'messages' => SupportMessage::getByTicket((int) $ticket['id']),
            'user' => Auth::user(),
        ]);
    }

    public function reply(string $id): void
    {
        Auth::require();
        Security::requireCsrf();

        $ticket = SupportTicket::findWithRelations((int) $id);
        if (!$ticket) {
            flash('error', 'Ticket nao encontrado.');
            redirect(url('/support'));
        }

        if (($ticket['status'] ?? '') === 'closed') {
            flash('error', 'Reabra o ticket antes de responder.');
            redirect(url('/support/tickets/' . (int) $ticket['id']));
        }

        $messageText = trim((string) ($_POST['message'] ?? ''));
        if ($messageText === '') {
            flash('error', 'Escreva uma resposta antes de enviar.');
            redirect(url('/support/tickets/' . (int) $ticket['id']));
        }

        $admin = Auth::user();
        $messageResult = SupportMessage::createMessageOnce(
            (int) $ticket['id'],
            'admin',
            $messageText,
            $admin['name'] ?? 'Suporte',
            $admin['email'] ?? null,
            (int) ($admin['id'] ?? 0)
        );

        if ($messageResult['created']) {
            $ticket = SupportTicket::findWithRelations((int) $ticket['id']);
            $message = SupportMessage::find((int) $messageResult['id']);
            $sent = SupportNotificationService::notifyCustomerAdminReply(
                $ticket,
                $message,
                url('/suporte/t/' . $ticket['secure_token'])
            );

            flash('success', $sent ? 'Resposta enviada e cliente notificado por email.' : 'Resposta salva. Email nao enviado porque o SMTP nao esta configurado.');
        } else {
            flash('success', 'Essa resposta ja tinha sido enviada.');
        }

        redirect(url('/support/tickets/' . (int) $ticket['id']));
    }

    public function updateStatus(string $id): void
    {
        Auth::require();
        Security::requireCsrf();

        $ticket = SupportTicket::findWithRelations((int) $id);
        if (!$ticket) {
            flash('error', 'Ticket nao encontrado.');
            redirect(url('/support'));
        }

        $status = (string) ($_POST['status'] ?? '');
        if (!SupportTicket::updateStatus((int) $ticket['id'], $status)) {
            flash('error', 'Status invalido.');
        } else {
            flash('success', 'Status atualizado.');
        }

        redirect(url('/support/tickets/' . (int) $ticket['id']));
    }
}
