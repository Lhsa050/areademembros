<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Compressor de Imagens para WebP
 */
class ImageCompressor
{
    /**
     * Converte imagem para WebP
     */
    public static function toWebp(string $sourcePath, ?string $targetPath = null): ?string
    {
        // Verifica se GD está disponível
        if (!extension_loaded('gd')) {
            return null;
        }

        // Verifica se arquivo existe
        if (!file_exists($sourcePath)) {
            return null;
        }

        // Detecta tipo da imagem
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return null;
        }

        $mimeType = $imageInfo['mime'];
        $image = null;

        // Carrega imagem baseado no tipo
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($sourcePath);
                // Preserva transparência
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($sourcePath);
                break;
            default:
                return null;
        }

        if (!$image) {
            return null;
        }

        // Define caminho de destino
        if (!$targetPath) {
            $pathInfo = pathinfo($sourcePath);
            $targetPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
        }

        // Obtém qualidade das configurações (com fallback se tabela não existir)
        try {
            $quality = (int) Setting::get('webp_quality', 80);
        } catch (\Exception $e) {
            $quality = 80;
        }
        $quality = max(1, min(100, $quality));

        // Salva como WebP
        $result = imagewebp($image, $targetPath, $quality);


        if ($result && file_exists($targetPath)) {
            return $targetPath;
        }

        return null;
    }

    /**
     * Comprime e converte arquivo de upload
     * Retorna o caminho relativo para salvar no banco
     */
    public static function compressUpload(array $file, string $uploadDir): ?string
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        // Cria diretório se não existir
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Gera nome único
        $filename = uniqid('img_') . '.webp';
        $targetPath = $uploadDir . $filename;

        // Debug Log ImageCompressor
        error_log(date('[Y-m-d H:i:s] ') . "ImageCompressor: Starting WebP conversion: " . $file['name'], 3, ABSPATH . '/debug_upload_log.txt');
        error_log(date('[Y-m-d H:i:s] ') . "Target WebP: " . $targetPath, 3, ABSPATH . '/debug_upload_log.txt');

        // Converte para WebP
        $result = self::toWebp($file['tmp_name'], $targetPath);

        if ($result && file_exists($targetPath)) {
            error_log(date('[Y-m-d H:i:s] ') . "ImageCompressor: WebP success. Size: " . filesize($targetPath), 3, ABSPATH . '/debug_upload_log.txt');
            return 'assets/uploads/' . $filename;
        } else {
             error_log(date('[Y-m-d H:i:s] ') . "ImageCompressor: WebP failed or file not found.", 3, ABSPATH . '/debug_upload_log.txt');
        }

        // Fallback: salva original se conversão falhar
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('img_') . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        error_log(date('[Y-m-d H:i:s] ') . "ImageCompressor: Try Fallback to original: " . $filename, 3, ABSPATH . '/debug_upload_log.txt');

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
             if (!file_exists($targetPath)) {
                error_log(date('[Y-m-d H:i:s] ') . "ImageCompressor: Fallback moved but NOT FOUND!", 3, ABSPATH . '/debug_upload_log.txt');
                return null;
            }
            error_log(date('[Y-m-d H:i:s] ') . "ImageCompressor: Fallback success.", 3, ABSPATH . '/debug_upload_log.txt');
            return 'assets/uploads/' . $filename;
        }

        return null;
    }
}
