<?php
$title = 'Notificações Push - ' . $funnel['name'];
ob_start();
?>
<div class="mb-6">
    <a href="<?= url('/funnels/' . $funnel['id']) ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        <?= e($funnel['name']) ?>
    </a>
</div>
<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-gray-800">Notificações Push</h2>
    <div class="flex items-center gap-3">
        <span class="text-sm text-gray-500">
            <i data-lucide="users" class="w-4 h-4 inline"></i>
            <?= $subscriberCount ?> subscriber<?= $subscriberCount !== 1 ? 's' : '' ?>
        </span>
    </div>
</div>

<?php if (!$vapidConfigured): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-6">
    <div class="flex items-start gap-3">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 mt-0.5"></i>
        <div>
            <h3 class="font-medium text-amber-800">Chaves VAPID não configuradas</h3>
            <p class="text-sm text-amber-700 mt-1">Para enviar notificações push, configure as chaves VAPID em <a href="<?= url('/settings') ?>" class="underline font-medium">Configurações</a>.</p>
            <p class="text-xs text-amber-600 mt-2">Gere suas chaves em: <a href="https://vapidkeys.com/" target="_blank" class="underline">vapidkeys.com</a></p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Formulário de envio -->
<div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Enviar Notificação</h3>
    <form method="POST" action="<?= url('/funnels/' . $funnel['id'] . '/notifications/send') ?>" class="space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
            <input type="text" name="title" required class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Ex: Nova aula disponível!">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem *</label>
            <textarea name="body" rows="2" required class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Confira o novo conteúdo que acabou de sair..."></textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">URL (opcional)</label>
            <input type="url" name="url" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="<?= url('/m/' . $funnel['slug'] . '/dashboard') ?>">
            <p class="text-xs text-gray-500 mt-1">Ao clicar na notificação, o membro será redirecionado para esta URL.</p>
        </div>
        <button type="submit" class="inline-flex items-center gap-2 bg-blue-500 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-600 transition"
                <?= !$vapidConfigured || $subscriberCount === 0 ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
            <i data-lucide="send" class="w-4 h-4"></i>
            Enviar para <?= $subscriberCount ?> subscriber<?= $subscriberCount !== 1 ? 's' : '' ?>
        </button>
    </form>
</div>

<!-- Histórico -->
<?php if (!empty($notifications)): ?>
<div class="bg-white rounded-xl shadow-sm border overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h3 class="font-semibold text-gray-800">Histórico de Envios</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
            <tr>
                <th class="text-left px-6 py-3 font-medium">Título</th>
                <th class="text-left px-6 py-3 font-medium">Mensagem</th>
                <th class="text-center px-6 py-3 font-medium">Enviados</th>
                <th class="text-right px-6 py-3 font-medium">Data</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php foreach ($notifications as $notif): ?>
            <tr>
                <td class="px-6 py-3 font-medium text-gray-800"><?= e($notif['title']) ?></td>
                <td class="px-6 py-3 text-gray-500 truncate max-w-xs"><?= e($notif['body']) ?></td>
                <td class="px-6 py-3 text-center">
                    <span class="inline-flex items-center justify-center bg-blue-100 text-blue-700 rounded-full px-2 py-0.5 text-xs font-medium"><?= $notif['sent_count'] ?></span>
                </td>
                <td class="px-6 py-3 text-right text-gray-400"><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="bg-white rounded-xl shadow-sm border p-8 text-center text-gray-400">
    <i data-lucide="bell-off" class="w-8 h-8 mx-auto mb-2"></i>
    <p>Nenhuma notificação enviada ainda.</p>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
