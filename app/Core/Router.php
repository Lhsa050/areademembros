<?php

namespace App\Core;

/**
 * Router - Sistema de rotas simples
 */
class Router
{
    private static array $routes = [];
    private static array $params = [];

    /**
     * Registra rota GET
     */
    public static function get(string $path, string $handler): void
    {
        self::$routes['GET'][$path] = $handler;
    }

    /**
     * Registra rota POST
     */
    public static function post(string $path, string $handler): void
    {
        self::$routes['POST'][$path] = $handler;
    }

    /**
     * Processa a requisição
     */
    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove /public se existir
        $uri = preg_replace('#^/public#', '', $uri);
        
        // Remove trailing slash exceto para /
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        // Busca rota correspondente
        $routes = self::$routes[$method] ?? [];
        
        foreach ($routes as $pattern => $handler) {
            if (self::match($pattern, $uri)) {
                self::execute($handler);
                return;
            }
        }

        // 404
        http_response_code(404);
        echo '<h1>404 - Página não encontrada</h1>';
    }

    /**
     * Verifica se URI casa com padrão
     */
    private static function match(string $pattern, string $uri): bool
    {
        // Converte {param} para regex
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            // Extrai apenas parâmetros nomeados
            self::$params = array_filter($matches, fn($key) => !is_numeric($key), ARRAY_FILTER_USE_KEY);
            return true;
        }

        return false;
    }

    /**
     * Executa o handler
     */
    private static function execute(string $handler): void
    {
        [$controller, $method] = explode('@', $handler);
        $controllerClass = "App\\Controllers\\{$controller}";

        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller não encontrado: {$controllerClass}");
        }

        $instance = new $controllerClass();

        if (!method_exists($instance, $method)) {
            throw new \Exception("Método não encontrado: {$controllerClass}@{$method}");
        }

        // Passa parâmetros da URL
        call_user_func_array([$instance, $method], self::$params);
    }

    /**
     * Obtém parâmetros da rota atual
     */
    public static function params(): array
    {
        return self::$params;
    }
}
