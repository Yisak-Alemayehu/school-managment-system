<?php
/**
 * Finance — Batch Payment Receipt
 * Printable receipt showing all fees paid in one batch submission.
 * Data is stored in $_SESSION['_batch_receipt'] by collect_payment_save.php.
 */

$batch = $_SESSION['_batch_receipt'] ?? null;
if (!$batch) {
    set_flash('error', 'No batch receipt data found.');
    redirect(url('finance', 'collect-payment'));
}

// Keep data for refresh but allow navigation away
$schoolName = get_school_name();
$channelLabel = ucfirst(str_replace('_', ' ', $batch['channel']));

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
            <button onclick="window.print()" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print
            </button>
            <a href="<?= url('finance', 'collect-payment') ?>&student_id=<?= (int)$batch['student_id'] ?>"
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
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 font-mono"><?= e($batch['batch_receipt_no']) ?></p>
        </div>

        <!-- Student Info -->
        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm mb-6">
            <div>
                <span class="text-gray-500 dark:text-dark-muted">Student:</span>
                <span class="font-medium text-gray-900 dark:text-dark-text ml-1"><?= e($batch['student_name']) ?></span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-dark-muted">Code:</span>
                <span class="font-medium text-gray-900 dark:text-dark-text ml-1"><?= e($batch['admission_no']) ?></span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-dark-muted">Date:</span>
                <span class="font-medium text-gray-900 dark:text-dark-text ml-1"><?= format_datetime($batch['date']) ?></span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-dark-muted">Channel:</span>
                <span class="font-medium text-gray-900 dark:text-dark-text ml-1"><?= e($channelLabel) ?></span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-dark-muted">Processed By:</span>
                <span class="font-medium text-gray-900 dark:text-dark-text ml-1"><?= e($batch['processed_by']) ?></span>
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
                <?php foreach ($batch['fees'] as $fee): ?>
                <tr class="border-b border-gray-100 dark:border-dark-border">
                    <td class="px-3 py-2 text-gray-900 dark:text-dark-text"><?= e($fee['fee_desc']) ?></td>
                    <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-dark-text"><?= format_money($fee['amount']) ?></td>
                    <td class="px-3 py-2 text-gray-500 dark:text-dark-muted font-mono text-xs"><?= e($fee['receipt_no']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-dark-bg">
                <tr>
                    <td class="px-3 py-2 font-bold text-gray-900 dark:text-dark-text">Total</td>
                    <td class="px-3 py-2 text-right font-bold text-gray-900 dark:text-dark-text"><?= format_money($batch['total_paid']) ?></td>
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
// Clear session data after rendering
unset($_SESSION['_batch_receipt']);

$content = ob_get_clean();
$page_title = $pageTitle;
include TEMPLATES_PATH . '/layout.php';
