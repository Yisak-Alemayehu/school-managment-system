<?php
/**
 * Academics — Sessions View
 */

$sessions = db_fetch_all("SELECT * FROM academic_sessions ORDER BY start_date DESC");
$editId   = input_int('edit');
$editing  = $editId ? db_fetch_one("SELECT * FROM academic_sessions WHERE id = ?", [$editId]) : null;

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <?php require APP_ROOT . '/templates/partials/academics_nav.php'; ?>

    <h1 class="text-xl font-bold text-gray-900 mb-6">Academic Sessions</h1>

    <!-- Add/Edit Form -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4"><?= $editing ? 'Edit Session' : 'Add New Session' ?></h2>
        <form method="POST" action="<?= url('academics', 'session-save') ?>" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <?= csrf_field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Session Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required placeholder="e.g. 2025/2026"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date <span class="text-red-500">*</span></label>
                <input type="date" name="start_date" value="<?= e($editing['start_date'] ?? '') ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">End Date <span class="text-red-500">*</span></label>
                <input type="date" name="end_date" value="<?= e($editing['end_date'] ?? '') ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                    <?= $editing ? 'Update' : 'Add' ?>
                </button>
                <?php if ($editing): ?>
                    <a href="<?= url('academics', 'sessions') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Session</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($sessions)): ?>
                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No academic sessions found. Add one above.</td></tr>
                <?php endif; ?>
                <?php foreach ($sessions as $s): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= e($s['name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= format_date($s['start_date']) ?> — <?= format_date($s['end_date']) ?></td>
                        <td class="px-4 py-3">
                            <?php if ($s['is_active']): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= url('academics', 'sessions') ?>&edit=<?= $s['id'] ?>" class="p-1.5 text-gray-400 hover:text-yellow-600 rounded" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="<?= url('academics', 'session-toggle') ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="p-1.5 text-gray-400 hover:text-primary-600 rounded" title="Toggle active">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    </button>
                                </form>
                            </div>
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
