<?php
/**
 * Criar Funil
 */
$title = 'Novo Funil';

ob_start();
?>

<div class="max-w-2xl">
    <a href="<?= url('/funnels') ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 mb-6">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Voltar
    </a>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="<?= url('/funnels') ?>" class="space-y-6">
            <?= csrf_field() ?>
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nome do Funil</label>
                <input type="text" id="name" name="name" value="<?= e(old('name')) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ex: Curso de Marketing" required>
            </div>
            
            <div>
                <label for="site_name" class="block text-sm font-medium text-gray-700 mb-2">Nome do Site (aparece na área de membros)</label>
                <input type="text" id="site_name" name="site_name" value="<?= e(old('site_name')) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ex: Academia Premium">
            </div>
            
            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
                    Slug da URL
                    <span class="text-gray-400 font-normal">(será gerado automaticamente se vazio)</span>
                </label>
                <div class="flex items-center gap-2">
                    <span class="text-gray-500 text-sm">seudominio.com/</span>
                    <input type="text" id="slug" name="slug" value="<?= e(old('slug')) ?>" class="flex-1 px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm" placeholder="ex: meu-curso">
                </div>
                <p class="text-xs text-gray-500 mt-1">Use apenas letras, números e hífens. Ex: curso-marketing-2024</p>
            </div>
            
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Descrição (opcional)</label>
                <textarea id="description" name="description" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?= e(old('description')) ?></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Tema</label>
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="theme" value="minimalista" class="peer sr-only" checked>
                        <div class="p-4 border-2 border-gray-200 rounded-xl hover:border-blue-300 peer-checked:border-blue-500 transition">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-5 h-5 rounded-full bg-indigo-700"></div>
                                <div class="w-5 h-5 rounded-full bg-gray-100 border"></div>
                            </div>
                            <p class="font-medium text-gray-800 text-sm">Minimalista</p>
                            <p class="text-xs text-gray-500">Violeta profundo</p>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="theme" value="elegante-escuro" class="peer sr-only">
                        <div class="p-4 border-2 border-gray-200 rounded-xl hover:border-blue-300 peer-checked:border-blue-500 transition">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-5 h-5 rounded-full bg-yellow-700"></div>
                                <div class="w-5 h-5 rounded-full bg-gray-900 border"></div>
                            </div>
                            <p class="font-medium text-gray-800 text-sm">Elegante Escuro</p>
                            <p class="text-xs text-gray-500">Grafite com ouro</p>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="theme" value="elegante-claro" class="peer sr-only">
                        <div class="p-4 border-2 border-gray-200 rounded-xl hover:border-blue-300 peer-checked:border-blue-500 transition">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-5 h-5 rounded-full bg-yellow-800"></div>
                                <div class="w-5 h-5 rounded-full bg-gray-50 border"></div>
                            </div>
                            <p class="font-medium text-gray-800 text-sm">Elegante Claro</p>
                            <p class="text-xs text-gray-500">Marfim com ouro</p>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="theme" value="moderno-azul" class="peer sr-only">
                        <div class="p-4 border-2 border-gray-200 rounded-xl hover:border-blue-300 peer-checked:border-blue-500 transition">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-5 h-5 rounded-full bg-blue-700"></div>
                                <div class="w-5 h-5 rounded-full bg-slate-100 border"></div>
                            </div>
                            <p class="font-medium text-gray-800 text-sm">Moderno Azul</p>
                            <p class="text-xs text-gray-500">Tecnologia & negócios</p>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="theme" value="moderno-verde" class="peer sr-only">
                        <div class="p-4 border-2 border-gray-200 rounded-xl hover:border-blue-300 peer-checked:border-blue-500 transition">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-5 h-5 rounded-full bg-emerald-700"></div>
                                <div class="w-5 h-5 rounded-full bg-emerald-50 border"></div>
                            </div>
                            <p class="font-medium text-gray-800 text-sm">Moderno Verde</p>
                            <p class="text-xs text-gray-500">Verde equilibrado</p>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="theme" value="premium-dourado" class="peer sr-only">
                        <div class="p-4 border-2 border-gray-200 rounded-xl hover:border-blue-300 peer-checked:border-blue-500 transition">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-5 h-5 rounded-full bg-orange-700"></div>
                                <div class="w-5 h-5 rounded-full bg-amber-50 border"></div>
                            </div>
                            <p class="font-medium text-gray-800 text-sm">Premium Dourado</p>
                            <p class="text-xs text-gray-500">Cobre premium</p>
                        </div>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-blue-500 text-white py-3 rounded-lg font-medium hover:bg-blue-600 transition">
                Criar Funil
            </button>
        </form>
    </div>
</div>

<script>
function generateSlug(text) {
    return text
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // Remove acentos
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '') // Remove caracteres especiais
        .replace(/\s+/g, '-') // Espaços viram hífens
        .replace(/-+/g, '-') // Remove hífens duplicados
        .replace(/^-|-$/g, ''); // Remove hífens no início e fim
}

function updateSlug() {
    var slugField = document.getElementById('slug');
    if (!slugField.dataset.edited) {
        var siteName = document.getElementById('site_name').value;
        var funnelName = document.getElementById('name').value;
        slugField.value = generateSlug(siteName || funnelName);
    }
}

document.getElementById('name').addEventListener('input', updateSlug);
document.getElementById('site_name').addEventListener('input', updateSlug);

document.getElementById('slug').addEventListener('input', function() {
    this.dataset.edited = this.value ? 'true' : '';
});
</script>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
