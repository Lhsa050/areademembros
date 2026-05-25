<?php
$title = 'Editar Oferta - ' . $funnel['name'];
ob_start();
?>
<div class="mb-6">
    <a href="<?= url('/funnels/' . $funnel['id'] . '/offers') ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Ofertas
    </a>
</div>
<div class="max-w-2xl">
    <h2 class="text-xl font-bold text-gray-800 mb-6">Editar Oferta</h2>
    <form method="POST" action="<?= url('/funnels/' . $funnel['id'] . '/offers/' . $offer['id']) ?>" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm border p-6 space-y-5">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
            <input type="text" name="title" value="<?= e($offer['title']) ?>" required class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
            <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500"><?= e($offer['description']) ?></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Imagem da Oferta</label>
            <?php if (!empty($offer['image'])): ?>
            <div class="mb-2">
                <img src="<?= url($offer['image']) ?>" class="h-32 rounded-lg object-cover">
            </div>
            <?php endif; ?>
            <input type="file" name="image" accept="image/*" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
            <p class="text-xs text-gray-500 mt-1">Deixe vazio para manter a imagem atual</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">URL de Checkout</label>
            <input type="url" name="checkout_url" value="<?= e($offer['checkout_url']) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="https://checkout.exemplo.com/oferta">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Valor fiscal padrao</label>
            <input type="text" name="price" value="<?= isset($offer['price']) ? e(number_format((float) $offer['price'], 2, ',', '.')) : '' ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Ex: 197,00">
            <p class="text-xs text-gray-500 mt-1">Usado se o webhook nao enviar o valor da compra.</p>
        </div>

        <!-- Webhook URL -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Webhook da Oferta</label>
            <div class="flex items-center gap-2 bg-gray-50 rounded-lg border px-3 py-2">
                <i data-lucide="webhook" class="w-4 h-4 text-gray-400"></i>
                <input type="text" value="<?= rtrim(env('APP_URL', ''), '/') . '/webhook/' . $offer['webhook_token'] ?>" readonly
                       class="flex-1 bg-transparent border-none text-xs text-gray-600 font-mono focus:ring-0 p-0" onclick="this.select()">
                <button type="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); this.textContent='Copiado!'; setTimeout(()=>this.textContent='Copiar', 1500)" class="text-blue-500 text-xs font-medium hover:underline">Copiar</button>
            </div>
            <p class="text-xs text-gray-500 mt-1">Use este webhook no CartPanda ou Hotmart para liberar automaticamente os produtos vinculados.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Produtos liberados ao comprar</label>
            <div class="space-y-2 max-h-48 overflow-y-auto border rounded-lg p-3">
                <?php foreach ($products as $product): ?>
                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="products[]" value="<?= $product['id'] ?>"
                           <?= in_array($product['id'], $selectedProductIds) ? 'checked' : '' ?>
                           class="w-4 h-4 rounded border-gray-300 text-blue-500">
                    <span class="text-sm text-gray-700"><?= e($product['title']) ?></span>
                    <span class="text-xs text-gray-400 ml-auto"><?= $product['type'] === 'video' ? 'Vídeo' : 'PDF' ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 space-y-3">
            <label class="block text-sm font-medium text-slate-700">
                <i data-lucide="receipt-text" class="w-4 h-4 inline mr-1"></i> Perfil fiscal da oferta
            </label>
            <?php $kind = $offer['fiscal_kind'] ?? 'course'; ?>
            <select name="fiscal_kind" class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-white">
                <option value="course" <?= $kind === 'course' ? 'selected' : '' ?>>Curso online</option>
                <option value="ebook" <?= $kind === 'ebook' ? 'selected' : '' ?>>Ebook / livro digital</option>
                <option value="saas" <?= $kind === 'saas' ? 'selected' : '' ?>>SaaS / software</option>
                <option value="other" <?= $kind === 'other' ? 'selected' : '' ?>>Outro servico digital</option>
            </select>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <input type="text" name="fiscal_service_code" value="<?= e($offer['fiscal_service_code'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Codigo servico NFS-e">
                <input type="text" name="fiscal_nbs_code" value="<?= e($offer['fiscal_nbs_code'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="NBS opcional">
            </div>
            <input type="text" name="fiscal_service_description" value="<?= e($offer['fiscal_service_description'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Descricao fiscal especifica">
        </div>

        <div class="border-t pt-4 space-y-4">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="show_as_popup" value="1" <?= !empty($offer['show_as_popup']) ? 'checked' : '' ?> class="w-5 h-5 rounded border-gray-300 text-blue-500">
                <div>
                    <span class="text-sm font-medium text-gray-700">Mostrar Oferta como Popup</span>
                    <p class="text-xs text-gray-500">Se ativado, esta oferta aparecerá como um popup na tela além de estar na lista de produtos. Recomendado apenas 1 oferta em Popup por vez.</p>
                </div>
            </label>

            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" <?= $offer['is_active'] ? 'checked' : '' ?> class="w-5 h-5 rounded border-gray-300 text-blue-500">
                <div>
                    <span class="text-sm font-medium text-gray-700">Ativar oferta</span>
                    <p class="text-xs text-gray-500">Ofertas ativas aparecerão na lista de produtos dos membros.</p>
                </div>
            </label>
        </div>

        <button type="submit" class="w-full bg-blue-500 text-white py-3 rounded-lg font-medium hover:bg-blue-600 transition">Salvar Alterações</button>
    </form>
</div>
<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
