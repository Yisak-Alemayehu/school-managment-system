<?php
/**
 * Communication — Sent Messages
 */
$pageTitle = 'Sent Messages';
$userId = current_user_id();
$page   = max(1, input_int('page') ?: 1);

$messages = db_paginate("
    SELECT m.*, u.full_name AS receiver_name
    FROM messages m
    JOIN users u ON u.id = m.receiver_id
    WHERE m.sender_id = ? AND m.deleted_by_sender = 0
    ORDER BY m.created_at DESC
", [$userId], $page, 20);

ob_start();
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Sent Messages</h1>
        <div class="flex gap-2">
            <a href="<?= url('communication', 'message-compose') ?>"
               class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">Compose</a>
            <a href="<?= url('communication', 'inbox') ?>"
               class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Inbox</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <?php if (empty($messages['data'])): ?>
            <div class="p-8 text-center text-gray-500">No sent messages.</div>
        <?php else: ?>
            <div class="divide-y divide-gray-200">
                <?php foreach ($messages['data'] as $m): ?>
                    <a href="<?= url('communication', 'message-view') ?>&id=<?= $m['id'] ?>"
                       class="block p-4 hover:bg-gray-50 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <span class="font-medium text-gray-900">To: <?= e($m['receiver_name']) ?></span>
                                <p class="text-sm font-semibold text-gray-800 mt-0.5 truncate"><?= e($m['subject']) ?></p>
                                <p class="text-xs text-gray-500 mt-0.5 truncate"><?= e(substr(strip_tags($m['body']), 0, 100)) ?></p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="text-xs text-gray-400"><?= format_datetime($m['created_at']) ?></span>
                                <?php if ($m['is_read']): ?>
                                    <div class="text-xs text-green-600">Read</div>
                                <?php else: ?>
                                    <div class="text-xs text-gray-400">Unread</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($messages['last_page'] > 1): ?>
            <div class="px-6 py-3 border-t bg-gray-50">
                <?= render_pagination($messages, url('communication', 'sent')) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
