<?php $pageTitle = 'Notifications'; $currentPage = 'notifications'; include __DIR__ . '/layout_top.php'; ?>

<div class="bg-white rounded-xl border border-gray-100 p-5 mb-6">
    <h3 class="text-sm font-bold text-gray-900 mb-4">Send Notification</h3>
    <form method="POST" action="<?= base_url('admin/send-notification') ?>">
        <?= csrf_field() ?>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Recipient</label>
                <select name="user_id" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white">
                    <option value="">Select User</option>
                    <?php foreach ($usersList as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                <input type="text" name="title" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
        </div>
        <div class="mt-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Message</label>
            <textarea name="message" required rows="3" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm"></textarea>
        </div>
        <button type="submit" class="mt-3 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">Send Notification</button>
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="text-sm font-bold text-gray-900">Recent Notifications Sent</h3>
    </div>
    <div class="divide-y divide-gray-50">
        <?php foreach ($notifications as $notif): ?>
        <div class="px-5 py-3 hover:bg-gray-50/50 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm font-medium text-gray-900"><?= e($notif['title']) ?></span>
                    <span class="text-xs text-gray-500 ml-2">to <?= e($notif['user_name'] ?? 'User') ?></span>
                </div>
                <span class="text-xs <?= $notif['is_read'] ? 'text-green-600' : 'text-gray-400' ?>"><?= $notif['is_read'] ? 'Read' : 'Unread' ?></span>
            </div>
            <p class="text-sm text-gray-600 mt-1"><?= e($notif['message']) ?></p>
            <span class="text-xs text-gray-400"><?= time_ago($notif['created_at']) ?></span>
        </div>
        <?php endforeach; ?>
        <?php if (empty($notifications)): ?>
        <p class="text-sm text-gray-500 text-center py-8">No notifications yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
