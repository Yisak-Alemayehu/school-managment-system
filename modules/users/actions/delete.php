<?php
/**
 * Users — Soft Delete Action (POST)
 */

csrf_protect();

$id = route_id();

// Prevent self-deletion
if ($id == auth_user()['id']) {
    set_flash('error', 'You cannot delete your own account.');
    redirect(url('users'));
}

$userRecord = db_fetch_one("SELECT id, full_name FROM users WHERE id = ? AND deleted_at IS NULL", [$id]);
if (!$userRecord) {
    set_flash('error', 'User not found.');
    redirect(url('users'));
}

// Soft delete
db_soft_delete('users', 'id = ?', [$id]);

audit_log('user_deleted', 'users', $id, null, ['full_name' => $userRecord['full_name']]);

set_flash('success', 'User "' . $userRecord['full_name'] . '" has been deleted.');
redirect(url('users'));
