<?php
/**
 * Editar Funil
 */
$title = 'Editar Funil';
ob_start();
?>
<div class="max-w-2xl">
    <a href="<?= url('/funnels/' . $funnel['id']) ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 mb-6">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Voltar
    </a>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <form method="POST" action="<?= url('/funnels/' . $funnel['id']) ?>" class="space-y-6">
            <?= csrf_field() ?>
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nome do Funil</label>
                <input type="text" id="name" name="name" value="<?= e($funnel['name']) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label for="site_name" class="block text-sm font-medium text-gray-700 mb-2">Nome do Site</label>
                <input type="text" id="site_name" name="site_name" value="<?= e($funnel['site_name']) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
            </div>
            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Slug da URL</label>
                <div class="flex items-center gap-2">
                    <span class="text-gray-500 text-sm">seudominio.com/</span>
                    <input type="text" id="slug" name="slug" value="<?= e($funnel['slug'] ?? '') ?>" class="flex-1 px-4 py-2 border border-gray-200 rounded-lg font-mono text-sm" placeholder="meu-curso">
                </div>
                <p class="text-xs text-gray-500 mt-1">Use apenas letras, números e hífens.</p>
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                <textarea id="description" name="description" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg"><?= e($funnel['description']) ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Tema</label>
                <?php 
                $themes = [
                    'minimalista' => ['color1' => 'bg-indigo-700', 'color2' => 'bg-gray-50', 'desc' => 'Violeta profundo'],
                    'elegante-escuro' => ['color1' => 'bg-yellow-700', 'color2' => 'bg-gray-900', 'desc' => 'Grafite com ouro'],
                    'elegante-claro' => ['color1' => 'bg-yellow-800', 'color2' => 'bg-gray-50', 'desc' => 'Marfim com ouro'],
                    'moderno-azul' => ['color1' => 'bg-blue-700', 'color2' => 'bg-slate-100', 'desc' => 'Tecnologia & negócios'],
                    'moderno-verde' => ['color1' => 'bg-emerald-700', 'color2' => 'bg-emerald-50', 'desc' => 'Verde equilibrado'],
                    'premium-dourado' => ['color1' => 'bg-orange-700', 'color2' => 'bg-amber-50', 'desc' => 'Cobre premium'],
                ];
                ?>
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
                    <?php foreach ($themes as $themeKey => $themeData): ?>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="theme" value="<?= $themeKey ?>" class="peer sr-only" <?= ($funnel['theme'] ?? 'minimalista') === $themeKey ? 'checked' : '' ?>>
                        <div class="p-3 border-2 border-gray-200 rounded-lg peer-checked:border-blue-500 transition">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-4 h-4 rounded-full <?= $themeData['color1'] ?>"></div>
                                <div class="w-4 h-4 rounded-full <?= $themeData['color2'] ?> border"></div>
                            </div>
                            <p class="font-medium text-gray-800 text-xs"><?= ucwords(str_replace('-', ' ', $themeKey)) ?></p>
                            <p class="text-xs text-gray-500"><?= $themeData['desc'] ?></p>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="border-t pt-4 mt-2">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="auto_organize" value="1" <?= !empty($funnel['auto_organize']) ? 'checked' : '' ?> class="w-5 h-5 rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                    <div>
                        <span class="text-sm font-medium text-gray-700">Organização automática</span>
                        <p class="text-xs text-gray-500">Separar "Meus Produtos" (comprados) e "Produtos Recomendados" (bloqueados) no painel do membro</p>
                    </div>
                </label>
            </div>
            <div>
                <label for="language" class="block text-sm font-medium text-gray-700 mb-2">Idioma da Área de Membros</label>
                <select id="language" name="language" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="pt-BR" <?= ($funnel['language'] ?? 'pt-BR') === 'pt-BR' ? 'selected' : '' ?>>🇧🇷 Português (Brasil)</option>
                    <option value="es" <?= ($funnel['language'] ?? 'pt-BR') === 'es' ? 'selected' : '' ?>>🇪🇸 Español</option>
                    <option value="en" <?= ($funnel['language'] ?? 'pt-BR') === 'en' ? 'selected' : '' ?>>🇺🇸 English</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Define o idioma dos textos na área de membros</p>
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white py-3 rounded-lg font-medium hover:bg-blue-600 transition">Salvar</button>
        </form>
    </div>
</div>

<script>
function generateSlug(text) {
    return text
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

document.getElementById('name').addEventListener('input', function() {
    var slugField = document.getElementById('slug');
    if (!slugField.dataset.edited && !slugField.value) {
        slugField.value = generateSlug(this.value);
    }
});

document.getElementById('slug').addEventListener('input', function() {
    this.dataset.edited = 'true';
});
</script>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
