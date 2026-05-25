<?php

namespace App\Core;

/**
 * Classe de Segurança - CSRF e XSS
 */
class Security
{
    /**
     * Gera ou retorna token CSRF
     */
    public static function csrfToken(): string
    {
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    /**
     * Gera campo hidden com token CSRF
     */
    public static function csrfField(): string
    {
        $token = self::csrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . e($token) . '">';
    }

    /**
     * Verifica token CSRF
     */
    public static function verifyCsrf(): bool
    {
        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $sessionToken = $_SESSION['_csrf_token'] ?? '';

        if (empty($token) || empty($sessionToken)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Requer token CSRF válido
     */
    public static function requireCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !self::verifyCsrf()) {
            http_response_code(403);
            if (self::expectsJson()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Token de seguranca invalido. Recarregue a pagina e tente novamente.'
                ]);
                exit;
            }

            flash('error', 'Token de segurança inválido. Tente novamente.');
            back();
        }
    }

    private static function expectsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return str_contains($accept, 'application/json')
            || strtolower($requestedWith) === 'xmlhttprequest';
    }

    /**
     * Escapa string para prevenir XSS
     */
    public static function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escapa array inteiro
     */
    public static function clean(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = self::clean($value);
            } else {
                $clean[$key] = self::escape($value);
            }
        }
        return $clean;
    }

    /**
     * Sanitiza email
     */
    public static function email(string $value): string
    {
        return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
    }

    /**
     * Força inteiro
     */
    public static function int($value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Força float
     */
    public static function float($value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}
