<?php
$appName = $funnel['site_name'] ?? $funnel['name'] ?? 'Suporte';
$formAction = $slug ? url('/suporte/' . $slug . '/start') : url('/suporte/start');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suporte - <?= e($appName) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f4f7fb;
            color: #152033;
        }
        .page { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .shell { width: min(100%, 920px); display: grid; grid-template-columns: 0.82fr 1.18fr; background: #fff; border: 1px solid #dfe7f1; border-radius: 22px; overflow: hidden; box-shadow: 0 24px 70px rgba(15, 23, 42, .14); }
        .intro { background: #0f172a; color: #fff; padding: 44px; display: flex; flex-direction: column; justify-content: space-between; min-height: 560px; }
        .intro h1 { margin: 0 0 14px; font-size: clamp(28px, 4vw, 42px); line-height: 1.05; letter-spacing: 0; }
        .intro p { margin: 0; color: #cbd5e1; line-height: 1.7; font-size: 15px; }
        .intro-card { margin-top: 36px; border: 1px solid rgba(255,255,255,.16); border-radius: 16px; padding: 18px; background: rgba(255,255,255,.06); }
        .form { padding: 42px; }
        .form h2 { margin: 0 0 6px; font-size: 22px; color: #0f172a; }
        .form .sub { margin: 0 0 26px; color: #667085; font-size: 14px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .field { margin-bottom: 14px; }
        label { display: block; margin: 0 0 7px; font-size: 13px; font-weight: 700; color: #344054; }
        input, select, textarea { width: 100%; border: 1px solid #d0d5dd; border-radius: 12px; padding: 13px 14px; color: #101828; font: inherit; outline: none; background: #fff; transition: border .2s, box-shadow .2s; }
        input:focus, select:focus, textarea:focus { border-color: #0ea5e9; box-shadow: 0 0 0 4px rgba(14,165,233,.13); }
        textarea { min-height: 150px; resize: vertical; line-height: 1.55; }
        .error { color: #b42318; font-size: 12px; margin-top: 6px; }
        .flash { border-radius: 12px; padding: 12px 14px; margin-bottom: 18px; font-size: 14px; }
        .flash.success { background: #ecfdf3; color: #027a48; border: 1px solid #abefc6; }
        .flash.error { background: #fef3f2; color: #b42318; border: 1px solid #fecdca; }
        .hidden-field { position: absolute; left: -9999px; opacity: 0; }
        .actions { display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 18px; }
        button { border: 0; border-radius: 12px; background: #0ea5e9; color: #fff; padding: 14px 20px; font-weight: 800; cursor: pointer; box-shadow: 0 10px 24px rgba(14,165,233,.24); }
        button:hover { background: #0284c7; }
        .note { margin-top: 18px; color: #667085; font-size: 13px; line-height: 1.6; }
        @media (max-width: 820px) {
            .page { padding: 0; place-items: stretch; }
            .shell { min-height: 100vh; border-radius: 0; grid-template-columns: 1fr; border: 0; }
            .intro { min-height: auto; padding: 30px 22px; }
            .intro-card { margin-top: 20px; }
            .form { padding: 28px 22px 34px; }
            .grid { grid-template-columns: 1fr; gap: 0; }
            .actions { justify-content: stretch; }
            button { width: 100%; }
        }
    </style>
</head>
<body>
<main class="page">
    <section class="shell">
        <aside class="intro">
            <div>
                <p style="font-size:13px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#7dd3fc;margin-bottom:12px;">Central de suporte</p>
                <h1><?= e($appName) ?></h1>
                <p>Envie sua duvida por aqui e acompanhe a conversa no mesmo link. Se seu email ja tiver acesso a um funil, o ticket tambem aparece dentro da sua area de membros.</p>
            </div>
            <div class="intro-card">
                <p style="font-weight:700;color:#fff;margin-bottom:6px;">Atendimento organizado</p>
                <p>Use o mesmo email da compra para que o sistema encontre seu acesso automaticamente.</p>
            </div>
        </aside>

        <section class="form">
            <h2>Abrir ticket</h2>
            <p class="sub">Conte o que aconteceu para o suporte continuar a conversa com voce.</p>

            <?php if ($success = flash('success')): ?>
                <div class="flash success"><?= e($success) ?></div>
            <?php endif; ?>
            <?php if ($error = flash('error')): ?>
                <div class="flash error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= $formAction ?>" data-support-form>
                <?= csrf_field() ?>
                <div class="hidden-field">
                    <label for="website">Website</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="grid">
                    <div class="field">
                        <label for="name">Nome</label>
                        <input id="name" name="name" type="text" value="<?= e(old('name')) ?>" placeholder="Seu nome">
                    </div>
                    <div class="field">
                        <label for="email">Email *</label>
                        <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" placeholder="voce@email.com" required>
                        <?php if (!empty($errors['email'])): ?><div class="error"><?= e($errors['email']) ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="grid">
                    <div class="field">
                        <label for="phone">WhatsApp</label>
                        <input id="phone" name="phone" type="text" value="<?= e(old('phone')) ?>" placeholder="(00) 00000-0000">
                    </div>
                    <?php if (!$funnel): ?>
                    <div class="field">
                        <label for="funnel_id">Funil relacionado</label>
                        <select id="funnel_id" name="funnel_id">
                            <option value="">Nao tenho certeza</option>
                            <?php foreach ($funnels as $funnelOption): ?>
                                <option value="<?= (int) $funnelOption['id'] ?>" <?= (int) old('funnel_id') === (int) $funnelOption['id'] ? 'selected' : '' ?>>
                                    <?= e($funnelOption['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label for="subject">Assunto *</label>
                    <input id="subject" name="subject" type="text" value="<?= e(old('subject')) ?>" placeholder="Ex: nao consigo acessar meu produto" required>
                    <?php if (!empty($errors['subject'])): ?><div class="error"><?= e($errors['subject']) ?></div><?php endif; ?>
                </div>

                <div class="field">
                    <label for="message">Mensagem *</label>
                    <textarea id="message" name="message" placeholder="Explique sua duvida com detalhes..." required><?= e(old('message')) ?></textarea>
                    <?php if (!empty($errors['message'])): ?><div class="error"><?= e($errors['message']) ?></div><?php endif; ?>
                </div>

                <div class="actions">
                    <button type="submit" data-submitting-text="Enviando...">Enviar ticket</button>
                </div>
                <p class="note">Ao enviar, voce recebe um link privado para acompanhar o atendimento.</p>
            </form>
        </section>
    </section>
</main>
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
</body>
</html>
