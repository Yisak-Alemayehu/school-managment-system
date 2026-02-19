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
