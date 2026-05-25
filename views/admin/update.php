<?php
/**
 * View: Sistema de Atualização
 */
$title = 'Atualizações do Sistema';
ob_start();
?>

<div class="max-w-3xl mx-auto">

    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                <i data-lucide="download-cloud" class="w-7 h-7 text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Atualizações do Sistema</h1>
                <p class="text-sm text-gray-500">Versão atual: <strong class="text-violet-600">v<?= e($currentVersion) ?></strong></p>
            </div>
        </div>
    </div>

    <?php if (!$configured): ?>
    <!-- Não configurado -->
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
        <h3 class="text-amber-800 font-semibold flex items-center gap-2 mb-2">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
            Sistema de atualização não configurado
        </h3>
        <p class="text-sm text-amber-700 mb-3">
            Edite o arquivo <code class="bg-amber-100 px-2 py-0.5 rounded">update_config.php</code> na raiz do projeto com os dados do repositório GitHub.
        </p>
        <div class="bg-amber-100 rounded-lg p-4 text-xs font-mono text-amber-900">
            'owner' => 'seu-usuario-github',<br>
            'repo'  => 'nome-do-repositorio',<br>
            'token' => 'github_pat_xxxx...',
        </div>
    </div>
    <?php else: ?>

    <!-- Verificação de Atualização -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6" id="check-section">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                <i data-lucide="refresh-cw" class="w-5 h-5 text-violet-500"></i>
                Verificar Atualizações
            </h3>
            <button onclick="checkUpdate()" id="btn-check"
                    class="bg-violet-500 hover:bg-violet-600 text-white px-6 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 transition shadow-sm">
                <i data-lucide="search" class="w-4 h-4"></i>
                Verificar Agora
            </button>
        </div>

        <!-- Status: Verificando -->
        <div id="status-checking" style="display:none;" class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
            <svg class="animate-spin w-5 h-5 text-violet-500" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.3"></circle><path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor"></path></svg>
            <span class="text-sm text-gray-600">Consultando GitHub...</span>
        </div>

        <!-- Status: Atualizado -->
        <div id="status-uptodate" style="display:none;" class="p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
                </div>
                <div>
                    <p class="text-green-800 font-semibold">Você já está na versão mais recente!</p>
                    <p class="text-sm text-green-600">v<span id="current-v"></span></p>
                </div>
            </div>
        </div>

        <!-- Status: Nova versão -->
        <div id="status-update" style="display:none;" class="p-5 bg-violet-50 border border-violet-200 rounded-lg">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-violet-100 flex items-center justify-center">
                    <i data-lucide="sparkles" class="w-5 h-5 text-violet-600"></i>
                </div>
                <div>
                    <p class="text-violet-800 font-semibold">Nova versão disponível! 🎉</p>
                    <p class="text-sm text-violet-600">
                        v<span id="old-v"></span> → v<span id="new-v" class="font-bold"></span>
                    </p>
                </div>
            </div>

            <!-- Release info -->
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-1" id="release-name"></h4>
                <p class="text-xs text-gray-500" id="release-date"></p>
            </div>

            <!-- Changelog -->
            <div id="changelog-container" style="display:none;" class="mb-5">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">📋 O que há de novo:</h4>
                <div id="changelog" class="bg-white rounded-lg p-4 text-sm text-gray-700 border border-violet-100 prose prose-sm max-w-none"></div>
            </div>

            <!-- Aviso -->
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                <p class="text-xs text-amber-700 flex items-center gap-2">
                    <i data-lucide="shield-check" class="w-4 h-4 flex-shrink-0"></i>
                    Arquivos protegidos: <strong>.env</strong>, <strong>uploads/</strong>, <strong>storage/</strong> e <strong>update_config.php</strong> nunca são sobrescritos.
                </p>
            </div>

            <!-- Botão Atualizar -->
            <button onclick="applyUpdate()" id="btn-apply"
                    class="w-full bg-gradient-to-r from-violet-500 to-purple-600 hover:from-violet-600 hover:to-purple-700 text-white px-6 py-3.5 rounded-lg text-sm font-bold flex items-center justify-center gap-2 transition shadow-md">
                <i data-lucide="download" class="w-5 h-5"></i>
                Atualizar Agora
            </button>
        </div>

        <!-- Status: Erro -->
        <div id="status-error" style="display:none;" class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0"></i>
                <p class="text-sm text-red-700" id="error-msg"></p>
            </div>
        </div>
    </div>

    <!-- Log de Atualização -->
    <div id="update-log" style="display:none;" class="bg-gray-900 rounded-xl p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-400 mb-3 flex items-center gap-2">
            <i data-lucide="terminal" class="w-4 h-4"></i>
            Log de Atualização
        </h3>
        <div id="log-content" class="font-mono text-xs text-gray-300 space-y-1"></div>
    </div>

    <!-- Resultado Final -->
    <div id="update-result" style="display:none;" class="bg-green-50 border border-green-200 rounded-xl p-6">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                <i data-lucide="check-circle-2" class="w-6 h-6 text-green-600"></i>
            </div>
            <div>
                <p class="text-green-800 font-bold text-lg">Atualização concluída!</p>
                <p class="text-sm text-green-600">Versão: v<span id="result-version"></span></p>
            </div>
        </div>
        <a href="<?= url('/update') ?>" class="inline-flex items-center gap-2 text-sm text-green-700 hover:text-green-800 font-medium mt-2">
            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
            Recarregar página
        </a>
    </div>

    <?php endif; ?>

    <?php if (!empty($restorePoints)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                    <i data-lucide="history" class="w-5 h-5 text-slate-500"></i>
                    Pontos de restauracao
                </h3>
                <p class="text-sm text-gray-500 mt-1">Backups automaticos criados antes de atualizar o sistema.</p>
            </div>
        </div>

        <div class="space-y-3">
            <?php foreach ($restorePoints as $point): ?>
                <?php
                    $createdAt = (int) ($point['created_at'] ?? 0);
                    $systemSize = (float) ($point['system_size'] ?? 0);
                    $systemSizeMb = $systemSize > 0 ? number_format($systemSize / 1024 / 1024, 2, ',', '.') . ' MB' : '-';
                    $canRestore = !empty($point['system_exists']);
                    $hasDatabase = !empty($point['database_exists']);
                ?>
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="font-semibold text-gray-800">
                                    v<?= e($point['from_version'] ?? '-') ?> para v<?= e($point['target_version'] ?? '-') ?>
                                </span>
                                <?php if (!$canRestore): ?>
                                    <span class="text-xs bg-red-50 text-red-600 border border-red-100 px-2 py-0.5 rounded-full">arquivo ausente</span>
                                <?php elseif ($hasDatabase): ?>
                                    <span class="text-xs bg-green-50 text-green-700 border border-green-100 px-2 py-0.5 rounded-full">sistema + banco</span>
                                <?php else: ?>
                                    <span class="text-xs bg-amber-50 text-amber-700 border border-amber-100 px-2 py-0.5 rounded-full">somente sistema</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-gray-500 font-mono truncate"><?= e($point['system_backup'] ?? '') ?></p>
                            <p class="text-xs text-gray-500 mt-1">
                                <?= $createdAt ? date('d/m/Y H:i:s', $createdAt) : '-' ?> · <?= $systemSizeMb ?>
                            </p>
                        </div>

                        <form method="POST" action="<?= url('/update/restore') ?>" class="flex-shrink-0" onsubmit="return confirm('Restaurar este ponto? O sistema atual sera salvo em um backup de seguranca antes da restauracao.')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="restore_id" value="<?= e($point['id'] ?? '') ?>">
                            <label class="flex items-center gap-2 text-xs text-gray-600 mb-2 justify-end">
                                <input type="checkbox" name="restore_database" value="1" <?= $hasDatabase ? 'checked' : 'disabled' ?> class="rounded border-gray-300">
                                Restaurar banco
                            </label>
                            <button type="submit" <?= $canRestore ? '' : 'disabled' ?>
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition <?= $canRestore ? 'bg-slate-800 hover:bg-slate-900 text-white' : 'bg-gray-100 text-gray-400 cursor-not-allowed' ?>">
                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                Restaurar
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
var csrfToken = '<?= \App\Core\Security::csrfToken() ?>';

function parseJsonResponse(response) {
    return response.text().then(function(text) {
        var body = text.trim();
        if (!body) {
            throw new Error('Resposta vazia do servidor (HTTP ' + response.status + ')');
        }

        try {
            var data = JSON.parse(body);
            if (!response.ok && !data.error) {
                data.error = 'Erro HTTP ' + response.status;
            }
            return data;
        } catch (err) {
            var preview = body.replace(/\s+/g, ' ').slice(0, 300);
            throw new Error('Resposta invalida do servidor (HTTP ' + response.status + '): ' + preview);
        }
    });
}

function checkUpdate() {
    hideAll();
    show('status-checking');
    var btnCheck = document.getElementById('btn-check');
    if (btnCheck) {
        btnCheck.disabled = true;
        btnCheck.classList.add('opacity-50');
    }

    fetch('<?= url('/update/check') ?>', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(parseJsonResponse)
    .then(function(data) {
        hideAll();
        if (btnCheck) {
            btnCheck.disabled = false;
            btnCheck.classList.remove('opacity-50');
        }

        if (data.error) {
            document.getElementById('error-msg').textContent = data.error;
            show('status-error');
            return;
        }

        if (!data.has_update) {
            document.getElementById('current-v').textContent = data.current_version;
            show('status-uptodate');
        } else {
            document.getElementById('old-v').textContent = data.current_version;
            document.getElementById('new-v').textContent = data.latest_version;
            document.getElementById('release-name').textContent = data.release_name;
            if (data.published_at) {
                var d = new Date(data.published_at);
                document.getElementById('release-date').textContent = 'Publicada em ' + d.toLocaleDateString('pt-BR') + ' às ' + d.toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'});
            }
            if (data.release_body) {
                document.getElementById('changelog').innerHTML = simpleMarkdown(data.release_body);
                show('changelog-container');
            }
            show('status-update');
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
    })
    .catch(function(err) {
        hideAll();
        document.getElementById('error-msg').textContent = 'Erro de conexão: ' + err.message;
        show('status-error');
        if (btnCheck) {
            btnCheck.disabled = false;
            btnCheck.classList.remove('opacity-50');
        }
    });
}

function applyUpdate() {
    var btn = document.getElementById('btn-apply');
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-5 h-5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.3"></circle><path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor"></path></svg> Atualizando...';

    show('update-log');
    addLog('🚀 Iniciando atualização...');

    var formData = new FormData();
    formData.append('_csrf_token', csrfToken);

    fetch('<?= url('/update/apply') ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(parseJsonResponse)
    .then(function(data) {
        if (data.steps) {
            data.steps.forEach(function(step) {
                addLog(step);
            });
        }

        if (data.success) {
            document.getElementById('result-version').textContent = data.new_version;
            show('update-result');
            hide('status-update');
            addLog('');
            addLog('✅ Sucesso! Recarregue a página.');
        } else if (data.error) {
            addLog('❌ ' + data.error);
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="download" class="w-5 h-5"></i> Tentar Novamente';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    })
    .catch(function(err) {
        addLog('❌ Erro de conexão: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="download" class="w-5 h-5"></i> Tentar Novamente';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
}

function addLog(text) {
    var el = document.createElement('div');
    el.textContent = text;
    if (text.includes('✅') || text.includes('🎉')) el.classList.add('text-green-400');
    if (text.includes('❌')) el.classList.add('text-red-400');
    if (text.includes('🚀')) el.classList.add('text-blue-400');
    if (text.includes('Banco de dados') || text.includes('Migracoes aplicadas')) el.classList.add('text-green-400');
    if (text.includes('ERRO') || text.includes('Erro')) el.classList.add('text-red-400');
    if (text.includes('Backup do') || text.includes('Ponto de restauracao')) el.classList.add('text-blue-300');
    document.getElementById('log-content').appendChild(el);
}

function show(id) { var el = document.getElementById(id); if (el) el.style.display = 'block'; }
function hide(id) { var el = document.getElementById(id); if (el) el.style.display = 'none'; }
function hideAll() {
    ['status-checking', 'status-uptodate', 'status-update', 'status-error', 'changelog-container'].forEach(function(id) {
        hide(id);
    });
}

function simpleMarkdown(text) {
    return text
        .replace(/^### (.+)$/gm, '<h4 class="font-semibold text-gray-800 mt-3">$1</h4>')
        .replace(/^## (.+)$/gm, '<h3 class="font-bold text-gray-800 mt-3">$1</h3>')
        .replace(/^- (.+)$/gm, '<div class="flex items-start gap-2"><span class="text-violet-400 mt-px">•</span><span>$1</span></div>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/`(.+?)`/g, '<code class="bg-violet-100 text-violet-800 px-1 py-0.5 rounded text-xs">$1</code>')
        .replace(/\n\n/g, '<br><br>')
        .replace(/\n/g, '<br>');
}
</script>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
