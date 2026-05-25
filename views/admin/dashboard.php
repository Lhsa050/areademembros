<?php
/**
 * View: Dashboard
 */
$title = 'Dashboard';
ob_start();
?>

<!-- Stat Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <!-- Membros -->
    <a href="<?= url('/funnels') ?>" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition">
                <i data-lucide="users" class="w-5 h-5 text-blue-600"></i>
            </div>
            <i data-lucide="arrow-right" class="w-4 h-4 text-gray-300 group-hover:text-gray-500 transition"></i>
        </div>
        <p class="text-2xl font-bold text-gray-900"><?= $stats['members'] ?></p>
        <p class="text-sm text-gray-500 mt-1">Membros</p>
    </a>

    <!-- Funis -->
    <a href="<?= url('/funnels') ?>" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center group-hover:bg-purple-100 transition">
                <i data-lucide="git-branch" class="w-5 h-5 text-purple-600"></i>
            </div>
            <i data-lucide="arrow-right" class="w-4 h-4 text-gray-300 group-hover:text-gray-500 transition"></i>
        </div>
        <p class="text-2xl font-bold text-gray-900"><?= $stats['funnels'] ?></p>
        <p class="text-sm text-gray-500 mt-1">Funis</p>
    </a>

    <!-- Produtos -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                <i data-lucide="package" class="w-5 h-5 text-green-600"></i>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900"><?= $stats['products'] ?></p>
        <p class="text-sm text-gray-500 mt-1">Produtos</p>
    </div>

    <!-- Gerações -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
                <i data-lucide="sparkles" class="w-5 h-5 text-amber-600"></i>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900"><?= $stats['generations'] ?></p>
        <p class="text-sm text-gray-500 mt-1">Gerações</p>
    </div>

    <!-- Admins -->
    <a href="<?= url('/admins') ?>" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center group-hover:bg-red-100 transition">
                <i data-lucide="shield" class="w-5 h-5 text-red-600"></i>
            </div>
            <i data-lucide="arrow-right" class="w-4 h-4 text-gray-300 group-hover:text-gray-500 transition"></i>
        </div>
        <p class="text-2xl font-bold text-gray-900"><?= $stats['admins'] ?></p>
        <p class="text-sm text-gray-500 mt-1">Admins</p>
    </a>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Ações Rápidas -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i data-lucide="zap" class="w-5 h-5 text-amber-500"></i>
            Ações Rápidas
        </h3>
        <div class="grid grid-cols-2 gap-3">
            <a href="<?= url('/funnels') ?>" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                <i data-lucide="user-plus" class="w-5 h-5 text-blue-500"></i>
                <span class="text-sm font-medium text-gray-700">Novo Membro</span>
            </a>
            <a href="<?= url('/funnels/create') ?>" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                <i data-lucide="plus-circle" class="w-5 h-5 text-purple-500"></i>
                <span class="text-sm font-medium text-gray-700">Novo Funil</span>
            </a>
            <a href="<?= url('/settings') ?>" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                <i data-lucide="settings" class="w-5 h-5 text-gray-500"></i>
                <span class="text-sm font-medium text-gray-700">Configurações</span>
            </a>
            <a href="<?= url('/funnels') ?>" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                <i data-lucide="eye" class="w-5 h-5 text-green-500"></i>
                <span class="text-sm font-medium text-gray-700">Ver Funis</span>
            </a>
        </div>
    </div>

    <!-- Gerações Recentes -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i data-lucide="clock" class="w-5 h-5 text-gray-400"></i>
            Gerações Recentes
        </h3>
        <?php if (empty($recentGenerations)): ?>
            <p class="text-sm text-gray-400 py-4 text-center">Nenhuma geração realizada ainda.</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($recentGenerations as $gen): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-700"><?= e($gen['site_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= e($gen['theme']) ?></p>
                    </div>
                    <span class="text-xs text-gray-400"><?= date('d/m H:i', strtotime($gen['created_at'])) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
