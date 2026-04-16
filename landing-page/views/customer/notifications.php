<?php $pageTitle = 'Notifications'; $currentPage = 'notifications'; include __DIR__ . '/layout_top.php'; ?>

<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <?php if (!empty($notifications)): ?>
    <div class="divide-y divide-gray-50">
        <?php foreach ($notifications as $notif): ?>
        <div class="px-5 py-4 <?= $notif['is_read'] ? '' : 'bg-primary-50/30' ?> hover:bg-gray-50/50 transition-colors">
            <div class="flex items-start gap-3">
                <div class="w-2 h-2 rounded-full mt-2 flex-shrink-0 <?= $notif['is_read'] ? 'bg-gray-300' : 'bg-primary-500' ?>"></div>
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-gray-900"><?= e($notif['title']) ?></h4>
                        <span class="text-xs text-gray-400"><?= time_ago($notif['created_at']) ?></span>
                    </div>
                    <p class="text-sm text-gray-600 mt-1"><?= e($notif['message']) ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="p-12 text-center">
        <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
        <h3 class="text-sm font-bold text-gray-900 mb-1">No Notifications</h3>
        <p class="text-sm text-gray-500">You're all caught up!</p>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
