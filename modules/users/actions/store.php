<?php
/**
 * Users — Store Action (POST)
 */

csrf_protect();

$rules = [
    'full_name'             => 'required|min:2|max:100',
    'username'              => 'required|min:3|max:50|alpha_num|unique:users,username',
    'email'                 => 'required|email|max:100|unique:users,email',
    'phone'                 => 'nullable|phone',
    'password'              => 'required|password|confirmed',
    'password_confirmation' => 'required',
    'role_id'               => 'required|integer',
];

$errors = validate($_POST, $rules);

// Verify role exists
if (empty($errors['role_id'])) {
    $roleExists = db_exists('roles', 'id = ?', [input_int('role_id')]);
    if (!$roleExists) {
        $errors['role_id'] = 'Invalid role selected.';
    }
}

if (!empty($errors)) {
    set_validation_errors($errors);
    set_old_input($_POST);
    redirect(url('users', 'create'));
}

// Create user
$userId = db_insert('users', [
    'username'              => input('username'),
    'email'                 => input('email'),
    'password_hash'         => password_hash(input('password'), PASSWORD_BCRYPT, ['cost' => 12]),
    'full_name'             => input('full_name'),
    'phone'                 => input('phone'),
    'is_active'             => isset($_POST['is_active']) ? 1 : 0,
    'force_password_change' => isset($_POST['force_password_change']) ? 1 : 0,
]);

// Assign role
db_insert('user_roles', [
    'user_id' => $userId,
    'role_id' => input_int('role_id'),
]);

audit_log('user_created', 'users', $userId, null, [
    'username'  => input('username'),
    'full_name' => input('full_name'),
    'role_id'   => input_int('role_id'),
]);

set_flash('success', 'User created successfully.');
redirect(url('users'));
