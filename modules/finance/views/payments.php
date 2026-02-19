<?php
/**
 * Finance — Payments List
 */
$pageTitle = 'Payments';

$sessionId = get_active_session_id();
$search    = trim($_GET['q'] ?? '');
$method    = $_GET['method'] ?? '';
$dateFrom  = $_GET['from'] ?? '';
$dateTo    = $_GET['to'] ?? '';
$page      = max(1, input_int('page') ?: 1);

$where  = ['i.session_id = ?'];
$params = [$sessionId];

if ($search) {
    $where[]  = '(s.first_name LIKE ? OR s.last_name LIKE ? OR p.receipt_no LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($method) {
    $where[]  = 'p.payment_method = ?';
    $params[] = $method;
}
if ($dateFrom) {
    $where[]  = 'p.payment_date >= ?';
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where[]  = 'p.payment_date <= ?';
    $params[] = $dateTo;
}

$whereStr = implode(' AND ', $where);

$payments = db_paginate("
    SELECT p.*, i.invoice_no, s.first_name, s.last_name, s.admission_no, c.name AS class_name
    FROM payments p
    JOIN invoices i ON i.id = p.invoice_id
    JOIN students s ON s.id = p.student_id
    JOIN classes c ON c.id = i.class_id
    WHERE {$whereStr}
    ORDER BY p.payment_date DESC, p.id DESC
", $params, $page, 20);

// Total collected in range
$totalCollected = db_fetch_one("
    SELECT SUM(p.amount) AS total
    FROM payments p
    JOIN invoices i ON i.id = p.invoice_id
    WHERE {$whereStr}
", $params);

ob_start();
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Payments</h1>
        <a href="<?= url('finance', 'payment-record') ?>"
           class="inline-flex items-center gap-2 px-4 py-2 bg-green-700 text-white rounded-lg text-sm font-medium hover:bg-green-800">
            + Record Payment
        </a>
    </div>

    <!-- Total -->
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center justify-between">
        <span class="text-sm text-green-800">Total Collected (filtered)</span>
        <span class="text-2xl font-bold text-green-800"><?= format_currency($totalCollected['total'] ?? 0) ?></span>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
            <input type="hidden" name="module" value="finance">
            <input type="hidden" name="action" value="payments">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Name / Receipt#..."
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Method</label>
                <select name="method" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">All</option>
                    <option value="cash" <?= $method === 'cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="bank_transfer" <?= $method === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                    <option value="telebirr" <?= $method === 'telebirr' ? 'selected' : '' ?>>Telebirr</option>
                    <option value="chapa" <?= $method === 'chapa' ? 'selected' : '' ?>>Chapa</option>
                    <option value="cheque" <?= $method === 'cheque' ? 'selected' : '' ?>>Cheque</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                <input type="date" name="from" value="<?= e($dateFrom) ?>"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                <input type="date" name="to" value="<?= e($dateTo) ?>"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-900">Filter</button>
                <a href="<?= url('finance', 'payments') ?>" class="text-sm text-gray-500 hover:text-gray-700 self-center">Reset</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($payments['data'])): ?>
                        <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No payments found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payments['data'] as $p): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-mono font-medium text-gray-900"><?= e($p['receipt_no'] ?? '—') ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-medium text-gray-900"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></div>
                                    <div class="text-xs text-gray-500"><?= e($p['admission_no']) ?></div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="<?= url('finance', 'invoice-view') ?>&id=<?= $p['invoice_id'] ?>" class="text-primary-700 hover:text-primary-900 font-mono"><?= e($p['invoice_no']) ?></a>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600"><?= format_date($p['payment_date']) ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800"><?= ucfirst(str_replace('_', ' ', $p['payment_method'])) ?></span>
                                </td>
                                <td class="px-4 py-3 text-sm text-right font-semibold text-green-700"><?= format_currency($p['amount']) ?></td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <a href="<?= url('finance', 'payment-receipt') ?>&id=<?= $p['id'] ?>" target="_blank"
                                       class="text-gray-600 hover:text-gray-800 font-medium">Receipt</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($payments['last_page'] > 1): ?>
            <div class="px-6 py-3 border-t bg-gray-50">
                <?= render_pagination($payments, url('finance', 'payments') . "&q=" . urlencode($search) . "&method={$method}&from={$dateFrom}&to={$dateTo}") ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
