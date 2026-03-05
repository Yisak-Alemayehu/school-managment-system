<?php
/**
 * Finance — Fee Detail View
 * Shows full fee info, assigned students, and transactions
 */

$feeId = $id;
$fee   = db_fetch_one("SELECT f.*, u.full_name AS created_by_name FROM fin_fees f LEFT JOIN users u ON f.created_by = u.id WHERE f.id = ?", [$feeId]);
if (!$fee) {
    set_flash('error', 'Fee not found.');
    redirect(url('finance', 'fee-due'));
}

$feeClasses = db_fetch_all(
    "SELECT c.name FROM fin_fee_classes fc JOIN classes c ON fc.class_id = c.id WHERE fc.fee_id = ? ORDER BY c.sort_order",
    [$feeId]
);

$varyingPenalties = db_fetch_all("SELECT * FROM fin_varying_penalties WHERE fee_id = ? ORDER BY sort_order", [$feeId]);

$assignedStudents = db_fetch_all(
    "SELECT sf.*, s.full_name, s.admission_no,
            c.name AS class_name
       FROM fin_student_fees sf
       JOIN students s ON sf.student_id = s.id
       LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
       LEFT JOIN classes c ON e.class_id = c.id
      WHERE sf.fee_id = ?
      ORDER BY sf.is_active DESC, s.full_name",
    [$feeId]
);

$recentTx = db_fetch_all(
    "SELECT t.*, s.full_name, s.admission_no
       FROM fin_transactions t
       JOIN students s ON t.student_id = s.id
       JOIN fin_student_fees sf ON t.student_fee_id = sf.id
      WHERE sf.fee_id = ?
      ORDER BY t.created_at DESC
      LIMIT 50",
    [$feeId]
);

$exportQs = http_build_query(['type' => 'fee-detail', 'id' => $feeId]);

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div class="flex items-center gap-3">
            <a href="<?= url('finance', 'fee-due') ?>" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-xl font-bold text-gray-900"><?= e($fee['description']) ?></h1>
            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $fee['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= $fee['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('finance', 'export-pdf') ?>&<?= $exportQs ?>"
               class="px-3 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 font-medium">PDF</a>
            <a href="<?= url('finance', 'export-excel') ?>&<?= $exportQs ?>"
               class="px-3 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 font-medium">Excel</a>
        </div>
    </div>

    <!-- Fee Information -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-700 uppercase mb-3">Fee Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
            <div><span class="text-gray-500">Amount:</span> <strong><?= format_money($fee['amount']) ?> <?= e($fee['currency']) ?></strong></div>
            <?php if ($fee['foreign_amount']): ?>
            <div><span class="text-gray-500">ETB Equivalent:</span> <strong><?= format_money($fee['foreign_amount']) ?></strong></div>
            <?php endif; ?>
            <div><span class="text-gray-500">Type:</span> <strong><?= $fee['fee_type'] ? 'Recurrent' : 'One-Time' ?></strong></div>
            <div><span class="text-gray-500">Effective Date:</span> <strong><?= format_date($fee['effective_date']) ?></strong></div>
            <div><span class="text-gray-500">End Date:</span> <strong><?= format_date($fee['end_date']) ?></strong></div>
            <?php if ($fee['fee_type']): ?>
            <div><span class="text-gray-500">Applies Every:</span> <strong><?= (int) $fee['apply_every'] ?> <?= e($fee['frequency']) ?></strong></div>
            <?php endif; ?>
            <div><span class="text-gray-500">Classes:</span> <strong><?= !empty($feeClasses) ? e(implode(', ', array_column($feeClasses, 'name'))) : 'All' ?></strong></div>
            <div><span class="text-gray-500">Created By:</span> <strong><?= e($fee['created_by_name'] ?? '—') ?></strong></div>
            <div><span class="text-gray-500">Created:</span> <strong><?= format_datetime($fee['created_at']) ?></strong></div>
        </div>
    </div>

    <!-- Penalty Info -->
    <?php if ($fee['has_penalty']): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-700 uppercase mb-3">Penalty Configuration</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
            <div><span class="text-gray-500">Penalty Type:</span> <strong><?= ucfirst(str_replace('_', ' ', $fee['penalty_type'] ?? '—')) ?></strong></div>
            <?php if (in_array($fee['penalty_type'], ['fixed_amount', 'fixed_percentage'])): ?>
            <div><span class="text-gray-500">Penalty Value:</span> <strong><?= $fee['penalty_value'] ?><?= $fee['penalty_type'] === 'fixed_percentage' ? '%' : '' ?></strong></div>
            <?php endif; ?>
            <div><span class="text-gray-500">Unpaid After:</span> <strong><?= (int) $fee['penalty_unpaid_after'] ?> <?= e($fee['penalty_unpaid_unit'] ?? '') ?></strong></div>
            <div><span class="text-gray-500">Frequency:</span> <strong><?= ucfirst(str_replace('_', ' ', $fee['penalty_frequency'] ?? '—')) ?></strong></div>
            <?php if ($fee['penalty_frequency'] === 'recurrent'): ?>
            <div><span class="text-gray-500">Reapply Every:</span> <strong><?= (int) $fee['penalty_reapply_every'] ?> <?= e($fee['penalty_reapply_unit'] ?? '') ?></strong></div>
            <?php endif; ?>
            <div><span class="text-gray-500">Penalty Expiry:</span> <strong><?= $fee['penalty_expiry_date'] ? format_date($fee['penalty_expiry_date']) : 'None' ?></strong></div>
            <div><span class="text-gray-500">Max Amount:</span> <strong><?= format_money($fee['max_penalty_amount']) ?></strong></div>
            <div><span class="text-gray-500">Max Count:</span> <strong><?= $fee['max_penalty_count'] ?: 'Unlimited' ?></strong></div>
            <div><span class="text-gray-500">Credit Hour:</span> <strong><?= $fee['is_credit_hour'] ? 'Yes' : 'No' ?></strong></div>
        </div>

        <?php if (!empty($varyingPenalties)): ?>
        <div class="mt-4">
            <h3 class="text-sm font-medium text-gray-700 mb-2">Varying Values</h3>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($varyingPenalties as $i => $vp): ?>
                <span class="px-3 py-1 bg-gray-100 rounded-lg text-sm"><?= ($i + 1) ?>. <?= $vp['value'] ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Assigned Students -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-700 uppercase">Assigned Students (<?= count($assignedStudents) ?>)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 responsive-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Class</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Balance</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($assignedStudents)): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No students assigned.</td></tr>
                    <?php else: ?>
                    <?php foreach ($assignedStudents as $as): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm" data-label="Student">
                            <a href="<?= url('finance', 'student-detail', $as['student_id']) ?>" class="text-primary-600 hover:underline font-medium"><?= e($as['full_name']) ?></a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600" data-label="Code"><?= e($as['admission_no']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600" data-label="Class"><?= e($as['class_name'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Amount"><?= format_money($as['amount']) ?></td>
                        <td class="px-4 py-3 text-sm font-semibold <?= $as['balance'] > 0 ? 'text-red-600' : 'text-green-600' ?>" data-label="Balance">
                            <?= format_money($as['balance']) ?>
                        </td>
                        <td class="px-4 py-3 text-sm" data-label="Status">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $as['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= $as['is_active'] ? 'Active' : 'Removed' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-700 uppercase">Recent Transactions</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 responsive-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($recentTx)): ?>
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No transactions yet.</td></tr>
                    <?php else: ?>
                    <?php foreach ($recentTx as $tx): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-600" data-label="Date"><?= format_datetime($tx['created_at']) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Student">
                            <a href="<?= url('finance', 'student-detail', $tx['student_id']) ?>" class="text-primary-600 hover:underline"><?= e($tx['full_name']) ?></a>
                        </td>
                        <td class="px-4 py-3 text-sm" data-label="Type">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                <?php
                                    switch($tx['type']) {
                                        case 'payment': echo 'bg-green-100 text-green-700'; break;
                                        case 'penalty': echo 'bg-red-100 text-red-700'; break;
                                        case 'adjustment': echo 'bg-yellow-100 text-yellow-700'; break;
                                        default: echo 'bg-gray-100 text-gray-700';
                                    }
                                ?>">
                                <?= ucfirst(str_replace('_', ' ', $tx['type'])) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold" data-label="Amount"><?= format_money($tx['amount']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600" data-label="Desc"><?= e(truncate($tx['description'] ?? '', 60)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = $pageTitle;
include TEMPLATES_PATH . '/layout.php';
