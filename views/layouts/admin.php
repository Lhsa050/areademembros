<?php
/**
 * Layout Admin com suporte a Funil
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Painel') ?> - Área de Membros</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        .sidebar-link { transition: all 0.2s; }
        .sidebar-link:hover { background: rgba(255,255,255,0.1); }
        .sidebar-link.active { background: rgba(255,255,255,0.15); border-left: 3px solid #0ea5e9; }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased overflow-x-hidden">
    <?php $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'; ?>
    <div class="min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white flex flex-col fixed h-full">
            <div class="p-6 border-b border-gray-800">
                <h1 class="text-xl font-bold flex items-center gap-2">
                    <i data-lucide="layout-dashboard" class="w-6 h-6 text-brand-500"></i>
                    <span>Membros</span>
                </h1>
                <p class="text-xs text-gray-500 mt-1">Área de Membros</p>
            </div>
            
            <nav class="flex-1 py-4">
                <a href="<?= url('/dashboard') ?>" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm <?= str_contains($_SERVER['REQUEST_URI'], '/dashboard') || $_SERVER['REQUEST_URI'] === '/' ? 'active' : '' ?>">
                    <i data-lucide="home" class="w-4 h-4"></i>
                    Dashboard
                </a>
                
                <a href="<?= url('/funnels') ?>" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm <?= str_contains($_SERVER['REQUEST_URI'], '/funnels') || str_contains($_SERVER['REQUEST_URI'], '/members') ? 'active' : '' ?>">
                    <i data-lucide="git-branch" class="w-4 h-4"></i>
                    Funis
                </a>

                <a href="<?= url('/products') ?>" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm <?= preg_match('#/products(?:/|$)#', $currentPath) && !preg_match('#/funnels/[^/]+/products#', $currentPath) ? 'active' : '' ?>">
                    <i data-lucide="box" class="w-4 h-4"></i>
                    Produtos
                </a>

                <a href="<?= url('/support') ?>" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm <?= str_contains($_SERVER['REQUEST_URI'], '/support') ? 'active' : '' ?>">
                    <i data-lucide="messages-square" class="w-4 h-4"></i>
                    Suporte
                </a>

                <a href="<?= url('/fiscal') ?>" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm <?= str_contains($_SERVER['REQUEST_URI'], '/fiscal') ? 'active' : '' ?>">
                    <i data-lucide="receipt-text" class="w-4 h-4"></i>
                    Fiscal
                </a>
                
                <?php if ($user['role'] === 'super_admin'): ?>
                <div class="border-t border-gray-800 mt-4 pt-4">
                    <span class="px-6 text-xs text-gray-600 uppercase tracking-wider">Administração</span>
                    <a href="<?= url('/admins') ?>" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm mt-2 <?= str_contains($_SERVER['REQUEST_URI'], '/admins') ? 'active' : '' ?>">
                        <i data-lucide="shield" class="w-4 h-4"></i>
                        Administradores
                    </a>
                    <a href="<?= url('/settings') ?>" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm <?= str_contains($_SERVER['REQUEST_URI'], '/settings') ? 'active' : '' ?>">
                        <i data-lucide="settings" class="w-4 h-4"></i>
                        Configurações
                    </a>
                    <a href="<?= url('/update') ?>" class="sidebar-link flex items-center gap-3 px-6 py-3 text-sm <?= str_contains($_SERVER['REQUEST_URI'], '/update') ? 'active' : '' ?>">
                        <i data-lucide="download-cloud" class="w-4 h-4"></i>
                        Atualizações
                    </a>
                </div>
                <?php endif; ?>
            </nav>
            
            <div class="p-4 border-t border-gray-800">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-sm font-bold">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate"><?= e($user['name']) ?></p>
                        <p class="text-xs text-gray-500"><?= $user['role'] === 'super_admin' ? 'Super Admin' : 'Admin' ?></p>
                    </div>
                </div>
                <a href="<?= url('/logout') ?>" class="flex items-center gap-2 text-xs text-gray-400 hover:text-red-400 transition">
                    <i data-lucide="log-out" class="w-3 h-3"></i>
                    Sair
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="ml-64 min-w-0 w-[calc(100%-16rem)] max-w-[calc(100%-16rem)]">
            <!-- Top Bar -->
            <header class="bg-white border-b border-gray-200 px-8 py-4 sticky top-0 z-10">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800"><?= e($title ?? 'Painel') ?></h2>
                    <div class="text-sm text-gray-500">
                        <?= date('d/m/Y H:i') ?>
                    </div>
                </div>
            </header>
            
            <!-- Flash Messages -->
            <?php if ($success = flash('success')): ?>
            <div class="mx-8 mt-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                <?= e($success) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error = flash('error')): ?>
            <div class="mx-8 mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                <?= e($error) ?>
            </div>
            <?php endif; ?>
            
            <!-- Page Content -->
            <div class="p-8 max-w-full overflow-x-hidden">
                <?= $content ?>
            </div>
        </main>
    </div>
    
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
