<?php
/**
 * PWA API — Shared Helpers
 * Used by all pwa-api action files.
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Emit JSON response and exit.
 */
function pwa_json(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Emit JSON error and exit.
 */
function pwa_error(string $message, int $status = 400): never
{
    pwa_json(['error' => $message], $status);
}

/**
 * Require a valid PWA bearer token.
 * Returns the token row (includes user_id, role, linked_id, username, full_name, email).
 */
function pwa_require_auth(): array
{
    $token = pwa_token_validate();
    if (!$token) {
        pwa_error('Unauthenticated. Please log in.', 401);
    }
    return $token;
}

/**
 * Require a specific role.
 */
function pwa_require_role(array $apiUser, string $role): void
{
    if ($apiUser['role'] !== $role) {
        pwa_error('Forbidden: insufficient role.', 403);
    }
}

/**
 * Get raw POST body as decoded JSON array.
 */
function pwa_request_json(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Safely get a string value from an array.
 */
function pwa_str(array $data, string $key, string $default = ''): string
{
    return trim((string) ($data[$key] ?? $default));
}

/**
 * Safely get an integer value from an array.
 */
function pwa_int(array $data, string $key, int $default = 0): int
{
    return (int) ($data[$key] ?? $default);
}

/**
 * Return only safe student fields for API responses.
 */
function pwa_student_safe(array $s): array
{
    return [
        'id'           => (int) $s['id'],
        'admission_no' => $s['admission_no'],
        'full_name'    => $s['full_name'],
        'first_name'   => $s['first_name'],
        'last_name'    => $s['last_name'],
        'gender'       => $s['gender'],
        'photo'        => $s['photo'] ? (APP_URL . '/uploads/students/' . $s['photo']) : null,
        'class_name'   => $s['class_name'] ?? null,
        'section_name' => $s['section_name'] ?? null,
        'class_id'     => isset($s['class_id']) ? (int) $s['class_id'] : null,
        'section_id'   => isset($s['section_id']) ? (int) $s['section_id'] : null,
        'roll_no'      => $s['roll_no'] ?? null,
        'status'       => $s['status'],
    ];
}
