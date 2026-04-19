<?php
/**
 * Academic Materials — Serve File Action
 * Streams PDF file inline or as download.
 */

$id = route_id();
$mode = input('mode'); // 'download' or 'inline' (default)

$material = db_fetch_one(
    "SELECT file_path, title, book_type FROM academic_materials WHERE id = ? AND deleted_at IS NULL AND status = 'active'",
    [$id]
);
if (!$material) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

$fullPath = UPLOAD_PATH . '/' . $material['file_path'];
if (!file_exists($fullPath)) {
    http_response_code(404);
    echo 'File not found on server.';
    exit;
}

// Sanitize filename for Content-Disposition
$safeTitle = preg_replace('/[^a-zA-Z0-9_\-\. ]/', '', $material['title']);
$fileName  = $safeTitle . '.pdf';

$disposition = ($mode === 'download') ? 'attachment' : 'inline';

header('Content-Type: application/pdf');
header('Content-Disposition: ' . $disposition . '; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');

readfile($fullPath);
exit;
