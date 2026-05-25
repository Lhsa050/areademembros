<?php
$title = 'Novo Nível - ' . $funnel['name'];
ob_start();
?>
<div class="max-w-2xl">
    <a href="<?= url('/funnels/' . $funnel['id'] . '/access-levels') ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 mb-6">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Voltar
    </a>
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <form method="POST" action="<?= url('/funnels/' . $funnel['id'] . '/access-levels') ?>" class="space-y-6">
            <?= csrf_field() ?>
            <div><label class="block text-sm font-medium text-gray-700 mb-2">Nome do Nível</label><input type="text" name="name" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="Ex: VIP, Premium" required></div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Senha de Acesso</label>
                <div class="flex gap-2">
                    <input type="text" id="password" name="password" class="flex-1 px-4 py-2 border border-gray-200 rounded-lg uppercase" placeholder="Ex: VIP2024" required>
                    <button type="button" onclick="generatePassword('simple')" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Simples</button>
                    <button type="button" onclick="generatePassword('secure')" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Segura</button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Produtos com Acesso</label>
                <?php if (empty($products)): ?><p class="text-gray-500 text-sm">Nenhum produto neste funil.</p>
                <?php else: ?>
                <div class="space-y-2 max-h-60 overflow-y-auto border rounded-lg p-3">
                    <?php foreach ($products as $product): ?>
                    <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer">
                        <input type="checkbox" name="products[]" value="<?= $product['id'] ?>" class="w-4 h-4 text-blue-500 rounded">
                        <i data-lucide="<?= $product['type'] === 'video' ? 'film' : 'file-text' ?>" class="w-4 h-4 text-gray-400"></i>
                        <span class="text-sm text-gray-700"><?= e($product['title']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white py-3 rounded-lg font-medium hover:bg-blue-600 transition">Criar Nível</button>
        </form>
    </div>
</div>
<script>
async function generatePassword(type) {
    const fd = new FormData(); fd.append('_csrf_token', '<?= csrf_token() ?>'); fd.append('type', type);
    const r = await fetch('/funnels/generate-password', { method: 'POST', body: fd });
    document.getElementById('password').value = (await r.json()).password;
}
</script>
<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
