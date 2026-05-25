<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Security;
use App\Models\Setting;

/**
 * Controller de configuracoes.
 */
class SettingsController
{
    public function index(): void
    {
        Auth::require();

        $settings = [
            'webp_quality' => Setting::get('webp_quality', 80),
            'vapid_public_key' => \App\Services\VapidService::getKeys()['public'],
            'vapid_private_key' => \App\Services\VapidService::getKeys()['private'],
        ];

        view('admin.global_settings', [
            'settings' => $settings,
            'user' => Auth::user()
        ]);
    }

    public function update(): void
    {
        Auth::require();
        Security::requireCsrf();

        $webpQuality = (int) ($_POST['webp_quality'] ?? 80);
        $webpQuality = max(1, min(100, $webpQuality));
        Setting::set('webp_quality', $webpQuality);

        if (!empty(trim($_POST['vapid_public_key'] ?? ''))) {
            Setting::set('vapid_public_key', trim($_POST['vapid_public_key']));
        }
        if (!empty(trim($_POST['vapid_private_key'] ?? ''))) {
            Setting::set('vapid_private_key', trim($_POST['vapid_private_key']));
        }

        if (!empty($_FILES['pwa_icon']['tmp_name']) && is_uploaded_file($_FILES['pwa_icon']['tmp_name'])) {
            $this->handlePwaIconUpload($_FILES['pwa_icon']);
        }

        \App\Services\PageCache::clearAll();

        flash('success', 'Configuracoes gerais salvas com sucesso!');
        redirect(url('/settings'));
    }

    public function testEmail(): void
    {
        Auth::require();
        Security::requireCsrf();

        flash('error', 'O teste de email agora fica dentro das configuracoes de cada funil.');
        redirect(url('/settings'));
    }

    private function handlePwaIconUpload(array $file): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            return;
        }

        $mimeType = mime_content_type($file['tmp_name']);
        $validTypes = ['image/png', 'image/jpeg', 'image/webp'];
        if (!in_array($mimeType, $validTypes, true)) {
            return;
        }

        switch ($mimeType) {
            case 'image/png':
                $src = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/jpeg':
                $src = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/webp':
                $src = imagecreatefromwebp($file['tmp_name']);
                break;
            default:
                return;
        }

        if (!$src) {
            return;
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        $destDir = ABSPATH . '/public/assets/images';

        foreach ([192, 512] as $size) {
            $dest = imagecreatetruecolor($size, $size);
            imagesavealpha($dest, true);
            $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
            imagefill($dest, 0, 0, $transparent);

            $cropSize = min($srcW, $srcH);
            $cropX = (int) (($srcW - $cropSize) / 2);
            $cropY = (int) (($srcH - $cropSize) / 2);

            imagecopyresampled($dest, $src, 0, 0, $cropX, $cropY, $size, $size, $cropSize, $cropSize);
            imagepng($dest, $destDir . '/icon-' . $size . '.png');
            imagedestroy($dest);
        }

        imagedestroy($src);
    }

    public function database(): void
    {
        Auth::require();

        $dbService = new \App\Services\DatabaseService();
        $status = $dbService->getMigrations();
        $backups = $dbService->getBackups();

        view('admin.settings_database', [
            'status' => $status,
            'backups' => $backups,
            'user' => Auth::user()
        ]);
    }

    public function createBackup(): void
    {
        Auth::require();
        Security::requireCsrf();

        try {
            $dbService = new \App\Services\DatabaseService();
            $filename = $dbService->createBackup();
            flash('success', "Backup '{$filename}' criado com sucesso!");
        } catch (\Exception $e) {
            flash('error', 'Erro ao criar backup: ' . $e->getMessage());
        }

        redirect(url('/settings/database'));
    }

    public function runMigrations(): void
    {
        Auth::require();
        Security::requireCsrf();

        try {
            $dbService = new \App\Services\DatabaseService();
            $dbService->createBackup();

            $results = $dbService->runMigrations();

            if ($results['success']) {
                flash('success', "Banco de dados atualizado com sucesso! ({$results['executed']} aplicadas). Um backup de seguranca foi criado antes da atualizacao.");
            } else {
                $errorMsg = array_pop($results['log']);
                flash('error', 'A atualizacao falhou! ' . $errorMsg);
            }
        } catch (\Exception $e) {
            flash('error', 'Erro ao atualizar: ' . $e->getMessage());
        }

        redirect(url('/settings/database'));
    }

    public function restoreBackup(): void
    {
        Auth::require();
        Security::requireCsrf();

        $filename = $_POST['filename'] ?? '';
        if (empty($filename)) {
            flash('error', 'Arquivo nao especificado.');
            redirect(url('/settings/database'));
            return;
        }

        try {
            $dbService = new \App\Services\DatabaseService();
            $dbService->restoreBackup($filename);
            flash('success', "Banco de dados restaurado do arquivo '{$filename}' com sucesso!");
        } catch (\Exception $e) {
            flash('error', 'Erro ao restaurar: ' . $e->getMessage());
        }

        redirect(url('/settings/database'));
    }

    public function deleteBackup(): void
    {
        Auth::require();
        Security::requireCsrf();

        $filename = $_POST['filename'] ?? '';
        if (empty($filename)) {
            flash('error', 'Arquivo nao especificado.');
            redirect(url('/settings/database'));
            return;
        }

        try {
            $dbService = new \App\Services\DatabaseService();
            if ($dbService->deleteBackup($filename)) {
                flash('success', "Backup '{$filename}' excluido.");
            } else {
                flash('error', "Nao foi possivel excluir o backup '{$filename}'.");
            }
        } catch (\Exception $e) {
            flash('error', 'Erro ao excluir: ' . $e->getMessage());
        }

        redirect(url('/settings/database'));
    }

    public function downloadBackup(): void
    {
        Auth::require();

        $filename = $_GET['file'] ?? '';
        if (empty($filename)) {
            flash('error', 'Arquivo nao especificado.');
            redirect(url('/settings/database'));
            return;
        }

        $filepath = ABSPATH . '/storage/backups/' . basename($filename);
        if (!file_exists($filepath)) {
            flash('error', 'Arquivo de backup nao encontrado.');
            redirect(url('/settings/database'));
            return;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
}
