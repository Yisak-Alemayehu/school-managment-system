<?php
/**
 * Fee Management — Dashboard
 * Summary cards, recent activity, quick actions
 */
$pageTitle = 'Fee Management Dashboard';

// ── Stats ────────────────────────────────────────────────────
$totalActiveFees = db_fetch_value("SELECT COUNT(*) FROM fees WHERE status = 'active' AND deleted_at IS NULL") ?: 0;
$totalDraftFees  = db_fetch_value("SELECT COUNT(*) FROM fees WHERE status = 'draft' AND deleted_at IS NULL") ?: 0;

$outstandingAmount = db_fetch_value("
    SELECT COALESCE(SUM(amount), 0) FROM student_fee_charges WHERE status IN ('pending','overdue')
") ?: 0;

$collectedThisMonth = db_fetch_value("
    SELECT COALESCE(SUM(paid_amount), 0) FROM student_fee_charges 
    WHERE status = 'paid' AND paid_at >= ?
", [date('Y-m-01')]) ?: 0;

$overdueCharges = db_fetch_value("
    SELECT COUNT(*) FROM student_fee_charges WHERE status = 'overdue'
") ?: 0;

$totalPenalties = db_fetch_value("
    SELECT COALESCE(SUM(penalty_amount), 0) FROM penalty_charges WHERE applied_at >= ?
", [date('Y-m-01')]) ?: 0;

$totalGroups = db_fetch_value("SELECT COUNT(*) FROM student_groups WHERE status = 'active'") ?: 0;

$totalExemptions = db_fetch_value("SELECT COUNT(*) FROM fee_exemptions") ?: 0;

// Outstanding by class
$outstandingByClass = db_fetch_all("
    SELECT c.name AS class_name, COUNT(sfc.id) AS charge_count, 
           COALESCE(SUM(sfc.amount), 0) AS total_amount
    FROM student_fee_charges sfc
    JOIN students s ON s.id = sfc.student_id
    LEFT JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
    LEFT JOIN classes c ON c.id = e.class_id
    WHERE sfc.status IN ('pending','overdue')
    GROUP BY c.id
    ORDER BY total_amount DESC
    LIMIT 10
");

// Recent activity
$recentActivity = db_fetch_all("
    SELECT fal.*, u.full_name AS user_name
    FROM finance_audit_log fal
    LEFT JOIN users u ON u.id = fal.user_id
    ORDER BY fal.created_at DESC
    LIMIT 10
");

ob_start();
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Fee Management</h1>
            <p class="text-sm text-gray-500 mt-1">Overview of fees, charges, and collections</p>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('finance', 'fm-create-fee') ?>"
               class="inline-flex items-center gap-2 px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Fee
            </a>
            <a href="<?= url('finance', 'fm-groups') ?>"
               class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                Manage Groups
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-50 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900"><?= $totalActiveFees ?></p>
                    <p class="text-xs text-gray-500">Active Fees</p>
                </div>
            </div>
            <?php if ($totalDraftFees > 0): ?>
            <p class="text-xs text-amber-600 mt-2"><?= $totalDraftFees ?> draft(s)</p>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-50 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-red-700"><?= CURRENCY_SYMBOL ?> <?= number_format($outstandingAmount, 2) ?></p>
                    <p class="text-xs text-gray-500">Outstanding</p>
                </div>
            </div>
            <?php if ($overdueCharges > 0): ?>
            <p class="text-xs text-red-600 mt-2"><?= $overdueCharges ?> overdue charge(s)</p>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-50 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-green-700"><?= CURRENCY_SYMBOL ?> <?= number_format($collectedThisMonth, 2) ?></p>
                    <p class="text-xs text-gray-500">Collected This Month</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-50 rounded-lg">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-amber-700"><?= CURRENCY_SYMBOL ?> <?= number_format($totalPenalties, 2) ?></p>
                    <p class="text-xs text-gray-500">Penalties This Month</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row: Quick Stats -->
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm border p-4 text-center">
            <p class="text-lg font-bold text-gray-900"><?= $totalGroups ?></p>
            <p class="text-xs text-gray-500">Student Groups</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4 text-center">
            <p class="text-lg font-bold text-gray-900"><?= $totalExemptions ?></p>
            <p class="text-xs text-gray-500">Exemptions</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4 text-center">
            <p class="text-lg font-bold text-gray-900"><?= $overdueCharges ?></p>
            <p class="text-xs text-gray-500">Overdue Charges</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Outstanding by Class -->
        <div class="bg-white rounded-xl shadow-sm border">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Outstanding by Class</h2>
            </div>
            <div class="p-6">
                <?php if (empty($outstandingByClass)): ?>
                    <p class="text-gray-500 text-sm text-center py-4">No outstanding charges.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($outstandingByClass as $row): ?>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-700"><?= e($row['class_name'] ?? 'Unassigned') ?></span>
                                <span class="text-xs text-gray-400">(<?= $row['charge_count'] ?> charges)</span>
                            </div>
                            <span class="text-sm font-bold text-red-700"><?= CURRENCY_SYMBOL ?> <?= number_format($row['total_amount'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-sm border">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Recent Activity</h2>
            </div>
            <div class="divide-y divide-gray-100">
                <?php if (empty($recentActivity)): ?>
                    <p class="text-gray-500 text-sm text-center py-8">No recent activity.</p>
                <?php else: ?>
                    <?php foreach ($recentActivity as $log): ?>
                    <div class="px-6 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-900"><?= e(ucwords(str_replace('_', ' ', $log['action']))) ?></p>
                            <p class="text-xs text-gray-500"><?= e($log['user_name'] ?? 'System') ?></p>
                        </div>
                        <span class="text-xs text-gray-400"><?= time_ago($log['created_at']) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
