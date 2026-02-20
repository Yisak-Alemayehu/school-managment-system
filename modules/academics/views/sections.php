<?php
/**
 * Academics — Sections View
 */

$classes = db_fetch_all("SELECT id, name FROM classes ORDER BY sort_order ASC");
$filterClass = input_int('class_id');

$where = $filterClass ? "WHERE s.class_id = {$filterClass}" : '';
$sections = db_fetch_all("
    SELECT s.*, c.name AS class_name,
           (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = s.id AND e.status = 'active') AS student_count
    FROM sections s
    JOIN classes c ON c.id = s.class_id
    {$where}
    ORDER BY c.sort_order ASC, s.name ASC
");

$editId  = input_int('edit');
$editing = $editId ? db_fetch_one("SELECT * FROM sections WHERE id = ?", [$editId]) : null;

ob_start();
?>

<div class="max-w-4xl mx-auto">

    <h1 class="text-xl font-bold text-gray-900 mb-6">Sections</h1>

    <!-- Form -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4"><?= $editing ? 'Edit Section' : 'Add New Section' ?></h2>
        <form method="POST" action="<?= url('academics', 'section-save') ?>" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <?= csrf_field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class <span class="text-red-500">*</span></label>
                <select name="class_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (($editing['class_id'] ?? $filterClass) == $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Section Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required placeholder="e.g. A"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                <input type="number" name="capacity" value="<?= e($editing['capacity'] ?? 40) ?>" min="1"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                    <?= $editing ? 'Update' : 'Add' ?>
                </button>
                <?php if ($editing): ?>
                    <a href="<?= url('academics', 'sections') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Filter -->
    <div class="flex items-center gap-3 mb-4">
        <label class="text-sm text-gray-600">Filter by Class:</label>
        <select onchange="window.location='<?= url('academics', 'sections') ?>&class_id='+this.value" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
            <option value="0">All Classes</option>
            <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $filterClass == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- List -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Section</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Capacity</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Students</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($sections)): ?>
                    <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No sections found. Add one above.</td></tr>
                <?php endif; ?>
                <?php foreach ($sections as $s): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= e($s['name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= e($s['class_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= $s['capacity'] ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= $s['student_count'] ?></td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?= url('academics', 'sections') ?>&edit=<?= $s['id'] ?>" class="p-1.5 text-gray-400 hover:text-yellow-600 rounded inline-block" title="Edit">
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
