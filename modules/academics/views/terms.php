<?php
/**
 * Academics — Terms View
 */

$sessions = db_fetch_all("SELECT id, name FROM academic_sessions ORDER BY start_date DESC");
$filterSession = input_int('session_id') ?: (get_active_session()['id'] ?? 0);

$where = $filterSession ? "WHERE t.session_id = {$filterSession}" : '';
$terms = db_fetch_all("
    SELECT t.*, s.name AS session_name
    FROM terms t
    JOIN academic_sessions s ON s.id = t.session_id
    {$where}
    ORDER BY t.session_id DESC, t.start_date ASC
");

$editId  = input_int('edit');
$editing = $editId ? db_fetch_one("SELECT * FROM terms WHERE id = ?", [$editId]) : null;

ob_start();
?>

<div class="max-w-5xl mx-auto">

    <h1 class="text-xl font-bold text-gray-900 mb-6">Terms / Semesters</h1>

    <!-- Form -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4"><?= $editing ? 'Edit Term' : 'Add New Term' ?></h2>
        <form method="POST" action="<?= url('academics', 'term-save') ?>" class="grid grid-cols-1 sm:grid-cols-5 gap-4">
            <?= csrf_field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Session <span class="text-red-500">*</span></label>
                <select name="session_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select</option>
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (($editing['session_id'] ?? $filterSession) == $s['id']) ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Term Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required placeholder="e.g. Term 1"
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
                    <a href="<?= url('academics', 'terms') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Filter -->
    <div class="flex items-center gap-3 mb-4">
        <label class="text-sm text-gray-600">Filter by Session:</label>
        <select onchange="window.location='<?= url('academics', 'terms') ?>&session_id='+this.value" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
            <option value="0">All Sessions</option>
            <?php foreach ($sessions as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $filterSession == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- List -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Term</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Session</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($terms)): ?>
                    <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No terms found. Add one above.</td></tr>
                <?php endif; ?>
                <?php foreach ($terms as $t): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= e($t['name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= e($t['session_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= format_date($t['start_date']) ?> — <?= format_date($t['end_date']) ?></td>
                        <td class="px-4 py-3">
                            <?php if ($t['is_active']): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?= url('academics', 'terms') ?>&edit=<?= $t['id'] ?>" class="p-1.5 text-gray-400 hover:text-yellow-600 rounded inline-block" title="Edit">
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
