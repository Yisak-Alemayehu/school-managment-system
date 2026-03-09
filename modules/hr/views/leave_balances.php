<?php
/**
 * HR — Leave Balances View
 * Shows all employees' leave balances with remaining days.
 */

$deptId = input_int('department_id');
$where  = ["e.deleted_at IS NULL", "e.status = 'active'"];
$params = [];

if ($deptId) {
    $where[] = "e.department_id = ?";
    $params[] = $deptId;
}

$whereStr = implode(' AND ', $where);
$employees = db_fetch_all(
    "SELECT e.id, e.employee_id, CONCAT(e.first_name, ' ', e.father_name) AS name,
            d.name AS department_name
     FROM hr_employees e
     LEFT JOIN hr_departments d ON e.department_id = d.id
     WHERE {$whereStr}
     ORDER BY e.first_name",
    $params
);

$leaveTypes = db_fetch_all("SELECT id, name, days_allowed FROM hr_leave_types WHERE status = 'active' ORDER BY name");
$departments = db_fetch_all("SELECT id, name FROM hr_departments WHERE deleted_at IS NULL AND status = 'active' ORDER BY name");

// Build balance map: emp_id => [leave_type_id => days_used]
$usedMap = [];
$usage = db_fetch_all(
    "SELECT lr.employee_id, lr.leave_type_id, SUM(lr.days) AS used
     FROM hr_leave_requests lr
     WHERE lr.status = 'approved' AND YEAR(lr.created_at) = YEAR(CURDATE())
     GROUP BY lr.employee_id, lr.leave_type_id"
);
foreach ($usage as $u) {
    $usedMap[$u['employee_id']][$u['leave_type_id']] = (float)$u['used'];
}

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Leave Balances</h1>
        <span class="text-sm text-gray-500 dark:text-dark-muted">Year: <?= date('Y') ?></span>
    </div>

    <!-- Filter -->
    <form method="GET" action="<?= url('hr', 'leave-balances') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
        <div class="flex gap-3 items-end">
            <div class="flex-1">
                <select name="department_id" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $deptId == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900 font-medium">Filter</button>
        </div>
    </form>

    <!-- Balance Table -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border text-sm">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Department</th>
                        <?php foreach ($leaveTypes as $lt): ?>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase"><?= e($lt['name']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php if (empty($employees)): ?>
                    <tr><td colspan="<?= 2 + count($leaveTypes) ?>" class="px-4 py-8 text-center text-gray-400">No employees found.</td></tr>
                    <?php else: foreach ($employees as $emp): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg">
                        <td class="px-4 py-3">
                            <a href="<?= url('hr', 'employee-detail', $emp['id']) ?>" class="text-primary-600 hover:text-primary-800 font-medium"><?= e($emp['name']) ?></a>
                            <div class="text-xs text-gray-400"><?= e($emp['employee_id']) ?></div>
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-dark-muted"><?= e($emp['department_name'] ?? '—') ?></td>
                        <?php foreach ($leaveTypes as $lt):
                            $used = $usedMap[$emp['id']][$lt['id']] ?? 0;
                            $remaining = $lt['days_allowed'] - $used;
                            $pct = $lt['days_allowed'] > 0 ? min(100, ($used / $lt['days_allowed']) * 100) : 0;
                            $color = $pct >= 90 ? 'text-red-600' : ($pct >= 60 ? 'text-amber-600' : 'text-green-600');
                        ?>
                        <td class="px-3 py-3 text-center">
                            <span class="font-medium <?= $color ?>"><?= $remaining ?></span>
                            <span class="text-xs text-gray-400">/<?= $lt['days_allowed'] ?></span>
                            <div class="w-full bg-gray-200 rounded-full h-1 mt-1 dark:bg-dark-border">
                                <div class="h-1 rounded-full <?= $pct >= 90 ? 'bg-red-500' : ($pct >= 60 ? 'bg-amber-500' : 'bg-green-500') ?>" style="width: <?= $pct ?>%"></div>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-500 dark:text-dark-muted">Total: <?= count($employees) ?> employee(s)</p>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
