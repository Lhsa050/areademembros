<?php
$title = 'Fiscal';
ob_start();

$money = fn($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
$statusLabels = [
    'paid' => 'Pago',
    'pending' => 'Pendente',
    'refunded' => 'Reembolsado',
    'canceled' => 'Cancelado',
];
$invoiceLabels = [
    'not_issued' => 'Nao emitida',
    'pending' => 'Processando',
    'issued' => 'Emitida',
    'error' => 'Erro',
    'rejected' => 'Rejeitada',
    'cancel_error' => 'Erro cancelamento',
    'canceled' => 'Cancelada',
];
?>
<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="text-sm text-gray-500">Vendas fiscais e NFS-e</p>
            <h3 class="text-2xl font-bold text-gray-900">Notas fiscais</h3>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= url('/fiscal/export?' . http_build_query(array_filter($filters))) ?>" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                Exportar contador
            </a>
            <a href="<?= url('/fiscal/settings') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg text-sm font-medium hover:bg-blue-600">
                <i data-lucide="settings" class="w-4 h-4"></i>
                Configurar
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white border rounded-xl p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Vendas</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= $totals['total_sales'] ?></p>
        </div>
        <div class="bg-white border rounded-xl p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Valor pago</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= $money($totals['paid_amount']) ?></p>
        </div>
        <div class="bg-white border rounded-xl p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Emitidas</p>
            <p class="text-2xl font-bold text-green-600 mt-1"><?= $totals['issued_count'] ?></p>
        </div>
        <div class="bg-white border rounded-xl p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Pendencias</p>
            <p class="text-2xl font-bold text-red-600 mt-1"><?= $totals['error_count'] ?></p>
        </div>
    </div>

    <form method="GET" action="<?= url('/fiscal') ?>" class="bg-white border rounded-xl p-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" class="px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="Cliente, email, CPF/CNPJ ou transacao">
        <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
            <option value="">Todas vendas</option>
            <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?= $key ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <select name="invoice_status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
            <option value="">Todas notas</option>
            <?php foreach ($invoiceLabels as $key => $label): ?>
                <option value="<?= $key ?>" <?= ($filters['invoice_status'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <button class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium">
            <i data-lucide="search" class="w-4 h-4"></i>
            Filtrar
        </button>
    </form>

    <div class="bg-white border rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                        <th class="px-4 py-3">Venda</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Produto</th>
                        <th class="px-4 py-3">Valor</th>
                        <th class="px-4 py-3">Nota</th>
                        <th class="px-4 py-3 text-right">Acoes</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-500">Nenhuma venda fiscal encontrada ainda.</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($sales as $sale): ?>
                    <?php
                        $invoiceStatus = $sale['fiscal_status'] ?: $sale['invoice_status'];
                        $itemTitle = $sale['product_title'] ?: ($sale['offer_title'] ?: 'Venda');
                    ?>
                    <tr class="align-top">
                        <td class="px-4 py-4">
                            <p class="font-medium text-gray-900">#<?= $sale['id'] ?></p>
                            <p class="text-xs text-gray-500"><?= e($sale['source_platform']) ?> <?= e($sale['transaction_id']) ?></p>
                            <p class="text-xs text-gray-400"><?= e($sale['paid_at'] ?? $sale['created_at']) ?></p>
                        </td>
                        <td class="px-4 py-4">
                            <p class="font-medium text-gray-900"><?= e($sale['customer_name']) ?></p>
                            <p class="text-xs text-gray-500"><?= e($sale['customer_email']) ?></p>
                            <p class="text-xs text-gray-400"><?= e($sale['customer_document']) ?></p>
                        </td>
                        <td class="px-4 py-4">
                            <p class="font-medium text-gray-900"><?= e($itemTitle) ?></p>
                            <p class="text-xs text-gray-500"><?= e($sale['funnel_name'] ?? '') ?></p>
                        </td>
                        <td class="px-4 py-4 font-semibold text-gray-900"><?= $money($sale['amount']) ?></td>
                        <td class="px-4 py-4">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $invoiceStatus === 'issued' ? 'bg-green-50 text-green-700' : ($invoiceStatus === 'error' || $invoiceStatus === 'rejected' ? 'bg-red-50 text-red-700' : 'bg-gray-100 text-gray-700') ?>">
                                <?= e($invoiceLabels[$invoiceStatus] ?? $invoiceStatus) ?>
                            </span>
                            <?php if (!empty($sale['access_key'])): ?>
                            <p class="text-[11px] text-gray-400 mt-1 font-mono"><?= e($sale['access_key']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <?php if (!$sale['invoice_id'] || in_array($invoiceStatus, ['not_issued', 'error', 'rejected'], true)): ?>
                                <form method="POST" action="<?= url('/fiscal/sales/' . $sale['id'] . '/issue') ?>">
                                    <?= csrf_field() ?>
                                    <button class="p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100" title="Emitir NFS-e"><i data-lucide="send" class="w-4 h-4"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if (!empty($sale['invoice_id']) && !empty($sale['xml_path'])): ?>
                                    <a class="p-2 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100" href="<?= url('/fiscal/invoices/' . $sale['invoice_id'] . '/download/xml') ?>" title="Baixar XML"><i data-lucide="file-code-2" class="w-4 h-4"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($sale['invoice_id']) && !empty($sale['pdf_path'])): ?>
                                    <a class="p-2 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100" href="<?= url('/fiscal/invoices/' . $sale['invoice_id'] . '/download/pdf') ?>" title="Baixar DANFSe"><i data-lucide="file-text" class="w-4 h-4"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($sale['invoice_id']) && $invoiceStatus === 'issued'): ?>
                                <form method="POST" action="<?= url('/fiscal/invoices/' . $sale['invoice_id'] . '/cancel') ?>" onsubmit="return confirm('Enviar cancelamento desta NFS-e?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="reason" value="Cancelamento solicitado pelo administrador.">
                                    <button class="p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100" title="Cancelar NFS-e"><i data-lucide="x-circle" class="w-4 h-4"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-between text-sm text-gray-500">
        <span><?= $total ?> registros</span>
        <div class="flex gap-2">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?= url('/fiscal?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>" class="px-3 py-1 rounded border <?= $i === $page ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
