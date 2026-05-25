<?php
/**
 * View: Editar Membro
 */
$title = 'Editar Membro';
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('/funnels/' . $funnel['id'] . '/members') ?>" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 transition">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Voltar para Membros
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Dados do membro -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center gap-2">
                <i data-lucide="user-cog" class="w-5 h-5 text-brand-500"></i>
                Dados do Membro
            </h3>

            <form action="<?= url('/funnels/' . $funnel['id'] . '/members/' . $member['id']) ?>" method="POST">
                <?= csrf_field() ?>

                <div class="space-y-5">
                    <!-- Nome -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome completo *</label>
                        <input type="text" id="name" name="name" value="<?= e($member['name']) ?>" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none">
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" id="email" name="email" value="<?= e($member['email']) ?>" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- CPF -->
                        <div>
                            <label for="cpf" class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                            <input type="text" id="cpf" name="cpf" value="<?= e($member['cpf'] ?? '') ?>"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none">
                        </div>

                        <!-- Telefone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                            <input type="text" id="phone" name="phone" value="<?= e($member['phone'] ?? '') ?>"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none">
                        </div>
                    </div>

                    <!-- Senha -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Nova senha</label>
                        <input type="text" id="password" name="password"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                               placeholder="Deixe vazio para manter a atual">
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none">
                            <option value="active" <?= $member['status'] === 'active' ? 'selected' : '' ?>>Ativo</option>
                            <option value="inactive" <?= $member['status'] === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-200">
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2 transition">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Salvar Alterações
                    </button>

                    <!-- Enviar Email de Acesso -->
                    <form action="<?= url('/funnels/' . $funnel['id'] . '/members/' . $member['id'] . '/send-access') ?>" method="POST" class="inline" onsubmit="return confirm('Enviar email de acesso para <?= e($member['email']) ?>?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2 transition">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                            Enviar Acesso
                        </button>
                    </form>

                    <!-- Deletar -->
                    <form action="<?= url('/funnels/' . $funnel['id'] . '/members/' . $member['id'] . '/delete') ?>" method="POST" class="ml-auto" onsubmit="return confirm('Tem certeza que deseja remover este membro?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="text-red-500 hover:text-red-700 text-sm transition flex items-center gap-1">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                            Remover
                        </button>
                    </form>
                </div>
            </form>
        </div>
    </div>

    <!-- Info lateral -->
    <div class="space-y-6">
        <!-- Info card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i data-lucide="info" class="w-4 h-4 text-gray-400"></i>
                Informações
            </h4>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-gray-500">UUID</dt>
                    <dd class="text-gray-700 font-mono text-xs mt-0.5"><?= e($member['uuid']) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500">Criado em</dt>
                    <dd class="text-gray-700"><?= date('d/m/Y H:i', strtotime($member['created_at'])) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500">Atualizado em</dt>
                    <dd class="text-gray-700"><?= date('d/m/Y H:i', strtotime($member['updated_at'])) ?></dd>
                </div>
                <?php if ($member['last_login_at']): ?>
                <div>
                    <dt class="text-gray-500">Último login</dt>
                    <dd class="text-gray-700"><?= date('d/m/Y H:i', strtotime($member['last_login_at'])) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>

        <!-- Produtos do membro -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i data-lucide="package" class="w-4 h-4 text-brand-500"></i>
                Produtos (<?= count($memberProducts) ?>)
            </h4>

            <?php if (empty($memberProducts)): ?>
                <p class="text-sm text-gray-400">Nenhum produto vinculado.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($memberProducts as $mp): ?>
                    <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                        <div>
                            <p class="text-sm font-medium text-gray-800"><?= e($mp['product_title']) ?></p>
                            <p class="text-xs text-gray-500">
                                <?= e($mp['funnel_name'] ?? 'Sem funil') ?> · Via <?= $mp['granted_by'] === 'webhook' ? 'Webhook' : 'Admin' ?>
                            </p>
                        </div>
                        <form action="<?= url('/funnels/' . $funnel['id'] . '/members/' . $member['id'] . '/products/' . $mp['product_id'] . '/remove') ?>" method="POST"
                              onsubmit="return confirm('Remover este produto do membro?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-red-400 hover:text-red-600 transition" title="Remover produto">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Adicionar produto -->
            <form action="<?= url('/funnels/' . $funnel['id'] . '/members/' . $member['id'] . '/products') ?>" method="POST" class="mt-4 pt-4 border-t border-gray-200">
                <?= csrf_field() ?>
                <label class="block text-xs font-medium text-gray-600 mb-2">Adicionar Produto</label>
                <div class="flex gap-2">
                    <select name="product_id" required class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none">
                        <option value="">Selecione...</option>
                        <?php foreach ($allProducts as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= e($p['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded-lg text-sm transition" title="Adicionar">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
