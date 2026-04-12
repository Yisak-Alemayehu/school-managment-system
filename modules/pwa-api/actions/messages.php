<?php
/**
 * PWA API — Messaging
 * Uses the core `messages` table (school → student/parent communication).
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// ─────────────────────────────────────────────────────────────
// GET /pwa-api/messages?page=1
// ─────────────────────────────────────────────────────────────
function pwa_messages_list(array $apiUser): never
{
    $userId = (int) $apiUser['user_id'];
    $page   = max(1, (int) ($_GET['page'] ?? 1));
    $limit  = 20;
    $offset = ($page - 1) * $limit;

    // Messages sent to this user OR from this user
    $messages = db_fetch_all(
        "SELECT m.id, m.subject, m.body, m.is_read, m.created_at,
                sender.full_name AS sender_name,
                sender.id AS sender_id,
                m.receiver_id
         FROM messages m
         LEFT JOIN users sender ON sender.id = m.sender_id
         WHERE m.receiver_id = ? OR m.sender_id = ?
         ORDER BY m.created_at DESC
         LIMIT $limit OFFSET $offset",
        [$userId, $userId]
    );

    $unreadCount = (int) db_fetch_value(
        "SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0",
        [$userId]
    );

    // Mark fetched messages for this user as read
    if (!empty($messages)) {
        $ids = array_map(fn($m) => (int) $m['id'], array_filter(
            $messages, fn($m) => (int) $m['receiver_id'] === $userId && !$m['is_read']
        ));
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            db_query(
                "UPDATE messages SET is_read = 1 WHERE id IN ($placeholders)",
                $ids
            );
        }
    }

    pwa_json([
        'messages'     => $messages,
        'unread_count' => $unreadCount,
        'page'         => $page,
    ]);
}

// ─────────────────────────────────────────────────────────────
// POST /pwa-api/messages-send
// Body: { "receiver_id": int, "subject": "...", "body": "..." }
// ─────────────────────────────────────────────────────────────
function pwa_messages_send(array $apiUser): never
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        pwa_error('Method not allowed.', 405);
    }

    $senderId   = (int) $apiUser['user_id'];
    $body       = pwa_request_json();
    $receiverId = pwa_int($body, 'receiver_id');
    $subject    = pwa_str($body, 'subject');
    $msgBody    = pwa_str($body, 'body');

    if (!$receiverId || $msgBody === '') {
        pwa_error('receiver_id and body are required.');
    }

    if (strlen($msgBody) > 5000) {
        pwa_error('Message body too long (max 5000 chars).');
    }

    // Verify receiver exists
    $receiver = db_fetch_one(
        "SELECT id, full_name FROM users WHERE id = ? AND deleted_at IS NULL",
        [$receiverId]
    );
    if (!$receiver) {
        pwa_error('Recipient not found.', 404);
    }

    // Prevent self-messaging
    if ($receiverId === $senderId) {
        pwa_error('Cannot send message to yourself.');
    }

    $msgId = db_insert('messages', [
        'sender_id'   => $senderId,
        'receiver_id' => $receiverId,
        'subject'     => $subject ?: '(No Subject)',
        'body'        => $msgBody,
        'is_read'     => 0,
    ]);

    pwa_json([
        'message' => 'Message sent successfully.',
        'id'      => $msgId,
    ], 201);
}
