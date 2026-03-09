<?php
/**
 * HR — Leave Types View
 */

$leaveTypes = db_fetch_all("SELECT * FROM hr_leave_types ORDER BY name");

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Leave Types</h1>
        <button onclick="openLTModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Leave Type
        </button>
    </div>

    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border">
            <thead class="bg-gray-50 dark:bg-dark-bg">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Code</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Days Allowed</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Description</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                <?php if (empty($leaveTypes)): ?>
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No leave types configured.</td></tr>
                <?php else: foreach ($leaveTypes as $lt): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($lt['name']) ?></td>
                    <td class="px-4 py-3 text-sm font-mono text-gray-500"><?= e($lt['code']) ?></td>
                    <td class="px-4 py-3 text-sm text-center font-medium"><?= (int)$lt['days_allowed'] ?></td>
                    <td class="px-4 py-3 text-sm text-gray-500"><?= e($lt['description'] ?? '') ?></td>
                    <td class="px-4 py-3 text-sm text-center">
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $lt['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                            <?= $lt['status'] === 'active' ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <button onclick='editLT(<?= json_encode($lt) ?>)' class="text-amber-600 hover:text-amber-800 text-xs font-medium">Edit</button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Leave Type Modal -->
<div id="ltModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-dark-card rounded-xl w-full max-w-md p-6 m-4">
        <h3 id="ltTitle" class="text-lg font-semibold text-gray-900 dark:text-dark-text mb-4">Add Leave Type</h3>
        <form method="POST" action="<?= url('hr', 'leave-type-save') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="lt_id" value="">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Name *</label>
                    <input type="text" name="name" id="lt_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Code *</label>
                    <input type="text" name="code" id="lt_code" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text" placeholder="e.g. AL">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Days Allowed Per Year *</label>
                    <input type="number" name="days_allowed" id="lt_days" required min="0" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Status</label>
                    <select name="status" id="lt_status" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Description</label>
                    <textarea name="description" id="lt_desc" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" onclick="closeLTModal()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openLTModal() {
    document.getElementById('ltTitle').textContent = 'Add Leave Type';
    document.getElementById('lt_id').value = '';
    document.getElementById('lt_name').value = '';
    document.getElementById('lt_code').value = '';
    document.getElementById('lt_days').value = '';
    document.getElementById('lt_status').value = 'active';
    document.getElementById('lt_desc').value = '';
    document.getElementById('ltModal').classList.remove('hidden');
}
function editLT(lt) {
    document.getElementById('ltTitle').textContent = 'Edit Leave Type';
    document.getElementById('lt_id').value = lt.id;
    document.getElementById('lt_name').value = lt.name;
    document.getElementById('lt_code').value = lt.code;
    document.getElementById('lt_days').value = lt.days_allowed;
    document.getElementById('lt_status').value = lt.status || 'active';
    document.getElementById('lt_desc').value = lt.description || '';
    document.getElementById('ltModal').classList.remove('hidden');
}
function closeLTModal() { document.getElementById('ltModal').classList.add('hidden'); }
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
