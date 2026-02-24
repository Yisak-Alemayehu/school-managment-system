<?php
/**
 * Fee Management — Student Groups List
 */
$search = input('search', '');
$groups = db_fetch_all(
    "SELECT sg.*, 
            (SELECT COUNT(*) FROM student_group_members WHERE group_id = sg.id AND deleted_at IS NULL) AS member_count,
            (SELECT COUNT(*) FROM fee_assignments WHERE assignment_type = 'group' AND target_id = sg.id AND deleted_at IS NULL) AS assignment_count
     FROM student_groups sg
     WHERE sg.deleted_at IS NULL" .
     ($search ? " AND sg.name LIKE ?" : "") .
    " ORDER BY sg.created_at DESC",
    $search ? ["%{$search}%"] : []
);

ob_start();
?>
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Student Groups</h1>
            <p class="text-sm text-gray-500 mt-1">Manage groups for bulk fee assignment</p>
        </div>
        <?php if (auth_has_permission('fee_management.manage_groups')): ?>
        <a href="<?= url('finance', 'fm-group-form') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Group
        </a>
        <?php endif; ?>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <form method="GET" action="<?= url('finance', 'fm-groups') ?>" class="flex gap-3">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search groups..."
                   class="flex-1 rounded-lg border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500">
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">Search</button>
            <?php if ($search): ?>
                <a href="<?= url('finance', 'fm-groups') ?>" class="px-4 py-2 text-gray-500 text-sm hover:text-gray-700">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($groups)): ?>
        <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <p class="text-gray-500">No groups found.</p>
            <?php if (auth_has_permission('fee_management.manage_groups')): ?>
                <a href="<?= url('finance', 'fm-group-form') ?>" class="mt-3 inline-block text-primary-600 text-sm hover:underline">Create your first group &rarr;</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($groups as $grp): ?>
            <div class="bg-white rounded-xl shadow-sm border p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="font-semibold text-gray-900"><?= e($grp['name']) ?></h3>
                        <?php if ($grp['description']): ?>
                            <p class="text-sm text-gray-500 mt-0.5"><?= e(mb_strimwidth($grp['description'], 0, 80, '...')) ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $grp['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                        <?= ucfirst($grp['status']) ?>
                    </span>
                </div>

                <div class="flex items-center gap-4 text-sm text-gray-500 mb-4">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                        <?= (int)$grp['member_count'] ?> members
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <?= (int)$grp['assignment_count'] ?> fees assigned
                    </span>
                </div>

                <div class="flex items-center gap-2 border-t pt-3">
                    <a href="<?= url('finance', 'fm-group-members', $grp['id']) ?>" class="flex-1 text-center px-3 py-1.5 border rounded-lg text-xs font-medium hover:bg-gray-50">
                        Members
                    </a>
                    <?php if (auth_has_permission('fee_management.manage_groups')): ?>
                    <a href="<?= url('finance', 'fm-group-form', $grp['id']) ?>" class="flex-1 text-center px-3 py-1.5 border rounded-lg text-xs font-medium hover:bg-gray-50">
                        Edit
                    </a>
                    <form method="POST" action="<?= url('finance', 'fm-group-delete') ?>" class="flex-1"
                          onsubmit="return confirm('Delete this group?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $grp['id'] ?>">
                        <button type="submit" class="w-full px-3 py-1.5 border border-red-200 text-red-600 rounded-lg text-xs font-medium hover:bg-red-50">
                            Delete
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
