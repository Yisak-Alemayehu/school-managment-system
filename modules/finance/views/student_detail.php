<?php
/**
 * Finance — Student Detail
 * Shows student info, payment summary, guardian, payment history + actions
 */

$student = db_fetch_one(
    "SELECT s.*, c.name AS class_name, sec.name AS section_name, e.roll_no
       FROM students s
       LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
       LEFT JOIN classes c ON e.class_id = c.id
       LEFT JOIN sections sec ON e.section_id = sec.id
      WHERE s.id = ? AND s.deleted_at IS NULL",
    [$id]
);
if (!$student) { set_flash('error', 'Student not found.'); redirect(url('finance', 'students')); }

// Guardian info
$guardian = db_fetch_one(
    "SELECT g.* FROM guardians g
       JOIN student_guardians sg ON g.id = sg.guardian_id
      WHERE sg.student_id = ? ORDER BY sg.is_primary DESC LIMIT 1",
    [$id]
);

// Active fees count & balance
$activeFees = (int) db_fetch_value(
    "SELECT COUNT(*) FROM fin_student_fees WHERE student_id = ? AND is_active = 1", [$id]
);
$accountBalance = (float) db_fetch_value(
    "SELECT COALESCE(SUM(balance), 0) FROM fin_student_fees WHERE student_id = ? AND is_active = 1", [$id]
);

// Available fees for assignment
$allFees = db_fetch_all(
    "SELECT id, description, amount, currency FROM fin_fees WHERE is_active = 1 ORDER BY description"
);

// Current tab
$tab = input('tab') ?: 'history';

// Fetch transactions based on tab
$txParams = [$id];
switch ($tab) {
    case 'active':
        $txSql = "SELECT sf.id AS sf_id, f.description, sf.amount, sf.balance, sf.currency, sf.assigned_at, sf.is_active
                    FROM fin_student_fees sf
                    JOIN fin_fees f ON sf.fee_id = f.id
                   WHERE sf.student_id = ? AND sf.is_active = 1
                   ORDER BY sf.assigned_at DESC";
        break;
    case 'payments':
        $txSql = "SELECT t.* FROM fin_transactions t WHERE t.student_id = ? AND t.type = 'payment' ORDER BY t.created_at DESC";
        break;
    case 'all':
        $txSql = "SELECT t.* FROM fin_transactions t WHERE t.student_id = ? ORDER BY t.created_at DESC";
        break;
    default: // history
        $txSql = "SELECT t.* FROM fin_transactions t WHERE t.student_id = ? ORDER BY t.created_at DESC";
        break;
}
$transactions = db_fetch_all($txSql, $txParams);

ob_start();
?>

<div class="space-y-6">
    <!-- Back link -->
    <a href="<?= url('finance', 'students') ?>" class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-dark-muted hover:text-primary-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Student List
    </a>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-3">
        <button onclick="document.getElementById('assignFeeModal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Assign Fee
        </button>
        <button onclick="document.getElementById('removeFeeModal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
            Remove Fee
        </button>
        <button onclick="document.getElementById('adjustModal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-600 text-white text-sm rounded-lg hover:bg-yellow-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Adjust Balance
        </button>
    </div>

    <!-- Student Information -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Student Information</h2>
        <div class="flex flex-col md:flex-row gap-6">
            <div class="flex-shrink-0">
                <?php if ($student['photo']): ?>
                    <img src="<?= upload_url($student['photo']) ?>" alt="Student Photo" class="w-24 h-24 rounded-xl object-cover border">
                <?php else: ?>
                    <div class="w-24 h-24 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center text-2xl font-bold border">
                        <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-3 flex-1">
                <div>
                    <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Student Name</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($student['full_name']) ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Student Code</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($student['admission_no']) ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Class</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($student['class_name'] ?? '—') ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Date of Birth</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= format_date($student['date_of_birth']) ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Gender</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= ucfirst(e($student['gender'])) ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Email</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($student['email'] ?? '—') ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Nationality</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($student['nationality'] ?? '—') ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Parent's Contact Phone</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($guardian['phone'] ?? $student['phone'] ?? '—') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Summary -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Payment Summary</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                <p class="text-xs text-blue-600 uppercase font-semibold">Total Active Fees</p>
                <p class="text-2xl font-bold text-blue-900 mt-1"><?= $activeFees ?></p>
            </div>
            <div class="p-4 <?= $accountBalance >= 0 ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' ?> rounded-lg border">
                <p class="text-xs <?= $accountBalance >= 0 ? 'text-green-600' : 'text-red-600' ?> uppercase font-semibold">Student Account Balance</p>
                <p class="text-2xl font-bold <?= $accountBalance >= 0 ? 'text-green-900' : 'text-red-900' ?> mt-1"><?= format_money($accountBalance) ?></p>
            </div>
        </div>
    </div>

    <!-- Guardian Information -->
    <?php if ($guardian): ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Guardian Information</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-3">
            <div>
                <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Name</p>
                <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($guardian['full_name']) ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Relationship</p>
                <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= ucfirst(e($guardian['relation'])) ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Phone Contact</p>
                <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($guardian['phone']) ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Email</p>
                <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($guardian['email'] ?? '—') ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Payment History / Transactions Section -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text">Transactions</h2>
            <div class="flex flex-wrap gap-2">
                <a href="<?= url('finance', 'export-pdf') ?>&student_id=<?= $id ?>" target="_blank"
                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 text-red-700 text-xs rounded-lg hover:bg-red-100 font-medium border border-red-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download PDF
                </a>
                <a href="<?= url('finance', 'export-excel') ?>&student_id=<?= $id ?>" target="_blank"
                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-50 text-green-700 text-xs rounded-lg hover:bg-green-100 font-medium border border-green-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download Excel
                </a>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="flex flex-wrap gap-1 mb-4 border-b border-gray-200 dark:border-dark-border">
            <?php
            $tabs = [
                'history' => 'Full Payment History',
                'active'  => 'Active Fees',
                'payments'=> 'Only Payments',
                'all'     => 'All Transactions',
            ];
            foreach ($tabs as $key => $label): ?>
            <a href="<?= url('finance', 'student-detail', $id) ?>&tab=<?= $key ?>"
               class="px-4 py-2 text-sm font-medium border-b-2 transition-colors <?= $tab === $key ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:text-gray-300' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Content for Active Fees tab -->
        <?php if ($tab === 'active'): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border responsive-table">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Balance</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Currency</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Assigned</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php if (empty($transactions)): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No active fees.</td></tr>
                    <?php else: foreach ($transactions as $tx): ?>
                    <tr class="hover:bg-gray-50 dark:bg-dark-bg">
                        <td class="px-4 py-3 text-sm" data-label="Description"><?= e($tx['description']) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Amount"><?= format_money($tx['amount']) ?></td>
                        <td class="px-4 py-3 text-sm font-semibold <?= $tx['balance'] > 0 ? 'text-red-600' : 'text-green-600' ?>" data-label="Balance"><?= format_money($tx['balance']) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Currency"><?= e($tx['currency']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted" data-label="Assigned"><?= format_datetime($tx['assigned_at']) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Status">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $tx['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 dark:bg-dark-card2 text-gray-500 dark:text-dark-muted' ?>">
                                <?= $tx['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php else: ?>
        <!-- Content for transaction tabs (history, payments, all) -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border responsive-table">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Balance After</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Channel</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php if (empty($transactions)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No transactions found.</td></tr>
                    <?php else: foreach ($transactions as $tx):
                        $typeBadge = match($tx['type']) {
                            'payment'      => 'bg-green-100 text-green-700',
                            'adjustment'   => 'bg-yellow-100 text-yellow-700',
                            'fee_assigned' => 'bg-blue-100 text-blue-700',
                            'fee_removed'  => 'bg-red-100 text-red-700',
                            'penalty'      => 'bg-orange-100 text-orange-700',
                            'refund'       => 'bg-purple-100 text-purple-700',
                            default        => 'bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300',
                        };
                    ?>
                    <tr class="hover:bg-gray-50 dark:bg-dark-bg">
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted" data-label="Date"><?= format_datetime($tx['created_at']) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Type">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $typeBadge ?>"><?= ucwords(str_replace('_', ' ', $tx['type'])) ?></span>
                        </td>
                        <td class="px-4 py-3 text-sm" data-label="Description"><?= e($tx['description'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm font-semibold <?= $tx['amount'] >= 0 ? 'text-green-600' : 'text-red-600' ?>" data-label="Amount"><?= format_money($tx['amount']) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Balance After"><?= $tx['balance_after'] !== null ? format_money($tx['balance_after']) : '—' ?></td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted" data-label="Channel"><?= e($tx['channel'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted" data-label="Reference"><?= e($tx['reference'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MODALS
     ═══════════════════════════════════════════════════════════ -->

<!-- Assign Fee Modal -->
<div id="assignFeeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white dark:bg-dark-card rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Assign Fee</h3>
        <form method="POST" action="<?= url('finance', 'assign-fee') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="student_id" value="<?= $id ?>">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Fee *</label>
                    <select name="fee_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">— Select Fee —</option>
                        <?php foreach ($allFees as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= e($f['description']) ?> (<?= format_money($f['amount']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="this.closest('#assignFeeModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">Assign</button>
            </div>
        </form>
    </div>
</div>

<!-- Remove Fee Modal -->
<div id="removeFeeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white dark:bg-dark-card rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Remove Fee</h3>
        <form method="POST" action="<?= url('finance', 'remove-fee') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="student_id" value="<?= $id ?>">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Active Fee to Remove *</label>
                    <select name="student_fee_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">— Select Fee —</option>
                        <?php
                        $activeFeeList = db_fetch_all(
                            "SELECT sf.id, f.description, sf.amount, sf.balance
                               FROM fin_student_fees sf JOIN fin_fees f ON sf.fee_id = f.id
                              WHERE sf.student_id = ? AND sf.is_active = 1 ORDER BY f.description", [$id]
                        );
                        foreach ($activeFeeList as $af): ?>
                        <option value="<?= $af['id'] ?>"><?= e($af['description']) ?> — Balance: <?= format_money($af['balance']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason</label>
                    <input type="text" name="reason" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm" placeholder="Optional reason">
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="this.closest('#removeFeeModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 font-medium">Remove</button>
            </div>
        </form>
    </div>
</div>

<!-- Adjust Balance Modal -->
<div id="adjustModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white dark:bg-dark-card rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Adjust Balance</h3>
        <form method="POST" action="<?= url('finance', 'adjust-balance') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="student_id" value="<?= $id ?>">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Fee *</label>
                    <select name="student_fee_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">— Select Fee —</option>
                        <?php foreach ($activeFeeList as $af): ?>
                        <option value="<?= $af['id'] ?>"><?= e($af['description']) ?> — Balance: <?= format_money($af['balance']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adjustment Amount *</label>
                    <input type="number" name="amount" step="0.01" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm" placeholder="Positive to credit, negative to debit">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason *</label>
                    <input type="text" name="reason" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm" placeholder="Reason for adjustment">
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="this.closest('#adjustModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg text-sm hover:bg-yellow-700 font-medium">Adjust</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = $pageTitle;
include TEMPLATES_PATH . '/layout.php';
