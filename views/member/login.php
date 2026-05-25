<?php
/**
 * View: Login do Membro
 */
$title = 'Login';
$supportUrl = url('/suporte/' . $slug);
ob_start();
?>

<style>
    /* Theme-aware validation */
    body {
        background: var(--bg);
        min-height: 100vh;
        color: var(--gray-800);
    }
    /* Override layout padding for login to be centered full screen */
    .member-main { display: flex !important; align-items: center; justify-content: center; min-height: 80vh; padding: 24px; }
    .member-header, .member-footer { display: none !important; } /* Hide header/footer on login */

    .login-container {
        width: 100%;
        max-width: 420px;
    }

    .login-logo {
        text-align: center;
        margin-bottom: 40px;
    }

    .icon-circle {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, var(--brand-500), var(--brand-600));
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        backdrop-filter: blur(8px);
        box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    }

    .login-logo h1 {
        color: var(--gray-900);
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .login-logo p {
        color: var(--gray-500);
        font-size: 0.9375rem;
    }

    .login-card {
        background: var(--surface);
        backdrop-filter: blur(12px);
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        border: 1px solid var(--gray-200);
    }

    .form-group { margin-bottom: 24px; }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 8px;
    }

    .form-input {
        width: 100%;
        padding: 12px 16px;
        background: var(--bg);
        border: 2px solid var(--gray-200);
        border-radius: 12px;
        font-size: 1rem;
        color: var(--gray-900);
        transition: all 0.2s;
    }

    .form-input:focus {
        border-color: var(--brand-500);
        outline: none;
        box-shadow: 0 0 0 4px var(--brand-100);
    }

    .btn-login {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, var(--brand-600), var(--brand-700));
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    }

    .login-error {
        background: #FEE2E2;
        color: #DC2626;
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 500;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-hint {
        font-size: 0.8125rem;
        color: var(--gray-500);
        margin-top: 8px;
        line-height: 1.4;
    }

    /* Login mode tabs for flexible */
    .login-tabs {
        display: flex;
        gap: 4px;
        margin-bottom: 20px;
        background: var(--gray-100);
        border-radius: 10px;
        padding: 4px;
    }
    .login-tab {
        flex: 1;
        padding: 8px 12px;
        border: none;
        background: transparent;
        border-radius: 8px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--gray-500);
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }
    .login-tab.active {
        background: var(--surface);
        color: var(--brand-600);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .login-tab:hover:not(.active) {
        color: var(--gray-700);
    }

    .login-support {
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid var(--gray-200);
        text-align: center;
    }

    .login-support p {
        margin: 0 0 10px;
        color: var(--gray-500);
        font-size: 0.875rem;
        line-height: 1.5;
    }

    .login-support-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        color: var(--brand-600);
        font-size: 0.9rem;
        font-weight: 700;
        text-decoration: none;
        transition: color 0.2s, transform 0.2s;
    }

    .login-support-link:hover {
        color: var(--brand-700);
        transform: translateY(-1px);
    }
</style>

<div class="login-container">
    <div class="login-logo">
        <div class="icon-circle">
            <?= icon('play', 'width:28px;height:28px;color:white;') ?>
        </div>
        <h1><?= e($appName) ?></h1>
        <p>Acesse sua área de membros</p>
    </div>

    <div class="login-card">
        <?php if (!empty($error)): ?>
            <div class="login-error">
                <?= icon('alert-circle', 'width:18px;height:18px;flex-shrink:0;') ?>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('/m/' . $slug . '/login') ?>">
            <?= csrf_field() ?>
            
            <?php if ($loginMode === 'password'): ?>
                <!-- Login com Email e Senha -->
                <div class="form-group">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="identifier" class="form-input" placeholder="seu@email.com" required autofocus>
                </div>
                <div style="margin-bottom:24px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                        <label class="form-label" style="margin:0;">Senha</label>
                        <a href="<?= e($supportUrl) ?>" style="font-size:0.8125rem;color:var(--brand-600);font-weight:500;text-decoration:none;">Esqueceu a senha?</a>
                    </div>
                    <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                </div>

            <?php elseif ($loginMode === 'flexible'): ?>
                <!-- Login Flexível: escolha entre Email, CPF ou Telefone -->
                <div class="login-tabs">
                    <button type="button" class="login-tab active" data-tab="email" onclick="switchLoginTab('email')">
                        Email
                    </button>
                    <button type="button" class="login-tab" data-tab="cpf" onclick="switchLoginTab('cpf')">
                        CPF
                    </button>
                    <button type="button" class="login-tab" data-tab="phone" onclick="switchLoginTab('phone')">
                        Telefone
                    </button>
                </div>

                <div class="form-group">
                    <!-- Email field -->
                    <div id="field-email">
                        <label class="form-label">Seu E-mail</label>
                        <input type="email" id="input-email" class="form-input login-field" placeholder="seu@email.com" autofocus>
                        <p class="form-hint">Utilize o e-mail cadastrado na sua compra.</p>
                    </div>

                    <!-- CPF field -->
                    <div id="field-cpf" style="display:none;">
                        <label class="form-label">Seu CPF</label>
                        <input type="text" id="input-cpf" class="form-input login-field" placeholder="000.000.000-00" maxlength="14" inputmode="numeric">
                        <p class="form-hint">Digite os números do seu CPF.</p>
                    </div>

                    <!-- Phone field -->
                    <div id="field-phone" style="display:none;">
                        <label class="form-label">Seu Telefone</label>
                        <input type="text" id="input-phone" class="form-input login-field" placeholder="(00) 00000-0000" maxlength="15" inputmode="numeric">
                        <p class="form-hint">DDD + Número (sem código do país).</p>
                    </div>

                    <!-- Hidden field that gets submitted -->
                    <input type="hidden" name="identifier" id="identifier-hidden">
                </div>

            <?php else: ?>
                <!-- Login sem senha (identifier) — modo fixo -->
                <div class="form-group">
                    <label class="form-label">
                        <?php 
                        switch($loginMode) {
                            case 'cpf_only': echo 'Seu CPF'; break;
                            case 'phone_only': echo 'Seu Telefone'; break;
                            default: echo 'Seu E-mail'; break;
                        }
                        ?>
                    </label>
                    <input type="text" name="identifier" id="input-fixed-<?= $loginMode ?>" class="form-input" 
                           placeholder="<?= match($loginMode) {
                               'cpf_only' => '000.000.000-00',
                               'phone_only' => '(00) 00000-0000',
                               default => 'seu@email.com'
                           } ?>" 
                           <?= $loginMode === 'cpf_only' ? 'maxlength="14" inputmode="numeric"' : '' ?>
                           <?= $loginMode === 'phone_only' ? 'maxlength="15" inputmode="numeric"' : '' ?>
                           required autofocus>
                    
                    <p class="form-hint">
                        <?php 
                        switch($loginMode) {
                            case 'cpf_only': echo 'Digite apenas os números do seu CPF para entrar.'; break;
                            case 'phone_only': echo 'Digite seu telefone com DDD (sem código do país).'; break;
                            default: echo 'Utilize o e-mail cadastrado na sua compra.'; break;
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn-login">
                <?= icon('log-in', 'width:20px;height:20px;') ?>
                Entrar na Plataforma
            </button>
        </form>

        <div class="login-support">
            <p>Precisa de ajuda para acessar?</p>
            <a class="login-support-link" href="<?= e($supportUrl) ?>">
                <?= icon('mail', 'width:18px;height:18px;flex-shrink:0;') ?>
                Clique aqui para falar com o suporte
            </a>
        </div>
    </div>
    
    <p style="text-align:center;color:rgba(255,255,255,0.4);font-size:0.8125rem;margin-top:32px;">
        &copy; <?= date('Y') ?> <?= e($appName) ?>. Todos os direitos reservados.
    </p>
</div>

<script>
// ===== INPUT MASKS =====

function maskCPF(input) {
    let v = input.value.replace(/\D/g, '').substring(0, 11);
    if (v.length > 9) {
        v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
    } else if (v.length > 6) {
        v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
    } else if (v.length > 3) {
        v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
    }
    input.value = v;
}

function maskPhone(input) {
    let v = input.value.replace(/\D/g, '').substring(0, 11);
    if (v.length > 6) {
        v = v.replace(/(\d{2})(\d{4,5})(\d{1,4})/, '($1) $2-$3');
    } else if (v.length > 2) {
        v = v.replace(/(\d{2})(\d{1,5})/, '($1) $2');
    } else if (v.length > 0) {
        v = v.replace(/(\d{1,2})/, '($1');
    }
    input.value = v;
}

// Apply masks to CPF inputs
document.querySelectorAll('#input-cpf, #input-fixed-cpf_only').forEach(function(el) {
    if (el) {
        el.addEventListener('input', function() { maskCPF(this); });
    }
});

// Apply masks to phone inputs
document.querySelectorAll('#input-phone, #input-fixed-phone_only').forEach(function(el) {
    if (el) {
        el.addEventListener('input', function() { maskPhone(this); });
    }
});

// ===== FLEXIBLE MODE: TAB SWITCHING =====

<?php if ($loginMode === 'flexible'): ?>

var currentTab = 'email';

function switchLoginTab(tab) {
    currentTab = tab;

    // Update tab styles
    document.querySelectorAll('.login-tab').forEach(function(t) {
        t.classList.remove('active');
    });
    document.querySelector('.login-tab[data-tab="' + tab + '"]').classList.add('active');

    // Show/hide fields
    document.getElementById('field-email').style.display = tab === 'email' ? '' : 'none';
    document.getElementById('field-cpf').style.display = tab === 'cpf' ? '' : 'none';
    document.getElementById('field-phone').style.display = tab === 'phone' ? '' : 'none';

    // Focus the active input
    var inputId = 'input-' + tab;
    var input = document.getElementById(inputId);
    if (input) {
        setTimeout(function() { input.focus(); }, 100);
    }
}

// Before form submit, copy the active field value to the hidden identifier
document.querySelector('form').addEventListener('submit', function(e) {
    var inputId = 'input-' + currentTab;
    var input = document.getElementById(inputId);
    var hidden = document.getElementById('identifier-hidden');
    
    if (input && hidden) {
        hidden.value = input.value.trim();
    }

    if (!hidden.value) {
        e.preventDefault();
        input.focus();
        return false;
    }
    
    // Fix 5: Ensure upsell popup shows every time user logs in
    sessionStorage.removeItem('upsell_dismissed_session');
});

<?php endif; ?>

<?php if ($loginMode !== 'flexible'): ?>
// Fix 5: Ensure upsell popup shows every time user logs in for non-flexible modes
document.querySelector('form').addEventListener('submit', function() {
    sessionStorage.removeItem('upsell_dismissed_session');
});
<?php endif; ?>
</script>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/member.php'; 
?>
