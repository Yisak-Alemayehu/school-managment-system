<?php
/**
 * Messaging — Delete Conversation (soft delete for user)
 */
csrf_protect();

$userId = auth_user_id();
$convId = input_int('conversation_id');

if (!$convId) {
    set_flash('error', 'Invalid conversation.');
    redirect('messaging', 'inbox');
}

// Mark as deleted for this user only
$affected = db_update('msg_conversation_participants', [
    'is_deleted' => 1,
], 'conversation_id = ? AND user_id = ?', [$convId, $userId]);

if ($affected) {
    set_flash('success', 'Conversation removed from your inbox.');
} else {
    set_flash('error', 'Conversation not found.');
}

redirect('messaging', 'inbox');
