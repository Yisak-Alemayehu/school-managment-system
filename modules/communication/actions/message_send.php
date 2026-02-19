<?php
/**
 * Communication — Send Message
 */
verify_csrf();

$errors = validate($_POST, [
    'receiver_id' => 'required|numeric',
    'subject'     => 'required|max:255',
    'body'        => 'required',
]);

if ($errors) {
    set_flash('error', implode('<br>', $errors));
    redirect(url('communication', 'message-compose'));
}

$receiverId = (int)$_POST['receiver_id'];
$senderId   = current_user_id();

if ($receiverId === $senderId) {
    set_flash('error', 'You cannot send a message to yourself.');
    redirect(url('communication', 'message-compose'));
}

// Verify receiver exists
$receiver = db_fetch_one("SELECT id, full_name FROM users WHERE id = ? AND is_active = 1", [$receiverId]);
if (!$receiver) {
    set_flash('error', 'Recipient not found.');
    redirect(url('communication', 'message-compose'));
}

$msgId = db_insert('messages', [
    'sender_id'   => $senderId,
    'receiver_id' => $receiverId,
    'subject'     => trim($_POST['subject']),
    'body'        => trim($_POST['body']),
    'is_read'     => 0,
]);

// Create notification for receiver
db_insert('notifications', [
    'user_id' => $receiverId,
    'type'    => 'message',
    'title'   => 'New message from ' . current_user()['full_name'],
    'message' => trim($_POST['subject']),
    'link'    => '?module=communication&action=message-view&id=' . $msgId,
    'is_read' => 0,
]);

audit_log('message_send', 'messages', $msgId);
set_flash('success', 'Message sent to ' . e($receiver['full_name']) . '.');
redirect(url('communication', 'sent'));
