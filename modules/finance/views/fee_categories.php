<?php
/**
 * Finance — Fee Categories List (inline create/edit)
 */
$pageTitle = 'Fee Categories';

$categories = db_fetch_all("SELECT * FROM fee_categories ORDER BY name ASC");

$edit = null;
if (!empty($_GET['edit'])) {
    $edit = db_fetch_one("SELECT * FROM fee_categories WHERE id = ?", [(int)$_GET['edit']]);
}

ob_start();
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Fee Categories</h1>
    </div>

    <!-- Add / Edit Form -->
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <h2 class="text-lg font-semibold mb-4"><?= $edit ? 'Edit Category' : 'Add Category' ?></h2>
        <form method="POST" action="<?= url('finance', 'fee-category-save') ?>" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <?= csrf_field() ?>
            <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category Name *</label>
                <input type="text" name="name" required value="<?= e($edit['name'] ?? '') ?>"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="type" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="tuition" <?= ($edit['type'] ?? '') === 'tuition' ? 'selected' : '' ?>>Tuition</option>
                    <option value="transport" <?= ($edit['type'] ?? '') === 'transport' ? 'selected' : '' ?>>Transport</option>
                    <option value="hostel" <?= ($edit['type'] ?? '') === 'hostel' ? 'selected' : '' ?>>Hostel</option>
                    <option value="exam" <?= ($edit['type'] ?? '') === 'exam' ? 'selected' : '' ?>>Exam</option>
                    <option value="other" <?= ($edit['type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <input type="text" name="description" value="<?= e($edit['description'] ?? '') ?>"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
                    <?= $edit ? 'Update' : 'Add' ?>
                </button>
                <?php if ($edit): ?>
                    <a href="<?= url('finance', 'fee-categories') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Categories Table -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($categories)): ?>
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No fee categories found.</td></tr>
                <?php else: ?>
                    <?php foreach ($categories as $i => $cat): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm text-gray-500"><?= $i + 1 ?></td>
                            <td class="px-6 py-3 text-sm font-medium text-gray-900"><?= e($cat['name']) ?></td>
                            <td class="px-6 py-3 text-sm">
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><?= ucfirst($cat['type']) ?></span>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600"><?= e($cat['description'] ?? '—') ?></td>
                            <td class="px-6 py-3 text-right text-sm space-x-2">
                                <a href="<?= url('finance', 'fee-categories') ?>&edit=<?= $cat['id'] ?>"
                                   class="text-primary-700 hover:text-primary-900 font-medium">Edit</a>
                                <a href="<?= url('finance', 'fee-category-delete') ?>&id=<?= $cat['id'] ?>"
                                   class="text-red-600 hover:text-red-800 font-medium"
                                   onclick="return confirm('Delete this category?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
