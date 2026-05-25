<?php
/**
 * Lista de Administradores
 */
$title = 'Administradores';

ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <p class="text-gray-600">Gerencie os administradores do sistema.</p>
    <a href="<?= url('/admins/create') ?>" class="inline-flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Novo Admin
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Nível</th>
                <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="text-right px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($admins as $admin): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-bold">
                            <?= strtoupper(substr($admin['name'], 0, 1)) ?>
                        </div>
                        <span class="font-medium text-gray-800"><?= e($admin['name']) ?></span>
                    </div>
                </td>
                <td class="px-6 py-4 text-gray-600"><?= e($admin['email']) ?></td>
                <td class="px-6 py-4">
                    <?php if ($admin['role'] === 'super_admin'): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                        <i data-lucide="shield" class="w-3 h-3"></i>
                        Super Admin
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                        <i data-lucide="user" class="w-3 h-3"></i>
                        Admin
                    </span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4">
                    <?php if ($admin['status'] === 'active'): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        Ativo
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                        Inativo
                    </span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="<?= url('/admins/' . $admin['id'] . '/edit') ?>" class="text-blue-500 hover:bg-blue-50 p-2 rounded-lg transition">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </a>
                        <?php if ($admin['id'] !== $user['id']): ?>
                        <form method="POST" action="<?= url('/admins/' . $admin['id'] . '/delete') ?>" onsubmit="return confirm('Tem certeza que deseja remover este admin?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-red-500 hover:bg-red-50 p-2 rounded-lg transition">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
