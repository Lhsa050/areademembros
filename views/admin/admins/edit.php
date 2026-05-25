<?php
/**
 * Editar Administrador
 */
$title = 'Editar Administrador';

ob_start();
?>

<div class="max-w-2xl">
    <a href="<?= url('/admins') ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 mb-6">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Voltar
    </a>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="<?= url('/admins/' . $admin['id']) ?>" class="space-y-6">
            <?= csrf_field() ?>
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nome</label>
                <input type="text" id="name" name="name" value="<?= e(old('name') ?: $admin['name']) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                <?php if (isset($errors['name'])): ?>
                <p class="text-red-500 text-xs mt-1"><?= e($errors['name'][0]) ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" id="email" name="email" value="<?= e(old('email') ?: $admin['email']) ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                <?php if (isset($errors['email'])): ?>
                <p class="text-red-500 text-xs mt-1"><?= e($errors['email'][0]) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Nova Senha <span class="text-gray-400">(deixe vazio para manter)</span></label>
                    <input type="password" id="password" name="password" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <?php if (isset($errors['password'])): ?>
                    <p class="text-red-500 text-xs mt-1"><?= e($errors['password'][0]) ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirmar Nova Senha</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Nível</label>
                    <select id="role" name="role" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="admin" <?= $admin['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="super_admin" <?= $admin['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                    </select>
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status" name="status" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="active" <?= $admin['status'] === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inactive" <?= $admin['status'] === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
            </div>
            
            <div class="flex gap-4 pt-4">
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-600 transition">
                    Salvar Alterações
                </button>
                <a href="<?= url('/admins') ?>" class="px-6 py-2 rounded-lg font-medium text-gray-600 hover:bg-gray-100 transition">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
