<?php
/**
 * View: Configurações com Abas
 */
$title = 'Configuracoes do Funil';
ob_start();

$exampleDataJson = json_encode($exampleData, JSON_UNESCAPED_UNICODE);
$settingsAction = $settingsAction ?? url('/settings');
$testEmailAction = $testEmailAction ?? url('/settings/test-email');
?>

<div class="max-w-4xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Configuracoes do Funil</h2>
            <?php if (!empty($selectedFunnel)): ?>
            <p class="text-sm text-gray-500 mt-1"><?= e($selectedFunnel['name']) ?></p>
            <?php endif; ?>
        </div>
        <a href="<?= url('/funnels/' . (int) ($selectedFunnelId ?? 0)) ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Voltar ao Funil
        </a>
    </div>

    <?php if (!empty($selectedFunnel)): ?>
    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-lg bg-blue-500/10 flex items-center justify-center flex-shrink-0">
                <i data-lucide="git-branch" class="w-4 h-4 text-blue-600"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-blue-900">Estas configuracoes valem somente para este funil.</p>
                <p class="text-xs text-blue-700 mt-1">SMTP, login, templates de email, suporte e player ficam isolados aqui.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($funnels)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <label for="settings_funnel_id" class="block text-sm font-semibold text-gray-800 mb-1">Funil das configuracoes</label>
        <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
            <select id="settings_funnel_id"
                    class="w-full sm:max-w-sm px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none bg-white"
                    onchange="window.location='<?= url('/settings') ?>?funnel_id=' + this.value">
                <?php foreach ($funnels as $funnelOption): ?>
                    <option value="<?= (int) $funnelOption['id'] ?>" <?= (int) ($selectedFunnelId ?? 0) === (int) $funnelOption['id'] ? 'selected' : '' ?>>
                        <?= e($funnelOption['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-gray-500">SMTP, login, templates e player serao salvos somente para este funil.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabs Navigation -->
    <div class="flex gap-1 bg-gray-100 rounded-xl p-1 mb-6 overflow-x-auto" id="settings-tabs">
        <button type="button" onclick="switchTab('geral')" data-tab="geral" class="settings-tab active flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition whitespace-nowrap">
            <i data-lucide="settings" class="w-4 h-4"></i> Geral
        </button>
        <button type="button" onclick="switchTab('email')" data-tab="email" class="settings-tab flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition whitespace-nowrap">
            <i data-lucide="mail" class="w-4 h-4"></i> Email
        </button>
        <button type="button" onclick="switchTab('templates')" data-tab="templates" class="settings-tab flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition whitespace-nowrap">
            <i data-lucide="file-text" class="w-4 h-4"></i> Templates
        </button>
        <button type="button" onclick="switchTab('login')" data-tab="login" class="settings-tab flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition whitespace-nowrap">
            <i data-lucide="log-in" class="w-4 h-4"></i> Login
        </button>
    </div>

    <form action="<?= e($settingsAction) ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>
        <input type="hidden" name="funnel_id" value="<?= (int) ($selectedFunnelId ?? 0) ?>">

        <!-- ====== TAB: GERAL ====== -->
        <div class="settings-panel" data-panel="geral">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">
                    <i data-lucide="settings" class="w-5 h-5 text-gray-500"></i>
                    Geral
                </h3>
                <p class="text-sm text-gray-500 mb-6">Configuracoes gerais deste funil</p>
                <div class="space-y-5">
                    <div>
                        <label for="app_name" class="block text-sm font-medium text-gray-700 mb-1">Nome da Aplicação</label>
                        <input type="text" id="app_name" name="app_name" value="<?= e($settings['app_name']) ?>"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                               placeholder="Área de Membros">
                    </div>
                </div>
            </div>

            <!-- Vídeo Player -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">
                    <i data-lucide="play-circle" class="w-5 h-5 text-red-500"></i>
                    Player de Vídeo
                </h3>
                <p class="text-sm text-gray-500 mb-6">Configurações de reprodução de vídeo</p>
                <div class="flex items-center justify-between">
                    <div>
                        <label for="plyr_enabled" class="text-sm font-medium text-gray-700">Ativar Player "Anti-Branding" (Plyr)</label>
                        <p class="text-xs text-gray-500 mt-1">Substitui o player padrão do YouTube por um player limpo.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="plyr_enabled" id="plyr_enabled" value="true" class="sr-only peer" <?= $settings['plyr_enabled'] === 'true' ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                    </label>
                </div>
            </div>

            <!-- Botão Salvar nesta aba -->
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-8 py-3 rounded-lg text-sm font-semibold flex items-center gap-2 transition shadow-sm">
                <i data-lucide="save" class="w-4 h-4"></i>
                Salvar Configurações
            </button>
        </div>

        <!-- ====== TAB: EMAIL ====== -->
        <div class="settings-panel" data-panel="email" style="display:none;">
            <!-- SMTP -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">
                    <i data-lucide="mail" class="w-5 h-5 text-indigo-500"></i>
                    Email (SMTP)
                </h3>
                <p class="text-sm text-gray-500 mb-6">Configurações para envio de emails transacionais</p>
                <div class="space-y-5">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label for="smtp_host" class="block text-sm font-medium text-gray-700 mb-1">Host SMTP</label>
                            <input type="text" id="smtp_host" name="smtp_host" value="<?= e($settings['smtp_host']) ?>"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                                   placeholder="smtp.gmail.com">
                        </div>
                        <div>
                            <label for="smtp_port" class="block text-sm font-medium text-gray-700 mb-1">Porta</label>
                            <input type="number" id="smtp_port" name="smtp_port" value="<?= e($settings['smtp_port']) ?>"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                                   placeholder="587">
                        </div>
                        <div>
                            <label for="smtp_encryption" class="block text-sm font-medium text-gray-700 mb-1">Criptografia</label>
                            <select id="smtp_encryption" name="smtp_encryption"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none bg-white">
                                <option value="tls" <?= $settings['smtp_encryption'] === 'tls' ? 'selected' : '' ?>>TLS (587)</option>
                                <option value="ssl" <?= $settings['smtp_encryption'] === 'ssl' ? 'selected' : '' ?>>SSL (465)</option>
                                <option value="none" <?= $settings['smtp_encryption'] === 'none' ? 'selected' : '' ?>>Nenhuma (25)</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="smtp_user" class="block text-sm font-medium text-gray-700 mb-1">Usuário</label>
                            <input type="text" id="smtp_user" name="smtp_user" value="<?= e($settings['smtp_user']) ?>"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                                   placeholder="seu@email.com">
                        </div>
                        <div>
                            <label for="smtp_pass" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                            <input type="password" id="smtp_pass" name="smtp_pass"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                                   placeholder="<?= empty($settings['smtp_pass']) ? 'Não configurada' : '••••••••' ?>">
                            <p class="text-xs text-gray-400 mt-1">Deixe vazio para manter a atual</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="smtp_from_email" class="block text-sm font-medium text-gray-700 mb-1">Email Remetente</label>
                            <input type="email" id="smtp_from_email" name="smtp_from_email" value="<?= e($settings['smtp_from_email']) ?>"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                                   placeholder="noreply@seusite.com">
                        </div>
                        <div>
                            <label for="smtp_from_name" class="block text-sm font-medium text-gray-700 mb-1">Nome Remetente</label>
                            <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?= e($settings['smtp_from_name']) ?>"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                                   placeholder="Área de Membros">
                        </div>
                    </div>
                    <div>
                        <label for="support_admin_email" class="block text-sm font-medium text-gray-700 mb-1">Email que recebe tickets de suporte</label>
                        <input type="email" id="support_admin_email" name="support_admin_email" value="<?= e($settings['support_admin_email']) ?>"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                               placeholder="seu@email.com">
                        <p class="text-xs text-gray-400 mt-1">Se ficar vazio, o sistema usa o primeiro super admin ativo.</p>
                    </div>
                    <?php if (!empty($settings['smtp_host'])): ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-600"></i>
                        <span class="text-sm text-green-700">SMTP configurado (<strong><?= e($settings['smtp_host']) ?></strong>)</span>
                    </div>
                    <?php else: ?>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                        <span class="text-sm text-amber-700">SMTP não configurado.</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Testar Email -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">
                    <i data-lucide="send" class="w-5 h-5 text-blue-500"></i>
                    Testar Email
                </h3>
                <p class="text-sm text-gray-500 mb-6">Envie um email de teste para verificar o SMTP</p>
                <div class="flex gap-3">
                    <input type="email" id="test_email_input"
                           class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                           placeholder="Digite o email para teste...">
                    <button type="button" id="btn_test_email"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 transition shadow-sm whitespace-nowrap">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        Enviar Teste
                    </button>
                </div>
            </div>

            <!-- Aparência dos Emails -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">
                    <i data-lucide="palette" class="w-5 h-5 text-purple-500"></i>
                    Aparência dos Emails
                </h3>
                <p class="text-sm text-gray-500 mb-6">Cores e rodapé padrão</p>
                <div class="space-y-5">
                    <div>
                        <label for="email_primary_color" class="block text-sm font-medium text-gray-700 mb-1">Cor Principal</label>
                        <div class="flex items-center gap-3">
                            <input type="color" id="email_primary_color" name="email_primary_color" value="<?= e($settings['email_primary_color']) ?>"
                                   class="w-12 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5">
                            <input type="text" id="email_primary_color_hex" value="<?= e($settings['email_primary_color']) ?>"
                                   class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono outline-none" readonly>
                        </div>
                    </div>
                    <div>
                        <label for="email_footer_text" class="block text-sm font-medium text-gray-700 mb-1">Texto do Rodapé</label>
                        <input type="text" id="email_footer_text" name="email_footer_text" value="<?= e($settings['email_footer_text']) ?>"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                               placeholder="Ex: Todos os direitos reservados.">
                    </div>
                </div>
            </div>

            <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-8 py-3 rounded-lg text-sm font-semibold flex items-center gap-2 transition shadow-sm">
                <i data-lucide="save" class="w-4 h-4"></i>
                Salvar Configurações
            </button>
        </div>

        <!-- ====== TAB: TEMPLATES ====== -->
        <div class="settings-panel" data-panel="templates" style="display:none;">
            <?php
            $templates = [
                'acesso' => [
                    'icon' => '📩',
                    'title' => 'Email de Acesso',
                    'desc' => 'Enviado ao criar um novo membro',
                    'color' => 'blue',
                    'subject_key' => 'email_acesso_subject',
                    'body_key' => 'email_acesso_body',
                    'var_key' => 'email_acesso',
                ],
                'compra' => [
                    'icon' => '✅',
                    'title' => 'Compra Aprovada',
                    'desc' => 'Enviado quando uma compra é confirmada',
                    'color' => 'green',
                    'subject_key' => 'email_compra_aprovada_subject',
                    'body_key' => 'email_compra_aprovada_body',
                    'var_key' => 'email_compra_aprovada',
                ],
                'removido' => [
                    'icon' => '🚫',
                    'title' => 'Acesso Removido',
                    'desc' => 'Enviado quando um reembolso é processado',
                    'color' => 'red',
                    'subject_key' => 'email_acesso_removido_subject',
                    'body_key' => 'email_acesso_removido_body',
                    'var_key' => 'email_acesso_removido',
                ],
            ];
            ?>

            <?php foreach ($templates as $tplKey => $tpl): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 pb-0">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">
                        <span><?= $tpl['icon'] ?></span>
                        <?= $tpl['title'] ?>
                    </h3>
                    <p class="text-sm text-gray-500 mb-4"><?= $tpl['desc'] ?></p>

                    <div class="bg-<?= $tpl['color'] ?>-50 border border-<?= $tpl['color'] ?>-200 rounded-lg p-3 mb-4">
                        <p class="text-xs font-semibold text-<?= $tpl['color'] ?>-700 mb-1.5">Variáveis disponíveis <span class="font-normal">(clique para copiar)</span>:</p>
                        <div class="flex flex-wrap gap-1.5">
                            <?php foreach ($emailVariables[$tpl['var_key']] as $var => $desc): ?>
                            <button type="button" class="var-tag bg-white border border-<?= $tpl['color'] ?>-300 text-<?= $tpl['color'] ?>-700 px-2 py-0.5 rounded text-xs font-mono hover:bg-<?= $tpl['color'] ?>-100 transition cursor-pointer" onclick="copyVar(this, '<?= $var ?>')" title="<?= e($desc) ?>">
                                <?= e($var) ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Assunto</label>
                        <input type="text" name="<?= $tpl['subject_key'] ?>" value="<?= e($settings[$tpl['subject_key']]) ?>"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none font-mono">
                    </div>

                    <div class="flex border-b border-gray-200">
                        <button type="button" class="email-mode-tab px-4 py-2.5 text-sm font-semibold border-b-2 transition active" data-tpl="<?= $tplKey ?>" data-mode="edit" onclick="switchEmailMode('<?= $tplKey ?>', 'edit')">
                            <span class="flex items-center gap-1.5">✏️ Editar HTML</span>
                        </button>
                        <button type="button" class="email-mode-tab px-4 py-2.5 text-sm font-semibold border-b-2 transition" data-tpl="<?= $tplKey ?>" data-mode="preview" onclick="switchEmailMode('<?= $tplKey ?>', 'preview')">
                            <span class="flex items-center gap-1.5">👁️ Visualizar</span>
                        </button>
                    </div>
                </div>

                <div id="edit-<?= $tplKey ?>" class="email-mode-content p-6 pt-4" data-tpl="<?= $tplKey ?>" data-mode="edit">
                    <textarea name="<?= $tpl['body_key'] ?>" id="textarea-<?= $tplKey ?>" rows="18"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none font-mono leading-relaxed bg-gray-50"
                              style="tab-size:2;resize:vertical;"><?= e($settings[$tpl['body_key']]) ?></textarea>
                    <p class="text-xs text-gray-400 mt-2">HTML completo — você tem controle total do layout. Use as variáveis acima para inserir dados dinâmicos.</p>
                </div>

                <div id="preview-<?= $tplKey ?>" class="email-mode-content" data-tpl="<?= $tplKey ?>" data-mode="preview" style="display:none;">
                    <div style="background:#e2e8f0;padding:16px;">
                        <iframe id="iframe-<?= $tplKey ?>" style="width:100%;border:none;border-radius:8px;background:#fff;min-height:500px;" sandbox="allow-same-origin"></iframe>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-8 py-3 rounded-lg text-sm font-semibold flex items-center gap-2 transition shadow-sm">
                <i data-lucide="save" class="w-4 h-4"></i>
                Salvar Configurações
            </button>
        </div>

        <!-- ====== TAB: LOGIN ====== -->
        <div class="settings-panel" data-panel="login" style="display:none;">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">
                    <i data-lucide="log-in" class="w-5 h-5 text-green-500"></i>
                    Login dos Membros
                </h3>
                <p class="text-sm text-gray-500 mb-6">Configure como os membros acessam a área</p>
                <div class="space-y-5">
                    <div class="space-y-2">
                        <?php
                        $modes = [
                            'flexible' => ['🔄 Flexível (Email, CPF ou Telefone)', 'O membro escolhe como logar', true],
                            'email_only' => ['Somente Email', 'Acesso usando apenas o email (sem senha)', false],
                            'cpf_only' => ['Somente CPF', 'Acesso usando apenas o CPF (sem senha)', false],
                            'phone_only' => ['Somente Telefone', 'DDD + número', false],
                            'password' => ['Email + Senha', 'Login tradicional com email e senha', false],
                        ];
                        foreach ($modes as $value => $info):
                            $checked = $settings['login_mode'] === $value;
                        ?>
                        <label class="flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition <?= $checked ? 'border-brand-500 bg-brand-50' : 'border-gray-200' ?>">
                            <input type="radio" name="login_mode" value="<?= $value ?>" <?= $checked ? 'checked' : '' ?> class="text-brand-500" onchange="updateLoginModeBorders()">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-700"><?= $info[0] ?></span>
                                <span class="block text-xs text-gray-500"><?= $info[1] ?></span>
                            </div>
                            <?php if ($info[2]): ?>
                            <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Recomendado</span>
                            <?php endif; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <label for="default_password" class="block text-sm font-medium text-gray-700 mb-1">Senha Padrão para Novos Membros</label>
                        <input type="text" id="default_password" name="default_password" value="<?= e($settings['default_password']) ?>"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none"
                               placeholder="Deixe vazio para não usar">
                        <p class="text-xs text-gray-400 mt-1">Usada via webhook (modo Email + Senha)</p>
                    </div>
                </div>
            </div>

            <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-8 py-3 rounded-lg text-sm font-semibold flex items-center gap-2 transition shadow-sm">
                <i data-lucide="save" class="w-4 h-4"></i>
                Salvar Configurações
            </button>
        </div>

        <!-- ====== TAB: PWA (App + Push) ====== -->
        <div class="settings-panel" data-panel="pwa" style="display:none;">
            <!-- Ícone do App -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">
                    <i data-lucide="image" class="w-5 h-5 text-blue-500"></i>
                    Ícone do App (PWA)
                </h3>
                <p class="text-sm text-gray-500 mb-6">Ícone que aparece ao instalar o app. Recomendado: 512×512px, PNG quadrado.</p>
                <div class="flex items-start gap-6">
                    <div class="flex-shrink-0">
                        <?php
                        $icon192 = ABSPATH . '/public/assets/images/icon-192.png';
                        $icon512 = ABSPATH . '/public/assets/images/icon-512.png';
                        $hasCustomIcon = file_exists($icon512) || file_exists($icon192);
                        $iconPreview = $hasCustomIcon
                            ? url('/assets/images/' . (file_exists($icon512) ? 'icon-512.png' : 'icon-192.png')) . '?t=' . time()
                            : url('/assets/images/pwa-icon.php?s=192');
                        ?>
                        <div class="w-24 h-24 rounded-2xl overflow-hidden border-2 border-gray-200 bg-gray-50 shadow-inner">
                            <img src="<?= $iconPreview ?>" alt="Ícone atual" class="w-full h-full object-cover" id="icon-preview">
                        </div>
                        <?php if ($hasCustomIcon): ?>
                        <span class="text-xs text-green-600 mt-1 block text-center">Personalizado</span>
                        <?php else: ?>
                        <span class="text-xs text-gray-400 mt-1 block text-center">Padrão</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Enviar novo ícone</label>
                            <input type="file" name="pwa_icon" accept="image/png,image/jpeg,image/webp"
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                                   onchange="previewIcon(this)">
                            <p class="text-xs text-gray-400 mt-1">PNG, JPEG ou WebP. Será redimensionado automaticamente para 192px e 512px.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notificações Push -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">
                    <i data-lucide="bell" class="w-5 h-5 text-orange-500"></i>
                    Notificações Push (VAPID)
                </h3>
                <p class="text-sm text-gray-500 mb-6">Chaves geradas automaticamente para envio de notificações push.</p>
                <div class="space-y-5">
                    <div>
                        <label for="vapid_public_key" class="block text-sm font-medium text-gray-700 mb-1">Chave Pública</label>
                        <input type="text" id="vapid_public_key" name="vapid_public_key" value="<?= e($settings['vapid_public_key'] ?? '') ?>"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none font-mono bg-gray-50" readonly>
                    </div>
                    <div>
                        <label for="vapid_private_key" class="block text-sm font-medium text-gray-700 mb-1">Chave Privada</label>
                        <input type="text" id="vapid_private_key" name="vapid_private_key" value="<?= e($settings['vapid_private_key'] ?? '') ?>"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none font-mono bg-gray-50" readonly>
                    </div>
                    <?php if (!empty($settings['vapid_public_key'] ?? '')): ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-600"></i>
                        <span class="text-sm text-green-700">Chaves VAPID geradas automaticamente ✓</span>
                    </div>
                    <?php else: ?>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                        <span class="text-sm text-amber-700">Erro ao gerar chaves VAPID. Verifique se OpenSSL está disponível no servidor.</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-8 py-3 rounded-lg text-sm font-semibold flex items-center gap-2 transition shadow-sm">
                <i data-lucide="save" class="w-4 h-4"></i>
                Salvar Configurações
            </button>
        </div>
    </form>
</div>

<style>
.settings-tab { color: #6b7280; background: transparent; border: none; cursor: pointer; }
.settings-tab:hover { color: #374151; background: rgba(255,255,255,0.5); }
.settings-tab.active { color: #1d4ed8; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.email-mode-tab { color: #9ca3af; border-color: transparent; cursor: pointer; background: none; }
.email-mode-tab:hover { color: #374151; }
.email-mode-tab.active { color: #3b82f6; border-color: #3b82f6; }
.var-tag.copied { background: #dbeafe !important; border-color: #3b82f6 !important; }
</style>

<script>
var exampleData = <?= $exampleDataJson ?>;

// ===== TAB SWITCHING =====
function switchTab(tabName) {
    document.querySelectorAll('.settings-tab').forEach(t => {
        t.classList.toggle('active', t.dataset.tab === tabName);
    });
    document.querySelectorAll('.settings-panel').forEach(p => {
        p.style.display = p.dataset.panel === tabName ? '' : 'none';
    });
    // Save to localStorage
    localStorage.setItem('funnel_settings_tab', tabName);
}
// Restore last tab
(function() {
    var saved = localStorage.getItem('funnel_settings_tab');
    if (saved && document.querySelector('.settings-tab[data-tab="' + saved + '"]')) switchTab(saved);
})();

// ===== ICON PREVIEW =====
function previewIcon(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('icon-preview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ===== EMAIL TABS =====
function switchEmailMode(tplKey, mode) {
    document.querySelectorAll('.email-mode-tab[data-tpl="' + tplKey + '"]').forEach(function(t) {
        t.classList.toggle('active', t.dataset.mode === mode);
    });
    document.querySelectorAll('.email-mode-content[data-tpl="' + tplKey + '"]').forEach(function(c) {
        c.style.display = c.dataset.mode === mode ? '' : 'none';
    });
    if (mode === 'preview') renderPreview(tplKey);
}

function renderPreview(tplKey) {
    var textarea = document.getElementById('textarea-' + tplKey);
    var iframe = document.getElementById('iframe-' + tplKey);
    var html = textarea.value;
    for (var key in exampleData) {
        html = html.split(key).join(exampleData[key]);
    }
    var doc = iframe.contentDocument || iframe.contentWindow.document;
    doc.open(); doc.write(html); doc.close();
    setTimeout(function() {
        try { iframe.style.height = Math.max(doc.documentElement.scrollHeight + 40, 400) + 'px'; } catch(e) {}
    }, 200);
}

// ===== COLOR PICKER =====
document.getElementById('email_primary_color').addEventListener('input', function() {
    document.getElementById('email_primary_color_hex').value = this.value;
});

// ===== LOGIN MODE =====
function updateLoginModeBorders() {
    document.querySelectorAll('input[name="login_mode"]').forEach(function(radio) {
        var label = radio.closest('label');
        if (radio.checked) {
            label.classList.remove('border-gray-200');
            label.classList.add('border-brand-500', 'bg-brand-50');
        } else {
            label.classList.add('border-gray-200');
            label.classList.remove('border-brand-500', 'bg-brand-50');
        }
    });
}

// ===== COPY VAR =====
function copyVar(el, text) {
    navigator.clipboard.writeText(text).then(function() {
        el.classList.add('copied');
        var orig = el.textContent;
        el.textContent = '✓ copiado!';
        setTimeout(function() { el.classList.remove('copied'); el.textContent = orig; }, 1200);
    });
}

// ===== TEST EMAIL =====
document.getElementById('btn_test_email').addEventListener('click', function() {
    var email = document.getElementById('test_email_input').value.trim();
    if (!email || !email.includes('@')) { alert('Digite um email válido para enviar o teste.'); return; }
    this.disabled = true;
    this.innerHTML = '<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.3"></circle><path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor"></path></svg> Enviando...';
    var form = document.createElement('form'); form.method='POST'; form.action='<?= e($testEmailAction) ?>';
    var csrf = document.createElement('input'); csrf.type='hidden'; csrf.name='_csrf_token'; csrf.value=document.querySelector('input[name="_csrf_token"]').value; form.appendChild(csrf);
    var inp = document.createElement('input'); inp.type='hidden'; inp.name='test_email'; inp.value=email; form.appendChild(inp);
    var funnel = document.createElement('input'); funnel.type='hidden'; funnel.name='funnel_id'; funnel.value='<?= (int) ($selectedFunnelId ?? 0) ?>'; form.appendChild(funnel);
    document.body.appendChild(form); form.submit();
});
</script>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/admin.php';
?>
