<?php
/**
 * Lista de Níveis de Acesso
 */
$title = 'Níveis de Acesso';

ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <p class="text-gray-600">Configure os níveis de acesso dos seus clientes.</p>
    <a href="<?= url('/access-levels/create') ?>" class="inline-flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Novo Nível
    </a>
</div>

<?php if (empty($levels)): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="key" class="w-8 h-8 text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-800 mb-2">Nenhum nível cadastrado</h3>
    <p class="text-gray-500 mb-6">Crie níveis como "Básico", "VIP", "Premium".</p>
    <a href="<?= url('/access-levels/create') ?>" class="inline-flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Criar Nível
    </a>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($levels as $level): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800"><?= e($level['name']) ?></h3>
            <span class="text-xs text-gray-500"><?= $level['product_count'] ?> produto(s)</span>
        </div>
        
        <div class="space-y-3 mb-4">
            <div class="bg-gray-50 p-3 rounded-lg">
                <p class="text-xs text-gray-500 mb-1">Senha de Acesso</p>
                <code class="text-sm font-mono font-bold text-gray-800"><?= e($level['password']) ?></code>
            </div>
            
            <div class="bg-blue-50 p-3 rounded-lg">
                <p class="text-xs text-blue-600 mb-1">Parâmetro URL</p>
                <code class="text-sm font-mono text-blue-700">?key=<?= e($level['uuid_key']) ?></code>
            </div>
        </div>
        
        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
            <a href="<?= url('/access-levels/' . $level['id'] . '/edit') ?>" class="text-blue-500 text-sm font-medium hover:underline">
                Editar
            </a>
            <form method="POST" action="<?= url('/access-levels/' . $level['id'] . '/delete') ?>" onsubmit="return confirm('Remover este nível de acesso?')">
                <?= csrf_field() ?>
                <button type="submit" class="text-red-500 text-sm font-medium hover:underline">
                    Remover
                </button>
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
