<?php
/**
 * PWA API Module — Main Router
 * All endpoints under /pwa-api/
 *
 * Auth: Bearer token (see core/pwa_token.php)
 * Responses: JSON only
 *
 * Route structure:
 *   /pwa-api/{action}[/{sub}]
 *   segments[0] = 'pwa-api'
 *   segments[1] = action
 *   segments[2] = optional sub-resource / id
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// ── Response headers ─────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// ── CORS: allow same origin or configured origin ─────────────
$allowedOrigin = defined('APP_URL') ? APP_URL : '*';
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($requestOrigin && (rtrim($requestOrigin, '/') === rtrim($allowedOrigin, '/') || APP_ENV === 'development')) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
    header('Vary: Origin');
} else {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Helpers ──────────────────────────────────────────────────
require __DIR__ . '/helpers.php';

// ── Routing ──────────────────────────────────────────────────
$segments = $GLOBALS['_route']['segments'] ?? [];
// segments[0] = 'pwa-api', segments[1] = action, segments[2] = sub
$action = $segments[1] ?? '';
$sub    = $segments[2] ?? null;

// ── Public endpoints ─────────────────────────────────────────
if ($action === 'login') {
    require __DIR__ . '/actions/auth.php';
    pwa_handle_login();
}

if ($action === 'logout') {
    require __DIR__ . '/actions/auth.php';
    pwa_handle_logout(); // token passed in header — no session needed
}

// ── All remaining endpoints require a valid token ────────────
$apiUser = pwa_require_auth();

switch ($action) {

    // ── Auth ─────────────────────────────────────────────────
    case 'me':
        require __DIR__ . '/actions/auth.php';
        pwa_handle_me($apiUser);
        break;

    // ── Student Endpoints ────────────────────────────────────
    case 'student-dashboard':
        pwa_require_role($apiUser, 'student');
        require __DIR__ . '/actions/student.php';
        pwa_student_dashboard($apiUser);
        break;

    case 'student-attendance':
        pwa_require_role($apiUser, 'student');
        require __DIR__ . '/actions/student.php';
        pwa_student_attendance($apiUser);
        break;

    case 'student-results':
        pwa_require_role($apiUser, 'student');
        require __DIR__ . '/actions/student.php';
        pwa_student_results($apiUser);
        break;

    case 'student-timetable':
        pwa_require_role($apiUser, 'student');
        require __DIR__ . '/actions/student.php';
        pwa_student_timetable($apiUser);
        break;

    // ── Parent Endpoints ─────────────────────────────────────
    case 'parent-dashboard':
        pwa_require_role($apiUser, 'parent');
        require __DIR__ . '/actions/parent.php';
        pwa_parent_dashboard($apiUser);
        break;

    case 'parent-children':
        pwa_require_role($apiUser, 'parent');
        require __DIR__ . '/actions/parent.php';
        pwa_parent_children($apiUser);
        break;

    case 'parent-student':
        pwa_require_role($apiUser, 'parent');
        require __DIR__ . '/actions/parent.php';
        pwa_parent_student($apiUser, $sub);
        break;

    case 'parent-fees':
        pwa_require_role($apiUser, 'parent');
        require __DIR__ . '/actions/parent.php';
        pwa_parent_fees($apiUser);
        break;

    // ── Shared Endpoints ─────────────────────────────────────
    case 'notices':
        require __DIR__ . '/actions/notices.php';
        pwa_notices($apiUser);
        break;

    case 'messages':
        require __DIR__ . '/actions/messages.php';
        pwa_messages_list($apiUser);
        break;

    case 'messages-send':
        require __DIR__ . '/actions/messages.php';
        pwa_messages_send($apiUser);
        break;

    default:
        pwa_error('Endpoint not found.', 404);
}
