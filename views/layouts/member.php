<?php \App\Services\TranslationService::init($funnel ?? []); ?>
<!DOCTYPE html>
<html lang="<?= e(\App\Services\TranslationService::lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Área de Membros') ?> — <?= e($appName ?? 'Membros') ?></title>
    <link rel="manifest" href="<?= url('/m/' . ($slug ?? '') . '/manifest.json') ?>">
    <?php
    $pwaThemeColors = [
        'minimalista' => '#3b82f6',
        'elegante-escuro' => '#d97706',
        'elegante-claro' => '#d97706',
        'moderno-azul' => '#06b6d4',
        'moderno-verde' => '#22c55e',
        'premium-dourado' => '#eab308',
    ];
    $pwaThemeColor = $pwaThemeColors[$funnel['theme'] ?? 'minimalista'] ?? '#3b82f6';
    ?>
    <meta name="theme-color" content="<?= $pwaThemeColor ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= e($appName ?? 'Membros') ?>">
    <link rel="apple-touch-icon" href="<?= url('/assets/images/icon-192.png') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php if (is_plyr_enabled((int) ($funnel['id'] ?? 0))): ?>
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">
    <?php endif; ?>
    <?php
    // Definição de Temas
    $themes = [
        'minimalista' => [ // Padrão (Violeta/Azul) - LIGHT
            '--brand-50' => '#eff6ff', '--brand-100' => '#dbeafe', '--brand-400' => '#60a5fa',
            '--brand-500' => '#3b82f6', '--brand-600' => '#2563eb', '--brand-700' => '#1d4ed8', '--brand-900' => '#1e3a8a',
            '--bg' => '#f8fafc', '--surface' => '#ffffff',
            '--gray-50' => '#f9fafb', '--gray-100' => '#f3f4f6', '--gray-200' => '#e5e7eb',
            '--gray-300' => '#d1d5db', '--gray-400' => '#9ca3af', '--gray-500' => '#6b7280',
            '--gray-600' => '#4b5563', '--gray-700' => '#374151', '--gray-800' => '#1f2937', '--gray-900' => '#111827'
        ],
        'elegante-escuro' => [ // Preto e Dourado - TRUE DARK
            '--brand-50' => '#451a03',   // Darkest Brown (used as bg tint)
            '--brand-100' => '#78350f',  // Dark Brown
            '--brand-400' => '#fbbf24',  // Gold
            '--brand-500' => '#d97706',  // Dark Gold
            '--brand-600' => '#b45309',  // Bronze
            '--brand-700' => '#92400e',  // Darker Bronze
            '--brand-900' => '#fef3c7',  // Light Gold (Accent for dark mode)
            '--bg' => '#020617',         // Almost Black
            '--surface' => '#1e293b',    // Dark Slate (Card Bg)
            // Inverted Grays for Dark Mode Text
            '--gray-50' => '#111827',    // Was light, now data-dark
            '--gray-100' => '#1f2937',
            '--gray-200' => '#374151',   // Borders (Dark Gray)
            '--gray-300' => '#4b5563',
            '--gray-400' => '#9ca3af',
            '--gray-500' => '#cbd5e1',   // Subtitles (Light Gray)
            '--gray-600' => '#e2e8f0',
            '--gray-700' => '#f1f5f9',
            '--gray-800' => '#f8fafc',   // Main Text (White-ish)
            '--gray-900' => '#ffffff'    // Headings (White)
        ],
        'elegante-claro' => [ // Branco e Dourado - LIGHT
            '--brand-50' => '#fffbeb', '--brand-100' => '#fef3c7', '--brand-400' => '#fbbf24',
            '--brand-500' => '#d97706', '--brand-600' => '#b45309', '--brand-700' => '#92400e', '--brand-900' => '#451a03',
            '--bg' => '#fafafa', '--surface' => '#ffffff',
            '--gray-50' => '#f9fafb', '--gray-100' => '#f3f4f6', '--gray-200' => '#e5e7eb',
            '--gray-300' => '#d1d5db', '--gray-400' => '#9ca3af', '--gray-500' => '#6b7280',
            '--gray-600' => '#4b5563', '--gray-700' => '#374151', '--gray-800' => '#1f2937', '--gray-900' => '#1c1917'
        ],
        'moderno-azul' => [ // Azul Tecnológico (Cyan/Blue) - LIGHT
            '--brand-50' => '#ecfeff', '--brand-100' => '#cffafe', '--brand-400' => '#22d3ee',
            '--brand-500' => '#06b6d4', '--brand-600' => '#0891b2', '--brand-700' => '#0e7490', '--brand-900' => '#164e63',
            '--bg' => '#f0f9ff', '--surface' => '#ffffff',
            '--gray-50' => '#f8fafc', '--gray-100' => '#f1f5f9', '--gray-200' => '#e2e8f0',
            '--gray-300' => '#cbd5e1', '--gray-400' => '#94a3b8', '--gray-500' => '#64748b',
            '--gray-600' => '#475569', '--gray-700' => '#334155', '--gray-800' => '#1e293b', '--gray-900' => '#0f172a'
        ],
        'moderno-verde' => [ // Verde Natureza - LIGHT
            '--brand-50' => '#f0fdf4', '--brand-100' => '#dcfce7', '--brand-400' => '#4ade80',
            '--brand-500' => '#22c55e', '--brand-600' => '#16a34a', '--brand-700' => '#15803d', '--brand-900' => '#14532d',
            '--bg' => '#f0fdfa', '--surface' => '#ffffff',
            '--gray-50' => '#f8fafc', '--gray-100' => '#f1f5f9', '--gray-200' => '#e2e8f0',
            '--gray-300' => '#cbd5e1', '--gray-400' => '#94a3b8', '--gray-500' => '#64748b',
            '--gray-600' => '#475569', '--gray-700' => '#334155', '--gray-800' => '#1e293b', '--gray-900' => '#064e3b'
        ],
        'premium-dourado' => [ // Similar ao elegante-claro mas mais amarelo - LIGHT
            '--brand-50' => '#fffbeb', '--brand-100' => '#fef3c7', '--brand-400' => '#facc15',
            '--brand-500' => '#eab308', '--brand-600' => '#ca8a04', '--brand-700' => '#a16207', '--brand-900' => '#451a03',
            '--bg' => '#fffaf0', '--surface' => '#ffffff',
            '--gray-50' => '#fafaf9', '--gray-100' => '#f5f5f4', '--gray-200' => '#e7e5e4',
            '--gray-300' => '#d6d3d1', '--gray-400' => '#a8a29e', '--gray-500' => '#78716c',
            '--gray-600' => '#57534e', '--gray-700' => '#44403c', '--gray-800' => '#292524', '--gray-900' => '#451a03'
        ],
    ];

    $currentTheme = $themes[$funnel['theme'] ?? 'minimalista'] ?? $themes['minimalista'];
    ?>
    <style>
        /* Base styles */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        /* Default vars (will be overridden by theme) */
        :root {
            --gray-50: #f9fafb; --gray-100: #f3f4f6; --gray-200: #e5e7eb; --gray-300: #d1d5db;
            --gray-400: #9ca3af; --gray-500: #6b7280; --gray-600: #4b5563; --gray-700: #374151;
            --gray-800: #1f2937; --gray-900: #111827;
            /* Default brand colors (fallback) */
            --brand-50: #eff6ff; --brand-500: #3b82f6; --brand-600: #2563eb; --brand-700: #1d4ed8; --brand-900: #1e3a8a;
            --bg: #f3f4f6; --surface: #ffffff;
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--gray-800); line-height: 1.6; min-height: 100vh; display: flex; flex-direction: column; }
        
        /* ... existing styles ... */
    </style>

    <!-- Theme Overrides -->
    <style>
        :root {
            <?php foreach ($currentTheme as $key => $value): ?>
            <?= $key ?>: <?= $value ?> !important;
            <?php endforeach; ?>
        }
    </style>

    <style>
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--gray-800); line-height: 1.6; min-height: 100vh; display: flex; flex-direction: column; }
        
        /* Header */
        .member-header {
            background: var(--surface);
            border-bottom: 1px solid var(--gray-200);
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .member-header .logo { font-size: 1.125rem; font-weight: 700; color: var(--gray-900); text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .member-header .logo-icon { width: 32px; height: 32px; background: linear-gradient(135deg, var(--brand-500), var(--brand-700)); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; }
        .member-header .user-area { display: flex; align-items: center; gap: 16px; }
        .member-header .member-user-summary { text-align: right; line-height: 1.2; display: block; }
        .member-header .user-name { font-size: 0.875rem; font-weight: 500; color: var(--gray-600); }
        .member-header .support-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; font-size: 0.8125rem; font-weight: 700; color: var(--brand-600); background: var(--brand-50); border: 1px solid var(--brand-100); border-radius: 8px; text-decoration: none; transition: all 0.2s; }
        .member-header .support-btn:hover { background: var(--brand-100); color: var(--brand-700); }
        .member-header .logout-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-size: 0.8125rem; font-weight: 500; color: var(--gray-500); background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 8px; text-decoration: none; transition: all 0.2s; }
        .member-header .logout-btn:hover { background: var(--gray-100); color: var(--gray-700); }

        /* Main */
        .member-main { max-width: 1200px; margin: 0 auto; padding: 32px 24px; flex: 1; width: 100%; }

        /* Footer */
        .member-footer {
            background: var(--surface);
            border-top: 1px solid var(--gray-200);
            padding: 24px;
            text-align: center;
            color: var(--gray-500);
            font-size: 0.875rem;
            margin-top: auto;
        }

        /* Cards Grid & Product Card */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; }
        .product-card { background: var(--surface); border-radius: 16px; border: 1px solid var(--gray-200); overflow: hidden; transition: all 0.3s; position: relative; display: block; text-decoration: none; }
        .product-card:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(0,0,0,0.08); }
        .product-card .card-image { position: relative; width: 100%; aspect-ratio: 4/3; background: linear-gradient(135deg, var(--gray-100), var(--gray-200)); overflow: hidden; }
        .product-card .card-image img { width: 100%; height: 100%; object-fit: cover; }
        .product-card .card-image .placeholder-icon { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: var(--gray-300); }
        
        /* Locked overlay - Reduced blur as requested */
        .product-card.locked .card-image::after { content: ''; position: absolute; inset: 0; background: rgba(0,0,0,0.3); backdrop-filter: blur(0px); transition: backdrop-filter 0.3s; }
        .product-card.locked:hover .card-image::after { backdrop-filter: blur(2px); }
        
        /* Lock icon - Top Right */
        .product-card.locked .lock-icon { position: absolute; top: 12px; right: 12px; z-index: 2; width: 32px; height: 32px; background: rgba(0,0,0,0.6); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; backdrop-filter: blur(4px); }

        .product-card .card-body { padding: 20px; }
        .product-card .card-title { font-size: 1rem; font-weight: 600; color: var(--gray-900); margin-bottom: 6px; line-height: 1.4; }
        .product-card .card-desc { font-size: 0.8125rem; color: var(--gray-500); margin-bottom: 16px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .product-card .card-type { display: inline-flex; align-items: center; gap: 4px; font-size: 0.75rem; font-weight: 500; padding: 4px 10px; border-radius: 20px; margin-bottom: 12px; }
        .type-video { background: var(--gray-200); color: var(--gray-700); font-weight: 600; }
        .type-pdf { background: var(--gray-200); color: var(--gray-700); font-weight: 600; }
        
        /* Theme-based type colors */
        .unlocked .type-video { background: var(--brand-100); color: var(--brand-900); border: 1px solid var(--brand-400); }
        .unlocked .type-pdf { background: #fde68a; color: #92400e; } /* Amber */

        /* Buttons */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 20px; font-size: 0.875rem; font-weight: 600; border-radius: 10px; text-decoration: none; transition: all 0.2s; width: 100%; border: none; cursor: pointer; }
        .btn-primary { background: linear-gradient(135deg, var(--brand-500), var(--brand-600)); color: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .btn-primary:hover { background: linear-gradient(135deg, var(--brand-600), var(--brand-700)); box-shadow: 0 4px 16px rgba(0,0,0,0.15); }
        .btn-checkout { background: linear-gradient(135deg, #10b981, #059669); color: white; box-shadow: 0 2px 8px rgba(16,185,129,0.3); }
        .btn-checkout:hover { background: linear-gradient(135deg, #059669, #047857); }
        .btn-secondary { background: var(--gray-100); color: var(--gray-700); border: 1px solid var(--gray-200); }
        .btn-secondary:hover { background: var(--gray-200); }
        .btn-sm { padding: 6px 12px; font-size: 0.8125rem; }

        /* Video Player (iframe padrão) */
        .tutor-video-player { margin-bottom: 24px; border-radius: 12px; overflow: hidden; background: #000; }
        .tutor-ratio { position: relative; overflow: hidden; }
        .tutor-ratio-16x9 { padding-bottom: 56.25%; }
        .tutor-ratio iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; }

        <?php if (is_plyr_enabled((int) ($funnel['id'] ?? 0))): ?>
        /* Plyr Customization - Estratégia de Stretching para esconder branding do YouTube */
        
        /* Container principal */
        .video-container {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        
        /* Plyr preenche o container */
        .video-container .plyr {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
        }

        /* Todos os wrappers internos do Plyr */
        .video-container .plyr__video-embed,
        .video-container .plyr__video-wrapper {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            padding-bottom: 0 !important;
            overflow: hidden !important;
        }

        /* Mágica: Estica iframe 200% e posiciona -50% para cortar barras do YouTube */
        .plyr--youtube iframe,
        .plyr--youtube .plyr__video-embed iframe {
            position: absolute !important;
            top: -50% !important;
            left: 0 !important;
            width: 100% !important;
            height: 200% !important;
        }

        /* Poster quando pausado/parado para cobrir branding restante */
        .plyr--youtube.plyr--paused.plyr__poster-enabled .plyr__poster,
        .plyr--youtube.plyr--stopped.plyr__poster-enabled .plyr__poster {
            opacity: 1 !important;
        }

        /* Theming Plyr com cores da marca */
        .plyr {
            --plyr-color-main: var(--brand-500);
            --plyr-video-background: #000;
        }
        .plyr__control--overlaid {
            background: var(--brand-500) !important;
        }
        .plyr__control--overlaid svg {
            fill: white;
        }
        <?php endif; ?>

        /* Flash messages */
        .flash-message { padding: 12px 16px; border-radius: 10px; margin-bottom: 24px; font-size: 0.875rem; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .flash-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .flash-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

        /* Page title */
        .page-title { font-size: 1.5rem; font-weight: 700; color: var(--gray-900); margin-bottom: 8px; }
        .page-subtitle { font-size: 0.9375rem; color: var(--gray-500); margin-bottom: 32px; }

        /* Breadcrumb */
        .breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 0.8125rem; color: var(--gray-400); margin-bottom: 24px; }
        .breadcrumb a { color: var(--gray-500); text-decoration: none; }
        .breadcrumb a:hover { color: var(--brand-500); }

        /* Module Accordion */
        .module-accordion { background: var(--surface); border: 1px solid var(--gray-200); border-radius: 12px; margin-bottom: 12px; overflow: hidden; }
        .module-header { padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: background 0.2s; }
        .module-header:hover { background: var(--gray-50); }
        .module-header h3 { font-size: 0.9375rem; font-weight: 600; color: var(--gray-800); display: flex; align-items: center; gap: 10px; }
        .module-count { font-size: 0.75rem; color: var(--gray-400); font-weight: 400; }
        .module-body { display: none; border-top: 1px solid var(--gray-100); }
        .module-accordion.open .module-body { display: block; }

        /* Lesson Link */
        .lesson-link { display: flex; align-items: center; gap: 12px; padding: 12px 20px 12px 48px; text-decoration: none; transition: background 0.2s; border-bottom: 1px solid var(--gray-50); }
        .lesson-link:hover { background: var(--brand-50); }
        .lesson-link.active { background: var(--brand-50); border-left: 3px solid var(--brand-500); }
        .lesson-link .lesson-icon { color: var(--gray-400); flex-shrink: 0; }
        .lesson-link.active .lesson-icon { color: var(--brand-500); }
        .lesson-link .lesson-title { font-size: 0.875rem; color: var(--gray-700); font-weight: 500; }
        .lesson-link.active .lesson-title { color: var(--brand-600); }

        /* Video Player (Plyr) */
        .video-container { position: relative; width: 100%; padding-bottom: 56.25%; background: #000; border-radius: 12px; overflow: hidden; margin-bottom: 24px; }
        .video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; }

        /* Video Player (iframe puro - sem Plyr) */
        .video-wrapper { position: relative; width: 100%; padding-bottom: 56.25%; background: #000; border-radius: 12px; overflow: hidden; margin-bottom: 24px; }
        .video-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; }

        /* PDF Viewer */
        .pdf-container { background: var(--surface); border: 1px solid var(--gray-200); border-radius: 12px; padding: 32px; text-align: center; margin-bottom: 24px; }

        /* Responsive */
        @media (max-width: 768px) {
            .member-main { padding: 16px; }
            .products-grid { grid-template-columns: 1fr; gap: 16px; }
            .member-header { padding: 0 16px; }
            .member-header .member-user-summary { display: none; }
            .member-header .user-name { display: none; }
            .member-header .support-btn span { display: none; }
            .member-header .support-btn { padding: 8px 10px; }
            .lesson-layout { flex-direction: column; }
            .lesson-sidebar { width: 100%; max-height: 300px; overflow-y: auto; }
        }
        
        /* Layout helpers */
        .lesson-layout { display: flex; gap: 24px; }
        .lesson-content { flex: 1; min-width: 0; }
        .lesson-sidebar { width: 340px; flex-shrink: 0; }
        .lesson-sidebar .module-accordion { margin-bottom: 8px; }
        .lesson-nav { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-top: 24px; }
        .empty-state { text-align: center; padding: 64px 24px; color: var(--gray-400); }
        .empty-state i { margin-bottom: 16px; }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="member-header">
        <a href="<?= url('/m/' . ($slug ?? '') . '/dashboard') ?>" class="logo">
            <div class="logo-icon">
                <?= icon('play', 'width:18px;height:18px;') ?>
            </div>
            <?= e($appName ?? 'Área de Membros') ?>
        </a>
        <?php if (isset($member)): ?>
        <div class="user-area">
            <div class="member-user-summary">
                <span class="user-name" id="header-user-name" style="display:block;color:var(--gray-800);font-weight:600;">Carregando...</span>
                <span id="header-user-email" style="font-size:0.75rem;color:var(--gray-500);"></span>
            </div>
            <button id="pwa-install-btn" style="display:none; align-items:center; gap:6px; padding:6px 14px; border-radius:8px; border:1px solid var(--brand-500); background:transparent; color:var(--brand-500); font-size:0.8rem; font-weight:600; cursor:pointer; transition:all 0.2s;" onmouseover="this.style.background='var(--brand-500)';this.style.color='#fff'" onmouseout="this.style.background='transparent';this.style.color='var(--brand-500)'">
                <?= icon('download', 'width:14px;height:14px;') ?>
                Instalar App
            </button>
            <a href="<?= url('/m/' . ($slug ?? '') . '/support') ?>" class="support-btn" title="Suporte">
                <?= icon('mail', 'width:16px;height:16px;') ?>
                <span>Suporte</span>
            </a>
            <a href="<?= url('/m/' . ($slug ?? '') . '/logout') ?>" class="logout-btn">
                <?= icon('log-out', 'width:16px;height:16px;') ?>
                <?= __('logout') ?>
            </a>
        </div>
        <?php endif; ?>
    </header>

    <!-- Main -->
    <main class="member-main">
        <?php $flashError = flash('error'); if ($flashError): ?>
            <div class="flash-message flash-error">
                <?= icon('alert-circle', 'width:18px;height:18px;') ?>
                <?= e($flashError) ?>
            </div>
        <?php endif; ?>
        
        <?php $flashSuccess = flash('success'); if ($flashSuccess): ?>
            <div class="flash-message flash-success">
                <?= icon('check-circle', 'width:18px;height:18px;') ?>
                <?= e($flashSuccess) ?>
            </div>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </main>

    <footer class="member-footer">
        <p>&copy; <?= date('Y') ?> <?= e($appName ?? 'Todos os direitos reservados') ?>.</p>
    </footer>

    <?php if (is_plyr_enabled((int) ($funnel['id'] ?? 0))): ?>
    <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
    <?php endif; ?>

    <script>
        // Fetch User Data (Client-side implementation to support page caching)
        document.addEventListener('DOMContentLoaded', function() {
            const slug = <?= json_encode($slug ?? '', JSON_UNESCAPED_SLASHES) ?>;
            const memberApiBase = <?= json_encode(url('/m/'), JSON_UNESCAPED_SLASHES) ?>;
            if (!slug) return;

            // Only fetch if elements exist
            const nameEl = document.getElementById('header-user-name');
            const emailEl = document.getElementById('header-user-email');
            
            if (nameEl && emailEl) {
                fetch(memberApiBase + encodeURIComponent(slug) + '/api/me')
                    .then(response => {
                        if (response.ok) return response.json();
                        throw new Error('Unauthorized');
                    })
                    .then(data => {
                        nameEl.textContent = data.name;
                        emailEl.textContent = data.email;
                    })
                    .catch(() => {
                        // If auth fails (session expired), optional redirect or silent fail
                        console.log('User session not active');
                    });
            }
        });
    </script>

    <!-- PWA: Service Worker & Install -->
    <script>
    (function() {
        // Register service worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('<?= url('/sw.js') ?>', { scope: '<?= url('/m/' . ($slug ?? '') . '/') ?>' })
                .catch(e => console.log('SW registration failed:', e));
        }

        // Install prompt (Android/Chrome/Edge)
        let deferredPrompt = null;
        const installBtn = document.getElementById('pwa-install-btn');
        if (!installBtn) return;

        window.addEventListener('beforeinstallprompt', e => {
            e.preventDefault();
            deferredPrompt = e;
            installBtn.style.display = 'flex';
        });

        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const result = await deferredPrompt.userChoice;
                if (result.outcome === 'accepted') installBtn.style.display = 'none';
                deferredPrompt = null;
            } else {
                // iOS Safari: mostrar instrução
                const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
                if (isIOS) {
                    alert('Para instalar:\n1. Toque no botão de Compartilhar ⎋\n2. Selecione "Adicionar à Tela Inicial"');
                }
            }
        });

        // iOS: mostrar botão mesmo sem beforeinstallprompt
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone;
        if (isIOS && !isStandalone) {
            installBtn.style.display = 'flex';
        }

        // Esconde se já instalado
        window.addEventListener('appinstalled', () => { installBtn.style.display = 'none'; });
    })();
    </script>

    <!-- Push Notifications: Subscription -->
    <?php
    $vapidPubKey = \App\Services\VapidService::getPublicKey();
    ?>
    <?php if (!empty($vapidPubKey)): ?>
    <script>
    (function() {
        if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) return;
        if (Notification.permission === 'denied') return;
        
        const slug = <?= json_encode($slug ?? '', JSON_UNESCAPED_SLASHES) ?>;
        const vapidPublicKey = <?= json_encode($vapidPubKey, JSON_UNESCAPED_SLASHES) ?>;
        const pushSubscribeBase = <?= json_encode(url('/m/'), JSON_UNESCAPED_SLASHES) ?>;
        const promptKey = 'push_prompt_dismissed_' + slug;
        const subKey = 'push_subscribed_' + slug;

        if (localStorage.getItem(subKey)) return;

        // Custom Modal UI Injection
        const modalHtml = `
            <div id="push-prompt" style="display:none; position:fixed; bottom:20px; right:20px; left:20px; z-index:9999;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
                <div style="background:#fff; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.1); border:1px solid #e2e8f0; padding:20px; position:relative; overflow:hidden;">
                    <button onclick="dismissPushPrompt()" style="position:absolute; top:12px; right:12px; background:none; border:none; color:#94a3b8; cursor:pointer; padding:4px;">✕</button>
                    <div style="display:flex; gap:16px; align-items:flex-start;">
                        <div style="width:40px; height:40px; border-radius:10px; background:#eff6ff; color:#3b82f6; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path></svg>
                        </div>
                        <div>
                            <h3 style="margin:0 0 6px 0; font-size:15px; font-weight:600; color:#1e293b;">Ativar Notificações</h3>
                            <p style="margin:0 0 16px 0; font-size:13px; color:#64748b; line-height:1.4;">Receba avisos de novas aulas, materiais e atualizações importantes.</p>
                            <div style="display:flex; gap:8px;">
                                <button onclick="subscribePush()" style="flex:1; background:#3b82f6; color:#fff; border:none; padding:8px 12px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; transition:all 0.2s;">Sim, ativar</button>
                                <button onclick="dismissPushPrompt()" style="flex:1; background:#f1f5f9; color:#475569; border:none; padding:8px 12px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; transition:all 0.2s;">Agora não</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Fix Desktop width
        if (window.innerWidth >= 768) {
            const el = document.getElementById('push-prompt');
            if(el) {
                el.style.left = 'auto';
                el.style.width = '350px';
            }
        }

        window.dismissPushPrompt = function() {
            document.getElementById('push-prompt').style.display = 'none';
            localStorage.setItem(promptKey, Date.now() + (7 * 24 * 60 * 60 * 1000));
        };

        window.subscribePush = async function() {
            try {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    if (permission === 'denied') alert('Você bloqueou as notificações. Libere nas configurações do seu navegador para receber avisos.');
                    dismissPushPrompt();
                    return;
                }

                const reg = await navigator.serviceWorker.ready;
                
                function urlBase64ToUint8Array(base64String) {
                    const padding = '='.repeat((4 - base64String.length % 4) % 4);
                    const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
                    const rawData = window.atob(base64);
                    const outputArray = new Uint8Array(rawData.length);
                    for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
                    return outputArray;
                }

                const sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
                });

                const subJson = sub.toJSON();
                const response = await fetch(pushSubscribeBase + encodeURIComponent(slug) + '/api/push/subscribe', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        endpoint: subJson.endpoint,
                        keys: { p256dh: subJson.keys.p256dh, auth: subJson.keys.auth }
                    })
                });

                if (response.ok) {
                    localStorage.setItem(subKey, '1');
                    document.getElementById('push-prompt').style.display = 'none';
                } else {
                    alert('Falha interna ao registrar notificação no servidor. Tente novamente.');
                }
            } catch(e) {
                console.log('Push error:', e);
                alert('Erro ao ativar notificações: ' + e.message);
            }
        };

        window.addEventListener('load', function() {
            const dismissedUntil = localStorage.getItem(promptKey);
            if (dismissedUntil && Date.now() < parseInt(dismissedUntil)) return;

            const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
            const isSecure = window.location.protocol === 'https:';
            
            if (isSecure || isLocalhost) {
                setTimeout(() => {
                    const p = document.getElementById('push-prompt');
                    if(p) p.style.display = 'block';
                }, 2500);
            }
        });
    })();
    </script>
    <?php endif; ?>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
    (function() {
        if (window.lucide && window.lucide.createIcons) {
            window.lucide.createIcons();
        }
    })();
    </script>
</body>
</html>
