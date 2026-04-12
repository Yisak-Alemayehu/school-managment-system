<?php
/**
 * Front Controller
 * Urji Beri School Management System
 *
 * All requests go through this file.
 */

// Define root path
define('APP_ROOT', dirname(__DIR__));

// Composer autoloader
require APP_ROOT . '/vendor/autoload.php';

// Load .env first (must be before config files that use getenv)
require APP_ROOT . '/core/env.php';
env_load(APP_ROOT . '/.env');

// Load configuration
require APP_ROOT . '/config/app.php';
require APP_ROOT . '/config/database.php';

// Load core libraries
require APP_ROOT . '/core/db.php';
require APP_ROOT . '/core/security.php';
require APP_ROOT . '/core/helpers.php';
require APP_ROOT . '/core/auth.php';
require APP_ROOT . '/core/csrf.php';
require APP_ROOT . '/core/validation.php';
require APP_ROOT . '/core/response.php';
require APP_ROOT . '/core/router.php';
require APP_ROOT . '/core/rbac.php';
require APP_ROOT . '/core/pwa.php';
require APP_ROOT . '/core/pwa_token.php';
require APP_ROOT . '/core/lang.php';

// Set security headers
set_security_headers();

// Initialize session
auth_init_session();

// Initialize language from cookie/session
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = $_COOKIE['lang'] ?? 'en';
}

// Handle language switch (GET ?lang=am or ?lang=en)
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'am'], true)) {
    set_lang($_GET['lang']);
    // Redirect back to same page without the lang param
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['lang']);
    if ($params) {
        $redirect .= '?' . http_build_query($params);
    }
    header('Location: ' . $redirect);
    exit;
}

// Generate CSRF token for forms
csrf_generate();

// Dispatch request to appropriate module
router_dispatch();
