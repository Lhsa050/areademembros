<?php
/**
 * Painel do Funil - UX melhorada
 */
$title = $funnel['name'];

ob_start();
?>

<div class="mb-6">
    <a href="<?= url('/funnels') ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Voltar para Funis
    </a>
</div>

<!-- Header do Funil com ações rápidas -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                <i data-lucide="git-branch" class="w-7 h-7 text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><?= e($funnel['name']) ?></h1>
                <p class="text-sm text-gray-500"><?= e($funnel['site_name'] ?: $funnel['name']) ?> • <?= e($funnel['theme']) ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <!-- Duplicar -->
            <form method="POST" action="<?= url('/funnels/' . $funnel['id'] . '/duplicate') ?>" class="inline">
                <?= csrf_field() ?>
                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition" title="Duplicar funil">
                    <i data-lucide="copy" class="w-4 h-4"></i>
                    Duplicar
                </button>
            </form>
            <!-- Configuracoes -->
            <a href="<?= url('/funnels/' . $funnel['id'] . '/settings') ?>" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition" title="Configuracoes do funil">
                <i data-lucide="settings" class="w-4 h-4"></i>
                Configuracoes
            </a>
            <!-- Editar -->
            <a href="<?= url('/funnels/' . $funnel['id'] . '/edit') ?>" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition" title="Editar funil">
                <i data-lucide="edit-3" class="w-4 h-4"></i>
                Editar
            </a>
            <!-- Excluir -->
            <form method="POST" action="<?= url('/funnels/' . $funnel['id'] . '/delete') ?>" onsubmit="return confirm('Excluir este funil e todos os dados? Esta ação não pode ser desfeita.')" class="inline">
                <?= csrf_field() ?>
                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition" title="Excluir funil">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Link de Acesso -->
<div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl border border-indigo-100 p-4 mb-6">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center flex-shrink-0">
            <i data-lucide="link" class="w-4 h-4 text-indigo-500"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-medium text-indigo-600 mb-0.5">Link de acesso para membros</p>
            <input type="text" id="access-link" readonly 
                   value="<?= rtrim(env('APP_URL', ''), '/') . '/m/' . e($funnel['slug']) . '/login' ?>"
                   class="w-full bg-transparent border-none text-sm text-indigo-800 font-mono p-0 focus:ring-0 truncate">
        </div>
        <button onclick="navigator.clipboard.writeText(document.getElementById('access-link').value); this.innerHTML='<i data-lucide=&quot;check&quot; class=&quot;w-4 h-4&quot;></i> Copiado!'; this.classList.add('bg-green-500','text-white'); setTimeout(()=>{this.innerHTML='<i data-lucide=&quot;copy&quot; class=&quot;w-4 h-4&quot;></i> Copiar'; this.classList.remove('bg-green-500','text-white'); lucide.createIcons();}, 2000); lucide.createIcons();"
                class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-1.5 transition whitespace-nowrap flex-shrink-0">
            <i data-lucide="copy" class="w-4 h-4"></i>
            Copiar
        </button>
    </div>
</div>

<!-- Webhook Unificado do Funil -->
<?php if (!empty($funnel['webhook_token'])): ?>
<div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl border border-emerald-100 p-4 mb-6">
    <div class="flex items-center gap-3 mb-2">
        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center flex-shrink-0">
            <i data-lucide="webhook" class="w-4 h-4 text-emerald-600"></i>
        </div>
        <div>
            <p class="text-xs font-medium text-emerald-700">Webhook unificado do funil</p>
            <p class="text-[11px] text-emerald-500 mt-0.5">Use esta URL única na CartPanda para todos os produtos deste funil. O sistema identifica automaticamente os produtos comprados (incluindo order bumps) pelo payload.</p>
        </div>
    </div>
    <div class="flex items-center gap-2 mt-2">
        <?php $funnelWebhookUrl = rtrim(env('APP_URL', ''), '/') . '/webhook/' . $funnel['webhook_token']; ?>
        <input type="text" id="funnel-webhook-url" readonly
               value="<?= e($funnelWebhookUrl) ?>"
               class="flex-1 px-3 py-2 bg-white/70 border border-emerald-200 rounded-lg text-xs font-mono text-emerald-800 truncate">
        <button onclick="navigator.clipboard.writeText(document.getElementById('funnel-webhook-url').value); this.innerHTML='<i data-lucide=&quot;check&quot; class=&quot;w-4 h-4&quot;></i> Copiado!'; this.classList.add('bg-green-500'); this.classList.remove('bg-emerald-600','hover:bg-emerald-700'); setTimeout(()=>{this.innerHTML='<i data-lucide=&quot;copy&quot; class=&quot;w-4 h-4&quot;></i> Copiar'; this.classList.remove('bg-green-500'); this.classList.add('bg-emerald-600','hover:bg-emerald-700'); lucide.createIcons();}, 2000); lucide.createIcons();"
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-1.5 transition whitespace-nowrap flex-shrink-0">
            <i data-lucide="copy" class="w-4 h-4"></i>
            Copiar
        </button>
    </div>
    <p class="text-[10px] text-emerald-400 mt-2 flex items-center gap-1">
        <i data-lucide="info" class="w-3 h-3"></i>
        Configure o "ID Externo (CartPanda)" em cada produto para que o mapeamento automático funcione.
    </p>
</div>
<?php endif; ?>

<!-- Cards de Navegação -->
<!-- Link de Suporte -->
<div class="bg-gradient-to-r from-sky-50 to-cyan-50 rounded-xl border border-sky-100 p-4 mb-6">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center flex-shrink-0">
            <i data-lucide="messages-square" class="w-4 h-4 text-sky-600"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-medium text-sky-700 mb-0.5">Link publico de suporte</p>
            <input type="text" id="support-link" readonly
                   value="<?= rtrim(env('APP_URL', ''), '/') . '/suporte/' . e($funnel['slug']) ?>"
                   class="w-full bg-transparent border-none text-sm text-sky-900 font-mono p-0 focus:ring-0 truncate">
        </div>
        <button onclick="navigator.clipboard.writeText(document.getElementById('support-link').value); this.innerHTML='<i data-lucide=&quot;check&quot; class=&quot;w-4 h-4&quot;></i> Copiado!'; this.classList.add('bg-green-500','text-white'); setTimeout(()=>{this.innerHTML='<i data-lucide=&quot;copy&quot; class=&quot;w-4 h-4&quot;></i> Copiar'; this.classList.remove('bg-green-500','text-white'); lucide.createIcons();}, 2000); lucide.createIcons();"
                class="bg-sky-600 hover:bg-sky-700 text-white px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-1.5 transition whitespace-nowrap flex-shrink-0">
            <i data-lucide="copy" class="w-4 h-4"></i>
            Copiar
        </button>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <!-- Produtos -->
    <a href="<?= url('/funnels/' . $funnel['id'] . '/products') ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-blue-200 transition group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
                <i data-lucide="box" class="w-5 h-5 text-blue-500"></i>
            </div>
            <span class="text-2xl font-bold text-gray-800"><?= count($products) ?></span>
        </div>
        <h3 class="font-semibold text-gray-800 text-sm">Produtos</h3>
        <p class="text-xs text-gray-500">Cursos, PDFs e conteúdos</p>
    </a>
    
    <!-- Membros -->
    <a href="<?= url('/funnels/' . $funnel['id'] . '/members') ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-amber-200 transition group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-amber-500/10 flex items-center justify-center">
                <i data-lucide="users" class="w-5 h-5 text-amber-500"></i>
            </div>
            <span class="text-2xl font-bold text-gray-800"><?= $memberCount ?></span>
        </div>
        <h3 class="font-semibold text-gray-800 text-sm">Membros</h3>
        <p class="text-xs text-gray-500">Gerenciar acessos</p>
    </a>

    <!-- Importar -->
    <a href="<?= url('/funnels/' . $funnel['id'] . '/import') ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-emerald-200 transition group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                <i data-lucide="file-spreadsheet" class="w-5 h-5 text-emerald-500"></i>
            </div>
            <i data-lucide="arrow-right" class="w-4 h-4 text-gray-300 group-hover:text-emerald-400 transition"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-sm">Importar Membros</h3>
        <p class="text-xs text-gray-500">Upload via planilha .xlsx</p>
    </a>

    <!-- Ofertas -->
    <a href="<?= url('/funnels/' . $funnel['id'] . '/offers') ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-purple-200 transition group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-purple-500/10 flex items-center justify-center">
                <i data-lucide="gift" class="w-5 h-5 text-purple-500"></i>
            </div>
            <i data-lucide="arrow-right" class="w-4 h-4 text-gray-300 group-hover:text-purple-400 transition"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-sm">Ofertas (Upsell)</h3>
        <p class="text-xs text-gray-500">Popup de oferta pós-login</p>
    </a>

    <!-- Notificações Push -->
    <a href="<?= url('/funnels/' . $funnel['id'] . '/notifications') ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-orange-200 transition group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-orange-500/10 flex items-center justify-center">
                <i data-lucide="bell" class="w-5 h-5 text-orange-500"></i>
            </div>
            <i data-lucide="arrow-right" class="w-4 h-4 text-gray-300 group-hover:text-orange-400 transition"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-sm">Notificações Push</h3>
        <p class="text-xs text-gray-500">Enviar push para membros</p>
    </a>

    <!-- Configuracoes -->
    <a href="<?= url('/funnels/' . $funnel['id'] . '/settings') ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-blue-200 transition group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
                <i data-lucide="settings" class="w-5 h-5 text-blue-500"></i>
            </div>
            <i data-lucide="arrow-right" class="w-4 h-4 text-gray-300 group-hover:text-blue-400 transition"></i>
        </div>
        <h3 class="font-semibold text-gray-800 text-sm">Configuracoes</h3>
        <p class="text-xs text-gray-500">Email, login, suporte e templates</p>
    </a>
</div>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
