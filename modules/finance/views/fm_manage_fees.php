<?php
/**
 * Fee Management — Manage Fees (List / Table)
 * Searchable, filterable, paginated fee list
 */
$search = input('search', '');
$status = input('status', '');
$type   = input('type', '');
$page   = max(1, input_int('page') ?: 1);
$perPage = 15;

// Build WHERE clause
$where = ["f.deleted_at IS NULL"];
$params = [];

if ($search !== '') {
    $where[] = "f.description LIKE ?";
    $params[] = "%{$search}%";
}
if (in_array($status, ['draft', 'active', 'inactive'])) {
    $where[] = "f.status = ?";
    $params[] = $status;
}
if (in_array($type, ['one_time', 'recurrent'])) {
    $where[] = "f.fee_type = ?";
    $params[] = $type;
}

$whereSQL = implode(' AND ', $where);

// Count
$total = db_fetch_value("SELECT COUNT(*) FROM fees f WHERE {$whereSQL}", $params);

// Fetch with pagination
$offset = ($page - 1) * $perPage;
$fees = db_fetch_all(
    "SELECT f.*, 
            (SELECT COUNT(*) FROM fee_assignments WHERE fee_id = f.id AND deleted_at IS NULL) AS assignment_count,
            (SELECT COUNT(*) FROM student_fee_charges WHERE fee_id = f.id AND status IN ('pending','overdue')) AS outstanding_count
     FROM fees f 
     WHERE {$whereSQL}
     ORDER BY f.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$totalPages = max(1, ceil($total / $perPage));

ob_start();
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manage Fees</h1>
            <p class="text-sm text-gray-500 mt-1"><?= $total ?> fee<?= $total != 1 ? 's' : '' ?> total</p>
        </div>
        <?php if (auth_has_permission('fee_management.create_fee')): ?>
        <a href="<?= url('finance', 'fm-create-fee') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Fee
        </a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <form method="GET" action="<?= url('finance', 'fm-manage-fees') ?>" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by description..."
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
            <select name="status" class="rounded-lg border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">All Status</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <select name="type" class="rounded-lg border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">All Types</option>
                <option value="one_time" <?= $type === 'one_time' ? 'selected' : '' ?>>One-Time</option>
                <option value="recurrent" <?= $type === 'recurrent' ? 'selected' : '' ?>>Recurrent</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">
                Filter
            </button>
            <?php if ($search || $status || $type): ?>
                <a href="<?= url('finance', 'fm-manage-fees') ?>" class="px-4 py-2 text-gray-500 text-sm hover:text-gray-700">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <?php if (empty($fees)): ?>
            <div class="p-12 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <p class="text-gray-500 text-sm">No fees found.</p>
                <?php if (auth_has_permission('fee_management.create_fee')): ?>
                    <a href="<?= url('finance', 'fm-create-fee') ?>" class="mt-4 inline-block text-primary-600 text-sm hover:underline">Create your first fee &rarr;</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Description</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-600">Type</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-600">Amount</th>
                            <th class="text-center px-4 py-3 font-medium text-gray-600">Period</th>
                            <th class="text-center px-4 py-3 font-medium text-gray-600">Status</th>
                            <th class="text-center px-4 py-3 font-medium text-gray-600">Assigned</th>
                            <th class="text-center px-4 py-3 font-medium text-gray-600">Outstanding</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($fees as $fee): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <a href="<?= url('finance', 'fm-fee-view', $fee['id']) ?>" class="font-medium text-gray-900 hover:text-primary-600">
                                    <?= e(mb_strimwidth($fee['description'], 0, 50, '...')) ?>
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 text-xs font-medium <?= $fee['fee_type'] === 'recurrent' ? 'text-purple-700' : 'text-blue-700' ?>">
                                    <?php if ($fee['fee_type'] === 'recurrent'): ?>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <?php endif; ?>
                                    <?= $fee['fee_type'] === 'one_time' ? 'One-Time' : 'Recurrent' ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono">
                                <?= CURRENCY_SYMBOL ?> <?= number_format($fee['amount'], 2) ?>
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-gray-500">
                                <?= format_date($fee['effective_date']) ?><br>
                                to <?= format_date($fee['end_date']) ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php
                                $statusColors = [
                                    'draft'    => 'bg-gray-100 text-gray-700',
                                    'active'   => 'bg-green-100 text-green-700',
                                    'inactive' => 'bg-red-100 text-red-700',
                                ];
                                ?>
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$fee['status']] ?? 'bg-gray-100 text-gray-600' ?>">
                                    <?= ucfirst($fee['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600"><?= (int)$fee['assignment_count'] ?></td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($fee['outstanding_count'] > 0): ?>
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                        <?= (int)$fee['outstanding_count'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="<?= url('finance', 'fm-fee-view', $fee['id']) ?>" title="View" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <?php if (auth_has_permission('fee_management.edit_fee')): ?>
                                    <a href="<?= url('finance', 'fm-edit-fee', $fee['id']) ?>" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (auth_has_permission('fee_management.create_fee')): ?>
                                    <form method="POST" action="<?= url('finance', 'fm-fee-duplicate') ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $fee['id'] ?>">
                                        <button type="submit" title="Duplicate" class="p-1.5 rounded-lg text-gray-400 hover:text-purple-600 hover:bg-purple-50">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (auth_has_permission('fee_management.assign_fee') && $fee['status'] === 'active'): ?>
                                    <a href="<?= url('finance', 'fm-assign-fees') ?>?fee_id=<?= $fee['id'] ?>" title="Assign" class="p-1.5 rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (auth_has_permission('fee_management.activate_fee') && $fee['status'] !== 'active'): ?>
                                    <form method="POST" action="<?= url('finance', 'fm-fee-toggle') ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $fee['id'] ?>">
                                        <button type="submit" title="Activate" class="p-1.5 rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                    </form>
                                    <?php elseif (auth_has_permission('fee_management.activate_fee') && $fee['status'] === 'active'): ?>
                                    <form method="POST" action="<?= url('finance', 'fm-fee-toggle') ?>" class="inline"
                                          onsubmit="return confirm('Deactivating will cancel all pending charges. Continue?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $fee['id'] ?>">
                                        <button type="submit" title="Deactivate" class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (auth_has_permission('fee_management.delete_fee')): ?>
                                    <form method="POST" action="<?= url('finance', 'fm-fee-delete') ?>" class="inline"
                                          onsubmit="return confirm('Delete this fee? This cannot be undone.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $fee['id'] ?>">
                                        <button type="submit" title="Delete" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50">
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
            <div class="flex items-center justify-between px-4 py-3 border-t bg-gray-50">
                <p class="text-xs text-gray-500">Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> of <?= $total ?></p>
                <div class="flex gap-1">
                    <?php if ($page > 1): ?>
                        <a href="<?= url('finance', 'fm-manage-fees') ?>?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>"
                           class="px-3 py-1 text-xs rounded border bg-white hover:bg-gray-100">&laquo; Prev</a>
                    <?php endif; ?>
                    <?php
                    $startP = max(1, $page - 2);
                    $endP   = min($totalPages, $page + 2);
                    for ($p = $startP; $p <= $endP; $p++):
                    ?>
                        <a href="<?= url('finance', 'fm-manage-fees') ?>?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>"
                           class="px-3 py-1 text-xs rounded border <?= $p == $page ? 'bg-primary-800 text-white' : 'bg-white hover:bg-gray-100' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= url('finance', 'fm-manage-fees') ?>?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>"
                           class="px-3 py-1 text-xs rounded border bg-white hover:bg-gray-100">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
