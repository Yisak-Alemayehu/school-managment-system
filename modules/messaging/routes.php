<?php
/**
 * Messaging Module — Routes
 * Solo messaging, Bulk messaging, Group messaging, AJAX API
 */

auth_require();

$action = current_action();
$GLOBALS['_is_admin'] = auth_is_super_admin() || auth_has_role('admin');

switch ($action) {
    // ══════════════════════════════════════════════════════════
    // INBOX / CONVERSATIONS
    // ══════════════════════════════════════════════════════════
    case 'index':
    case 'inbox':
        $pageTitle = 'Messaging — Inbox';
        require __DIR__ . '/views/inbox.php';
        break;

    case 'sent':
        $pageTitle = 'Messaging — Sent';
        require __DIR__ . '/views/sent.php';
        break;

    case 'conversation':
        $pageTitle = 'Conversation';
        require __DIR__ . '/views/conversation.php';
        break;

    // ══════════════════════════════════════════════════════════
    // SOLO MESSAGING
    // ══════════════════════════════════════════════════════════
    case 'compose':
        $pageTitle = 'New Message';
        require __DIR__ . '/views/compose.php';
        break;

    case 'send':
        if (is_post()) { require __DIR__ . '/actions/send.php'; }
        else { redirect('messaging', 'compose'); }
        break;

    case 'reply':
        if (is_post()) { require __DIR__ . '/actions/reply.php'; }
        else { redirect('messaging', 'inbox'); }
        break;

    // ══════════════════════════════════════════════════════════
    // BULK MESSAGING (Admin only)
    // ══════════════════════════════════════════════════════════
    case 'bulk':
        if (!$GLOBALS['_is_admin']) {
            http_response_code(403);
            require ROOT_PATH . '/templates/errors/403.php';
            break;
        }
        $pageTitle = 'Bulk Message';
        require __DIR__ . '/views/bulk.php';
        break;

    case 'bulk-send':
        if (!$GLOBALS['_is_admin']) {
            http_response_code(403);
            require ROOT_PATH . '/templates/errors/403.php';
            break;
        }
        if (is_post()) { require __DIR__ . '/actions/bulk_send.php'; }
        else { redirect('messaging', 'bulk'); }
        break;

    case 'bulk-history':
        if (!$GLOBALS['_is_admin']) {
            http_response_code(403);
            require ROOT_PATH . '/templates/errors/403.php';
            break;
        }
        $pageTitle = 'Bulk Message History';
        require __DIR__ . '/views/bulk_history.php';
        break;

    // ══════════════════════════════════════════════════════════
    // GROUP MESSAGING (Students only)
    // ══════════════════════════════════════════════════════════
    case 'groups':
        if (!auth_has_role('student')) {
            http_response_code(403);
            require ROOT_PATH . '/templates/errors/403.php';
            break;
        }
        $pageTitle = 'My Groups';
        require __DIR__ . '/views/groups.php';
        break;

    case 'group-create':
        if (!auth_has_role('student')) {
            http_response_code(403);
            require ROOT_PATH . '/templates/errors/403.php';
            break;
        }
        if (is_post()) { require __DIR__ . '/actions/group_create.php'; }
        else {
            $pageTitle = 'Create Group';
            require __DIR__ . '/views/group_form.php';
        }
        break;

    case 'group-detail':
        if (!auth_has_role('student')) {
            http_response_code(403);
            require ROOT_PATH . '/templates/errors/403.php';
            break;
        }
        $pageTitle = 'Group Chat';
        require __DIR__ . '/views/group_detail.php';
        break;

    case 'group-edit':
        if (!auth_has_role('student')) {
            http_response_code(403);
            require ROOT_PATH . '/templates/errors/403.php';
            break;
        }
        if (is_post()) { require __DIR__ . '/actions/group_edit.php'; }
        else { redirect('messaging', 'groups'); }
        break;

    case 'group-delete':
        if (!auth_has_role('student')) {
            http_response_code(403);
            require ROOT_PATH . '/templates/errors/403.php';
            break;
        }
        if (is_post()) { require __DIR__ . '/actions/group_delete.php'; }
        else { redirect('messaging', 'groups'); }
        break;

    case 'group-add-member':
        if (!auth_has_role('student')) {
            http_response_code(403);
            require ROOT_PATH . '/templates/errors/403.php';
            break;
        }
        if (is_post()) { require __DIR__ . '/actions/group_add_member.php'; }
        else { redirect('messaging', 'groups'); }
        break;

    case 'group-remove-member':
        if (!auth_has_role('student')) {
            http_response_code(403);
            require ROOT_PATH . '/templates/errors/403.php';
            break;
        }
        if (is_post()) { require __DIR__ . '/actions/group_remove_member.php'; }
        else { redirect('messaging', 'groups'); }
        break;

    case 'group-send':
        if (!auth_has_role('student')) {
            http_response_code(403);
            require ROOT_PATH . '/templates/errors/403.php';
            break;
        }
        if (is_post()) { require __DIR__ . '/actions/group_send.php'; }
        else { redirect('messaging', 'groups'); }
        break;

    // ══════════════════════════════════════════════════════════
    // AJAX API ENDPOINTS
    // ══════════════════════════════════════════════════════════
    case 'api-search-users':
        require __DIR__ . '/actions/api_search_users.php';
        break;

    case 'api-messages':
        require __DIR__ . '/actions/api_messages.php';
        break;

    case 'api-mark-read':
        if (is_post()) { require __DIR__ . '/actions/api_mark_read.php'; }
        else { json_response(['error' => 'POST required'], 405); }
        break;

    case 'api-upload':
        if (is_post()) { require __DIR__ . '/actions/api_upload.php'; }
        else { json_response(['error' => 'POST required'], 405); }
        break;

    case 'api-unread-count':
        require __DIR__ . '/actions/api_unread_count.php';
        break;

    case 'delete':
        if (is_post()) { require __DIR__ . '/actions/delete_conversation.php'; }
        else { redirect('messaging', 'inbox'); }
        break;

    default:
        http_response_code(404);
        require ROOT_PATH . '/templates/errors/404.php';
}
