<?php
/**
 * Gerador de HTML
 */
$title = 'Gerar HTML';

$generatedLinks = $_SESSION['generated_links'] ?? null;
$generatedId = $_SESSION['generated_id'] ?? null;
unset($_SESSION['generated_links'], $_SESSION['generated_id']);

ob_start();
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Formulário -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h3 class="font-semibold text-gray-800 mb-4">Configurar Área de Membros</h3>
            
            <form method="POST" action="<?= url('/generator') ?>" class="space-y-6">
                <?= csrf_field() ?>
                
                <div>
                    <label for="site_name" class="block text-sm font-medium text-gray-700 mb-2">Nome do Site</label>
                    <input type="text" id="site_name" name="site_name" value="<?= e(old('site_name')) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ex: Meu Curso Premium" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Escolha o Tema</label>
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Masculino Light -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="theme" value="masculino-light" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl hover:border-blue-300 peer-checked:border-blue-500 peer-checked:ring-2 peer-checked:ring-blue-200 transition">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-6 h-6 rounded-full bg-blue-500"></div>
                                    <div class="w-6 h-6 rounded-full bg-gray-100 border border-gray-200"></div>
                                </div>
                                <p class="font-medium text-gray-800">Masculino Light</p>
                                <p class="text-xs text-gray-500">Azul, fundo claro</p>
                            </div>
                        </label>
                        
                        <!-- Masculino Dark -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="theme" value="masculino-dark" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl hover:border-blue-300 peer-checked:border-blue-500 peer-checked:ring-2 peer-checked:ring-blue-200 transition">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-6 h-6 rounded-full bg-blue-400"></div>
                                    <div class="w-6 h-6 rounded-full bg-gray-900 border border-gray-700"></div>
                                </div>
                                <p class="font-medium text-gray-800">Masculino Dark</p>
                                <p class="text-xs text-gray-500">Azul, fundo escuro</p>
                            </div>
                        </label>
                        
                        <!-- Feminino Light -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="theme" value="feminino-light" class="peer sr-only" checked>
                            <div class="p-4 border-2 border-gray-200 rounded-xl hover:border-blue-300 peer-checked:border-blue-500 peer-checked:ring-2 peer-checked:ring-blue-200 transition">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-6 h-6 rounded-full bg-amber-400"></div>
                                    <div class="w-6 h-6 rounded-full bg-rose-50 border border-rose-100"></div>
                                </div>
                                <p class="font-medium text-gray-800">Feminino Light</p>
                                <p class="text-xs text-gray-500">Dourado, fundo rose</p>
                            </div>
                        </label>
                        
                        <!-- Feminino Dark -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="theme" value="feminino-dark" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl hover:border-blue-300 peer-checked:border-blue-500 peer-checked:ring-2 peer-checked:ring-blue-200 transition">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-6 h-6 rounded-full bg-pink-400"></div>
                                    <div class="w-6 h-6 rounded-full bg-purple-900 border border-purple-800"></div>
                                </div>
                                <p class="font-medium text-gray-800">Feminino Dark</p>
                                <p class="text-xs text-gray-500">Rosa, fundo roxo</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:from-blue-600 hover:to-blue-700 transition shadow-lg flex items-center justify-center gap-2">
                    <i data-lucide="wand-2" class="w-5 h-5"></i>
                    Gerar HTML
                </button>
            </form>
        </div>
        
        <?php if ($generatedLinks): ?>
        <!-- Links Gerados -->
        <div class="bg-green-50 rounded-xl border border-green-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center">
                    <i data-lucide="check" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-green-800">HTML Gerado com Sucesso!</h3>
                    <p class="text-sm text-green-600">Copie os links abaixo para compartilhar com seus clientes.</p>
                </div>
            </div>
            
            <div class="space-y-3 mb-4">
                <?php foreach ($generatedLinks as $link): ?>
                <div class="bg-white p-4 rounded-lg border border-green-100">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium text-gray-800"><?= e($link['name']) ?></span>
                        <span class="text-xs text-gray-500">Senha: <code class="bg-gray-100 px-2 py-1 rounded"><?= e($link['password']) ?></code></span>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" value="arquivo.html<?= $link['param'] ?>" class="flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded text-sm font-mono" readonly>
                        <button onclick="copyToClipboard(this.previousElementSibling)" class="bg-blue-500 text-white px-3 py-2 rounded text-sm hover:bg-blue-600 transition">
                            Copiar
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="flex gap-3">
                <a href="<?= url('/generator/download/' . $generatedId) ?>" class="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-green-700 transition text-center flex items-center justify-center gap-2">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    Baixar HTML
                </a>
                <a href="<?= url('/generator/preview/' . $generatedId) ?>" target="_blank" class="flex-1 bg-white border border-green-300 text-green-700 py-2 px-4 rounded-lg font-medium hover:bg-green-50 transition text-center flex items-center justify-center gap-2">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                    Visualizar
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar -->
    <div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
            <h4 class="font-medium text-gray-800 mb-3">Resumo</h4>
            
            <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Produtos</span>
                    <span class="font-medium text-gray-800"><?= \App\Models\Product::count() ?></span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Níveis de Acesso</span>
                    <span class="font-medium text-gray-800"><?= count($levels) ?></span>
                </div>
            </div>
            
            <?php if (empty($levels)): ?>
            <div class="mt-4 p-3 bg-amber-50 rounded-lg border border-amber-200">
                <p class="text-xs text-amber-700">
                    <i data-lucide="alert-triangle" class="w-3 h-3 inline"></i>
                    Crie pelo menos um nível de acesso antes de gerar.
                </p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($generations)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <h4 class="font-medium text-gray-800 mb-3">Histórico</h4>
            
            <div class="space-y-2">
                <?php foreach (array_slice($generations, 0, 5) as $gen): ?>
                <div class="flex items-center justify-between text-sm p-2 hover:bg-gray-50 rounded">
                    <div class="flex-1 min-w-0 mr-2">
                        <p class="font-medium text-gray-800 truncate"><?= e($gen['site_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= date('d/m H:i', strtotime($gen['created_at'])) ?></p>
                    </div>
                    <a href="<?= url('/generator/download/' . $gen['id']) ?>" class="text-blue-500 hover:text-blue-700">
                        <i data-lucide="download" class="w-4 h-4"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copyToClipboard(input) {
    input.select();
    document.execCommand('copy');
    
    const btn = input.nextElementSibling;
    const originalText = btn.textContent;
    btn.textContent = 'Copiado!';
    btn.classList.remove('bg-blue-500', 'hover:bg-blue-600');
    btn.classList.add('bg-green-500');
    
    setTimeout(() => {
        btn.textContent = originalText;
        btn.classList.remove('bg-green-500');
        btn.classList.add('bg-blue-500', 'hover:bg-blue-600');
    }, 2000);
}
</script>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
