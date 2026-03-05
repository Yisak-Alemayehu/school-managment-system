<?php
/**
 * Messaging — Send Solo Message
 * Creates a new conversation or finds existing one, sends the message
 */
csrf_protect();

$userId      = auth_user_id();
$recipientId = input_int('recipient_id');
$subject     = trim(input('subject'));
$body        = trim(input('body'));

// Rate limiting for students: max 100/day, 10/min
if (auth_has_role('student')) {
    $dailyCount = (int) db_fetch_value(
        "SELECT COUNT(*) FROM msg_messages WHERE sender_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)",
        [$userId]
    );
    if ($dailyCount >= 100) {
        set_flash('error', 'Daily message limit reached (100 messages/day).');
        redirect('messaging', 'compose');
    }

    $minuteCount = (int) db_fetch_value(
        "SELECT COUNT(*) FROM msg_messages WHERE sender_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)",
        [$userId]
    );
    if ($minuteCount >= 10) {
        set_flash('error', 'Too many messages. Please wait a moment.');
        redirect('messaging', 'compose');
    }
}

// Validate
$errors = [];
if (!$recipientId) {
    $errors['recipient_id'] = 'Please select a recipient.';
}
if (empty($body)) {
    $errors['body'] = 'Message cannot be empty.';
}
if (mb_strlen($body) > 5000) {
    $errors['body'] = 'Message too long (max 5000 characters).';
}
if ($recipientId == $userId) {
    $errors['recipient_id'] = 'You cannot message yourself.';
}

// Verify recipient is a valid active user
if ($recipientId && empty($errors['recipient_id'])) {
    $recipient = db_fetch_one("SELECT id FROM users WHERE id = ? AND status = 'active' AND deleted_at IS NULL", [$recipientId]);
    if (!$recipient) {
        $errors['recipient_id'] = 'Recipient not found.';
    }
}

if (!empty($errors)) {
    set_validation_errors($errors);
    set_old_input();
    redirect('messaging', 'compose');
}

db_begin();
try {
    // Check for existing solo conversation between these two users
    $existingConvId = db_fetch_value("
        SELECT c.id
          FROM msg_conversations c
          JOIN msg_conversation_participants cp1 ON cp1.conversation_id = c.id AND cp1.user_id = ?
          JOIN msg_conversation_participants cp2 ON cp2.conversation_id = c.id AND cp2.user_id = ?
         WHERE c.type = 'solo'
         LIMIT 1
    ", [$userId, $recipientId]);

    if ($existingConvId) {
        $convId = (int) $existingConvId;
        // Restore if deleted
        db_query("UPDATE msg_conversation_participants SET is_deleted = 0 WHERE conversation_id = ? AND user_id = ?", [$convId, $userId]);
        db_query("UPDATE msg_conversation_participants SET is_deleted = 0 WHERE conversation_id = ? AND user_id = ?", [$convId, $recipientId]);
    } else {
        // Create new conversation
        $convId = db_insert('msg_conversations', [
            'type'       => 'solo',
            'subject'    => $subject ?: null,
            'created_by' => $userId,
        ]);

        db_insert('msg_conversation_participants', ['conversation_id' => $convId, 'user_id' => $userId]);
        db_insert('msg_conversation_participants', ['conversation_id' => $convId, 'user_id' => $recipientId]);
    }

    // Insert message
    $messageId = db_insert('msg_messages', [
        'conversation_id' => $convId,
        'sender_id'       => $userId,
        'body'            => $body,
    ]);

    // Create message status for recipient
    db_insert('msg_message_status', [
        'message_id' => $messageId,
        'user_id'    => $recipientId,
        'status'     => 'sent',
    ]);

    // Handle file attachments
    if (!empty($_FILES['attachments']['name'][0])) {
        $maxFiles = 5;
        $maxFileSize = 10 * 1024 * 1024; // 10 MB
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'application/msword',
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
    set_flash('success', 'Message sent successfully.');
    redirect('messaging', 'conversation', $convId);

} catch (Throwable $e) {
    db_rollback();
    error_log('Message send error: ' . $e->getMessage());
    set_flash('error', 'Failed to send message. Please try again.');
    redirect('messaging', 'compose');
}
