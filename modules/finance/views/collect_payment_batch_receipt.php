<?php
/**
 * Finance — Batch Payment Receipt
 * Shows all fees paid in one batch submission.
 * Loads from DB by batch_receipt_no so receipts can be viewed/printed later.
 * Session data is used for initial redirect; direct URL access uses ?batch= param.
 */

// Determine batch receipt number from session (fresh payment) or query param (later access)
$batchNo = null;
$fromSession = false;
if (!empty($_SESSION['_batch_receipt']['batch_receipt_no'])) {
    $batchNo = $_SESSION['_batch_receipt']['batch_receipt_no'];
    $fromSession = true;
    unset($_SESSION['_batch_receipt']);
} else {
    $batchNo = input('batch') ?: ($_GET['batch'] ?? null);
}

if (!$batchNo) {
    set_flash('error', 'No batch receipt specified.');
    redirect(url('finance', 'collect-payment'));
}

// Load transactions from DB
$txRows = db_fetch_all(
    "SELECT t.*,
            f.description AS fee_description,
            s.full_name AS student_name, s.admission_no,
            c.name AS class_name,
            u.full_name AS processed_by_name
       FROM fin_transactions t
       JOIN students s ON t.student_id = s.id
       LEFT JOIN fin_student_fees sf ON t.student_fee_id = sf.id
       LEFT JOIN fin_fees f ON sf.fee_id = f.id
       LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
       LEFT JOIN classes c ON e.class_id = c.id
       LEFT JOIN users u ON t.processed_by = u.id
      WHERE t.batch_receipt_no = ? AND t.type = 'payment'
      ORDER BY t.id ASC",
    [$batchNo]
);

if (empty($txRows)) {
    set_flash('error', 'Batch receipt not found.');
    redirect(url('finance', 'collect-payment'));
}

$first = $txRows[0];
$studentId = $first['student_id'];
$studentName = $first['student_name'];
$admissionNo = $first['admission_no'];
$className = $first['class_name'] ?? '—';
$channel = $first['channel'];
$channelLabel = ucfirst(str_replace('_', ' ', $channel));
$processedBy = $first['processed_by_name'] ?? '—';
$createdAt = $first['created_at'];
$schoolName = get_school_name();

$totalPaid = 0;
foreach ($txRows as $r) {
    $totalPaid += abs((float)$r['amount']);
}

ob_start();
?>

<style>
@media print {
    nav, header, footer, .sidebar, .no-print, .print-hide { display: none !important; }
    body { background: white !important; }
    .print-area { box-shadow: none !important; border: none !important; }
}
</style>

<div class="max-w-2xl mx-auto space-y-4">
    <!-- Action Buttons (hidden on print) -->
    <div class="flex items-center justify-between no-print">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Batch Payment Receipt</h1>
        <div class="flex gap-2">
            <a href="<?= url('finance', 'batch-payment-attachment') ?>&batch=<?= urlencode($batchNo) ?>" target="_blank"
               class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 font-medium inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print PDF
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-gray-200 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 font-medium inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Page
            </button>
            <a href="<?= url('finance', 'collect-payment') ?>&student_id=<?= (int)$studentId ?>"
               class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 font-medium">
                Done
            </a>
        </div>
    </div>

    <!-- Receipt Card -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6 print-area">
        <!-- Header -->
        <div class="text-center mb-6 border-b border-gray-200 dark:border-dark-border pb-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text"><?= e($schoolName) ?></h2>
            <p class="text-sm text-gray-500 dark:text-dark-muted mt-1">Batch Payment Receipt</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 font-mono"><?= e($batchNo) ?></p>
        </div>

        <!-- Student Info -->
        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm mb-6">
            <div>
                <span class="text-gray-500 dark:text-dark-muted">Student:</span>
                <span class="font-medium text-gray-900 dark:text-dark-text ml-1"><?= e($studentName) ?></span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-dark-muted">Code:</span>
                <span class="font-medium text-gray-900 dark:text-dark-text ml-1"><?= e($admissionNo) ?></span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-dark-muted">Class:</span>
                <span class="font-medium text-gray-900 dark:text-dark-text ml-1"><?= e($className) ?></span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-dark-muted">Date:</span>
                <span class="font-medium text-gray-900 dark:text-dark-text ml-1"><?= format_datetime($createdAt) ?></span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-dark-muted">Channel:</span>
                <span class="font-medium text-gray-900 dark:text-dark-text ml-1"><?= e($channelLabel) ?></span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-dark-muted">Processed By:</span>
                <span class="font-medium text-gray-900 dark:text-dark-text ml-1"><?= e($processedBy) ?></span>
            </div>
        </div>

        <!-- Fees Table -->
        <table class="w-full text-sm border border-gray-200 dark:border-dark-border mb-4">
            <thead class="bg-gray-50 dark:bg-dark-bg">
                <tr>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-dark-muted border-b border-gray-200 dark:border-dark-border">Fee</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-600 dark:text-dark-muted border-b border-gray-200 dark:border-dark-border">Amount</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-dark-muted border-b border-gray-200 dark:border-dark-border">Receipt #</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($txRows as $r): ?>
                <tr class="border-b border-gray-100 dark:border-dark-border">
                    <td class="px-3 py-2 text-gray-900 dark:text-dark-text"><?= e($r['fee_description'] ?? $r['description'] ?? '—') ?></td>
                    <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-dark-text"><?= format_money(abs((float)$r['amount'])) ?></td>
                    <td class="px-3 py-2 text-gray-500 dark:text-dark-muted font-mono text-xs"><?= e($r['receipt_no'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-dark-bg">
                <tr>
                    <td class="px-3 py-2 font-bold text-gray-900 dark:text-dark-text">Total</td>
                    <td class="px-3 py-2 text-right font-bold text-gray-900 dark:text-dark-text"><?= format_money($totalPaid) ?></td>
                    <td class="px-3 py-2"></td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer -->
        <div class="text-center text-xs text-gray-400 dark:text-gray-500 mt-4 pt-4 border-t border-gray-200 dark:border-dark-border">
            <p>This is a computer-generated receipt. Thank you for your payment.</p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = $pageTitle;
include TEMPLATES_PATH . '/layout.php';
