<?php
/**
 * Finance — Printable Payment Receipt
 */
$id = input_int('id');
$payment = db_fetch_one("
    SELECT p.*, i.invoice_no, i.total_amount, i.paid_amount,
           s.first_name, s.last_name, s.admission_no,
           c.name AS class_name, u.full_name AS received_by_name
    FROM payments p
    JOIN invoices i ON i.id = p.invoice_id
    JOIN students s ON s.id = p.student_id
    JOIN classes c ON c.id = i.class_id
    LEFT JOIN users u ON u.id = p.received_by
    WHERE p.id = ?
", [$id]);

if (!$payment) {
    set_flash('error', 'Payment not found.');
    redirect(url('finance', 'payments'));
}

$schoolName = get_school_name();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt <?= e($payment['receipt_no']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            @page { margin: 1cm; size: A5 landscape; }
        }
    </style>
</head>
<body class="bg-white p-6 max-w-xl mx-auto text-sm">

    <div class="no-print mb-4 flex gap-3">
        <button onclick="window.print()" class="px-4 py-2 bg-green-700 text-white rounded-lg text-sm font-medium">Print Receipt</button>
        <a href="<?= url('finance', 'payments') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Back to Payments</a>
        <a href="<?= url('finance', 'invoice-view') ?>&id=<?= $payment['invoice_id'] ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">View Invoice</a>
    </div>

    <div class="border-2 border-gray-800 rounded-lg p-6">
        <!-- Header -->
        <div class="text-center border-b border-gray-300 pb-3 mb-4">
            <h1 class="text-lg font-bold"><?= e($schoolName) ?></h1>
            <p class="text-sm text-gray-600">PAYMENT RECEIPT</p>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <div class="text-xs text-gray-500">Receipt No:</div>
                <div class="font-bold font-mono text-lg"><?= e($payment['receipt_no']) ?></div>
            </div>
            <div class="text-right">
                <div class="text-xs text-gray-500">Date:</div>
                <div class="font-semibold"><?= format_date($payment['payment_date']) ?></div>
            </div>
        </div>

        <div class="border rounded-lg p-4 bg-gray-50 mb-4 space-y-1">
            <div class="flex justify-between">
                <span class="text-gray-600">Student:</span>
                <strong><?= e($payment['first_name'] . ' ' . $payment['last_name']) ?></strong>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Admission No:</span>
                <span><?= e($payment['admission_no']) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Class:</span>
                <span><?= e($payment['class_name']) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Invoice:</span>
                <span class="font-mono"><?= e($payment['invoice_no']) ?></span>
            </div>
        </div>

        <div class="border rounded-lg p-4 mb-4 space-y-2">
            <div class="flex justify-between text-lg">
                <span class="font-semibold">Amount Paid:</span>
                <span class="font-bold text-green-700"><?= format_currency($payment['amount']) ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Payment Method:</span>
                <span><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></span>
            </div>
            <?php if ($payment['reference']): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Reference:</span>
                    <span class="font-mono"><?= e($payment['reference']) ?></span>
                </div>
            <?php endif; ?>
            <div class="flex justify-between text-sm border-t pt-2">
                <span class="text-gray-600">Invoice Balance:</span>
                <span class="font-semibold <?= ($payment['total_amount'] - $payment['paid_amount']) > 0 ? 'text-red-600' : 'text-green-700' ?>">
                    <?= format_currency($payment['total_amount'] - $payment['paid_amount']) ?>
                </span>
            </div>
        </div>

        <?php if ($payment['remarks']): ?>
            <p class="text-xs text-gray-500 mb-4">Remarks: <?= e($payment['remarks']) ?></p>
        <?php endif; ?>

        <div class="grid grid-cols-2 gap-8 mt-8 pt-4">
            <div class="text-center">
                <div class="border-t border-gray-400 pt-1 text-xs text-gray-600">Received By</div>
                <div class="text-xs"><?= e($payment['received_by_name'] ?? '') ?></div>
            </div>
            <div class="text-center">
                <div class="border-t border-gray-400 pt-1 text-xs text-gray-600">Parent/Guardian</div>
            </div>
        </div>

        <div class="text-center text-xs text-gray-400 mt-4">
            Generated on <?= date('F j, Y') ?> — <?= e($schoolName) ?>
        </div>
    </div>
</body>
</html>
<?php exit; ?>
