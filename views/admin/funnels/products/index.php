<?php
$title = 'Produtos - ' . $funnel['name'];
$reorderUrl = parse_url(url('/funnels/' . $funnel['id'] . '/products/reorder'), PHP_URL_PATH) ?: url('/funnels/' . $funnel['id'] . '/products/reorder');
ob_start();
?>
<div class="mb-6">
    <a href="<?= url('/funnels/' . $funnel['id']) ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        <?= e($funnel['name']) ?>
    </a>
</div>
<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-gray-800">Produtos</h2>
    <a href="<?= url('/funnels/' . $funnel['id'] . '/products/create') ?>" class="inline-flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Adicionar Produto
    </a>
</div>
<?php if (empty($products)): ?>
<div class="bg-white rounded-xl shadow-sm border p-12 text-center">
    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="box" class="w-8 h-8 text-gray-400"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-800 mb-2">Nenhum produto</h3>
    <a href="<?= url('/funnels/' . $funnel['id'] . '/products/create') ?>" class="inline-flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition mt-4">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Selecionar Produto
    </a>
</div>
<?php else: ?>
<p class="text-xs text-gray-400 mb-3 flex items-center gap-1">
    <i data-lucide="grip-vertical" class="w-3 h-3 inline"></i>
    Arraste para reordenar os produtos
</p>

<div id="products-sortable">
    <?php foreach ($products as $product): ?>
    <div class="bg-white rounded-xl shadow-sm border flex items-center gap-4 p-3 group product-sortable-item hover:shadow-md transition-shadow transition-colors mb-3" data-id="<?= $product['id'] ?>">
        <!-- Drag Handle -->
        <div class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 transition-colors flex-shrink-0 px-2 py-2">
            <i data-lucide="grip-vertical" class="w-5 h-5 pointer-events-none"></i>
        </div>

        <!-- Image -->
        <div class="w-20 aspect-[4/3] rounded-lg bg-gray-100 overflow-hidden flex-shrink-0">
            <?php if ($product['image']): ?>
                <img src="<?= url($product['image']) ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center">
                    <i data-lucide="<?= $product['type'] === 'video' ? 'film' : 'file-text' ?>" class="w-6 h-6 text-gray-300"></i>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-0.5">
                <h3 class="font-semibold text-gray-800 truncate text-sm"><?= e($product['title']) ?></h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium <?= $product['type'] === 'video' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600' ?> flex-shrink-0">
                    <?= $product['type'] === 'video' ? 'Vídeo' : 'PDF' ?>
                </span>
                <?php if (!empty($product['release_days'])): ?>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-600 flex-shrink-0">
                    <?= $product['release_days'] ?> dias
                </span>
                <?php endif; ?>
                <?php if (!empty($product['is_public'])): ?>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-100 text-emerald-600 flex-shrink-0">
                    Todos
                </span>
                <?php endif; ?>
                <?php if (!empty($product['funnel_role'])): ?>
                <?php
                    $roleLabels = ['principal' => 'Principal', 'bonus' => 'Bônus', 'orderbump' => 'Order Bump'];
                    $roleColors = ['principal' => 'bg-blue-100 text-blue-600', 'bonus' => 'bg-amber-100 text-amber-600', 'orderbump' => 'bg-purple-100 text-purple-600'];
                    $roleIcons = ['principal' => 'star', 'bonus' => 'gift', 'orderbump' => 'plus-circle'];
                    $role = $product['funnel_role'];
                ?>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium <?= $roleColors[$role] ?? 'bg-gray-100 text-gray-600' ?> flex-shrink-0 flex items-center gap-0.5">
                    <i data-lucide="<?= $roleIcons[$role] ?? 'tag' ?>" class="w-3 h-3"></i>
                    <?= $roleLabels[$role] ?? $role ?>
                </span>
                <?php endif; ?>
            </div>
            <?php if (!empty($product['external_product_id'])): ?>
            <div class="flex items-center gap-1 text-[10px] text-teal-600">
                <i data-lucide="link-2" class="w-3 h-3 flex-shrink-0"></i>
                <span>CartPanda: <strong><?= e($product['external_product_id']) ?></strong></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($product['checkout_url'])): ?>
            <div class="flex items-center gap-1 text-[10px] text-amber-600">
                <i data-lucide="shopping-cart" class="w-3 h-3 flex-shrink-0"></i>
                <span>Checkout configurado no produto global</span>
            </div>
            <?php endif; ?>
            <?php if (!empty($product['is_public'])): ?>
            <div class="flex items-center gap-1 text-[10px] text-emerald-600">
                <i data-lucide="unlock" class="w-3 h-3 flex-shrink-0"></i>
                <span>Liberado para qualquer membro logado</span>
            </div>
            <?php elseif (!empty($product['webhook_token'])): ?>
            <div class="flex items-center gap-1 max-w-md">
                <i data-lucide="webhook" class="w-3 h-3 text-gray-400 flex-shrink-0"></i>
                <input type="text" value="<?= rtrim(env('APP_URL', ''), '/') . '/webhook/' . $product['webhook_token'] ?>" readonly
                       class="bg-transparent border-none text-[10px] text-gray-400 font-mono focus:ring-0 p-0 w-full truncate" onclick="this.select()">
                <button type="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); this.innerHTML='<i data-lucide=\'check\' class=\'w-3 h-3 text-green-500\'></i>'; setTimeout(()=>{this.innerHTML='<i data-lucide=\'copy\' class=\'w-3 h-3\'></i>'; lucide.createIcons();}, 1200); lucide.createIcons();" class="text-gray-400 hover:text-brand-600 flex-shrink-0">
                    <i data-lucide="copy" class="w-3 h-3"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="<?= url('/funnels/' . $funnel['id'] . '/products/' . $product['id'] . '/edit') ?>" class="p-2 rounded-lg text-gray-400 hover:text-blue-500 hover:bg-blue-50 transition" title="Editar">
                <i data-lucide="pencil" class="w-4 h-4"></i>
            </a>
            <form method="POST" action="<?= url('/funnels/' . $funnel['id'] . '/products/' . $product['id'] . '/duplicate') ?>" onsubmit="return confirm('Duplicar este produto? Um novo webhook será gerado.')">
                <?= csrf_field() ?>
                <button type="submit" class="p-2 rounded-lg text-gray-400 hover:text-purple-500 hover:bg-purple-50 transition" title="Duplicar">
                    <i data-lucide="copy" class="w-4 h-4"></i>
                </button>
            </form>
            <form method="POST" action="<?= url('/funnels/' . $funnel['id'] . '/products/' . $product['id'] . '/delete') ?>" onsubmit="return confirm('Remover este produto deste funil? O cadastro global continuará disponível.')">
                <?= csrf_field() ?>
                <button type="submit" class="p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition" title="Remover">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- SortableJS CDN (Robust implementation) -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('products-sortable');
    if (!container) return;
    const csrfToken = '<?= csrf_token() ?>';
    const reorderUrl = '<?= e($reorderUrl) ?>';
    let previousOrder = currentOrder();

    function currentOrder() {
        return Array.from(container.querySelectorAll('.product-sortable-item')).map(item => parseInt(item.dataset.id, 10));
    }

    function applyOrder(order) {
        const items = new Map(Array.from(container.querySelectorAll('.product-sortable-item')).map(item => [parseInt(item.dataset.id, 10), item]));
        order.forEach(id => {
            const item = items.get(parseInt(id, 10));
            if (item) container.appendChild(item);
        });
    }

    new Sortable(container, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'opacity-50',
        onStart: function() {
            previousOrder = currentOrder();
        },
        onEnd: async function(evt) {
            // Prevent sending if position didn't actually change
            if (evt.oldIndex === evt.newIndex) return;
            
            const order = currentOrder();
            
            // UI Feedback imediato de carregamento
            container.style.opacity = '0.7';
            container.style.pointerEvents = 'none';
            
            try {
                const formData = new FormData();
                formData.append('_csrf_token', csrfToken);
                order.forEach(id => formData.append('order[]', id));

                const res = await fetch(reorderUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const text = await res.text();
                let data = {};
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (parseError) {
                    throw new Error('Resposta invalida do servidor ao salvar a ordem.');
                }

                if (!res.ok || !data.success) {
                    applyOrder(previousOrder);
                    alert('Erro ao reordenar: ' + (data.error || 'Tente novamente'));
                    return;
                }

                if (Array.isArray(data.order)) {
                    applyOrder(data.order);
                    previousOrder = data.order.map(id => parseInt(id, 10));
                } else {
                    previousOrder = order;
                }

                // Flash visual de sucesso
                container.style.outline = '2px solid #22c55e';
                setTimeout(() => container.style.outline = '', 600);
            } catch(e) {
                console.error('Reorder error:', e);
                applyOrder(previousOrder);
                alert('Erro ao salvar ordem. Verifique a conexão.');
            } finally {
                container.style.opacity = '1';
                container.style.pointerEvents = 'auto';
            }
        }
    });
});
</script>
<?php endif; ?>
<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
