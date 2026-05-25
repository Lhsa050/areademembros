<?php
$title = 'Editar: ' . $product['title'];
ob_start();
$taxGroups = $taxGroups ?? [];
$isFunnelProductEditor = isset($funnel) && is_array($funnel);
$backUrl = $isFunnelProductEditor ? url('/funnels/' . $funnel['id'] . '/products') : url('/products');
$formAction = $isFunnelProductEditor ? url('/funnels/' . $funnel['id'] . '/products/' . $product['id']) : url('/products/' . $product['id']);
$deleteAction = $isFunnelProductEditor ? url('/funnels/' . $funnel['id'] . '/products/' . $product['id'] . '/delete') : url('/products/' . $product['id'] . '/delete');
$deleteConfirm = $isFunnelProductEditor
    ? 'Remover este produto deste funil? O cadastro global continuará disponível.'
    : 'Tem certeza que deseja remover este produto? Ele será desvinculado de todos os funis.';
$moduleStoreUrl = $isFunnelProductEditor ? '/funnels/' . $funnel['id'] . '/products/' . $product['id'] . '/modules' : '/products/' . $product['id'] . '/modules';
$productFileStoreUrl = $isFunnelProductEditor ? '/funnels/' . $funnel['id'] . '/products/' . $product['id'] . '/files' : '/products/' . $product['id'] . '/files';
$accessMode = !empty($product['is_public']) ? 'public' : 'webhook';

// Carrega os arquivos do produto
$productFiles = \App\Models\ProductFile::getByProduct($product['id']);

// Carrega arquivos de todas as aulas (batch)
$allLessonIds = [];
if (!empty($product['modules'])) {
    foreach ($product['modules'] as $m) {
        foreach ($m['lessons'] as $l) {
            $allLessonIds[] = $l['id'];
        }
    }
}
$lessonFilesMap = \App\Models\LessonFile::getByLessonIds($allLessonIds);
?>
<div class="max-w-4xl">
    <a href="<?= $backUrl ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 mb-6">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Voltar
    </a>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
                <h3 class="font-semibold text-gray-800 mb-4">Informações</h3>
                <form method="POST" action="<?= $formAction ?>" enctype="multipart/form-data" class="space-y-4">
                    <?= csrf_field() ?>
                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Título</label><input type="text" name="title" value="<?= e($product['title']) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg" required></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label><textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-200 rounded-lg" required><?= e($product['description']) ?></textarea></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Valor fiscal padrao</label><input type="text" name="price" value="<?= isset($product['price']) ? e(number_format((float) $product['price'], 2, ',', '.')) : '' ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="Ex: 97,00"><p class="text-xs text-gray-500 mt-1">Usado se o webhook nao enviar o valor da compra.</p></div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nova Imagem</label>
                        <input type="file" name="image" accept="image/*" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                        <p class="text-xs text-gray-500 mt-1">Recomendado: proporção 4:3.</p>
                        <?php if ($product['image']): ?>
                        <div class="mt-2 text-sm text-gray-500 flex items-center gap-2">
                            <i data-lucide="image" class="w-4 h-4"></i>
                            <span>Atual: <strong><?= e(basename($product['image'])) ?></strong></span>
                            <a href="<?= url($product['image']) ?>" target="_blank" class="text-blue-500 hover:underline text-xs">(Ver)</a>
                        </div>
                        <?php else: ?>
                        <p class="text-xs text-gray-500 mt-1">Nenhuma imagem definida.</p>
                        <?php endif; ?>
                    </div>
                    <?php if ($product['type'] === 'pdf'): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Novo arquivo PDF principal</label>
                        <input type="file" name="file" accept=".pdf" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                        <?php if (!empty($product['file'])): ?>
                        <div class="mt-2 text-sm text-gray-500 flex items-center gap-2">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            <span>Atual: <strong><?= e(basename($product['file'])) ?></strong></span>
                            <a href="<?= url($product['file']) ?>" target="_blank" class="text-blue-500 hover:underline text-xs">(Baixar)</a>
                        </div>
                        <?php else: ?>
                        <p class="text-xs text-gray-500 mt-1">Opcional: use este campo para manter compatibilidade com o arquivo PDF principal antigo.</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="bg-teal-50 border border-teal-200 rounded-lg p-4">
                        <label class="block text-sm font-medium text-teal-700 mb-2">
                            <i data-lucide="link-2" class="w-4 h-4 inline mr-1"></i> ID Externo (CartPanda)
                        </label>
                        <input type="text" name="external_product_id" value="<?= e($product['external_product_id'] ?? '') ?>" class="w-full px-4 py-2 border border-teal-300 rounded-lg bg-white" placeholder="Ex: 12345 ou SKU do produto">
                        <p class="text-xs text-teal-600 mt-1">Informe o <strong>product_id</strong> ou <strong>SKU</strong> deste produto na CartPanda. Usado pelo webhook unificado do funil para identificar automaticamente qual produto foi comprado.</p>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <label class="block text-sm font-medium text-amber-700 mb-2">
                            <i data-lucide="shopping-cart" class="w-4 h-4 inline mr-1"></i> Link de Checkout
                        </label>
                        <input type="url" name="checkout_url" value="<?= e($product['checkout_url'] ?? '') ?>" class="w-full px-4 py-2 border border-amber-300 rounded-lg bg-white" placeholder="https://pay.cartpanda.com/...">
                        <p class="text-xs text-amber-600 mt-1">Ao clicar em produtos bloqueados, o usuário será redirecionado para este link de compra.</p>
                    </div>
                    <?php if ($isFunnelProductEditor): ?>
                    <?php $currentRole = $product['funnel_role'] ?? ''; ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <label class="block text-sm font-medium text-blue-700 mb-3">
                            <i data-lucide="tag" class="w-4 h-4 inline mr-1"></i> Tipo do produto no funil
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label class="relative">
                                <input type="radio" name="funnel_role" value="principal" class="peer sr-only" <?= $currentRole === 'principal' ? 'checked' : '' ?>>
                                <div class="p-3 border-2 border-blue-200 rounded-lg bg-white peer-checked:border-blue-500 peer-checked:bg-blue-100 transition cursor-pointer text-center">
                                    <i data-lucide="star" class="w-5 h-5 mx-auto mb-1 text-blue-500"></i>
                                    <p class="font-medium text-gray-800 text-sm">Principal</p>
                                    <p class="text-[10px] text-gray-500 mt-0.5">Produto principal do funil</p>
                                </div>
                            </label>
                            <label class="relative">
                                <input type="radio" name="funnel_role" value="bonus" class="peer sr-only" <?= $currentRole === 'bonus' ? 'checked' : '' ?>>
                                <div class="p-3 border-2 border-blue-200 rounded-lg bg-white peer-checked:border-amber-500 peer-checked:bg-amber-50 transition cursor-pointer text-center">
                                    <i data-lucide="gift" class="w-5 h-5 mx-auto mb-1 text-amber-500"></i>
                                    <p class="font-medium text-gray-800 text-sm">Bônus</p>
                                    <p class="text-[10px] text-gray-500 mt-0.5">Liberado junto com o principal</p>
                                </div>
                            </label>
                            <label class="relative">
                                <input type="radio" name="funnel_role" value="orderbump" class="peer sr-only" <?= $currentRole === 'orderbump' ? 'checked' : '' ?>>
                                <div class="p-3 border-2 border-blue-200 rounded-lg bg-white peer-checked:border-purple-500 peer-checked:bg-purple-50 transition cursor-pointer text-center">
                                    <i data-lucide="plus-circle" class="w-5 h-5 mx-auto mb-1 text-purple-500"></i>
                                    <p class="font-medium text-gray-800 text-sm">Order Bump</p>
                                    <p class="text-[10px] text-gray-500 mt-0.5">Só libera se comprar</p>
                                </div>
                            </label>
                        </div>
                        <p class="text-xs text-blue-600 mt-2"><strong>Principal + Bônus</strong> são liberados para todos que comprarem o funil. <strong>Order Bump</strong> só é liberado se a pessoa comprar.</p>
                    </div>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                        <label class="block text-sm font-medium text-emerald-700 mb-3">
                            <i data-lucide="unlock" class="w-4 h-4 inline mr-1"></i> Liberacao do acesso
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="relative">
                                <input type="radio" name="access_mode" value="webhook" class="peer sr-only" <?= $accessMode === 'webhook' ? 'checked' : '' ?>>
                                <div class="p-4 border-2 border-emerald-200 rounded-lg bg-white peer-checked:border-emerald-500 peer-checked:bg-emerald-100 transition">
                                    <p class="font-medium text-gray-800">Via webhook</p>
                                    <p class="text-xs text-gray-500 mt-1">Mantem a liberacao por compra, admin ou oferta.</p>
                                </div>
                            </label>
                            <label class="relative">
                                <input type="radio" name="access_mode" value="public" class="peer sr-only" <?= $accessMode === 'public' ? 'checked' : '' ?>>
                                <div class="p-4 border-2 border-emerald-200 rounded-lg bg-white peer-checked:border-emerald-500 peer-checked:bg-emerald-100 transition">
                                    <p class="font-medium text-gray-800">Liberado para todos</p>
                                    <p class="text-xs text-gray-500 mt-1">Qualquer membro logado neste funil acessa.</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                        <label class="block text-sm font-medium text-purple-700 mb-2">
                            <i data-lucide="clock" class="w-4 h-4 inline mr-1"></i> Liberação Agendada (dias após a compra)
                        </label>
                        <input type="number" name="release_days" value="<?= e($product['release_days'] ?? '') ?>" class="w-full px-4 py-2 border border-purple-300 rounded-lg bg-white" placeholder="Ex: 7 (liberar após 7 dias)" min="0">
                        <p class="text-xs text-purple-600 mt-1">Deixe vazio para liberar imediatamente. O produto será liberado X dias após o membro ganhar acesso.</p>
                    </div>
                    <?php endif; ?>
                    <details class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                        <summary class="flex items-center justify-between gap-3 cursor-pointer text-sm font-medium text-slate-700">
                            <span><i data-lucide="receipt-text" class="w-4 h-4 inline mr-1"></i> Configurações fiscais</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400"></i>
                        </summary>
                        <div class="pt-4 space-y-4">
                            <div class="flex justify-end">
                                <a href="<?= url('/fiscal/taxation') ?>" class="text-xs font-medium text-blue-600 hover:underline">Tributacao</a>
                            </div>
                        <?php
                            $kind = $product['fiscal_kind'] ?? ($product['type'] === 'pdf' ? 'ebook' : 'course');
                            $documentModel = $product['fiscal_document_model'] ?? 'nfse';
                            $issuePolicy = $product['fiscal_issue_policy'] ?? 'on_payment';
                            $selectedTaxGroup = (string) ($product['fiscal_tax_group_id'] ?? '');
                        ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Tipo fiscal</label>
                                <select name="fiscal_kind" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white">
                                    <option value="course" <?= $kind === 'course' ? 'selected' : '' ?>>Curso online</option>
                                    <option value="ebook" <?= $kind === 'ebook' ? 'selected' : '' ?>>Ebook / livro digital</option>
                                    <option value="saas" <?= $kind === 'saas' ? 'selected' : '' ?>>SaaS / software</option>
                                    <option value="other" <?= $kind === 'other' ? 'selected' : '' ?>>Outro servico digital</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Perfil tributario</label>
                                <select name="fiscal_tax_group_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white">
                                    <option value="">Padrao das configuracoes</option>
                                    <?php foreach ($taxGroups as $group): ?>
                                        <option value="<?= $group['id'] ?>" <?= $selectedTaxGroup === (string) $group['id'] ? 'selected' : '' ?>><?= e($group['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Modelo</label>
                                <select name="fiscal_document_model" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white">
                                    <option value="nfse" <?= $documentModel === 'nfse' ? 'selected' : '' ?>>NFS-e</option>
                                    <option value="nfe" <?= $documentModel === 'nfe' ? 'selected' : '' ?>>NF-e (futuro)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Emissao</label>
                                <select name="fiscal_issue_policy" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white">
                                    <option value="on_payment" <?= $issuePolicy === 'on_payment' ? 'selected' : '' ?>>Ao pagamento</option>
                                    <option value="manual" <?= $issuePolicy === 'manual' ? 'selected' : '' ?>>Manual</option>
                                    <option value="after_warranty" <?= $issuePolicy === 'after_warranty' ? 'selected' : '' ?>>Apos garantia</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Garantia em dias</label>
                                <input type="number" name="fiscal_warranty_days" value="<?= e($product['fiscal_warranty_days'] ?? '') ?>" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Ex: 7">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Codigo servico NFS-e</label>
                                <input type="text" name="fiscal_service_code" value="<?= e($product['fiscal_service_code'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Ex: 080201">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Item LC 116</label>
                                <input type="text" name="fiscal_lc116_code" value="<?= e($product['fiscal_lc116_code'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Ex: 1.05">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Codigo municipal</label>
                                <input type="text" name="fiscal_municipal_service_code" value="<?= e($product['fiscal_municipal_service_code'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Conforme SJC/contador">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">CNAE</label>
                                <input type="text" name="fiscal_cnae_code" value="<?= e($product['fiscal_cnae_code'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Opcional">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">NBS</label>
                                <input type="text" name="fiscal_nbs_code" value="<?= e($product['fiscal_nbs_code'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Opcional">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">ISS %</label>
                                <input type="text" name="fiscal_iss_rate" value="<?= isset($product['fiscal_iss_rate']) && $product['fiscal_iss_rate'] !== null ? e(number_format((float) $product['fiscal_iss_rate'], 2, ',', '.')) : '' ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Ex: 2,00">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Descricao fiscal especifica</label>
                            <input type="text" name="fiscal_service_description" value="<?= e($product['fiscal_service_description'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Se vazio, usa a descricao das configuracoes fiscais">
                        </div>
                        </div>
                    </details>
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-600 transition">Salvar</button>
                </form>
            </div>

            <?php if ($product['type'] === 'pdf'): ?>
            <!-- SEÇÃO DE ARQUIVOS DO PRODUTO -->
            <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-800">
                        <i data-lucide="files" class="w-5 h-5 inline text-blue-500 mr-1"></i>
                        Arquivos para Download
                    </h3>
                    <button onclick="openAddFileModal()" class="bg-blue-500 text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-blue-600 transition">
                        <i data-lucide="plus" class="w-4 h-4 inline"></i> Arquivo
                    </button>
                </div>
                <div id="files-container" class="space-y-3">
                    <?php if (empty($productFiles)): ?>
                    <p class="text-gray-500 text-sm text-center py-6" id="no-files-msg">Nenhum arquivo. Clique em "Arquivo" para adicionar.</p>
                    <?php endif; ?>
                    <?php foreach ($productFiles as $file): ?>
                    <?php $isLink = ($file['file_type'] ?? 'upload') === 'link'; ?>
                    <div class="file-item bg-gray-50 border rounded-lg p-4 flex items-center justify-between gap-3" data-id="<?= $file['id'] ?>">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <div class="w-10 h-10 <?= $isLink ? 'bg-indigo-100' : 'bg-blue-100' ?> rounded-lg flex items-center justify-center flex-shrink-0">
                                <i data-lucide="<?= $isLink ? 'external-link' : 'file-down' ?>" class="w-5 h-5 <?= $isLink ? 'text-indigo-500' : 'text-blue-500' ?>"></i>
                            </div>
                            <div class="min-w-0">
                                <input type="text" value="<?= e($file['title']) ?>" class="file-title text-sm font-medium text-gray-800 bg-transparent border-0 p-0 w-full focus:outline-none focus:ring-0" placeholder="Nome do arquivo">
                                <p class="text-xs text-gray-400 truncate"><?= $isLink ? $file['file'] : basename($file['file']) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <div class="flex items-center gap-1">
                                <i data-lucide="clock" class="w-3 h-3 text-purple-400"></i>
                                <input type="number" value="<?= e($file['release_days'] ?? '') ?>" class="file-release-days w-16 px-2 py-1 border border-gray-200 rounded text-xs text-center" placeholder="Dias" min="0" title="Dias para liberação">
                            </div>
                            <?php if ($isLink): ?>
                            <label class="flex items-center gap-1 text-xs text-gray-500 whitespace-nowrap" title="Abrir link em nova aba">
                                <input type="checkbox" class="file-open-new-tab rounded border-gray-300" value="1" <?= !empty($file['open_in_new_tab']) ? 'checked' : '' ?>>
                                Nova aba
                            </label>
                            <?php endif; ?>
                            <a href="<?= $isLink ? e($file['file']) : url($file['file']) ?>" target="_blank" class="text-blue-500 p-1.5 rounded-lg hover:bg-blue-50" title="<?= $isLink ? 'Abrir link' : 'Baixar' ?>">
                                <i data-lucide="<?= $isLink ? 'external-link' : 'download' ?>" class="w-4 h-4"></i>
                            </a>
                            <button onclick="deleteProductFile(<?= $file['id'] ?>)" class="text-red-500 p-1.5 rounded-lg hover:bg-red-50" title="Remover">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($product['type'] === 'video'): ?>
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-800">Módulos e Aulas</h3>
                    <button onclick="openAddModuleModal()" class="bg-blue-500 text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-blue-600 transition"><i data-lucide="plus" class="w-4 h-4 inline"></i> Módulo</button>
                </div>
                <div id="modules-container" class="space-y-4">
                    <?php foreach ($product['modules'] as $module): ?>
                    <div class="module-item border rounded-lg p-4" data-id="<?= $module['id'] ?>">
                        <div class="flex items-center gap-3 mb-3">
                            <input type="text" value="<?= e($module['title']) ?>" class="module-title flex-1 px-3 py-1.5 border rounded-lg text-sm font-medium" placeholder="Nome do módulo">
                            <div class="flex items-center gap-1">
                                <i data-lucide="clock" class="w-3 h-3 text-purple-400"></i>
                                <input type="number" value="<?= e($module['release_days'] ?? '') ?>" class="module-release-days w-16 px-2 py-1 border rounded text-xs text-center" placeholder="Dias" min="0" title="Dias para liberação">
                            </div>
                            <button onclick="openAddLessonModal(<?= $module['id'] ?>)" class="text-blue-500 p-1.5 rounded-lg hover:bg-blue-50" title="Adicionar aula"><i data-lucide="plus-circle" class="w-4 h-4"></i></button>
                            <button onclick="openDeleteModal('module', <?= $module['id'] ?>, '<?= e(addslashes($module['title'])) ?>')" class="text-red-500 p-1.5 rounded-lg hover:bg-red-50"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                        </div>
                        <div class="lessons-container pl-7 space-y-2">
                            <?php foreach ($module['lessons'] as $lesson): ?>
                            <div class="lesson-item bg-gray-50 p-3 rounded-lg mb-2" data-id="<?= $lesson['id'] ?>">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="<?= empty($lesson['youtube_id']) ? 'file-text' : 'play-circle' ?>" class="w-4 h-4 text-gray-400"></i>
                                    <input type="text" value="<?= e($lesson['title']) ?>" class="lesson-title flex-1 px-2 py-1 border rounded text-sm" placeholder="Título">
                                    <input type="text" value="<?= e($lesson['youtube_id'] ?? '') ?>" class="lesson-youtube w-32 px-2 py-1 border rounded text-sm" placeholder="YouTube (opcional)">
                                    <button type="button" onclick="toggleLessonDetails(this)" class="text-blue-500 p-1 rounded hover:bg-blue-100"><i data-lucide="chevron-down" class="w-4 h-4"></i></button>
                                    <button onclick="openDeleteModal('lesson', <?= $lesson['id'] ?>, '<?= e(addslashes($lesson['title'])) ?>')" class="text-red-500 p-1 rounded hover:bg-red-100"><i data-lucide="x" class="w-3 h-3"></i></button>
                                </div>
                                <div class="lesson-details hidden mt-3 pl-6 space-y-2 border-t pt-3">
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">Descrição</label>
                                        <textarea class="lesson-description w-full px-2 py-1 border rounded text-sm" rows="2" placeholder="Descrição da aula (aparece abaixo do vídeo)"><?= e($lesson['description'] ?? '') ?></textarea>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">Liberação (dias após a compra)</label>
                                        <input type="number" value="<?= e($lesson['release_days'] ?? '') ?>" class="lesson-release-days w-24 px-2 py-1 border rounded text-sm" placeholder="Dias" min="0">
                                    </div>
                                    <!-- Múltiplos Arquivos da Aula -->
                                    <div class="lesson-files-section">
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="text-xs text-gray-500">Arquivos para Download</label>
                                            <button type="button" onclick="openAddLessonFileModal(<?= $lesson['id'] ?>)" class="text-blue-500 text-xs font-medium hover:text-blue-600 flex items-center gap-1">
                                                <i data-lucide="plus" class="w-3 h-3"></i> Arquivo
                                            </button>
                                        </div>
                                        <div class="lesson-files-list space-y-1" data-lesson-id="<?= $lesson['id'] ?>">
                                            <?php $lessonFiles = $lessonFilesMap[$lesson['id']] ?? []; ?>
                                            <?php if (empty($lessonFiles)): ?>
                                            <p class="text-xs text-gray-400 py-2 no-lesson-files-msg">Nenhum arquivo. Clique em "+ Arquivo".</p>
                                            <?php endif; ?>
                                            <?php foreach ($lessonFiles as $lf): ?>
                                            <?php $lfIsLink = ($lf['file_type'] ?? 'upload') === 'link'; ?>
                                            <div class="lesson-file-item flex items-center gap-2 bg-gray-100 rounded p-2" data-id="<?= $lf['id'] ?>">
                                                <i data-lucide="<?= $lfIsLink ? 'external-link' : 'file-down' ?>" class="w-3 h-3 <?= $lfIsLink ? 'text-indigo-500' : 'text-blue-500' ?> flex-shrink-0"></i>
                                                <input type="text" value="<?= e($lf['title']) ?>" class="lf-title flex-1 px-1 py-0.5 border rounded text-xs bg-white" placeholder="Nome">
                                                <input type="number" value="<?= e($lf['release_days'] ?? '') ?>" class="lf-release-days w-14 px-1 py-0.5 border rounded text-xs text-center" placeholder="Dias" min="0" title="Dias para liberação">
                                                <a href="<?= $lfIsLink ? e($lf['file']) : url($lf['file']) ?>" target="_blank" class="text-blue-500 hover:text-blue-600" title="<?= $lfIsLink ? 'Abrir link' : 'Baixar' ?>"><i data-lucide="<?= $lfIsLink ? 'external-link' : 'download' ?>" class="w-3 h-3"></i></a>
                                                <button onclick="deleteLessonFile(<?= $lf['id'] ?>)" class="text-red-400 hover:text-red-600" title="Remover"><i data-lucide="x" class="w-3 h-3"></i></button>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if (!empty($lesson['file'])): ?>
                                        <p class="text-xs text-amber-600 mt-1">⚠ Arquivo antigo (campo único): <?= basename($lesson['file']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($product['modules'])): ?><p class="text-gray-500 text-sm text-center py-6">Nenhum módulo. Clique em "Módulo" para criar.</p><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <div>
            <?php if ($isFunnelProductEditor): ?>
            <?php if (!empty($product['is_public'])): ?>
            <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100 mb-4">
                <h4 class="text-sm font-medium text-emerald-700 mb-2 flex items-center gap-2">
                    <i data-lucide="unlock" class="w-4 h-4"></i>
                    Liberado para todos
                </h4>
                <p class="text-xs text-emerald-600">Qualquer membro logado neste funil consegue acessar este produto.</p>
            </div>
            <?php elseif (!empty($product['webhook_token'])): ?>
            <div class="bg-white rounded-xl shadow-sm border p-4 mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                    <i data-lucide="webhook" class="w-4 h-4 text-brand-500"></i>
                    Webhook CartPanda
                </h4>
                <p class="text-xs text-gray-500 mb-2">Cole esta URL no CartPanda para receber notificações:</p>
                <?php $webhookUrl = rtrim(env('APP_URL', ''), '/') . '/webhook/' . $product['webhook_token']; ?>
                <div class="flex items-center gap-1">
                    <input type="text" value="<?= e($webhookUrl) ?>" readonly
                           id="webhook-url"
                           class="flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-mono text-gray-600 truncate">
                    <button type="button" onclick="copyWebhookUrl()" class="bg-brand-500 hover:bg-brand-600 text-white px-3 py-2 rounded-lg text-xs transition" title="Copiar">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
                <div id="copy-feedback" class="hidden text-xs text-green-600 mt-1">✓ Copiado!</div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border p-4 mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Preview</h4>
                <div class="aspect-[4/3] bg-gray-100 rounded-lg overflow-hidden mb-3">
                    <?php if ($product['image']): ?><img src="<?= url($product['image']) ?>" class="w-full h-full object-cover"><?php else: ?><div class="w-full h-full flex items-center justify-center"><i data-lucide="image" class="w-8 h-8 text-gray-300"></i></div><?php endif; ?>
                </div>
            </div>
            <div class="bg-red-50 rounded-xl p-4 border border-red-100">
                <form method="POST" action="<?= $deleteAction ?>" onsubmit="return confirm('<?= e($deleteConfirm) ?>')">
                    <?= csrf_field() ?>
                    <button type="submit" class="w-full bg-red-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-600"><?= $isFunnelProductEditor ? 'Remover do funil' : 'Excluir produto' ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ====== MODAL COMPONENT ====== -->
<div id="app-modal" class="app-modal-overlay" style="display:none;">
    <div class="app-modal-container">
        <div class="app-modal-header">
            <h3 id="modal-title" class="app-modal-title">Modal</h3>
            <button onclick="closeModal()" class="app-modal-close">&times;</button>
        </div>
        <div id="modal-body" class="app-modal-body">
            <!-- Dynamic content -->
        </div>
        <div id="modal-footer" class="app-modal-footer">
            <button onclick="closeModal()" class="app-modal-btn-cancel">Cancelar</button>
            <button id="modal-confirm-btn" onclick="" class="app-modal-btn-confirm">Confirmar</button>
        </div>
    </div>
</div>

<style>
.app-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999;
    display: flex; align-items: center; justify-content: center;
    animation: modalFadeIn 0.2s ease;
    backdrop-filter: blur(4px);
}
@keyframes modalFadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes modalSlideIn { from { transform: translateY(-20px) scale(0.95); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }
.app-modal-container {
    background: white; border-radius: 16px; width: 90%; max-width: 520px;
    box-shadow: 0 25px 60px rgba(0,0,0,0.15);
    animation: modalSlideIn 0.25s ease;
    overflow: hidden;
}
.app-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 24px; border-bottom: 1px solid #e5e7eb;
}
.app-modal-title { font-size: 1rem; font-weight: 600; color: #1f2937; }
.app-modal-close { background: none; border: none; font-size: 1.5rem; color: #9ca3af; cursor: pointer; padding: 0; line-height: 1; transition: color 0.2s; }
.app-modal-close:hover { color: #374151; }
.app-modal-body { padding: 24px; }
.app-modal-body .modal-field { margin-bottom: 16px; }
.app-modal-body .modal-field label { display: block; font-size: 0.8125rem; font-weight: 500; color: #374151; margin-bottom: 6px; }
.app-modal-body .modal-field input,
.app-modal-body .modal-field textarea,
.app-modal-body .modal-field select { width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 0.875rem; transition: border-color 0.2s; outline: none; }
.app-modal-body .modal-field input:focus,
.app-modal-body .modal-field textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.app-modal-body .modal-field .hint { font-size: 0.75rem; color: #9ca3af; margin-top: 4px; }
.app-modal-footer {
    display: flex; justify-content: flex-end; gap: 10px;
    padding: 16px 24px; border-top: 1px solid #e5e7eb; background: #f9fafb;
}
.app-modal-btn-cancel { padding: 10px 20px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 0.875rem; font-weight: 500; background: white; color: #6b7280; cursor: pointer; transition: all 0.2s; }
.app-modal-btn-cancel:hover { background: #f3f4f6; }
.app-modal-btn-confirm { padding: 10px 20px; border: none; border-radius: 10px; font-size: 0.875rem; font-weight: 600; background: #3b82f6; color: white; cursor: pointer; transition: all 0.2s; }
.app-modal-btn-confirm:hover { background: #2563eb; }
.app-modal-btn-danger { background: #ef4444 !important; }
.app-modal-btn-danger:hover { background: #dc2626 !important; }
</style>

<script>
const productId = <?= $product['id'] ?>;
const moduleStoreUrl = '<?= e($moduleStoreUrl) ?>';
const productFileStoreUrl = '<?= e($productFileStoreUrl) ?>';
const csrfToken = '<?= csrf_token() ?>';

async function request(url, data) {
    const fd = new FormData(); fd.append('_csrf_token', csrfToken);
    Object.keys(data).forEach(k => fd.append(k, data[k]));
    const response = await fetch(url, { method: 'POST', body: fd });
    const text = await response.text();
    try { return JSON.parse(text); } catch(e) { console.error('Response:', text); alert('Erro no servidor. Veja o console.'); throw e; }
}

async function requestFormData(url, fd) {
    fd.append('_csrf_token', csrfToken);
    const response = await fetch(url, { method: 'POST', body: fd });
    const text = await response.text();
    try { return JSON.parse(text); } catch(e) { console.error('Response:', text); alert('Erro no servidor.'); throw e; }
}

// === MODAL FUNCTIONS ===
function openModal(title, bodyHtml, confirmText, onConfirm, isDanger = false) {
    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-body').innerHTML = bodyHtml;
    const btn = document.getElementById('modal-confirm-btn');
    btn.textContent = confirmText;
    btn.className = 'app-modal-btn-confirm' + (isDanger ? ' app-modal-btn-danger' : '');
    btn.onclick = onConfirm;
    document.getElementById('app-modal').style.display = 'flex';
    // Focus first input
    setTimeout(() => {
        const firstInput = document.querySelector('#modal-body input:not([type=file]), #modal-body textarea');
        if (firstInput) firstInput.focus();
    }, 100);
}

function closeModal() {
    document.getElementById('app-modal').style.display = 'none';
}

// Close modal on ESC or backdrop click
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
document.getElementById('app-modal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });

function toggleLessonDetails(btn) {
    const details = btn.closest('.lesson-item').querySelector('.lesson-details');
    details.classList.toggle('hidden');
    // Update icon
    const icon = btn.querySelector('svg, i');
    if (icon) {
        const isHidden = details.classList.contains('hidden');
        icon.setAttribute('data-lucide', isHidden ? 'chevron-down' : 'chevron-up');
        lucide.createIcons({nodes: [btn]});
    }
}

// === MODULE MODALS ===
function openAddModuleModal() {
    openModal('Novo Módulo', `
        <div class="modal-field">
            <label>Nome do Módulo</label>
            <input type="text" id="modal-module-title" placeholder="Ex: Módulo 1 - Introdução">
        </div>
        <div class="modal-field">
            <label>Liberação (dias após a compra)</label>
            <input type="number" id="modal-module-release" placeholder="Vazio = imediato" min="0">
            <div class="hint">Deixe vazio para liberar imediatamente</div>
        </div>
    `, 'Criar Módulo', confirmAddModule);
}

async function confirmAddModule() {
    const title = document.getElementById('modal-module-title').value.trim();
    if (!title) { document.getElementById('modal-module-title').style.borderColor = '#ef4444'; return; }
    const release = document.getElementById('modal-module-release').value;
    const data = { title };
    if (release) data.release_days = release;
    await request(moduleStoreUrl, data);
    closeModal();
    location.reload();
}

// === LESSON MODALS ===
let currentModuleId = null;

function openAddLessonModal(moduleId) {
    currentModuleId = moduleId;
    openModal('Nova Aula', `
        <div class="modal-field">
            <label>Título da Aula *</label>
            <input type="text" id="modal-lesson-title" placeholder="Ex: Aula 1 - Boas vindas">
        </div>
        <div class="modal-field">
            <label>YouTube URL ou ID <span style="color:#9ca3af;font-weight:400">(opcional)</span></label>
            <input type="text" id="modal-lesson-youtube" placeholder="Ex: https://youtube.com/watch?v=xxxxx">
            <div class="hint">Deixe vazio para criar uma aula só com descrição/arquivo</div>
        </div>
        <div class="modal-field">
            <label>Descrição <span style="color:#9ca3af;font-weight:400">(opcional)</span></label>
            <textarea id="modal-lesson-desc" rows="2" placeholder="Descrição da aula"></textarea>
        </div>
        <div class="modal-field">
            <label>Arquivo para Download <span style="color:#9ca3af;font-weight:400">(opcional)</span></label>
            <input type="file" id="modal-lesson-file" accept=".pdf,.zip,.rar,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.mp3,.mp4">
            <div class="hint">Você pode adicionar mais arquivos depois de criar a aula</div>
        </div>
        <div class="modal-field">
            <label>Liberação (dias após a compra)</label>
            <input type="number" id="modal-lesson-release" placeholder="Vazio = imediato" min="0">
        </div>
    `, 'Criar Aula', confirmAddLesson);
}

async function confirmAddLesson() {
    const title = document.getElementById('modal-lesson-title').value.trim();
    if (!title) { document.getElementById('modal-lesson-title').style.borderColor = '#ef4444'; return; }
    
    const btn = document.getElementById('modal-confirm-btn');
    btn.textContent = 'Criando...';
    btn.disabled = true;
    
    const data = { title };
    const youtube = document.getElementById('modal-lesson-youtube').value.trim();
    const desc = document.getElementById('modal-lesson-desc').value.trim();
    const release = document.getElementById('modal-lesson-release').value;
    if (youtube) data.youtube_id = youtube;
    if (desc) data.description = desc;
    if (release) data.release_days = release;
    
    const result = await request(`/modules/${currentModuleId}/lessons`, data);
    
    // Se tem arquivo, faz upload para a aula recém-criada
    const fileInput = document.getElementById('modal-lesson-file');
    if (fileInput && fileInput.files[0] && result.id) {
        const fd = new FormData();
        fd.append('title', fileInput.files[0].name.replace(/\.[^/.]+$/, ''));
        fd.append('file', fileInput.files[0]);
        await requestFormData(`/lessons/${result.id}/files`, fd);
    }
    
    closeModal();
    location.reload();
}

// === DELETE MODALS ===
function openDeleteModal(type, id, name) {
    const label = type === 'module' ? 'módulo' : 'aula';
    openModal(
        `Remover ${label}`,
        `<p style="font-size:0.9375rem;color:#4b5563;">Tem certeza que deseja remover <strong>"${name}"</strong>?</p>
         <p style="font-size:0.8125rem;color:#9ca3af;margin-top:8px;">${type === 'module' ? 'Todas as aulas deste módulo também serão removidas.' : 'Esta ação não pode ser desfeita.'}</p>`,
        'Remover',
        () => confirmDelete(type, id),
        true
    );
}

async function confirmDelete(type, id) {
    if (type === 'module') {
        await request(`/modules/${id}/delete`, {});
    } else {
        await request(`/lessons/${id}/delete`, {});
    }
    closeModal();
    location.reload();
}

// === PRODUCT FILE MODALS ===
function openAddFileModal() {
    openModal('Adicionar Arquivo / Link', `
        <div class="modal-field">
            <label>Nome do Arquivo *</label>
            <input type="text" id="modal-file-title" placeholder="Ex: E-book completo, Planilha de exercícios...">
        </div>
        <div class="modal-field">
            <label style="margin-bottom:8px;">Tipo</label>
            <div style="display:flex;gap:8px;margin-bottom:12px;">
                <button type="button" onclick="toggleFileMode('upload','file')" id="file-mode-upload" class="app-modal-btn-cancel" style="flex:1;font-weight:600;background:#3b82f6;color:#fff;border-color:#3b82f6;">📁 Upload</button>
                <button type="button" onclick="toggleFileMode('link','file')" id="file-mode-link" class="app-modal-btn-cancel" style="flex:1;">🔗 Link Externo</button>
            </div>
        </div>
        <div class="modal-field" id="file-upload-section">
            <label>Arquivo *</label>
            <input type="file" id="modal-file-input" accept=".pdf,.zip,.rar,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.mp3,.mp4">
            <div class="hint">Formatos aceitos: PDF, ZIP, RAR, DOC, XLS, PPT, TXT, CSV, MP3, MP4</div>
        </div>
        <div class="modal-field" id="file-link-section" style="display:none;">
            <label>URL do Link *</label>
            <input type="url" id="modal-file-url" placeholder="https://drive.google.com/...">
            <div class="hint">Cole o link externo (Google Drive, Dropbox, etc.)</div>
            <label style="display:flex;align-items:center;gap:8px;margin-top:12px;font-size:0.875rem;color:#4b5563;">
                <input type="checkbox" id="modal-file-open-new-tab" value="1" style="width:16px;height:16px;">
                Abrir em nova aba
            </label>
            <div class="hint">Desmarcado abre na mesma aba, ideal para produtos de upsell.</div>
        </div>
        <div class="modal-field">
            <label>Liberação (dias após a compra)</label>
            <input type="number" id="modal-file-release" placeholder="Vazio = imediato" min="0">
            <div class="hint">Deixe vazio para liberar imediatamente</div>
        </div>
    `, 'Enviar', confirmAddProductFile);
}

var currentFileMode = 'upload'; // 'upload' ou 'link'

function toggleFileMode(mode, prefix) {
    currentFileMode = mode;
    const uploadBtn = document.getElementById(prefix + '-mode-upload');
    const linkBtn = document.getElementById(prefix + '-mode-link');
    const uploadSection = document.getElementById(prefix + '-upload-section');
    const linkSection = document.getElementById(prefix + '-link-section');
    
    if (mode === 'upload') {
        uploadBtn.style.background = '#3b82f6'; uploadBtn.style.color = '#fff'; uploadBtn.style.borderColor = '#3b82f6';
        linkBtn.style.background = ''; linkBtn.style.color = ''; linkBtn.style.borderColor = '';
        uploadSection.style.display = ''; linkSection.style.display = 'none';
    } else {
        linkBtn.style.background = '#6366f1'; linkBtn.style.color = '#fff'; linkBtn.style.borderColor = '#6366f1';
        uploadBtn.style.background = ''; uploadBtn.style.color = ''; uploadBtn.style.borderColor = '';
        uploadSection.style.display = 'none'; linkSection.style.display = '';
    }
}

async function confirmAddProductFile() {
    const title = document.getElementById('modal-file-title').value.trim();
    if (!title) { document.getElementById('modal-file-title').style.borderColor = '#ef4444'; return; }

    const btn = document.getElementById('modal-confirm-btn');
    btn.textContent = 'Enviando...';
    btn.disabled = true;

    const fd = new FormData();
    fd.append('title', title);
    const release = document.getElementById('modal-file-release').value;
    if (release) fd.append('release_days', release);

    if (currentFileMode === 'link') {
        const fileUrl = document.getElementById('modal-file-url').value.trim();
        if (!fileUrl) { document.getElementById('modal-file-url').style.borderColor = '#ef4444'; btn.textContent = 'Enviar'; btn.disabled = false; return; }
        fd.append('file_url', fileUrl);
        fd.append('open_in_new_tab', document.getElementById('modal-file-open-new-tab')?.checked ? '1' : '0');
    } else {
        const fileInput = document.getElementById('modal-file-input');
        if (!fileInput.files[0]) { fileInput.style.borderColor = '#ef4444'; btn.textContent = 'Enviar'; btn.disabled = false; return; }
        fd.append('file', fileInput.files[0]);
    }

    const result = await requestFormData(productFileStoreUrl, fd);
    
    if (result.success) {
        closeModal();
        location.reload();
    } else {
        btn.textContent = 'Enviar';
        btn.disabled = false;
        alert(result.error || 'Erro ao enviar arquivo');
    }
}

function deleteProductFile(fileId) {
    openModal(
        'Remover Arquivo',
        '<p style="color:#4b5563;">Tem certeza que deseja remover este arquivo?</p><p style="font-size:0.8125rem;color:#9ca3af;margin-top:8px;">Esta ação não pode ser desfeita.</p>',
        'Remover',
        async () => { await request(`/product-files/${fileId}/delete`, {}); closeModal(); location.reload(); },
        true
    );
}

// === AUTO-SAVE ===
async function saveLessonData(lessonItem) {
    const id = lessonItem.dataset.id;
    const data = {
        title: lessonItem.querySelector('.lesson-title').value,
        youtube_id: lessonItem.querySelector('.lesson-youtube').value,
        description: lessonItem.querySelector('.lesson-description')?.value || '',
        release_days: lessonItem.querySelector('.lesson-release-days')?.value || ''
    };
    await request(`/lessons/${id}`, data);
}

async function saveModuleData(moduleItem) {
    const id = moduleItem.dataset.id;
    const data = {
        title: moduleItem.querySelector('.module-title').value,
        release_days: moduleItem.querySelector('.module-release-days')?.value || ''
    };
    await request(`/modules/${id}`, data);
}

async function saveFileData(fileItem) {
    const id = fileItem.dataset.id;
    const data = {
        title: fileItem.querySelector('.file-title').value,
        release_days: fileItem.querySelector('.file-release-days')?.value || ''
    };
    const openInNewTab = fileItem.querySelector('.file-open-new-tab');
    if (openInNewTab) data.open_in_new_tab = openInNewTab.checked ? '1' : '0';
    await request(`/product-files/${id}`, data);
}

async function uploadLessonFile(input, lessonId) {
    if (!input.files[0]) return;
    const fd = new FormData();
    fd.append('_csrf_token', csrfToken);
    fd.append('file', input.files[0]);
    const response = await fetch(`/lessons/${lessonId}/upload-file`, { method: 'POST', body: fd });
    const result = await response.json();
    if (result.success && result.file) {
        input.closest('.lesson-item').querySelector('.lesson-file').value = result.file;
        location.reload();
    } else {
        alert(result.error || 'Erro ao enviar arquivo');
    }
}

function copyWebhookUrl() {
    const input = document.getElementById('webhook-url');
    navigator.clipboard.writeText(input.value).then(() => {
        const feedback = document.getElementById('copy-feedback');
        feedback.classList.remove('hidden');
        setTimeout(() => feedback.classList.add('hidden'), 2000);
    });
}

// Bind blur auto-save
document.querySelectorAll('.module-title, .module-release-days').forEach(i => i.addEventListener('blur', async function() { await saveModuleData(this.closest('.module-item')); }));
document.querySelectorAll('.lesson-title, .lesson-youtube, .lesson-description, .lesson-release-days').forEach(i => i.addEventListener('blur', async function() { await saveLessonData(this.closest('.lesson-item')); }));
document.querySelectorAll('.file-title, .file-release-days').forEach(i => i.addEventListener('blur', async function() { await saveFileData(this.closest('.file-item')); }));
document.querySelectorAll('.file-open-new-tab').forEach(i => i.addEventListener('change', async function() { await saveFileData(this.closest('.file-item')); }));
document.querySelectorAll('.lf-title, .lf-release-days').forEach(i => i.addEventListener('blur', async function() { await saveLessonFileData(this.closest('.lesson-file-item')); }));

// === ARQUIVOS DA AULA ===
let currentLessonIdForFile = null;

function openAddLessonFileModal(lessonId) {
    currentLessonIdForFile = lessonId;
    currentFileMode = 'upload';
    openModal('Adicionar Arquivo / Link à Aula', `
        <div class="modal-field">
            <label>Nome *</label>
            <input type="text" id="modal-lf-title" placeholder="Ex: Exercícios, Material complementar...">
        </div>
        <div class="modal-field">
            <label style="margin-bottom:8px;">Tipo</label>
            <div style="display:flex;gap:8px;margin-bottom:12px;">
                <button type="button" onclick="toggleFileMode('upload','lf')" id="lf-mode-upload" class="app-modal-btn-cancel" style="flex:1;font-weight:600;background:#3b82f6;color:#fff;border-color:#3b82f6;">📁 Upload</button>
                <button type="button" onclick="toggleFileMode('link','lf')" id="lf-mode-link" class="app-modal-btn-cancel" style="flex:1;">🔗 Link Externo</button>
            </div>
        </div>
        <div class="modal-field" id="lf-upload-section">
            <label>Arquivo *</label>
            <input type="file" id="modal-lf-input" accept=".pdf,.zip,.rar,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.mp3,.mp4">
            <div class="hint">PDF, ZIP, RAR, DOC, XLS, PPT, TXT, CSV, MP3, MP4</div>
        </div>
        <div class="modal-field" id="lf-link-section" style="display:none;">
            <label>URL do Link *</label>
            <input type="url" id="modal-lf-url" placeholder="https://drive.google.com/...">
            <div class="hint">Cole o link externo (Google Drive, Dropbox, etc.)</div>
        </div>
        <div class="modal-field">
            <label>Liberação (dias após a compra)</label>
            <input type="number" id="modal-lf-release" placeholder="Vazio = imediato" min="0">
        </div>
    `, 'Enviar', confirmAddLessonFile);
}

async function confirmAddLessonFile() {
    const title = document.getElementById('modal-lf-title').value.trim();
    if (!title) { document.getElementById('modal-lf-title').style.borderColor = '#ef4444'; return; }

    const btn = document.getElementById('modal-confirm-btn');
    btn.textContent = 'Enviando...';
    btn.disabled = true;

    const fd = new FormData();
    fd.append('title', title);
    const release = document.getElementById('modal-lf-release').value;
    if (release) fd.append('release_days', release);

    if (currentFileMode === 'link') {
        const fileUrl = document.getElementById('modal-lf-url').value.trim();
        if (!fileUrl) { document.getElementById('modal-lf-url').style.borderColor = '#ef4444'; btn.textContent = 'Enviar'; btn.disabled = false; return; }
        fd.append('file_url', fileUrl);
    } else {
        const fileInput = document.getElementById('modal-lf-input');
        if (!fileInput.files[0]) { fileInput.style.borderColor = '#ef4444'; btn.textContent = 'Enviar'; btn.disabled = false; return; }
        fd.append('file', fileInput.files[0]);
    }

    const result = await requestFormData(`/lessons/${currentLessonIdForFile}/files`, fd);
    
    if (result.success) {
        closeModal();
        location.reload();
    } else {
        btn.textContent = 'Enviar';
        btn.disabled = false;
        alert(result.error || 'Erro ao enviar arquivo');
    }
}

function deleteLessonFile(fileId) {
    openModal(
        'Remover Arquivo',
        '<p style="color:#4b5563;">Tem certeza que deseja remover este arquivo?</p>',
        'Remover',
        async () => { await request(`/lesson-files/${fileId}/delete`, {}); closeModal(); location.reload(); },
        true
    );
}

async function saveLessonFileData(fileItem) {
    const id = fileItem.dataset.id;
    const data = {
        title: fileItem.querySelector('.lf-title').value,
        release_days: fileItem.querySelector('.lf-release-days')?.value || ''
    };
    await request(`/lesson-files/${id}`, data);
}
</script>
<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
