<?php
/**
 * Environment File Loader
 * Urjiberi School Management ERP
 *
 * Loads .env file from the project root and makes values
 * available via getenv(), $_ENV, and $_SERVER.
 * This replaces the need for server-level env vars on cPanel.
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Load environment variables from a .env file.
 * Supports: KEY=value, KEY="value", KEY='value', # comments, empty lines
 */
function env_load(string $path): void {
    if (!file_exists($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and empty lines
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Must contain =
        if (strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);

        // Remove surrounding quotes
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        // Don't overwrite existing real env vars
        if (getenv($name) !== false) {
            continue;
        }

        putenv("{$name}={$value}");
        $_ENV[$name]    = $value;
        $_SERVER[$name] = $value;
    }
}

/**
 * Get an environment variable with a default fallback.
 */
function env(string $key, mixed $default = null): mixed {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    // Cast common string representations
    return match (strtolower($value)) {
        'true', '(true)'   => true,
        'false', '(false)'  => false,
        'null', '(null)'    => null,
        'empty', '(empty)'  => '',
        default             => $value,
    };
}
