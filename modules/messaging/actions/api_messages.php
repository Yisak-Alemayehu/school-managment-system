<?php
/**
 * Messaging — API: Get Messages for a Conversation (AJAX)
 */
$userId         = auth_user_id();
$conversationId = input_int('id') ?: route_id();

if (!$conversationId) {
    json_response(['error' => 'Missing conversation ID'], 400);
}

// Verify participant
$isParticipant = db_exists('msg_conversation_participants', 'conversation_id = ? AND user_id = ? AND is_deleted = 0', [$conversationId, $userId]);
if (!$isParticipant) {
    json_response(['error' => 'Access denied'], 403);
}

$afterId = input_int('after'); // For polling new messages

$params = [$conversationId];
$afterFilter = '';
if ($afterId) {
    $afterFilter = 'AND m.id > ?';
    $params[] = $afterId;
}

$messages = db_fetch_all("
    SELECT m.id, m.body, m.sender_id, m.created_at,
           u.full_name AS sender_name
      FROM msg_messages m
      JOIN users u ON m.sender_id = u.id
     WHERE m.conversation_id = ? $afterFilter
     ORDER BY m.created_at ASC
     LIMIT 100
", $params);

// Get attachments
$msgIds = array_column($messages, 'id');
$attachments = [];
if (!empty($msgIds)) {
    $ph = implode(',', array_fill(0, count($msgIds), '?'));
    $allAtt = db_fetch_all("SELECT * FROM msg_attachments WHERE message_id IN ($ph)", $msgIds);
    foreach ($allAtt as $att) {
        $attachments[$att['message_id']][] = [
            'file_name' => $att['file_name'],
            'file_url'  => upload_url($att['file_path']),
            'file_size' => (int) $att['file_size'],
            'mime_type' => $att['mime_type'],
            'is_image'  => str_starts_with($att['mime_type'], 'image/'),
        ];
    }
}

$result = [];
foreach ($messages as $msg) {
    $result[] = [
        'id'          => (int) $msg['id'],
        'body'        => $msg['body'],
        'sender_id'   => (int) $msg['sender_id'],
        'sender_name' => $msg['sender_name'],
        'is_mine'     => $msg['sender_id'] == $userId,
        'created_at'  => $msg['created_at'],
        'time'        => format_datetime($msg['created_at'], 'g:i A'),
        'attachments' => $attachments[$msg['id']] ?? [],
    ];
}

json_response(['messages' => $result]);
