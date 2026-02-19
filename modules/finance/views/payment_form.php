<?php
/**
 * Finance — Record Payment Form
 */
$invoiceId = input_int('invoice_id');
$pageTitle = 'Record Payment';

$invoice = null;
if ($invoiceId) {
    $invoice = db_fetch_one("
        SELECT i.*, s.first_name, s.last_name, s.admission_no, c.name AS class_name
        FROM invoices i
        JOIN students s ON s.id = i.student_id
        JOIN classes c ON c.id = i.class_id
        WHERE i.id = ? AND i.status != 'paid' AND i.status != 'cancelled'
    ", [$invoiceId]);
}

// If no specific invoice, get unpaid invoices for search
$unpaidInvoices = [];
if (!$invoice) {
    $sessionId = get_active_session_id();
    $unpaidInvoices = db_fetch_all("
        SELECT i.id, i.invoice_no, i.total_amount, i.paid_amount,
               s.first_name, s.last_name, s.admission_no, c.name AS class_name
        FROM invoices i
        JOIN students s ON s.id = i.student_id
        JOIN classes c ON c.id = i.class_id
        WHERE i.session_id = ? AND i.status IN ('unpaid', 'partial')
        ORDER BY s.first_name, s.last_name
    ", [$sessionId]);
}

ob_start();
?>
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Record Payment</h1>
        <a href="<?= url('finance', 'payments') ?>" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back</a>
    </div>

    <?php if (!$invoice && !$invoiceId): ?>
        <!-- Select Invoice -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold mb-4">Select Invoice</h2>
            <?php if (empty($unpaidInvoices)): ?>
                <p class="text-gray-500">No unpaid invoices found.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($unpaidInvoices as $ui): ?>
                        <?php $balance = $ui['total_amount'] - $ui['paid_amount']; ?>
                        <a href="<?= url('finance', 'payment-record') ?>&invoice_id=<?= $ui['id'] ?>"
                           class="block p-3 border rounded-lg hover:bg-gray-50 transition">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="font-medium text-gray-900"><?= e($ui['first_name'] . ' ' . $ui['last_name']) ?></span>
                                    <span class="text-xs text-gray-500 ml-2"><?= e($ui['admission_no']) ?> • <?= e($ui['class_name']) ?></span>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-red-600"><?= format_currency($balance) ?></div>
                                    <div class="text-xs text-gray-500"><?= e($ui['invoice_no']) ?></div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif (!$invoice): ?>
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <p class="text-gray-500">Invoice not found or already fully paid.</p>
        </div>
    <?php else: ?>
        <?php $balance = $invoice['total_amount'] - $invoice['paid_amount']; ?>

        <!-- Invoice Summary -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-blue-700">Student:</span>
                    <strong><?= e($invoice['first_name'] . ' ' . $invoice['last_name']) ?></strong>
                </div>
                <div class="text-right">
                    <span class="text-blue-700">Invoice:</span>
                    <strong class="font-mono"><?= e($invoice['invoice_no']) ?></strong>
                </div>
                <div><span class="text-blue-700">Class:</span> <?= e($invoice['class_name']) ?></div>
                <div class="text-right"><span class="text-blue-700">Total:</span> <?= format_currency($invoice['total_amount']) ?></div>
                <div><span class="text-blue-700">Paid so far:</span> <span class="text-green-700"><?= format_currency($invoice['paid_amount']) ?></span></div>
                <div class="text-right"><span class="text-blue-700">Balance:</span> <strong class="text-red-600"><?= format_currency($balance) ?></strong></div>
            </div>
        </div>

        <!-- Payment Form -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <form method="POST" action="<?= url('finance', 'payment-save') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount (ETB) *</label>
                        <input type="number" name="amount" required step="0.01" min="0.01" max="<?= $balance ?>"
                               value="<?= number_format($balance, 2, '.', '') ?>"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <p class="text-xs text-gray-500 mt-1">Max: <?= format_currency($balance) ?></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date *</label>
                        <input type="date" name="payment_date" required value="<?= date('Y-m-d') ?>"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
                        <select name="payment_method" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="telebirr">Telebirr</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reference / Transaction #</label>
                        <input type="text" name="reference" placeholder="Transaction or cheque number"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                    <textarea name="remarks" rows="2"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="submit" class="px-6 py-2 bg-green-700 text-white rounded-lg text-sm font-medium hover:bg-green-800">
                        Record Payment
                    </button>
                    <a href="<?= url('finance', 'invoice-view') ?>&id=<?= $invoice['id'] ?>"
                       class="px-6 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
