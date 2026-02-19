<?php
/**
 * CSRF Protection
 * Urjiberi School Management ERP
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Generate CSRF token and store in session
 */
function csrf_generate(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Get hidden input HTML for forms
 */
function csrf_field(): string {
    $token = csrf_generate();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
}

/**
 * Get meta tag for AJAX requests
 */
function csrf_meta(): string {
    $token = csrf_generate();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF token from request
 */
function csrf_validate(): bool {
    $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken = $_SESSION[CSRF_TOKEN_NAME] ?? '';

    if (empty($token) || empty($sessionToken)) {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

/**
 * Require valid CSRF token on state-changing requests
 */
function csrf_protect(): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
        if (!csrf_validate()) {
            http_response_code(403);
            if (is_ajax_request()) {
                json_response(['error' => 'Invalid CSRF token. Please refresh the page.'], 403);
            }
            set_flash('error', 'Security token expired. Please try again.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }

    // Regenerate token after validation
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}

/**
 * Alias for csrf_protect()
 */
function verify_csrf(): void {
    csrf_protect();
}

/**
 * Alias for csrf_protect() — used in GET-based delete actions
 */
function verify_csrf_get(): void {
    if (!csrf_validate()) {
        http_response_code(403);
        if (is_ajax_request()) {
            json_response(['error' => 'Invalid CSRF token. Please refresh the page.'], 403);
        }
        set_flash('error', 'Security token expired. Please try again.');
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
}
