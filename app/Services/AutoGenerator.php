<?php

namespace App\Services;

use App\Models\Funnel;

/**
 * Service para regenerar HTML automaticamente
 */
class AutoGenerator
{
    /**
     * Regenera o HTML de um funil
     */
    public static function regenerate(int $funnelId): bool
    {
        $funnel = Funnel::find($funnelId);
        if (!$funnel) {
            return false;
        }

        $siteName = $funnel['site_name'] ?: $funnel['name'];
        $theme = $funnel['theme'];

        $generator = new HtmlGenerator($siteName, $theme, $funnelId);
        $generator->saveToFile();

        return true;
    }

    /**
     * Verifica se arquivo do funil existe
     */
    public static function fileExists(int $funnelId): bool
    {
        $filename = 'area-membros-funil-' . $funnelId . '.html';
        $filepath = ABSPATH . '/storage/generated/' . $filename;
        return file_exists($filepath);
    }

    /**
     * Retorna URL do arquivo
     */
    public static function getFileUrl(int $funnelId): string
    {
        $filename = 'area-membros-funil-' . $funnelId . '.html';
        return '/storage/generated/' . $filename;
    }
}
