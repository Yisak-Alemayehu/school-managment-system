<?php
/**
 * HR — Reports Dashboard View
 * Full analytics dashboard with summary cards, charts, and drill-down reports.
 */

require_once APP_ROOT . '/core/ethiopian_calendar.php';
require_once APP_ROOT . '/core/payroll.php';

$ecToday = ec_today();
$currentYear = (int)$ecToday['year'];
$currentMonth = (int)$ecToday['month'];

// ── Summary Statistics ──────────────────────────────────────
$totalActive = (int)db_fetch_value("SELECT COUNT(*) FROM hr_employees WHERE deleted_at IS NULL AND status='active'");

$presentToday = (int)db_fetch_value(
    "SELECT COUNT(*) FROM hr_attendance WHERE date_gregorian = CURDATE() AND status='present'"
);

$onLeaveToday = (int)db_fetch_value(
    "SELECT COUNT(*) FROM hr_leave_requests WHERE status='approved' AND start_date_gregorian <= CURDATE() AND end_date_gregorian >= CURDATE()"
);

$pendingLeave = (int)db_fetch_value("SELECT COUNT(*) FROM hr_leave_requests WHERE status='pending'");

// Latest payroll period stats
$latestPayroll = db_fetch_one(
    "SELECT pp.*,
            (SELECT COUNT(*) FROM hr_payroll_records WHERE payroll_period_id = pp.id) AS emp_count,
            (SELECT SUM(gross_salary) FROM hr_payroll_records WHERE payroll_period_id = pp.id) AS total_gross,
            (SELECT SUM(income_tax) FROM hr_payroll_records WHERE payroll_period_id = pp.id) AS total_tax,
            (SELECT SUM(employee_pension + employer_pension) FROM hr_payroll_records WHERE payroll_period_id = pp.id) AS total_pension,
            (SELECT SUM(net_salary) FROM hr_payroll_records WHERE payroll_period_id = pp.id) AS total_net
     FROM hr_payroll_periods pp
     WHERE pp.status IN ('generated','approved','paid')
     ORDER BY pp.year_ec DESC, pp.month_ec DESC LIMIT 1"
);

$monthlyPayrollCost = $latestPayroll['total_gross'] ?? 0;
$totalTaxWithheld   = $latestPayroll['total_tax'] ?? 0;
$totalPension       = $latestPayroll['total_pension'] ?? 0;

// ── Payroll Trend (last 6 months) ───────────────────────────
$payrollTrend = db_fetch_all(
    "SELECT pp.month_ec, pp.year_ec, pp.month_name_ec,
            SUM(pr.gross_salary) AS gross,
            SUM(pr.net_salary) AS net,
            SUM(pr.income_tax) AS tax,
            SUM(pr.employee_pension + pr.employer_pension) AS pension
     FROM hr_payroll_periods pp
     JOIN hr_payroll_records pr ON pr.payroll_period_id = pp.id
     WHERE pp.status IN ('generated','approved','paid')
     GROUP BY pp.id
     ORDER BY pp.year_ec DESC, pp.month_ec DESC
     LIMIT 6"
);
$payrollTrend = array_reverse($payrollTrend);

// ── Department Salary Distribution ──────────────────────────
$deptSalary = db_fetch_all(
    "SELECT d.name, SUM(e.basic_salary) AS total_salary, COUNT(e.id) AS emp_count
     FROM hr_departments d
     JOIN hr_employees e ON e.department_id = d.id AND e.deleted_at IS NULL AND e.status='active'
     WHERE d.deleted_at IS NULL
     GROUP BY d.id
     ORDER BY total_salary DESC"
);

// ── Attendance This Month ───────────────────────────────────
$attendanceSummary = db_fetch_one(
    "SELECT 
        SUM(status='present') AS present_count,
        SUM(status='absent') AS absent_count,
        SUM(status='late') AS late_count,
        SUM(status='leave') AS leave_count,
        COUNT(*) AS total_records
     FROM hr_attendance
     WHERE MONTH(date_gregorian) = MONTH(CURDATE()) AND YEAR(date_gregorian) = YEAR(CURDATE())"
);

// ── Leave Utilization by Type ───────────────────────────────
$leaveUtilization = db_fetch_all(
    "SELECT lt.name, 
            COUNT(lr.id) AS request_count,
            SUM(lr.days) AS total_days,
            SUM(lr.status='approved') AS approved_count,
            SUM(lr.status='rejected') AS rejected_count,
            SUM(lr.status='pending') AS pending_count
     FROM hr_leave_types lt
     LEFT JOIN hr_leave_requests lr ON lr.leave_type_id = lt.id AND YEAR(lr.created_at) = YEAR(CURDATE())
     WHERE lt.status='active'
     GROUP BY lt.id
     ORDER BY total_days DESC"
);

// ── Gender Distribution ─────────────────────────────────────
$genderDist = db_fetch_all(
    "SELECT gender, COUNT(*) AS cnt
     FROM hr_employees WHERE deleted_at IS NULL AND status='active'
     GROUP BY gender"
);

$ecMonths = ec_month_names();

ob_start();
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">HR Reports Dashboard</h1>
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500 dark:text-dark-muted"><?= ec_format_display($ecToday['date_ec']) ?></span>
        </div>
    </div>

    <!-- ═══ Summary Cards ═══ -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-dark-text"><?= $totalActive ?></p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Active Employees</p>
        </div>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-green-700 dark:text-green-400"><?= $presentToday ?></p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Present Today</p>
        </div>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-amber-600"><?= $onLeaveToday ?></p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">On Leave</p>
        </div>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-dark-text"><?= number_format($monthlyPayrollCost, 0) ?></p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Monthly Payroll (Br)</p>
        </div>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-red-600"><?= number_format($totalTaxWithheld, 0) ?></p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Tax Withheld (Br)</p>
        </div>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-teal-600"><?= number_format($totalPension, 0) ?></p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Total Pension (Br)</p>
        </div>
    </div>

    <!-- ═══ Charts Row ═══ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Payroll Trend Chart -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4">Payroll Trend (Monthly)</h2>
            <canvas id="payrollTrendChart" height="220"></canvas>
        </div>

        <!-- Department Salary Distribution -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4">Department Salary Distribution</h2>
            <canvas id="deptSalaryChart" height="220"></canvas>
        </div>

        <!-- Attendance This Month -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4">Attendance This Month</h2>
            <canvas id="attendanceChart" height="220"></canvas>
        </div>

        <!-- Leave Utilization -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4">Leave Utilization (This Year)</h2>
            <canvas id="leaveChart" height="220"></canvas>
        </div>
    </div>

    <!-- ═══ Reports Section ═══ -->
    <div>
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Generate Reports</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Monthly Payroll Report -->
            <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-dark-text">Monthly Payroll Report</h3>
                        <p class="text-xs text-gray-500 dark:text-dark-muted">Detailed payroll for any period</p>
                    </div>
                </div>
                <a href="<?= url('hr', 'payroll') ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 font-medium">View Payroll Periods</a>
            </div>

            <!-- Tax Report -->
            <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-dark-text">Income Tax Report</h3>
                        <p class="text-xs text-gray-500 dark:text-dark-muted">Schedule A declaration forms</p>
                    </div>
                </div>
                <a href="<?= url('hr', 'payroll-printing') ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 font-medium">Print Tax Forms</a>
            </div>

            <!-- Pension Report -->
            <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-dark-text">Pension Report</h3>
                        <p class="text-xs text-gray-500 dark:text-dark-muted">Pension contribution declarations</p>
                    </div>
                </div>
                <a href="<?= url('hr', 'payroll-printing') ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-teal-600 text-white text-xs rounded-lg hover:bg-teal-700 font-medium">Print Pension Forms</a>
            </div>

            <!-- Attendance Report -->
            <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-dark-text">Attendance Report</h3>
                        <p class="text-xs text-gray-500 dark:text-dark-muted">Summary and detailed view</p>
                    </div>
                </div>
                <a href="<?= url('hr', 'attendance-report') ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 font-medium">View Report</a>
            </div>

            <!-- Leave Report -->
            <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-dark-text">Leave Report</h3>
                        <p class="text-xs text-gray-500 dark:text-dark-muted">Leave requests and balances</p>
                    </div>
                </div>
                <a href="<?= url('hr', 'leave-requests') ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-600 text-white text-xs rounded-lg hover:bg-amber-700 font-medium">View Leaves</a>
            </div>

            <!-- Employee Salary History -->
            <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-dark-text">Employee Directory</h3>
                        <p class="text-xs text-gray-500 dark:text-dark-muted">Search, filter, and export</p>
                    </div>
                </div>
                <a href="<?= url('hr', 'employees') ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-purple-600 text-white text-xs rounded-lg hover:bg-purple-700 font-medium">View Employees</a>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Chart Initialization ═══ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var isDark = document.documentElement.classList.contains('dark');
    var gridColor = isDark ? 'rgba(148,163,184,0.15)' : 'rgba(0,0,0,0.06)';
    var textColor = isDark ? '#94a3b8' : '#6b7280';

    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;

    // ── Payroll Trend ──
    var ptLabels = <?= json_encode(array_map(function($p) { return ($p['month_name_ec'] ?? $p['month_ec']) . ' ' . $p['year_ec']; }, $payrollTrend)) ?>;
    var ptGross  = <?= json_encode(array_map(function($p) { return (float)$p['gross']; }, $payrollTrend)) ?>;
    var ptNet    = <?= json_encode(array_map(function($p) { return (float)$p['net']; }, $payrollTrend)) ?>;
    var ptTax    = <?= json_encode(array_map(function($p) { return (float)$p['tax']; }, $payrollTrend)) ?>;

    new Chart(document.getElementById('payrollTrendChart'), {
        type: 'line',
        data: {
            labels: ptLabels,
            datasets: [
                { label: 'Gross', data: ptGross, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.3 },
                { label: 'Net', data: ptNet, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.3 },
                { label: 'Tax', data: ptTax, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: false, tension: 0.3 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: function(v) { return v.toLocaleString() + ' Br'; } } }
            }
        }
    });

    // ── Department Salary ──
    var dsLabels = <?= json_encode(array_column($deptSalary, 'name')) ?>;
    var dsValues = <?= json_encode(array_map(function($d) { return (float)$d['total_salary']; }, $deptSalary)) ?>;
    var dsColors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16','#f97316','#6366f1'];

    new Chart(document.getElementById('deptSalaryChart'), {
        type: 'doughnut',
        data: {
            labels: dsLabels,
            datasets: [{ data: dsValues, backgroundColor: dsColors.slice(0, dsLabels.length), borderWidth: 2, borderColor: isDark ? '#1e293b' : '#fff' }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 10, padding: 8, font: { size: 11 } } }
            }
        }
    });

    // ── Attendance Pie ──
    var attData = [
        <?= (int)($attendanceSummary['present_count'] ?? 0) ?>,
        <?= (int)($attendanceSummary['absent_count'] ?? 0) ?>,
        <?= (int)($attendanceSummary['late_count'] ?? 0) ?>,
        <?= (int)($attendanceSummary['leave_count'] ?? 0) ?>
    ];
    new Chart(document.getElementById('attendanceChart'), {
        type: 'pie',
        data: {
            labels: ['Present', 'Absent', 'Late', 'Leave'],
            datasets: [{ data: attData, backgroundColor: ['#10b981','#ef4444','#f59e0b','#8b5cf6'], borderWidth: 2, borderColor: isDark ? '#1e293b' : '#fff' }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12 } } }
        }
    });

    // ── Leave Utilization ──
    var lvLabels = <?= json_encode(array_column($leaveUtilization, 'name')) ?>;
    var lvApproved = <?= json_encode(array_map(function($l) { return (int)$l['approved_count']; }, $leaveUtilization)) ?>;
    var lvPending  = <?= json_encode(array_map(function($l) { return (int)$l['pending_count']; }, $leaveUtilization)) ?>;
    var lvRejected = <?= json_encode(array_map(function($l) { return (int)$l['rejected_count']; }, $leaveUtilization)) ?>;

    new Chart(document.getElementById('leaveChart'), {
        type: 'bar',
        data: {
            labels: lvLabels,
            datasets: [
                { label: 'Approved', data: lvApproved, backgroundColor: '#10b981' },
                { label: 'Pending', data: lvPending, backgroundColor: '#f59e0b' },
                { label: 'Rejected', data: lvRejected, backgroundColor: '#ef4444' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12 } } },
            scales: {
                x: { stacked: true },
                y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
