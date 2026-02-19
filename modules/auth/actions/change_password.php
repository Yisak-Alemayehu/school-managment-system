<?php
/**
 * Auth — Change Password Action (POST)
 */

auth_require();
csrf_protect();

$rules = [
    'current_password'      => 'required',
    'password'              => 'required|password|confirmed',
    'password_confirmation' => 'required',
];

$errors = validate($_POST, $rules);
if (!empty($errors)) {
    set_validation_errors($errors);
    redirect(url('auth', 'change-password'));
}

$user = auth_user();
$currentPassword = input('current_password');
$newPassword     = input('password');

// Verify current password
$dbUser = db_fetch_one("SELECT password_hash FROM users WHERE id = ?", [$user['id']]);
if (!$dbUser || !password_verify($currentPassword, $dbUser['password_hash'])) {
    set_flash('error', 'Current password is incorrect.');
    redirect(url('auth', 'change-password'));
}

// Update password
db_update('users', [
    'password_hash'          => password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]),
    'password_changed_at'    => date('Y-m-d H:i:s'),
    'force_password_change'  => 0,
], 'id = ?', [$user['id']]);

audit_log('password_changed', 'users', $user['id']);

set_flash('success', 'Password updated successfully.');
redirect(url('dashboard'));
