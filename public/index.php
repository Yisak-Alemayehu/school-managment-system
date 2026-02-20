<?php
/**
 * Front Controller
 * Urjiberi School Management ERP
 *
 * All requests go through this file.
 */

// Define root path
define('APP_ROOT', dirname(__DIR__));

// Load .env first (must be before config files that use getenv)
require APP_ROOT . '/core/env.php';
env_load(APP_ROOT . '/.env');

// Load configuration
require APP_ROOT . '/config/app.php';
require APP_ROOT . '/config/database.php';
require APP_ROOT . '/config/payment.php';

// Load core libraries
require APP_ROOT . '/core/db.php';
require APP_ROOT . '/core/security.php';
require APP_ROOT . '/core/helpers.php';
require APP_ROOT . '/core/auth.php';
require APP_ROOT . '/core/csrf.php';
require APP_ROOT . '/core/validation.php';
require APP_ROOT . '/core/response.php';
require APP_ROOT . '/core/router.php';
require APP_ROOT . '/core/pwa.php';

// Set security headers
set_security_headers();

// Initialize session
auth_init_session();

// Generate CSRF token for forms
csrf_generate();

// Dispatch request to appropriate module
router_dispatch();
