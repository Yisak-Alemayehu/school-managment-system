<?php
/**
 * Finance — Fee Report
 */
$pageTitle = 'Fee Collection Report';

$sessionId   = get_active_session_id();
$classFilter = input_int('class_id');
$termFilter  = input_int('term_id');

$classes = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY name");
$terms   = db_fetch_all("SELECT id, name FROM terms WHERE session_id = ? ORDER BY id", [$sessionId]);

// Per-class breakdown
$where  = ['i.session_id = ?'];
$params = [$sessionId];
if ($classFilter) {
    $where[]  = 'i.class_id = ?';
    $params[] = $classFilter;
}
if ($termFilter) {
    $where[]  = 'i.term_id = ?';
    $params[] = $termFilter;
}
$whereStr = implode(' AND ', $where);

$classReport = db_fetch_all("
    SELECT c.name AS class_name,
           COUNT(DISTINCT i.id) AS total_invoices,
           COUNT(DISTINCT i.student_id) AS students,
           SUM(i.total_amount) AS total_billed,
           SUM(i.paid_amount) AS total_paid,
           SUM(i.total_amount - i.paid_amount) AS total_due,
           SUM(CASE WHEN i.status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
           SUM(CASE WHEN i.status = 'partial' THEN 1 ELSE 0 END) AS partial_count,
           SUM(CASE WHEN i.status = 'unpaid' THEN 1 ELSE 0 END) AS unpaid_count
    FROM invoices i
    JOIN classes c ON c.id = i.class_id
    WHERE {$whereStr}
    GROUP BY i.class_id, c.name
    ORDER BY c.name
", $params);

// Payment method breakdown
$methodReport = db_fetch_all("
    SELECT p.payment_method, COUNT(*) AS count, SUM(p.amount) AS total
    FROM payments p
    JOIN invoices i ON i.id = p.invoice_id
    WHERE {$whereStr}
    GROUP BY p.payment_method
    ORDER BY total DESC
", $params);

// Grand totals
$grand = db_fetch_one("
    SELECT SUM(i.total_amount) AS billed, SUM(i.paid_amount) AS paid,
           SUM(i.total_amount - i.paid_amount) AS due
    FROM invoices i
    WHERE {$whereStr}
", $params);

ob_start();
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Fee Collection Report</h1>
        <button onclick="window.print()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 no-print">Print</button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border p-4 no-print">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="module" value="finance">
            <input type="hidden" name="action" value="fee-report">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select name="class_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $classFilter == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Term</label>
                <select name="term_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">All Terms</option>
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $termFilter == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-900">Filter</button>
                <a href="<?= url('finance', 'fee-report') ?>" class="ml-2 text-sm text-gray-500 hover:text-gray-700">Reset</a>
            </div>
        </form>
    </div>

    <!-- Grand Totals -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 text-center">
            <div class="text-3xl font-bold text-blue-800"><?= format_currency($grand['billed'] ?? 0) ?></div>
            <div class="text-sm text-blue-600">Total Billed</div>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl p-5 text-center">
            <div class="text-3xl font-bold text-green-800"><?= format_currency($grand['paid'] ?? 0) ?></div>
            <div class="text-sm text-green-600">Total Collected</div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-5 text-center">
            <div class="text-3xl font-bold text-red-800"><?= format_currency($grand['due'] ?? 0) ?></div>
            <div class="text-sm text-red-600">Outstanding Balance</div>
        </div>
    </div>

    <!-- Collection Rate -->
    <?php $rate = ($grand['billed'] ?? 0) > 0 ? round(($grand['paid'] / $grand['billed']) * 100, 1) : 0; ?>
    <div class="bg-white rounded-xl shadow-sm border p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">Collection Rate</span>
            <span class="text-sm font-bold text-gray-900"><?= $rate ?>%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-green-600 h-3 rounded-full" style="width: <?= min(100, $rate) ?>%"></div>
        </div>
    </div>

    <!-- Per-Class Table -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-6 py-4 border-b bg-gray-50">
            <h2 class="font-semibold text-gray-900">Class-wise Breakdown</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Students</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Billed</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Collected</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Due</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Paid</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Partial</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Unpaid</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($classReport)): ?>
                        <tr><td colspan="8" class="px-6 py-8 text-center text-gray-500">No data.</td></tr>
                    <?php else: ?>
                        <?php foreach ($classReport as $cr): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= e($cr['class_name']) ?></td>
                                <td class="px-4 py-3 text-sm text-center"><?= $cr['students'] ?></td>
                                <td class="px-4 py-3 text-sm text-right"><?= format_currency($cr['total_billed']) ?></td>
                                <td class="px-4 py-3 text-sm text-right text-green-700 font-semibold"><?= format_currency($cr['total_paid']) ?></td>
                                <td class="px-4 py-3 text-sm text-right text-red-600 font-semibold"><?= format_currency($cr['total_due']) ?></td>
                                <td class="px-4 py-3 text-sm text-center"><span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs"><?= $cr['paid_count'] ?></span></td>
                                <td class="px-4 py-3 text-sm text-center"><span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs"><?= $cr['partial_count'] ?></span></td>
                                <td class="px-4 py-3 text-sm text-center"><span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs"><?= $cr['unpaid_count'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment Methods -->
    <?php if (!empty($methodReport)): ?>
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50">
                <h2 class="font-semibold text-gray-900">Payment Method Breakdown</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Transactions</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($methodReport as $mr): ?>
                        <tr>
                            <td class="px-6 py-3 text-sm font-medium text-gray-900"><?= ucfirst(str_replace('_', ' ', $mr['payment_method'])) ?></td>
                            <td class="px-6 py-3 text-sm text-center"><?= $mr['count'] ?></td>
                            <td class="px-6 py-3 text-sm text-right font-semibold"><?= format_currency($mr['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
