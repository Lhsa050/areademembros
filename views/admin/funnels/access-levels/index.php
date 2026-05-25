<?php
$title = 'Níveis de Acesso - ' . $funnel['name'];
ob_start();
?>
<div class="mb-6">
    <a href="<?= url('/funnels/' . $funnel['id']) ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        <?= e($funnel['name']) ?>
    </a>
</div>
<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-gray-800">Níveis de Acesso</h2>
    <a href="<?= url('/funnels/' . $funnel['id'] . '/access-levels/create') ?>" class="inline-flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Novo Nível
    </a>
</div>
<?php if (empty($levels)): ?>
<div class="bg-white rounded-xl shadow-sm border p-12 text-center">
    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="key" class="w-8 h-8 text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-800 mb-2">Nenhum nível</h3>
    <a href="<?= url('/funnels/' . $funnel['id'] . '/access-levels/create') ?>" class="inline-flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition mt-4">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Criar Nível
    </a>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($levels as $level): ?>
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800"><?= e($level['name']) ?></h3>
            <span class="text-xs text-gray-500"><?= $level['product_count'] ?> produto(s)</span>
        </div>
        <div class="space-y-3 mb-4">
            <div class="bg-gray-50 p-3 rounded-lg"><p class="text-xs text-gray-500 mb-1">Senha</p><code class="text-sm font-mono font-bold text-gray-800"><?= e($level['password']) ?></code></div>
            <div class="bg-blue-50 p-3 rounded-lg"><p class="text-xs text-blue-600 mb-1">Parâmetro URL</p><code class="text-sm font-mono text-blue-700">?key=<?= e($level['uuid_key']) ?></code></div>
        </div>
        <div class="flex items-center justify-between pt-4 border-t">
            <a href="<?= url('/funnels/' . $funnel['id'] . '/access-levels/' . $level['id'] . '/edit') ?>" class="text-blue-500 text-sm font-medium hover:underline">Editar</a>
            <form method="POST" action="<?= url('/funnels/' . $funnel['id'] . '/access-levels/' . $level['id'] . '/delete') ?>" onsubmit="return confirm('Remover?')">
                <?= csrf_field() ?>
                <button type="submit" class="text-red-500 text-sm font-medium hover:underline">Remover</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
