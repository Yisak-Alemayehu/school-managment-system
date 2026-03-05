<?php
/**
 * Messaging — API: Unread Message Count (AJAX GET)
 */
$userId = auth_user_id();

$count = db_fetch_value("
    SELECT COUNT(DISTINCT ms.message_id)
      FROM msg_message_status ms
      JOIN msg_messages m ON ms.message_id = m.id
      JOIN msg_conversation_participants cp ON m.conversation_id = cp.conversation_id AND cp.user_id = ? AND cp.is_deleted = 0
     WHERE ms.user_id = ? AND ms.status != 'read'
", [$userId, $userId]);

json_response(['unread_count' => (int) $count]);
