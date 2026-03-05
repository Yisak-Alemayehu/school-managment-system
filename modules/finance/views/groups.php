<?php
/**
 * Finance — Groups List
 * Lists all finance groups with Add New Group option
 */

$groups = db_fetch_all(
    "SELECT g.*, u.full_name AS created_by_name,
            (SELECT COUNT(*) FROM fin_group_members WHERE group_id = g.id) AS member_count
       FROM fin_groups g
       LEFT JOIN users u ON g.created_by = u.id
      ORDER BY g.created_at DESC"
);

$classes = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Finance — Grouping</h1>
        <button onclick="document.getElementById('newGroupModal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add New Group
        </button>
    </div>

    <!-- Groups List -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border responsive-table">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Date Created</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Group Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Members</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Active</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php if (empty($groups)): ?>
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No groups created yet.</td></tr>
                    <?php else: foreach ($groups as $g): ?>
                    <tr class="hover:bg-gray-50 dark:bg-dark-bg transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted" data-label="Date Created"><?= format_date($g['created_at']) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Group Name">
                            <a href="<?= url('finance', 'group-detail', $g['id']) ?>"
                               class="text-primary-600 hover:text-primary-800 font-semibold underline"><?= e($g['name']) ?></a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Description"><?= e(truncate($g['description'] ?? '', 60)) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Members"><?= $g['member_count'] ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Active">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $g['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 dark:bg-dark-card2 text-gray-500 dark:text-dark-muted' ?>">
                                <?= $g['is_active'] ? 'Yes' : 'No' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Group Modal -->
<div id="newGroupModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white dark:bg-dark-card rounded-xl shadow-xl w-full max-w-lg mx-4 p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Add New Group</h3>
        <form method="POST" action="<?= url('finance', 'group-save') ?>">
            <?= csrf_field() ?>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Group Type *</label>
                    <select name="source" id="groupSource" onchange="toggleClassSelect()" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="empty">Empty Group</option>
                        <option value="class">From Class</option>
                    </select>
                </div>
                <div id="classSelectRow" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Class *</label>
                    <select name="source_class_id" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">— Select Class —</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Group Name *</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm" placeholder="Group Name">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Group Description *</label>
                    <textarea name="description" required rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm" placeholder="Group Description"></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" checked id="grpActive" class="rounded">
                    <label for="grpActive" class="text-sm text-gray-700 dark:text-gray-300">Active</label>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="this.closest('#newGroupModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">Add Group</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleClassSelect() {
    var src = document.getElementById('groupSource').value;
    document.getElementById('classSelectRow').classList.toggle('hidden', src !== 'class');
}
</script>

<?php
$content = ob_get_clean();
$page_title = $pageTitle;
include TEMPLATES_PATH . '/layout.php';
