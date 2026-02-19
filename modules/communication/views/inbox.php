<?php
/**
 * Communication — Inbox (received messages)
 */
$pageTitle = 'Inbox';
$userId = current_user_id();
$page = max(1, input_int('page') ?: 1);

$messages = db_paginate("
    SELECT m.*, u.full_name AS sender_name
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE m.receiver_id = ? AND m.deleted_by_receiver = 0
    ORDER BY m.created_at DESC
", [$userId], $page, 20);

// Unread count
$unread = db_fetch_one("SELECT COUNT(*) AS cnt FROM messages WHERE receiver_id = ? AND is_read = 0 AND deleted_by_receiver = 0", [$userId]);

ob_start();
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Inbox</h1>
            <?php if ($unread['cnt'] > 0): ?>
                <p class="text-sm text-primary-700"><?= $unread['cnt'] ?> unread message(s)</p>
            <?php endif; ?>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('communication', 'message-compose') ?>"
               class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">Compose</a>
            <a href="<?= url('communication', 'sent') ?>"
               class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Sent</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <?php if (empty($messages['data'])): ?>
            <div class="p-8 text-center text-gray-500">Your inbox is empty.</div>
        <?php else: ?>
            <div class="divide-y divide-gray-200">
                <?php foreach ($messages['data'] as $m): ?>
                    <a href="<?= url('communication', 'message-view') ?>&id=<?= $m['id'] ?>"
                       class="block p-4 hover:bg-gray-50 transition <?= !$m['is_read'] ? 'bg-blue-50' : '' ?>">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <?php if (!$m['is_read']): ?>
                                        <span class="w-2 h-2 rounded-full bg-primary-600 flex-shrink-0"></span>
                                    <?php endif; ?>
                                    <span class="font-medium text-gray-900 <?= !$m['is_read'] ? 'font-bold' : '' ?>"><?= e($m['sender_name']) ?></span>
                                </div>
                                <p class="text-sm font-semibold text-gray-800 mt-0.5 truncate"><?= e($m['subject']) ?></p>
                                <p class="text-xs text-gray-500 mt-0.5 truncate"><?= e(substr(strip_tags($m['body']), 0, 100)) ?></p>
                            </div>
                            <span class="text-xs text-gray-400 whitespace-nowrap"><?= format_datetime($m['created_at']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($messages['last_page'] > 1): ?>
            <div class="px-6 py-3 border-t bg-gray-50">
                <?= render_pagination($messages, url('communication', 'inbox')) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
