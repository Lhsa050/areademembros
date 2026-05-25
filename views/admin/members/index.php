<?php
/**
 * View: Lista de Membros
 */
$title = 'Membros — ' . e($funnel['name']);
ob_start();
?>

<div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div>
        <h3 class="text-lg font-semibold text-gray-700">Membros de <?= e($funnel['name']) ?></h3>
        <p class="text-sm text-gray-500"><?= $total ?> membro(s) encontrado(s)</p>
    </div>
    <a href="<?= url('/funnels/' . $funnel['id'] . '/members/create') ?>" class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition">
        <i data-lucide="user-plus" class="w-4 h-4"></i>
        Novo Membro
    </a>
</div>

<!-- Busca -->
<div class="mb-6">
    <form method="GET" action="<?= url('/funnels/' . $funnel['id'] . '/members') ?>" class="flex gap-2">
        <div class="relative flex-1 max-w-md">
            <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <input type="text" name="q" value="<?= e($query ?? '') ?>" placeholder="Buscar por nome, email, CPF ou telefone..."
                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none">
        </div>
        <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition">Buscar</button>
        <?php if ($query): ?>
            <a href="<?= url('/funnels/' . $funnel['id'] . '/members') ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition">Limpar</a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabela -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50">
                <th class="text-left text-xs font-medium text-gray-500 uppercase px-6 py-3">Membro</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase px-6 py-3">CPF</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase px-6 py-3">Telefone</th>
                <th class="text-center text-xs font-medium text-gray-500 uppercase px-6 py-3">Produtos</th>
                <th class="text-center text-xs font-medium text-gray-500 uppercase px-6 py-3">Status</th>
                <th class="text-center text-xs font-medium text-gray-500 uppercase px-6 py-3">Criado em</th>
                <th class="text-right text-xs font-medium text-gray-500 uppercase px-6 py-3">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php if (empty($members)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                        <i data-lucide="users" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                        <p>Nenhum membro encontrado.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($members as $member): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center text-white text-sm font-bold">
                                <?= strtoupper(substr($member['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 text-sm"><?= e($member['name']) ?></div>
                                <div class="text-xs text-gray-500"><?= e($member['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?= e($member['cpf'] ?? '-') ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?= e($member['phone'] ?? '-') ?></td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 text-xs font-medium px-2 py-1 rounded-full">
                            <i data-lucide="package" class="w-3 h-3"></i>
                            <?= $member['product_count'] ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php if ($member['status'] === 'active'): ?>
                            <span class="inline-flex items-center gap-1 bg-green-50 text-green-700 text-xs font-medium px-2 py-1 rounded-full">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                Ativo
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 bg-red-50 text-red-700 text-xs font-medium px-2 py-1 rounded-full">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                Inativo
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-center text-xs text-gray-500">
                        <?= date('d/m/Y', strtotime($member['created_at'])) ?>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="<?= url('/funnels/' . $funnel['id'] . '/members/' . $member['id'] . '/edit') ?>" class="text-brand-500 hover:text-brand-700 text-sm font-medium transition">
                            Editar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Paginação -->
<?php if ($totalPages > 1): ?>
<div class="mt-6 flex items-center justify-between">
    <p class="text-sm text-gray-500">
        Página <?= $page ?> de <?= $totalPages ?>
    </p>
    <div class="flex gap-1">
        <?php if ($page > 1): ?>
            <a href="<?= url('/funnels/' . $funnel['id'] . '/members?page=' . ($page - 1) . ($query ? '&q=' . urlencode($query) : '')) ?>"
               class="px-3 py-1 bg-white border border-gray-300 rounded text-sm hover:bg-gray-50 transition">Anterior</a>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
            <a href="<?= url('/funnels/' . $funnel['id'] . '/members?page=' . ($page + 1) . ($query ? '&q=' . urlencode($query) : '')) ?>"
               class="px-3 py-1 bg-white border border-gray-300 rounded text-sm hover:bg-gray-50 transition">Próxima</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
