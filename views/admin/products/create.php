<?php
/**
 * Criar Produto
 */
$title = 'Novo Produto';

ob_start();
?>

<div class="max-w-4xl">
    <a href="<?= url('/products') ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 mb-6">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Voltar
    </a>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="<?= url('/products') ?>" enctype="multipart/form-data" class="space-y-6">
            <?= csrf_field() ?>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Tipo de Produto</label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="relative">
                        <input type="radio" name="type" value="video" class="peer sr-only" checked>
                        <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                            <div class="flex items-center gap-3">
                                <i data-lucide="film" class="w-6 h-6 text-blue-500"></i>
                                <div>
                                    <p class="font-medium text-gray-800">Curso em Vídeo</p>
                                    <p class="text-xs text-gray-500">Com módulos e aulas</p>
                                </div>
                            </div>
                        </div>
                    </label>
                    
                    <label class="relative">
                        <input type="radio" name="type" value="pdf" class="peer sr-only">
                        <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                            <div class="flex items-center gap-3">
                                <i data-lucide="file-text" class="w-6 h-6 text-green-500"></i>
                                <div>
                                    <p class="font-medium text-gray-800">PDF / E-book</p>
                                    <p class="text-xs text-gray-500">Arquivo para download</p>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
            
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Título</label>
                <input type="text" id="title" name="title" value="<?= e(old('title')) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ex: Curso Completo de Marketing" required>
                <?php if (isset($errors['title'])): ?>
                <p class="text-red-500 text-xs mt-1"><?= e($errors['title'][0]) ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                <textarea id="description" name="description" rows="3" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Breve descrição do produto" required><?= e(old('description')) ?></textarea>
                <?php if (isset($errors['description'])): ?>
                <p class="text-red-500 text-xs mt-1"><?= e($errors['description'][0]) ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Valor fiscal padrão</label>
                <input type="text" id="price" name="price" value="<?= e(old('price')) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ex: 97,00">
                <p class="text-xs text-gray-500 mt-1">Usado quando o webhook não enviar o valor da compra.</p>
            </div>
            
            <div>
                <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Imagem de Capa</label>
                <input type="file" id="image" name="image" accept="image/*" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Formatos: JPG, PNG, GIF, WebP. Recomendado: proporção 4:3.</p>
            </div>
            
            <div id="pdf-upload" class="hidden border border-green-100 bg-green-50/40 rounded-lg p-4 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-gray-800">Arquivos para download</h3>
                        <p class="text-xs text-gray-500 mt-1">Adicione um ou vários arquivos, cada um com seu nome.</p>
                    </div>
                    <button type="button" id="add-initial-product-file" class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-white border border-green-200 text-sm font-medium text-green-700 hover:bg-green-50 transition">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Arquivo
                    </button>
                </div>
                <div id="initial-product-files-list" class="space-y-4"></div>
            </div>

            <div id="video-content-builder" class="border border-blue-100 bg-blue-50/40 rounded-lg p-4 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-gray-800">Módulos e aulas</h3>
                        <p class="text-xs text-gray-500 mt-1">Monte o conteúdo inicial antes de salvar.</p>
                    </div>
                    <button type="button" id="add-initial-module" class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-white border border-blue-200 text-sm font-medium text-blue-600 hover:bg-blue-50 transition">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Módulo
                    </button>
                </div>
                <div id="initial-modules-list" class="space-y-4"></div>
            </div>

            <div class="bg-teal-50 border border-teal-200 rounded-lg p-4">
                <label class="block text-sm font-medium text-teal-700 mb-2">
                    <i data-lucide="link-2" class="w-4 h-4 inline mr-1"></i> Codigo do produto na CartPanda
                </label>
                <input type="text" name="external_product_id" value="<?= e(old('external_product_id')) ?>" class="w-full px-4 py-2 border border-teal-300 rounded-lg bg-white" placeholder="Ex: 12345 ou SKU do produto">
                <p class="text-xs text-teal-600 mt-1">Informe o <strong>product_id</strong>, <strong>variant_id</strong> ou <strong>SKU</strong> deste produto na CartPanda. Esse codigo fica fixo no produto e vale em todos os funis.</p>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                <label class="block text-sm font-medium text-amber-700 mb-2">
                    <i data-lucide="shopping-cart" class="w-4 h-4 inline mr-1"></i> Link do checkout
                </label>
                <input type="url" name="checkout_url" value="<?= e(old('checkout_url')) ?>" class="w-full px-4 py-2 border border-amber-300 rounded-lg bg-white" placeholder="https://pay.cartpanda.com/...">
                <p class="text-xs text-amber-600 mt-1">Ao clicar em produtos bloqueados, o usuário será redirecionado para este link de compra.</p>
            </div>

            <details class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                <summary class="flex items-center justify-between gap-3 cursor-pointer text-sm font-medium text-slate-700">
                    <span><i data-lucide="receipt-text" class="w-4 h-4 inline mr-1"></i> Configurações fiscais</span>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400"></i>
                </summary>
                <div class="pt-4 space-y-4">
                    <div class="flex justify-end">
                        <a href="<?= url('/fiscal/taxation') ?>" class="text-xs font-medium text-blue-600 hover:underline">Tributação</a>
                    </div>
                <?php $taxGroups = $taxGroups ?? []; ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Tipo fiscal</label>
                        <select name="fiscal_kind" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white">
                            <option value="course" <?= old('fiscal_kind', 'course') === 'course' ? 'selected' : '' ?>>Curso online</option>
                            <option value="ebook" <?= old('fiscal_kind') === 'ebook' ? 'selected' : '' ?>>Ebook / livro digital</option>
                            <option value="saas" <?= old('fiscal_kind') === 'saas' ? 'selected' : '' ?>>SaaS / software</option>
                            <option value="other" <?= old('fiscal_kind') === 'other' ? 'selected' : '' ?>>Outro serviço digital</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Perfil tributário</label>
                        <select name="fiscal_tax_group_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white">
                            <option value="">Padrão das configurações</option>
                            <?php foreach ($taxGroups as $group): ?>
                                <option value="<?= $group['id'] ?>" <?= (string) old('fiscal_tax_group_id') === (string) $group['id'] ? 'selected' : '' ?>><?= e($group['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Modelo</label>
                        <select name="fiscal_document_model" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white">
                            <option value="nfse" <?= old('fiscal_document_model', 'nfse') === 'nfse' ? 'selected' : '' ?>>NFS-e</option>
                            <option value="nfe" <?= old('fiscal_document_model') === 'nfe' ? 'selected' : '' ?>>NF-e (futuro)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Emissão</label>
                        <select name="fiscal_issue_policy" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white">
                            <option value="on_payment" <?= old('fiscal_issue_policy', 'on_payment') === 'on_payment' ? 'selected' : '' ?>>Ao pagamento</option>
                            <option value="manual" <?= old('fiscal_issue_policy') === 'manual' ? 'selected' : '' ?>>Manual</option>
                            <option value="after_warranty" <?= old('fiscal_issue_policy') === 'after_warranty' ? 'selected' : '' ?>>Após garantia</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Garantia em dias</label>
                        <input type="number" name="fiscal_warranty_days" value="<?= e(old('fiscal_warranty_days')) ?>" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Ex: 7">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Código serviço NFS-e</label>
                        <input type="text" name="fiscal_service_code" value="<?= e(old('fiscal_service_code')) ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Ex: 080201">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Item LC 116</label>
                        <input type="text" name="fiscal_lc116_code" value="<?= e(old('fiscal_lc116_code')) ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Ex: 1.05">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Código municipal</label>
                        <input type="text" name="fiscal_municipal_service_code" value="<?= e(old('fiscal_municipal_service_code')) ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Conforme SJC/contador">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">CNAE</label>
                        <input type="text" name="fiscal_cnae_code" value="<?= e(old('fiscal_cnae_code')) ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Opcional">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">NBS</label>
                        <input type="text" name="fiscal_nbs_code" value="<?= e(old('fiscal_nbs_code')) ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Opcional">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">ISS %</label>
                        <input type="text" name="fiscal_iss_rate" value="<?= e(old('fiscal_iss_rate')) ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Ex: 2,00">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Descrição fiscal específica</label>
                    <input type="text" name="fiscal_service_description" value="<?= e(old('fiscal_service_description')) ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white" placeholder="Se vazio, usa a descrição das configurações fiscais">
                </div>
                </div>
            </details>
            
            <div class="flex gap-4 pt-4">
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-600 transition">
                    Criar Produto
                </button>
                <a href="<?= url('/products') ?>" class="px-6 py-2 rounded-lg font-medium text-gray-600 hover:bg-gray-100 transition">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
let fiscalKindTouched = false;
const fiscalKindSelect = document.querySelector('select[name="fiscal_kind"]');
const pdfUpload = document.getElementById('pdf-upload');
const videoContentBuilder = document.getElementById('video-content-builder');
const initialModulesList = document.getElementById('initial-modules-list');
const addInitialModuleButton = document.getElementById('add-initial-module');
const initialProductFilesList = document.getElementById('initial-product-files-list');
const addInitialProductFileButton = document.getElementById('add-initial-product-file');
let initialModuleIndex = 0;
let initialProductFileIndex = 0;

function refreshIcons() {
    if (window.lucide) {
        window.lucide.createIcons();
    }
}

function lessonTemplate(moduleIndex, lessonIndex) {
    return `
        <div class="initial-lesson rounded-lg border border-gray-200 bg-white p-3 space-y-3" data-lesson-index="${lessonIndex}">
            <div class="flex items-center justify-between gap-3">
                <span class="text-xs font-semibold uppercase text-gray-400">Aula ${lessonIndex + 1}</span>
                <button type="button" class="remove-initial-lesson p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition" title="Remover aula">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_140px] gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Título da aula</label>
                    <input type="text" name="initial_modules[${moduleIndex}][lessons][${lessonIndex}][title]" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="Ex: Aula 1">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Liberação</label>
                    <input type="number" name="initial_modules[${moduleIndex}][lessons][${lessonIndex}][release_days]" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" min="0" placeholder="Imediata">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">YouTube</label>
                <input type="text" name="initial_modules[${moduleIndex}][lessons][${lessonIndex}][youtube_id]" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="Cole o link do vídeo">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Descrição</label>
                <textarea name="initial_modules[${moduleIndex}][lessons][${lessonIndex}][description]" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="Resumo da aula"></textarea>
            </div>
        </div>
    `;
}

function moduleTemplate(moduleIndex) {
    return `
        <div class="initial-module rounded-lg border border-blue-100 bg-white p-4 space-y-4" data-module-index="${moduleIndex}" data-next-lesson="0">
            <div class="flex items-start justify-between gap-3">
                <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_140px] gap-3 flex-1">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Título do módulo</label>
                        <input type="text" name="initial_modules[${moduleIndex}][title]" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="Ex: Módulo 1">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Liberação</label>
                        <input type="number" name="initial_modules[${moduleIndex}][release_days]" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" min="0" placeholder="Imediata">
                    </div>
                </div>
                <button type="button" class="remove-initial-module p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition" title="Remover módulo">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="initial-lessons-list space-y-3"></div>
            <button type="button" class="add-initial-lesson inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Aula
            </button>
        </div>
    `;
}

function productFileTemplate(fileIndex) {
    return `
        <div class="initial-product-file rounded-lg border border-green-100 bg-white p-4 space-y-3" data-file-index="${fileIndex}">
            <div class="flex items-start justify-between gap-3">
                <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_140px] gap-3 flex-1">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nome do arquivo</label>
                        <input type="text" name="initial_product_files[${fileIndex}][title]" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="Ex: E-book completo">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Liberação</label>
                        <input type="number" name="initial_product_files[${fileIndex}][release_days]" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" min="0" placeholder="Imediata">
                    </div>
                </div>
                <button type="button" class="remove-initial-product-file p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition" title="Remover arquivo">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-[160px_minmax(0,1fr)] gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                    <select name="initial_product_files[${fileIndex}][file_type]" class="initial-file-mode w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
                        <option value="upload">Upload</option>
                        <option value="link">Link externo</option>
                    </select>
                </div>
                <div class="initial-upload-field">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Arquivo</label>
                    <input type="file" name="initial_product_files[${fileIndex}][file]" accept=".pdf,.zip,.rar,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.mp3,.mp4" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                </div>
                <div class="initial-link-field hidden">
                    <label class="block text-xs font-medium text-gray-600 mb-1">URL</label>
                    <input type="url" name="initial_product_files[${fileIndex}][file_url]" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="https://drive.google.com/...">
                    <label class="flex items-center gap-2 text-xs text-gray-500 mt-2">
                        <input type="checkbox" name="initial_product_files[${fileIndex}][open_in_new_tab]" value="1" class="rounded border-gray-300">
                        Abrir em nova aba
                    </label>
                </div>
            </div>
        </div>
    `;
}

function addInitialLesson(moduleElement) {
    const moduleIndex = Number(moduleElement.dataset.moduleIndex);
    const lessonIndex = Number(moduleElement.dataset.nextLesson || 0);
    moduleElement.querySelector('.initial-lessons-list').insertAdjacentHTML('beforeend', lessonTemplate(moduleIndex, lessonIndex));
    moduleElement.dataset.nextLesson = String(lessonIndex + 1);
    refreshIcons();
}

function addInitialModule() {
    if (!initialModulesList) return;
    const moduleIndex = initialModuleIndex++;
    initialModulesList.insertAdjacentHTML('beforeend', moduleTemplate(moduleIndex));
    const moduleElement = initialModulesList.querySelector(`[data-module-index="${moduleIndex}"]`);
    addInitialLesson(moduleElement);
    refreshIcons();
}

function addInitialProductFile() {
    if (!initialProductFilesList) return;
    const fileIndex = initialProductFileIndex++;
    initialProductFilesList.insertAdjacentHTML('beforeend', productFileTemplate(fileIndex));
    syncInitialFileMode(initialProductFilesList.querySelector(`[data-file-index="${fileIndex}"]`));
    refreshIcons();
}

function syncInitialFileMode(fileItem) {
    if (!fileItem) return;
    const mode = fileItem.querySelector('.initial-file-mode')?.value || 'upload';
    fileItem.querySelector('.initial-upload-field')?.classList.toggle('hidden', mode !== 'upload');
    fileItem.querySelector('.initial-link-field')?.classList.toggle('hidden', mode !== 'link');
    fileItem.querySelector('.initial-upload-field input')?.toggleAttribute('disabled', mode !== 'upload');
    fileItem.querySelector('.initial-link-field input[type="url"]')?.toggleAttribute('disabled', mode !== 'link');
    fileItem.querySelector('.initial-link-field input[type="checkbox"]')?.toggleAttribute('disabled', mode !== 'link');
}

function syncProductType(selectedType = null) {
    const type = selectedType || document.querySelector('input[name="type"]:checked')?.value || 'video';
    if (pdfUpload) {
        pdfUpload.classList.toggle('hidden', type !== 'pdf');
    }
    if (videoContentBuilder) {
        videoContentBuilder.classList.toggle('hidden', type !== 'video');
    }
    if (fiscalKindSelect && !fiscalKindTouched) {
        fiscalKindSelect.value = type === 'pdf' ? 'ebook' : 'course';
    }
}

if (fiscalKindSelect) {
    fiscalKindSelect.addEventListener('change', () => fiscalKindTouched = true);
}
document.querySelectorAll('input[name="type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        syncProductType(this.value);
    });
});
addInitialModuleButton?.addEventListener('click', addInitialModule);
initialModulesList?.addEventListener('click', event => {
    const addLessonButton = event.target.closest('.add-initial-lesson');
    if (addLessonButton) {
        addInitialLesson(addLessonButton.closest('.initial-module'));
        return;
    }

    const removeLessonButton = event.target.closest('.remove-initial-lesson');
    if (removeLessonButton) {
        removeLessonButton.closest('.initial-lesson')?.remove();
        return;
    }

    const removeModuleButton = event.target.closest('.remove-initial-module');
    if (removeModuleButton) {
        removeModuleButton.closest('.initial-module')?.remove();
    }
});
addInitialProductFileButton?.addEventListener('click', addInitialProductFile);
initialProductFilesList?.addEventListener('click', event => {
    const removeButton = event.target.closest('.remove-initial-product-file');
    if (removeButton) {
        removeButton.closest('.initial-product-file')?.remove();
    }
});
initialProductFilesList?.addEventListener('change', event => {
    const modeSelect = event.target.closest('.initial-file-mode');
    if (modeSelect) {
        syncInitialFileMode(modeSelect.closest('.initial-product-file'));
    }
});
addInitialModule();
addInitialProductFile();
syncProductType();
</script>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
