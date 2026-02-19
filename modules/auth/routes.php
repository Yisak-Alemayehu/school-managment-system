<?php
/**
 * Auth Module — Routes
 * Handles: login, logout, forgot password, reset password
 */

$action = current_action();

switch ($action) {
    case 'login':
        if (is_post()) {
            require __DIR__ . '/actions/login.php';
        } else {
            // If already logged in, redirect to dashboard
            if (auth_check()) {
                redirect(url('dashboard'));
            }
            $pageTitle = 'Sign In';
            require __DIR__ . '/views/login.php';
        }
        break;

    case 'logout':
        csrf_protect();
        auth_logout();
        set_flash('success', 'You have been signed out.');
        redirect(url('auth', 'login'));
        break;

    case 'forgot-password':
        if (is_post()) {
            require __DIR__ . '/actions/forgot_password.php';
        } else {
            $pageTitle = 'Forgot Password';
            require __DIR__ . '/views/forgot_password.php';
        }
        break;

    case 'reset-password':
        if (is_post()) {
            require __DIR__ . '/actions/reset_password.php';
        } else {
            $token = $_GET['token'] ?? '';
            if (empty($token)) {
                set_flash('error', 'Invalid password reset link.');
                redirect(url('auth', 'login'));
            }
            $pageTitle = 'Reset Password';
            require __DIR__ . '/views/reset_password.php';
        }
        break;

    case 'change-password':
        auth_require();
        if (is_post()) {
            require __DIR__ . '/actions/change_password.php';
        } else {
            $pageTitle = 'Change Password';
            require __DIR__ . '/views/change_password.php';
        }
        break;

    case 'profile':
        auth_require();
        if (is_post()) {
            require __DIR__ . '/actions/update_profile.php';
        } else {
            $pageTitle = 'My Profile';
            require __DIR__ . '/views/profile.php';
        }
        break;

    default:
        redirect(url('auth', 'login'));
        break;
}
