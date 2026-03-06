<?php
/**
 * Communication — Notifications List
 */
$pageTitle = 'Notifications';
$userId = current_user_id();
$page   = max(1, input_int('page') ?: 1);

$notifications = db_paginate("
    SELECT * FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
", [$userId], $page, 30);

$unreadCount = db_fetch_one("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0", [$userId]);

ob_start();
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-dark-text">Notifications</h1>
            <?php if ($unreadCount['cnt'] > 0): ?>
                <p class="text-sm text-primary-700"><?= $unreadCount['cnt'] ?> unread</p>
            <?php endif; ?>
        </div>
        <?php if ($unreadCount['cnt'] > 0): ?>
            <a href="<?= url('communication', 'notifications-read-all') ?>"
               class="px-4 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text hover:bg-gray-50 dark:bg-dark-bg">Mark All Read</a>
        <?php endif; ?>
    </div>

    <div class="bg-white dark:bg-dark-card rounded-xl shadow-sm border overflow-hidden">
        <?php if (empty($notifications['data'])): ?>
            <div class="p-8 text-center text-gray-500 dark:text-dark-muted">No notifications.</div>
        <?php else: ?>
            <div class="divide-y divide-gray-200 dark:divide-dark-border">
                <?php foreach ($notifications['data'] as $n): ?>
                    <div class="p-4 <?= !$n['is_read'] ? 'bg-blue-50' : '' ?> hover:bg-gray-50 dark:bg-dark-bg transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <?php if (!$n['is_read']): ?>
                                        <span class="w-2 h-2 rounded-full bg-primary-600 flex-shrink-0"></span>
                                    <?php endif; ?>
                                    <?php
                                    $icon = match($n['type']) {
                                        'message'  => '💬',
                                        'payment'  => '💰',
                                        'exam'     => '📝',
                                        'grade'    => '📊',
                                        'attendance' => '📋',
                                        default    => '🔔',
                                    };
                                    ?>
                                    <span><?= $icon ?></span>
                                    <span class="font-medium text-gray-900 dark:text-dark-text text-sm"><?= e($n['title']) ?></span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-dark-muted mt-1 ml-6"><?= e($n['message']) ?></p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 ml-6"><?= format_datetime($n['created_at']) ?></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if ($n['link']): ?>
                                    <a href="<?= e($n['link']) ?>" class="text-xs text-primary-700 hover:text-primary-900 font-medium">View</a>
                                <?php endif; ?>
                                <?php if (!$n['is_read']): ?>
                                    <a href="<?= url('communication', 'notification-read') ?>&id=<?= $n['id'] ?>"
                                       class="text-xs text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:text-gray-300">Mark read</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($notifications['last_page'] > 1): ?>
            <div class="px-6 py-3 border-t bg-gray-50 dark:bg-dark-bg">
                <?= render_pagination($notifications, url('communication', 'notifications')) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
