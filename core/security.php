<?php
/**
 * Security Helpers
 * Urjiberi School Management ERP
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Escape output for HTML
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Get client IP address
 */
function get_client_ip(): string {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            if (str_contains($ip, ',')) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/**
 * Handle secure file upload
 * Returns relative file path on success, null on failure
 */
function handle_upload(string $fieldName, string $subDir = 'general', array $options = []): ?string {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES[$fieldName];
    $maxSize = $options['max_size'] ?? UPLOAD_MAX_SIZE;
    $allowedTypes = $options['allowed_types'] ?? UPLOAD_ALLOWED_TYPES;

    // Verify size
    if ($file['size'] > $maxSize) {
        return null;
    }

    // Verify MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return null;
    }

    // Generate random filename
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safeExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    if (!in_array($ext, $safeExtensions)) {
        $ext = 'bin';
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $targetDir = UPLOAD_PATH . '/' . $subDir . '/' . date('Y/m');

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = $targetDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return null;
    }

    // Return relative path from upload root
    return $subDir . '/' . date('Y/m') . '/' . $filename;
}

/**
 * Delete uploaded file
 */
function delete_upload(string $relativePath): bool {
    $fullPath = UPLOAD_PATH . '/' . $relativePath;
    if (file_exists($fullPath) && is_file($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

/**
 * Log audit event
 * Flexible signature to support various call patterns:
 *   audit_log('action', 'description')
 *   audit_log('action', 'module', $entityId)
 *   audit_log('action', 'module', $entityId, 'description')
 *   audit_log('action', 'module', $entityId, $oldValues, $newValues)
 */
function audit_log(string $action, string $moduleOrDesc = '', $entityIdOrType = null, $oldOrDesc = null, $newValues = null, $extraNew = null, ?string $description = null): void {
    try {
        $module = $moduleOrDesc;
        $entityType = null;
        $entityId   = null;
        $oldValues  = null;
        $desc       = $description;

        // Pattern: audit_log('action.verb', 'description') — 2 args, module contains dot or is a sentence
        if ($entityIdOrType === null && $oldOrDesc === null && (str_contains($action, '.') || strlen($moduleOrDesc) > 60)) {
            $desc   = $moduleOrDesc;
            $module = explode('.', $action)[0];
        }
        // Pattern: audit_log('action', 'table', int_id)
        elseif (is_int($entityIdOrType) || is_numeric($entityIdOrType)) {
            $entityId = (int)$entityIdOrType;
            $entityType = $module;

            if (is_string($oldOrDesc) && $newValues === null) {
                // audit_log('action', 'table', id, 'description')
                $desc = $oldOrDesc;
            } elseif (is_array($oldOrDesc)) {
                $oldValues = $oldOrDesc;
                if (is_array($newValues)) {
                    // audit_log('action', 'table', id, [...old], [...new])
                    // $newValues stays
                }
            }
        }
        // Pattern: audit_log('action', 'table', 'entity_type_string', int_id, ...)
        elseif (is_string($entityIdOrType)) {
            $entityType = $entityIdOrType;
            if (is_int($oldOrDesc) || is_numeric($oldOrDesc)) {
                $entityId = (int)$oldOrDesc;
            }
        }

        db_insert('audit_logs', [
            'user_id'     => auth_user_id(),
            'action'      => $action,
            'module'      => $module,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => is_array($oldValues) ? json_encode($oldValues) : (is_string($oldValues) ? $oldValues : null),
            'new_values'  => is_array($newValues) ? json_encode($newValues) : (is_string($newValues) ? $newValues : null),
            'ip_address'  => get_client_ip(),
            'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'description' => $desc,
        ]);
    } catch (Throwable $e) {
        error_log('Audit log failed: ' . $e->getMessage());
    }
}

/**
 * Rate limiting (simple in-memory via session)
 */
function rate_limit(string $key, int $maxAttempts = 10, int $windowSeconds = 60): bool {
    $now = time();
    $sessionKey = '_rate_' . $key;

    if (!isset($_SESSION[$sessionKey])) {
        $_SESSION[$sessionKey] = [];
    }

    // Clean old entries
    $_SESSION[$sessionKey] = array_filter(
        $_SESSION[$sessionKey],
        fn($ts) => $ts > ($now - $windowSeconds)
    );

    if (count($_SESSION[$sessionKey]) >= $maxAttempts) {
        return false; // Rate limited
    }

    $_SESSION[$sessionKey][] = $now;
    return true; // Allowed
}

/**
 * Set security headers
 */
function set_security_headers(): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if (SESSION_SECURE) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
