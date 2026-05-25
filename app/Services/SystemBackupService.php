<?php

namespace App\Services;

use Exception;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class SystemBackupService
{
    private string $backupDir;
    private string $systemDir;

    public function __construct()
    {
        $this->backupDir = ABSPATH . '/storage/backups';
        $this->systemDir = $this->backupDir . '/system';

        if (!is_dir($this->systemDir)) {
            mkdir($this->systemDir, 0755, true);
        }
    }

    public function createBackup(string $fromVersion, string $targetVersion, array $protected = []): array
    {
        $timestamp = date('Y_m_d_His');
        $safeFrom = $this->safeName($fromVersion);
        $safeTarget = $this->safeName($targetVersion);
        $baseName = "system_v{$safeFrom}_to_v{$safeTarget}_{$timestamp}";

        $excluded = array_merge($this->defaultProtectedPaths(), $protected, [
            '.git',
            'node_modules',
            'storage/backups',
        ]);

        $root = realpath(ABSPATH);
        if ($root === false) {
            throw new Exception('Diretorio raiz do sistema nao encontrado.');
        }

        if (!class_exists(ZipArchive::class)) {
            $filename = $baseName . '_files';
            $path = $this->systemDir . '/' . $filename;

            if (!is_dir($path) && !mkdir($path, 0755, true)) {
                throw new Exception('Nao foi possivel criar o backup de arquivos do sistema.');
            }

            $copied = $this->copyDirectory($root, $path, $excluded);
            $size = $this->directorySize($path);
            if ($copied === 0 && $size <= 0) {
                throw new Exception('Backup de arquivos do sistema ficou vazio ou invalido.');
            }

            return [
                'filename' => $filename,
                'path' => $path,
                'size' => $size,
            ];
        }

        $filename = $baseName . '.zip';
        $path = $this->systemDir . '/' . $filename;

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Nao foi possivel criar o backup ZIP do sistema.');
        }

        $directoryIterator = new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS);
        $filterIterator = new RecursiveCallbackFilterIterator(
            $directoryIterator,
            function ($item) use ($root, $excluded) {
                $relativePath = $this->relativePath($root, $item->getPathname());
                return $relativePath === '' || !$this->isProtectedPath($relativePath, $excluded);
            }
        );

        $iterator = new RecursiveIteratorIterator($filterIterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $item) {
            $absolutePath = $item->getPathname();
            $relativePath = $this->relativePath($root, $absolutePath);

            if ($relativePath === '' || $this->isProtectedPath($relativePath, $excluded)) {
                continue;
            }

            if ($item->isDir()) {
                $zip->addEmptyDir($relativePath);
                continue;
            }

            if ($item->isFile()) {
                $zip->addFile($absolutePath, $relativePath);
            }
        }

        $zip->close();

        if (!is_file($path) || filesize($path) <= 0) {
            throw new Exception('Backup ZIP do sistema ficou vazio ou invalido.');
        }

        return [
            'filename' => $filename,
            'path' => $path,
            'size' => filesize($path),
        ];
    }

    public function createRestorePoint(array $data): array
    {
        $id = $this->safeName($data['id'] ?? ('restore_' . date('Y_m_d_His')));

        $point = array_merge([
            'id' => $id,
            'created_at' => time(),
            'from_version' => '',
            'target_version' => '',
            'system_backup' => '',
            'database_backup' => '',
            'system_size' => 0,
        ], $data);

        $point['id'] = $id;
        $metadataPath = $this->systemDir . '/' . $id . '.json';

        $encoded = json_encode($point, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false || file_put_contents($metadataPath, $encoded) === false) {
            throw new Exception('Nao foi possivel salvar os metadados do ponto de restauracao.');
        }

        return $point;
    }

    public function getRestorePoints(): array
    {
        $points = [];
        $files = glob($this->systemDir . '/*.json') ?: [];

        foreach ($files as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            if (!is_array($data) || empty($data['id']) || empty($data['system_backup'])) {
                continue;
            }

            $systemPath = $this->systemDir . '/' . basename($data['system_backup']);
            $databasePath = $this->backupDir . '/' . basename($data['database_backup'] ?? '');

            $data['system_exists'] = is_file($systemPath) || is_dir($systemPath);
            $data['database_exists'] = !empty($data['database_backup']) && is_file($databasePath);
            $data['system_size'] = $data['system_exists']
                ? (is_dir($systemPath) ? $this->directorySize($systemPath) : filesize($systemPath))
                : ($data['system_size'] ?? 0);
            $points[] = $data;
        }

        usort($points, function ($a, $b) {
            return (int) ($b['created_at'] ?? 0) <=> (int) ($a['created_at'] ?? 0);
        });

        return $points;
    }

    public function getRestorePoint(string $id): ?array
    {
        $path = $this->systemDir . '/' . $this->safeName($id) . '.json';

        if (!is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);
        return is_array($data) ? $data : null;
    }

    public function restoreBackup(string $filename, array $protected = []): int
    {
        $path = $this->systemDir . '/' . basename($filename);
        if (!is_file($path) && !is_dir($path)) {
            throw new Exception('Backup do sistema nao encontrado.');
        }

        $excluded = array_merge($this->defaultProtectedPaths(), $protected, [
            '.git',
            'storage/backups',
        ]);

        if (is_dir($path)) {
            $this->removeFilesMissingFromBackup($path, ABSPATH, $excluded);
            return $this->copyDirectory($path, ABSPATH, $excluded);
        }

        if (!class_exists(ZipArchive::class)) {
            throw new Exception('Extensao ZipArchive nao instalada no servidor para restaurar este backup ZIP.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new Exception('Nao foi possivel abrir o backup ZIP do sistema.');
        }

        $this->validateZipEntries($zip);

        $extractDir = sys_get_temp_dir() . '/system_restore_' . time() . '_' . bin2hex(random_bytes(4));
        if (!mkdir($extractDir, 0755, true) && !is_dir($extractDir)) {
            $zip->close();
            throw new Exception('Nao foi possivel criar diretorio temporario de restauracao.');
        }

        if (!$zip->extractTo($extractDir)) {
            $zip->close();
            $this->removeDirectory($extractDir);
            throw new Exception('Nao foi possivel extrair o backup do sistema.');
        }
        $zip->close();

        try {
            $this->removeFilesMissingFromBackup($extractDir, ABSPATH, $excluded);
            $copied = $this->copyDirectory($extractDir, ABSPATH, $excluded);
        } finally {
            $this->removeDirectory($extractDir);
        }

        return $copied;
    }

    private function removeFilesMissingFromBackup(string $backupRoot, string $currentRoot, array $protected): void
    {
        $backupPaths = $this->collectBackupPaths($backupRoot);
        $root = realpath($currentRoot);

        if ($root === false) {
            throw new Exception('Diretorio atual do sistema nao encontrado.');
        }

        $directoryIterator = new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS);
        $filterIterator = new RecursiveCallbackFilterIterator(
            $directoryIterator,
            function ($item) use ($root, $protected) {
                $relativePath = $this->relativePath($root, $item->getPathname());
                return $relativePath === '' || !$this->isProtectedPath($relativePath, $protected);
            }
        );

        $iterator = new RecursiveIteratorIterator($filterIterator, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($iterator as $item) {
            $relativePath = $this->relativePath($root, $item->getPathname());
            if ($relativePath === '' || isset($backupPaths[$relativePath])) {
                continue;
            }

            if ($item->isDir()) {
                @rmdir($item->getPathname());
                continue;
            }

            if ($item->isFile()) {
                @unlink($item->getPathname());
            }
        }
    }

    private function collectBackupPaths(string $backupRoot): array
    {
        $paths = [];
        $root = realpath($backupRoot);

        if ($root === false) {
            return $paths;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = $this->relativePath($root, $item->getPathname());
            if ($relativePath !== '') {
                $paths[$relativePath] = true;
            }
        }

        return $paths;
    }

    private function validateZipEntries(ZipArchive $zip): void
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $normalized = str_replace('\\', '/', (string) $zip->getNameIndex($i));

            if (
                str_starts_with($normalized, '/') ||
                preg_match('/^[a-zA-Z]:\//', $normalized) ||
                str_contains($normalized, '../') ||
                str_contains($normalized, '/..') ||
                $normalized === '..'
            ) {
                throw new Exception('Backup ZIP contem caminho inseguro.');
            }
        }
    }

    private function copyDirectory(string $source, string $dest, array $protected = [], string $basePath = ''): int
    {
        $count = 0;
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $items = scandir($source);
        if ($items === false) {
            return 0;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $item;
            $destPath = $dest . '/' . $item;
            $relativePath = $basePath ? $basePath . '/' . $item : $item;

            if ($this->isProtectedPath($relativePath, $protected)) {
                continue;
            }

            if (is_dir($sourcePath)) {
                $count += $this->copyDirectory($sourcePath, $destPath, $protected, $relativePath);
                continue;
            }

            $parentDir = dirname($destPath);
            if (!is_dir($parentDir)) {
                mkdir($parentDir, 0755, true);
            }

            if (copy($sourcePath, $destPath)) {
                $count++;
            }
        }

        return $count;
    }

    private function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $items = scandir($dir);
        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        return @rmdir($dir);
    }

    private function directorySize(string $dir): int
    {
        if (!is_dir($dir)) {
            return 0;
        }

        $size = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $size += (int) $item->getSize();
            }
        }

        return $size;
    }

    private function relativePath(string $root, string $absolutePath): string
    {
        $relative = ltrim(substr($absolutePath, strlen($root)), DIRECTORY_SEPARATOR);
        return str_replace(DIRECTORY_SEPARATOR, '/', $relative);
    }

    private function isProtectedPath(string $relativePath, array $protected): bool
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');

        foreach ($protected as $path) {
            $path = trim(str_replace('\\', '/', (string) $path), '/');
            if ($path === '') {
                continue;
            }

            if ($relativePath === $path || str_starts_with($relativePath, $path . '/')) {
                return true;
            }
        }

        return false;
    }

    private function defaultProtectedPaths(): array
    {
        return [
            '.env',
            'uploads',
            'public/uploads',
            'public/assets/uploads',
            'storage',
            'update_config.php',
        ];
    }

    private function safeName(string $value): string
    {
        $value = preg_replace('/[^a-zA-Z0-9_.-]+/', '_', $value);
        return trim((string) $value, '._-') ?: 'backup';
    }
}
