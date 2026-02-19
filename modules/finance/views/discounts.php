<?php
/**
 * Finance — Fee Discounts List
 */
$pageTitle = 'Fee Discounts';

$sessionId = get_active_session_id();
$page = max(1, input_int('page') ?: 1);

$discounts = db_paginate("
    SELECT fd.*, s.first_name, s.last_name, s.admission_no, c.name AS class_name
    FROM fee_discounts fd
    JOIN students s ON s.id = fd.student_id
    LEFT JOIN enrollments en ON en.student_id = s.id AND en.session_id = fd.session_id AND en.status = 'active'
    LEFT JOIN classes c ON c.id = en.class_id
    WHERE fd.session_id = ?
    ORDER BY fd.created_at DESC
", [$sessionId], $page, 20);

ob_start();
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Fee Discounts</h1>
        <a href="<?= url('finance', 'discount-create') ?>"
           class="inline-flex items-center gap-2 px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
            + Add Discount
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($discounts['data'])): ?>
                        <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No discounts found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($discounts['data'] as $d): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm">
                                    <div class="font-medium text-gray-900"><?= e($d['first_name'] . ' ' . $d['last_name']) ?></div>
                                    <div class="text-xs text-gray-500"><?= e($d['admission_no']) ?></div>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600"><?= e($d['class_name'] ?? '—') ?></td>
                                <td class="px-6 py-3 text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800"><?= ucfirst($d['discount_type']) ?></span>
                                </td>
                                <td class="px-6 py-3 text-sm text-right font-semibold">
                                    <?= $d['discount_type'] === 'percentage' ? $d['amount'] . '%' : format_currency($d['amount']) ?>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600"><?= e($d['reason'] ?? '—') ?></td>
                                <td class="px-6 py-3 text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full <?= $d['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' ?>">
                                        <?= $d['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right text-sm space-x-2">
                                    <a href="<?= url('finance', 'discount-edit') ?>&id=<?= $d['id'] ?>"
                                       class="text-primary-700 hover:text-primary-900 font-medium">Edit</a>
                                    <a href="<?= url('finance', 'discount-delete') ?>&id=<?= $d['id'] ?>"
                                       class="text-red-600 hover:text-red-800 font-medium"
                                       onclick="return confirm('Delete this discount?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($discounts['last_page'] > 1): ?>
            <div class="px-6 py-3 border-t bg-gray-50">
                <?= render_pagination($discounts, url('finance', 'discounts')) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
