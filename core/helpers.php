<?php
/**
 * Helper Functions
 * Urjiberi School Management ERP
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// ── Flash Messages ───────────────────────────────────────────

function set_flash(string $type, string $message): void {
    $_SESSION['_flash'][$type] = $message;
}

function get_flash(string $type): ?string {
    $message = $_SESSION['_flash'][$type] ?? null;
    unset($_SESSION['_flash'][$type]);
    return $message;
}

function has_flash(string $type): bool {
    return isset($_SESSION['_flash'][$type]);
}

function get_all_flash(): array {
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

// ── Old Input (form repopulation) ────────────────────────────

function set_old_input(array $data = null): void {
    $_SESSION['_old_input'] = $data ?? $_POST;
}

function old(string $key, string $default = ''): string {
    $value = $_SESSION['_old_input'][$key] ?? $default;
    unset($_SESSION['_old_input'][$key]);
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function clear_old_input(): void {
    unset($_SESSION['_old_input']);
}

// ── Form Errors ──────────────────────────────────────────────

function set_validation_errors(array $errors): void {
    $_SESSION['_validation_errors'] = $errors;
}

function get_validation_errors(): array {
    $errors = $_SESSION['_validation_errors'] ?? [];
    unset($_SESSION['_validation_errors']);
    return $errors;
}

function get_error(string $field): ?string {
    return $_SESSION['_validation_errors'][$field] ?? null;
}

function get_validation_error(string $field): ?string {
    return $_SESSION['_validation_errors'][$field] ?? null;
}

function has_error(string $field): bool {
    return isset($_SESSION['_validation_errors'][$field]);
}

// ── Date / Time ──────────────────────────────────────────────

function format_date(?string $date, string $format = ''): string {
    if (!$date) return '';
    $format = $format ?: DATE_FORMAT_DISPLAY;
    $dt = date_create($date);
    return $dt ? date_format($dt, $format) : '';
}

function format_datetime(?string $datetime, string $format = ''): string {
    if (!$datetime) return '';
    $format = $format ?: DATETIME_FORMAT_DISPLAY;
    $dt = date_create($datetime);
    return $dt ? date_format($dt, $format) : '';
}

function time_ago(string $datetime): string {
    $now = time();
    $diff = $now - strtotime($datetime);

    if ($diff < 60) return 'just now';
    if ($diff < 3600) return (int)($diff / 60) . 'm ago';
    if ($diff < 86400) return (int)($diff / 3600) . 'h ago';
    if ($diff < 604800) return (int)($diff / 86400) . 'd ago';

    return format_date($datetime);
}

// ── Currency ─────────────────────────────────────────────────

function format_money($amount, bool $withSymbol = true): string {
    $formatted = number_format((float) $amount, 2);
    return $withSymbol ? CURRENCY_SYMBOL . ' ' . $formatted : $formatted;
}

// ── Upload URL ───────────────────────────────────────────────

/**
 * Build a public URL for an uploaded file.
 * e.g. upload_url('students/2026/02/abc.jpg') → /uploads.php?file=students/2026/02/abc.jpg
 */
function upload_url(?string $relativePath): string {
    if (empty($relativePath)) {
        return APP_URL . '/assets/images/placeholder.png';
    }
    return APP_URL . '/uploads.php?file=' . urlencode($relativePath);
}

// ── String Helpers ───────────────────────────────────────────

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function truncate(string $text, int $length = 100, string $suffix = '...'): string {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $suffix;
}

function generate_code(string $prefix = '', int $length = 8): string {
    $number = strtoupper(bin2hex(random_bytes($length / 2)));
    return $prefix . $number;
}

function generate_invoice_no(): string {
    $prefix = db_fetch_value("SELECT setting_value FROM settings WHERE setting_group = 'finance' AND setting_key = 'invoice_prefix'") ?: 'INV-';
    $year = date('Y');
    $count = db_count('invoices', "invoice_no LIKE ?", [$prefix . $year . '%']) + 1;
    return $prefix . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
}

function generate_receipt_no(): string {
    $prefix = db_fetch_value("SELECT setting_value FROM settings WHERE setting_group = 'finance' AND setting_key = 'receipt_prefix'") ?: 'RCP-';
    $year = date('Y');
    $count = db_count('payments', "receipt_no LIKE ?", [$prefix . $year . '%']) + 1;
    return $prefix . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
}

// ── Pagination Helper ────────────────────────────────────────

function pagination_html(array $pagination, string $baseUrl = ''): string {
    if ($pagination['last_page'] <= 1) return '';

    $current = $pagination['current_page'];
    $last = $pagination['last_page'];
    $baseUrl = $baseUrl ?: strtok($_SERVER['REQUEST_URI'], '?');

    $params = $_GET;
    $html = '<nav class="flex items-center justify-between px-2 py-3" aria-label="Pagination">';
    $html .= '<div class="hidden sm:block text-sm text-gray-600">';
    $html .= "Showing {$pagination['from']} to {$pagination['to']} of {$pagination['total']} results";
    $html .= '</div>';
    $html .= '<div class="flex gap-1">';

    // Previous
    if ($current > 1) {
        $params['page'] = $current - 1;
        $html .= '<a href="' . $baseUrl . '?' . http_build_query($params) . '" class="px-3 py-1.5 rounded-lg text-sm bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">&laquo; Prev</a>';
    }

    // Page numbers
    $start = max(1, $current - 2);
    $end = min($last, $current + 2);

    if ($start > 1) {
        $params['page'] = 1;
        $html .= '<a href="' . $baseUrl . '?' . http_build_query($params) . '" class="px-3 py-1.5 rounded-lg text-sm bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">1</a>';
        if ($start > 2) $html .= '<span class="px-2 py-1.5 text-gray-400">...</span>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $params['page'] = $i;
        $active = $i === $current ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50';
        $html .= '<a href="' . $baseUrl . '?' . http_build_query($params) . '" class="px-3 py-1.5 rounded-lg text-sm border ' . $active . '">' . $i . '</a>';
    }

    if ($end < $last) {
        if ($end < $last - 1) $html .= '<span class="px-2 py-1.5 text-gray-400">...</span>';
        $params['page'] = $last;
        $html .= '<a href="' . $baseUrl . '?' . http_build_query($params) . '" class="px-3 py-1.5 rounded-lg text-sm bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">' . $last . '</a>';
    }

    // Next
    if ($current < $last) {
        $params['page'] = $current + 1;
        $html .= '<a href="' . $baseUrl . '?' . http_build_query($params) . '" class="px-3 py-1.5 rounded-lg text-sm bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">Next &raquo;</a>';
    }

    $html .= '</div></nav>';
    return $html;
}

// ── Settings ─────────────────────────────────────────────────

function get_setting(string $group, string $key, $default = null): ?string {
    static $cache = [];
    $cacheKey = "$group.$key";

    if (!isset($cache[$cacheKey])) {
        $val = db_fetch_value(
            "SELECT setting_value FROM settings WHERE setting_group = ? AND setting_key = ?",
            [$group, $key]
        );
        $cache[$cacheKey] = $val;
    }

    return $cache[$cacheKey] ?? $default;
}

function get_school_name(): string {
    return get_setting('school', 'name', APP_NAME);
}

// ── Active Session/Term ──────────────────────────────────────

function get_active_session(): ?array {
    static $session = false;
    if ($session === false) {
        $session = db_fetch_one("SELECT * FROM academic_sessions WHERE is_active = 1 LIMIT 1");
    }
    return $session;
}

function get_active_session_id(): ?int {
    $s = get_active_session();
    return $s ? (int) $s['id'] : null;
}

function get_active_term(): ?array {
    static $term = false;
    if ($term === false) {
        $sessionId = get_active_session_id();
        $term = $sessionId ? db_fetch_one("SELECT * FROM terms WHERE session_id = ? AND is_active = 1 LIMIT 1", [$sessionId]) : null;
    }
    return $term;
}

function get_active_term_id(): ?int {
    $t = get_active_term();
    return $t ? (int) $t['id'] : null;
}

// ── Notification Helper ──────────────────────────────────────

function create_notification(int $userId, string $type, string $title, ?string $message = null, ?string $link = null): void {
    db_insert('notifications', [
        'user_id' => $userId,
        'type'    => $type,
        'title'   => $title,
        'message' => $message,
        'link'    => $link,
    ]);
}

function get_unread_notification_count(?int $userId = null): int {
    $userId = $userId ?? auth_user_id();
    if (!$userId) return 0;
    return db_count('notifications', 'user_id = ? AND is_read = 0', [$userId]);
}

// ── Misc ─────────────────────────────────────────────────────

function dd(...$vars): never {
    if (!APP_DEBUG) {
        die('Debug not available');
    }
    echo '<pre style="background:#1e1e2e;color:#cdd6f4;padding:1rem;margin:1rem;border-radius:0.5rem;overflow:auto;">';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    exit;
}

function array_pick(array $source, array $keys): array {
    return array_intersect_key($source, array_flip($keys));
}

function is_active_nav(string $module, string $action = ''): string {
    return route_is($module, $action) ? 'active' : '';
}

// ── Convenience Aliases ──────────────────────────────────────

/**
 * Alias for format_money()
 */
function format_currency($amount, bool $withSymbol = true): string {
    return format_money($amount, $withSymbol);
}

/**
 * Alias for pagination_html() — also supports (int $page, int $totalPages, string $url) signature
 */
function render_pagination($paginationOrPage, $baseUrlOrTotal = '', $url = ''): string {
    if (is_array($paginationOrPage)) {
        return pagination_html($paginationOrPage, $baseUrlOrTotal);
    }
    // Legacy signature: render_pagination(int $page, int $totalPages, string $url)
    $page = (int) $paginationOrPage;
    $total = (int) $baseUrlOrTotal;
    $pagination = [
        'current_page' => $page,
        'last_page'    => $total,
        'total'        => $total,
        'from'         => $page,
        'to'           => $page,
    ];
    return pagination_html($pagination, $url);
}

/**
 * Alias for auth_has_permission()
 */
function has_permission(string $permission): bool {
    return auth_has_permission($permission);
}
