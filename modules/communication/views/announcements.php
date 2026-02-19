<?php
/**
 * Communication — Announcements List
 */
$pageTitle = 'Announcements';
$user = current_user();
$page = max(1, input_int('page') ?: 1);

$canManage = has_permission('manage_communication');

// Everyone can see published announcements; admins see all
if ($canManage) {
    $announcements = db_paginate("
        SELECT a.*, u.full_name AS author_name
        FROM announcements a
        LEFT JOIN users u ON u.id = a.created_by
        ORDER BY a.is_pinned DESC, a.publish_date DESC
    ", [], $page, 15);
} else {
    $announcements = db_paginate("
        SELECT a.*, u.full_name AS author_name
        FROM announcements a
        LEFT JOIN users u ON u.id = a.created_by
        WHERE a.status = 'published' AND a.publish_date <= NOW()
          AND (a.target_audience = 'all' OR a.target_audience = ?)
        ORDER BY a.is_pinned DESC, a.publish_date DESC
    ", [$user['role_slug']], $page, 15);
}

ob_start();
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Announcements</h1>
        <?php if ($canManage): ?>
            <a href="<?= url('communication', 'announcement-create') ?>"
               class="inline-flex items-center gap-2 px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
                + New Announcement
            </a>
        <?php endif; ?>
    </div>

    <div class="space-y-4">
        <?php if (empty($announcements['data'])): ?>
            <div class="bg-white rounded-xl shadow-sm border p-8 text-center text-gray-500">
                No announcements to display.
            </div>
        <?php else: ?>
            <?php foreach ($announcements['data'] as $a): ?>
                <div class="bg-white rounded-xl shadow-sm border p-5 hover:shadow-md transition <?= $a['is_pinned'] ? 'border-l-4 border-l-yellow-400' : '' ?>">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <?php if ($a['is_pinned']): ?>
                                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full font-medium">Pinned</span>
                            <?php endif; ?>
                            <h2 class="text-lg font-semibold text-gray-900 mt-1">
                                <a href="<?= url('communication', 'announcement-view') ?>&id=<?= $a['id'] ?>" class="hover:text-primary-700">
                                    <?= e($a['title']) ?>
                                </a>
                            </h2>
                            <p class="text-sm text-gray-600 mt-1 line-clamp-2"><?= e(substr(strip_tags($a['content']), 0, 200)) ?></p>
                            <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                <span>By <?= e($a['author_name'] ?? 'System') ?></span>
                                <span><?= format_date($a['publish_date']) ?></span>
                                <span class="px-2 py-0.5 rounded-full text-xs <?= $a['target_audience'] === 'all' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700' ?>">
                                    <?= ucfirst($a['target_audience']) ?>
                                </span>
                                <?php if ($canManage): ?>
                                    <span class="px-2 py-0.5 rounded-full text-xs <?= $a['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' ?>">
                                        <?= ucfirst($a['status']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($canManage): ?>
                            <div class="flex gap-2 text-sm">
                                <a href="<?= url('communication', 'announcement-edit') ?>&id=<?= $a['id'] ?>" class="text-primary-700 hover:text-primary-900">Edit</a>
                                <a href="<?= url('communication', 'announcement-delete') ?>&id=<?= $a['id'] ?>" class="text-red-600 hover:text-red-800"
                                   onclick="return confirm('Delete this announcement?')">Del</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($announcements['last_page'] > 1): ?>
        <div class="mt-4">
            <?= render_pagination($announcements, url('communication', 'announcements')) ?>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
