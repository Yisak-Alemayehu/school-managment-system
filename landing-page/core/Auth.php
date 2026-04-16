<?php
/**
 * Authentication Handler
 */

class Auth
{
    /**
     * Start session if not already started.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../config/config.php';
            session_name($config['session']['name']);
            session_set_cookie_params([
                'lifetime' => $config['session']['lifetime'],
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly'  => true,
                'samesite'  => 'Lax',
            ]);
            session_start();
        }
    }

    /**
     * Generate CSRF token.
     */
    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token.
     */
    public static function validateCsrf(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Hash a password.
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Attempt login.
     */
    public static function attempt(string $email, string $password): ?array
    {
        $user = Database::fetch("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);
        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['login_time'] = time();

            Database::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

            unset($user['password']);
            return $user;
        }
        return null;
    }

    /**
     * Register a new user.
     */
    public static function register(string $email, string $password, string $name, string $role = 'school'): int
    {
        return Database::insert('users', [
            'email'    => $email,
            'password' => self::hashPassword($password),
            'name'     => $name,
            'role'     => $role,
        ]);
    }

    /**
     * Check if user is logged in.
     */
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user.
     */
    public static function user(): ?array
    {
        if (!self::check()) return null;
        return Database::fetch("SELECT id, email, name, role, last_login, created_at FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }

    /**
     * Get current user ID.
     */
    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user role.
     */
    public static function role(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    /**
     * Check if current user is admin.
     */
    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    /**
     * Logout.
     */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /**
     * Require authentication — redirect if not logged in.
     */
    public static function requireAuth(): void
    {
        if (!self::check()) {
            header('Location: ' . self::baseUrl() . '/login');
            exit;
        }
    }

    /**
     * Require admin role.
     */
    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    /**
     * Require school role.
     */
    public static function requireSchool(): void
    {
        self::requireAuth();
        if (self::role() !== 'school') {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    /**
     * Get the base URL for the landing page.
     */
    public static function baseUrl(): string
    {
        static $base = null;
        if ($base === null) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
            $scheme = $isHttps ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
            $base = $scheme . '://' . $host . $scriptDir;
        }
        return $base;
    }
}
