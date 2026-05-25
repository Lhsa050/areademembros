<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Security;
use App\Services\DatabaseService;
use App\Services\SystemBackupService;

/**
 * Controller de Atualização Automática via GitHub Releases
 */
class UpdateController
{
    private array $config;

    public function __construct()
    {
        $configPath = ABSPATH . '/update_config.php';
        if (file_exists($configPath)) {
            $this->config = require $configPath;
        } else {
            $this->config = [];
        }
    }

    /**
     * Página principal de atualização
     */
    public function index(): void
    {
        Auth::require();
        $user = Auth::user();
        if ($user['role'] !== 'super_admin') {
            flash('error', 'Acesso restrito a Super Admins.');
            redirect(url('/dashboard'));
            return;
        }

        require_once ABSPATH . '/version.php';
        $currentVersion = APP_VERSION;
        $configured = $this->isConfigured();
        $restorePoints = [];

        try {
            $restorePoints = (new SystemBackupService())->getRestorePoints();
        } catch (\Throwable $e) {
            $restorePoints = [];
        }

        view('admin.update', [
            'user' => $user,
            'currentVersion' => $currentVersion,
            'configured' => $configured,
            'restorePoints' => $restorePoints,
        ]);
    }

    /**
     * Verificar se há atualização (AJAX)
     */
    public function check(): void
    {
        Auth::require();
        $user = Auth::user();
        if ($user['role'] !== 'super_admin') {
            $this->jsonResponse(['error' => 'Acesso negado'], 403);
            return;
        }

        if (!$this->isConfigured()) {
            $this->jsonResponse(['error' => 'Sistema de atualização não configurado. Edite o arquivo update_config.php']);
            return;
        }

        require_once ABSPATH . '/version.php';
        $currentVersion = APP_VERSION;

        $result = $this->getLatestRelease();
        if (isset($result['error'])) {
            $this->jsonResponse(['error' => $result['error']]);
            return;
        }
        $release = $result;

        $latestVersion = ltrim($release['tag_name'], 'vV');
        $hasUpdate = version_compare($latestVersion, $currentVersion, '>');

        $this->jsonResponse([
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'has_update' => $hasUpdate,
            'release_name' => $release['name'] ?? $release['tag_name'],
            'release_body' => $release['body'] ?? '',
            'published_at' => $release['published_at'] ?? '',
            'download_url' => $release['zipball_url'] ?? '',
        ]);
    }

    /**
     * Executar atualização
     */
    public function apply(): void
    {
        Auth::require();
        Security::requireCsrf();
        $user = Auth::user();
        if ($user['role'] !== 'super_admin') {
            $this->jsonResponse(['error' => 'Acesso negado'], 403);
            return;
        }

        if (!$this->isConfigured()) {
            $this->jsonResponse(['error' => 'Sistema não configurado'], 400);
            return;
        }

        // Aumentar tempo de execução
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $result = $this->getLatestRelease();
        if (isset($result['error'])) {
            $this->jsonResponse(['error' => $result['error']], 500);
            return;
        }
        $release = $result;

        require_once ABSPATH . '/version.php';
        $latestVersion = ltrim($release['tag_name'], 'vV');
        if (!version_compare($latestVersion, APP_VERSION, '>')) {
            $this->jsonResponse(['error' => 'Já está na versão mais recente'], 400);
            return;
        }

        $steps = [];

        try {
            // Step 1: Baixar ZIP
            $steps[] = 'Baixando atualização...';
            $zipPath = sys_get_temp_dir() . '/update_' . time() . '.zip';
            $downloadUrl = $release['zipball_url'] ?? '';
            if ($downloadUrl === '') {
                $this->jsonResponse(['error' => 'Release sem arquivo ZIP para download'], 500);
                return;
            }

            $downloaded = $this->downloadRelease($downloadUrl, $zipPath);
            if (!$downloaded) {
                $this->jsonResponse(['error' => 'Erro ao baixar o arquivo da atualização'], 500);
                return;
            }
            $steps[] = '✅ Download concluído';

            // Step 2: Extrair ZIP
            $steps[] = 'Extraindo arquivos...';
            $extractDir = sys_get_temp_dir() . '/update_extract_' . time();
            $extracted = $this->extractZip($zipPath, $extractDir);
            if (!$extracted) {
                @unlink($zipPath);
                $this->jsonResponse(['error' => 'Erro ao extrair o arquivo ZIP'], 500);
                return;
            }
            $steps[] = '✅ Extração concluída';

            // Step 3: Encontrar o diretório raiz dentro do ZIP (GitHub adiciona uma pasta extra)
            $dirs = glob($extractDir . '/*', GLOB_ONLYDIR);
            $sourceDir = $dirs[0] ?? $extractDir;
            $protected = $this->protectedPaths();
            $steps[] = 'Diretório fonte: ' . basename($sourceDir);

            $steps[] = 'Criando ponto de restauracao...';
            $databaseBackup = $this->createDatabaseBackup(
                'update_db_v' . APP_VERSION . '_to_v' . $latestVersion
            );
            $steps[] = 'Backup do banco criado: ' . $databaseBackup;

            $systemBackupService = new SystemBackupService();
            $systemBackup = $systemBackupService->createBackup(APP_VERSION, $latestVersion, $protected);
            $steps[] = 'Backup do sistema criado: ' . $systemBackup['filename'];

            $restorePointId = 'restore_v' . APP_VERSION . '_to_v' . $latestVersion . '_' . date('Y_m_d_His');
            $systemBackupService->createRestorePoint([
                'id' => $restorePointId,
                'created_at' => time(),
                'from_version' => APP_VERSION,
                'target_version' => $latestVersion,
                'system_backup' => $systemBackup['filename'],
                'database_backup' => $databaseBackup,
                'system_size' => $systemBackup['size'],
            ]);
            $steps[] = 'Ponto de restauracao salvo';

            // Step 4: Preservar arquivos protegidos
            $steps[] = 'Preservando arquivos protegidos...';
            $backups = [];
            $preservedDirs = 0;
            foreach ($protected as $item) {
                $fullPath = ABSPATH . '/' . $item;
                if (is_file($fullPath)) {
                    $backupPath = sys_get_temp_dir() . '/backup_' . md5($item) . '_' . time();
                    copy($fullPath, $backupPath);
                    $backups[$item] = ['path' => $backupPath, 'is_dir' => false];
                    continue;
                }

                if (is_dir($fullPath)) {
                    $preservedDirs++;
                }
            }
            $steps[] = '✅ Protegidos preservados (' . count($backups) . ' arquivos, ' . $preservedDirs . ' diretorios)';

            // Step 5: Copiar novos arquivos
            $steps[] = 'Aplicando atualização...';
            $copied = $this->copyDirectory($sourceDir, ABSPATH, $protected);
            $steps[] = '✅ Arquivos atualizados (' . $copied . ' copiados)';

            // Step 6: Restaurar arquivos protegidos
            $steps[] = 'Restaurando arquivos protegidos...';
            foreach ($backups as $item => $backup) {
                $fullPath = ABSPATH . '/' . $item;
                // Garantir que o diretório pai existe
                $parentDir = dirname($fullPath);
                if (!is_dir($parentDir)) {
                    mkdir($parentDir, 0755, true);
                }
                copy($backup['path'], $fullPath);
            }
            $steps[] = '✅ Arquivos protegidos restaurados; diretorios protegidos nao foram sobrescritos';

            $steps[] = 'Executando migracoes do banco de dados...';
            $migrationResult = $this->runDatabaseMigrations();
            foreach ($migrationResult['log'] as $line) {
                $steps[] = $line;
            }

            $migrationError = null;
            if (!$migrationResult['success']) {
                $migrationError = 'Atualizacao aplicada, mas houve erro ao migrar o banco de dados. Verifique o log acima.';
                $steps[] = 'Erro nas migracoes do banco de dados';
            } elseif ($migrationResult['executed'] > 0) {
                $steps[] = 'Migracoes aplicadas: ' . $migrationResult['executed'];
            } else {
                $steps[] = 'Banco de dados ja estava atualizado';
            }

            if ($migrationError !== null) {
                @unlink($zipPath);
                $this->removeDirectory($extractDir);
                foreach ($backups as $backup) {
                    if ($backup['is_dir']) {
                        $this->removeDirectory($backup['path']);
                    } else {
                        @unlink($backup['path']);
                    }
                }
                $steps[] = 'Temporarios limpos';

                $this->jsonResponse([
                    'success' => false,
                    'error' => $migrationError,
                    'steps' => $steps,
                ], 500);
                return;
            }

            // Step 7: Limpar temporários
            @unlink($zipPath);
            $this->removeDirectory($extractDir);
            foreach ($backups as $backup) {
                if ($backup['is_dir']) {
                    $this->removeDirectory($backup['path']);
                } else {
                    @unlink($backup['path']);
                }
            }
            $steps[] = '✅ Temporários limpos';

            $steps[] = '🎉 Atualização concluída! Versão: v' . $latestVersion;

            $this->jsonResponse([
                'success' => true,
                'new_version' => $latestVersion,
                'steps' => $steps,
            ]);

        } catch (\Throwable $e) {
            // Limpar temporários em caso de erro
            if (isset($zipPath) && file_exists($zipPath)) @unlink($zipPath);
            if (isset($extractDir) && is_dir($extractDir)) $this->removeDirectory($extractDir);

            $steps[] = '❌ Erro: ' . $e->getMessage();
            $this->jsonResponse([
                'error' => $e->getMessage(),
                'steps' => $steps,
            ], 500);
        }
    }

    /**
     * Restaura um ponto de backup criado antes de atualizar.
     */
    public function restore(): void
    {
        Auth::require();
        Security::requireCsrf();

        $user = Auth::user();
        if ($user['role'] !== 'super_admin') {
            flash('error', 'Acesso negado.');
            redirect(url('/dashboard'));
            return;
        }

        $restoreId = trim($_POST['restore_id'] ?? '');
        if ($restoreId === '') {
            flash('error', 'Ponto de restauracao nao informado.');
            redirect(url('/update'));
            return;
        }

        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        try {
            require_once ABSPATH . '/version.php';

            $protected = $this->protectedPaths();
            $systemBackupService = new SystemBackupService();
            $point = $systemBackupService->getRestorePoint($restoreId);

            if (!$point) {
                flash('error', 'Ponto de restauracao nao encontrado.');
                redirect(url('/update'));
                return;
            }

            $safetySystem = $systemBackupService->createBackup(APP_VERSION, 'pre_restore', $protected);
            $safetyDatabase = $this->createDatabaseBackup('pre_restore_db_v' . APP_VERSION);
            $systemBackupService->createRestorePoint([
                'id' => 'pre_restore_v' . APP_VERSION . '_' . date('Y_m_d_His'),
                'created_at' => time(),
                'from_version' => APP_VERSION,
                'target_version' => 'pre_restore',
                'system_backup' => $safetySystem['filename'],
                'database_backup' => $safetyDatabase,
                'system_size' => $safetySystem['size'],
            ]);

            $systemBackupService->restoreBackup($point['system_backup'], $protected);

            $databaseRestored = false;
            if (isset($_POST['restore_database']) && !empty($point['database_backup'])) {
                $this->restoreDatabaseBackup($point['database_backup']);
                $databaseRestored = true;
            }

            $message = 'Sistema restaurado com sucesso.';
            if ($databaseRestored) {
                $message .= ' Banco de dados restaurado junto.';
            }
            $message .= ' Um backup de seguranca do estado anterior foi criado: ' . $safetySystem['filename'] . ' / ' . $safetyDatabase . '.';

            flash('success', $message);
        } catch (\Throwable $e) {
            flash('error', 'Erro ao restaurar: ' . $e->getMessage());
        }

        redirect(url('/update'));
    }

    /**
     * Consulta a API do GitHub para obter a última release
     * Retorna array com dados da release ou ['error' => 'mensagem']
     */
    private function getLatestRelease(): array
    {
        $owner = $this->config['owner'] ?? '';
        $repo = $this->config['repo'] ?? '';
        $token = $this->config['token'] ?? '';

        $url = "https://api.github.com/repos/{$owner}/{$repo}/releases?per_page=30";

        if (!function_exists('curl_init')) {
            return ['error' => 'Extensão cURL não instalada no servidor.'];
        }

        $headers = [
            'Accept: application/vnd.github+json',
            'User-Agent: MetodoGo-Updater/1.0',
            'X-GitHub-Api-Version: 2022-11-28',
        ];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            error_log("GitHub API curl error: {$curlError}");
            return ['error' => 'Erro de conexão com GitHub: ' . $curlError];
        }

        if ($httpCode === 401 || $httpCode === 403) {
            return ['error' => 'Token inválido ou sem permissão (HTTP ' . $httpCode . '). Verifique o token no update_config.php'];
        }

        if ($httpCode === 404) {
            $msg = "Repositório ou release não encontrado (HTTP 404). ";
            $msg .= "Verifique: owner='{$owner}', repo='{$repo}'. ";
            $msg .= "Você já criou uma Release no GitHub?";
            return ['error' => $msg];
        }

        if ($httpCode !== 200 || !$response) {
            error_log("GitHub API error: HTTP {$httpCode} - {$response}");
            return ['error' => 'Erro ao consultar GitHub (HTTP ' . $httpCode . '). Verifique os logs do servidor.'];
        }

        $releases = json_decode($response, true);
        if (!is_array($releases)) {
            return ['error' => 'Resposta inválida do GitHub. Verifique se há releases publicadas.'];
        }

        $latest = null;
        $latestVersion = null;
        foreach ($releases as $release) {
            if (!is_array($release) || !empty($release['draft']) || !empty($release['prerelease']) || empty($release['tag_name'])) {
                continue;
            }

            $version = ltrim((string) $release['tag_name'], 'vV');
            if (!preg_match('/^\d+(?:\.\d+){1,3}$/', $version)) {
                continue;
            }

            if ($latestVersion === null || version_compare($version, $latestVersion, '>')) {
                $latestVersion = $version;
                $latest = $release;
            }
        }

        if (!$latest) {
            return ['error' => 'Nenhuma release estável encontrada no GitHub.'];
        }

        return $latest;
    }

    /**
     * Baixa o ZIP da release
     */
    private function downloadRelease(string $url, string $destPath): bool
    {
        $token = $this->config['token'] ?? '';

        $headers = [
            'Accept: application/vnd.github+json',
            'User-Agent: MetodoGo-Updater/1.0',
            'X-GitHub-Api-Version: 2022-11-28',
        ];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        if (!function_exists('curl_init')) {
            return false;
        }

        $fp = @fopen($destPath, 'wb');
        if ($fp === false) {
            return false;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            fclose($fp);
            @unlink($destPath);
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        $success = $result && $httpCode === 200 && file_exists($destPath) && filesize($destPath) > 0;
        if (!$success) {
            @unlink($destPath);
        }

        return $success;
    }

    /**
     * Extrai ZIP
     */
    private function extractZip(string $zipPath, string $destDir): bool
    {
        if (!is_file($zipPath) || filesize($zipPath) <= 0) {
            return false;
        }

        if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
            throw new \RuntimeException('Nao foi possivel criar o diretorio temporario de extracao.');
        }

        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                return false;
            }

            $extracted = $zip->extractTo($destDir);
            $zip->close();

            return (bool) $extracted;
        }

        if (class_exists(\PharData::class)) {
            try {
                $archive = new \PharData($zipPath);
                $archive->extractTo($destDir, null, true);
                return true;
            } catch (\Throwable $e) {
                error_log('Falha ao extrair ZIP com PharData: ' . $e->getMessage());
            }
        }

        throw new \RuntimeException('Nenhum extrator ZIP disponivel no servidor. Habilite a extensao PHP zip (ZipArchive) ou a extensao Phar.');
    }

    /**
     * Copia diretório recursivamente, ignorando itens protegidos
     */
    private function copyDirectory(string $source, string $dest, array $protected = [], string $basePath = ''): int
    {
        $count = 0;
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $items = scandir($source);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $sourcePath = $source . '/' . $item;
            $destPath = $dest . '/' . $item;
            $relativePath = $basePath ? $basePath . '/' . $item : $item;

            // Verificar se é protegido
            if (in_array($relativePath, $protected) || in_array($item, $protected)) {
                continue;
            }

            if (is_dir($sourcePath)) {
                $count += $this->copyDirectory($sourcePath, $destPath, $protected, $relativePath);
            } else {
                // Garantir diretório pai
                $parentDir = dirname($destPath);
                if (!is_dir($parentDir)) {
                    mkdir($parentDir, 0755, true);
                }
                if (copy($sourcePath, $destPath)) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Remove diretório recursivamente
     */
    private function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) return false;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        return @rmdir($dir);
    }

    /**
     * Verifica se o sistema está configurado
     */
    private function createDatabaseBackup(string $prefix): string
    {
        $backupDir = ABSPATH . '/storage/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $db = Database::getInstance();
        $filename = $this->safeBackupName($prefix) . '_' . date('Y_m_d_His') . '.sql';
        $filepath = $backupDir . '/' . $filename;
        $handle = fopen($filepath, 'wb');

        if ($handle === false) {
            throw new \Exception('Nao foi possivel salvar o backup do banco de dados.');
        }

        try {
            $this->writeBackupLine($handle, "-- Backup gerado em " . date('Y-m-d H:i:s') . "\n");
            $this->writeBackupLine($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            $this->writeBackupLine($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
            $this->writeBackupLine($handle, "START TRANSACTION;\n\n");

            $stmt = $db->query('SHOW TABLES');
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $tableName = str_replace('`', '``', (string) $table);

                $stmt = $db->query("SHOW CREATE TABLE `{$tableName}`");
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                $this->writeBackupLine($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                $this->writeBackupLine($handle, $row['Create Table'] . ";\n\n");

                $select = $db->query("SELECT * FROM `{$tableName}`");
                $columnsSql = null;
                $inserted = 0;

                while ($dataRow = $select->fetch(\PDO::FETCH_ASSOC)) {
                    if ($columnsSql === null) {
                        $columns = array_map(function ($column) {
                            return '`' . str_replace('`', '``', (string) $column) . '`';
                        }, array_keys($dataRow));
                        $columnsSql = implode(', ', $columns);
                    }

                    $rowValues = array_map(function ($value) use ($db) {
                        if ($value === null) {
                            return 'NULL';
                        }

                        return $db->quote((string) $value);
                    }, array_values($dataRow));

                    $this->writeBackupLine(
                        $handle,
                        'INSERT INTO `' . $tableName . '` (' . $columnsSql . ') VALUES (' . implode(', ', $rowValues) . ");\n"
                    );
                    $inserted++;

                    if ($inserted % 100 === 0) {
                        fflush($handle);
                    }
                }

                $select->closeCursor();
                $this->writeBackupLine($handle, "\n");
            }

            $this->writeBackupLine($handle, "COMMIT;\n");
            $this->writeBackupLine($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
        }

        if (!is_file($filepath) || filesize($filepath) <= 0) {
            @unlink($filepath);
            throw new \Exception('Backup do banco de dados ficou vazio ou invalido.');
        }

        return $filename;
    }

    private function writeBackupLine($handle, string $line): void
    {
        if (fwrite($handle, $line) === false) {
            throw new \Exception('Nao foi possivel escrever o backup do banco de dados.');
        }
    }

    private function restoreDatabaseBackup(string $filename): void
    {
        $filepath = ABSPATH . '/storage/backups/' . basename($filename);
        if (!is_file($filepath)) {
            throw new \Exception('Backup do banco de dados nao encontrado.');
        }

        $sql = file_get_contents($filepath);
        if (!$sql) {
            throw new \Exception('O backup do banco de dados esta vazio.');
        }

        Database::getInstance()->exec($sql);
    }

    private function protectedPaths(): array
    {
        $defaults = [
            '.env',
            'uploads',
            'public/uploads',
            'public/assets/uploads',
            'storage',
            'update_config.php',
        ];

        $configured = $this->config['protected'] ?? [];

        return array_values(array_unique(array_filter(array_merge($defaults, $configured))));
    }

    private function safeBackupName(string $value): string
    {
        $value = preg_replace('/[^a-zA-Z0-9_.-]+/', '_', $value);
        return trim((string) $value, '._-') ?: 'backup';
    }

    private function runDatabaseMigrations(): array
    {
        $servicePath = ABSPATH . '/app/Services/DatabaseService.php';

        if (function_exists('opcache_invalidate') && file_exists($servicePath)) {
            @opcache_invalidate($servicePath, true);
        }

        if (!class_exists(DatabaseService::class) && file_exists($servicePath)) {
            require_once $servicePath;
        }

        if (!class_exists(DatabaseService::class)) {
            return [
                'success' => false,
                'log' => ['ERRO: Servico de migrations nao encontrado.'],
                'executed' => 0,
            ];
        }

        try {
            $databaseService = new DatabaseService();
            return $databaseService->runMigrations();
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'log' => ['ERRO: ' . $e->getMessage()],
                'executed' => 0,
            ];
        }
    }

    private function isConfigured(): bool
    {
        return !empty($this->config['owner'])
            && $this->config['owner'] !== 'SEU_USUARIO_GITHUB'
            && !empty($this->config['repo'])
            && $this->config['repo'] !== 'SEU_REPOSITORIO'
            && !empty($this->config['token'])
            && $this->config['token'] !== 'SEU_TOKEN_AQUI';
    }

    /**
     * Resposta JSON
     */
    private function jsonResponse(array $data, int $code = 200): void
    {
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: application/json');
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        echo $json !== false ? $json : '{"error":"Erro ao gerar resposta JSON"}';
        exit;
    }
}
