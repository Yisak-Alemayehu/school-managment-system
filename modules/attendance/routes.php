<?php
/**
 * Attendance Module Routes
 */

$action = current_action();

switch ($action) {
    case 'index':
    case '':
        auth_require_permission('attendance.view');
        $pageTitle = 'Take Attendance';
        require __DIR__ . '/views/take.php';
        break;

    case 'save':
        auth_require_permission('attendance.manage');
        require __DIR__ . '/actions/save.php';
        break;

    case 'report':
        auth_require_permission('attendance.view');
        $pageTitle = 'Attendance Report';
        require __DIR__ . '/views/report.php';
        break;

    case 'student':
        auth_require_permission('attendance.view');
        $pageTitle = 'Student Attendance';
        require __DIR__ . '/views/student.php';
        break;

    default:
        http_response_code(404);
        require APP_ROOT . '/templates/errors/404.php';
}
