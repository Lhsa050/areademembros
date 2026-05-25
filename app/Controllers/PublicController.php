<?php

namespace App\Controllers;

use App\Models\Funnel;

/**
 * Controller para páginas públicas do funil
 */
class PublicController
{
    /**
     * Exibe a área de membros pelo slug
     */
    public function show(string $slug): void
    {
        $funnel = Funnel::findBySlug($slug);
        
        if (!$funnel) {
            http_response_code(404);
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Não encontrado</title></head><body style="display:flex;justify-content:center;align-items:center;height:100vh;font-family:sans-serif;background:#f5f5f5;"><div style="text-align:center;"><h1 style="color:#666;">Página não encontrada</h1><p style="color:#999;">Este link não está disponível.</p></div></body></html>';
            exit;
        }

        $filename = 'area-membros-funil-' . $funnel['id'] . '.html';
        $filepath = ABSPATH . '/storage/generated/' . $filename;
        
        if (!file_exists($filepath)) {
            http_response_code(404);
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Não encontrado</title></head><body style="display:flex;justify-content:center;align-items:center;height:100vh;font-family:sans-serif;background:#f5f5f5;"><div style="text-align:center;"><h1 style="color:#666;">Página não encontrada</h1><p style="color:#999;">Este link ainda não está disponível.</p></div></body></html>';
            exit;
        }

        header('Content-Type: text/html; charset=UTF-8');
        readfile($filepath);
        exit;
    }
}
