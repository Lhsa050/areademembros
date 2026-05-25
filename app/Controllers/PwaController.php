<?php
/**
 * Gera manifest.json dinâmico baseado no funil
 * Usado como endpoint: GET /m/{slug}/manifest.json
 */

namespace App\Controllers;

use App\Models\Funnel;

class PwaController
{
    public function manifest(string $slug): void
    {
        $funnel = Funnel::findBy('slug', $slug);
        if (!$funnel) {
            http_response_code(404);
            echo '{}';
            exit;
        }

        $appName = $funnel['site_name'] ?: $funnel['name'];
        $startUrl = url('/m/' . $slug . '/dashboard');

        // Cores baseadas no tema
        $themeColors = [
            'minimalista' => '#4F46E5',
            'elegante-escuro' => '#8A651F',
            'elegante-claro' => '#8A651F',
            'moderno-azul' => '#2563EB',
            'moderno-verde' => '#047857',
            'premium-dourado' => '#B45309',
        ];
        $themeColor = $themeColors[$funnel['theme'] ?? 'minimalista'] ?? '#4F46E5';

        $manifest = [
            'name' => $appName,
            'short_name' => mb_substr($appName, 0, 12),
            'description' => $funnel['description'] ?: 'Área de Membros',
            'start_url' => $startUrl,
            'scope' => url('/m/' . $slug . '/'),
            'display' => 'standalone',
            'orientation' => 'portrait',
            'theme_color' => $themeColor,
            'background_color' => '#f8fafc',
            'icons' => [
                [
                    'src' => file_exists(ABSPATH . '/public/assets/images/icon-192.png')
                        ? url('/assets/images/icon-192.png')
                        : url('/assets/images/pwa-icon.php?s=192'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => file_exists(ABSPATH . '/public/assets/images/icon-512.png')
                        ? url('/assets/images/icon-512.png')
                        : url('/assets/images/pwa-icon.php?s=512'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ]
            ]
        ];

        header('Content-Type: application/manifest+json');
        header('Cache-Control: public, max-age=86400');
        echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
