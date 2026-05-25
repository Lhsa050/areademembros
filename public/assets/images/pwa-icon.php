<?php
/**
 * Gera ícones PWA automaticamente usando GD
 * Acesse: /assets/images/icon-192.png ou /assets/images/icon-512.png
 */

require_once __DIR__ . '/../../bootstrap.php';

$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$size = (int) ($_GET['s'] ?? 192);
if (!in_array($size, [192, 512])) {
    $size = 192;
}

// Se o arquivo real já existe, serve-o
$realFile = __DIR__ . '/icon-' . $size . '.png';
if (file_exists($realFile)) {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=31536000');
    readfile($realFile);
    exit;
}

// Gera ícone dinâmico com GD
if (!function_exists('imagecreatetruecolor')) {
    // GD não disponível — serve um 1x1 transparent PNG
    header('Content-Type: image/png');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAABmJLR0QA/wD/AP+gvaeTAAAADUlEQVQI12NgYGBgAAAABQABXvMqOgAAAABJRU5ErkJggg==');
    exit;
}

$img = imagecreatetruecolor($size, $size);
imagesavealpha($img, true);

// Cor de fundo (brand blue)
$bg = imagecolorallocate($img, 59, 130, 246); // #3b82f6
imagefilledrectangle($img, 0, 0, $size, $size, $bg);

// Texto "M" no centro
$white = imagecolorallocate($img, 255, 255, 255);
$fontSize = (int) ($size * 0.45);
$fontPath = null;

// Tenta usar uma fonte do sistema, senão usa a built-in
$systemFonts = [
    'C:/Windows/Fonts/arial.ttf',
    'C:/Windows/Fonts/segoeui.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
];

foreach ($systemFonts as $f) {
    if (file_exists($f)) {
        $fontPath = $f;
        break;
    }
}

if ($fontPath) {
    $bbox = imagettfbbox($fontSize, 0, $fontPath, 'M');
    $textW = abs($bbox[2] - $bbox[0]);
    $textH = abs($bbox[7] - $bbox[1]);
    $x = (int) (($size - $textW) / 2);
    $y = (int) (($size + $textH) / 2);
    imagettftext($img, $fontSize, 0, $x, $y, $white, $fontPath, 'M');
} else {
    // Fallback: use built-in font (small but works)
    $builtinSize = 5; // Largest built-in font
    $textW = imagefontwidth($builtinSize);
    $textH = imagefontheight($builtinSize);
    $x = (int) (($size - $textW) / 2);
    $y = (int) (($size - $textH) / 2);
    imagestring($img, $builtinSize, $x, $y, 'M', $white);
}

// Salva para cache futuro
imagepng($img, $realFile);

// Serve
header('Content-Type: image/png');
header('Cache-Control: public, max-age=31536000');
imagepng($img);
imagedestroy($img);
