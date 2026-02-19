<?php
/**
 * Academics — Shifts View
 */

$shifts  = db_fetch_all("SELECT * FROM shifts ORDER BY sort_order ASC, name ASC");
$editId  = input_int('edit');
$editing = $editId ? db_fetch_one("SELECT * FROM shifts WHERE id = ?", [$editId]) : null;

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <?php require APP_ROOT . '/templates/partials/academics_nav.php'; ?>

    <h1 class="text-xl font-bold text-gray-900 mb-6">Shifts</h1>

    <!-- Form -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4"><?= $editing ? 'Edit Shift' : 'Add New Shift' ?></h2>
        <form method="POST" action="<?= url('academics', 'shift-save') ?>" class="grid grid-cols-1 sm:grid-cols-5 gap-4">
            <?= csrf_field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Shift Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required placeholder="e.g. Morning"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                <input type="time" name="start_time" value="<?= e($editing['start_time'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                <input type="time" name="end_time" value="<?= e($editing['end_time'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="is_active" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="1" <?= ($editing['is_active'] ?? 1) == 1 ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= ($editing['is_active'] ?? 1) == 0 ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                    <?= $editing ? 'Update' : 'Add' ?>
                </button>
                <?php if ($editing): ?>
                    <a href="<?= url('academics', 'shifts') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shift Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($shifts)): ?>
                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No shifts found. Add one above.</td></tr>
                <?php endif; ?>
                <?php foreach ($shifts as $i => $s): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500"><?= $i + 1 ?></td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= e($s['name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            <?php if ($s['start_time'] && $s['end_time']): ?>
                                <?= e(substr($s['start_time'], 0, 5)) ?> — <?= e(substr($s['end_time'], 0, 5)) ?>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($s['is_active']): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?= url('academics', 'shifts') ?>&edit=<?= $s['id'] ?>" class="p-1.5 text-gray-400 hover:text-yellow-600 rounded inline-block" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
