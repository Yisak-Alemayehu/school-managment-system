<?php
/**
 * Finance — Discount Create/Edit Form
 */
$id   = input_int('id');
$edit = null;
if ($id) {
    $edit = db_fetch_one("SELECT * FROM fee_discounts WHERE id = ?", [$id]);
    if (!$edit) {
        set_flash('error', 'Discount not found.');
        redirect(url('finance', 'discounts'));
    }
}

$pageTitle = $edit ? 'Edit Discount' : 'Add Discount';
$sessionId = get_active_session_id();

// Get students with enrollments
$students = db_fetch_all("
    SELECT s.id, s.first_name, s.last_name, s.admission_no, c.name AS class_name
    FROM students s
    JOIN enrollments en ON en.student_id = s.id AND en.session_id = ? AND en.status = 'active'
    JOIN classes c ON c.id = en.class_id
    WHERE s.status = 'active'
    ORDER BY s.first_name, s.last_name
", [$sessionId]);

ob_start();
?>
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900"><?= $pageTitle ?></h1>
        <a href="<?= url('finance', 'discounts') ?>" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border p-6">
        <form method="POST" action="<?= url('finance', 'discount-save') ?>">
            <?= csrf_field() ?>
            <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Student *</label>
                    <select name="student_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Select Student</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($edit['student_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                                <?= e($s['first_name'] . ' ' . $s['last_name']) ?> (<?= e($s['admission_no']) ?> — <?= e($s['class_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount Type *</label>
                    <select name="discount_type" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="percentage" <?= ($edit['discount_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                        <option value="fixed" <?= ($edit['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed Amount (ETB)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                    <input type="number" name="amount" required step="0.01" min="0"
                           value="<?= e($edit['amount'] ?? '') ?>"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <p class="text-xs text-gray-500 mt-1">For percentage, enter value like 10 for 10%</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                    <textarea name="reason" rows="2"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"><?= e($edit['reason'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1"
                               <?= ($edit['is_active'] ?? 1) ? 'checked' : '' ?>
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm font-medium text-gray-700">Active</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="px-6 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
                    <?= $edit ? 'Update' : 'Add' ?> Discount
                </button>
                <a href="<?= url('finance', 'discounts') ?>" class="px-6 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
