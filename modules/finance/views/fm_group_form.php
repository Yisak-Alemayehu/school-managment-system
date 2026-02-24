<?php
/**
 * Fee Management — Create / Edit Student Group
 */
$id = route_id() ?: input_int('id');
$edit = null;
if ($id) {
    $edit = db_fetch_one("SELECT * FROM student_groups WHERE id = ? AND deleted_at IS NULL", [$id]);
    if (!$edit) {
        set_flash('error', 'Group not found.');
        redirect('finance', 'fm-groups');
    }
}

$pageTitle = $edit ? 'Edit Group' : 'Create Student Group';

ob_start();
?>
<div class="max-w-xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900"><?= $pageTitle ?></h1>
        <a href="<?= url('finance', 'fm-groups') ?>" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Groups</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border p-6">
        <form method="POST" action="<?= url('finance', 'fm-group-save') ?>" class="space-y-5">
            <?= csrf_field() ?>
            <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Group Name *</label>
                <input type="text" name="name" required maxlength="100"
                       value="<?= old('name', $edit['name'] ?? '') ?>"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                       placeholder="E.g., Scholarship Students 2026">
                <?php if ($err = get_validation_error('name')): ?>
                    <p class="text-xs text-red-600 mt-1"><?= e($err) ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" maxlength="500"
                          class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                          placeholder="Optional description for this group"><?= old('description', $edit['description'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="active" <?= old('status', $edit['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= old('status', $edit['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="flex gap-3 justify-end pt-4 border-t">
                <a href="<?= url('finance', 'fm-groups') ?>" class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
                    <?= $edit ? 'Update Group' : 'Create Group' ?>
                </button>
            </div>
        </form>
    </div>

    <?php if ($edit): ?>
    <div class="bg-blue-50 rounded-xl border border-blue-200 p-4">
        <p class="text-sm text-blue-700">
            After creating a group, <a href="<?= url('finance', 'fm-group-members', $edit['id']) ?>" class="font-medium underline">manage members</a> to add students.
        </p>
    </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
