<?php
/**
 * Users Module — Routes
 * CRUD for user management (admin only)
 */

auth_require();
auth_require_permission('users.view');

$action = current_action();

switch ($action) {
    case 'index':
        $pageTitle = 'User Management';
        require __DIR__ . '/views/index.php';
        break;

    case 'create':
        auth_require_permission('users.create');
        if (is_post()) {
            require __DIR__ . '/actions/store.php';
        } else {
            $pageTitle = 'Add New User';
            require __DIR__ . '/views/create.php';
        }
        break;

    case 'edit':
        auth_require_permission('users.edit');
        $id = route_id();
        if (!$id) { redirect(url('users')); }
        if (is_post()) {
            require __DIR__ . '/actions/update.php';
        } else {
            $pageTitle = 'Edit User';
            require __DIR__ . '/views/edit.php';
        }
        break;

    case 'view':
        $id = route_id();
        if (!$id) { redirect(url('users')); }
        $pageTitle = 'User Details';
        require __DIR__ . '/views/view.php';
        break;

    case 'delete':
        auth_require_permission('users.delete');
        if (is_post()) {
            require __DIR__ . '/actions/delete.php';
        }
        break;

    case 'toggle-status':
        auth_require_permission('users.edit');
        if (is_post()) {
            require __DIR__ . '/actions/toggle_status.php';
        }
        break;

    default:
        redirect(url('users'));
        break;
}
