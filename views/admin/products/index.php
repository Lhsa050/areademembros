<?php
/**
 * Lista de Produtos
 */
$title = 'Produtos';
$filters = $filters ?? ['q' => '', 'funnel_id' => 0];
$funnels = $funnels ?? [];

ob_start();
?>

<div class="products-list-page max-w-full overflow-hidden">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 min-w-0">
        <p class="text-gray-600 min-w-0">Cadastre produtos uma vez e reutilize em vários funis.</p>
        <a href="<?= url('/products/create') ?>" class="inline-flex items-center justify-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition flex-shrink-0">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Novo Produto
        </a>
    </div>

<form method="GET" action="<?= url('/products') ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5 max-w-full overflow-hidden">
    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_260px_auto_auto] gap-3 items-end">
        <div class="min-w-0">
            <label class="block text-xs font-medium text-gray-500 mb-1">Buscar por nome</label>
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nome ou descrição do produto">
            </div>
        </div>
        <div class="min-w-0">
            <label class="block text-xs font-medium text-gray-500 mb-1">Filtrar por funil</label>
            <select name="funnel_id" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Todos os funis</option>
                <?php foreach ($funnels as $funnel): ?>
                    <option value="<?= (int) $funnel['id'] ?>" <?= (int) ($filters['funnel_id'] ?? 0) === (int) $funnel['id'] ? 'selected' : '' ?>><?= e($funnel['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="inline-flex items-center justify-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition">
            <i data-lucide="filter" class="w-4 h-4"></i>
            Filtrar
        </button>
        <?php if (!empty($filters['q']) || !empty($filters['funnel_id'])): ?>
        <a href="<?= url('/products') ?>" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">
            <i data-lucide="x" class="w-4 h-4"></i>
            Limpar
        </a>
        <?php endif; ?>
    </div>
</form>

<?php if (empty($products)): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="box" class="w-8 h-8 text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-800 mb-2"><?= (!empty($filters['q']) || !empty($filters['funnel_id'])) ? 'Nenhum produto encontrado' : 'Nenhum produto cadastrado' ?></h3>
    <p class="text-gray-500 mb-6"><?= (!empty($filters['q']) || !empty($filters['funnel_id'])) ? 'Ajuste os filtros para ver mais produtos.' : 'Comece adicionando seu primeiro produto.' ?></p>
    <a href="<?= url('/products/create') ?>" class="inline-flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Criar Produto
    </a>
</div>
<?php else: ?>
<div class="space-y-3 max-w-full overflow-hidden">
    <?php foreach ($products as $product): ?>
    <div class="bg-white rounded-xl shadow-sm border flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 p-3 group hover:shadow-md transition-shadow transition-colors max-w-full overflow-hidden">
        <div class="w-20 aspect-[4/3] rounded-lg bg-gray-100 overflow-hidden flex-shrink-0">
            <?php if (!empty($product['image'])): ?>
                <img src="<?= url($product['image']) ?>" alt="<?= e($product['title']) ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center">
                    <i data-lucide="<?= $product['type'] === 'video' ? 'film' : 'file-text' ?>" class="w-6 h-6 text-gray-300"></i>
                </div>
            <?php endif; ?>
        </div>

        <div class="w-full flex-1 min-w-0 overflow-hidden">
            <div class="flex flex-wrap items-center gap-2 mb-0.5 min-w-0">
                <h3 class="font-semibold text-gray-800 truncate text-sm min-w-0 max-w-full"><?= e($product['title']) ?></h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium <?= $product['type'] === 'video' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600' ?> flex-shrink-0">
                    <?= $product['type'] === 'video' ? 'Vídeo' : 'PDF' ?>
                </span>
                <?php if (!empty($product['price'])): ?>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600 flex-shrink-0">
                    R$ <?= e(number_format((float) $product['price'], 2, ',', '.')) ?>
                </span>
                <?php endif; ?>
            </div>
            <p class="text-xs text-gray-500 truncate mb-1"><?= e($product['description']) ?></p>
            <div class="flex items-center gap-1 text-[10px] text-gray-400 min-w-0 max-w-full overflow-hidden">
                <i data-lucide="git-branch" class="w-3 h-3 flex-shrink-0"></i>
                <?php if (!empty($product['funnel_count'])): ?>
                    <span class="flex-shrink-0"><?= (int) $product['funnel_count'] ?> funil<?= (int) $product['funnel_count'] === 1 ? '' : 's' ?></span>
                    <?php if (!empty($product['funnel_names'])): ?>
                        <span class="block truncate min-w-0" title="<?= e($product['funnel_names']) ?>">- <?= e($product['funnel_names']) ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span>Ainda não vinculado a nenhum funil</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0 self-end sm:self-auto">
            <a href="<?= url('/products/' . $product['id'] . '/edit') ?>" class="p-2 rounded-lg text-gray-400 hover:text-blue-500 hover:bg-blue-50 transition" title="Editar">
                <i data-lucide="pencil" class="w-4 h-4"></i>
            </a>
            <form method="POST" action="<?= url('/products/' . $product['id'] . '/delete') ?>" onsubmit="return confirm('Tem certeza que deseja remover este produto?')">
                <?= csrf_field() ?>
                <button type="submit" class="p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition" title="Remover">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.products-list-page,
.products-list-page * {
    max-width: 100%;
}
</style>
</div>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
