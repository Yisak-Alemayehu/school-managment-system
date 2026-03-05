<?php
/**
 * Messaging — Send Message to Group
 */
csrf_protect();

$userId         = auth_user_id();
$groupId        = input_int('group_id');
$conversationId = input_int('conversation_id');
$body           = trim(input('body'));

if (!$groupId || !$conversationId) {
    set_flash('error', 'Invalid request.');
    redirect('messaging', 'groups');
}

// Verify membership
$membership = db_fetch_one("SELECT * FROM msg_group_members WHERE group_id = ? AND user_id = ?", [$groupId, $userId]);
if (!$membership) {
    set_flash('error', 'You are not a member of this group.');
    redirect('messaging', 'groups');
}

// Validate body
if ($body === '') {
    set_flash('error', 'Message cannot be empty.');
    redirect('messaging', 'group-detail', $groupId);
}
if (mb_strlen($body) > 5000) {
    set_flash('error', 'Message is too long (max 5000 characters).');
    redirect('messaging', 'group-detail', $groupId);
}

// Rate limiting for students
if (auth_has_role('student')) {
    $todayCount = db_fetch_value("
        SELECT COUNT(*) FROM msg_messages WHERE sender_id = ? AND created_at >= CURDATE()
    ", [$userId]);
    if ($todayCount >= 100) {
        set_flash('error', 'You have reached your daily message limit (100).');
        redirect('messaging', 'group-detail', $groupId);
    }
    $minuteCount = db_fetch_value("
        SELECT COUNT(*) FROM msg_messages WHERE sender_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ", [$userId]);
    if ($minuteCount >= 10) {
        set_flash('error', 'You are sending messages too fast. Please wait a moment.');
        redirect('messaging', 'group-detail', $groupId);
    }
}

db_begin();
try {
    // Insert message
    $msgId = db_insert('msg_messages', [
        'conversation_id' => $conversationId,
        'sender_id'       => $userId,
        'body'            => $body,
    ]);

    // Update conversation timestamp
    db_query("UPDATE msg_conversations SET updated_at = NOW() WHERE id = ?", [$conversationId]);

    // Restore soft-deleted participants
    db_query("UPDATE msg_conversation_participants SET is_deleted = 0 WHERE conversation_id = ? AND user_id = ?", [$conversationId, $userId]);

    // Create status records for all other participants
    $participants = db_fetch_all("
        SELECT user_id FROM msg_conversation_participants
         WHERE conversation_id = ? AND user_id != ? AND is_deleted = 0
    ", [$conversationId, $userId]);
    foreach ($participants as $p) {
        db_insert('msg_message_status', [
            'message_id' => $msgId,
            'user_id'    => $p['user_id'],
            'status'     => 'sent',
        ]);
    }

    // Handle attachments
    if (!empty($_FILES['attachments']['name'][0])) {
        $maxAttach = 5;
        $count = min(count($_FILES['attachments']['name']), $maxAttach);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($_FILES['attachments']['size'][$i] > 10 * 1024 * 1024) continue;

            $tmpFile = [
                'name'     => $_FILES['attachments']['name'][$i],
                'type'     => $_FILES['attachments']['type'][$i],
                'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                'error'    => $_FILES['attachments']['error'][$i],
                'size'     => $_FILES['attachments']['size'][$i],
            ];
            $_FILES['_msg_attach'] = $tmpFile;
            $result = handle_upload('_msg_attach', 'messages', [
                'max_size'      => 10 * 1024 * 1024,
                'allowed_types' => ['image/jpeg','image/png','image/gif','image/webp','application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            ]);
            if ($result) {
                $finalSize = $tmpFile['size'];
                // Compress images
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, UPLOAD_PATH . '/' . $result);
                finfo_close($finfo);
                $imageTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($mime, $imageTypes)) {
                    $compressed = compress_image(UPLOAD_PATH . '/' . $result, 1200, 1200, 75);
                    if ($compressed !== false) $finalSize = $compressed;
                }
                db_insert('msg_attachments', [
                    'message_id' => $msgId,
                    'file_name'  => $tmpFile['name'],
                    'file_path'  => $result,
                    'file_size'  => $finalSize,
                    'mime_type'  => $tmpFile['type'],
                ]);
            }
        }
    }

    db_commit();
} catch (Exception $ex) {
    db_rollback();
    set_flash('error', 'Failed to send message.');
}

redirect('messaging', 'group-detail', $groupId);
