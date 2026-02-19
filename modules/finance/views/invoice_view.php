<?php
/**
 * Finance — Invoice Detail View
 */
$id = input_int('id');
$invoice = db_fetch_one("
    SELECT i.*, s.first_name, s.last_name, s.admission_no, s.phone,
           c.name AS class_name, sess.name AS session_name, t.name AS term_name
    FROM invoices i
    JOIN students s ON s.id = i.student_id
    JOIN classes c ON c.id = i.class_id
    LEFT JOIN academic_sessions sess ON sess.id = i.session_id
    LEFT JOIN terms t ON t.id = i.term_id
    WHERE i.id = ?
", [$id]);

if (!$invoice) {
    set_flash('error', 'Invoice not found.');
    redirect(url('finance', 'invoices'));
}

$pageTitle = 'Invoice ' . $invoice['invoice_no'];

// Get items
$items = db_fetch_all("
    SELECT ii.*, fc.name AS category_name, fc.type AS category_type
    FROM invoice_items ii
    LEFT JOIN fee_categories fc ON fc.id = ii.fee_category_id
    WHERE ii.invoice_id = ?
    ORDER BY ii.id
", [$id]);

// Get payments
$payments = db_fetch_all("
    SELECT p.* FROM payments p WHERE p.invoice_id = ? ORDER BY p.payment_date DESC
", [$id]);

$due = $invoice['total_amount'] - $invoice['paid_amount'];

ob_start();
?>
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Invoice <?= e($invoice['invoice_no']) ?></h1>
        <div class="flex gap-2">
            <?php if ($invoice['status'] !== 'paid' && $invoice['status'] !== 'cancelled'): ?>
                <a href="<?= url('finance', 'payment-record') ?>&invoice_id=<?= $id ?>"
                   class="px-4 py-2 bg-green-700 text-white rounded-lg text-sm font-medium hover:bg-green-800">Record Payment</a>
            <?php endif; ?>
            <a href="<?= url('finance', 'invoice-print') ?>&id=<?= $id ?>" target="_blank"
               class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Print</a>
            <a href="<?= url('finance', 'invoices') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Back</a>
        </div>
    </div>

    <!-- Invoice Meta -->
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <div class="text-xs text-gray-500">Student</div>
                <div class="font-medium text-gray-900"><?= e($invoice['first_name'] . ' ' . $invoice['last_name']) ?></div>
                <div class="text-xs text-gray-500"><?= e($invoice['admission_no']) ?></div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Class / Term</div>
                <div class="font-medium text-gray-900"><?= e($invoice['class_name']) ?></div>
                <div class="text-xs text-gray-500"><?= e(($invoice['session_name'] ?? '') . ' • ' . ($invoice['term_name'] ?? '')) ?></div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Due Date</div>
                <div class="font-medium text-gray-900"><?= $invoice['due_date'] ? format_date($invoice['due_date']) : '—' ?></div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Status</div>
                <?php
                $badge = match($invoice['status']) {
                    'paid'      => 'bg-green-100 text-green-800',
                    'partial'   => 'bg-yellow-100 text-yellow-800',
                    'cancelled' => 'bg-gray-100 text-gray-800',
                    default     => 'bg-red-100 text-red-800',
                };
                ?>
                <span class="inline-block px-2 py-1 text-xs rounded-full <?= $badge ?>"><?= ucfirst($invoice['status']) ?></span>
            </div>
        </div>
    </div>

    <!-- Items -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-6 py-4 border-b bg-gray-50">
            <h2 class="font-semibold text-gray-900">Fee Items</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="px-6 py-3 text-sm text-gray-900"><?= e($item['description'] ?? $item['category_name']) ?></td>
                        <td class="px-6 py-3 text-sm">
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><?= ucfirst($item['category_type'] ?? 'other') ?></span>
                        </td>
                        <td class="px-6 py-3 text-sm text-right font-semibold"><?= format_currency($item['amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <?php if ($invoice['discount_amount'] > 0): ?>
                    <tr class="bg-gray-50">
                        <td colspan="2" class="px-6 py-2 text-sm text-right text-gray-500">Subtotal</td>
                        <td class="px-6 py-2 text-sm text-right"><?= format_currency($invoice['total_amount'] + $invoice['discount_amount']) ?></td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td colspan="2" class="px-6 py-2 text-sm text-right text-green-700">Discount</td>
                        <td class="px-6 py-2 text-sm text-right text-green-700">-<?= format_currency($invoice['discount_amount']) ?></td>
                    </tr>
                <?php endif; ?>
                <tr class="bg-gray-50 font-bold">
                    <td colspan="2" class="px-6 py-3 text-right">Total</td>
                    <td class="px-6 py-3 text-right text-lg"><?= format_currency($invoice['total_amount']) ?></td>
                </tr>
                <tr class="bg-green-50">
                    <td colspan="2" class="px-6 py-2 text-right text-green-700">Paid</td>
                    <td class="px-6 py-2 text-right font-semibold text-green-700"><?= format_currency($invoice['paid_amount']) ?></td>
                </tr>
                <tr class="bg-red-50 font-bold">
                    <td colspan="2" class="px-6 py-3 text-right text-red-700">Balance Due</td>
                    <td class="px-6 py-3 text-right text-lg text-red-700"><?= format_currency($due) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Payments History -->
    <?php if (!empty($payments)): ?>
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50">
                <h2 class="font-semibold text-gray-900">Payment History</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td class="px-6 py-3 text-sm font-mono"><?= e($pay['receipt_no'] ?? '—') ?></td>
                            <td class="px-6 py-3 text-sm"><?= format_date($pay['payment_date']) ?></td>
                            <td class="px-6 py-3 text-sm"><?= ucfirst(str_replace('_', ' ', $pay['payment_method'])) ?></td>
                            <td class="px-6 py-3 text-sm text-right font-semibold text-green-700"><?= format_currency($pay['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($invoice['notes']): ?>
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Notes</h3>
            <p class="text-sm text-gray-700"><?= nl2br(e($invoice['notes'])) ?></p>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
