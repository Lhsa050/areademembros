<?php
$title = 'Suporte';
ob_start();

$statusClasses = [
    'open' => 'bg-blue-50 text-blue-700 border-blue-100',
    'waiting_support' => 'bg-amber-50 text-amber-700 border-amber-100',
    'waiting_customer' => 'bg-purple-50 text-purple-700 border-purple-100',
    'closed' => 'bg-gray-100 text-gray-600 border-gray-200',
];
$activeTab = $activeTab ?? ($filters['tab'] ?? 'open');
$tabCounts = $tabCounts ?? ['open' => 0, 'resolved' => 0, 'all' => 0];

$queryString = function (array $extra = []) use ($filters) {
    $params = array_filter(array_merge($filters, $extra), fn($v) => $v !== '' && $v !== null);
    return http_build_query($params);
};

$tabQueryString = function (string $tab) use ($filters) {
    $params = array_filter(
        array_merge($filters, ['tab' => $tab, 'status' => '', 'page' => null]),
        fn($v) => $v !== '' && $v !== null
    );
    return http_build_query($params);
};
?>

<div class="mb-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h3 class="text-lg font-semibold text-gray-800">Central de Suporte</h3>
        <p class="text-sm text-gray-500">Todos os tickets dos funis em um unico painel.</p>
    </div>
    <a href="<?= url('/suporte') ?>" target="_blank" class="inline-flex items-center gap-2 bg-gray-900 hover:bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
        <i data-lucide="external-link" class="w-4 h-4"></i>
        Link publico
    </a>
</div>

<form method="GET" action="<?= url('/support') ?>" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
    <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="relative md:col-span-2">
            <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Buscar por ticket, cliente, email ou assunto..."
                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none">
        </div>
        <select name="funnel_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none bg-white">
            <option value="">Todos os funis</option>
            <?php foreach ($funnels as $funnel): ?>
                <option value="<?= (int) $funnel['id'] ?>" <?= (int) ($filters['funnel_id'] ?? 0) === (int) $funnel['id'] ? 'selected' : '' ?>>
                    <?= e($funnel['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mt-3 flex items-center gap-2">
        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold inline-flex items-center gap-2 transition">
            <i data-lucide="filter" class="w-4 h-4"></i>
            Filtrar
        </button>
        <a href="<?= url('/support') ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition">Limpar</a>
    </div>
</form>

<nav class="mb-6 flex flex-wrap gap-2" aria-label="Filtros de tickets">
    <?php foreach (\App\Models\SupportTicket::STATUS_TABS as $tab): ?>
        <?php $isActiveTab = $activeTab === $tab; ?>
        <a href="<?= url('/support?' . $tabQueryString($tab)) ?>"
           class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-semibold transition <?= $isActiveTab ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' ?>">
            <?= e(\App\Models\SupportTicket::statusTabLabel($tab)) ?>
            <span class="rounded-full px-2 py-0.5 text-xs <?= $isActiveTab ? 'bg-brand-100 text-brand-700' : 'bg-gray-100 text-gray-500' ?>">
                <?= (int) ($tabCounts[$tab] ?? 0) ?>
            </span>
        </a>
    <?php endforeach; ?>
</nav>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <p class="text-sm text-gray-600"><strong><?= (int) $total ?></strong> ticket(s) encontrado(s)</p>
    </div>

    <?php if (empty($tickets)): ?>
        <div class="px-6 py-16 text-center text-gray-400">
            <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-3 opacity-50"></i>
            <p>Nenhum ticket encontrado.</p>
        </div>
    <?php else: ?>
        <div class="divide-y divide-gray-100">
            <?php foreach ($tickets as $ticket): ?>
                <?php $status = $ticket['status'] ?? 'open'; ?>
                <a href="<?= url('/support/tickets/' . (int) $ticket['id']) ?>" class="block px-6 py-4 hover:bg-gray-50 transition">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-3 lg:gap-5">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-xs font-semibold text-gray-500"><?= e($ticket['ticket_number']) ?></span>
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold <?= $statusClasses[$status] ?? $statusClasses['open'] ?>">
                                    <?= e(\App\Models\SupportTicket::statusLabel($status)) ?>
                                </span>
                                <span class="text-xs text-gray-400"><?= e($ticket['funnel_name'] ?? 'Geral') ?></span>
                            </div>
                            <h4 class="font-semibold text-gray-900 truncate"><?= e($ticket['subject']) ?></h4>
                            <p class="text-sm text-gray-500 truncate">
                                <?= e($ticket['contact_name'] ?: 'Sem nome') ?> &lt;<?= e($ticket['contact_email']) ?>&gt;
                            </p>
                        </div>
                        <div class="text-left lg:text-right shrink-0">
                            <p class="text-xs text-gray-400">Ultima atividade</p>
                            <p class="text-sm font-medium text-gray-700">
                                <?= !empty($ticket['last_message_at']) ? date('d/m/Y H:i', strtotime($ticket['last_message_at'])) : date('d/m/Y H:i', strtotime($ticket['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
<div class="mt-6 flex items-center justify-between">
    <p class="text-sm text-gray-500">Pagina <?= (int) $page ?> de <?= (int) $totalPages ?></p>
    <div class="flex gap-1">
        <?php if ($page > 1): ?>
            <a href="<?= url('/support?' . $queryString(['page' => $page - 1])) ?>" class="px-3 py-1 bg-white border border-gray-300 rounded text-sm hover:bg-gray-50 transition">Anterior</a>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
            <a href="<?= url('/support?' . $queryString(['page' => $page + 1])) ?>" class="px-3 py-1 bg-white border border-gray-300 rounded text-sm hover:bg-gray-50 transition">Proxima</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
