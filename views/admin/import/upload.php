<?php
/**
 * View: Upload de arquivo XLSX para importação
 */
$title = 'Importar Membros — ' . $funnel['name'];
ob_start();
?>

<div class="max-w-2xl mx-auto">

    <div class="mb-6">
        <a href="<?= url('/funnels/' . $funnel['id']) ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Voltar para <?= e($funnel['name']) ?>
        </a>
    </div>

    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center">
                <i data-lucide="upload" class="w-7 h-7 text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Importar Membros</h1>
                <p class="text-sm text-gray-500"><?= e($funnel['name']) ?></p>
            </div>
        </div>
    </div>

    <!-- Instruções -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6">
        <h3 class="text-sm font-semibold text-blue-800 mb-2 flex items-center gap-2">
            <i data-lucide="info" class="w-4 h-4"></i>
            Como funciona
        </h3>
        <ul class="text-sm text-blue-700 space-y-1.5">
            <li>📄 Envie um arquivo <strong>.xlsx</strong> com os dados dos membros</li>
            <li>✅ Apenas registros com <strong>Payment status = "Paid"</strong> serão importados</li>
            <li>🔗 Você poderá mapear os produtos do arquivo para produtos ou ofertas do funil</li>
            <li>🔄 Membros existentes terão apenas novos produtos adicionados</li>
            <li>Arquivos grandes sao processados em lotes para evitar travamentos</li>
        </ul>
        <div class="mt-3 text-xs text-blue-600">
            <strong>Colunas reconhecidas:</strong> Email address, Full name, CPF, mobile_no, product_id, Product name, Payment status
        </div>
    </div>

    <!-- Upload Form -->
    <form action="<?= url('/funnels/' . $funnel['id'] . '/import/parse') ?>" method="POST" enctype="multipart/form-data" id="uploadForm">
        <?= csrf_field() ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <!-- Dropzone -->
            <div id="dropzone"
                 class="border-2 border-dashed border-gray-300 rounded-xl p-12 text-center cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/30 transition-all duration-200"
                 onclick="document.getElementById('xlsx_file').click()">

                <div id="dropzone-icon" class="mb-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto">
                        <i data-lucide="file-spreadsheet" class="w-8 h-8 text-gray-400"></i>
                    </div>
                </div>

                <div id="dropzone-text">
                    <p class="text-gray-600 font-medium mb-1">Arraste o arquivo .xlsx aqui</p>
                    <p class="text-sm text-gray-400">ou clique para selecionar</p>
                </div>

                <div id="dropzone-file" style="display:none;">
                    <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="file-check-2" class="w-8 h-8 text-emerald-600"></i>
                    </div>
                    <p class="text-emerald-700 font-semibold mb-1" id="file-name"></p>
                    <p class="text-sm text-gray-400" id="file-size"></p>
                </div>

                <input type="file" name="xlsx_file" id="xlsx_file" accept=".xlsx,.xls" class="hidden">
            </div>

            <!-- Submit -->
            <div class="mt-6 flex items-center justify-between">
                <p class="text-xs text-gray-400">Formatos: .xlsx, .xls</p>
                <button type="submit" id="btn-parse" disabled
                        class="bg-emerald-500 hover:bg-emerald-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white px-8 py-3 rounded-lg text-sm font-semibold flex items-center gap-2 transition shadow-sm">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    Analisar Arquivo
                </button>
            </div>
        </div>
    </form>
</div>

<script>
var dropzone = document.getElementById('dropzone');
var fileInput = document.getElementById('xlsx_file');
var btnParse = document.getElementById('btn-parse');

// Drag & drop
['dragenter', 'dragover'].forEach(function(ev) {
    dropzone.addEventListener(ev, function(e) {
        e.preventDefault();
        dropzone.classList.add('border-emerald-400', 'bg-emerald-50/30');
    });
});
['dragleave', 'drop'].forEach(function(ev) {
    dropzone.addEventListener(ev, function(e) {
        e.preventDefault();
        dropzone.classList.remove('border-emerald-400', 'bg-emerald-50/30');
    });
});
dropzone.addEventListener('drop', function(e) {
    e.preventDefault();
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        showFile(e.dataTransfer.files[0]);
    }
});

fileInput.addEventListener('change', function() {
    if (this.files.length) showFile(this.files[0]);
});

function showFile(file) {
    document.getElementById('dropzone-icon').style.display = 'none';
    document.getElementById('dropzone-text').style.display = 'none';
    document.getElementById('dropzone-file').style.display = 'block';
    document.getElementById('file-name').textContent = file.name;
    document.getElementById('file-size').textContent = (file.size / 1024).toFixed(1) + ' KB';
    btnParse.disabled = false;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// Loading state on submit
document.getElementById('uploadForm').addEventListener('submit', function() {
    btnParse.disabled = true;
    btnParse.innerHTML = '<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.3"></circle><path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor"></path></svg> Analisando...';
});
</script>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
