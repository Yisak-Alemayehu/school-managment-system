<?php
/**
 * Settings Module Routes
 */

$action = $_GET['action'] ?? 'general';

switch ($action) {
    case 'general':
        require_permission('manage_settings');
        require __DIR__ . '/views/general.php';
        break;
    case 'general-save':
        require_permission('manage_settings');
        require __DIR__ . '/actions/general_save.php';
        break;
    case 'audit-logs':
        require_permission('manage_settings');
        require __DIR__ . '/views/audit_logs.php';
        break;
    case 'backup':
        require_permission('super_admin');
        require __DIR__ . '/views/backup.php';
        break;
    default:
        http_response_code(404);
        require ROOT_PATH . '/templates/errors/404.php';
}
