<?php
/**
 * Messaging — My Groups (Student Only)
 * List groups the current student belongs to or created
 */

$userId = auth_user_id();

$groups = db_fetch_all("
    SELECT g.id, g.name, g.description, g.created_by, g.max_members, g.is_active, g.created_at,
           u.full_name AS creator_name,
           (SELECT COUNT(*) FROM msg_group_members gm WHERE gm.group_id = g.id) AS member_count,
           (SELECT 1 FROM msg_group_members gm2 WHERE gm2.group_id = g.id AND gm2.user_id = ? AND gm2.is_admin = 1) AS is_my_admin,
           c.name AS class_name, sec.name AS section_name
      FROM msg_groups g
      JOIN msg_group_members gm ON g.id = gm.group_id AND gm.user_id = ?
      JOIN users u ON g.created_by = u.id
      LEFT JOIN classes c ON g.class_id = c.id
      LEFT JOIN sections sec ON g.section_id = sec.id
     WHERE g.is_active = 1
     ORDER BY g.created_at DESC
", [$userId, $userId]);

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">My Groups</h1>
        <a href="<?= url('messaging', 'group-create') ?>"
           class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 font-medium inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Group
        </a>
    </div>

    <?php if (empty($groups)): ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-8 text-center text-gray-500 dark:text-dark-muted">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <p class="font-medium">No groups yet</p>
        <p class="text-sm mt-1">Create a group to start messaging with classmates.</p>
    </div>
    <?php else: ?>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($groups as $g): ?>
        <a href="<?= url('messaging', 'group-detail', $g['id']) ?>"
           class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 hover:border-green-300 hover:shadow-sm transition-all">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-dark-text truncate"><?= e($g['name']) ?></h3>
                    <?php if ($g['description']): ?>
                    <p class="text-xs text-gray-500 dark:text-dark-muted mt-0.5 truncate"><?= e($g['description']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-dark-muted">
                <span><?= $g['member_count'] ?>/<?= $g['max_members'] ?> members</span>
                <?php if ($g['is_my_admin']): ?>
                <span class="bg-green-100 text-green-700 px-1.5 py-0.5 rounded font-medium">Admin</span>
                <?php endif; ?>
            </div>
            <?php if ($g['class_name']): ?>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1"><?= e($g['class_name']) ?><?= $g['section_name'] ? ' - ' . e($g['section_name']) : '' ?></p>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
