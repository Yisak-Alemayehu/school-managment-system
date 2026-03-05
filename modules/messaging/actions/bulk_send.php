<?php
/**
 * Messaging — Send Bulk Message (Admin Only)
 */
csrf_protect();

$userId     = auth_user_id();
$targetType = input('target_type');
$classId    = input_int('class_id');
$sectionId  = input_int('section_id');
$teacherIds = input_array('teacher_ids');
$subject    = trim(input('subject'));
$body       = trim(input('body'));

// Validate
$errors = [];
if (empty($subject)) $errors['subject'] = 'Subject is required.';
if (empty($body))    $errors['body'] = 'Message is required.';
if (mb_strlen($body) > 5000) $errors['body'] = 'Message too long (max 5000 characters).';

if (!in_array($targetType, ['students', 'teachers', 'all'])) {
    set_flash('error', 'Invalid target type.');
    redirect('messaging', 'bulk');
}

if (!empty($errors)) {
    set_validation_errors($errors);
    set_old_input();
    redirect('messaging', 'bulk');
}

// Gather recipient user IDs
$recipientIds = [];

if ($targetType === 'students' || $targetType === 'all') {
    $studentWhere = ["s.status = 'active'", "s.deleted_at IS NULL", "s.user_id IS NOT NULL"];
    $studentParams = [];

    if ($targetType === 'students') {
        if ($classId) {
            $studentWhere[] = "e.class_id = ?";
            $studentParams[] = $classId;
        }
        if ($sectionId) {
            $studentWhere[] = "e.section_id = ?";
            $studentParams[] = $sectionId;
        }
    }

    $studentWhereClause = implode(' AND ', $studentWhere);
    $studentUsers = db_fetch_all("
        SELECT DISTINCT s.user_id AS id
          FROM students s
          LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
         WHERE $studentWhereClause
    ", $studentParams);

    foreach ($studentUsers as $su) {
        $recipientIds[$su['id']] = true;
    }
}

if ($targetType === 'teachers' || $targetType === 'all') {
    if ($targetType === 'teachers' && !empty($teacherIds)) {
        // Validate teacher IDs - only use integer IDs
        foreach ($teacherIds as $tid) {
            $tid = (int) $tid;
            if ($tid > 0) $recipientIds[$tid] = true;
        }
    } else {
        $teacherUsers = db_fetch_all("
            SELECT u.id FROM users u
              JOIN user_roles ur ON u.id = ur.user_id
              JOIN roles r ON ur.role_id = r.id AND r.slug = 'teacher'
             WHERE u.status = 'active' AND u.deleted_at IS NULL
        ");
        foreach ($teacherUsers as $tu) {
            $recipientIds[$tu['id']] = true;
        }
    }
}

if ($targetType === 'all') {
    // Also include parents (via guardians table or via role)
    $parentUsers = db_fetch_all("
        SELECT DISTINCT u.id FROM users u
         WHERE u.status = 'active' AND u.deleted_at IS NULL
           AND (
               u.id IN (SELECT g.user_id FROM guardians g WHERE g.user_id IS NOT NULL)
               OR u.id IN (SELECT ur.user_id FROM user_roles ur JOIN roles r ON ur.role_id = r.id AND r.slug = 'parent')
           )
    ");
    foreach ($parentUsers as $pu) {
        $recipientIds[$pu['id']] = true;
    }
}

// Remove sender from recipients
unset($recipientIds[$userId]);
$recipientIds = array_keys($recipientIds);

if (empty($recipientIds)) {
    $hint = '';
    if ($targetType === 'students') {
        $hint = ' Make sure students have login accounts created (via CSV import with credentials enabled or user management).';
    }
    set_flash('error', 'No recipients found matching your criteria.' . $hint);
    set_old_input();
    redirect('messaging', 'bulk');
}

db_begin();
try {
    // Create bulk conversation
    $convId = db_insert('msg_conversations', [
        'type'       => 'bulk',
        'subject'    => $subject,
        'created_by' => $userId,
    ]);

    // Add sender as participant
    db_insert('msg_conversation_participants', ['conversation_id' => $convId, 'user_id' => $userId]);

    // Add all recipients as participants
    foreach ($recipientIds as $rid) {
        db_insert('msg_conversation_participants', ['conversation_id' => $convId, 'user_id' => (int) $rid]);
    }

    // Insert message
    $messageId = db_insert('msg_messages', [
        'conversation_id' => $convId,
        'sender_id'       => $userId,
        'body'            => $body,
    ]);

    // Create message status for all recipients
    foreach ($recipientIds as $rid) {
        db_insert('msg_message_status', [
            'message_id' => $messageId,
            'user_id'    => (int) $rid,
            'status'     => 'sent',
        ]);
    }

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

            if (move_uploaded_file($tmpName, $targetDir . '/' . $filename)) {
                db_insert('msg_attachments', [
                    'message_id' => $messageId,
                    'file_name'  => $_FILES['attachments']['name'][$i],
                    'file_path'  => $subDir . '/' . $filename,
                    'file_size'  => $_FILES['attachments']['size'][$i],
                    'mime_type'  => $mime,
                ]);
            }
        }
    }

    db_commit();
    set_flash('success', 'Bulk message sent to ' . count($recipientIds) . ' recipient(s).');
    redirect('messaging', 'bulk-history');

} catch (Throwable $e) {
    db_rollback();
    error_log('Bulk send error: ' . $e->getMessage());
    set_flash('error', 'Failed to send bulk message.');
    set_old_input();
    redirect('messaging', 'bulk');
}
