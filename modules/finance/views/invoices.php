<?php
/**
 * Finance — Invoices List
 */
$pageTitle = 'Invoices';

$sessionId   = get_active_session_id();
$classFilter = input_int('class_id');
$status      = $_GET['status'] ?? '';
$search      = trim($_GET['q'] ?? '');
$page        = max(1, input_int('page') ?: 1);

$where  = ['i.session_id = ?'];
$params = [$sessionId];

if ($classFilter) {
    $where[]  = 'i.class_id = ?';
    $params[] = $classFilter;
}
if ($status) {
    $where[]  = 'i.status = ?';
    $params[] = $status;
}
if ($search) {
    $where[]  = '(s.first_name LIKE ? OR s.last_name LIKE ? OR i.invoice_no LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereStr = implode(' AND ', $where);

$invoices = db_paginate("
    SELECT i.*, s.first_name, s.last_name, s.admission_no, c.name AS class_name
    FROM invoices i
    JOIN students s ON s.id = i.student_id
    JOIN classes c ON c.id = i.class_id
    WHERE {$whereStr}
    ORDER BY i.created_at DESC
", $params, $page, 20);

$classes = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY name");

// Summary totals
$totals = db_fetch_one("
    SELECT COUNT(*) AS total_invoices,
           SUM(total_amount) AS total_billed,
           SUM(paid_amount) AS total_paid,
           SUM(total_amount - paid_amount) AS total_due
    FROM invoices i
    WHERE i.session_id = ?
", [$sessionId]);

ob_start();
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Invoices</h1>
        <a href="<?= url('finance', 'invoice-create') ?>"
           class="inline-flex items-center gap-2 px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
            + Generate Invoice
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="text-2xl font-bold text-gray-900"><?= number_format($totals['total_invoices'] ?? 0) ?></div>
            <div class="text-xs text-gray-500">Total Invoices</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="text-2xl font-bold text-blue-700"><?= format_currency($totals['total_billed'] ?? 0) ?></div>
            <div class="text-xs text-gray-500">Total Billed</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="text-2xl font-bold text-green-700"><?= format_currency($totals['total_paid'] ?? 0) ?></div>
            <div class="text-xs text-gray-500">Total Collected</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="text-2xl font-bold text-red-700"><?= format_currency($totals['total_due'] ?? 0) ?></div>
            <div class="text-xs text-gray-500">Outstanding</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <input type="hidden" name="module" value="finance">
            <input type="hidden" name="action" value="invoices">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Name or Invoice #..."
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select name="class_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">All</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $classFilter == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">All</option>
                    <option value="unpaid" <?= $status === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                    <option value="partial" <?= $status === 'partial' ? 'selected' : '' ?>>Partial</option>
                    <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div>
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-900">Filter</button>
                <a href="<?= url('finance', 'invoices') ?>" class="ml-2 text-sm text-gray-500 hover:text-gray-700">Reset</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Paid</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Due</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($invoices['data'])): ?>
                        <tr><td colspan="9" class="px-6 py-8 text-center text-gray-500">No invoices found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($invoices['data'] as $inv): ?>
                            <?php $due = $inv['total_amount'] - $inv['paid_amount']; ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-mono font-medium text-primary-700"><?= e($inv['invoice_no']) ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-medium text-gray-900"><?= e($inv['first_name'] . ' ' . $inv['last_name']) ?></div>
                                    <div class="text-xs text-gray-500"><?= e($inv['admission_no']) ?></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600"><?= e($inv['class_name']) ?></td>
                                <td class="px-4 py-3 text-sm text-right font-semibold"><?= format_currency($inv['total_amount']) ?></td>
                                <td class="px-4 py-3 text-sm text-right text-green-700"><?= format_currency($inv['paid_amount']) ?></td>
                                <td class="px-4 py-3 text-sm text-right <?= $due > 0 ? 'text-red-600 font-semibold' : 'text-gray-500' ?>"><?= format_currency($due) ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <?php
                                    $badge = match($inv['status']) {
                                        'paid'      => 'bg-green-100 text-green-800',
                                        'partial'   => 'bg-yellow-100 text-yellow-800',
                                        'cancelled' => 'bg-gray-100 text-gray-800',
                                        default     => 'bg-red-100 text-red-800',
                                    };
                                    ?>
                                    <span class="px-2 py-1 text-xs rounded-full <?= $badge ?>"><?= ucfirst($inv['status']) ?></span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600"><?= $inv['due_date'] ? format_date($inv['due_date']) : '—' ?></td>
                                <td class="px-4 py-3 text-right text-sm space-x-1">
                                    <a href="<?= url('finance', 'invoice-view') ?>&id=<?= $inv['id'] ?>" class="text-primary-700 hover:text-primary-900 font-medium">View</a>
                                    <?php if ($inv['status'] !== 'paid' && $inv['status'] !== 'cancelled'): ?>
                                        <a href="<?= url('finance', 'payment-record') ?>&invoice_id=<?= $inv['id'] ?>" class="text-green-700 hover:text-green-900 font-medium">Pay</a>
                                    <?php endif; ?>
                                    <a href="<?= url('finance', 'invoice-print') ?>&id=<?= $inv['id'] ?>" class="text-gray-600 hover:text-gray-800" target="_blank">Print</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($invoices['last_page'] > 1): ?>
            <div class="px-6 py-3 border-t bg-gray-50">
                <?= render_pagination($invoices, url('finance', 'invoices') . "&class_id={$classFilter}&status={$status}&q=" . urlencode($search)) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
