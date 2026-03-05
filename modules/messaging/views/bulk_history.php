<?php
/**
 * Messaging — Bulk Message History (Admin Only)
 * Shows all bulk messages sent by admins
 */

$userId = auth_user_id();
$page   = max(1, input_int('page') ?: 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

$total = (int) db_fetch_value("SELECT COUNT(*) FROM msg_conversations WHERE type = 'bulk'");

$bulkMessages = db_fetch_all("
    SELECT c.id, c.subject, c.created_at, c.created_by,
           u.full_name AS sender_name,
           (SELECT COUNT(*) FROM msg_conversation_participants cp WHERE cp.conversation_id = c.id AND cp.user_id != c.created_by) AS recipient_count,
           (SELECT mm.body FROM msg_messages mm WHERE mm.conversation_id = c.id ORDER BY mm.created_at ASC LIMIT 1) AS body_preview
      FROM msg_conversations c
      JOIN users u ON c.created_by = u.id
     WHERE c.type = 'bulk'
     ORDER BY c.created_at DESC
     LIMIT $perPage OFFSET $offset
", []);

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
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Bulk Message History</h1>
        <a href="<?= url('messaging', 'bulk') ?>"
           class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm hover:bg-purple-700 font-medium inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Bulk Message
        </a>
    </div>

    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <?php if (empty($bulkMessages)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-dark-muted">
            <p class="font-medium">No bulk messages sent yet</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm responsive-table">
                <thead class="bg-gray-50 dark:bg-dark-bg border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-dark-muted">Subject</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-dark-muted">Sent By</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-dark-muted">Recipients</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-dark-muted">Date</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-dark-muted">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php foreach ($bulkMessages as $bm): ?>
                    <tr class="hover:bg-gray-50 dark:bg-dark-bg">
                        <td class="px-4 py-3" data-label="Subject">
                            <div class="font-medium text-gray-900 dark:text-dark-text"><?= e($bm['subject'] ?: '(No subject)') ?></div>
                            <div class="text-xs text-gray-500 dark:text-dark-muted mt-0.5"><?= e(truncate($bm['body_preview'] ?? '', 60)) ?></div>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-dark-muted" data-label="Sent By"><?= e($bm['sender_name']) ?></td>
                        <td class="px-4 py-3 text-center" data-label="Recipients">
                            <span class="bg-purple-100 text-purple-700 text-xs font-semibold px-2 py-0.5 rounded-full"><?= $bm['recipient_count'] ?></span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-dark-muted" data-label="Date"><?= format_datetime($bm['created_at']) ?></td>
                        <td class="px-4 py-3 text-center" data-label="Action">
                            <a href="<?= url('messaging', 'conversation', $bm['id']) ?>" class="text-primary-600 hover:text-primary-800 text-xs font-medium">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <?= pagination_html($pagination) ?>
</div>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
