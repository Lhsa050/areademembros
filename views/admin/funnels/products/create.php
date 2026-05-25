<?php
$title = 'Adicionar Produtos - ' . $funnel['name'];
$availableProducts = $availableProducts ?? [];
ob_start();
?>
<div class="max-w-full overflow-hidden">
    <a href="<?= url('/funnels/' . $funnel['id'] . '/products') ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 mb-6">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Voltar
    </a>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 min-w-0">
        <div class="min-w-0">
            <h2 class="text-xl font-bold text-gray-800">Selecionar produtos</h2>
            <p class="text-sm text-gray-500 mt-1 truncate">Vincule produtos já cadastrados ou crie um novo produto para este funil.</p>
        </div>
        <button type="button" id="open-create-product" class="inline-flex items-center justify-center gap-2 bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-800 transition flex-shrink-0">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Criar Produto
        </button>
    </div>

    <?php if (!empty($availableProducts)): ?>
        <form method="POST" action="<?= url('/funnels/' . $funnel['id'] . '/products') ?>" class="space-y-6">
            <?= csrf_field() ?>

            <div class="bg-white rounded-xl shadow-sm border p-4 max-w-full overflow-hidden">
                <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_180px] gap-3 mb-4">
                    <div class="relative min-w-0">
                        <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="text" id="product-search" class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Filtrar produtos por nome ou descrição">
                    </div>
                    <select id="product-type-filter" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Todos os tipos</option>
                        <option value="video">Vídeo</option>
                        <option value="pdf">PDF</option>
                    </select>
                </div>

                <div class="flex items-center justify-between mb-3 text-xs text-gray-400">
                    <span id="visible-products-count"><?= count($availableProducts) ?> produto<?= count($availableProducts) === 1 ? '' : 's' ?> disponível<?= count($availableProducts) === 1 ? '' : 'is' ?></span>
                    <span id="selected-products-count">0 selecionado<?= count($availableProducts) === 1 ? '' : 's' ?></span>
                </div>

                <div id="available-products-list" class="space-y-3 max-w-full overflow-hidden">
                    <?php foreach ($availableProducts as $product): ?>
                    <?php $searchText = strtolower(($product['title'] ?? '') . ' ' . ($product['description'] ?? '') . ' ' . ($product['external_product_id'] ?? '')); ?>
                    <label class="product-option bg-white rounded-xl shadow-sm border flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 p-3 cursor-pointer hover:border-blue-300 transition max-w-full overflow-hidden" data-search="<?= e($searchText) ?>" data-type="<?= e($product['type']) ?>">
                        <input type="checkbox" name="product_ids[]" value="<?= (int) $product['id'] ?>" class="product-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 flex-shrink-0">

                        <div class="w-20 aspect-[4/3] rounded-lg bg-gray-100 overflow-hidden flex-shrink-0">
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?= url($product['image']) ?>" alt="<?= e($product['title']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <i data-lucide="<?= $product['type'] === 'video' ? 'film' : 'file-text' ?>" class="w-6 h-6 text-gray-300"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="w-full flex-1 min-w-0 overflow-hidden">
                            <div class="flex flex-wrap items-center gap-2 mb-0.5 min-w-0">
                                <h3 class="font-semibold text-gray-800 truncate text-sm min-w-0 max-w-full"><?= e($product['title']) ?></h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium <?= $product['type'] === 'video' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600' ?> flex-shrink-0">
                                    <?= $product['type'] === 'video' ? 'Vídeo' : 'PDF' ?>
                                </span>
                                <?php if (!empty($product['price'])): ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600 flex-shrink-0">
                                    R$ <?= e(number_format((float) $product['price'], 2, ',', '.')) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-gray-500 truncate mb-1"><?= e($product['description']) ?></p>
                            <?php if (!empty($product['external_product_id']) || !empty($product['checkout_url'])): ?>
                            <div class="flex flex-wrap items-center gap-2 mb-1 text-[10px]">
                                <?php if (!empty($product['external_product_id'])): ?>
                                    <span class="inline-flex items-center gap-1 text-teal-700 bg-teal-50 border border-teal-100 px-2 py-0.5 rounded-full">
                                        <i data-lucide="link-2" class="w-3 h-3"></i>
                                        CartPanda: <?= e($product['external_product_id']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($product['checkout_url'])): ?>
                                    <span class="inline-flex items-center gap-1 text-amber-700 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-full">
                                        <i data-lucide="shopping-cart" class="w-3 h-3"></i>
                                        Checkout
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <div class="flex items-center gap-1 text-[10px] text-gray-400 min-w-0 max-w-full overflow-hidden">
                                <i data-lucide="git-branch" class="w-3 h-3 flex-shrink-0"></i>
                                <span><?= (int) ($product['funnel_count'] ?? 0) ?> funil<?= (int) ($product['funnel_count'] ?? 0) === 1 ? '' : 's' ?></span>
                                <?php if (!empty($product['funnel_names'])): ?>
                                    <span class="block truncate min-w-0" title="<?= e($product['funnel_names']) ?>">- <?= e($product['funnel_names']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <p id="no-products-match" class="hidden text-sm text-gray-500 text-center py-6">Nenhum produto encontrado com esse filtro.</p>
            </div>

            <div class="sticky bottom-0 bg-gray-50/95 backdrop-blur border-t border-gray-200 py-4 flex items-center justify-end gap-3">
                <a href="<?= url('/funnels/' . $funnel['id'] . '/products') ?>" class="px-5 py-2 rounded-lg font-medium text-gray-600 hover:bg-gray-100 transition">Cancelar</a>
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-500 text-white px-5 py-2 rounded-lg font-medium hover:bg-blue-600 transition">
                    <i data-lucide="link" class="w-4 h-4"></i>
                    Vincular Selecionados
                </button>
            </div>
        </form>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm border p-12 text-center mb-6">
            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="box" class="w-8 h-8 text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-800 mb-2">Nenhum produto disponível</h3>
            <p class="text-gray-500">Todos os produtos globais já estão neste funil ou ainda não há produtos cadastrados.</p>
        </div>
    <?php endif; ?>

    <div id="create-product-modal" class="fixed inset-0 z-50 hidden items-start justify-center bg-black/40 p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-xl border p-5 w-full max-w-3xl mt-8 mb-8 max-h-[calc(100vh-4rem)] overflow-y-auto">
        <div class="flex items-center justify-between gap-3 mb-4">
            <h3 class="font-semibold text-gray-800">Criar produto neste funil</h3>
            <button type="button" id="close-create-product" class="p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition" title="Fechar">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form method="POST" action="<?= url('/funnels/' . $funnel['id'] . '/products') ?>" enctype="multipart/form-data" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Tipo de Produto</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="relative">
                        <input type="radio" name="type" value="video" class="peer sr-only" checked>
                        <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                            <div class="flex items-center gap-3">
                                <i data-lucide="film" class="w-6 h-6 text-blue-500"></i>
                                <div class="min-w-0">
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
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-800">PDF / E-book</p>
                                    <p class="text-xs text-gray-500">Arquivo para download</p>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="min-w-0">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Título</label>
                    <input type="text" name="title" class="w-full px-4 py-2 border border-gray-200 rounded-lg" required>
                </div>
                <div class="min-w-0">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Valor fiscal padrão</label>
                    <input type="text" name="price" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="Ex: 97,00">
                </div>
                <div class="lg:col-span-2 min-w-0">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-200 rounded-lg" required></textarea>
                </div>
                <div class="min-w-0">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Imagem de capa</label>
                    <input type="file" name="image" accept="image/*" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                    <p class="text-xs text-gray-500 mt-1">Recomendado: proporção 4:3.</p>
                </div>
            </div>

            <div class="bg-teal-50 border border-teal-200 rounded-lg p-4">
                <label class="block text-sm font-medium text-teal-700 mb-2">
                    <i data-lucide="link-2" class="w-4 h-4 inline mr-1"></i> Codigo do produto na CartPanda
                </label>
                <input type="text" name="external_product_id" class="w-full px-4 py-2 border border-teal-300 rounded-lg bg-white" placeholder="Ex: 12345 ou SKU do produto">
                <p class="text-xs text-teal-600 mt-1">Informe o <strong>product_id</strong>, <strong>variant_id</strong> ou <strong>SKU</strong> na CartPanda. Fica fixo no produto global.</p>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                <label class="block text-sm font-medium text-amber-700 mb-2">
                    <i data-lucide="shopping-cart" class="w-4 h-4 inline mr-1"></i> Link do checkout
                </label>
                <input type="url" name="checkout_url" class="w-full px-4 py-2 border border-amber-300 rounded-lg bg-white" placeholder="https://pay.cartpanda.com/...">
                <p class="text-xs text-amber-600 mt-1">Use principalmente quando este produto for vendido como order bump ou compra avulsa. Fica fixo no produto global.</p>
            </div>

            <div id="new-pdf-upload" class="hidden border border-green-100 bg-green-50/40 rounded-lg p-4 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-gray-800">Arquivos para download</h3>
                        <p class="text-xs text-gray-500 mt-1">Adicione um ou vários arquivos, cada um com seu nome.</p>
                    </div>
                    <button type="button" id="new-add-initial-product-file" class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-white border border-green-200 text-sm font-medium text-green-700 hover:bg-green-50 transition">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Arquivo
                    </button>
                </div>
                <div id="new-initial-product-files-list" class="space-y-4"></div>
            </div>

            <div id="new-video-content-builder" class="border border-blue-100 bg-blue-50/40 rounded-lg p-4 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-gray-800">Módulos e aulas</h3>
                        <p class="text-xs text-gray-500 mt-1">Monte o conteúdo inicial antes de salvar.</p>
                    </div>
                    <button type="button" id="new-add-initial-module" class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-white border border-blue-200 text-sm font-medium text-blue-600 hover:bg-blue-50 transition">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Módulo
                    </button>
                </div>
                <div id="new-initial-modules-list" class="space-y-4"></div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-500 text-white px-5 py-2 rounded-lg font-medium hover:bg-blue-600 transition">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Criar e Vincular
                </button>
            </div>
        </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rows = Array.from(document.querySelectorAll('.product-option'));
    const search = document.getElementById('product-search');
    const typeFilter = document.getElementById('product-type-filter');
    const visibleCount = document.getElementById('visible-products-count');
    const selectedCount = document.getElementById('selected-products-count');
    const noMatch = document.getElementById('no-products-match');
    const pdfUpload = document.getElementById('new-pdf-upload');
    const createModal = document.getElementById('create-product-modal');
    const openCreate = document.getElementById('open-create-product');
    const closeCreate = document.getElementById('close-create-product');
    const videoContentBuilder = document.getElementById('new-video-content-builder');
    const initialModulesList = document.getElementById('new-initial-modules-list');
    const addInitialModuleButton = document.getElementById('new-add-initial-module');
    const initialProductFilesList = document.getElementById('new-initial-product-files-list');
    const addInitialProductFileButton = document.getElementById('new-add-initial-product-file');
    let initialModuleIndex = 0;
    let initialProductFileIndex = 0;

    function normalize(value) {
        return (value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

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
        if (!moduleElement) return;
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
    }

    function updateSelectedState() {
        const selected = rows.filter(row => row.querySelector('.product-checkbox')?.checked);
        rows.forEach(row => {
            const checked = row.querySelector('.product-checkbox')?.checked;
            row.classList.toggle('border-blue-400', !!checked);
            row.classList.toggle('bg-blue-50', !!checked);
            row.classList.toggle('shadow-md', !!checked);
        });
        if (selectedCount) {
            selectedCount.textContent = `${selected.length} selecionado${selected.length === 1 ? '' : 's'}`;
        }
    }

    function applyFilters() {
        const query = normalize(search?.value || '');
        const type = typeFilter?.value || '';
        let shown = 0;

        rows.forEach(row => {
            const matchesQuery = !query || normalize(row.dataset.search).includes(query);
            const matchesType = !type || row.dataset.type === type;
            const visible = matchesQuery && matchesType;
            row.classList.toggle('hidden', !visible);
            if (visible) shown++;
        });

        if (visibleCount) {
            visibleCount.textContent = `${shown} produto${shown === 1 ? '' : 's'} disponível${shown === 1 ? '' : 'is'}`;
        }
        if (noMatch) {
            noMatch.classList.toggle('hidden', shown > 0);
        }
    }

    rows.forEach(row => {
        row.querySelector('.product-checkbox')?.addEventListener('change', updateSelectedState);
    });
    search?.addEventListener('input', applyFilters);
    typeFilter?.addEventListener('change', applyFilters);

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

    function setCreateModal(open) {
        if (!createModal) return;
        createModal.classList.toggle('hidden', !open);
        createModal.classList.toggle('flex', open);
        document.body.classList.toggle('overflow-hidden', open);
    }

    openCreate?.addEventListener('click', () => setCreateModal(true));
    closeCreate?.addEventListener('click', () => setCreateModal(false));
    createModal?.addEventListener('click', event => {
        if (event.target === createModal) {
            setCreateModal(false);
        }
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            setCreateModal(false);
        }
    });

    updateSelectedState();
    applyFilters();
    addInitialModule();
    addInitialProductFile();
    syncProductType();
});
</script>
<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
