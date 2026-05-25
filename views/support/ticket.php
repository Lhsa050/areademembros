<?php
$appName = $ticket['funnel_name'] ?: 'Suporte';
$status = $ticket['status'] ?? 'open';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($ticket['ticket_number']) ?> - Suporte</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #eef3f8; color: #172033; }
        .page { width: min(100%, 980px); margin: 0 auto; padding: 24px; }
        .top { background: #fff; border: 1px solid #dfe7f1; border-radius: 18px; padding: 22px; display: flex; justify-content: space-between; gap: 18px; align-items: flex-start; margin-bottom: 18px; }
        .top h1 { margin: 4px 0 6px; font-size: clamp(22px, 4vw, 30px); letter-spacing: 0; }
        .meta { color: #667085; font-size: 14px; line-height: 1.6; }
        .badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 7px 12px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 12px; font-weight: 800; white-space: nowrap; }
        .chat { background: #fff; border: 1px solid #dfe7f1; border-radius: 18px; overflow: hidden; }
        .messages { min-height: 420px; max-height: 62vh; overflow-y: auto; padding: 22px; background: #f8fafc; }
        .row { display: flex; margin-bottom: 14px; }
        .row.admin { justify-content: flex-start; }
        .row.customer { justify-content: flex-end; }
        .bubble { max-width: 82%; padding: 13px 15px; border-radius: 18px; box-shadow: 0 1px 3px rgba(15,23,42,.07); }
        .admin .bubble { background: #fff; border: 1px solid #e2e8f0; border-bottom-left-radius: 6px; }
        .customer .bubble { background: #0ea5e9; color: #fff; border-bottom-right-radius: 6px; }
        .by { font-size: 12px; font-weight: 800; opacity: .72; margin-bottom: 4px; }
        .text { font-size: 14px; line-height: 1.62; white-space: pre-wrap; }
        .reply { padding: 18px; border-top: 1px solid #e5e7eb; background: #fff; }
        textarea { width: 100%; min-height: 118px; resize: vertical; border: 1px solid #d0d5dd; border-radius: 14px; padding: 13px 14px; font: inherit; outline: none; }
        textarea:focus { border-color: #0ea5e9; box-shadow: 0 0 0 4px rgba(14,165,233,.13); }
        .actions { margin-top: 12px; display: flex; justify-content: flex-end; }
        button { border: 0; border-radius: 12px; background: #0ea5e9; color: #fff; padding: 13px 18px; font-weight: 800; cursor: pointer; }
        .flash { border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; font-size: 14px; }
        .flash.success { background: #ecfdf3; color: #027a48; border: 1px solid #abefc6; }
        .flash.error { background: #fef3f2; color: #b42318; border: 1px solid #fecdca; }
        @media (max-width: 680px) {
            .page { padding: 12px; }
            .top { border-radius: 14px; flex-direction: column; }
            .messages { max-height: none; min-height: 360px; padding: 14px; }
            .bubble { max-width: 94%; }
            .actions button { width: 100%; }
        }
    </style>
</head>
<body>
<main class="page">
    <section class="top">
        <div>
            <div class="meta"><?= e($ticket['ticket_number']) ?> &bull; <?= e($appName) ?></div>
            <h1><?= e($ticket['subject']) ?></h1>
            <div class="meta">Aberto em <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></div>
        </div>
        <span class="badge"><?= e(\App\Models\SupportTicket::statusLabel($status)) ?></span>
    </section>

    <?php if ($success = flash('success')): ?><div class="flash success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash('error')): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>

    <section class="chat">
        <div class="messages" id="messages">
            <?php foreach ($messages as $message): ?>
                <?php $isAdmin = ($message['sender_type'] ?? '') === 'admin'; ?>
                <div class="row <?= $isAdmin ? 'admin' : 'customer' ?>">
                    <div class="bubble">
                        <div class="by"><?= e($isAdmin ? 'Suporte' : 'Voce') ?> &bull; <?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></div>
                        <div class="text"><?= e($message['message']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="reply">
            <?php if ($status === 'closed'): ?>
                <p class="meta">Este ticket esta resolvido.</p>
            <?php else: ?>
                <form method="POST" action="<?= url('/suporte/t/' . e($ticket['secure_token']) . '/message') ?>" data-support-form>
                    <?= csrf_field() ?>
                    <label for="message" class="meta" style="display:block;margin-bottom:8px;font-weight:800;color:#344054;">Responder</label>
                    <textarea id="message" name="message" placeholder="Digite sua resposta..." required></textarea>
                    <div class="actions">
                        <button type="submit" data-submitting-text="Enviando...">Enviar mensagem</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </section>
</main>
<script>
    const messages = document.getElementById('messages');
    if (messages) messages.scrollTop = messages.scrollHeight;

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
</body>
</html>
