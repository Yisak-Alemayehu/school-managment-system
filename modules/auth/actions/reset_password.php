<?php
/**
 * Auth — Reset Password Action (POST)
 */

csrf_protect();

$rules = [
    'token'                 => 'required',
    'password'              => 'required|password|confirmed',
    'password_confirmation' => 'required',
];

$errors = validate($_POST, $rules);
if (!empty($errors)) {
    set_validation_errors($errors);
    redirect(url('auth', 'reset-password') . '&token=' . urlencode(input('token')));
}

$token    = input('token');
$password = input('password');

$result = auth_reset_password($token, $password);

if ($result === true) {
    set_flash('success', 'Password has been reset successfully. Please sign in.');
    redirect(url('auth', 'login'));
} else {
    set_flash('error', $result);
    redirect(url('auth', 'reset-password') . '&token=' . urlencode($token));
}
