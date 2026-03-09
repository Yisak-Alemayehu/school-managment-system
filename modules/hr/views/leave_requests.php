<?php
/**
 * HR — Leave Requests View
 */

$status  = input('status') ?: '';
$deptId  = input_int('department_id');

$where  = ["lr.id IS NOT NULL"];
$params = [];

if ($status) {
    $where[] = "lr.status = ?";
    $params[] = $status;
}
if ($deptId) {
    $where[] = "e.department_id = ?";
    $params[] = $deptId;
}

$whereStr = implode(' AND ', $where);
$requests = db_fetch_all(
    "SELECT lr.*, 
            CONCAT(e.first_name, ' ', e.father_name) AS employee_name, e.employee_id AS emp_code,
            lt.name AS leave_type_name,
            d.name AS department_name
     FROM hr_leave_requests lr
     JOIN hr_employees e ON lr.employee_id = e.id
     JOIN hr_leave_types lt ON lr.leave_type_id = lt.id
     LEFT JOIN hr_departments d ON e.department_id = d.id
     WHERE {$whereStr}
     ORDER BY lr.created_at DESC",
    $params
);

$departments = db_fetch_all("SELECT id, name FROM hr_departments WHERE deleted_at IS NULL AND status = 'active' ORDER BY name");

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Leave Requests</h1>
        <a href="<?= url('hr', 'leave-request-form') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Submit Leave
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?= url('hr', 'leave-requests') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <select name="status" class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                <option value="">All Status</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
            <select name="department_id" class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $deptId == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900 font-medium">Filter</button>
        </div>
    </form>

    <!-- Requests Table -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Leave Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">From</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">To</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Days</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Reason</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php if (empty($requests)): ?>
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No leave requests found.</td></tr>
                    <?php else: foreach ($requests as $lr): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg">
                        <td class="px-4 py-3 text-sm">
                            <div class="font-medium text-gray-900 dark:text-dark-text"><?= e($lr['employee_name']) ?></div>
                            <div class="text-xs text-gray-400"><?= e($lr['emp_code']) ?> &bull; <?= e($lr['department_name'] ?? '') ?></div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted"><?= e($lr['leave_type_name']) ?></td>
                        <td class="px-4 py-3 text-sm font-mono text-gray-600 dark:text-dark-muted"><?= e($lr['start_date']) ?></td>
                        <td class="px-4 py-3 text-sm font-mono text-gray-600 dark:text-dark-muted"><?= e($lr['end_date']) ?></td>
                        <td class="px-4 py-3 text-sm text-center font-medium"><?= (int)$lr['days'] ?></td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted max-w-[200px] truncate"><?= e($lr['reason'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm text-center">
                            <?php
                            $statusColors = ['pending' => 'bg-amber-100 text-amber-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700'];
                            ?>
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $statusColors[$lr['status']] ?? 'bg-gray-100 text-gray-600' ?>">
                                <?= ucfirst($lr['status']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <?php if ($lr['status'] === 'pending'): ?>
                            <div class="flex gap-2">
                                <form method="POST" action="<?= url('hr', 'leave-approve') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $lr['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button class="text-green-600 hover:text-green-800 text-xs font-medium">Approve</button>
                                </form>
                                <button onclick="rejectLeave(<?= $lr['id'] ?>)" class="text-red-600 hover:text-red-800 text-xs font-medium">Reject</button>
                            </div>
                            <?php else: ?>
                            <span class="text-xs text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-500 dark:text-dark-muted">Total: <?= count($requests) ?> request(s)</p>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-dark-card rounded-xl w-full max-w-sm p-6 m-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-dark-text mb-4">Reject Leave Request</h3>
        <form method="POST" action="<?= url('hr', 'leave-approve') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="reject_id" value="">
            <input type="hidden" name="action" value="reject">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Reason for Rejection</label>
                <textarea name="rejection_reason" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text"></textarea>
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 font-medium">Reject</button>
            </div>
        </form>
    </div>
</div>

<script>
function rejectLeave(id) {
    document.getElementById('reject_id').value = id;
    document.getElementById('rejectModal').classList.remove('hidden');
}
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
