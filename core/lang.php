<?php
/**
 * Language / i18n Helper
 * Urji Beri School Management System
 *
 * Usage: __('key') returns translated string, falls back to English, then to key.
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Get current language code.
 */
function get_lang(): string {
    return $_SESSION['lang'] ?? 'en';
}

/**
 * Set current language and persist in session + cookie.
 */
function set_lang(string $lang): void {
    $allowed = ['en', 'am'];
    if (!in_array($lang, $allowed, true)) {
        $lang = 'en';
    }
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, time() + 86400 * 365, '/', '', false, true);
}

/**
 * Translate a key. Returns the translation for the current language,
 * falls back to English, then returns the key itself.
 */
function __(string $key, array $replacements = []): string {
    static $translations = [];

    $lang = get_lang();

    // Load language file if not cached
    if (!isset($translations[$lang])) {
        $file = APP_ROOT . '/lang/' . $lang . '.php';
        if (file_exists($file)) {
            $translations[$lang] = require $file;
        } else {
            $translations[$lang] = [];
        }
    }

    // Look up in current language, fallback to English, then key
    $text = $translations[$lang][$key] ?? null;
    if ($text === null && $lang !== 'en') {
        if (!isset($translations['en'])) {
            $enFile = APP_ROOT . '/lang/en.php';
            $translations['en'] = file_exists($enFile) ? require $enFile : [];
        }
        $text = $translations['en'][$key] ?? $key;
    }
    if ($text === null) {
        $text = $key;
    }

    // Simple placeholder replacement: __('welcome_user', ['name' => 'John'])
    foreach ($replacements as $placeholder => $value) {
        $text = str_replace(':' . $placeholder, $value, $text);
    }

    return $text;
}

/**
 * Get all available languages.
 */
function get_languages(): array {
    return [
        'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇬🇧'],
        'am' => ['name' => 'Amharic', 'native' => 'አማርኛ', 'flag' => '🇪🇹'],
    ];
}
