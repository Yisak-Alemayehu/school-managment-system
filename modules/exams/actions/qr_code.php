<?php
/**
 * Exams — QR Code Proxy
 * Fetches QR code image from goQR.me API server-side and serves it as PNG.
 * This avoids browser restrictions on external image loading.
 */

$data = trim($_GET['data'] ?? '');
if ($data === '') {
    http_response_code(400);
    exit;
}

$apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&format=png&margin=4&data=' . rawurlencode($data);

$img = false;

// Try cURL first
if (function_exists('curl_init')) {
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $img = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) {
        $img = false;
    }
}

// Fallback to file_get_contents
if ($img === false) {
    $ctx = stream_context_create([
        'http' => ['timeout' => 8],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $img = @file_get_contents($apiUrl, false, $ctx);
}

if ($img === false || strlen($img) < 100) {
    http_response_code(502);
    exit;
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
echo $img;
exit;
