<?php
/**
 * Finance — Dashboard
 * Overview page with stat cards and recent payments.
 */

// Stat queries
$totalAssigned = (float) db_fetch_value(
    "SELECT COALESCE(SUM(amount), 0) FROM fin_student_fees WHERE is_active = 1"
);
$totalCollected = (float) db_fetch_value(
    "SELECT COALESCE(SUM(ABS(amount)), 0) FROM fin_transactions WHERE type = 'payment'"
);
$totalOutstanding = (float) db_fetch_value(
    "SELECT COALESCE(SUM(balance), 0) FROM fin_student_fees WHERE is_active = 1 AND balance > 0"
);
$totalPenalties = (float) db_fetch_value(
    "SELECT COALESCE(SUM(amount), 0) FROM fin_transactions WHERE type = 'penalty'"
);
$collectionRate = $totalAssigned > 0 ? round(($totalCollected / $totalAssigned) * 100, 1) : 0;

// Recent 10 payments
$recentPayments = db_fetch_all(
    "SELECT t.*, s.full_name, s.admission_no, f.description AS fee_desc
       FROM fin_transactions t
       JOIN students s ON t.student_id = s.id
       LEFT JOIN fin_student_fees sf ON t.student_fee_id = sf.id
       LEFT JOIN fin_fees f ON sf.fee_id = f.id
      WHERE t.type = 'payment'
      ORDER BY t.created_at DESC
      LIMIT 10"
);

ob_start();
?>

<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Finance Dashboard</h1>
        <div class="flex gap-2">
            <a href="<?= url('finance', 'collect-payment') ?>"
               class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 font-medium inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Collect Payment
            </a>
            <a href="<?= url('finance', 'students') ?>"
               class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">
                Students
            </a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <p class="text-xs text-blue-600 uppercase font-semibold">Total Fees Assigned</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-dark-text mt-1"><?= format_money($totalAssigned) ?></p>
        </div>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <p class="text-xs text-green-600 uppercase font-semibold">Total Collected</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-dark-text mt-1"><?= format_money($totalCollected) ?></p>
        </div>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <p class="text-xs text-red-600 uppercase font-semibold">Total Outstanding</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-dark-text mt-1"><?= format_money($totalOutstanding) ?></p>
        </div>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <p class="text-xs text-orange-600 uppercase font-semibold">Total Penalties</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-dark-text mt-1"><?= format_money($totalPenalties) ?></p>
        </div>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <p class="text-xs text-purple-600 uppercase font-semibold">Collection Rate</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-dark-text mt-1"><?= $collectionRate ?>%</p>
            <div class="mt-2 w-full bg-gray-200 dark:bg-dark-border rounded-full h-2">
                <div class="bg-purple-600 h-2 rounded-full" style="width: <?= min(100, $collectionRate) ?>%"></div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
        <a href="<?= url('finance', 'fee-due') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 text-center hover:bg-gray-50 dark:hover:bg-dark-bg transition-colors">
            <p class="text-sm font-medium text-gray-900 dark:text-dark-text">Fee Management</p>
        </a>
        <a href="<?= url('finance', 'supplementary-fees') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 text-center hover:bg-gray-50 dark:hover:bg-dark-bg transition-colors">
            <p class="text-sm font-medium text-gray-900 dark:text-dark-text">Supplementary Fees</p>
        </a>
        <a href="<?= url('finance', 'payments') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 text-center hover:bg-gray-50 dark:hover:bg-dark-bg transition-colors">
            <p class="text-sm font-medium text-gray-900 dark:text-dark-text">Payments</p>
        </a>
        <a href="<?= url('finance', 'groups') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 text-center hover:bg-gray-50 dark:hover:bg-dark-bg transition-colors">
            <p class="text-sm font-medium text-gray-900 dark:text-dark-text">Groups</p>
        </a>
        <a href="<?= url('finance', 'report-students') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 text-center hover:bg-gray-50 dark:hover:bg-dark-bg transition-colors">
            <p class="text-sm font-medium text-gray-900 dark:text-dark-text">Reports</p>
        </a>
        <a href="<?= url('finance', 'collect-supplementary-payment') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 text-center hover:bg-gray-50 dark:hover:bg-dark-bg transition-colors">
            <p class="text-sm font-medium text-gray-900 dark:text-dark-text">Supp. Payments</p>
        </a>
    </div>

    <!-- Recent Payments -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-dark-border">
            <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text">Recent Payments</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border responsive-table">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Fee</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Channel</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Receipt</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php if (empty($recentPayments)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No payments recorded yet.</td></tr>
                    <?php else: ?>
                    <?php foreach ($recentPayments as $rp): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted"><?= format_datetime($rp['created_at']) ?></td>
                        <td class="px-4 py-3 text-sm font-medium">
                            <a href="<?= url('finance', 'student-detail', $rp['student_id']) ?>" class="text-primary-600 hover:underline"><?= e($rp['full_name']) ?></a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted"><?= e($rp['fee_desc'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm font-semibold text-green-600"><?= format_money(abs($rp['amount'])) ?></td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300">
                                <?= ucfirst(str_replace('_', ' ', $rp['channel'] ?? '—')) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted"><?= e($rp['receipt_no'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm">
                            <a href="<?= url('finance', 'payment-attachment', $rp['id']) ?>" target="_blank"
                               class="inline-flex items-center gap-1 px-2 py-1 bg-indigo-50 text-indigo-700 text-xs rounded-lg hover:bg-indigo-100 font-medium border border-indigo-200">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print
                            </a>
                        </td>
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
