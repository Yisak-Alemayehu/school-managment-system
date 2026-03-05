<?php
/**
 * Messaging — API: Upload Attachment (AJAX POST)
 */
csrf_protect();

$userId = auth_user_id();

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    json_response(['error' => 'No file uploaded or upload error.'], 400);
}

if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
    json_response(['error' => 'File is too large. Maximum size is 10MB.'], 400);
}

$result = handle_upload('file', 'messages', [
    'max_size'      => 10 * 1024 * 1024,
    'allowed_types' => ['image/jpeg','image/png','image/gif','image/webp','application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
]);

if (!$result || isset($result['error'])) {
    json_response(['error' => $result['error'] ?? 'Upload failed.'], 400);
}

json_response([
    'success'   => true,
    'file_name' => $_FILES['file']['name'],
    'file_path' => $result['path'],
    'file_size' => $_FILES['file']['size'],
    'mime_type' => $_FILES['file']['type'],
    'file_url'  => upload_url($result['path']),
]);
