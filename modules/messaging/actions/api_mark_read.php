<?php
/**
 * Messaging — API: Mark Messages as Read (AJAX POST)
 */
csrf_protect();

$userId         = auth_user_id();
$conversationId = input_int('conversation_id');

if (!$conversationId) {
    json_response(['error' => 'Missing conversation ID'], 400);
}

// Verify participant
$isParticipant = db_exists('msg_conversation_participants', 'conversation_id = ? AND user_id = ? AND is_deleted = 0', [$conversationId, $userId]);
if (!$isParticipant) {
    json_response(['error' => 'Access denied'], 403);
}

$updated = db_query("
    UPDATE msg_message_status SET status = 'read', read_at = NOW()
     WHERE user_id = ? AND status != 'read'
       AND message_id IN (SELECT id FROM msg_messages WHERE conversation_id = ?)
", [$userId, $conversationId]);

// Update last_read_at on participant record
db_query("UPDATE msg_conversation_participants SET last_read_at = NOW() WHERE conversation_id = ? AND user_id = ?", [$conversationId, $userId]);

json_response(['success' => true]);
