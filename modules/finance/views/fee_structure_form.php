<?php
/**
 * Finance — Fee Structure Create/Edit Form
 */
$id   = input_int('id');
$edit = null;

if ($id) {
    $edit = db_fetch_one("SELECT * FROM fee_structures WHERE id = ?", [$id]);
    if (!$edit) {
        set_flash('error', 'Fee structure not found.');
        redirect(url('finance', 'fee-structures'));
    }
}

$pageTitle = $edit ? 'Edit Fee Structure' : 'New Fee Structure';
$classes    = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY name");
$categories = db_fetch_all("SELECT id, name FROM fee_categories ORDER BY name");

ob_start();
?>
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900"><?= $pageTitle ?></h1>
        <a href="<?= url('finance', 'fee-structures') ?>" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border p-6">
        <form method="POST" action="<?= url('finance', 'fee-structure-save') ?>">
            <?= csrf_field() ?>
            <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class *</label>
                    <select name="class_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($edit['class_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fee Category *</label>
                    <select name="fee_category_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($edit['fee_category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount (ETB) *</label>
                    <input type="number" name="amount" required step="0.01" min="0"
                           value="<?= e($edit['amount'] ?? '') ?>"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
                    <select name="frequency" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="term" <?= ($edit['frequency'] ?? '') === 'term' ? 'selected' : '' ?>>Per Term</option>
                        <option value="annual" <?= ($edit['frequency'] ?? '') === 'annual' ? 'selected' : '' ?>>Annual</option>
                        <option value="monthly" <?= ($edit['frequency'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="one_time" <?= ($edit['frequency'] ?? '') === 'one_time' ? 'selected' : '' ?>>One-time</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                    <input type="date" name="due_date" value="<?= e($edit['due_date'] ?? '') ?>"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Late Fee (ETB)</label>
                    <input type="number" name="late_fee" step="0.01" min="0"
                           value="<?= e($edit['late_fee'] ?? '0') ?>"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2"
                          class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"><?= e($edit['description'] ?? '') ?></textarea>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="px-6 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
                    <?= $edit ? 'Update' : 'Create' ?> Fee Structure
                </button>
                <a href="<?= url('finance', 'fee-structures') ?>" class="px-6 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
