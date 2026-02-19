<?php
/**
 * Dashboard Module — Routes
 */

auth_require();

$action = current_action();

switch ($action) {
    case 'index':
    default:
        $pageTitle = 'Dashboard';
        require __DIR__ . '/views/index.php';
        break;
}
