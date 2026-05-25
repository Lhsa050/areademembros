<?php
$title = 'Configuracoes Fiscais';
ob_start();
$s = $settings;
?>
<div class="max-w-5xl space-y-6">
    <div class="flex items-center justify-between">
        <a href="<?= url('/fiscal') ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Fiscal
        </a>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
        Configure aqui a parte tecnica. Os codigos de servico, enquadramento e regras de imposto precisam bater com o que seu contador definiu para seu CNPJ e municipio.
    </div>

    <form method="POST" action="<?= url('/fiscal/settings') ?>" class="bg-white border rounded-xl p-6 space-y-8">
        <?= csrf_field() ?>

        <section class="space-y-4">
            <h3 class="font-semibold text-gray-900">Operacao</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label class="flex items-start gap-3 p-4 border rounded-lg">
                    <input type="checkbox" name="fiscal_enabled" value="1" <?= $s['fiscal_enabled'] === '1' ? 'checked' : '' ?> class="mt-1 rounded border-gray-300">
                    <span><span class="block font-medium text-gray-800">Ativar modulo fiscal</span><span class="text-xs text-gray-500">Webhooks passam a gerar vendas fiscais.</span></span>
                </label>
                <label class="flex items-start gap-3 p-4 border rounded-lg">
                    <input type="checkbox" name="fiscal_auto_issue" value="1" <?= $s['fiscal_auto_issue'] === '1' ? 'checked' : '' ?> class="mt-1 rounded border-gray-300">
                    <span><span class="block font-medium text-gray-800">Emitir automaticamente</span><span class="text-xs text-gray-500">Ao receber compra aprovada.</span></span>
                </label>
                <label class="flex items-start gap-3 p-4 border rounded-lg">
                    <input type="checkbox" name="fiscal_auto_cancel_refund" value="1" <?= $s['fiscal_auto_cancel_refund'] === '1' ? 'checked' : '' ?> class="mt-1 rounded border-gray-300">
                    <span><span class="block font-medium text-gray-800">Cancelar no reembolso</span><span class="text-xs text-gray-500">Envia evento de cancelamento.</span></span>
                </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ambiente</label>
                    <select name="fiscal_environment" class="w-full px-3 py-2 border border-gray-200 rounded-lg">
                        <option value="restricted" <?= $s['fiscal_environment'] === 'restricted' ? 'selected' : '' ?>>Homologacao / producao restrita</option>
                        <option value="production" <?= $s['fiscal_environment'] === 'production' ? 'selected' : '' ?>>Producao</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Serie DPS</label>
                    <input name="fiscal_default_series" value="<?= e($s['fiscal_default_series']) ?>" class="w-full px-3 py-2 border border-gray-200 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proximo numero DPS</label>
                    <input name="fiscal_next_dps_number" value="<?= e($s['fiscal_next_dps_number']) ?>" class="w-full px-3 py-2 border border-gray-200 rounded-lg">
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <h3 class="font-semibold text-gray-900">Emitente</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input name="fiscal_company_legal_name" value="<?= e($s['fiscal_company_legal_name']) ?>" class="px-3 py-2 border border-gray-200 rounded-lg" placeholder="Razao social">
                <input name="fiscal_company_cnpj" value="<?= e($s['fiscal_company_cnpj']) ?>" class="px-3 py-2 border border-gray-200 rounded-lg" placeholder="CNPJ">
                <input name="fiscal_company_im" value="<?= e($s['fiscal_company_im']) ?>" class="px-3 py-2 border border-gray-200 rounded-lg" placeholder="Inscricao municipal">
                <input name="fiscal_company_municipality_code" value="<?= e($s['fiscal_company_municipality_code']) ?>" class="px-3 py-2 border border-gray-200 rounded-lg" placeholder="Codigo IBGE do municipio">
                <input name="fiscal_company_email" value="<?= e($s['fiscal_company_email']) ?>" class="px-3 py-2 border border-gray-200 rounded-lg" placeholder="Email">
                <input name="fiscal_company_phone" value="<?= e($s['fiscal_company_phone']) ?>" class="px-3 py-2 border border-gray-200 rounded-lg" placeholder="Telefone">
            </div>
        </section>

        <section class="space-y-4">
            <h3 class="font-semibold text-gray-900">Tributacao NFS-e</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input name="fiscal_op_simp_nac" value="<?= e($s['fiscal_op_simp_nac']) ?>" class="px-3 py-2 border border-gray-200 rounded-lg" placeholder="opSimpNac">
                <input name="fiscal_reg_esp_trib" value="<?= e($s['fiscal_reg_esp_trib']) ?>" class="px-3 py-2 border border-gray-200 rounded-lg" placeholder="regEspTrib">
                <input name="fiscal_trib_issqn" value="<?= e($s['fiscal_trib_issqn']) ?>" class="px-3 py-2 border border-gray-200 rounded-lg" placeholder="tribISSQN">
                <input name="fiscal_tp_ret_issqn" value="<?= e($s['fiscal_tp_ret_issqn']) ?>" class="px-3 py-2 border border-gray-200 rounded-lg" placeholder="tpRetISSQN">
            </div>
        </section>

        <section class="space-y-4">
            <h3 class="font-semibold text-gray-900">Padroes por tipo de produto</h3>
            <?php foreach (['course' => 'Curso online', 'saas' => 'SaaS / software', 'ebook' => 'Ebook', 'other' => 'Outro'] as $kind => $label): ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 p-4 border rounded-lg">
                <div class="font-medium text-gray-700"><?= $label ?></div>
                <input name="fiscal_service_code_<?= $kind ?>" value="<?= e($s['fiscal_service_code_' . $kind]) ?>" class="px-3 py-2 border border-gray-200 rounded-lg" placeholder="Codigo NFS-e nacional">
                <input name="fiscal_description_<?= $kind ?>" value="<?= e($s['fiscal_description_' . $kind]) ?>" class="px-3 py-2 border border-gray-200 rounded-lg" placeholder="Descricao fiscal padrao">
            </div>
            <?php endforeach; ?>
        </section>

        <button class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600">
            <i data-lucide="save" class="w-4 h-4"></i>
            Salvar configuracoes
        </button>
    </form>

    <form method="POST" action="<?= url('/fiscal/certificate') ?>" enctype="multipart/form-data" class="bg-white border rounded-xl p-6 space-y-4">
        <?= csrf_field() ?>
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="font-semibold text-gray-900">Certificado digital A1</h3>
                <?php if ($certificate): ?>
                    <p class="text-sm text-gray-500 mt-1"><?= e($certificate['subject']) ?>, valido ate <?= e($certificate['valid_to']) ?></p>
                <?php else: ?>
                    <p class="text-sm text-gray-500 mt-1">Nenhum certificado configurado.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="file" name="certificate" accept=".pfx,.p12" class="px-3 py-2 border border-gray-200 rounded-lg">
            <input type="password" name="certificate_password" class="px-3 py-2 border border-gray-200 rounded-lg" placeholder="Senha do certificado">
        </div>
        <button class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white rounded-lg font-medium hover:bg-gray-800">
            <i data-lucide="key-round" class="w-4 h-4"></i>
            Salvar certificado
        </button>
    </form>
</div>
<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
