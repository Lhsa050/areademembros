<?php
/**
 * View: Banco de Dados (Backups e Atualizações)
 */
$title = 'Banco de Dados';
ob_start();
?>

<div class="max-w-4xl">
    <div class="flex items-center gap-4 mb-6">
        <a href="<?= url('/settings') ?>" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Banco de Dados</h2>
    </div>

    <!-- Atualizações / Migrações -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-6">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">
                    <i data-lucide="database" class="w-5 h-5 text-brand-500"></i>
                    Atualizações Pendentes
                </h3>
                <p class="text-sm text-gray-500">Verifica se a estrutura do banco de dados precisa ser atualizada para suportar novas funcionalidades.</p>
            </div>
            <a href="<?= url('/settings/database') ?>" class="bg-gray-50 border border-gray-200 text-gray-600 hover:text-gray-900 hover:bg-gray-100 px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2 transition flex-shrink-0">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                Verificar Agora
            </a>
        </div>

        <?php if ($status['has_updates']): ?>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600"></i>
                    </div>
                    <div>
                        <h4 class="text-amber-800 font-semibold mb-1">Atualização Necessária</h4>
                        <p class="text-amber-700 text-sm mb-4">Existem <?= count($status['pending']) ?> modificações pendentes na estrutura do banco de dados.</p>
                        
                        <ul class="space-y-2 mb-6 text-sm text-amber-800 font-mono bg-amber-100/50 p-4 rounded-lg">
                            <?php foreach ($status['pending'] as $m): ?>
                                <li>• <?= e($m['description']) ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <form method="POST" action="<?= url('/settings/database/migrate') ?>" onsubmit="return confirm('Isso fará um backup automático e atualizará o banco. Continuar?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 transition shadow-sm">
                                <i data-lucide="arrow-up-circle" class="w-4 h-4"></i>
                                Fazer Backup e Atualizar Agora
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-green-50 border border-green-200 rounded-xl p-6 flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
                </div>
                <div>
                    <h4 class="text-green-800 font-semibold mb-0.5">Banco de dados atualizado!</h4>
                    <p class="text-green-700 text-sm">Todas as <?= count($status['applied']) ?> tabelas e colunas necessárias já existem. Nenhuma ação necessária.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Backups -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">
                    <i data-lucide="save" class="w-5 h-5 text-blue-500"></i>
                    Gerenciar Backups
                </h3>
                <p class="text-sm text-gray-500">Backups locais gerados pelo sistema. Arquivos SQL puros.</p>
            </div>
            <form method="POST" action="<?= url('/settings/database/backup') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="bg-blue-50 text-blue-600 hover:bg-blue-100 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    Criar Backup Manual
                </button>
            </form>
        </div>

        <?php if (empty($backups)): ?>
            <div class="text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                <i data-lucide="inbox" class="w-8 h-8 text-gray-400 mx-auto mb-3"></i>
                <p class="text-gray-500 text-sm">Nenhum backup encontrado.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-gray-700 text-xs uppercase font-medium border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 rounded-tl-lg">Arquivo</th>
                            <th class="px-4 py-3">Data</th>
                            <th class="px-4 py-3">Tamanho</th>
                            <th class="px-4 py-3 rounded-tr-lg text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($backups as $b): ?>
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-4 py-3 font-mono text-xs text-gray-800">
                                <i data-lucide="file-text" class="w-4 h-4 inline text-blue-400 mr-1"></i>
                                <?= e($b['filename']) ?>
                            </td>
                            <td class="px-4 py-3"><?= date('d/m/Y H:i:s', $b['date']) ?></td>
                            <td class="px-4 py-3"><?= number_format($b['size'] / 1024, 2, ',', '.') ?> KB</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Baixar -->
                                    <a href="<?= url('/settings/database/download?file=' . urlencode($b['filename'])) ?>" class="p-1.5 text-blue-500 hover:bg-blue-50 rounded transition" title="Baixar SQL">
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                    </a>
                                    <!-- Restaurar -->
                                    <form method="POST" action="<?= url('/settings/database/restore') ?>" onsubmit="return confirm('ATENÇÃO: Isso vai SOBRESCREVER o banco de dados atual com este backup. Todas as vendas ou mudanças feitas após este backup serão perdidas. Deseja mesmo restaurar?')" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="filename" value="<?= e($b['filename']) ?>">
                                        <button type="submit" class="p-1.5 text-amber-500 hover:bg-amber-50 rounded transition" title="Restaurar Banco">
                                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                    <!-- Excluir -->
                                    <form method="POST" action="<?= url('/settings/database/delete') ?>" onsubmit="return confirm('Excluir este arquivo de backup permanentemente?')" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="filename" value="<?= e($b['filename']) ?>">
                                        <button type="submit" class="p-1.5 text-red-500 hover:bg-red-50 rounded transition" title="Excluir Backup">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
