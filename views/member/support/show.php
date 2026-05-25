<?php
$title = 'Suporte';
$status = $ticket['status'] ?? 'open';
ob_start();
?>

<style>
.support-chat-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:18px; }
.support-chat-head h1 { margin:4px 0 6px; color:var(--gray-900); font-size:1.35rem; font-weight:800; }
.support-chat-head p { color:var(--gray-500); font-size:.85rem; margin:0; }
.support-status { border:1px solid var(--gray-200); background:var(--gray-100); color:var(--gray-700); border-radius:999px; padding:6px 10px; font-size:.75rem; font-weight:800; white-space:nowrap; }
.support-chat { background:var(--surface); border:1px solid var(--gray-200); border-radius:16px; overflow:hidden; }
.support-messages { min-height:430px; max-height:62vh; overflow-y:auto; padding:20px; background:var(--gray-50); }
.support-row { display:flex; margin-bottom:14px; }
.support-row.admin { justify-content:flex-start; }
.support-row.customer { justify-content:flex-end; }
.support-bubble { max-width:82%; padding:13px 15px; border-radius:18px; box-shadow:0 1px 3px rgba(15,23,42,.08); }
.support-row.admin .support-bubble { background:var(--surface); color:var(--gray-800); border:1px solid var(--gray-200); border-bottom-left-radius:6px; }
.support-row.customer .support-bubble { background:var(--brand-500); color:#fff; border-bottom-right-radius:6px; }
.support-by { font-size:.74rem; font-weight:800; opacity:.72; margin-bottom:5px; }
.support-text { font-size:.9rem; line-height:1.62; white-space:pre-wrap; }
.support-reply { padding:18px; border-top:1px solid var(--gray-200); }
.support-reply textarea { width:100%; min-height:118px; border:1px solid var(--gray-200); background:var(--surface); color:var(--gray-900); border-radius:14px; padding:13px 14px; font:inherit; outline:none; resize:vertical; }
.support-reply textarea:focus { border-color:var(--brand-500); box-shadow:0 0 0 4px color-mix(in srgb, var(--brand-500) 16%, transparent); }
.support-actions { display:flex; justify-content:flex-end; margin-top:12px; }
@media (max-width: 720px) {
    .support-chat-head { flex-direction:column; }
    .support-messages { max-height:none; min-height:360px; padding:14px; }
    .support-bubble { max-width:94%; }
    .support-actions .btn { width:100%; }
}
</style>

<div class="support-chat-head">
    <div>
        <a href="<?= url('/m/' . $slug . '/support') ?>" class="breadcrumb" style="text-decoration:none;margin-bottom:8px;">
            <?= icon('arrow-left', 'width:14px;height:14px;') ?>
            Voltar para suporte
        </a>
        <p><?= e($ticket['ticket_number']) ?></p>
        <h1><?= e($ticket['subject']) ?></h1>
        <p>Aberto em <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></p>
    </div>
    <span class="support-status"><?= e(\App\Models\SupportTicket::statusLabel($status)) ?></span>
</div>

<section class="support-chat">
    <div class="support-messages" id="support-messages">
        <?php foreach ($messages as $message): ?>
            <?php $isAdmin = ($message['sender_type'] ?? '') === 'admin'; ?>
            <div class="support-row <?= $isAdmin ? 'admin' : 'customer' ?>">
                <div class="support-bubble">
                    <div class="support-by">
                        <?= e($isAdmin ? 'Suporte' : 'Voce') ?> &bull; <?= date('d/m/Y H:i', strtotime($message['created_at'])) ?>
                    </div>
                    <div class="support-text"><?= e($message['message']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="support-reply">
        <?php if ($status === 'closed'): ?>
            <p style="color:var(--gray-500);font-size:.9rem;">Este ticket esta resolvido.</p>
        <?php else: ?>
            <form method="POST" action="<?= url('/m/' . $slug . '/support/' . (int) $ticket['id'] . '/message') ?>" data-support-form>
                <?= csrf_field() ?>
                <label for="message" style="display:block;color:var(--gray-700);font-size:.82rem;font-weight:800;margin-bottom:8px;">Responder</label>
                <textarea id="message" name="message" placeholder="Digite sua resposta..." required></textarea>
                <div class="support-actions">
                    <button class="btn btn-primary" type="submit" style="width:auto;" data-submitting-text="Enviando...">
                        <?= icon('mail', 'width:16px;height:16px;') ?>
                        Enviar mensagem
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<script>
    const supportMessages = document.getElementById('support-messages');
    if (supportMessages) supportMessages.scrollTop = supportMessages.scrollHeight;

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
