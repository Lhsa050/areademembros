<?php
/**
 * Helpers Globais
 */

// Ícones SVG inline para área de membros
require_once __DIR__ . '/helpers/icons.php';

use App\Core\Security;

/**
 * Escapa HTML para prevenir XSS
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Obtém variável de ambiente
 */
function env(string $key, $default = null)
{
    return $_ENV[$key] ?? $default;
}

/**
 * Gera URL completa
 */
function url(string $path = ''): string
{
    $base = rtrim(env('APP_URL', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

/**
 * URL para assets
 */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Renderiza uma view
 */
function view(string $path, array $data = []): void
{
    extract($data);
    $viewPath = ABSPATH . '/views/' . str_replace('.', '/', $path) . '.php';
    
    if (!file_exists($viewPath)) {
        throw new Exception("View não encontrada: {$path}");
    }
    
    require $viewPath;
}

/**
 * Redireciona para URL
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Volta para página anterior
 */
function back(): void
{
    $referer = $_SERVER['HTTP_REFERER'] ?? url('/');
    redirect($referer);
}

/**
 * Resposta JSON
 */
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Define mensagem flash
 */
function flash(string $key, ?string $message = null)
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

/**
 * Obtém input anterior (para repopular forms)
 */
function old(string $key, $default = '')
{
    return $_SESSION['_old_input'][$key] ?? $default;
}

/**
 * Salva inputs para repopular após erro
 */
function save_old_input(): void
{
    $_SESSION['_old_input'] = $_POST;
}

/**
 * Limpa inputs antigos
 */
function clear_old_input(): void
{
    unset($_SESSION['_old_input']);
}

/**
 * Gera campo CSRF
 */
function csrf_field(): string
{
    return Security::csrfField();
}

/**
 * Obtém token CSRF
 */
function csrf_token(): string
{
    return Security::csrfToken();
}

/**
 * Verifica se usuário está logado
 */
function auth(): bool
{
    return \App\Core\Auth::check();
}

/**
 * Obtém usuário logado
 */
function user(): ?array
{
    return \App\Core\Auth::user();
}

/**
 * Debug e para execução
 */
function dd(...$vars): void
{
    echo '<pre style="background:#1e1e1e;color:#d4d4d4;padding:20px;margin:10px;border-radius:8px;font-family:monospace;overflow:auto;">';
    foreach ($vars as $var) {
        var_dump($var);
        echo "\n";
    }
    echo '</pre>';
    exit;
}

/**
 * Verifica se o Plyr está habilitado (Cacheado na requisição seria ideal, mas direto do DB por enquanto)
 */
function is_plyr_enabled(?int $funnelId = null): bool
{
    // Fallback para constante se existir, senão busca no banco (padrão true)
    if (defined('PLYR_ENABLED')) {
        return PLYR_ENABLED;
    }
    
    // Tenta buscar do banco se a classe Setting existir e estiver carregada
    if (class_exists('App\Models\Setting')) {
        try {
            return \App\Models\Setting::get('plyr_enabled', 'true', $funnelId ?: null) === 'true';
        } catch (\Throwable $e) {
            return true; // Padrão ligado em caso de erro
        }
    }
    
    return true;
}

/**
 * Traduz uma chave usando TranslationService
 */
function __(string $key): string
{
    if (class_exists(\App\Services\TranslationService::class)) {
        try {
            return \App\Services\TranslationService::get($key);
        } catch (\Throwable $e) {
            error_log('[translation] fallback for key ' . $key . ': ' . $e->getMessage());
        }
    }

    $fallback = [
        'hello' => 'Olá',
        'my_products' => 'Meus Produtos',
        'recommended_products' => 'Produtos Recomendados',
        'check_products' => 'Confira seus produtos abaixo',
        'no_products' => 'Nenhum produto disponível',
        'no_products_desc' => 'Ainda não há produtos cadastrados nesta área.',
        'access' => 'Acessar',
        'buy_access' => 'Comprar Acesso',
        'upgrade_now' => 'Garantir Oferta Agora',
        'locked' => 'Bloqueado',
        'releases_on' => 'Libera em',
        'video_course' => 'Curso em Vídeo',
        'pdf_material' => 'Material PDF',
        'download' => 'Baixar',
        'open_link' => 'Acessar',
    ];

    return $fallback[$key] ?? $key;
}
