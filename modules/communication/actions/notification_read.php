<?php
/**
 * Communication — Mark Notification as Read
 */
$id     = input_int('id');
$userId = current_user_id();

if ($id) {
    db_update('notifications', ['is_read' => 1], 'id = ? AND user_id = ?', [$id, $userId]);
}

// Redirect to notification link if available
$notif = db_fetch_one("SELECT link FROM notifications WHERE id = ? AND user_id = ?", [$id, $userId]);
if ($notif && $notif['link']) {
    header('Location: ' . $notif['link']);
    exit;
}

redirect(url('communication', 'notifications'));
