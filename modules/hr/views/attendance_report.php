<?php
/**
 * HR — Attendance Report View
 */

require_once APP_ROOT . '/core/ethiopian_calendar.php';

$from   = input('from') ?: date('Y-m-01');
$to     = input('to') ?: date('Y-m-d');
$deptId = input_int('department_id');

$where  = ["e.deleted_at IS NULL", "e.status = 'active'", "a.date_gregorian BETWEEN ? AND ?"];
$params = [$from, $to];

if ($deptId) {
    $where[] = "e.department_id = ?";
    $params[] = $deptId;
}
$whereStr = implode(' AND ', $where);

$report = db_fetch_all(
    "SELECT e.id, e.employee_id, e.first_name, e.father_name, d.name AS department_name,
            SUM(a.status = 'present') AS present_days,
            SUM(a.status = 'absent') AS absent_days,
            SUM(a.status = 'late') AS late_days,
            SUM(a.status = 'half_day') AS half_days,
            SUM(a.status = 'leave') AS leave_days,
            COUNT(a.id) AS total_days
     FROM hr_employees e
     LEFT JOIN hr_attendance a ON a.employee_id = e.id AND a.date_gregorian BETWEEN ? AND ?
     LEFT JOIN hr_departments d ON e.department_id = d.id
     WHERE e.deleted_at IS NULL AND e.status = 'active' " . ($deptId ? "AND e.department_id = ?" : "") . "
     GROUP BY e.id
     ORDER BY d.name, e.first_name",
    $params
);

$departments = db_fetch_all("SELECT id, name FROM hr_departments WHERE deleted_at IS NULL AND status = 'active' ORDER BY name");

$ecFrom = gregorian_str_to_ec($from);
$ecTo   = gregorian_str_to_ec($to);

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Attendance Report</h1>
        <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900 font-medium print:hidden">Print</button>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?= url('hr', 'attendance-report') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 print:hidden">
        <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">From</label>
                <input type="date" name="from" value="<?= e($from) ?>"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">To</label>
                <input type="date" name="to" value="<?= e($to) ?>"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Department</label>
                <select name="department_id" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $deptId == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900 font-medium">Generate</button>
            <p class="text-xs text-gray-500 dark:text-dark-muted">EC: <?= e($ecFrom) ?> — <?= e($ecTo) ?></p>
        </div>
    </form>

    <!-- Report Table -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Department</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-green-600 uppercase">Present</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-red-600 uppercase">Absent</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-amber-600 uppercase">Late</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-blue-600 uppercase">Half Day</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-purple-600 uppercase">Leave</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php if (empty($report)): ?>
                    <tr><td colspan="10" class="px-4 py-8 text-center text-gray-400">No data for the selected period.</td></tr>
                    <?php else: $n = 0; foreach ($report as $r): $n++;
                        $rate = $r['total_days'] > 0 ? round(($r['present_days'] / $r['total_days']) * 100) : 0;
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg">
                        <td class="px-4 py-2 text-sm text-gray-500"><?= $n ?></td>
                        <td class="px-4 py-2 text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($r['first_name'] . ' ' . $r['father_name']) ?></td>
                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-dark-muted"><?= e($r['department_name'] ?? '—') ?></td>
                        <td class="px-4 py-2 text-sm text-center font-medium text-green-700"><?= (int)$r['present_days'] ?></td>
                        <td class="px-4 py-2 text-sm text-center font-medium text-red-600"><?= (int)$r['absent_days'] ?></td>
                        <td class="px-4 py-2 text-sm text-center font-medium text-amber-600"><?= (int)$r['late_days'] ?></td>
                        <td class="px-4 py-2 text-sm text-center font-medium text-blue-600"><?= (int)$r['half_days'] ?></td>
                        <td class="px-4 py-2 text-sm text-center font-medium text-purple-600"><?= (int)$r['leave_days'] ?></td>
                        <td class="px-4 py-2 text-sm text-center font-medium text-gray-900 dark:text-dark-text"><?= (int)$r['total_days'] ?></td>
                        <td class="px-4 py-2 text-sm text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $rate >= 80 ? 'bg-green-100 text-green-700' : ($rate >= 60 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>">
                                <?= $rate ?>%
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
