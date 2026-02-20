<?php
/**
 * Academics — Classes View (Fixed)
 * Shows classes with medium, stream, shift associations.
 */

$classes = db_fetch_all("
    SELECT c.*,
           m.name AS medium_name,
           str.name AS stream_name,
           sh.name AS shift_name,
           (SELECT COUNT(*) FROM sections WHERE class_id = c.id) AS section_count,
           (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = c.id AND e.status = 'active') AS student_count
    FROM classes c
    LEFT JOIN mediums m ON m.id = c.medium_id
    LEFT JOIN streams str ON str.id = c.stream_id
    LEFT JOIN shifts sh ON sh.id = c.shift_id
    ORDER BY c.sort_order ASC, c.name ASC
");

$mediums = db_fetch_all("SELECT id, name FROM mediums WHERE is_active = 1 ORDER BY sort_order ASC");
$streams = db_fetch_all("SELECT id, name FROM streams WHERE is_active = 1 ORDER BY sort_order ASC");
$shifts  = db_fetch_all("SELECT id, name FROM shifts WHERE is_active = 1 ORDER BY sort_order ASC");

$editId  = input_int('edit');
$editing = $editId ? db_fetch_one("SELECT * FROM classes WHERE id = ?", [$editId]) : null;

ob_start();
?>

<div class="max-w-6xl mx-auto">

    <h1 class="text-xl font-bold text-gray-900 mb-6">Classes / Grades</h1>

    <!-- Form -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4"><?= $editing ? 'Edit Class' : 'Add New Class' ?></h2>
        <form method="POST" action="<?= url('academics', 'class-save') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required placeholder="e.g. Grade 1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Numeric Level</label>
                    <input type="number" name="numeric_name" value="<?= e($editing['numeric_name'] ?? '') ?>" min="0" max="20" placeholder="e.g. 1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Medium</label>
                    <select name="medium_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">None</option>
                        <?php foreach ($mediums as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= ($editing['medium_id'] ?? '') == $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stream</label>
                    <select name="stream_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">None</option>
                        <?php foreach ($streams as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($editing['stream_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Shift</label>
                    <select name="shift_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">None</option>
                        <?php foreach ($shifts as $sh): ?>
                            <option value="<?= $sh['id'] ?>" <?= ($editing['shift_id'] ?? '') == $sh['id'] ? 'selected' : '' ?>><?= e($sh['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                        <?= $editing ? 'Update' : 'Add' ?>
                    </button>
                    <?php if ($editing): ?>
                        <a href="<?= url('academics', 'classes') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medium</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stream</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shift</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sections</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Students</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($classes)): ?>
                    <tr><td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500">No classes found. Add one above.</td></tr>
                <?php endif; ?>
                <?php foreach ($classes as $c): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= e($c['name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= $c['numeric_name'] ?? '—' ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= e($c['medium_name'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= e($c['stream_name'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= e($c['shift_name'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= $c['section_count'] ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= $c['student_count'] ?></td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?= url('academics', 'classes') ?>&edit=<?= $c['id'] ?>" class="p-1.5 text-gray-400 hover:text-yellow-600 rounded inline-block" title="Edit">
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
