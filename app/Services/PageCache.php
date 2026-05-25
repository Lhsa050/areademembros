<?php

namespace App\Services;

/**
 * Cache de páginas em arquivo — serve HTML como estático
 * 
 * Páginas de produto e aula são idênticas para todos os membros.
 * O cache armazena o HTML renderizado e serve direto do arquivo.
 */
class PageCache
{
    private static string $cacheDir = '';

    /**
     * Retorna o diretório de cache
     */
    private static function dir(): string
    {
        if (empty(self::$cacheDir)) {
            self::$cacheDir = ABSPATH . '/storage/cache/pages';
            if (!is_dir(self::$cacheDir)) {
                @mkdir(self::$cacheDir, 0755, true);
            }
        }
        return self::$cacheDir;
    }

    /**
     * Gera chave de cache para uma página
     */
    public static function key(string $type, int ...$ids): string
    {
        return $type . '_' . implode('_', $ids);
    }

    /**
     * Tenta servir do cache. Retorna true se serviu (e fez exit).
     * TTL padrão: 1 hora
     */
    public static function serve(string $cacheKey, int $ttlSeconds = 3600): bool
    {
        $file = self::dir() . '/' . $cacheKey . '.html';

        if (!file_exists($file)) {
            return false;
        }

        // Verifica TTL
        if ((time() - filemtime($file)) > $ttlSeconds) {
            @unlink($file);
            return false;
        }

        // Serve direto do arquivo — velocidade de HTML estático
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Cache: HIT');
        
        // Compressão gzip se suportada
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && str_contains($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
            $gzFile = $file . '.gz';
            if (file_exists($gzFile) && filemtime($gzFile) >= filemtime($file)) {
                header('Content-Encoding: gzip');
                header('Content-Length: ' . filesize($gzFile));
                readfile($gzFile);
                exit;
            }
        }

        readfile($file);
        exit;
    }

    /**
     * Salva HTML no cache (e versão gzip)
     */
    public static function save(string $cacheKey, string $html): void
    {
        $file = self::dir() . '/' . $cacheKey . '.html';
        file_put_contents($file, $html, LOCK_EX);

        // Salva versão gzip para servir ainda mais rápido
        $gzFile = $file . '.gz';
        $gz = gzopen($gzFile, 'wb9');
        if ($gz) {
            gzwrite($gz, $html);
            gzclose($gz);
        }
    }

    /**
     * Invalida cache de um produto (quando admin edita)
     */
    public static function invalidateProduct(int $productId): void
    {
        $dir = self::dir();
        $pattern = $dir . '/product_' . $productId . '*.html*';
        foreach (glob($pattern) as $file) {
            @unlink($file);
        }
    }

    /**
     * Invalida cache de uma aula (quando admin edita)
     */
    public static function invalidateLesson(int $lessonId): void
    {
        $dir = self::dir();
        // Aula cache: lesson_{productId}_{lessonId}
        $pattern = $dir . '/lesson_*_' . $lessonId . '.html*';
        foreach (glob($pattern) as $file) {
            @unlink($file);
        }
    }

    /**
     * Invalida todo o cache de um funil
     */
    public static function invalidateFunnel(int $funnelId): void
    {
        $dir = self::dir();
        $pattern = $dir . '/funnel_' . $funnelId . '_*.html*';
        foreach (glob($pattern) as $file) {
            @unlink($file);
        }
        // Também limpa produto e aula genericamente
        self::clearAll();
    }

    /**
     * Limpa todo o cache
     */
    public static function clearAll(): void
    {
        $dir = self::dir();
        foreach (glob($dir . '/*.html*') as $file) {
            @unlink($file);
        }
    }
}
