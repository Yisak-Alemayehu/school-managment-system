<?php
/**
 * Serve uploaded files securely from outside the web root.
 * URL: /uploads.php?file=students/2026/02/abc123.jpg
 * This prevents direct directory listing and validates paths.
 */

// Bootstrap just the config for UPLOAD_PATH
require __DIR__ . '/../config/app.php';

$requestedFile = $_GET['file'] ?? '';

// Sanitize: no directory traversal
$requestedFile = str_replace(['..', "\0"], '', $requestedFile);
$requestedFile = ltrim($requestedFile, '/\\');

if (empty($requestedFile)) {
    http_response_code(400);
    exit('Bad request');
}

$fullPath = UPLOAD_PATH . '/' . $requestedFile;

if (!file_exists($fullPath) || !is_file($fullPath)) {
    http_response_code(404);
    exit('Not found');
}

// Only serve safe file types
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $fullPath);
finfo_close($finfo);

$allowedMimes = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
];

if (!in_array($mime, $allowedMimes)) {
    http_response_code(403);
    exit('Forbidden file type');
}

// Cache for 1 day
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: public, max-age=86400');
header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');

readfile($fullPath);
exit;
