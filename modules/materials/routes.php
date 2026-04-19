<?php
/**
 * Academic Materials Module — Routes
 * URL pattern: /materials/{action}/{id}
 *
 * Manages textbook uploads with Grade → Subject → Book Type hierarchy.
 */

// Serve action allows portal access; everything else requires admin auth
$action = current_action();
if ($action !== 'serve') {
    auth_require();
}

switch ($action) {

    // ── List all materials ───────────────────────────────────────────────────
    case 'index':
        auth_require_permission('materials.view');
        $pageTitle = 'Academic Materials';
        require __DIR__ . '/views/index.php';
        break;

    // ── Upload new material ──────────────────────────────────────────────────
    case 'create':
        auth_require_permission('materials.create');
        if (is_post()) {
            require __DIR__ . '/actions/store.php';
        } else {
            $pageTitle = 'Upload Material';
            require __DIR__ . '/views/create.php';
        }
        break;

    // ── Edit material ────────────────────────────────────────────────────────
    case 'edit':
        auth_require_permission('materials.edit');
        $id = route_id();
        if (!$id) { redirect(url('materials')); }
        if (is_post()) {
            require __DIR__ . '/actions/update.php';
        } else {
            $pageTitle = 'Edit Material';
            require __DIR__ . '/views/edit.php';
        }
        break;

    // ── View single material detail ──────────────────────────────────────────
    case 'view':
        auth_require_permission('materials.view');
        $id = route_id();
        if (!$id) { redirect(url('materials')); }
        $pageTitle = 'View Material';
        require __DIR__ . '/views/view.php';
        break;

    // ── Delete material ──────────────────────────────────────────────────────
    case 'delete':
        auth_require_permission('materials.delete');
        if (is_post()) {
            require __DIR__ . '/actions/delete.php';
        }
        break;

    // ── Built-in PDF Viewer ──────────────────────────────────────────────────
    case 'pdf-viewer':
        auth_require_permission('materials.view');
        $id = route_id();
        if (!$id) { redirect(url('materials')); }
        $pageTitle = 'PDF Viewer';
        require __DIR__ . '/views/pdf_viewer.php';
        break;

    // ── Serve material file (download/inline) ────────────────────────────────
    case 'serve':
        // Allow both admin users and portal students to access files
        $id = route_id();
        if (!$id) { http_response_code(404); exit; }

        // Load portal session helpers (not auto-loaded outside portal module)
        require_once MODULES_PATH . '/portal/_session.php';

        if (portal_check()) {
            // Portal user: verify material belongs to student's class
            $student = portal_student();
            $classId = $student['class_id'] ?? null;
            if (!$classId) { http_response_code(403); exit; }
            $check = db_fetch_one(
                "SELECT id FROM academic_materials WHERE id = ? AND class_id = ? AND deleted_at IS NULL AND status = 'active'",
                [$id, $classId]
            );
            if (!$check) { http_response_code(403); exit; }
        } else {
            auth_require_permission('materials.view');
        }
        require __DIR__ . '/actions/serve.php';
        break;

    default:
        router_not_found();
}
