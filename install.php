<?php
/**
 * Instalador Raiz — redireciona para public/install.php
 * Coloque este arquivo na raiz (public_html/) caso não consiga acessar public/install.php diretamente.
 */

// Detecta se o instalador público existe
$publicInstaller = __DIR__ . '/public/install.php';

if (file_exists($publicInstaller)) {
    // Inclui diretamente
    include $publicInstaller;
    exit;
}

echo '<h1>Erro: Arquivo public/install.php não encontrado.</h1>';
echo '<p>Verifique se os arquivos foram enviados corretamente.</p>';
