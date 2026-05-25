<?php

namespace App\Services;

use App\Models\Funnel;
use App\Models\Product;
use App\Models\AccessLevel;
use ZipArchive;

/**
 * Exportador de ZIP com HTML + assets
 */
class ZipExporter
{
    private int $funnelId;
    private string $htmlFilename;
    private string $htmlPath;
    private array $assets = [];

    public function __construct(int $funnelId)
    {
        $this->funnelId = $funnelId;
        $this->htmlFilename = 'area-membros-funil-' . $funnelId . '.html';
        $this->htmlPath = ABSPATH . '/storage/generated/' . $this->htmlFilename;
    }

    /**
     * Exporta ZIP completo
     */
    public function export(): ?string
    {
        // Busca funil
        $funnel = Funnel::find($this->funnelId);
        if (!$funnel) {
            return null;
        }
        
        // Regenera HTML com caminhos relativos para funcionar em qualquer servidor
        $generator = new HtmlGenerator(
            $funnel['site_name'] ?? $funnel['name'],
            $funnel['theme'],
            $this->funnelId
        );
        $generator->setUseRelativePaths(true);
        $htmlContent = $generator->generate();
        
        // Salva HTML temporário
        $tempHtmlPath = ABSPATH . '/storage/cache/zip-temp-' . $this->funnelId . '.html';
        file_put_contents($tempHtmlPath, $htmlContent);
        
        $slugName = $funnel['slug'] ?? 'funil-' . $this->funnelId;

        // Cria diretório temporário
        $tempDir = ABSPATH . '/storage/cache';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Coleta assets antes de criar ZIP
        $this->collectAssets();

        $zipFilename = $slugName . '.zip';
        $zipPath = $tempDir . '/' . $zipFilename;

        // Remove ZIP antigo se existir
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        // Cria ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            @unlink($tempHtmlPath);
            return null;
        }

        // Adiciona HTML com caminhos relativos
        $zip->addFile($tempHtmlPath, 'index.html');

        // Adiciona assets
        foreach ($this->assets as $asset) {
            $localPath = $asset['local_path'];
            $zipPath_internal = $asset['zip_path'];
            
            if (file_exists($localPath)) {
                $zip->addFile($localPath, $zipPath_internal);
            }
        }

        $zip->close();
        
        // Remove HTML temporário
        @unlink($tempHtmlPath);

        return $zipPath;
    }

    /**
     * Coleta todos os assets do funil (imagens e arquivos)
     */
    private function collectAssets(): void
    {
        $this->assets = [];
        $uploadDir = ABSPATH . '/public/assets/uploads/';

        // Busca produtos do funil
        $products = Product::getByFunnel($this->funnelId);

        foreach ($products as $product) {
            // Imagem do produto
            if (!empty($product['image']) && strpos($product['image'], 'assets/uploads/') !== false) {
                $filename = basename($product['image']);
                $localPath = $uploadDir . $filename;
                
                if (file_exists($localPath)) {
                    $this->assets[] = [
                        'local_path' => $localPath,
                        'zip_path' => 'assets/uploads/' . $filename
                    ];
                }
            }

            // Arquivo do produto (PDF)
            if (!empty($product['file']) && strpos($product['file'], 'assets/uploads/') !== false) {
                $filename = basename($product['file']);
                $localPath = $uploadDir . $filename;
                
                if (file_exists($localPath)) {
                    $this->assets[] = [
                        'local_path' => $localPath,
                        'zip_path' => 'assets/uploads/' . $filename
                    ];
                }
            }
            
            // Arquivos das aulas (para produtos de vídeo)
            if ($product['type'] === 'video') {
                $productFull = Product::findWithModules($product['id']);
                if (!empty($productFull['modules'])) {
                    foreach ($productFull['modules'] as $module) {
                        foreach ($module['lessons'] ?? [] as $lesson) {
                            if (!empty($lesson['file']) && strpos($lesson['file'], 'assets/uploads/') !== false) {
                                $filename = basename($lesson['file']);
                                $localPath = $uploadDir . $filename;
                                
                                if (file_exists($localPath)) {
                                    $this->assets[] = [
                                        'local_path' => $localPath,
                                        'zip_path' => 'assets/uploads/' . $filename
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        // Remove duplicatas
        $unique = [];
        foreach ($this->assets as $asset) {
            $key = $asset['local_path'];
            if (!isset($unique[$key])) {
                $unique[$key] = $asset;
            }
        }
        $this->assets = array_values($unique);
    }

    /**
     * Retorna nome do arquivo ZIP
     */
    public function getZipFilename(): string
    {
        $funnel = Funnel::find($this->funnelId);
        $slugName = $funnel['slug'] ?? 'funil-' . $this->funnelId;
        return $slugName . '.zip';
    }
}
