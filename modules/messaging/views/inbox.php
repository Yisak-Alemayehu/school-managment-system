<?php
/**
 * Messaging — Inbox
 * Shows all conversations where current user is a participant
 */

$userId  = auth_user_id();
$search  = input('search');
$page    = max(1, input_int('page') ?: 1);
$perPage = 20;

$searchFilter = '';
$searchParams = [];

if ($search) {
    $searchFilter = "AND (c.subject LIKE ? OR EXISTS (
        SELECT 1 FROM msg_messages mm WHERE mm.conversation_id = c.id AND mm.body LIKE ?
    ) OR EXISTS (
        SELECT 1 FROM msg_conversation_participants cp3
        JOIN users u3 ON cp3.user_id = u3.id
        WHERE cp3.conversation_id = c.id AND cp3.user_id != ? AND u3.full_name LIKE ?
    ))";
    $searchParams = ["%$search%", "%$search%", $userId, "%$search%"];
}

$offset = ($page - 1) * $perPage;

$total = (int) db_fetch_value("
    SELECT COUNT(DISTINCT c.id)
      FROM msg_conversations c
      JOIN msg_conversation_participants cp ON c.id = cp.conversation_id
     WHERE cp.user_id = ? AND cp.is_deleted = 0
           $searchFilter
", array_merge([$userId], $searchParams));

$conversations = db_fetch_all("
    SELECT c.id, c.type, c.subject, c.created_at,
           (SELECT mm.body FROM msg_messages mm WHERE mm.conversation_id = c.id ORDER BY mm.created_at DESC LIMIT 1) AS last_message,
           (SELECT mm.created_at FROM msg_messages mm WHERE mm.conversation_id = c.id ORDER BY mm.created_at DESC LIMIT 1) AS last_message_at,
           (SELECT mm.sender_id FROM msg_messages mm WHERE mm.conversation_id = c.id ORDER BY mm.created_at DESC LIMIT 1) AS last_sender_id,
           (SELECT COUNT(*) FROM msg_messages mm
              JOIN msg_message_status ms ON ms.message_id = mm.id AND ms.user_id = ?
             WHERE mm.conversation_id = c.id AND ms.status != 'read') AS unread_count,
           CASE
               WHEN c.type = 'solo' THEN (SELECT u2.full_name FROM msg_conversation_participants cp2
                   JOIN users u2 ON cp2.user_id = u2.id
                   WHERE cp2.conversation_id = c.id AND cp2.user_id != ? LIMIT 1)
               WHEN c.type = 'group' THEN (SELECT g.name FROM msg_groups g WHERE g.id = c.group_id)
               WHEN c.type = 'bulk' THEN c.subject
           END AS display_name,
           CASE
               WHEN c.type = 'solo' THEN (SELECT u2.avatar FROM msg_conversation_participants cp2
                   JOIN users u2 ON cp2.user_id = u2.id
                   WHERE cp2.conversation_id = c.id AND cp2.user_id != ? LIMIT 1)
               ELSE NULL
           END AS display_avatar
      FROM msg_conversations c
      JOIN msg_conversation_participants cp ON c.id = cp.conversation_id
     WHERE cp.user_id = ? AND cp.is_deleted = 0
           $searchFilter
     GROUP BY c.id
     ORDER BY last_message_at DESC
     LIMIT $perPage OFFSET $offset
", array_merge([$userId, $userId, $userId, $userId], $searchParams));

$lastPage = max(1, (int) ceil($total / $perPage));
$pagination = [
    'total' => $total, 'per_page' => $perPage, 'current_page' => $page,
    'last_page' => $lastPage, 'from' => $total > 0 ? $offset + 1 : 0,
    'to' => min($offset + $perPage, $total),
];

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Inbox</h1>
        <a href="<?= url('messaging', 'compose') ?>"
           class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Message
        </a>
    </div>

    <!-- Search -->
    <form method="GET" action="<?= url('messaging', 'inbox') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
        <div class="flex gap-3">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search messages…"
                   class="flex-1 px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">Search</button>
            <?php if ($search): ?>
            <a href="<?= url('messaging', 'inbox') ?>" class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 font-medium">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Conversation List -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden divide-y divide-gray-100 dark:divide-dark-border">
        <?php if (empty($conversations)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-dark-muted">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            <p class="font-medium">No messages yet</p>
            <p class="text-sm mt-1">Start a conversation by composing a new message.</p>
        </div>
        <?php else: ?>
            <?php foreach ($conversations as $conv): ?>
            <a href="<?= url('messaging', 'conversation', $conv['id']) ?>"
               class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:bg-dark-bg transition-colors <?= $conv['unread_count'] > 0 ? 'bg-blue-50/50' : '' ?>">
                <!-- Avatar -->
                <div class="flex-shrink-0">
                    <?php if ($conv['display_avatar']): ?>
                        <img src="<?= upload_url($conv['display_avatar']) ?>" class="w-10 h-10 rounded-full object-cover" alt="">
                    <?php elseif ($conv['type'] === 'group'): ?>
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                    <?php elseif ($conv['type'] === 'bulk'): ?>
                        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        </div>
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                            <span class="text-primary-700 font-semibold text-sm"><?= strtoupper(mb_substr($conv['display_name'] ?? '?', 0, 1)) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-dark-text truncate <?= $conv['unread_count'] > 0 ? 'font-bold' : '' ?>">
                            <?= e($conv['display_name'] ?: 'Unknown') ?>
                        </h3>
                        <span class="text-xs text-gray-500 dark:text-dark-muted flex-shrink-0 ml-2"><?= $conv['last_message_at'] ? time_ago($conv['last_message_at']) : '' ?></span>
                    </div>
                    <div class="flex items-center justify-between mt-0.5">
                        <p class="text-xs text-gray-500 dark:text-dark-muted truncate <?= $conv['unread_count'] > 0 ? 'font-semibold text-gray-700 dark:text-gray-300' : '' ?>">
                            <?= e(truncate($conv['last_message'] ?? 'No messages yet', 80)) ?>
                        </p>
                        <?php if ($conv['unread_count'] > 0): ?>
                        <span class="ml-2 bg-primary-600 text-white text-xs font-bold px-1.5 py-0.5 rounded-full flex-shrink-0"><?= $conv['unread_count'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Type badge -->
                <?php if ($conv['type'] !== 'solo'): ?>
                <span class="text-xs px-2 py-0.5 rounded-full flex-shrink-0 <?= $conv['type'] === 'bulk' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700' ?>">
                    <?= ucfirst($conv['type']) ?>
                </span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?= pagination_html($pagination) ?>
</div>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
