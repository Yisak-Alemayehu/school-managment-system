<?php
/**
 * Router
 * Urjiberi School Management ERP
 * 
 * Simple procedural router: maps URL paths to module files.
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Parse the current request and route to the appropriate module.
 */
function router_dispatch(): void {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $uri = rtrim($uri, '/') ?: '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Remove base path if app is in a subdirectory
    $basePath = parse_url(APP_URL, PHP_URL_PATH) ?: '';
    if ($basePath && $basePath !== '/' && str_starts_with($uri, $basePath)) {
        $uri = substr($uri, strlen($basePath)) ?: '/';
    }

    // Parse segments
    $segments = array_values(array_filter(explode('/', $uri)));
    $module = $segments[0] ?? 'dashboard';
    $action = $segments[1] ?? 'index';
    $id = $segments[2] ?? null;

    // Store for later use
    $GLOBALS['_route'] = [
        'uri'      => $uri,
        'method'   => $method,
        'module'   => $module,
        'action'   => $action,
        'id'       => $id,
        'segments' => $segments,
    ];

    // Static assets bypass
    if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|webp|woff2?|ttf|eot|map)$/i', $uri)) {
        return;
    }

    // Public routes (no auth required)
    $publicRoutes = [
        'auth/login', 'auth/forgot-password', 'auth/reset-password',
        'payments/webhook', 'payments/return', 'payments/timeout',
        'offline',
    ];

    $routeKey = $module . '/' . $action;
    $isPublic = in_array($routeKey, $publicRoutes) || $module === 'auth';

    // Require auth for non-public routes
    if (!$isPublic) {
        auth_require();
    }

    // Route to module
    $moduleDir = MODULES_PATH . '/' . sanitize_module_name($module);

    if (!is_dir($moduleDir)) {
        router_not_found();
        return;
    }

    $routesFile = $moduleDir . '/routes.php';
    if (file_exists($routesFile)) {
        require $routesFile;
        return;
    }

    // Default: try action file
    $actionFile = $moduleDir . '/' . sanitize_module_name($action) . '.php';
    if (file_exists($actionFile)) {
        require $actionFile;
        return;
    }

    router_not_found();
}

/**
 * Get current route info
 */
function route_info(string $key = '') {
    if ($key) {
        return $GLOBALS['_route'][$key] ?? null;
    }
    return $GLOBALS['_route'] ?? [];
}

/**
 * Get current module
 */
function current_module(): string {
    return route_info('module') ?: 'dashboard';
}

/**
 * Get current action
 */
function current_action(): string {
    return route_info('action') ?: 'index';
}

/**
 * Get route ID parameter
 */
function route_id(): ?int {
    $id = route_info('id');
    return $id !== null ? (int) $id : null;
}

/**
 * Sanitize module/action name to prevent directory traversal
 */
function sanitize_module_name(string $name): string {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
}

/**
 * Handle 404
 */
function router_not_found(): void {
    http_response_code(404);
    if (is_ajax_request()) {
        json_response(['error' => 'Not found'], 404);
    }
    include TEMPLATES_PATH . '/errors/404.php';
    exit;
}

/**
 * Build URL
 * Supports:
 *   url('/some/path')
 *   url('module', 'action')
 *   url('module', 'action', $id)
 */
function url(string $pathOrModule = '', string|array $actionOrParams = [], $id = null): string {
    // Pattern: url('module', 'action') or url('module', 'action', $id)
    if (is_string($actionOrParams) && $actionOrParams !== '') {
        $path = '/' . $pathOrModule . '/' . $actionOrParams;
        if ($id !== null) {
            $path .= '/' . $id;
        }
        // Append ? so callers can chain &key=value query params directly
        return rtrim(APP_URL, '/') . $path . '?';
    }

    // Pattern: url('/path') or url('/path', ['key' => 'val'])
    $params = is_array($actionOrParams) ? $actionOrParams : [];
    $url = rtrim(APP_URL, '/') . '/' . ltrim($pathOrModule, '/');
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

/**
 * Build module URL (alias for url with module/action)
 */
function module_url(string $module, string $action = '', $id = null): string {
    return url($module, $action ?: [], $id);
}

/**
 * Redirect to URL
 * Supports:
 *   redirect('/path')
 *   redirect('module', 'action')
 */
function redirect(string $pathOrModule, string|int $actionOrStatus = 302, $id = null): never {
    // Pattern: redirect('module', 'action') or redirect('module', 'action', $id)
    if (is_string($actionOrStatus)) {
        $url = url($pathOrModule, $actionOrStatus, $id);
        header("Location: $url", true, 302);
        exit;
    }

    // Pattern: redirect('/path', 302)
    $url = str_starts_with($pathOrModule, 'http') ? $pathOrModule : url($pathOrModule);
    header("Location: $url", true, (int)$actionOrStatus);
    exit;
}

/**
 * Redirect back to referrer
 */
function redirect_back(string $fallback = '/'): never {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    redirect($referer ?: $fallback);
}

/**
 * Check if current route matches
 */
function route_is(string $module, string $action = ''): bool {
    if (current_module() !== $module) {
        return false;
    }
    if ($action && current_action() !== $action) {
        return false;
    }
    return true;
}
