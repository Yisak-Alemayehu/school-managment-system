<?php
/**
 * Authentication & Session Management
 * Urjiberi School Management ERP
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Initialize session with secure settings
 */
function auth_init_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.cookie_httponly', SESSION_HTTPONLY ? '1' : '0');
    ini_set('session.cookie_secure', SESSION_SECURE ? '1' : '0');
    ini_set('session.cookie_samesite', SESSION_SAMESITE);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);
    ini_set('session.cookie_lifetime', (string) SESSION_LIFETIME);

    session_name(SESSION_NAME);
    session_start();

    // Regenerate session ID periodically (every 30 minutes)
    if (!isset($_SESSION['_last_regeneration'])) {
        $_SESSION['_last_regeneration'] = time();
    } elseif (time() - $_SESSION['_last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_last_regeneration'] = time();
    }
}

/**
 * Attempt login with username/email and password
 * Returns user array on success, error string on failure
 */
function auth_attempt(string $identifier, string $password): array|string {
    // Check brute force lockout
    $lockout = auth_check_lockout($identifier);
    if ($lockout !== false) {
        return "Too many login attempts. Try again in {$lockout} minutes.";
    }

    // Find user
    $user = db_fetch_one(
        "SELECT u.*, GROUP_CONCAT(r.slug) AS role_slugs, GROUP_CONCAT(r.id) AS role_ids
         FROM users u
         LEFT JOIN user_roles ur ON u.id = ur.user_id
         LEFT JOIN roles r ON ur.role_id = r.id
         WHERE (u.username = ? OR u.email = ?) AND u.deleted_at IS NULL
         GROUP BY u.id",
        [$identifier, $identifier]
    );

    if (!$user) {
        auth_log_attempt($identifier, false);
        return 'Invalid username or password.';
    }

    if ($user['status'] !== 'active') {
        auth_log_attempt($identifier, false);
        return 'Your account is not active. Please contact administration.';
    }

    if (!password_verify($password, $user['password_hash'])) {
        auth_log_attempt($identifier, false);
        // Increment login attempts on user
        db_query("UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?", [$user['id']]);
        if (($user['login_attempts'] + 1) >= LOGIN_MAX_ATTEMPTS) {
            $lockUntil = date('Y-m-d H:i:s', time() + (LOGIN_LOCKOUT_MINUTES * 60));
            db_query("UPDATE users SET locked_until = ? WHERE id = ?", [$lockUntil, $user['id']]);
        }
        return 'Invalid username or password.';
    }

    // Check if account is locked
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $mins = (int) ceil((strtotime($user['locked_until']) - time()) / 60);
        return "Account is locked. Try again in {$mins} minutes.";
    }

    // Success — set session
    auth_log_attempt($identifier, true);
    db_query(
        "UPDATE users SET login_attempts = 0, locked_until = NULL, last_login_at = NOW(), last_login_ip = ? WHERE id = ?",
        [get_client_ip(), $user['id']]
    );

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_name'] = $user['full_name'] ?? ($user['first_name'] . ' ' . $user['last_name']);
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_avatar'] = $user['avatar'];
    $_SESSION['user_roles'] = $user['role_slugs'] ? explode(',', $user['role_slugs']) : [];
    $_SESSION['user_role_ids'] = $user['role_ids'] ? array_map('intval', explode(',', $user['role_ids'])) : [];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['_last_regeneration'] = time();

    // Load permissions
    $_SESSION['permissions'] = auth_load_permissions($_SESSION['user_role_ids']);

    return $user;
}

/**
 * Load permissions for given role IDs
 */
function auth_load_permissions(array $roleIds): array {
    if (empty($roleIds)) {
        return [];
    }

    // Super admin (role_id = 1) gets wildcard
    if (in_array(1, $roleIds)) {
        return ['*'];
    }

    $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
    $rows = db_fetch_all(
        "SELECT DISTINCT CONCAT(p.module, '.', p.action) AS perm
         FROM role_permissions rp
         JOIN permissions p ON rp.permission_id = p.id
         WHERE rp.role_id IN ($placeholders)",
        $roleIds
    );

    return array_column($rows, 'perm');
}

/**
 * Check if current user is authenticated
 */
function auth_check(): bool {
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function auth_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data from session
 */
function auth_user(): array {
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'name'     => $_SESSION['user_name'] ?? '',
        'email'    => $_SESSION['user_email'] ?? '',
        'avatar'   => $_SESSION['user_avatar'] ?? '',
        'roles'    => $_SESSION['user_roles'] ?? [],
        'role_ids' => $_SESSION['user_role_ids'] ?? [],
    ];
}

/**
 * Check if user has a specific permission
 */
function auth_has_permission(string $permission): bool {
    if (!auth_check()) {
        return false;
    }
    $perms = $_SESSION['permissions'] ?? [];
    // Wildcard for super admin
    if (in_array('*', $perms)) {
        return true;
    }
    return in_array($permission, $perms);
}

/**
 * Check if user has any of the given permissions
 */
function auth_has_any_permission(array $permissions): bool {
    foreach ($permissions as $perm) {
        if (auth_has_permission($perm)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if user has a specific role
 */
function auth_has_role(string $roleSlug): bool {
    return in_array($roleSlug, $_SESSION['user_roles'] ?? []);
}

/**
 * Check if user is super admin
 */
function auth_is_super_admin(): bool {
    return auth_has_role('super_admin');
}

/**
 * Require authentication — redirect to login if not authenticated
 */
function auth_require(): void {
    if (!auth_check()) {
        set_flash('error', 'Please log in to continue.');
        redirect('/auth/login');
    }
}

/**
 * Require specific permission — show 403 if not allowed
 */
function auth_require_permission(string $permission): void {
    auth_require();
    if (!auth_has_permission($permission)) {
        http_response_code(403);
        include TEMPLATES_PATH . '/errors/403.php';
        exit;
    }
}

/**
 * Logout
 */
function auth_logout(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Generate password reset token
 */
function auth_create_reset_token(string $email): ?string {
    $user = db_fetch_one("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL AND status = 'active'", [$email]);
    if (!$user) {
        return null;
    }

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    db_update('users', [
        'password_reset_token'   => password_hash($token, PASSWORD_BCRYPT),
        'password_reset_expires' => $expires,
    ], 'id = ?', [$user['id']]);

    return $token;
}

/**
 * Reset password with token
 */
function auth_reset_password(string $email, string $token, string $newPassword): bool {
    $user = db_fetch_one(
        "SELECT id, password_reset_token, password_reset_expires FROM users WHERE email = ? AND deleted_at IS NULL",
        [$email]
    );

    if (!$user || !$user['password_reset_token']) {
        return false;
    }

    if (strtotime($user['password_reset_expires']) < time()) {
        return false;
    }

    if (!password_verify($token, $user['password_reset_token'])) {
        return false;
    }

    db_update('users', [
        'password_hash'          => password_hash($newPassword, PASSWORD_BCRYPT),
        'password_reset_token'   => null,
        'password_reset_expires' => null,
        'login_attempts'         => 0,
        'locked_until'           => null,
    ], 'id = ?', [$user['id']]);

    return true;
}

/**
 * Log login attempt
 */
function auth_log_attempt(string $identifier, bool $success): void {
    db_insert('login_attempts', [
        'username_or_email' => $identifier,
        'ip_address'        => get_client_ip(),
        'user_agent'        => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        'success'           => $success ? 1 : 0,
    ]);
}

/**
 * Check if identifier is locked out. Returns minutes remaining or false.
 */
function auth_check_lockout(string $identifier): int|false {
    $since = date('Y-m-d H:i:s', time() - (LOGIN_LOCKOUT_MINUTES * 60));
    $attempts = (int) db_fetch_value(
        "SELECT COUNT(*) FROM login_attempts
         WHERE username_or_email = ? AND success = 0 AND attempted_at > ?",
        [$identifier, $since]
    );

    if ($attempts >= LOGIN_MAX_ATTEMPTS) {
        // Find the oldest relevant attempt to calculate remaining time
        $oldest = db_fetch_value(
            "SELECT MIN(attempted_at) FROM (
                SELECT attempted_at FROM login_attempts
                WHERE username_or_email = ? AND success = 0 AND attempted_at > ?
                ORDER BY attempted_at DESC
                LIMIT ?
            ) AS recent",
            [$identifier, $since, LOGIN_MAX_ATTEMPTS]
        );
        
        if ($oldest) {
            $unlockTime = strtotime($oldest) + (LOGIN_LOCKOUT_MINUTES * 60);
            $remaining = (int) ceil(($unlockTime - time()) / 60);
            return max(1, $remaining);
        }
    }

    return false;
}

// ── Convenience aliases ──────────────────────────────────────────────

/**
 * Alias for auth_require_permission()
 */
function require_permission(string $permission): void {
    auth_require_permission($permission);
}

/**
 * Alias for auth_user()
 */
function current_user(): ?array {
    return auth_user();
}

/**
 * Alias for auth_user_id()
 */
function current_user_id(): ?int {
    return auth_user_id();
}
