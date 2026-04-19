<?php
/**
 * Users — Store Action (POST)
 * Creates a user account from employee or as standalone (non-employee)
 */

csrf_protect();

$userMode   = input('user_mode'); // 'employee' or 'manual'
$employeeId = input_int('employee_id');
$employee   = null;
$fullName   = '';
$email      = '';
$phone      = '';

// ---- EMPLOYEE MODE ----
if ($userMode === 'employee') {
    if (!$employeeId) {
        set_validation_errors(['employee_id' => 'Please select an employee.']);
        set_old_input($_POST);
        redirect(url('users', 'create'));
    }

    $employee = db_fetch_one("SELECT * FROM hr_employees WHERE id = ? AND deleted_at IS NULL", [$employeeId]);
    if (!$employee) {
        set_validation_errors(['employee_id' => 'Employee not found.']);
        set_old_input($_POST);
        redirect(url('users', 'create'));
    }

    if (!empty($employee['user_id'])) {
        set_validation_errors(['employee_id' => 'This employee already has a user account.']);
        set_old_input($_POST);
        redirect(url('users', 'create'));
    }

    $fullName = trim($employee['first_name'] . ' ' . $employee['father_name'] . ' ' . $employee['grandfather_name']);
    $email    = !empty($employee['email']) ? $employee['email'] : input('email');
    $phone    = $employee['phone'] ?? '';
} else {
    // ---- MANUAL / NON-EMPLOYEE MODE ----
    $fullName = input('full_name');
    $email    = input('email');
    $phone    = input('phone');
}

// ---- VALIDATION ----
$rules = [
    'username'              => 'required|min:3|max:50|alpha_num|unique:users,username',
    'password'              => 'required|password|confirmed',
    'password_confirmation' => 'required',
    'role_id'               => 'required|integer',
];

if ($userMode !== 'employee') {
    // Manual mode: validate name, email, phone from form
    $rules['full_name'] = 'required|min:2|max:100';
    $rules['email']     = 'required|email|max:100|unique:users,email';
    $rules['phone']     = 'nullable|phone';
} else {
    // Employee mode: validate email only if employee has none
    if (empty($employee['email'])) {
        $rules['email'] = 'required|email|max:100|unique:users,email';
    } else {
        $emailExists = db_fetch_one("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL", [$email]);
        if ($emailExists) {
            set_validation_errors(['email' => 'This email is already in use by another user account.']);
            set_old_input($_POST);
            redirect(url('users', 'create'));
        }
    }
}

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

// ---- CREATE USER ----
$userId = db_insert('users', [
    'username'              => input('username'),
    'email'                 => $email,
    'password_hash'         => password_hash(input('password'), PASSWORD_BCRYPT, ['cost' => 12]),
    'full_name'             => $fullName,
    'phone'                 => $phone,
    'is_active'             => isset($_POST['is_active']) ? 1 : 0,
    'force_password_change' => isset($_POST['force_password_change']) ? 1 : 0,
]);

// Assign role
db_insert('user_roles', [
    'user_id' => $userId,
    'role_id' => input_int('role_id'),
]);

// ---- EMPLOYEE LINKING ----
if ($employee) {
    db_update('hr_employees', ['user_id' => $userId], 'id = ?', [$employeeId]);

    if (empty($employee['email']) && !empty($email)) {
        db_update('hr_employees', ['email' => $email], 'id = ?', [$employeeId]);
    }
}

audit_log('user_created', 'users', $userId, null, [
    'username'    => input('username'),
    'full_name'   => $fullName,
    'role_id'     => input_int('role_id'),
    'employee_id' => $employeeId ?: null,
    'mode'        => $userMode,
]);

set_flash('success', 'User account created successfully for ' . $fullName . '.');
redirect(url('users'));
