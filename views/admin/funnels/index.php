<?php
/**
 * Lista de Funis
 */
$title = 'Funis';

ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <p class="text-gray-600">Gerencie suas áreas de membros.</p>
    <a href="<?= url('/funnels/create') ?>" class="inline-flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Novo Funil
    </a>
</div>

<?php if (empty($funnels)): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="git-branch" class="w-8 h-8 text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-800 mb-2">Nenhum funil criado</h3>
    <p class="text-gray-500 mb-6">Crie seu primeiro funil para começar.</p>
    <a href="<?= url('/funnels/create') ?>" class="inline-flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Criar Funil
    </a>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($funnels as $funnel): ?>
    <a href="<?= url('/funnels/' . $funnel['id']) ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md hover:border-blue-200 transition group">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-lg bg-blue-500/10 flex items-center justify-center group-hover:bg-blue-500/20 transition">
                <i data-lucide="git-branch" class="w-6 h-6 text-blue-500"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-gray-800 truncate"><?= e($funnel['name']) ?></h3>
                <p class="text-xs text-gray-500"><?= e($funnel['theme']) ?></p>
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-4 text-center border-t border-gray-100 pt-4">
            <div>
                <p class="text-2xl font-bold text-gray-800"><?= $funnel['product_count'] ?></p>
                <p class="text-xs text-gray-500">Produtos</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800"><?= $funnel['generation_count'] ?? 0 ?></p>
                <p class="text-xs text-gray-500">Gerações</p>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
