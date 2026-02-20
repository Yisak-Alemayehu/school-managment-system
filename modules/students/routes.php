<?php
/**
 * Students Module — Routes
 * Admission, profiles, enrollment, promotion, documents
 */

auth_require();

$action = current_action();

switch ($action) {
    case 'index':
        auth_require_permission('students.view');
        $pageTitle = 'Students';
        require __DIR__ . '/views/index.php';
        break;

    case 'create':
        auth_require_permission('students.create');
        if (is_post()) {
            require __DIR__ . '/actions/store.php';
        } else {
            $pageTitle = 'Student Admission';
            require __DIR__ . '/views/create.php';
        }
        break;

    case 'edit':
        auth_require_permission('students.edit');
        $id = route_id();
        if (!$id) { redirect(url('students')); }
        if (is_post()) {
            require __DIR__ . '/actions/update.php';
        } else {
            $pageTitle = 'Edit Student';
            require __DIR__ . '/views/edit.php';
        }
        break;

    case 'view':
        auth_require_permission('students.view');
        $id = route_id();
        if (!$id) { redirect(url('students')); }
        $pageTitle = 'Student Profile';
        require __DIR__ . '/views/view.php';
        break;

    case 'delete':
        auth_require_permission('students.delete');
        if (is_post()) {
            require __DIR__ . '/actions/delete.php';
        }
        break;

    case 'promote':
        auth_require_permission('students.promote');
        if (is_post()) {
            require __DIR__ . '/actions/promote.php';
        } else {
            $pageTitle = 'Student Promotion';
            require __DIR__ . '/views/promote.php';
        }
        break;

    // ── Admission (alias of create) ──────────────────────────────────────
    case 'admission':
        auth_require_permission('students.create');
        if (is_post()) {
            require __DIR__ . '/actions/store.php';
        } else {
            $pageTitle = 'Student Admission';
            require __DIR__ . '/views/create.php';
        }
        break;

    // ── Assign Roll Numbers ──────────────────────────────────────────────
    case 'roll-numbers':
        auth_require_permission('students.edit');
        if (is_post()) {
            require __DIR__ . '/actions/save_roll_numbers.php';
        } else {
            $pageTitle = 'Assign Roll Numbers';
            require __DIR__ . '/views/roll_numbers.php';
        }
        break;

    // ── Student Details ──────────────────────────────────────────────────
    case 'details':
        auth_require_permission('students.view');
        $pageTitle = 'Student Details';
        require __DIR__ . '/views/details.php';
        break;

    // ── Generate ID Cards ────────────────────────────────────────────────
    case 'id-cards':
        auth_require_permission('students.view');
        $pageTitle = 'Generate ID Cards';
        require __DIR__ . '/views/id_cards.php';
        break;

    // ── Generate Username & Password ─────────────────────────────────────
    case 'credentials':
        auth_require_permission('students.edit');
        if (is_post()) {
            require __DIR__ . '/actions/generate_credentials.php';
        } else {
            $pageTitle = 'Generate Username & Password';
            require __DIR__ . '/views/credentials.php';
        }
        break;

    // ── Reset Student Password ───────────────────────────────────────────
    case 'reset-password':
        auth_require_permission('students.edit');
        if (is_post()) {
            require __DIR__ . '/actions/reset_student_password.php';
        } else {
            $pageTitle = 'Reset Student Password';
            require __DIR__ . '/views/reset_password.php';
        }
        break;

    // ── Bulk Import ──────────────────────────────────────────────────────
    case 'bulk-import':
        auth_require_permission('students.create');
        if (is_post()) {
            require __DIR__ . '/actions/bulk_import.php';
        } else {
            $pageTitle = 'Add Bulk Data';
            require __DIR__ . '/views/bulk_import.php';
        }
        break;

    // ── Sample CSV download ──────────────────────────────────────────────
    case 'sample-csv':
        auth_require_permission('students.create');
        require __DIR__ . '/actions/sample_csv.php';
        break;

    case 'enroll':
        auth_require_permission('students.create');
        if (is_post()) {
            require __DIR__ . '/actions/enroll.php';
        }
        break;

    case 'export':
        auth_require_permission('students.view');
        require __DIR__ . '/actions/export.php';
        break;

    default:
        redirect(url('students'));
        break;
}
