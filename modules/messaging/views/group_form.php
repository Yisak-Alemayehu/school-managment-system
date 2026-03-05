<?php
/**
 * Messaging — Create Group Form (Student Only)
 */

$userId = auth_user_id();
$errors = get_validation_errors();

// Check group limit: max 3 groups per student
$myGroupCount = (int) db_fetch_value("SELECT COUNT(*) FROM msg_groups WHERE created_by = ? AND is_active = 1", [$userId]);
if ($myGroupCount >= 3) {
    set_flash('error', 'You can create a maximum of 3 groups.');
    redirect('messaging', 'groups');
}

// Get student's enrollment to find classmates
$enrollment = db_fetch_one("
    SELECT e.class_id, e.section_id, c.name AS class_name, sec.name AS section_name
      FROM students s
      JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
      JOIN classes c ON e.class_id = c.id
      LEFT JOIN sections sec ON e.section_id = sec.id
     WHERE s.user_id = ?
     LIMIT 1
", [$userId]);

// Get classmates (same class/section)
$classmates = [];
if ($enrollment) {
    $cmWhere = ["s.user_id != ?", "e.class_id = ?", "e.status = 'active'", "u.status = 'active'", "u.deleted_at IS NULL"];
    $cmParams = [$userId, $enrollment['class_id']];
    if ($enrollment['section_id']) {
        $cmWhere[] = "e.section_id = ?";
        $cmParams[] = $enrollment['section_id'];
    }
    $cmWhereClause = implode(' AND ', $cmWhere);

    $classmates = db_fetch_all("
        SELECT u.id, u.full_name
          FROM students s
          JOIN enrollments e ON s.id = e.student_id
          JOIN users u ON s.user_id = u.id
         WHERE $cmWhereClause
         ORDER BY u.full_name
    ", $cmParams);
}

ob_start();
?>

<div class="max-w-2xl mx-auto space-y-4">
    <div class="flex items-center gap-3">
        <a href="<?= url('messaging', 'groups') ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900">Create Group</h1>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-700">
        You can create up to 3 groups with a maximum of 30 members each. Only classmates from your class/section can be added.
    </div>

    <form method="POST" action="<?= url('messaging', 'group-create') ?>" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Group Name</label>
            <input type="text" name="name" value="<?= old('name') ?>" required maxlength="100"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 <?= !empty($errors['name']) ? 'border-red-300' : '' ?>"
                   placeholder="e.g. Study Group A">
            <?php if (!empty($errors['name'])): ?>
            <p class="mt-1 text-xs text-red-600"><?= e($errors['name']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-gray-400">(optional)</span></label>
            <input type="text" name="description" value="<?= old('description') ?>" maxlength="255"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500"
                   placeholder="Brief description of the group">
        </div>

        <!-- Classmate Selector -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Add Members (max 30)</label>
            <?php if (empty($classmates)): ?>
            <p class="text-sm text-gray-500">No classmates found in your class/section.</p>
            <?php else: ?>
            <div class="border border-gray-300 rounded-lg max-h-60 overflow-y-auto p-2 space-y-1">
                <?php foreach ($classmates as $cm): ?>
                <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-gray-50 rounded cursor-pointer">
                    <input type="checkbox" name="member_ids[]" value="<?= $cm['id'] ?>" class="text-green-600 rounded focus:ring-green-500">
                    <span class="text-sm"><?= e($cm['full_name']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <p class="mt-1 text-xs text-gray-400"><?= count($classmates) ?> classmate(s) available</p>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="<?= url('messaging', 'groups') ?>" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 font-medium">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 font-medium">Create Group</button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
