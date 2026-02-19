<?php
/**
 * Communication — Mark All Notifications as Read
 */
$userId = current_user_id();
db_update('notifications', ['is_read' => 1], 'user_id = ? AND is_read = 0', [$userId]);
set_flash('success', 'All notifications marked as read.');
redirect(url('communication', 'notifications'));
