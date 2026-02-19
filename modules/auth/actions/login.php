<?php
/**
 * Auth — Login Action (POST)
 */

csrf_protect();

$rules = [
    'username' => 'required|max:100',
    'password' => 'required|max:255',
];

$errors = validate($_POST, $rules);
if (!empty($errors)) {
    set_validation_errors($errors);
    set_old_input($_POST);
    redirect(url('auth', 'login'));
}

$username = input('username');
$password = input('password');

$result = auth_attempt($username, $password);

if (is_array($result)) {
    // Successful login
    $intended = $_SESSION['intended_url'] ?? url('dashboard');
    unset($_SESSION['intended_url']);
    redirect($intended);
} else {
    // Login failed — $result is an error string
    set_flash('error', $result);
    set_old_input(['username' => $username]);
    redirect(url('auth', 'login'));
}
