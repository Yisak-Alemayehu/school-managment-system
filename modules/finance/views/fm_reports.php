<?php
/**
 * Fee Management — Reports
 * Report type selector, date filters, summary tables, and CSV export
 */
$reportType = input('report', 'outstanding');
$dateFrom   = input('date_from', date('Y-m-01'));
$dateTo     = input('date_to', date('Y-m-d'));
$classId    = input_int('class_id');

$classes = db_fetch_all("SELECT id, name FROM classes ORDER BY name");

// Build report data
$reportData  = [];
$reportTitle = '';
$columns     = [];

switch ($reportType) {
    case 'outstanding':
        $reportTitle = 'Outstanding Fee Report';
        $columns = ['Student', 'Admission #', 'Class', 'Fee', 'Amount', 'Due Date', 'Status'];

        $sql = "SELECT sfc.*, f.description AS fee_desc, 
                       CONCAT(u.first_name, ' ', u.last_name) AS student_name, 
                       st.admission_no, c.name AS class_name
                FROM student_fee_charges sfc
                JOIN fees f ON f.id = sfc.fee_id
                JOIN students st ON st.id = sfc.student_id
                JOIN users u ON u.id = st.user_id
                LEFT JOIN enrollments e ON e.student_id = st.id AND e.status = 'active'
                LEFT JOIN classes c ON c.id = e.class_id
                WHERE sfc.status IN ('pending','overdue')
                AND sfc.due_date BETWEEN ? AND ?";
        $params = [$dateFrom, $dateTo];
        if ($classId) { $sql .= " AND e.class_id = ?"; $params[] = $classId; }
        $sql .= " ORDER BY sfc.due_date ASC, u.first_name";
        $reportData = db_fetch_all($sql, $params);
        break;

    case 'penalties':
        $reportTitle = 'Penalty Report';
        $columns = ['Student', 'Fee', 'Penalty Amount', 'Applied Date', 'Status'];

        $sql = "SELECT pc.*, sfc.amount AS charge_amount, f.description AS fee_desc,
                       CONCAT(u.first_name, ' ', u.last_name) AS student_name
                FROM penalty_charges pc
                JOIN student_fee_charges sfc ON sfc.id = pc.charge_id
                JOIN fees f ON f.id = sfc.fee_id
                JOIN students st ON st.id = sfc.student_id
                JOIN users u ON u.id = st.user_id
                WHERE pc.applied_at BETWEEN ? AND ?";
        $params = [$dateFrom, $dateTo];
        $sql .= " ORDER BY pc.applied_at DESC";
        $reportData = db_fetch_all($sql, $params);
        break;

    case 'revenue':
        $reportTitle = 'Revenue / Collection Report';
        $columns = ['Fee Description', 'Total Billed', 'Collected', 'Outstanding', 'Waived', 'Collection Rate'];

        $sql = "SELECT f.description AS fee_desc,
                       COUNT(sfc.id) AS total_charges,
                       COALESCE(SUM(sfc.amount), 0) AS total_billed,
                       COALESCE(SUM(CASE WHEN sfc.status = 'paid' THEN sfc.amount ELSE 0 END), 0) AS collected,
                       COALESCE(SUM(CASE WHEN sfc.status IN ('pending','overdue') THEN sfc.amount ELSE 0 END), 0) AS outstanding,
                       COALESCE(SUM(CASE WHEN sfc.status = 'waived' THEN sfc.amount ELSE 0 END), 0) AS waived
                FROM student_fee_charges sfc
                JOIN fees f ON f.id = sfc.fee_id
                WHERE sfc.created_at BETWEEN ? AND ?
                GROUP BY f.id, f.description
                ORDER BY total_billed DESC";
        $params = [$dateFrom, $dateTo];
        $reportData = db_fetch_all($sql, $params);
        break;

    case 'exemptions':
        $reportTitle = 'Exemptions Report';
        $columns = ['Student', 'Admission #', 'Fee', 'Reason', 'Exempted On'];

        $sql = "SELECT fe.*, CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                       st.admission_no, f.description AS fee_desc
                FROM fee_exemptions fe
                JOIN fees f ON f.id = fe.fee_id
                JOIN students st ON st.id = fe.student_id
                JOIN users u ON u.id = st.user_id
                WHERE fe.deleted_at IS NULL AND fe.created_at BETWEEN ? AND ?
                ORDER BY fe.created_at DESC";
        $params = [$dateFrom, $dateTo];
        $reportData = db_fetch_all($sql, $params);
        break;
}

// Summary stats
$summaryStats = db_fetch_one(
    "SELECT 
        COALESCE(SUM(CASE WHEN status IN ('pending','overdue') THEN amount ELSE 0 END), 0) AS total_outstanding,
        COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) AS total_collected,
        COUNT(DISTINCT student_id) AS unique_students
     FROM student_fee_charges
     WHERE created_at BETWEEN ? AND ?",
    [$dateFrom, $dateTo]
);

ob_start();
?>
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Fee Reports</h1>
        <a href="<?= url('finance', 'fm-dashboard') ?>" class="text-sm text-gray-500 hover:text-gray-700">&larr; Dashboard</a>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <p class="text-xs text-gray-500 font-medium uppercase">Total Collected (Period)</p>
            <p class="text-2xl font-bold text-green-600 mt-1"><?= CURRENCY_SYMBOL ?> <?= number_format($summaryStats['total_collected'], 2) ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <p class="text-xs text-gray-500 font-medium uppercase">Total Outstanding (Period)</p>
            <p class="text-2xl font-bold text-amber-600 mt-1"><?= CURRENCY_SYMBOL ?> <?= number_format($summaryStats['total_outstanding'], 2) ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <p class="text-xs text-gray-500 font-medium uppercase">Unique Students</p>
            <p class="text-2xl font-bold text-primary-800 mt-1"><?= number_format($summaryStats['unique_students']) ?></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <form method="GET" action="<?= url('finance', 'fm-reports') ?>" class="flex flex-col sm:flex-row gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Report Type</label>
                <select name="report" class="rounded-lg border-gray-300 shadow-sm text-sm">
                    <option value="outstanding" <?= $reportType === 'outstanding' ? 'selected' : '' ?>>Outstanding Fees</option>
                    <option value="penalties" <?= $reportType === 'penalties' ? 'selected' : '' ?>>Penalties</option>
                    <option value="revenue" <?= $reportType === 'revenue' ? 'selected' : '' ?>>Revenue / Collection</option>
                    <option value="exemptions" <?= $reportType === 'exemptions' ? 'selected' : '' ?>>Exemptions</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="rounded-lg border-gray-300 shadow-sm text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="rounded-lg border-gray-300 shadow-sm text-sm">
            </div>
            <?php if ($reportType === 'outstanding'): ?>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Class</label>
                <select name="class_id" class="rounded-lg border-gray-300 shadow-sm text-sm">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $cl): ?>
                        <option value="<?= $cl['id'] ?>" <?= $classId == $cl['id'] ? 'selected' : '' ?>><?= e($cl['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <button type="submit" class="px-6 py-2.5 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">Generate</button>
            <?php if (auth_has_permission('fee_management.export_reports')): ?>
            <a href="<?= url('finance', 'fm-report-export') ?>&report=<?= urlencode($reportType) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?><?= $classId ? '&class_id=' . $classId : '' ?>"
               class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                CSV
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Report Table -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900"><?= $reportTitle ?></h2>
            <p class="text-xs text-gray-500 mt-0.5"><?= count($reportData) ?> record<?= count($reportData) != 1 ? 's' : '' ?> &middot; <?= format_date($dateFrom) ?> to <?= format_date($dateTo) ?></p>
        </div>

        <?php if (empty($reportData)): ?>
            <div class="p-12 text-center text-gray-400 text-sm">
                <p>No data for the selected period and filters.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <th class="text-left px-4 py-3 font-medium text-gray-600"><?= $col ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($reportType === 'outstanding'): ?>
                            <?php foreach ($reportData as $row): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2.5 font-medium"><?= e($row['student_name']) ?></td>
                                <td class="px-4 py-2.5 text-gray-500 font-mono text-xs"><?= e($row['admission_no']) ?></td>
                                <td class="px-4 py-2.5 text-gray-500"><?= e($row['class_name'] ?? '-') ?></td>
                                <td class="px-4 py-2.5"><?= e(mb_strimwidth($row['fee_desc'], 0, 40, '...')) ?></td>
                                <td class="px-4 py-2.5 font-mono"><?= CURRENCY_SYMBOL ?> <?= number_format($row['amount'], 2) ?></td>
                                <td class="px-4 py-2.5"><?= format_date($row['due_date']) ?></td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $row['status'] === 'overdue' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                        <?php elseif ($reportType === 'penalties'): ?>
                            <?php foreach ($reportData as $row): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2.5 font-medium"><?= e($row['student_name']) ?></td>
                                <td class="px-4 py-2.5"><?= e($row['fee_desc'] ?? '-') ?></td>
                                <td class="px-4 py-2.5 font-mono text-red-600"><?= CURRENCY_SYMBOL ?> <?= number_format($row['penalty_amount'], 2) ?></td>
                                <td class="px-4 py-2.5 text-gray-500"><?= format_date($row['applied_at']) ?></td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $row['status'] === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                        <?php elseif ($reportType === 'revenue'): ?>
                            <?php
                            $grandBilled = $grandCollected = $grandOutstanding = $grandWaived = 0;
                            foreach ($reportData as $row):
                                $grandBilled     += $row['total_billed'];
                                $grandCollected  += $row['collected'];
                                $grandOutstanding+= $row['outstanding'];
                                $grandWaived     += $row['waived'];
                                $rate = $row['total_billed'] > 0 ? round(($row['collected'] / $row['total_billed']) * 100, 1) : 0;
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2.5 font-medium"><?= e($row['fee_desc']) ?></td>
                                <td class="px-4 py-2.5 font-mono"><?= CURRENCY_SYMBOL ?> <?= number_format($row['total_billed'], 2) ?></td>
                                <td class="px-4 py-2.5 font-mono text-green-600"><?= CURRENCY_SYMBOL ?> <?= number_format($row['collected'], 2) ?></td>
                                <td class="px-4 py-2.5 font-mono text-amber-600"><?= CURRENCY_SYMBOL ?> <?= number_format($row['outstanding'], 2) ?></td>
                                <td class="px-4 py-2.5 font-mono text-blue-600"><?= CURRENCY_SYMBOL ?> <?= number_format($row['waived'], 2) ?></td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                                            <div class="bg-green-500 h-2 rounded-full" style="width: <?= $rate ?>%"></div>
                                        </div>
                                        <span class="text-xs font-medium"><?= $rate ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <!-- Totals row -->
                            <?php $grandRate = $grandBilled > 0 ? round(($grandCollected / $grandBilled) * 100, 1) : 0; ?>
                            <tr class="bg-gray-50 font-semibold">
                                <td class="px-4 py-2.5">TOTAL</td>
                                <td class="px-4 py-2.5 font-mono"><?= CURRENCY_SYMBOL ?> <?= number_format($grandBilled, 2) ?></td>
                                <td class="px-4 py-2.5 font-mono text-green-600"><?= CURRENCY_SYMBOL ?> <?= number_format($grandCollected, 2) ?></td>
                                <td class="px-4 py-2.5 font-mono text-amber-600"><?= CURRENCY_SYMBOL ?> <?= number_format($grandOutstanding, 2) ?></td>
                                <td class="px-4 py-2.5 font-mono text-blue-600"><?= CURRENCY_SYMBOL ?> <?= number_format($grandWaived, 2) ?></td>
                                <td class="px-4 py-2.5 text-sm"><?= $grandRate ?>%</td>
                            </tr>

                        <?php elseif ($reportType === 'exemptions'): ?>
                            <?php foreach ($reportData as $row): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2.5 font-medium"><?= e($row['student_name']) ?></td>
                                <td class="px-4 py-2.5 text-gray-500 font-mono text-xs"><?= e($row['admission_no']) ?></td>
                                <td class="px-4 py-2.5"><?= e($row['fee_desc'] ?? '-') ?></td>
                                <td class="px-4 py-2.5"><?= e($row['reason'] ?? '-') ?></td>
                                <td class="px-4 py-2.5 text-gray-500"><?= format_date($row['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
