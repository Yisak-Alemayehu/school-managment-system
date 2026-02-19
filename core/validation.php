<?php
/**
 * Input Validation
 * Urjiberi School Management ERP
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Validate input data against rules.
 * Returns array of errors (empty = valid).
 *
 * Rules format: ['field' => 'required|email|min:3|max:100|numeric|date|in:a,b,c|unique:table,column']
 */
function validate(array $data, array $rules): array {
    $errors = [];

    foreach ($rules as $field => $ruleString) {
        $value = $data[$field] ?? null;
        $fieldRules = explode('|', $ruleString);
        $label = str_replace('_', ' ', ucfirst($field));

        foreach ($fieldRules as $rule) {
            $params = [];
            if (str_contains($rule, ':')) {
                [$rule, $paramStr] = explode(':', $rule, 2);
                $params = explode(',', $paramStr);
            }

            $error = validate_rule($field, $value, $rule, $params, $label, $data);
            if ($error !== null) {
                $errors[$field] = $error;
                break; // Stop at first error per field
            }
        }
    }

    return $errors;
}

/**
 * Validate a single rule
 */
function validate_rule(string $field, $value, string $rule, array $params, string $label, array $data): ?string {
    switch ($rule) {
        case 'required':
            if ($value === null || $value === '' || $value === []) {
                return "$label is required.";
            }
            break;

        case 'email':
            if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return "$label must be a valid email address.";
            }
            break;

        case 'min':
            $min = (int) ($params[0] ?? 0);
            if (is_string($value) && mb_strlen($value) < $min) {
                return "$label must be at least $min characters.";
            }
            if (is_numeric($value) && $value < $min) {
                return "$label must be at least $min.";
            }
            break;

        case 'max':
            $max = (int) ($params[0] ?? 0);
            if (is_string($value) && mb_strlen($value) > $max) {
                return "$label must not exceed $max characters.";
            }
            if (is_numeric($value) && $value > $max) {
                return "$label must not exceed $max.";
            }
            break;

        case 'numeric':
            if ($value !== null && $value !== '' && !is_numeric($value)) {
                return "$label must be a number.";
            }
            break;

        case 'integer':
            if ($value !== null && $value !== '' && !ctype_digit((string) $value)) {
                return "$label must be a whole number.";
            }
            break;

        case 'date':
            if ($value !== null && $value !== '') {
                $d = date_create($value);
                if (!$d) {
                    return "$label must be a valid date.";
                }
            }
            break;

        case 'in':
            if ($value !== null && $value !== '' && !in_array($value, $params)) {
                return "$label must be one of: " . implode(', ', $params) . ".";
            }
            break;

        case 'alpha':
            if ($value !== null && $value !== '' && !preg_match('/^[\pL\s]+$/u', $value)) {
                return "$label must contain only letters.";
            }
            break;

        case 'alpha_num':
            if ($value !== null && $value !== '' && !preg_match('/^[\pL\pN\s]+$/u', $value)) {
                return "$label must contain only letters and numbers.";
            }
            break;

        case 'phone':
            if ($value !== null && $value !== '' && !preg_match('/^[\+]?[0-9\s\-\(\)]{7,20}$/', $value)) {
                return "$label must be a valid phone number.";
            }
            break;

        case 'unique':
            $table = $params[0] ?? '';
            $column = $params[1] ?? $field;
            $exceptId = $params[2] ?? null;
            if ($value !== null && $value !== '' && $table) {
                $sql = "SELECT COUNT(*) FROM `$table` WHERE `$column` = ?";
                $queryParams = [$value];
                if ($exceptId) {
                    $sql .= " AND id != ?";
                    $queryParams[] = $exceptId;
                }
                if (str_contains($table, 'users') || str_contains($table, 'students')) {
                    $sql .= " AND deleted_at IS NULL";
                }
                if (db_fetch_value($sql, $queryParams) > 0) {
                    return "$label already exists.";
                }
            }
            break;

        case 'confirmed':
            $confirmField = $field . '_confirmation';
            if (($data[$confirmField] ?? '') !== $value) {
                return "$label confirmation does not match.";
            }
            break;

        case 'password':
            if ($value !== null && $value !== '') {
                if (mb_strlen($value) < PASSWORD_MIN_LENGTH) {
                    return "$label must be at least " . PASSWORD_MIN_LENGTH . " characters.";
                }
                if (!preg_match('/[A-Z]/', $value)) {
                    return "$label must contain at least one uppercase letter.";
                }
                if (!preg_match('/[a-z]/', $value)) {
                    return "$label must contain at least one lowercase letter.";
                }
                if (!preg_match('/[0-9]/', $value)) {
                    return "$label must contain at least one number.";
                }
            }
            break;

        case 'nullable':
            // Allow null/empty — skip remaining rules if empty
            if ($value === null || $value === '') {
                return null;
            }
            break;

        case 'file':
            // File validation is handled separately
            break;
    }

    return null;
}

/**
 * Validate file upload
 */
function validate_file(string $fieldName, array $options = []): ?string {
    $maxSize = $options['max_size'] ?? UPLOAD_MAX_SIZE;
    $allowedTypes = $options['allowed_types'] ?? UPLOAD_ALLOWED_TYPES;
    $required = $options['required'] ?? false;

    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $required ? 'File is required.' : null;
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'File upload error. Please try again.';
    }

    if ($file['size'] > $maxSize) {
        $maxMb = round($maxSize / 1024 / 1024, 1);
        return "File size must not exceed {$maxMb}MB.";
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return 'File type is not allowed.';
    }

    return null;
}

/**
 * Sanitize input string
 */
function sanitize_input(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Get validated input value with default
 */
function input(string $key, $default = '') {
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    if (is_string($value)) {
        return trim($value);
    }
    return $value;
}

/**
 * Get integer input
 */
function input_int(string $key, int $default = 0): int {
    return (int) input($key, $default);
}

/**
 * Get array input
 */
function input_array(string $key): array {
    $value = $_POST[$key] ?? $_GET[$key] ?? [];
    return is_array($value) ? $value : [];
}
