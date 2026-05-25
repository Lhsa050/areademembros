<?php
/**
 * View: Criar Membro
 */
$title = 'Novo Membro';
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('/funnels/' . $funnel['id'] . '/members') ?>" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 transition">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Voltar para Membros
    </a>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center gap-2">
            <i data-lucide="user-plus" class="w-5 h-5 text-brand-500"></i>
            Cadastrar Novo Membro
        </h3>

        <form action="<?= url('/funnels/' . $funnel['id'] . '/members') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="space-y-5">
                <!-- Nome -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome completo *</label>
                    <input type="text" id="name" name="name" value="<?= e(old('name')) ?>" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                           placeholder="Nome do membro">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                           placeholder="email@exemplo.com">
                </div>

                <!-- CPF -->
                <div>
                    <label for="cpf" class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                    <input type="text" id="cpf" name="cpf" value="<?= e(old('cpf')) ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                           placeholder="000.000.000-00">
                </div>

                <!-- Telefone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                    <input type="text" id="phone" name="phone" value="<?= e(old('phone')) ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                           placeholder="+5511999999999">
                </div>

                <!-- Senha -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input type="text" id="password" name="password"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                           placeholder="Deixe vazio para usar a senha padrão">
                    <p class="text-xs text-gray-400 mt-1">Se vazio, será usada a senha padrão das configurações (se aplicável).</p>
                </div>

                <!-- Enviar email? -->
                <div class="flex items-center gap-3 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <input type="checkbox" id="send_email" name="send_email" value="1" checked
                           class="w-4 h-4 text-brand-500 border-gray-300 rounded focus:ring-brand-500">
                    <label for="send_email" class="text-sm text-blue-800">
                        <span class="font-medium">Enviar email de acesso</span>
                        <span class="block text-xs text-blue-600 mt-0.5">O membro receberá seus dados de acesso por email</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-200">
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2 transition">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    Criar Membro
                </button>
                <a href="<?= url('/funnels/' . $funnel['id'] . '/members') ?>" class="text-gray-500 hover:text-gray-700 text-sm transition">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
