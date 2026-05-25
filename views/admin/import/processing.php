<?php
/**
 * View: processamento em lotes da importacao.
 */
$title = 'Processando Importacao';
ob_start();

$total = (int) ($counts['paid_rows'] ?? 0);
?>

<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="<?= url('/funnels/' . $funnel['id']) ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Voltar para <?= e($funnel['name']) ?>
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center">
                <i data-lucide="database" class="w-7 h-7 text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Importando membros</h1>
                <p class="text-sm text-gray-500"><?= e($funnel['name']) ?> - <?= $total ?> registros validos</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-semibold text-gray-700" id="progress-label">Preparando...</span>
            <span class="text-sm font-bold text-emerald-600" id="progress-percent">0%</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
            <div id="progress-bar" class="h-3 bg-emerald-500 rounded-full transition-all duration-300" style="width:0%"></div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mt-6">
            <div class="rounded-lg border border-gray-200 p-3 text-center">
                <p class="text-xl font-bold text-gray-800" id="stat-processed">0</p>
                <p class="text-xs text-gray-500">Processados</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-3 text-center">
                <p class="text-xl font-bold text-emerald-600" id="stat-created">0</p>
                <p class="text-xs text-gray-500">Criados</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-3 text-center">
                <p class="text-xl font-bold text-blue-600" id="stat-updated">0</p>
                <p class="text-xs text-gray-500">Ja existiam</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-3 text-center">
                <p class="text-xl font-bold text-purple-600" id="stat-granted">0</p>
                <p class="text-xs text-gray-500">Acessos</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-3 text-center">
                <p class="text-xl font-bold text-red-500" id="stat-errors">0</p>
                <p class="text-xs text-gray-500">Erros</p>
            </div>
        </div>

        <div id="import-status" class="mt-5 text-sm text-gray-500">
            Mantenha esta pagina aberta enquanto a importacao roda em lotes.
        </div>

        <div id="done-actions" class="mt-6 hidden">
            <a href="<?= e($membersUrl) ?>" class="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition">
                <i data-lucide="users" class="w-4 h-4"></i>
                Ver membros
            </a>
        </div>
    </div>
</div>

<script>
(function() {
    var batchUrl = <?= json_encode($batchUrl) ?>;
    var jobToken = <?= json_encode($jobToken) ?>;
    var csrfToken = <?= json_encode(csrf_token()) ?>;
    var running = true;

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function updateUi(data) {
        var progress = data.progress || { processed: 0, total: <?= $total ?>, percent: 0 };
        var stats = data.stats || {};
        var percent = Math.min(100, Math.max(0, Number(progress.percent || 0)));

        document.getElementById('progress-bar').style.width = percent + '%';
        setText('progress-percent', percent.toFixed(1).replace('.0', '') + '%');
        setText('progress-label', progress.processed + ' de ' + progress.total + ' registros');
        setText('stat-processed', progress.processed || 0);
        setText('stat-created', stats.created || 0);
        setText('stat-updated', stats.updated || 0);
        setText('stat-granted', stats.products_granted || 0);
        setText('stat-errors', stats.errors || 0);

        if (data.done) {
            running = false;
            setText('import-status', 'Importacao concluida com sucesso.');
            document.getElementById('done-actions').classList.remove('hidden');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    function runBatch() {
        if (!running) return;

        fetch(batchUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ job_token: jobToken })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data.success) {
                running = false;
                setText('import-status', data.error || 'A importacao falhou.');
                return;
            }
            updateUi(data);
            if (!data.done) {
                setTimeout(runBatch, 150);
            }
        })
        .catch(function(error) {
            running = false;
            setText('import-status', 'Erro de comunicacao durante a importacao: ' + error.message);
        });
    }

    runBatch();
})();
</script>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
