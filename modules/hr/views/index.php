<?php
/**
 * HR — Dashboard / Index View
 */

$totalEmployees = db_fetch_value("SELECT COUNT(*) FROM hr_employees WHERE deleted_at IS NULL AND status = 'active'");
$totalDepts     = db_fetch_value("SELECT COUNT(*) FROM hr_departments WHERE deleted_at IS NULL AND status = 'active'");
$pendingLeave   = db_fetch_value("SELECT COUNT(*) FROM hr_leave_requests WHERE status = 'pending'");
$ecToday        = ec_today();

// Recent employees
$recentEmployees = db_fetch_all(
    "SELECT e.id, e.employee_id, e.first_name, e.father_name, e.position, d.name AS department_name, e.created_at
     FROM hr_employees e
     LEFT JOIN hr_departments d ON e.department_id = d.id
     WHERE e.deleted_at IS NULL
     ORDER BY e.created_at DESC LIMIT 5"
);

// Latest payroll period
$latestPeriod = db_fetch_one(
    "SELECT pp.*, 
            (SELECT COUNT(*) FROM hr_payroll_records WHERE payroll_period_id = pp.id) AS emp_count,
            (SELECT SUM(net_salary) FROM hr_payroll_records WHERE payroll_period_id = pp.id) AS total_net
     FROM hr_payroll_periods pp
     ORDER BY pp.year_ec DESC, pp.month_ec DESC LIMIT 1"
);

ob_start();
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">HR Management</h1>
        <span class="text-sm text-gray-500 dark:text-dark-muted"><?= ec_format_display($ecToday['date_ec']) ?></span>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-dark-text"><?= $totalEmployees ?></p>
                    <p class="text-xs text-gray-500 dark:text-dark-muted">Active Employees</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-dark-text"><?= $totalDepts ?></p>
                    <p class="text-xs text-gray-500 dark:text-dark-muted">Departments</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-dark-text"><?= $pendingLeave ?></p>
                    <p class="text-xs text-gray-500 dark:text-dark-muted">Pending Leave</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <?php if ($latestPeriod): ?>
                    <p class="text-2xl font-bold text-gray-900 dark:text-dark-text"><?= number_format($latestPeriod['total_net'] ?? 0, 2) ?></p>
                    <p class="text-xs text-gray-500 dark:text-dark-muted">Latest Payroll (Br)</p>
                    <?php else: ?>
                    <p class="text-2xl font-bold text-gray-900 dark:text-dark-text">—</p>
                    <p class="text-xs text-gray-500 dark:text-dark-muted">No Payroll Yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-3">Quick Actions</h2>
        <div class="flex flex-wrap gap-2">
            <a href="<?= url('hr', 'employee-form') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Employee
            </a>
            <a href="<?= url('hr', 'attendance') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Mark Attendance
            </a>
            <a href="<?= url('hr', 'leave-requests') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white text-sm rounded-lg hover:bg-amber-700 font-medium">
                Leave Requests <?php if ($pendingLeave > 0): ?><span class="bg-white/20 text-xs px-1.5 py-0.5 rounded-full"><?= $pendingLeave ?></span><?php endif; ?>
            </a>
            <a href="<?= url('hr', 'payroll') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 font-medium">
                Payroll
            </a>
        </div>
    </div>

    <!-- Recent Employees -->
    <?php if (!empty($recentEmployees)): ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-dark-border">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text">Recently Added Employees</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Position</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Department</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php foreach ($recentEmployees as $emp): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg transition-colors">
                        <td class="px-4 py-3 text-sm font-mono text-gray-600 dark:text-dark-muted"><?= e($emp['employee_id']) ?></td>
                        <td class="px-4 py-3 text-sm">
                            <a href="<?= url('hr', 'employee-detail', $emp['id']) ?>" class="text-primary-600 hover:text-primary-800 font-semibold">
                                <?= e($emp['first_name'] . ' ' . $emp['father_name']) ?>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted"><?= e($emp['position']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted"><?= e($emp['department_name'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted"><?= date('M d, Y', strtotime($emp['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
