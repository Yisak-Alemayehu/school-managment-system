<?php
/**
 * Messaging — Reply to a Conversation
 * Adds a message to an existing conversation
 */
csrf_protect();

$userId = auth_user_id();
$convId = input_int('conversation_id');
$body   = trim(input('body'));

if (!$convId || empty($body)) {
    set_flash('error', 'Invalid reply.');
    redirect('messaging', 'inbox');
}

if (mb_strlen($body) > 5000) {
    set_flash('error', 'Message too long (max 5000 characters).');
    redirect('messaging', 'conversation', $convId);
}

// Verify user is a participant
$participant = db_fetch_one("SELECT * FROM msg_conversation_participants WHERE conversation_id = ? AND user_id = ? AND is_deleted = 0", [$convId, $userId]);
if (!$participant) {
    set_flash('error', 'Conversation not found.');
    redirect('messaging', 'inbox');
}

$conversation = db_fetch_one("SELECT * FROM msg_conversations WHERE id = ?", [$convId]);
if (!$conversation) {
    set_flash('error', 'Conversation not found.');
    redirect('messaging', 'inbox');
}

// Bulk messages: only admin can reply
if ($conversation['type'] === 'bulk' && !(auth_is_super_admin() || auth_has_role('admin'))) {
    set_flash('error', 'You cannot reply to bulk messages.');
    redirect('messaging', 'conversation', $convId);
}

// Rate limiting for students
if (auth_has_role('student')) {
    $dailyCount = (int) db_fetch_value(
        "SELECT COUNT(*) FROM msg_messages WHERE sender_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)",
        [$userId]
    );
    if ($dailyCount >= 100) {
        set_flash('error', 'Daily message limit reached (100/day).');
        redirect('messaging', 'conversation', $convId);
    }

    $minuteCount = (int) db_fetch_value(
        "SELECT COUNT(*) FROM msg_messages WHERE sender_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)",
        [$userId]
    );
    if ($minuteCount >= 10) {
        set_flash('error', 'Too many messages. Please wait a moment.');
        redirect('messaging', 'conversation', $convId);
    }
}

db_begin();
try {
    // Insert message
    $messageId = db_insert('msg_messages', [
        'conversation_id' => $convId,
        'sender_id'       => $userId,
        'body'            => $body,
    ]);

    // Create message status for all other participants
    $otherParticipants = db_fetch_all("SELECT user_id FROM msg_conversation_participants WHERE conversation_id = ? AND user_id != ? AND is_deleted = 0", [$convId, $userId]);
    foreach ($otherParticipants as $op) {
        db_insert('msg_message_status', [
            'message_id' => $messageId,
            'user_id'    => $op['user_id'],
            'status'     => 'sent',
        ]);
    }

    // Restore deleted participants so they see the new message
    db_query("UPDATE msg_conversation_participants SET is_deleted = 0 WHERE conversation_id = ? AND is_deleted = 1", [$convId]);

    // Handle attachments
    if (!empty($_FILES['attachments']['name'][0])) {
        $maxFiles = 5;
        $maxFileSize = 10 * 1024 * 1024;
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        $fileCount = min($maxFiles, count($_FILES['attachments']['name']));
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($_FILES['attachments']['size'][$i] > $maxFileSize) continue;

            $tmpName = $_FILES['attachments']['tmp_name'][$i];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmpName);
            finfo_close($finfo);

            if (!in_array($mime, $allowedTypes)) continue;

            $ext = strtolower(pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION));
            $safeExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
            if (!in_array($ext, $safeExts)) $ext = 'bin';

            $filename = bin2hex(random_bytes(16)) . '.' . $ext;
            $subDir = 'messaging/' . date('Y/m');
            $targetDir = UPLOAD_PATH . '/' . $subDir;

            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

            $targetPath = $targetDir . '/' . $filename;
            if (move_uploaded_file($tmpName, $targetPath)) {
                $finalSize = $_FILES['attachments']['size'][$i];
                // Compress images
                $imageTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($mime, $imageTypes)) {
                    $compressed = compress_image($targetPath, 1200, 1200, 75);
                    if ($compressed !== false) $finalSize = $compressed;
                }
                db_insert('msg_attachments', [
                    'message_id' => $messageId,
                    'file_name'  => $_FILES['attachments']['name'][$i],
                    'file_path'  => $subDir . '/' . $filename,
                    'file_size'  => $finalSize,
                    'mime_type'  => $mime,
                ]);
            }
        }
    }

    // Update conversation timestamp
    db_update('msg_conversations', ['updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$convId]);

    db_commit();

} catch (Throwable $e) {
    db_rollback();
    error_log('Reply error: ' . $e->getMessage());
    set_flash('error', 'Failed to send reply.');
}

redirect('messaging', 'conversation', $convId);
