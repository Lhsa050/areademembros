<?php
$title = 'Ticket ' . e($ticket['ticket_number']);
ob_start();

$statusClasses = [
    'open' => 'bg-blue-50 text-blue-700 border-blue-100',
    'waiting_support' => 'bg-amber-50 text-amber-700 border-amber-100',
    'waiting_customer' => 'bg-purple-50 text-purple-700 border-purple-100',
    'closed' => 'bg-gray-100 text-gray-600 border-gray-200',
];
$status = $ticket['status'] ?? 'open';
?>

<div class="mb-6">
    <a href="<?= url('/support') ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 text-sm">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Voltar para suporte
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_340px] gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="text-xs font-semibold text-gray-500"><?= e($ticket['ticket_number']) ?></span>
                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold <?= $statusClasses[$status] ?? $statusClasses['open'] ?>">
                            <?= e(\App\Models\SupportTicket::statusLabel($status)) ?>
                        </span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900"><?= e($ticket['subject']) ?></h3>
                    <p class="text-sm text-gray-500 mt-1"><?= e($ticket['contact_name'] ?: 'Cliente') ?> &lt;<?= e($ticket['contact_email']) ?>&gt;</p>
                </div>
                <form method="POST" action="<?= url('/support/tickets/' . (int) $ticket['id'] . '/status') ?>" class="flex items-center gap-2">
                    <?= csrf_field() ?>
                    <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
                        <?php foreach (\App\Models\SupportTicket::STATUSES as $statusOption): ?>
                            <option value="<?= e($statusOption) ?>" <?= $status === $statusOption ? 'selected' : '' ?>>
                                <?= e(\App\Models\SupportTicket::statusLabel($statusOption)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="bg-gray-900 hover:bg-gray-800 text-white px-3 py-2 rounded-lg text-sm font-semibold transition" type="submit">Salvar</button>
                </form>
            </div>
        </div>

        <div class="p-6 bg-gray-50 min-h-[420px]">
            <div class="space-y-4">
                <?php foreach ($messages as $message): ?>
                    <?php $isAdmin = ($message['sender_type'] ?? '') === 'admin'; ?>
                    <div class="flex <?= $isAdmin ? 'justify-end' : 'justify-start' ?>">
                        <div class="max-w-[82%] rounded-2xl px-4 py-3 shadow-sm <?= $isAdmin ? 'bg-brand-500 text-white rounded-br-md' : 'bg-white text-gray-800 border border-gray-200 rounded-bl-md' ?>">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-semibold <?= $isAdmin ? 'text-white/85' : 'text-gray-500' ?>">
                                    <?= e($isAdmin ? ($message['admin_name'] ?: $message['sender_name'] ?: 'Suporte') : ($message['sender_name'] ?: 'Cliente')) ?>
                                </span>
                                <span class="text-[11px] <?= $isAdmin ? 'text-white/65' : 'text-gray-400' ?>">
                                    <?= date('d/m/Y H:i', strtotime($message['created_at'])) ?>
                                </span>
                            </div>
                            <div class="text-sm leading-relaxed whitespace-pre-wrap"><?= e($message['message']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <form method="POST" action="<?= url('/support/tickets/' . (int) $ticket['id'] . '/reply') ?>" class="p-6 border-t border-gray-100 bg-white" data-support-form>
            <?= csrf_field() ?>
            <?php if ($ticket['status'] === 'closed'): ?>
                <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    Este ticket esta resolvido. Reabra o status se quiser responder.
                </div>
            <?php endif; ?>
            <label for="message" class="block text-sm font-semibold text-gray-700 mb-2">Responder ao cliente</label>
            <textarea id="message" name="message" rows="5" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none resize-y" placeholder="Digite sua resposta..."></textarea>
            <div class="mt-3 flex justify-end">
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold inline-flex items-center gap-2 transition disabled:opacity-60 disabled:cursor-not-allowed" data-submitting-text="Enviando...">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Enviar resposta
                </button>
            </div>
        </form>
    </div>

    <aside class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h4 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i data-lucide="user" class="w-4 h-4 text-gray-500"></i>
                Cliente
            </h4>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Nome</dt>
                    <dd class="font-medium text-gray-800"><?= e($ticket['contact_name'] ?: '-') ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Email</dt>
                    <dd class="font-medium text-gray-800 break-all"><?= e($ticket['contact_email']) ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Telefone</dt>
                    <dd class="font-medium text-gray-800"><?= e($ticket['contact_phone'] ?: '-') ?></dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h4 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i data-lucide="git-branch" class="w-4 h-4 text-gray-500"></i>
                Contexto
            </h4>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Funil</dt>
                    <dd class="font-medium text-gray-800"><?= e($ticket['funnel_name'] ?: 'Geral') ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Origem</dt>
                    <dd class="font-medium text-gray-800"><?= e($ticket['source'] ?? '-') ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Criado em</dt>
                    <dd class="font-medium text-gray-800"><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></dd>
                </div>
            </dl>
        </div>
    </aside>
</div>

<script>
    document.querySelectorAll('[data-support-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) return;

            form.dataset.submitting = '1';
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.textContent = button.dataset.submittingText || 'Enviando...';
            }
        });
    });
</script>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
