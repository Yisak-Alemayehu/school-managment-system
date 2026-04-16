<?php
/**
 * Helper Functions
 */

/**
 * Escape HTML output.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get base URL.
 */
function base_url(string $path = ''): string
{
    return Auth::baseUrl() . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Asset URL helper.
 */
function asset(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

/**
 * Redirect helper.
 */
function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

/**
 * Set flash message.
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get and clear flash message.
 */
function get_flash(string $type): ?string
{
    $msg = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $msg;
}

/**
 * Check if flash exists.
 */
function has_flash(string $type): bool
{
    return isset($_SESSION['flash'][$type]);
}

/**
 * Format currency in ETB.
 */
function format_etb(float $amount): string
{
    return number_format($amount, 0, '.', ',') . ' ETB';
}

/**
 * CSRF hidden input field.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(Auth::generateCsrfToken()) . '">';
}

/**
 * Get CMS content by section key.
 */
function get_content(string $key): ?array
{
    static $cache = [];
    if (!isset($cache[$key])) {
        $cache[$key] = Database::fetch("SELECT * FROM content WHERE section_key = ? AND is_active = 1", [$key]);
        if ($cache[$key] && $cache[$key]['extra_data']) {
            $cache[$key]['extra_data'] = json_decode($cache[$key]['extra_data'], true);
        }
    }
    return $cache[$key];
}

/**
 * Get SEO settings for a page.
 */
function get_seo(string $slug): array
{
    $seo = Database::fetch("SELECT * FROM seo_settings WHERE page_slug = ?", [$slug]);
    return $seo ?: [
        'meta_title'       => 'Eduelevate – Smart School Management',
        'meta_description' => 'All-in-one school management platform.',
        'keywords'         => 'school management',
        'og_title'         => 'Eduelevate',
        'og_description'   => 'Smart school management platform.',
        'og_image'         => '',
    ];
}

/**
 * Get all active features.
 */
function get_features(): array
{
    return Database::fetchAll("SELECT * FROM features WHERE is_active = 1 ORDER BY sort_order ASC");
}

/**
 * Get all active pricing packages.
 */
function get_pricing(): array
{
    $packages = Database::fetchAll("SELECT * FROM pricing_packages WHERE is_active = 1 ORDER BY sort_order ASC");
    foreach ($packages as &$pkg) {
        $pkg['features_list'] = json_decode($pkg['features_list'], true) ?: [];
    }
    return $packages;
}

/**
 * Get all active testimonials.
 */
function get_testimonials(): array
{
    return Database::fetchAll("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC");
}

/**
 * Get all active FAQs.
 */
function get_faqs(): array
{
    return Database::fetchAll("SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC");
}

/**
 * Get unread notification count for a user.
 */
function unread_notifications_count(int $userId): int
{
    return (int) Database::fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", [$userId]);
}

/**
 * JSON response helper.
 */
function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Validate required fields.
 */
function validate_required(array $data, array $fields): array
{
    $errors = [];
    foreach ($fields as $field => $label) {
        if (empty(trim($data[$field] ?? ''))) {
            $errors[$field] = "{$label} is required.";
        }
    }
    return $errors;
}

/**
 * Sanitize input string.
 */
function sanitize(string $input): string
{
    return trim(strip_tags($input));
}

/**
 * Get pipeline stage display info.
 */
function pipeline_stage_info(string $stage): array
{
    $stages = [
        'requested'        => ['label' => 'Access Requested', 'color' => 'gray',   'icon' => 'inbox',       'step' => 1],
        'demo_scheduled'   => ['label' => 'Demo Scheduled',   'color' => 'blue',   'icon' => 'calendar',    'step' => 2],
        'demo_completed'   => ['label' => 'Demo Completed',   'color' => 'indigo', 'icon' => 'check-circle','step' => 3],
        'interested'       => ['label' => 'Interested',       'color' => 'purple', 'icon' => 'heart',       'step' => 4],
        'agreement_sent'   => ['label' => 'Agreement Sent',   'color' => 'yellow', 'icon' => 'document',    'step' => 5],
        'payment_pending'  => ['label' => 'Payment Pending',  'color' => 'orange', 'icon' => 'cash',        'step' => 6],
        'active'           => ['label' => 'Active',           'color' => 'green',  'icon' => 'badge-check', 'step' => 7],
        'churned'          => ['label' => 'Churned',          'color' => 'red',    'icon' => 'x-circle',    'step' => 0],
    ];
    return $stages[$stage] ?? ['label' => ucfirst($stage), 'color' => 'gray', 'icon' => 'question', 'step' => 0];
}

/**
 * Format date for display.
 */
function format_date(?string $date, string $format = 'M d, Y'): string
{
    if (!$date) return '—';
    return date($format, strtotime($date));
}

/**
 * Format datetime for display.
 */
function format_datetime(?string $dt): string
{
    if (!$dt) return '—';
    return date('M d, Y \a\t h:i A', strtotime($dt));
}

/**
 * Time ago helper.
 */
function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M d', strtotime($datetime));
}

/**
 * Upload a file and return the path.
 */
function upload_file(array $file, string $subdir = ''): ?string
{
    $config = require __DIR__ . '/../config/config.php';
    $maxSize = $config['upload']['max_size'];
    $allowed = $config['upload']['allowed_types'];
    $basePath = $config['upload']['path'];

    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $maxSize) return null;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return null;

    $dir = $basePath . ($subdir ? '/' . trim($subdir, '/') : '');
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $path = $dir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        return ($subdir ? trim($subdir, '/') . '/' : '') . $filename;
    }
    return null;
}
