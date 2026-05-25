<?php
/**
 * Criar Nível de Acesso
 */
$title = 'Novo Nível de Acesso';

ob_start();
?>

<div class="max-w-2xl">
    <a href="<?= url('/access-levels') ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 mb-6">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Voltar
    </a>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="<?= url('/access-levels') ?>" class="space-y-6">
            <?= csrf_field() ?>
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nome do Nível</label>
                <input type="text" id="name" name="name" value="<?= e(old('name')) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ex: VIP, Premium, Básico" required>
                <?php if (isset($errors['name'])): ?>
                <p class="text-red-500 text-xs mt-1"><?= e($errors['name'][0]) ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Senha de Acesso</label>
                <div class="flex gap-2">
                    <input type="text" id="password" name="password" value="<?= e(old('password')) ?>" class="flex-1 px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase" placeholder="Ex: VIP2024" required>
                    <button type="button" onclick="generatePassword('simple')" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                        Simples
                    </button>
                    <button type="button" onclick="generatePassword('secure')" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                        Segura
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Esta é a senha que seus clientes usarão para acessar.</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Produtos com Acesso</label>
                <?php if (empty($products)): ?>
                <p class="text-gray-500 text-sm">Nenhum produto cadastrado. <a href="<?= url('/products/create') ?>" class="text-blue-500 hover:underline">Criar produto</a></p>
                <?php else: ?>
                <div class="space-y-2 max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-3">
                    <?php foreach ($products as $product): ?>
                    <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer">
                        <input type="checkbox" name="products[]" value="<?= $product['id'] ?>" class="w-4 h-4 text-blue-500 rounded border-gray-300 focus:ring-blue-500">
                        <i data-lucide="<?= $product['type'] === 'video' ? 'film' : 'file-text' ?>" class="w-4 h-4 text-gray-400"></i>
                        <span class="text-sm text-gray-700"><?= e($product['title']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="flex gap-4 pt-4">
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-600 transition">
                    Criar Nível
                </button>
                <a href="<?= url('/access-levels') ?>" class="px-6 py-2 rounded-lg font-medium text-gray-600 hover:bg-gray-100 transition">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
async function generatePassword(type) {
    const formData = new FormData();
    formData.append('_csrf_token', '<?= csrf_token() ?>');
    formData.append('type', type);
    
    const response = await fetch('/access-levels/generate-password', { method: 'POST', body: formData });
    const result = await response.json();
    
    document.getElementById('password').value = result.password;
}
</script>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
