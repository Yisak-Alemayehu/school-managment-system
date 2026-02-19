<?php
/**
 * Settings — Audit Logs
 */
$pageTitle = 'Audit Logs';
require_permission('settings_manage');

// Filters
$filterUser   = $_GET['user'] ?? '';
$filterAction = $_GET['act'] ?? '';
$filterTable  = $_GET['tbl'] ?? '';
$filterFrom   = $_GET['from'] ?? '';
$filterTo     = $_GET['to'] ?? '';

$where  = [];
$params = [];

if ($filterUser) {
    $where[]  = "al.user_id = ?";
    $params[] = $filterUser;
}
if ($filterAction) {
    $where[]  = "al.action = ?";
    $params[] = $filterAction;
}
if ($filterTable) {
    $where[]  = "al.module = ?";
    $params[] = $filterTable;
}
if ($filterFrom) {
    $where[]  = "al.created_at >= ?";
    $params[] = $filterFrom . ' 00:00:00';
}
if ($filterTo) {
    $where[]  = "al.created_at <= ?";
    $params[] = $filterTo . ' 23:59:59';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset  = ($page - 1) * $perPage;

$total = (int)db_fetch_one("SELECT COUNT(*) as cnt FROM audit_logs al $whereSQL", $params)['cnt'];
$logs  = db_fetch_all(
    "SELECT al.*, u.full_name AS user_name
     FROM audit_logs al
     LEFT JOIN users u ON al.user_id = u.id
     $whereSQL
     ORDER BY al.created_at DESC
     LIMIT $perPage OFFSET $offset",
    $params
);

// Lookup data for filters
$users   = db_fetch_all("SELECT DISTINCT al.user_id, u.full_name FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY u.full_name");
$actions = db_fetch_all("SELECT DISTINCT action FROM audit_logs ORDER BY action");
$tables  = db_fetch_all("SELECT DISTINCT module AS table_name FROM audit_logs WHERE module IS NOT NULL ORDER BY module");

$totalPages = ceil($total / $perPage);

ob_start();
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Audit Logs</h1>
        <span class="text-sm text-gray-500"><?= number_format($total) ?> records</span>
    </div>

    <!-- Filters -->
    <form class="bg-white rounded-xl shadow-sm border p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <select name="user" class="rounded-lg border-gray-300 text-sm">
                <option value="">All Users</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['user_id'] ?>" <?= $filterUser == $u['user_id'] ? 'selected' : '' ?>>
                        <?= e($u['full_name'] ?? 'System') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="act" class="rounded-lg border-gray-300 text-sm">
                <option value="">All Actions</option>
                <?php foreach ($actions as $a): ?>
                    <option value="<?= e($a['action']) ?>" <?= $filterAction === $a['action'] ? 'selected' : '' ?>>
                        <?= e(ucfirst($a['action'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="tbl" class="rounded-lg border-gray-300 text-sm">
                <option value="">All Tables</option>
                <?php foreach ($tables as $t): ?>
                    <option value="<?= e($t['table_name']) ?>" <?= $filterTable === $t['table_name'] ? 'selected' : '' ?>>
                        <?= e($t['table_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="from" value="<?= e($filterFrom) ?>"
                   class="rounded-lg border-gray-300 text-sm" placeholder="From">
            <div class="flex gap-2">
                <input type="date" name="to" value="<?= e($filterTo) ?>"
                       class="rounded-lg border-gray-300 text-sm flex-1" placeholder="To">
                <button type="submit"
                        class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
                    Filter
                </button>
            </div>
        </div>
    </form>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Time</th>
                        <th class="px-4 py-3 text-left font-medium">User</th>
                        <th class="px-4 py-3 text-left font-medium">Action</th>
                        <th class="px-4 py-3 text-left font-medium">Module</th>
                        <th class="px-4 py-3 text-left font-medium">Entity</th>
                        <th class="px-4 py-3 text-left font-medium">Description</th>
                        <th class="px-4 py-3 text-left font-medium">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No audit logs found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap text-xs">
                                    <?= format_datetime($log['created_at']) ?>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    <?= e($log['user_name'] ?? 'System') ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php
                                    $badges = [
                                        'create' => 'bg-green-100 text-green-700',
                                        'update' => 'bg-blue-100 text-blue-700',
                                        'delete' => 'bg-red-100 text-red-700',
                                        'login'  => 'bg-purple-100 text-purple-700',
                                        'logout' => 'bg-gray-100 text-gray-700',
                                    ];
                                    $cls = $badges[$log['action']] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $cls ?>">
                                        <?= e(ucfirst($log['action'])) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600"><?= e($log['module'] ?? '-') ?></td>
                                <td class="px-4 py-3 text-gray-600"><?= $log['entity_id'] ? e($log['entity_type'] ?? '') . ' #' . $log['entity_id'] : '-' ?></td>
                                <td class="px-4 py-3 text-gray-600 max-w-xs truncate" title="<?= e($log['description'] ?? '') ?>">
                                    <?= e($log['description'] ?? '-') ?>
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs"><?= e($log['ip_address'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
        <?= render_pagination($page, $totalPages, url('settings', 'audit-logs') . '&user=' . urlencode($filterUser) . '&act=' . urlencode($filterAction) . '&tbl=' . urlencode($filterTable) . '&from=' . urlencode($filterFrom) . '&to=' . urlencode($filterTo)) ?>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
