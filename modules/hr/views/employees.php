<?php
/**
 * HR — Employees List View
 */

$search  = trim(input('search'));
$deptId  = input_int('department_id');
$status  = input('status') ?: 'active';
$page    = max(1, input_int('page') ?: 1);
$perPage = 20;

$where  = ["e.deleted_at IS NULL"];
$params = [];

if ($search) {
    $where[] = "(e.first_name LIKE ? OR e.father_name LIKE ? OR e.employee_id LIKE ? OR e.phone LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}
if ($deptId) {
    $where[] = "e.department_id = ?";
    $params[] = $deptId;
}
if ($status) {
    $where[] = "e.status = ?";
    $params[] = $status;
}

$whereStr = implode(' AND ', $where);

$totalRows = (int)db_fetch_value("SELECT COUNT(*) FROM hr_employees e WHERE {$whereStr}", $params);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$employees = db_fetch_all(
    "SELECT e.*, d.name AS department_name
     FROM hr_employees e
     LEFT JOIN hr_departments d ON e.department_id = d.id
     WHERE {$whereStr}
     ORDER BY e.first_name
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$departments = db_fetch_all("SELECT id, name FROM hr_departments WHERE deleted_at IS NULL AND status = 'active' ORDER BY name");

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Employees</h1>
        <a href="<?= url('hr', 'employee-form') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Employee
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?= url('hr', 'employees') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, ID, phone..."
                   class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
            <select name="department_id" class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $deptId == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="left" <?= $status === 'left' ? 'selected' : '' ?>>Left</option>
                <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                <option value="" <?= $status === '' ? 'selected' : '' ?>>All Status</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900 font-medium">Filter</button>
        </div>
    </form>

    <!-- Employees Table -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border responsive-table">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Employee ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Position</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Department</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Phone</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Salary (Br)</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php if (empty($employees)): ?>
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No employees found.</td></tr>
                    <?php else: foreach ($employees as $emp): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg transition-colors">
                        <td class="px-4 py-3 text-sm font-mono text-gray-600 dark:text-dark-muted" data-label="ID"><?= e($emp['employee_id']) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Name">
                            <a href="<?= url('hr', 'employee-detail', $emp['id']) ?>" class="text-primary-600 hover:text-primary-800 font-semibold">
                                <?= e($emp['first_name'] . ' ' . $emp['father_name'] . ' ' . $emp['grandfather_name']) ?>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Position"><?= e($emp['position']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Department"><?= e($emp['department_name'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Phone"><?= e($emp['phone'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Salary"><?= number_format($emp['basic_salary'], 2) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Status">
                            <?php
                            $statusColors = ['active' => 'bg-green-100 text-green-700', 'left' => 'bg-gray-100 text-gray-600', 'suspended' => 'bg-red-100 text-red-700'];
                            ?>
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $statusColors[$emp['status']] ?? 'bg-gray-100 text-gray-600' ?>">
                                <?= ucfirst($emp['status']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm" data-label="Actions">
                            <div class="flex items-center gap-2">
                                <a href="<?= url('hr', 'employee-detail', $emp['id']) ?>" class="text-blue-600 hover:text-blue-800" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="<?= url('hr', 'employee-form', $emp['id']) ?>" class="text-amber-600 hover:text-amber-800" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex items-center justify-between">
        <p class="text-xs text-gray-500 dark:text-dark-muted">
            Showing <?= count($employees) ?> of <?= $totalRows ?> employee(s)
            <?php if ($totalPages > 1): ?> — Page <?= $page ?> of <?= $totalPages ?><?php endif; ?>
        </p>
        <?php if ($totalPages > 1): ?>
        <nav class="flex items-center gap-1">
            <?php
            $queryParams = array_filter(['search' => $search, 'department_id' => $deptId, 'status' => $status]);
            $buildUrl = function($p) use ($queryParams) {
                return url('hr', 'employees') . '?' . http_build_query(array_merge($queryParams, ['page' => $p]));
            };
            ?>
            <?php if ($page > 1): ?>
            <a href="<?= $buildUrl($page - 1) ?>" class="px-2.5 py-1 text-xs border border-gray-300 dark:border-dark-border rounded-lg hover:bg-gray-100 dark:hover:bg-dark-bg text-gray-600 dark:text-dark-muted">&laquo; Prev</a>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
            <a href="<?= $buildUrl($i) ?>" class="px-2.5 py-1 text-xs border rounded-lg font-medium <?= $i === $page ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-300 dark:border-dark-border text-gray-600 dark:text-dark-muted hover:bg-gray-100 dark:hover:bg-dark-bg' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="<?= $buildUrl($page + 1) ?>" class="px-2.5 py-1 text-xs border border-gray-300 dark:border-dark-border rounded-lg hover:bg-gray-100 dark:hover:bg-dark-bg text-gray-600 dark:text-dark-muted">Next &raquo;</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
