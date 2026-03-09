<?php
/**
 * HR — Attendance Marking View
 */

require_once APP_ROOT . '/core/ethiopian_calendar.php';

$selectedDate = input('date') ?: date('Y-m-d');
$deptId = input_int('department_id');

$where = ["e.deleted_at IS NULL", "e.status = 'active'"];
$params = [];

if ($deptId) {
    $where[] = "e.department_id = ?";
    $params[] = $deptId;
}

$whereStr = implode(' AND ', $where);
$employees = db_fetch_all(
    "SELECT e.id, e.employee_id, e.first_name, e.father_name, e.position, d.name AS department_name
     FROM hr_employees e
     LEFT JOIN hr_departments d ON e.department_id = d.id
     WHERE {$whereStr}
     ORDER BY d.name, e.first_name",
    $params
);

// Get existing attendance for the selected date
$existing = [];
if (!empty($employees)) {
    $empIds = array_column($employees, 'id');
    $placeholders = implode(',', array_fill(0, count($empIds), '?'));
    $rows = db_fetch_all(
        "SELECT employee_id, status, check_in, check_out, notes FROM hr_attendance WHERE date_gregorian = ? AND employee_id IN ({$placeholders})",
        array_merge([$selectedDate], $empIds)
    );
    foreach ($rows as $r) {
        $existing[$r['employee_id']] = $r;
    }
}

$departments = db_fetch_all("SELECT id, name FROM hr_departments WHERE deleted_at IS NULL AND status = 'active' ORDER BY name");

$ecDate = gregorian_str_to_ec($selectedDate);

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Mark Attendance</h1>
        <div class="flex gap-2">
            <form method="POST" action="<?= url('hr', 'attendance-process-biometric') ?>" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="date" value="<?= e($selectedDate) ?>">
                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 font-medium" title="Process biometric device scans for selected date">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/></svg>
                    Process Biometric
                </button>
            </form>
            <form method="POST" action="<?= url('hr', 'attendance-mark-absent') ?>" class="inline" onsubmit="return confirm('Mark all employees without attendance as absent for <?= e($selectedDate) ?>?')">
                <?= csrf_field() ?>
                <input type="hidden" name="date" value="<?= e($selectedDate) ?>">
                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 font-medium" title="Auto-mark employees without records as absent">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    Mark Absent
                </button>
            </form>
        </div>
    </div>

    <!-- Date & Filter -->
    <form method="GET" action="<?= url('hr', 'attendance') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Date (GC)</label>
                <input type="date" name="date" value="<?= e($selectedDate) ?>"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Ethiopian Date</label>
                <p class="px-3 py-2 text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($ecDate) ?></p>
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
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900 font-medium">Load</button>
        </div>
    </form>

    <!-- Attendance Form -->
    <?php if (!empty($employees)): ?>
    <form method="POST" action="<?= url('hr', 'attendance-mark') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="date_gregorian" value="<?= e($selectedDate) ?>">

        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border">
                    <thead class="bg-gray-50 dark:bg-dark-bg">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Employee</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Department</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Check In</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Check Out</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                        <?php $n = 0; foreach ($employees as $emp): $n++; $ex = $existing[$emp['id']] ?? []; ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg">
                            <td class="px-4 py-2 text-sm text-gray-500"><?= $n ?></td>
                            <td class="px-4 py-2 text-sm">
                                <span class="font-medium text-gray-900 dark:text-dark-text"><?= e($emp['first_name'] . ' ' . $emp['father_name']) ?></span>
                                <span class="text-xs text-gray-400 ml-1">(<?= e($emp['employee_id']) ?>)</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-dark-muted"><?= e($emp['department_name'] ?? '—') ?></td>
                            <td class="px-4 py-2">
                                <select name="attendance[<?= $emp['id'] ?>][status]" class="px-2 py-1.5 border border-gray-300 dark:border-dark-border rounded text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                                    <?php foreach (['present', 'absent', 'late', 'half_day', 'leave'] as $s): ?>
                                    <option value="<?= $s ?>" <?= ($ex['status'] ?? 'present') === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="px-4 py-2">
                                <input type="time" name="attendance[<?= $emp['id'] ?>][check_in]" value="<?= e($ex['check_in'] ?? '') ?>"
                                       class="px-2 py-1.5 border border-gray-300 dark:border-dark-border rounded text-sm bg-white dark:bg-dark-card dark:text-dark-text w-28">
                            </td>
                            <td class="px-4 py-2">
                                <input type="time" name="attendance[<?= $emp['id'] ?>][check_out]" value="<?= e($ex['check_out'] ?? '') ?>"
                                       class="px-2 py-1.5 border border-gray-300 dark:border-dark-border rounded text-sm bg-white dark:bg-dark-card dark:text-dark-text w-28">
                            </td>
                            <td class="px-4 py-2">
                                <input type="text" name="attendance[<?= $emp['id'] ?>][notes]" value="<?= e($ex['notes'] ?? '') ?>" placeholder="Optional"
                                       class="px-2 py-1.5 border border-gray-300 dark:border-dark-border rounded text-sm bg-white dark:bg-dark-card dark:text-dark-text w-32">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-between items-center mt-4">
            <p class="text-sm text-gray-500 dark:text-dark-muted"><?= count($employees) ?> employee(s)</p>
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">Save Attendance</button>
        </div>
    </form>
    <?php else: ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-8 text-center text-gray-400">
        No active employees found. <?= $deptId ? 'Try selecting a different department.' : '' ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
