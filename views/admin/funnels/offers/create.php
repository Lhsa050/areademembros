<?php
$title = 'Nova Oferta - ' . $funnel['name'];
ob_start();
?>
<div class="mb-6">
    <a href="<?= url('/funnels/' . $funnel['id'] . '/offers') ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Ofertas
    </a>
</div>
<div class="max-w-2xl">
    <h2 class="text-xl font-bold text-gray-800 mb-6">Nova Oferta</h2>
    <form method="POST" action="<?= url('/funnels/' . $funnel['id'] . '/offers') ?>" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm border p-6 space-y-5">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
            <input type="text" name="title" required class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Ex: Oferta Especial! Acesse todos os cursos">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
            <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Descrição da oferta que aparecerá no popup"></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Imagem da Oferta</label>
            <input type="file" name="image" accept="image/*" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
            <p class="text-xs text-gray-500 mt-1">Imagem exibida no popup (recomendado: 600x400px)</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">URL de Checkout</label>
            <input type="url" name="checkout_url" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="https://checkout.exemplo.com/oferta">
            <p class="text-xs text-gray-500 mt-1">Link para a página de compra. O botão do popup levará para este link.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Valor fiscal padrao</label>
            <input type="text" name="price" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Ex: 197,00">
            <p class="text-xs text-gray-500 mt-1">Usado se o webhook nao enviar o valor da compra.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Produtos liberados ao comprar</label>
            <p class="text-xs text-gray-500 mb-3">Quando alguém comprar via webhook desta oferta, os produtos selecionados serão liberados automaticamente.</p>
            <div class="space-y-2 max-h-48 overflow-y-auto border rounded-lg p-3">
                <?php if (empty($products)): ?>
                <p class="text-sm text-gray-400">Nenhum produto neste funil</p>
                <?php else: ?>
                <?php foreach ($products as $product): ?>
                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="products[]" value="<?= $product['id'] ?>" class="w-4 h-4 rounded border-gray-300 text-blue-500">
                    <span class="text-sm text-gray-700"><?= e($product['title']) ?></span>
                    <span class="text-xs text-gray-400 ml-auto"><?= $product['type'] === 'video' ? 'Vídeo' : 'PDF' ?></span>
                </label>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 space-y-3">
            <label class="block text-sm font-medium text-slate-700">
                <i data-lucide="receipt-text" class="w-4 h-4 inline mr-1"></i> Perfil fiscal da oferta
            </label>
            <select name="fiscal_kind" class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-white">
                <option value="course">Curso online</option>
                <option value="ebook">Ebook / livro digital</option>
                <option value="saas">SaaS / software</option>
                <option value="other">Outro servico digital</option>
            </select>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <input type="text" name="fiscal_service_code" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Codigo servico NFS-e">
                <input type="text" name="fiscal_nbs_code" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="NBS opcional">
            </div>
            <input type="text" name="fiscal_service_description" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Descricao fiscal especifica">
        </div>

        <div class="border-t pt-4 space-y-4">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="show_as_popup" value="1" class="w-5 h-5 rounded border-gray-300 text-blue-500">
                <div>
                    <span class="text-sm font-medium text-gray-700">Mostrar Oferta como Popup</span>
                    <p class="text-xs text-gray-500">Se ativado, esta oferta aparecerá como um popup na tela além de estar na lista de produtos. Recomendado apenas 1 oferta em Popup por vez.</p>
                </div>
            </label>

            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" checked class="w-5 h-5 rounded border-gray-300 text-blue-500">
                <div>
                    <span class="text-sm font-medium text-gray-700">Ativar oferta</span>
                    <p class="text-xs text-gray-500">Ofertas ativas aparecerão na lista de produtos dos membros.</p>
                </div>
            </label>
        </div>

        <button type="submit" class="w-full bg-blue-500 text-white py-3 rounded-lg font-medium hover:bg-blue-600 transition">Criar Oferta</button>
    </form>
</div>
<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
