<?php
/**
 * PWA API — Notices / Announcements
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// ─────────────────────────────────────────────────────────────
// GET /pwa-api/notices?page=1&limit=20
// ─────────────────────────────────────────────────────────────
function pwa_notices(array $apiUser): never
{
    $role  = $apiUser['role'];
    $page  = max(1, (int) ($_GET['page'] ?? 1));
    $limit = min(50, max(5, (int) ($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    // Audience filter: students see 'all' + 'students', parents see 'all' + 'parents'
    $audience = $role === 'student' ? "'all','students'" : "'all','parents'";

    $total = (int) db_fetch_value(
        "SELECT COUNT(*) FROM announcements WHERE audience IN ($audience) AND is_active = 1"
    );

    $notices = db_fetch_all(
        "SELECT a.id, a.title, a.content, a.audience,
                a.created_at, u.full_name AS author
         FROM announcements a
         LEFT JOIN users u ON u.id = a.created_by
         WHERE a.audience IN ($audience) AND a.is_active = 1
         ORDER BY a.created_at DESC
         LIMIT $limit OFFSET $offset"
    );

    pwa_json([
        'notices'    => $notices,
        'pagination' => [
            'page'       => $page,
            'limit'      => $limit,
            'total'      => $total,
            'total_pages' => (int) ceil($total / $limit),
        ],
    ]);
}
