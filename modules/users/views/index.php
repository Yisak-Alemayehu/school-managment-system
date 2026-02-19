<?php
/**
 * Users — List View
 */

$search  = input('search');
$roleFilter = input_int('role');
$statusFilter = input('status');
$page    = max(1, input_int('page') ?: 1);

// Build query
$where = ["u.deleted_at IS NULL"];
$params = [];

if ($search) {
    $where[] = "(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($roleFilter) {
    $where[] = "ur.role_id = ?";
    $params[] = $roleFilter;
}

if ($statusFilter === '1') {
    $where[] = "u.is_active = 1";
} elseif ($statusFilter === '0') {
    $where[] = "u.is_active = 0";
}

$whereClause = implode(' AND ', $where);

$totalUsers = db_fetch_value(
    "SELECT COUNT(DISTINCT u.id) FROM users u LEFT JOIN user_roles ur ON u.id = ur.user_id WHERE $whereClause",
    $params
);

$perPage = ITEMS_PER_PAGE;
$totalPages = max(1, ceil($totalUsers / $perPage));
$offset = ($page - 1) * $perPage;

$users = db_fetch_all(
    "SELECT u.id, u.username, u.full_name, u.email, u.phone, u.is_active, u.last_login_at, u.created_at,
            GROUP_CONCAT(r.name SEPARATOR ', ') as roles
     FROM users u
     LEFT JOIN user_roles ur ON u.id = ur.user_id
     LEFT JOIN roles r ON ur.role_id = r.id
     WHERE $whereClause
     GROUP BY u.id
     ORDER BY u.created_at DESC
     LIMIT $perPage OFFSET $offset",
    $params
);

$roles = db_fetch_all("SELECT id, name FROM roles ORDER BY id");

ob_start();
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900">User Management</h1>
        <p class="text-sm text-gray-500"><?= number_format($totalUsers) ?> total users</p>
    </div>
    <?php if (auth_has_permission('users.create')): ?>
        <a href="<?= url('users', 'create') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add User
        </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
    <form method="GET" action="<?= url('users') ?>" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <input type="hidden" name="module" value="users">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, username, email..."
               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        <select name="role" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            <option value="">All Roles</option>
            <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>" <?= $roleFilter == $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            <option value="">All Status</option>
            <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Active</option>
            <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Inactive</option>
        </select>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-900 transition">Filter</button>
            <a href="<?= url('users') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Clear</a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full table-responsive">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Login</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($users)): ?>
                    <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">No users found.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-gray-50">
                        <td data-label="User" class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-800 text-sm font-bold flex-shrink-0">
                                    <?= e(strtoupper(substr($u['full_name'], 0, 1))) ?>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900"><?= e($u['full_name']) ?></p>
                                    <p class="text-xs text-gray-500"><?= e($u['username']) ?> &bull; <?= e($u['email']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td data-label="Role" class="px-4 py-3">
                            <span class="text-xs font-medium text-gray-600"><?= e($u['roles'] ?: 'No role') ?></span>
                        </td>
                        <td data-label="Status" class="px-4 py-3">
                            <?php if ($u['is_active']): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Last Login" class="px-4 py-3 text-xs text-gray-500">
                            <?= $u['last_login_at'] ? time_ago($u['last_login_at']) : 'Never' ?>
                        </td>
                        <td data-label="Actions" class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= url('users', 'view', $u['id']) ?>" title="View" class="p-1.5 text-gray-400 hover:text-primary-600 rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <?php if (auth_has_permission('users.edit')): ?>
                                    <a href="<?= url('users', 'edit', $u['id']) ?>" title="Edit" class="p-1.5 text-gray-400 hover:text-yellow-600 rounded">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                <?php endif; ?>
                                <?php if (auth_has_permission('users.delete') && $u['id'] != auth_user()['id']): ?>
                                    <form method="POST" action="<?= url('users', 'delete', $u['id']) ?>" class="inline" onsubmit="return confirmDelete('Delete this user?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" title="Delete" class="p-1.5 text-gray-400 hover:text-red-600 rounded">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 border-t flex items-center justify-between">
            <p class="text-xs text-gray-500">Showing <?= ($offset + 1) ?>-<?= min($offset + $perPage, $totalUsers) ?> of <?= $totalUsers ?></p>
            <div class="flex gap-1">
                <?php if ($page > 1): ?>
                    <a href="<?= url('users') ?>&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&role=<?= $roleFilter ?>&status=<?= urlencode($statusFilter) ?>"
                       class="px-3 py-1 border rounded text-xs hover:bg-gray-50">Prev</a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="<?= url('users') ?>&page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= $roleFilter ?>&status=<?= urlencode($statusFilter) ?>"
                       class="px-3 py-1 border rounded text-xs <?= $i === $page ? 'bg-primary-800 text-white border-primary-800' : 'hover:bg-gray-50' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="<?= url('users') ?>&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&role=<?= $roleFilter ?>&status=<?= urlencode($statusFilter) ?>"
                       class="px-3 py-1 border rounded text-xs hover:bg-gray-50">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'User Management';
require APP_ROOT . '/templates/layout.php';
