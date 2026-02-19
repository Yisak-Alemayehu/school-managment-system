<?php
/**
 * Communication Module Routes
 * Handles: announcements, messages, notifications
 */

$action = $_GET['action'] ?? 'announcements';

switch ($action) {
    // ── Announcements ──
    case 'announcements':
        require __DIR__ . '/views/announcements.php';
        break;
    case 'announcement-create':
    case 'announcement-edit':
        require_permission('manage_communication');
        require __DIR__ . '/views/announcement_form.php';
        break;
    case 'announcement-save':
        require_permission('manage_communication');
        require __DIR__ . '/actions/announcement_save.php';
        break;
    case 'announcement-view':
        require __DIR__ . '/views/announcement_view.php';
        break;
    case 'announcement-delete':
        require_permission('manage_communication');
        require __DIR__ . '/actions/announcement_delete.php';
        break;

    // ── Messages ──
    case 'messages':
    case 'inbox':
        require __DIR__ . '/views/inbox.php';
        break;
    case 'message-compose':
        require __DIR__ . '/views/compose.php';
        break;
    case 'message-send':
        require __DIR__ . '/actions/message_send.php';
        break;
    case 'message-view':
        require __DIR__ . '/views/message_view.php';
        break;
    case 'sent':
        require __DIR__ . '/views/sent.php';
        break;

    // ── Notifications ──
    case 'notifications':
        require __DIR__ . '/views/notifications.php';
        break;
    case 'notification-read':
        require __DIR__ . '/actions/notification_read.php';
        break;
    case 'notifications-read-all':
        require __DIR__ . '/actions/notification_read_all.php';
        break;

    default:
        http_response_code(404);
        require ROOT_PATH . '/templates/errors/404.php';
}
