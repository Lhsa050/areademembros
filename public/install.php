<?php
/**
 * Instalador do Sistema
 */

// Inicia sessão se não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se já está instalado (só considera instalado se tiver .env E tiver admin no banco)
$isInstalled = false;
if (file_exists(dirname(__DIR__) . '/.env')) {
    $envContent = file_get_contents(dirname(__DIR__) . '/.env');
    if (strpos($envContent, 'DB_NAME=') !== false && strpos($envContent, 'CHANGE_THIS') === false) {
        // Tenta verificar se há admin no banco
        try {
            // Carrega variáveis do .env manualmente
            $lines = explode("\n", $envContent);
            $env = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $line[0] === '#') continue;
                if (strpos($line, '=') !== false) {
                    [$key, $value] = explode('=', $line, 2);
                    $env[trim($key)] = trim($value);
                }
            }
            
            $pdo = new PDO(
                "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_NAME']};charset=utf8mb4",
                $env['DB_USER'],
                $env['DB_PASS'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM admins");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['total'] > 0) {
                $isInstalled = true;
            }
        } catch (Exception $e) {
            // Se der erro, não está instalado corretamente
            echo '<h1>Instalador bloqueado</h1>';
            echo '<p>Ja existe um arquivo <code>.env</code>, mas nao foi possivel validar o banco de dados configurado.</p>';
            echo '<p>Por seguranca, o instalador nao pode sobrescrever a configuracao atual. Corrija o <code>.env</code> manualmente ou remova-o antes de instalar novamente.</p>';
            exit;
        }
    }
}

if ($isInstalled) {
    echo '<h1>Sistema já instalado</h1>';
    echo '<p><a href="/">Ir para o painel</a></p>';
    exit;
}

$step = $_GET['step'] ?? 1;
$error = null;
$success = null;

// Processa instalação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = (int) $_POST['step'];
    
    if ($step === 1) {
        // Verifica requisitos
        $requirements = [
            'PHP 8.1+' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'PDO' => extension_loaded('pdo'),
            'PDO MySQL' => extension_loaded('pdo_mysql'),
            'JSON' => extension_loaded('json'),
            'MBString' => extension_loaded('mbstring'),
            'storage gravável' => is_writable(dirname(__DIR__) . '/storage'),
        ];
        
        $allPassed = !in_array(false, $requirements, true);
        
        if ($allPassed) {
            header('Location: ?step=2');
            exit;
        } else {
            $error = 'Alguns requisitos não foram atendidos.';
        }
    }
    
    if ($step === 2) {
        // Configura banco de dados
        $dbHost = $_POST['db_host'] ?? '127.0.0.1';
        $dbPort = $_POST['db_port'] ?? '3306';
        $dbName = $_POST['db_name'] ?? '';
        $dbUser = $_POST['db_user'] ?? '';
        $dbPass = $_POST['db_pass'] ?? '';
        
        try {
            $pdo = new PDO(
                "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
                $dbUser,
                $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Cria tabelas
            $schema = include dirname(__DIR__) . '/database/schema.php';
            foreach ($schema as $table => $sql) {
                $pdo->exec($sql);
            }
            
            // Executa migration do webhook unificado
            $hasFunnelWebhookCol = $pdo->query("SHOW COLUMNS FROM funnels LIKE 'webhook_token'")->fetch();
            if (!$hasFunnelWebhookCol) {
                $pdo->exec("ALTER TABLE funnels ADD COLUMN webhook_token VARCHAR(64) NULL UNIQUE AFTER language");
            }
            $hasExtCol = $pdo->query("SHOW COLUMNS FROM funnel_products LIKE 'external_product_id'")->fetch();
            if (!$hasExtCol) {
                $pdo->exec("ALTER TABLE funnel_products ADD COLUMN external_product_id VARCHAR(191) NULL AFTER webhook_token");
                try { $pdo->exec("CREATE INDEX idx_external_product ON funnel_products (funnel_id, external_product_id)"); } catch (\Exception $e) {}
            }
            $hasRoleCol = $pdo->query("SHOW COLUMNS FROM funnel_products LIKE 'funnel_role'")->fetch();
            if (!$hasRoleCol) {
                $pdo->exec("ALTER TABLE funnel_products ADD COLUMN funnel_role ENUM('principal', 'bonus', 'orderbump') NULL DEFAULT NULL AFTER external_product_id");
            }
            
            // Cria arquivo .env
            $appKey = bin2hex(random_bytes(16));
            $envContent = <<<ENV
# Configuração do Ambiente
APP_ENV=production
APP_DEBUG=false
APP_URL=https://{$_SERVER['HTTP_HOST']}
APP_KEY={$appKey}

# Banco de Dados
DB_HOST={$dbHost}
DB_PORT={$dbPort}
DB_NAME={$dbName}
DB_USER={$dbUser}
DB_PASS={$dbPass}

# Timezone
TIMEZONE=America/Sao_Paulo
ENV;
            
            file_put_contents(dirname(__DIR__) . '/.env', $envContent);
            
            // Salva dados na sessão para step 3
            $_SESSION['install_pdo'] = serialize([
                'host' => $dbHost,
                'port' => $dbPort,
                'name' => $dbName,
                'user' => $dbUser,
                'pass' => $dbPass
            ]);
            
            header('Location: ?step=3');
            exit;
            
        } catch (PDOException $e) {
            $error = 'Erro de conexão: ' . $e->getMessage();
        }
    }
    
    if ($step === 3) {
        // Cria admin inicial
        $dbConfig = unserialize($_SESSION['install_pdo'] ?? '');
        
        if (!$dbConfig) {
            header('Location: ?step=2');
            exit;
        }
        
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Preencha todos os campos.';
        } elseif (strlen($password) < 6) {
            $error = 'A senha deve ter pelo menos 6 caracteres.';
        } else {
            try {
                $pdo = new PDO(
                    "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4",
                    $dbConfig['user'],
                    $dbConfig['pass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                $uuid = sprintf('%s-%s-%s-%s-%s',
                    bin2hex(random_bytes(4)),
                    bin2hex(random_bytes(2)),
                    bin2hex(random_bytes(2)),
                    bin2hex(random_bytes(2)),
                    bin2hex(random_bytes(6))
                );
                
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                
                $stmt = $pdo->prepare("
                    INSERT INTO admins (uuid, name, email, password, role, status)
                    VALUES (?, ?, ?, ?, 'super_admin', 'active')
                ");
                $stmt->execute([$uuid, $name, $email, $hashedPassword]);
                
                unset($_SESSION['install_pdo']);
                
                header('Location: ?step=4');
                exit;
                
            } catch (PDOException $e) {
                $error = 'Erro ao criar admin: ' . $e->getMessage();
            }
        }
    }
}

// Verifica requisitos para step 1
$requirements = [];
if ($step == 1) {
    $requirements = [
        'PHP 8.1+' => version_compare(PHP_VERSION, '8.1.0', '>='),
        'PDO' => extension_loaded('pdo'),
        'PDO MySQL' => extension_loaded('pdo_mysql'),
        'JSON' => extension_loaded('json'),
        'MBString' => extension_loaded('mbstring'),
        'storage gravável' => is_writable(dirname(__DIR__) . '/storage'),
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Gerador de Área de Membros</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-500 mb-4">
                <i data-lucide="layout-dashboard" class="w-8 h-8 text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Instalação</h1>
            <p class="text-gray-500">Gerador de Área de Membros</p>
        </div>
        
        <!-- Steps -->
        <div class="flex items-center justify-center mb-8">
            <?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold <?= $i < $step ? 'bg-green-500 text-white' : ($i == $step ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-500') ?>">
                    <?= $i < $step ? '✓' : $i ?>
                </div>
                <?php if ($i < 4): ?>
                <div class="w-12 h-1 <?= $i < $step ? 'bg-green-500' : 'bg-gray-200' ?>"></div>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>
        
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
            <!-- Step 1: Requisitos -->
            <h2 class="text-xl font-bold text-gray-800 mb-4">Verificar Requisitos</h2>
            
            <div class="space-y-2 mb-6">
                <?php foreach ($requirements as $name => $passed): ?>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-700"><?= $name ?></span>
                    <?php if ($passed): ?>
                    <span class="text-green-500 font-bold">✓ OK</span>
                    <?php else: ?>
                    <span class="text-red-500 font-bold">✗ Falhou</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <form method="POST">
                <input type="hidden" name="step" value="1">
                <button type="submit" class="w-full bg-blue-500 text-white py-3 rounded-lg font-medium hover:bg-blue-600 transition">
                    Continuar
                </button>
            </form>
            
            <?php elseif ($step == 2): ?>
            <!-- Step 2: Banco de Dados -->
            <h2 class="text-xl font-bold text-gray-800 mb-4">Configurar Banco de Dados</h2>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="step" value="2">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Host</label>
                        <input type="text" name="db_host" value="127.0.0.1" class="w-full px-4 py-2 border border-gray-200 rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Porta</label>
                        <input type="text" name="db_port" value="3306" class="w-full px-4 py-2 border border-gray-200 rounded-lg" required>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Banco</label>
                    <input type="text" name="db_name" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="gerador_membros" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Usuário</label>
                    <input type="text" name="db_user" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="root" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input type="password" name="db_pass" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                </div>
                
                <button type="submit" class="w-full bg-blue-500 text-white py-3 rounded-lg font-medium hover:bg-blue-600 transition">
                    Criar Tabelas
                </button>
            </form>
            
            <?php elseif ($step == 3): ?>
            <!-- Step 3: Admin -->
            <h2 class="text-xl font-bold text-gray-800 mb-4">Criar Administrador</h2>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="step" value="3">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                    <input type="text" name="name" class="w-full px-4 py-2 border border-gray-200 rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" class="w-full px-4 py-2 border border-gray-200 rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input type="password" name="password" class="w-full px-4 py-2 border border-gray-200 rounded-lg" minlength="6" required>
                </div>
                
                <button type="submit" class="w-full bg-blue-500 text-white py-3 rounded-lg font-medium hover:bg-blue-600 transition">
                    Criar Admin
                </button>
            </form>
            
            <?php elseif ($step == 4): ?>
            <!-- Step 4: Sucesso -->
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-green-500 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="check" class="w-8 h-8 text-white"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Instalação Concluída!</h2>
                <p class="text-gray-500 mb-6">O sistema foi instalado com sucesso.</p>
                
                <div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-lg mb-6 text-sm">
                    <strong>Importante:</strong> Delete este arquivo (install.php) por segurança.
                </div>
                
                <a href="/login" class="inline-block bg-blue-500 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-600 transition">
                    Acessar o Painel
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>lucide.createIcons();</script>
</body>
</html>
