<?php
$title = 'Suporte';
$activeTab = $activeTab ?? 'open';
$ticketCounts = $ticketCounts ?? ['open' => 0, 'resolved' => 0, 'all' => 0];
ob_start();
?>

<style>
.support-head { display:flex; align-items:flex-start; justify-content:space-between; gap:18px; margin-bottom:24px; }
.support-head h1 { font-size:1.5rem; font-weight:800; color:var(--gray-900); margin:0 0 6px; }
.support-head p { color:var(--gray-500); font-size:.9375rem; margin:0; }
.support-layout { display:grid; grid-template-columns: 1fr 390px; gap:24px; align-items:start; }
.support-panel { background:var(--surface); border:1px solid var(--gray-200); border-radius:16px; overflow:hidden; }
.support-panel-header { padding:18px 20px; border-bottom:1px solid var(--gray-200); }
.support-panel-title { font-size:1rem; font-weight:800; color:var(--gray-900); margin:0; }
.support-tabs { display:flex; flex-wrap:wrap; gap:8px; padding:12px 20px; border-bottom:1px solid var(--gray-100); background:var(--gray-50); }
.support-tab { display:inline-flex; align-items:center; gap:7px; border:1px solid var(--gray-200); background:var(--surface); color:var(--gray-600); border-radius:10px; padding:8px 11px; text-decoration:none; font-size:.8rem; font-weight:800; transition:all .2s; }
.support-tab:hover { border-color:var(--brand-400); color:var(--brand-700); }
.support-tab.active { border-color:var(--brand-500); background:var(--brand-50); color:var(--brand-700); }
.support-tab-count { display:inline-flex; min-width:22px; height:20px; align-items:center; justify-content:center; border-radius:999px; background:var(--gray-100); color:var(--gray-500); font-size:.72rem; padding:0 6px; }
.support-tab.active .support-tab-count { background:var(--brand-100); color:var(--brand-700); }
.support-ticket { display:block; padding:18px 20px; border-bottom:1px solid var(--gray-100); text-decoration:none; transition:background .2s; }
.support-ticket:hover { background:var(--gray-50); }
.support-ticket:last-child { border-bottom:0; }
.support-ticket-top { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:7px; }
.support-ticket-number { font-size:.75rem; color:var(--gray-400); font-weight:800; }
.support-ticket h3 { color:var(--gray-900); font-size:.95rem; margin:0 0 6px; }
.support-ticket p { color:var(--gray-500); font-size:.8125rem; margin:0; }
.support-status { border:1px solid var(--gray-200); background:var(--gray-100); color:var(--gray-700); border-radius:999px; padding:4px 8px; font-size:.72rem; font-weight:800; white-space:nowrap; }
.support-form { padding:20px; }
.support-field { margin-bottom:14px; }
.support-field label { display:block; color:var(--gray-700); font-size:.82rem; font-weight:800; margin-bottom:7px; }
.support-field input, .support-field textarea { width:100%; border:1px solid var(--gray-200); background:var(--surface); color:var(--gray-900); border-radius:12px; padding:12px 14px; font:inherit; outline:none; }
.support-field textarea { min-height:150px; resize:vertical; line-height:1.55; }
.support-field input:focus, .support-field textarea:focus { border-color:var(--brand-500); box-shadow:0 0 0 4px color-mix(in srgb, var(--brand-500) 16%, transparent); }
.support-empty { padding:46px 20px; text-align:center; color:var(--gray-400); }
.support-submit { width:100%; }
@media (max-width: 880px) {
    .support-head { flex-direction:column; }
    .support-layout { grid-template-columns:1fr; }
}
</style>

<div class="support-head">
    <div>
        <h1>Suporte</h1>
        <p>Abra um ticket e acompanhe a conversa com o suporte.</p>
    </div>
    <a href="<?= url('/m/' . $slug . '/dashboard') ?>" class="btn btn-secondary" style="width:auto;">
        <?= icon('arrow-left', 'width:16px;height:16px;') ?>
        Voltar
    </a>
</div>

<div class="support-layout">
    <section class="support-panel">
        <div class="support-panel-header">
            <h2 class="support-panel-title">Meus tickets</h2>
        </div>
        <nav class="support-tabs" aria-label="Filtros de tickets">
            <?php foreach (\App\Models\SupportTicket::STATUS_TABS as $tab): ?>
                <?php $isActiveTab = $activeTab === $tab; ?>
                <a class="support-tab <?= $isActiveTab ? 'active' : '' ?>" href="<?= url('/m/' . $slug . '/support?tab=' . $tab) ?>">
                    <?= e(\App\Models\SupportTicket::statusTabLabel($tab)) ?>
                    <span class="support-tab-count"><?= (int) ($ticketCounts[$tab] ?? 0) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php if (empty($tickets)): ?>
            <div class="support-empty">
                <?= icon('mail', 'width:42px;height:42px;') ?>
                <p style="margin-top:10px;">Nenhum ticket nesta aba.</p>
            </div>
        <?php else: ?>
            <?php foreach ($tickets as $ticket): ?>
                <a class="support-ticket" href="<?= url('/m/' . $slug . '/support/' . (int) $ticket['id']) ?>">
                    <div class="support-ticket-top">
                        <span class="support-ticket-number"><?= e($ticket['ticket_number']) ?></span>
                        <span class="support-status"><?= e(\App\Models\SupportTicket::statusLabel($ticket['status'])) ?></span>
                    </div>
                    <h3><?= e($ticket['subject']) ?></h3>
                    <p>Ultima atividade: <?= !empty($ticket['last_message_at']) ? date('d/m/Y H:i', strtotime($ticket['last_message_at'])) : date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></p>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <aside class="support-panel">
        <div class="support-panel-header">
            <h2 class="support-panel-title">Novo ticket</h2>
        </div>
        <form class="support-form" method="POST" action="<?= url('/m/' . $slug . '/support') ?>" data-support-form>
            <?= csrf_field() ?>
            <div class="support-field">
                <label for="subject">Assunto</label>
                <input id="subject" name="subject" type="text" placeholder="Ex: preciso de ajuda com o acesso" required>
            </div>
            <div class="support-field">
                <label for="message">Mensagem</label>
                <textarea id="message" name="message" placeholder="Explique sua duvida..." required></textarea>
            </div>
            <button class="btn btn-primary support-submit" type="submit" data-submitting-text="Enviando...">
                <?= icon('mail', 'width:16px;height:16px;') ?>
                Enviar ticket
            </button>
        </form>
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
include ABSPATH . '/views/layouts/member.php';
?>
