<?php
$title = 'Ofertas - ' . $funnel['name'];
ob_start();
?>
<div class="mb-6">
    <a href="<?= url('/funnels/' . $funnel['id']) ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        <?= e($funnel['name']) ?>
    </a>
</div>
<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-gray-800">Ofertas (Upsell)</h2>
    <a href="<?= url('/funnels/' . $funnel['id'] . '/offers/create') ?>" class="inline-flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Nova Oferta
    </a>
</div>

<p class="text-sm text-gray-500 mb-6">Ofertas aparecem como popup na área de membros após o login. Configure qual oferta será exibida e quais produtos serão liberados ao comprar.</p>

<?php if (empty($offers)): ?>
<div class="bg-white rounded-xl shadow-sm border p-12 text-center">
    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="gift" class="w-8 h-8 text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-800 mb-2">Nenhuma oferta</h3>
    <p class="text-sm text-gray-500 mb-4">Crie uma oferta para exibir como popup na área de membros.</p>
    <a href="<?= url('/funnels/' . $funnel['id'] . '/offers/create') ?>" class="inline-flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Criar Oferta
    </a>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($offers as $offer): ?>
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <?php if (!empty($offer['image'])): ?>
        <div class="h-40 bg-gray-100 overflow-hidden">
            <img src="<?= url($offer['image']) ?>" class="w-full h-full object-cover">
        </div>
        <?php else: ?>
        <div class="h-24 bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center">
            <i data-lucide="gift" class="w-10 h-10 text-white/80"></i>
        </div>
        <?php endif; ?>
        <div class="p-4">
            <div class="flex items-center gap-2 mb-2">
                <h3 class="font-semibold text-gray-800 flex-1 truncate"><?= e($offer['title']) ?></h3>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $offer['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                    <?= $offer['is_active'] ? 'Ativa' : 'Inativa' ?>
                </span>
            </div>
            <?php if (!empty($offer['description'])): ?>
            <p class="text-sm text-gray-500 mb-3 line-clamp-2"><?= e($offer['description']) ?></p>
            <?php endif; ?>

            <?php if (!empty($offer['webhook_token'])): ?>
            <div class="mb-3">
                <div class="flex items-center gap-1 bg-gray-50 rounded border px-2 py-1">
                    <i data-lucide="webhook" class="w-3 h-3 text-gray-400"></i>
                    <input type="text" value="<?= rtrim(env('APP_URL', ''), '/') . '/webhook/' . $offer['webhook_token'] ?>" readonly
                           class="flex-1 bg-transparent border-none text-[10px] text-gray-500 font-mono focus:ring-0 p-0" onclick="this.select()">
                    <button type="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); this.innerHTML='✓'; setTimeout(()=>this.innerHTML='<i data-lucide=\'copy\' class=\'w-3 h-3\'></i>', 1000)" class="text-gray-400 hover:text-brand-600">
                        <i data-lucide="copy" class="w-3 h-3"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div class="flex items-center justify-between">
                <a href="<?= url('/funnels/' . $funnel['id'] . '/offers/' . $offer['id'] . '/edit') ?>" class="text-blue-500 text-sm font-medium hover:underline">Editar</a>
                <form method="POST" action="<?= url('/funnels/' . $funnel['id'] . '/offers/' . $offer['id'] . '/delete') ?>" onsubmit="return confirm('Remover esta oferta?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="text-red-500 text-sm font-medium hover:underline">Remover</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
