<?php
/**
 * View: Mapeamento de produtos do XLSX -> produtos/ofertas do funil
 */
$title = 'Mapear Produtos — Importação';
$funnelOffers = $funnelOffers ?? [];
ob_start();
?>

<div class="max-w-3xl mx-auto">

    <div class="mb-6">
        <a href="<?= url('/funnels/' . $funnel['id'] . '/import') ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Voltar
        </a>
    </div>

    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                <i data-lucide="git-compare" class="w-7 h-7 text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Mapear Produtos</h1>
                <p class="text-sm text-gray-500"><?= e($funnel['name']) ?> — Passo 2</p>
            </div>
        </div>
    </div>

    <!-- Resumo da análise -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-800"><?= $totalRows ?></p>
            <p class="text-xs text-gray-500 mt-1">Linhas no arquivo</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600"><?= $paidRows ?></p>
            <p class="text-xs text-gray-500 mt-1">Registros Paid ✅</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-red-400"><?= $skippedRows ?></p>
            <p class="text-xs text-gray-500 mt-1">Ignorados (não Paid)</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600"><?= $memberCount ?></p>
            <p class="text-xs text-gray-500 mt-1">Membros únicos</p>
        </div>
    </div>

    <?php if (!empty($invalidEmailRows ?? 0)): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 text-sm text-amber-800">
        <?= (int) $invalidEmailRows ?> linha(s) Paid foram ignoradas por email ausente, invalido ou muito longo.
    </div>
    <?php endif; ?>

    <?php if (empty($funnelProducts) && empty($funnelOffers) && !empty($importProducts)): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6">
        <p class="text-amber-800 font-medium">Este funil nao tem produtos nem ofertas cadastradas.</p>
        <p class="text-sm text-amber-700 mt-1">Crie um produto ou uma oferta no funil antes de importar os membros.</p>
        <a href="<?= url('/funnels/' . $funnel['id'] . '/products/create') ?>" class="inline-flex items-center gap-2 mt-3 bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-700 transition">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Criar Produto
        </a>
    </div>
    <?php else: ?>

    <!-- Formulário de mapeamento -->
    <form action="<?= url('/funnels/' . $funnel['id'] . '/import/process') ?>" method="POST" id="mappingForm">
        <?= csrf_field() ?>
        <input type="hidden" name="job_token" value="<?= e($jobToken ?? '') ?>">

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="text-base font-semibold text-gray-800 mb-1 flex items-center gap-2">
                <i data-lucide="link" class="w-5 h-5 text-blue-500"></i>
                Associe cada produto do arquivo a um destino no funil
            </h3>
            <p class="text-sm text-gray-500 mb-6">Foram encontrados <strong><?= count($importProducts) ?></strong> produto(s) distintos no arquivo. Voce pode apontar cada um para um produto unico ou para uma oferta com varios produtos.</p>

            <div class="space-y-4">
                <?php foreach ($importProducts as $key => $prod): ?>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="flex items-center gap-4">
                        <!-- Produto do XLSX -->
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 font-medium mb-1">No arquivo</p>
                            <p class="text-sm font-semibold text-gray-800"><?= e($prod['product_name']) ?></p>
                            <?php if ($prod['product_id']): ?>
                            <p class="text-xs text-gray-400 font-mono mt-0.5">ID: <?= e($prod['product_id']) ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Seta -->
                        <div class="text-gray-300">
                            <i data-lucide="arrow-right" class="w-6 h-6"></i>
                        </div>

                        <!-- Dropdown: produto ou oferta do funil -->
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 font-medium mb-1">Destino no funil</p>
                            <select name="product_mapping[<?= e($key) ?>]" required
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white">
                                <option value="">— Selecione —</option>
                                <?php if (!empty($funnelProducts)): ?>
                                <optgroup label="Produtos">
                                <?php foreach ($funnelProducts as $fp): ?>
                                <option value="product:<?= $fp['id'] ?>"><?= e($fp['title']) ?></option>
                                <?php endforeach; ?>
                                </optgroup>
                                <?php endif; ?>
                                <?php if (!empty($funnelOffers)): ?>
                                <optgroup label="Ofertas">
                                <?php foreach ($funnelOffers as $offer): ?>
                                <option value="offer:<?= $offer['id'] ?>" <?= empty($offer['product_count']) ? 'disabled' : '' ?>>
                                    <?= e($offer['title']) ?> (<?= (int) $offer['product_count'] ?> produto<?= (int) $offer['product_count'] === 1 ? '' : 's' ?>)
                                </option>
                                <?php endforeach; ?>
                                </optgroup>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Botão Importar -->
        <div class="flex items-center justify-between pb-8">
            <a href="<?= url('/funnels/' . $funnel['id'] . '/import') ?>" class="text-gray-500 hover:text-gray-700 text-sm font-medium flex items-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Enviar outro arquivo
            </a>
            <button type="submit" id="btn-import"
                    class="bg-emerald-500 hover:bg-emerald-600 text-white px-8 py-3 rounded-lg text-sm font-bold flex items-center gap-2 transition shadow-sm">
                <i data-lucide="download" class="w-5 h-5"></i>
                Importar <?= $memberCount ?> membros
            </button>
        </div>
    </form>

    <?php endif; ?>
</div>

<script>
document.getElementById('mappingForm')?.addEventListener('submit', function() {
    var btn = document.getElementById('btn-import');
    btn.disabled = true;
    btn.classList.add('opacity-60');
    btn.innerHTML = '<svg class="animate-spin w-5 h-5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.3"></circle><path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor"></path></svg> Importando...';
});
</script>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
