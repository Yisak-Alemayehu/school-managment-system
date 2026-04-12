<?php
/**
 * PWA Token Authentication Helper
 * Urji Beri School Management System
 *
 * Provides secure bearer-token auth for the Student & Parent PWA.
 * Tokens are random 32-byte values stored as SHA-256 hashes in pwa_tokens.
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

define('PWA_TOKEN_LIFETIME', 86400 * 30); // 30 days

/**
 * Generate a new PWA bearer token for a user.
 *
 * @param int    $userId   User's id
 * @param string $role     'student' or 'parent'
 * @param int    $linkedId student.id or guardian.id
 * @return string          Raw token to return to client
 */
function pwa_token_create(int $userId, string $role, int $linkedId): string
{
    // Clean up expired tokens for this user first
    db_query(
        "DELETE FROM pwa_tokens WHERE user_id = ? AND expires_at < NOW()",
        [$userId]
    );

    // Generate cryptographically secure random token
    $raw   = bin2hex(random_bytes(32)); // 64-char hex
    $hash  = hash('sha256', $raw);
    $exp   = date('Y-m-d H:i:s', time() + PWA_TOKEN_LIFETIME);
    $device = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255);

    db_insert('pwa_tokens', [
        'user_id'     => $userId,
        'token_hash'  => $hash,
        'role'        => $role,
        'linked_id'   => $linkedId,
        'device_name' => $device,
        'expires_at'  => $exp,
    ]);

    return $raw;
}

/**
 * Validate a bearer token from the Authorization header.
 * Returns token row (with user data) or null on failure.
 */
function pwa_token_validate(): ?array
{
    // Apache often strips Authorization before PHP sees it.
    // Try every known location in order.
    $header = $_SERVER['HTTP_AUTHORIZATION']
           ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
           ?? '';

    // getallheaders() works in Apache mod_php and FastCGI
    if ($header === '' && function_exists('getallheaders')) {
        $all = getallheaders();
        // Headers are case-insensitive; check both capitalizations
        $header = $all['Authorization'] ?? $all['authorization'] ?? '';
    }

    if (!str_starts_with($header, 'Bearer ')) {
        return null;
    }

    $raw  = trim(substr($header, 7));
    if (strlen($raw) !== 64 || !ctype_xdigit($raw)) {
        return null;
    }

    $hash = hash('sha256', $raw);

    $row = db_fetch_one(
        "SELECT t.*, u.username, u.full_name, u.email, u.avatar, u.status
         FROM pwa_tokens t
         JOIN users u ON u.id = t.user_id
         WHERE t.token_hash = ? AND t.expires_at > NOW() AND u.deleted_at IS NULL",
        [$hash]
    );

    if (!$row || $row['status'] !== 'active') {
        return null;
    }

    // Update last_used_at (silent - no need to block on failure)
    db_query(
        "UPDATE pwa_tokens SET last_used_at = NOW() WHERE token_hash = ?",
        [$hash]
    );

    return $row;
}

/**
 * Revoke a specific token (logout).
 */
function pwa_token_revoke(string $raw): void
{
    $hash = hash('sha256', $raw);
    db_query("DELETE FROM pwa_tokens WHERE token_hash = ?", [$hash]);
}

/**
 * Revoke ALL tokens for a user (logout everywhere).
 */
function pwa_token_revoke_all(int $userId): void
{
    db_query("DELETE FROM pwa_tokens WHERE user_id = ?", [$userId]);
}
