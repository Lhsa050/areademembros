<?php
/**
 * Bootstrap - Inicialização do Sistema
 */

// Constante de segurança
define('ABSPATH', __DIR__);

// Sobrescrever headers restritivos do Nginx que bloqueiam embeds do YouTube
// O Nginx envia X-Frame-Options: SAMEORIGIN e Referrer-Policy: same-origin
// que causam erro 153 nos embeds do YouTube
header_remove('X-Frame-Options');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: compute-pressure=*, autoplay=*, encrypted-media=*, picture-in-picture=*, fullscreen=*');

// Autoload Composer
require_once ABSPATH . '/vendor/autoload.php';

// Carregar Configurações
require_once ABSPATH . '/app/config.php';

// Carregar variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(ABSPATH);
if (file_exists(ABSPATH . '/.env')) {
    $dotenv->load();
}

// Timezone
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'America/Sao_Paulo');

if (!function_exists('app_log_error')) {
    function app_log_error(string $message): void
    {
        $logDir = ABSPATH . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        @error_log($line, 3, $logDir . '/app.log');
    }
}

set_exception_handler(function (Throwable $e): void {
    app_log_error('Uncaught ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);

    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
    }
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }

    app_log_error('Fatal error: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
});

// Configurar erros baseado no ambiente
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Configurar sessão segura
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', '7200');

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', '1');
}

// Iniciar sessão se ainda não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerar ID de sessão periodicamente (a cada 30 min)
if (!isset($_SESSION['_last_regeneration'])) {
    $_SESSION['_last_regeneration'] = time();
} elseif (time() - $_SESSION['_last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['_last_regeneration'] = time();
}
