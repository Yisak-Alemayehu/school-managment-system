<?php
/**
 * Auth — Forgot Password Action (POST)
 */

csrf_protect();

$errors = validate($_POST, ['email' => 'required|email']);
if (!empty($errors)) {
    set_validation_errors($errors);
    set_old_input($_POST);
    redirect(url('auth', 'forgot-password'));
}

$email = input('email');

// Always show same message to prevent user enumeration
$genericMsg = 'If an account exists with that email, a password reset link has been sent.';

// Check if user exists
$user = db_fetch_one("SELECT id, email, full_name FROM users WHERE email = ? AND is_active = 1 AND deleted_at IS NULL", [$email]);

if ($user) {
    $token = auth_create_reset_token($user['id']);
    
    if ($token) {
        // In production, send email. For now, store token and display message.
        // Build reset URL
        $resetUrl = rtrim(APP_URL, '/') . url('auth', 'reset-password') . '&token=' . $token;
        
        // Log for development — in production, integrate email service
        audit_log('password_reset_requested', 'users', $user['id'], null, [
            'email' => $email,
            'reset_url' => $resetUrl
        ]);
        
        // TODO: Send email via SMTP
        // send_email($user['email'], 'Password Reset', "Click here to reset: $resetUrl");
    }
}

set_flash('success', $genericMsg);
redirect(url('auth', 'forgot-password'));
