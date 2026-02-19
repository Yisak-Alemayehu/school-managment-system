<?php
/**
 * Users — Update Action (POST)
 */

csrf_protect();

$id = route_id();
$userRecord = db_fetch_one("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL", [$id]);
if (!$userRecord) {
    set_flash('error', 'User not found.');
    redirect(url('users'));
}

$rules = [
    'full_name' => 'required|min:2|max:100',
    'email'     => 'required|email|max:100',
    'phone'     => 'nullable|phone',
    'role_id'   => 'required|integer',
];

// Only validate password if provided
if (!empty($_POST['password'])) {
    $rules['password'] = 'password|confirmed';
    $rules['password_confirmation'] = 'required';
}

$errors = validate($_POST, $rules);

// Check email uniqueness excluding current user
if (empty($errors['email'])) {
    $exists = db_fetch_one("SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL", [input('email'), $id]);
    if ($exists) {
        $errors['email'] = 'This email is already in use.';
    }
}

if (!empty($errors)) {
    set_validation_errors($errors);
    set_old_input($_POST);
    redirect(url('users', 'edit', $id));
}

$updateData = [
    'full_name'             => input('full_name'),
    'email'                 => input('email'),
    'phone'                 => input('phone'),
    'is_active'             => isset($_POST['is_active']) ? 1 : 0,
    'force_password_change' => isset($_POST['force_password_change']) ? 1 : 0,
];

if (!empty($_POST['password'])) {
    $updateData['password_hash']      = password_hash(input('password'), PASSWORD_BCRYPT, ['cost' => 12]);
    $updateData['password_changed_at'] = date('Y-m-d H:i:s');
}

db_update('users', $updateData, 'id = ?', [$id]);

// Update role
$newRoleId = input_int('role_id');
db_delete('user_roles', 'user_id = ?', [$id]);
db_insert('user_roles', ['user_id' => $id, 'role_id' => $newRoleId]);

audit_log('user_updated', 'users', $id, json_encode(array_pick($userRecord, ['full_name', 'email', 'phone', 'is_active'])), $updateData);

set_flash('success', 'User updated successfully.');
redirect(url('users'));
