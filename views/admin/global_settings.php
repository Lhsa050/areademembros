<?php
/**
 * View: Configuracoes gerais do sistema.
 */
$title = 'Configuracoes';
ob_start();
?>

<div class="max-w-4xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Configuracoes Gerais</h2>
            <p class="text-sm text-gray-500 mt-1">Itens que pertencem ao sistema inteiro.</p>
        </div>
        <a href="<?= url('/settings/database') ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition">
            <i data-lucide="database" class="w-4 h-4"></i>
            Banco de Dados & Backups
        </a>
    </div>

    <form action="<?= url('/settings') ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">
                <i data-lucide="image" class="w-5 h-5 text-emerald-500"></i>
                Imagens
            </h3>
            <p class="text-sm text-gray-500 mb-6">Configuracao global usada na compressao dos uploads.</p>
            <div>
                <label for="webp_quality" class="block text-sm font-medium text-gray-700 mb-1">Qualidade WebP (1-100)</label>
                <input type="number" id="webp_quality" name="webp_quality" value="<?= e((string) $settings['webp_quality']) ?>"
                       min="1" max="100"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none">
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">
                <i data-lucide="smartphone" class="w-5 h-5 text-blue-500"></i>
                App (PWA)
            </h3>
            <p class="text-sm text-gray-500 mb-6">Icone que aparece ao instalar o app no celular.</p>

            <div class="flex flex-col sm:flex-row sm:items-start gap-6">
                <div class="flex-shrink-0">
                    <?php
                    $icon192 = ABSPATH . '/public/assets/images/icon-192.png';
                    $icon512 = ABSPATH . '/public/assets/images/icon-512.png';
                    $hasCustomIcon = file_exists($icon512) || file_exists($icon192);
                    $iconPreview = $hasCustomIcon
                        ? url('/assets/images/' . (file_exists($icon512) ? 'icon-512.png' : 'icon-192.png')) . '?t=' . time()
                        : url('/assets/images/pwa-icon.php?s=192');
                    ?>
                    <div class="w-24 h-24 rounded-2xl overflow-hidden border-2 border-gray-200 bg-gray-50 shadow-inner">
                        <img src="<?= $iconPreview ?>" alt="Icone atual" class="w-full h-full object-cover" id="icon-preview">
                    </div>
                    <span class="text-xs <?= $hasCustomIcon ? 'text-green-600' : 'text-gray-400' ?> mt-1 block text-center">
                        <?= $hasCustomIcon ? 'Personalizado' : 'Padrao' ?>
                    </span>
                </div>

                <div class="flex-1 space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Enviar novo icone</label>
                        <input type="file" name="pwa_icon" accept="image/png,image/jpeg,image/webp"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                               onchange="previewIcon(this)">
                        <p class="text-xs text-gray-400 mt-1">PNG, JPEG ou WebP. O sistema gera os tamanhos 192px e 512px.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">
                <i data-lucide="bell" class="w-5 h-5 text-orange-500"></i>
                Notificacoes Push (VAPID)
            </h3>
            <p class="text-sm text-gray-500 mb-6">Chaves globais usadas para notificacoes push.</p>
            <div class="space-y-5">
                <div>
                    <label for="vapid_public_key" class="block text-sm font-medium text-gray-700 mb-1">Chave Publica</label>
                    <input type="text" id="vapid_public_key" name="vapid_public_key" value="<?= e($settings['vapid_public_key'] ?? '') ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none font-mono bg-gray-50" readonly>
                </div>
                <div>
                    <label for="vapid_private_key" class="block text-sm font-medium text-gray-700 mb-1">Chave Privada</label>
                    <input type="text" id="vapid_private_key" name="vapid_private_key" value="<?= e($settings['vapid_private_key'] ?? '') ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none font-mono bg-gray-50" readonly>
                </div>
                <?php if (!empty($settings['vapid_public_key'] ?? '')): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-3 flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-green-600"></i>
                    <span class="text-sm text-green-700">Chaves VAPID geradas automaticamente.</span>
                </div>
                <?php else: ?>
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                    <span class="text-sm text-amber-700">Nao foi possivel gerar as chaves. Verifique o OpenSSL no servidor.</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-8 py-3 rounded-lg text-sm font-semibold flex items-center gap-2 transition shadow-sm">
            <i data-lucide="save" class="w-4 h-4"></i>
            Salvar Configuracoes
        </button>
    </form>
</div>

<script>
function previewIcon(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('icon-preview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
