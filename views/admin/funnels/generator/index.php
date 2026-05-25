<?php
$title = 'Links de Acesso - ' . $funnel['name'];
$filename = 'area-membros-funil-' . $funnel['id'] . '.html';
$filePath = ABSPATH . '/storage/generated/' . $filename;
$fileExists = file_exists($filePath);
$fileModified = $fileExists ? date('d/m/Y H:i:s', filemtime($filePath)) : null;
ob_start();
?>
<div class="mb-6">
    <a href="<?= url('/funnels/' . $funnel['id']) ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        <?= e($funnel['name']) ?>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <!-- Links de Checkout por Nível -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-semibold text-gray-800">Links de Acesso</h3>
                    <p class="text-sm text-gray-500">Envie estes links para os clientes após a compra.</p>
                </div>
                <?php if ($fileExists): ?>
                <span class="text-xs text-green-600 bg-green-50 px-3 py-1 rounded-full font-medium">Ativo</span>
                <?php else: ?>
                <span class="text-xs text-amber-600 bg-amber-50 px-3 py-1 rounded-full font-medium">Não gerado</span>
                <?php endif; ?>
            </div>
            
            <?php $levels = $levels ?? []; ?>
            <?php if (!empty($levels)): ?>
            <div class="space-y-4">
                <?php foreach ($levels as $level): ?>
                <div class="border rounded-lg p-4 hover:bg-gray-50 transition">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                                <i data-lucide="key" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <span class="font-medium text-gray-800"><?= e($level['name']) ?></span>
                                <p class="text-xs text-gray-500"><?= count($level['product_ids'] ?? []) ?> produtos vinculados</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                <i data-lucide="info" class="w-4 h-4"></i>
                O acesso agora é controlado pelo <a href="<?= url('/funnels/' . $funnel['id'] . '/members') ?>" class="underline font-medium">Sistema de Membros</a>. Os membros fazem login em <code>/m/<?= e($funnel['slug']) ?>/login</code>.
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div>
        <!-- Status do Arquivo -->
        <div class="bg-white rounded-xl shadow-sm border p-4 mb-4">
            <h4 class="font-medium text-gray-800 mb-3">Arquivo Gerado</h4>
            <div class="bg-gray-50 rounded-lg p-3 mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
                        <i data-lucide="file-code" class="w-5 h-5 text-blue-500"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-mono text-xs text-gray-800 truncate"><?= e($filename) ?></p>
                        <?php if ($fileExists): ?>
                        <p class="text-xs text-gray-500"><?= $fileModified ?></p>
                        <?php else: ?>
                        <p class="text-xs text-gray-500">Não gerado ainda</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($fileExists): ?>
            <a href="<?= url('/funnels/' . $funnel['id'] . '/generator/download') ?>" 
               class="w-full bg-blue-500 text-white py-2.5 rounded-lg font-medium hover:bg-blue-600 text-sm flex items-center justify-center gap-2 transition">
                <i data-lucide="download" class="w-4 h-4"></i> Download ZIP
            </a>
            <p class="text-xs text-gray-500 mt-2 text-center">Inclui HTML + imagens + arquivos</p>
            <?php endif; ?>
        </div>
        
        <!-- Regenerar -->
        <div class="bg-white rounded-xl shadow-sm border p-4 mb-4">
            <h4 class="font-medium text-gray-800 mb-2">Regenerar</h4>
            <p class="text-xs text-gray-500 mb-3">O arquivo é atualizado automaticamente. Use isso apenas se precisar forçar.</p>
            <form method="POST" action="<?= url('/funnels/' . $funnel['id'] . '/generator') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="w-full bg-gray-100 text-gray-700 py-2.5 rounded-lg font-medium hover:bg-gray-200 text-sm flex items-center justify-center gap-2 transition">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i> Regenerar
                </button>
            </form>
        </div>
        
        <!-- Resumo -->
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <h4 class="font-medium text-gray-800 mb-3">Configurações</h4>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Nome do Site:</span>
                    <span class="font-medium text-gray-800"><?= e($funnel['site_name'] ?: $funnel['name']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Tema:</span>
                    <span class="font-medium text-gray-800"><?= ucwords(str_replace('-', ' ', $funnel['theme'])) ?></span>
                </div>
                <div class="flex justify-between pt-2 border-t">
                    <span class="text-gray-500">Produtos:</span>
                    <span class="font-medium text-gray-800"><?= count(\App\Models\Product::getByFunnel((int) $funnel['id'])) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Membros:</span>
                    <span class="font-medium text-gray-800"><?= \App\Models\Member::countByFunnel($funnel['id']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyLink(inputId) {
    const input = document.getElementById(inputId);
    input.select();
    document.execCommand('copy');
    
    const btn = input.nextElementSibling;
    const icon = btn.querySelector('i');
    const originalHTML = btn.innerHTML;
    
    btn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> Copiado!';
    btn.classList.replace('bg-blue-500', 'bg-green-500');
    btn.classList.replace('hover:bg-blue-600', 'hover:bg-green-600');
    
    lucide.createIcons();
    
    setTimeout(() => {
        btn.innerHTML = originalHTML;
        btn.classList.replace('bg-green-500', 'bg-blue-500');
        btn.classList.replace('hover:bg-green-600', 'hover:bg-blue-600');
        lucide.createIcons();
    }, 2000);
}
</script>
<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
