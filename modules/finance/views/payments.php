<?php
/**
 * Finance — School Payment History
 * Lists fin_transactions where type = 'payment', with filters.
 */

$search   = input('search');
$feeId    = input_int('fee_id');
$classId  = input_int('class_id');
$dateFrom = input('date_from');
$dateTo   = input('date_to');
$channel  = input('channel');
$page     = max(1, input_int('page') ?: 1);
$perPage  = 25;

$fees    = db_fetch_all("SELECT id, description FROM fin_fees WHERE is_active = 1 ORDER BY description");
$classes = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");

$where  = ["t.type = 'payment'"];
$params = [];

if ($search) {
    $where[]  = "(s.full_name LIKE ? OR s.admission_no LIKE ? OR t.receipt_no LIKE ? OR t.reference LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($feeId) {
    $where[]  = "sf.fee_id = ?";
    $params[] = $feeId;
}
if ($classId) {
    $where[]  = "e.class_id = ?";
    $params[] = $classId;
}
if ($dateFrom) { $where[] = "t.created_at >= ?"; $params[] = $dateFrom . ' 00:00:00'; }
if ($dateTo)   { $where[] = "t.created_at <= ?"; $params[] = $dateTo   . ' 23:59:59'; }
if ($channel)  { $where[] = "t.channel = ?";     $params[] = $channel; }

$joins = "JOIN students s ON t.student_id = s.id
          LEFT JOIN fin_student_fees sf ON t.student_fee_id = sf.id
          LEFT JOIN fin_fees f ON sf.fee_id = f.id
          LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
          LEFT JOIN classes c ON e.class_id = c.id
          LEFT JOIN users u ON t.processed_by = u.id";

$whereClause = implode(' AND ', $where);
$offset      = ($page - 1) * $perPage;

$total = (int) db_fetch_value("SELECT COUNT(*) FROM fin_transactions t $joins WHERE $whereClause", $params);
$rows  = db_fetch_all(
    "SELECT t.*, s.full_name, s.admission_no, c.name AS class_name,
            f.description AS fee_desc, u.full_name AS processed_by_name
       FROM fin_transactions t $joins
      WHERE $whereClause
      ORDER BY t.created_at DESC
      LIMIT $perPage OFFSET $offset",
    $params
);

$lastPage   = max(1, (int) ceil($total / $perPage));
$pagination = [
    'total' => $total, 'per_page' => $perPage, 'current_page' => $page,
    'last_page' => $lastPage, 'from' => $total > 0 ? $offset + 1 : 0,
    'to' => min($offset + $perPage, $total),
];

// Build query string for exports
$exportQs = http_build_query(array_filter([
    'type' => 'payments', 'search' => $search, 'fee_id' => $feeId,
    'class_id' => $classId, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'channel' => $channel,
]));

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">School Payment History</h1>
        <div class="flex gap-2">
            <a href="<?= url('finance', 'export-pdf') ?>&<?= $exportQs ?>"
               class="px-3 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 font-medium inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                PDF
            </a>
            <a href="<?= url('finance', 'export-excel') ?>&<?= $exportQs ?>"
               class="px-3 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 font-medium inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                Excel
            </a>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?= url('finance', 'payments') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
        <div class="flex flex-wrap gap-3">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search student, code, receipt…"
                   class="flex-1 min-w-48 px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">

            <select name="fee_id" class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                <option value="">All Fees</option>
                <?php foreach ($fees as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= $feeId == $f['id'] ? 'selected' : '' ?>><?= e($f['description']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="class_id" class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                <option value="">All Classes</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $classId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="channel" class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                <option value="">All Channels</option>
                <option value="cash" <?= $channel === 'cash' ? 'selected' : '' ?>>Cash</option>
                <option value="bank" <?= $channel === 'bank' ? 'selected' : '' ?>>Bank</option>
                <option value="mobile" <?= $channel === 'mobile' ? 'selected' : '' ?>>Mobile</option>
                <option value="online" <?= $channel === 'online' ? 'selected' : '' ?>>Online</option>
            </select>

            <input type="date" name="date_from" value="<?= e($dateFrom) ?>" placeholder="From"
                   class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
            <input type="date" name="date_to" value="<?= e($dateTo) ?>" placeholder="To"
                   class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">

            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">Search</button>
            <a href="<?= url('finance', 'payments') ?>" class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 font-medium">Clear</a>
        </div>
    </form>

    <!-- Payments Table -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border responsive-table">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Class</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Fee</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Channel</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Receipt #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Batch #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Processed By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="11" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No payment records found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                    <tr class="hover:bg-gray-50 dark:bg-dark-bg transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Date"><?= format_datetime($r['created_at']) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Student">
                            <a href="<?= url('finance', 'student-detail', $r['student_id']) ?>" class="text-primary-600 hover:underline font-medium"><?= e($r['full_name']) ?></a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Code"><?= e($r['admission_no']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Class"><?= e($r['class_name'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Fee"><?= e($r['fee_desc'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-dark-text" data-label="Amount"><?= format_money($r['amount']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Channel"><?= ucfirst(e($r['channel'] ?? '—')) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Receipt"><?= e($r['receipt_no'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Batch">
                            <?php if (!empty($r['batch_receipt_no'])): ?>
                                <a href="<?= url('finance', 'collect-payment-batch-receipt') ?>&batch=<?= urlencode($r['batch_receipt_no']) ?>"
                                   class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-xs rounded font-mono hover:bg-blue-100 dark:hover:bg-blue-900/40"
                                   title="View Batch Receipt"><?= e($r['batch_receipt_no']) ?></a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="By"><?= e($r['processed_by_name'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Actions">
                            <a href="<?= url('finance', 'payment-attachment', $r['id']) ?>" target="_blank"
                               class="inline-flex items-center gap-1 px-2 py-1 bg-indigo-50 text-indigo-700 text-xs rounded-lg hover:bg-indigo-100 font-medium border border-indigo-200" title="Print Receipt">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= pagination_html($pagination, url('finance/payments')) ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = $pageTitle;
include TEMPLATES_PATH . '/layout.php';
