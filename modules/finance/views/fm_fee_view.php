<?php
/**
 * Fee Management — Fee Detail / View
 * Shows full fee info + assignments + charges + exemptions
 */
$id = route_id();
if (!$id) { redirect('finance', 'fm-manage-fees'); }

$fee = db_fetch_one("SELECT * FROM fees WHERE id = ? AND deleted_at IS NULL", [$id]);
if (!$fee) {
    set_flash('error', 'Fee not found.');
    redirect('finance', 'fm-manage-fees');
}

$recurrence = db_fetch_one("SELECT * FROM recurrence_configs WHERE fee_id = ?", [$id]);
$penalty    = db_fetch_one("SELECT * FROM penalty_configs WHERE fee_id = ?", [$id]);

// Assignments
$assignments = db_fetch_all(
    "SELECT fa.*, 
            CASE fa.assignment_type 
                WHEN 'class' THEN (SELECT CONCAT(c.name, ' - ', COALESCE(s.name,'')) FROM classes c LEFT JOIN sections s ON s.id = fa.target_id WHERE c.id = fa.target_id LIMIT 1)
                WHEN 'grade' THEN (SELECT name FROM classes WHERE id = fa.target_id LIMIT 1)
                WHEN 'group' THEN (SELECT name FROM student_groups WHERE id = fa.target_id LIMIT 1)
                WHEN 'individual' THEN (SELECT CONCAT(u.first_name, ' ', u.last_name) FROM students st JOIN users u ON u.id = st.user_id WHERE st.id = fa.target_id LIMIT 1)
                ELSE 'Unknown'
            END AS target_label
     FROM fee_assignments fa 
     WHERE fa.fee_id = ? AND fa.deleted_at IS NULL
     ORDER BY fa.created_at DESC",
    [$id]
);

// Charges summary
$chargeSummary = db_fetch_one(
    "SELECT 
        COUNT(*) AS total_charges,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) AS overdue_count,
        SUM(CASE WHEN status = 'waived' THEN 1 ELSE 0 END) AS waived_count,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count,
        COALESCE(SUM(amount), 0) AS total_amount,
        COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) AS paid_amount,
        COALESCE(SUM(CASE WHEN status IN ('pending','overdue') THEN amount ELSE 0 END), 0) AS outstanding_amount
     FROM student_fee_charges WHERE fee_id = ?", [$id]
);

// Recent charges (top 20)
$charges = db_fetch_all(
    "SELECT sfc.*, CONCAT(u.first_name, ' ', u.last_name) AS student_name, st.admission_no
     FROM student_fee_charges sfc
     JOIN students st ON st.id = sfc.student_id
     JOIN users u ON u.id = st.user_id
     WHERE sfc.fee_id = ?
     ORDER BY sfc.due_date DESC, sfc.created_at DESC
     LIMIT 20",
    [$id]
);

// Exemptions
$exemptions = db_fetch_all(
    "SELECT fe.*, CONCAT(u.first_name, ' ', u.last_name) AS student_name, st.admission_no
     FROM fee_exemptions fe
     JOIN students st ON st.id = fe.student_id
     JOIN users u ON u.id = st.user_id
     WHERE fe.fee_id = ? AND fe.deleted_at IS NULL
     ORDER BY fe.created_at DESC",
    [$id]
);

// Penalty charges
$penaltyCharges = db_fetch_all(
    "SELECT pc.*, CONCAT(u.first_name, ' ', u.last_name) AS student_name
     FROM penalty_charges pc
     JOIN student_fee_charges sfc ON sfc.id = pc.charge_id
     JOIN students st ON st.id = sfc.student_id
     JOIN users u ON u.id = st.user_id
     WHERE sfc.fee_id = ?
     ORDER BY pc.created_at DESC
     LIMIT 10",
    [$id]
);

$statusColors = [
    'draft'    => 'bg-gray-100 text-gray-700',
    'active'   => 'bg-green-100 text-green-700',
    'inactive' => 'bg-red-100 text-red-700',
];
$chargeColors = [
    'pending'   => 'bg-yellow-100 text-yellow-700',
    'paid'      => 'bg-green-100 text-green-700',
    'overdue'   => 'bg-red-100 text-red-700',
    'waived'    => 'bg-blue-100 text-blue-700',
    'cancelled' => 'bg-gray-100 text-gray-500',
];

ob_start();
?>
<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <a href="<?= url('finance', 'fm-manage-fees') ?>" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900"><?= e($fee['description']) ?></h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$fee['status']] ?? '' ?>"><?= ucfirst($fee['status']) ?></span>
            </div>
            <p class="text-sm text-gray-500">Fee ID: #<?= $fee['id'] ?> &middot; Created <?= format_date($fee['created_at']) ?></p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <?php if (auth_has_permission('fee_management.edit_fee')): ?>
                <a href="<?= url('finance', 'fm-edit-fee', $fee['id']) ?>" class="px-4 py-2 bg-white border rounded-lg text-sm hover:bg-gray-50">Edit</a>
            <?php endif; ?>
            <?php if (auth_has_permission('fee_management.assign_fee') && $fee['status'] === 'active'): ?>
                <a href="<?= url('finance', 'fm-assign-fees') ?>?fee_id=<?= $fee['id'] ?>" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm hover:bg-primary-900">Assign</a>
            <?php endif; ?>
            <?php if (auth_has_permission('fee_management.activate_fee')): ?>
                <form method="POST" action="<?= url('finance', 'fm-fee-toggle') ?>" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $fee['id'] ?>">
                    <button type="submit" class="px-4 py-2 border rounded-lg text-sm hover:bg-gray-50 <?= $fee['status'] === 'active' ? 'text-red-600 border-red-300' : 'text-green-600 border-green-300' ?>">
                        <?= $fee['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Fee Details -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border p-6 md:col-span-2">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Fee Details</h2>
            <dl class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
                <div>
                    <dt class="text-gray-500">Type</dt>
                    <dd class="font-medium text-gray-900 mt-1"><?= $fee['fee_type'] === 'one_time' ? 'One-Time' : 'Recurrent' ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500">Amount</dt>
                    <dd class="font-medium text-gray-900 mt-1"><?= CURRENCY_SYMBOL ?> <?= number_format($fee['amount'], 2) ?> <?= $fee['currency'] ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500">Effective Date</dt>
                    <dd class="font-medium text-gray-900 mt-1"><?= format_date($fee['effective_date']) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500">End Date</dt>
                    <dd class="font-medium text-gray-900 mt-1"><?= format_date($fee['end_date']) ?></dd>
                </div>
            </dl>

            <?php if ($recurrence): ?>
            <div class="mt-6 pt-4 border-t">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Recurrence Configuration</h3>
                <dl class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Frequency</dt>
                        <dd class="font-medium mt-1">Every <?= $recurrence['frequency_number'] ?> <?= $recurrence['frequency_unit'] ?></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Max Recurrences</dt>
                        <dd class="font-medium mt-1"><?= $recurrence['max_recurrences'] == 0 ? 'Unlimited' : $recurrence['max_recurrences'] ?></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Next Due Date</dt>
                        <dd class="font-medium mt-1"><?= $recurrence['next_due_date'] ? format_date($recurrence['next_due_date']) : 'N/A' ?></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Generated So Far</dt>
                        <dd class="font-medium mt-1"><?= $recurrence['current_recurrence'] ?? 0 ?></dd>
                    </div>
                </dl>
            </div>
            <?php endif; ?>

            <?php if ($penalty): ?>
            <div class="mt-6 pt-4 border-t">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Penalty Configuration</h3>
                <dl class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Grace Period</dt>
                        <dd class="font-medium mt-1"><?= $penalty['grace_period_number'] ?> <?= $penalty['grace_period_unit'] ?></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Penalty</dt>
                        <dd class="font-medium mt-1">
                            <?= $penalty['penalty_type'] === 'fixed' ? CURRENCY_SYMBOL . ' ' . number_format($penalty['penalty_amount'], 2) : $penalty['penalty_amount'] . '%' ?>
                            (<?= ucfirst($penalty['penalty_frequency']) ?>)
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Max Penalty Cap</dt>
                        <dd class="font-medium mt-1"><?= CURRENCY_SYMBOL ?> <?= number_format($penalty['max_penalty_amount'], 2) ?></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Max Applications</dt>
                        <dd class="font-medium mt-1"><?= $penalty['max_penalty_applications'] == 0 ? 'Unlimited' : $penalty['max_penalty_applications'] ?></dd>
                    </div>
                </dl>
            </div>
            <?php endif; ?>
        </div>

        <!-- Financial Summary -->
        <div class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Financial Summary</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Total Billed</span>
                        <span class="text-sm font-semibold"><?= CURRENCY_SYMBOL ?> <?= number_format($chargeSummary['total_amount'], 2) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-green-600">Collected</span>
                        <span class="text-sm font-semibold text-green-600"><?= CURRENCY_SYMBOL ?> <?= number_format($chargeSummary['paid_amount'], 2) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-amber-600">Outstanding</span>
                        <span class="text-sm font-semibold text-amber-600"><?= CURRENCY_SYMBOL ?> <?= number_format($chargeSummary['outstanding_amount'], 2) ?></span>
                    </div>
                    <hr>
                    <div class="grid grid-cols-2 gap-2 text-center text-xs">
                        <div class="p-2 rounded bg-green-50"><span class="font-bold text-green-700"><?= (int)$chargeSummary['paid_count'] ?></span><br>Paid</div>
                        <div class="p-2 rounded bg-yellow-50"><span class="font-bold text-yellow-700"><?= (int)$chargeSummary['pending_count'] ?></span><br>Pending</div>
                        <div class="p-2 rounded bg-red-50"><span class="font-bold text-red-700"><?= (int)$chargeSummary['overdue_count'] ?></span><br>Overdue</div>
                        <div class="p-2 rounded bg-blue-50"><span class="font-bold text-blue-700"><?= (int)$chargeSummary['waived_count'] ?></span><br>Waived</div>
                    </div>
                </div>
            </div>

            <!-- Collection Rate Chart -->
            <?php
            $totalNonCancelled = (int)$chargeSummary['total_charges'] - (int)$chargeSummary['cancelled_count'];
            $collectRate = $totalNonCancelled > 0 ? round(((int)$chargeSummary['paid_count'] / $totalNonCancelled) * 100) : 0;
            ?>
            <div class="bg-white rounded-xl shadow-sm border p-6 text-center">
                <p class="text-xs text-gray-500 mb-2">Collection Rate</p>
                <p class="text-3xl font-bold text-primary-800"><?= $collectRate ?>%</p>
                <div class="w-full bg-gray-200 rounded-full h-2.5 mt-3">
                    <div class="bg-primary-600 h-2.5 rounded-full" style="width: <?= $collectRate ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignments -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Assignments (<?= count($assignments) ?>)</h2>
            <?php if (auth_has_permission('fee_management.assign_fee') && $fee['status'] === 'active'): ?>
                <a href="<?= url('finance', 'fm-assign-fees') ?>?fee_id=<?= $fee['id'] ?>" class="text-sm text-primary-600 hover:underline">+ Add Assignment</a>
            <?php endif; ?>
        </div>
        <?php if (empty($assignments)): ?>
            <div class="p-6 text-center text-gray-400 text-sm">No assignments yet.</div>
        <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Type</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Target</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Created</th>
                        <th class="text-right px-4 py-2 font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($assignments as $a): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2"><span class="capitalize"><?= e($a['assignment_type']) ?></span></td>
                        <td class="px-4 py-2 font-medium"><?= e($a['target_label']) ?></td>
                        <td class="px-4 py-2 text-gray-500"><?= format_date($a['created_at']) ?></td>
                        <td class="px-4 py-2 text-right">
                            <?php if (auth_has_permission('fee_management.assign_fee')): ?>
                            <form method="POST" action="<?= url('finance', 'fm-assignment-delete') ?>" class="inline"
                                  onsubmit="return confirm('Remove this assignment? Related pending charges will be cancelled.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                <input type="hidden" name="fee_id" value="<?= $fee['id'] ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs">Remove</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Recent Charges -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">Recent Charges (showing <?= count($charges) ?>)</h2>
        </div>
        <?php if (empty($charges)): ?>
            <div class="p-6 text-center text-gray-400 text-sm">No charges generated yet.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="text-left px-4 py-2 font-medium text-gray-600">Student</th>
                            <th class="text-left px-4 py-2 font-medium text-gray-600">Admission #</th>
                            <th class="text-right px-4 py-2 font-medium text-gray-600">Amount</th>
                            <th class="text-center px-4 py-2 font-medium text-gray-600">Due Date</th>
                            <th class="text-center px-4 py-2 font-medium text-gray-600">Status</th>
                            <th class="text-right px-4 py-2 font-medium text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($charges as $ch): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium"><?= e($ch['student_name']) ?></td>
                            <td class="px-4 py-2 text-gray-500"><?= e($ch['admission_no']) ?></td>
                            <td class="px-4 py-2 text-right font-mono"><?= CURRENCY_SYMBOL ?> <?= number_format($ch['amount'], 2) ?></td>
                            <td class="px-4 py-2 text-center"><?= format_date($ch['due_date']) ?></td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $chargeColors[$ch['status']] ?? 'bg-gray-100 text-gray-500' ?>">
                                    <?= ucfirst($ch['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <?php if (auth_has_permission('fee_management.manage_charges') && in_array($ch['status'], ['pending', 'overdue'])): ?>
                                <form method="POST" action="<?= url('finance', 'fm-charge-waive') ?>" class="inline"
                                      onsubmit="return confirm('Waive this charge?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="charge_id" value="<?= $ch['id'] ?>">
                                    <input type="hidden" name="fee_id" value="<?= $fee['id'] ?>">
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 text-xs">Waive</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Exemptions -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">Exemptions (<?= count($exemptions) ?>)</h2>
        </div>
        <?php if (empty($exemptions)): ?>
            <div class="p-6 text-center text-gray-400 text-sm">No exemptions.</div>
        <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Student</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Admission #</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Reason</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Exempted On</th>
                        <th class="text-right px-4 py-2 font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($exemptions as $ex): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium"><?= e($ex['student_name']) ?></td>
                        <td class="px-4 py-2 text-gray-500"><?= e($ex['admission_no']) ?></td>
                        <td class="px-4 py-2"><?= e($ex['reason'] ?? 'N/A') ?></td>
                        <td class="px-4 py-2 text-gray-500"><?= format_date($ex['created_at']) ?></td>
                        <td class="px-4 py-2 text-right">
                            <?php if (auth_has_permission('fee_management.manage_exemptions')): ?>
                            <form method="POST" action="<?= url('finance', 'fm-exemption-delete') ?>" class="inline"
                                  onsubmit="return confirm('Remove this exemption?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $ex['id'] ?>">
                                <input type="hidden" name="fee_id" value="<?= $fee['id'] ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs">Remove</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Penalty Charges -->
    <?php if ($penalty && !empty($penaltyCharges)): ?>
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">Penalty Charges</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-4 py-2 font-medium text-gray-600">Student</th>
                    <th class="text-right px-4 py-2 font-medium text-gray-600">Amount</th>
                    <th class="text-center px-4 py-2 font-medium text-gray-600">Applied On</th>
                    <th class="text-center px-4 py-2 font-medium text-gray-600">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($penaltyCharges as $pc): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 font-medium"><?= e($pc['student_name']) ?></td>
                    <td class="px-4 py-2 text-right font-mono text-red-600"><?= CURRENCY_SYMBOL ?> <?= number_format($pc['penalty_amount'], 2) ?></td>
                    <td class="px-4 py-2 text-center text-gray-500"><?= format_date($pc['applied_at']) ?></td>
                    <td class="px-4 py-2 text-center">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $chargeColors[$pc['status']] ?? '' ?>">
                            <?= ucfirst($pc['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Audit Log -->
    <?php if (auth_has_permission('fee_management.view_audit_log')): ?>
    <?php
    $auditLogs = db_fetch_all(
        "SELECT fal.*, CONCAT(u.first_name, ' ', u.last_name) AS user_name
         FROM finance_audit_log fal
         LEFT JOIN users u ON u.id = fal.user_id
         WHERE fal.entity_type = 'fee' AND fal.entity_id = ?
         ORDER BY fal.created_at DESC
         LIMIT 15",
        [$id]
    );
    ?>
    <?php if (!empty($auditLogs)): ?>
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">Audit Log</h2>
        </div>
        <div class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
            <?php foreach ($auditLogs as $log): ?>
            <div class="px-6 py-3 flex items-start gap-3">
                <div class="text-xs text-gray-400 whitespace-nowrap mt-0.5"><?= date('M d H:i', strtotime($log['created_at'])) ?></div>
                <div>
                    <span class="text-sm font-medium text-gray-700"><?= e($log['user_name'] ?? 'System') ?></span>
                    <span class="text-sm text-gray-500"><?= e($log['action']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
