<?php
/**
 * Application Configuration
 * Urjiberi School Management ERP
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// ── Application ──────────────────────────────────────────────
define('APP_NAME', 'Urjiberi School ERP');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'development'); // development | production
define('APP_DEBUG', APP_ENV === 'development');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8080');
define('APP_TIMEZONE', 'Africa/Addis_Ababa');

// ── Paths ────────────────────────────────────────────────────
define('ROOT_PATH', APP_ROOT);
define('CONFIG_PATH', APP_ROOT . '/config');
define('CORE_PATH', APP_ROOT . '/core');
define('MODULES_PATH', APP_ROOT . '/modules');
define('TEMPLATES_PATH', APP_ROOT . '/templates');
define('PUBLIC_PATH', APP_ROOT . '/public');
define('UPLOAD_PATH', APP_ROOT . '/uploads');
define('LOG_PATH', APP_ROOT . '/logs');
define('SQL_PATH', APP_ROOT . '/sql');

// ── Session ──────────────────────────────────────────────────
define('SESSION_NAME', 'urjiberi_session');
define('SESSION_LIFETIME', 7200);       // 2 hours
define('SESSION_SECURE', APP_ENV === 'production');
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Lax');

// ── Security ─────────────────────────────────────────────────
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
define('PASSWORD_MIN_LENGTH', 8);
define('CSRF_TOKEN_NAME', '_csrf_token');

// ── Upload ───────────────────────────────────────────────────
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5 MB
define('UPLOAD_ALLOWED_TYPES', [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
]);

// ── Pagination ───────────────────────────────────────────────
define('DEFAULT_PER_PAGE', 20);
define('ITEMS_PER_PAGE', DEFAULT_PER_PAGE); // Alias
define('MAX_PER_PAGE', 100);

// ── Currency ─────────────────────────────────────────────────
define('DEFAULT_CURRENCY', 'ETB');
define('CURRENCY_SYMBOL', 'Br');
define('CURRENCY', DEFAULT_CURRENCY); // Alias

// ── Date/Time ────────────────────────────────────────────────
date_default_timezone_set(APP_TIMEZONE);
define('DATE_FORMAT_DISPLAY', 'd M Y');
define('DATE_FORMAT_DB', 'Y-m-d');
define('DATETIME_FORMAT_DISPLAY', 'd M Y H:i');
define('DATETIME_FORMAT_DB', 'Y-m-d H:i:s');

// ── Error Handling ───────────────────────────────────────────
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
}

ini_set('log_errors', '1');
ini_set('error_log', LOG_PATH . '/php_errors.log');
