<?php
/**
 * Messaging — Sent Messages
 * Shows conversations where current user has sent messages
 */

$userId  = auth_user_id();
$search  = input('search');
$page    = max(1, input_int('page') ?: 1);
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = ["cp.user_id = ?", "cp.is_deleted = 0"];
$params = [$userId];

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
$whereClause = implode(' AND ', $where);

// Only show conversations where user has actually sent a message
$total = (int) db_fetch_value("
    SELECT COUNT(DISTINCT c.id)
      FROM msg_conversations c
      JOIN msg_conversation_participants cp ON c.id = cp.conversation_id
      JOIN msg_messages m ON m.conversation_id = c.id AND m.sender_id = ?
     WHERE $whereClause
           $searchFilter
", array_merge([$userId], $params, $searchParams));

$conversations = db_fetch_all("
    SELECT c.id, c.type, c.subject,
           (SELECT mm.body FROM msg_messages mm WHERE mm.conversation_id = c.id ORDER BY mm.created_at DESC LIMIT 1) AS last_message,
           (SELECT mm.created_at FROM msg_messages mm WHERE mm.conversation_id = c.id ORDER BY mm.created_at DESC LIMIT 1) AS last_message_at,
           CASE
               WHEN c.type = 'solo' THEN (SELECT u3.full_name FROM msg_conversation_participants cp3
                   JOIN users u3 ON cp3.user_id = u3.id WHERE cp3.conversation_id = c.id AND cp3.user_id != ? LIMIT 1)
               WHEN c.type = 'group' THEN (SELECT g.name FROM msg_groups g WHERE g.id = c.group_id)
               WHEN c.type = 'bulk' THEN c.subject
           END AS display_name,
           (SELECT COUNT(*) FROM msg_conversation_participants cp4 WHERE cp4.conversation_id = c.id) AS participant_count
      FROM msg_conversations c
      JOIN msg_conversation_participants cp ON c.id = cp.conversation_id
      JOIN msg_messages m2 ON m2.conversation_id = c.id AND m2.sender_id = ?
     WHERE $whereClause
           $searchFilter
     GROUP BY c.id
     ORDER BY last_message_at DESC
     LIMIT $perPage OFFSET $offset
", array_merge([$userId, $userId], $params, $searchParams));

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
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Sent Messages</h1>
        <a href="<?= url('messaging', 'compose') ?>"
           class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Message
        </a>
    </div>

    <!-- Search -->
    <form method="GET" action="<?= url('messaging', 'sent') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
        <div class="flex gap-3">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search sent messages…"
                   class="flex-1 px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">Search</button>
            <?php if ($search): ?>
            <a href="<?= url('messaging', 'sent') ?>" class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 font-medium">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Sent List -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden divide-y divide-gray-100 dark:divide-dark-border">
        <?php if (empty($conversations)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-dark-muted">
            <p class="font-medium">No sent messages</p>
            <p class="text-sm mt-1">Messages you send will appear here.</p>
        </div>
        <?php else: ?>
            <?php foreach ($conversations as $conv): ?>
            <a href="<?= url('messaging', 'conversation', $conv['id']) ?>"
               class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:bg-dark-bg transition-colors">
                <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-dark-card2 flex items-center justify-center flex-shrink-0">
                    <span class="text-gray-600 dark:text-dark-muted font-semibold text-sm"><?= strtoupper(mb_substr($conv['display_name'] ?? '?', 0, 1)) ?></span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-dark-text truncate"><?= e($conv['display_name'] ?: 'Unknown') ?></h3>
                        <span class="text-xs text-gray-500 dark:text-dark-muted flex-shrink-0 ml-2"><?= $conv['last_message_at'] ? time_ago($conv['last_message_at']) : '' ?></span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-dark-muted truncate mt-0.5"><?= e(truncate($conv['last_message'] ?? '', 80)) ?></p>
                </div>
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
