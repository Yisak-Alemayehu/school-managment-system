<?php
/**
 * Settings Module Routes
 */

$action = $_GET['action'] ?? 'general';

switch ($action) {
    case 'general':
        auth_require_permission('settings.view');
        require __DIR__ . '/views/general.php';
        break;
    case 'general-save':
        auth_require_permission('settings.update');
        require __DIR__ . '/actions/general_save.php';
        break;
    case 'audit-logs':
        auth_require_permission('audit_logs.view');
        require __DIR__ . '/views/audit_logs.php';
        break;
    case 'backup':
        if (!auth_is_super_admin()) { http_response_code(403); include TEMPLATES_PATH . '/errors/403.php'; exit; }
        require __DIR__ . '/views/backup.php';
        break;
    default:
        http_response_code(404);
        require ROOT_PATH . '/templates/errors/404.php';
}
