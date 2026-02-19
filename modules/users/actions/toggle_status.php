<?php
/**
 * Users — Toggle Active Status (POST)
 */

csrf_protect();

$id = route_id();

if ($id == auth_user()['id']) {
    set_flash('error', 'You cannot deactivate your own account.');
    redirect(url('users'));
}

$userRecord = db_fetch_one("SELECT id, full_name, is_active FROM users WHERE id = ? AND deleted_at IS NULL", [$id]);
if (!$userRecord) {
    set_flash('error', 'User not found.');
    redirect(url('users'));
}

$newStatus = $userRecord['is_active'] ? 0 : 1;
db_update('users', ['is_active' => $newStatus], 'id = ?', [$id]);

$statusLabel = $newStatus ? 'activated' : 'deactivated';
audit_log("user_$statusLabel", 'users', $id);

set_flash('success', "User \"{$userRecord['full_name']}\" has been $statusLabel.");
redirect(url('users'));
