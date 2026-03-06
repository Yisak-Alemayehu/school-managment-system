<?php
/**
 * Finance — Supplementary Fee Payment History
 * Lists fin_supplementary_transactions with filters.
 */

$search   = input('search');
$sfeeId   = input_int('sfee_id');
$dateFrom = input('date_from');
$dateTo   = input('date_to');
$channel  = input('channel');
$page     = max(1, input_int('page') ?: 1);
$perPage  = 25;

$supFees = db_fetch_all("SELECT id, description FROM fin_supplementary_fees WHERE is_active = 1 ORDER BY description");

$where  = ["1=1"];
$params = [];

if ($search) {
    $where[]  = "(s.full_name LIKE ? OR s.admission_no LIKE ? OR st.receipt_no LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($sfeeId)   { $where[] = "st.supplementary_fee_id = ?"; $params[] = $sfeeId; }
if ($dateFrom) { $where[] = "st.created_at >= ?"; $params[] = $dateFrom . ' 00:00:00'; }
if ($dateTo)   { $where[] = "st.created_at <= ?"; $params[] = $dateTo   . ' 23:59:59'; }
if ($channel)  { $where[] = "st.channel = ?";     $params[] = $channel; }

$joins = "JOIN students s ON st.student_id = s.id
          LEFT JOIN fin_supplementary_fees sf ON st.supplementary_fee_id = sf.id
          LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
          LEFT JOIN classes c ON e.class_id = c.id
          LEFT JOIN users u ON st.processed_by = u.id";

$whereClause = implode(' AND ', $where);
$offset      = ($page - 1) * $perPage;

$total = (int) db_fetch_value("SELECT COUNT(*) FROM fin_supplementary_transactions st $joins WHERE $whereClause", $params);
$rows  = db_fetch_all(
    "SELECT st.*, s.full_name, s.admission_no, c.name AS class_name,
            sf.description AS fee_desc, u.full_name AS processed_by_name
       FROM fin_supplementary_transactions st $joins
      WHERE $whereClause
      ORDER BY st.created_at DESC
      LIMIT $perPage OFFSET $offset",
    $params
);

$lastPage   = max(1, (int) ceil($total / $perPage));
$pagination = [
    'total' => $total, 'per_page' => $perPage, 'current_page' => $page,
    'last_page' => $lastPage, 'from' => $total > 0 ? $offset + 1 : 0,
    'to' => min($offset + $perPage, $total),
];

$exportQs = http_build_query(array_filter([
    'type' => 'supplementary-payments', 'search' => $search, 'sfee_id' => $sfeeId,
    'date_from' => $dateFrom, 'date_to' => $dateTo, 'channel' => $channel,
]));

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Supplementary Fee Payment History</h1>
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
    <form method="GET" action="<?= url('finance', 'supplementary-payments') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
        <div class="flex flex-wrap gap-3">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search student, code, receipt…"
                   class="flex-1 min-w-48 px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">

            <select name="sfee_id" class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                <option value="">All Supplementary Fees</option>
                <?php foreach ($supFees as $sf): ?>
                    <option value="<?= $sf['id'] ?>" <?= $sfeeId == $sf['id'] ? 'selected' : '' ?>><?= e($sf['description']) ?></option>
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
            <a href="<?= url('finance', 'supplementary-payments') ?>" class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 font-medium">Clear</a>
        </div>
    </form>

    <!-- Table -->
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Processed By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No supplementary payment records found.</td></tr>
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
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="By"><?= e($r['processed_by_name'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= pagination_html($pagination, url('finance/supplementary-payments')) ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = $pageTitle;
include TEMPLATES_PATH . '/layout.php';
