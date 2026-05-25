<?php
/**
 * Página de Login
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gerador de Área de Membros</title>
    
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
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 min-h-screen flex items-center justify-center p-4 font-sans">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 mb-4 shadow-lg shadow-blue-500/30">
                <i data-lucide="layout-dashboard" class="w-8 h-8 text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">Gerador de Área de Membros</h1>
            <p class="text-gray-400 mt-2">Faça login para continuar</p>
        </div>
        
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <?php if ($error = flash('error')): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-6 flex items-center gap-2 text-sm">
                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                <?= e($error) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success = flash('success')): ?>
            <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-lg mb-6 flex items-center gap-2 text-sm">
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                <?= e($success) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="<?= url('/login') ?>" class="space-y-6">
                <?= csrf_field() ?>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="mail" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?= e(old('email')) ?>"
                            class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            placeholder="seu@email.com"
                            required
                        >
                    </div>
                    <?php if (isset($errors['email'])): ?>
                    <p class="text-red-500 text-xs mt-1"><?= e($errors['email'][0]) ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Senha</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="lock" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            placeholder="••••••••"
                            required
                        >
                    </div>
                    <?php if (isset($errors['password'])): ?>
                    <p class="text-red-500 text-xs mt-1"><?= e($errors['password'][0]) ?></p>
                    <?php endif; ?>
                </div>
                
                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:from-blue-600 hover:to-blue-700 transition shadow-lg shadow-blue-500/30 flex items-center justify-center gap-2"
                >
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    Entrar
                </button>
            </form>
        </div>
        
        <p class="text-center text-gray-500 text-sm mt-6">
            &copy; <?= date('Y') ?> Gerador de Área de Membros
        </p>
    </div>
    
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
