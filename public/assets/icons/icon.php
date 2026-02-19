<?php
/**
 * Dynamic PWA Icon Generator
 * Generates PNG icons with the school initial "U" on a blue background.
 * Usage: icon.php?s=192  (size in px)
 *
 * Requires PHP GD extension.
 */

$size = max(16, min(1024, (int)($_GET['s'] ?? 192)));

if (!function_exists('imagecreatetruecolor')) {
    // Fallback: output a 1x1 blue PNG
    header('Content-Type: image/png');
    $img = imagecreate(1, 1);
    imagecolorallocate($img, 30, 64, 175);
    imagepng($img);
    imagedestroy($img);
    exit;
}

// Create image
$img = imagecreatetruecolor($size, $size);
imagesavealpha($img, true);

// Background color — primary-800 (#1e40af)
$bg = imagecolorallocate($img, 30, 64, 175);
$white = imagecolorallocate($img, 255, 255, 255);

// Fill rounded rect (approx with full fill; browsers handle masking)
imagefilledrectangle($img, 0, 0, $size - 1, $size - 1, $bg);

// Draw "U" letter centered
$fontSize = (int)($size * 0.5);
$fontFile = null;

// Try to use a TTF font if available
$possibleFonts = [
    __DIR__ . '/../../fonts/arial.ttf',
    'C:/Windows/Fonts/arial.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf',
];

foreach ($possibleFonts as $f) {
    if (file_exists($f)) {
        $fontFile = $f;
        break;
    }
}

if ($fontFile) {
    $bbox = imagettfbbox($fontSize, 0, $fontFile, 'U');
    $textW = $bbox[2] - $bbox[0];
    $textH = $bbox[1] - $bbox[7];
    $x = (int)(($size - $textW) / 2) - $bbox[0];
    $y = (int)(($size - $textH) / 2) - $bbox[7];
    imagettftext($img, $fontSize, 0, $x, $y, $white, $fontFile, 'U');
} else {
    // Fallback to built-in font
    $font = 5; // largest built-in font
    $charW = imagefontwidth($font);
    $charH = imagefontheight($font);
    $x = (int)(($size - $charW) / 2);
    $y = (int)(($size - $charH) / 2);
    imagestring($img, $font, $x, $y, 'U', $white);
}

// Output
header('Content-Type: image/png');
header('Cache-Control: public, max-age=31536000');
imagepng($img);
imagedestroy($img);
