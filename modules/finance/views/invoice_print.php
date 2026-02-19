<?php
/**
 * Finance — Printable Invoice
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

$items = db_fetch_all("
    SELECT ii.*, fc.name AS category_name
    FROM invoice_items ii
    LEFT JOIN fee_categories fc ON fc.id = ii.fee_category_id
    WHERE ii.invoice_id = ?
    ORDER BY ii.id
", [$id]);

$due = $invoice['total_amount'] - $invoice['paid_amount'];
$schoolName = get_school_name();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice <?= e($invoice['invoice_no']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            @page { margin: 1cm; size: A4; }
        }
    </style>
</head>
<body class="bg-white p-6 max-w-2xl mx-auto text-sm">

    <div class="no-print mb-4 flex gap-3">
        <button onclick="window.print()" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium">Print</button>
        <a href="<?= url('finance', 'invoice-view') ?>&id=<?= $id ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Back</a>
    </div>

    <div class="text-center border-b-2 border-gray-800 pb-4 mb-4">
        <h1 class="text-xl font-bold"><?= e($schoolName) ?></h1>
        <p class="text-gray-600 text-sm">FEE INVOICE</p>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6">
        <div>
            <div class="text-xs text-gray-500">Bill To:</div>
            <div class="font-semibold"><?= e($invoice['first_name'] . ' ' . $invoice['last_name']) ?></div>
            <div class="text-xs text-gray-600">Adm: <?= e($invoice['admission_no']) ?> | Class: <?= e($invoice['class_name']) ?></div>
        </div>
        <div class="text-right">
            <div class="text-xs text-gray-500">Invoice No:</div>
            <div class="font-semibold font-mono"><?= e($invoice['invoice_no']) ?></div>
            <div class="text-xs text-gray-600">Date: <?= format_date($invoice['created_at']) ?></div>
            <?php if ($invoice['due_date']): ?>
                <div class="text-xs text-gray-600">Due: <?= format_date($invoice['due_date']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <table class="w-full border border-gray-300 mb-4">
        <thead>
            <tr class="bg-gray-100">
                <th class="border border-gray-300 px-3 py-2 text-left text-xs">#</th>
                <th class="border border-gray-300 px-3 py-2 text-left text-xs">Description</th>
                <th class="border border-gray-300 px-3 py-2 text-right text-xs">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td class="border border-gray-300 px-3 py-1.5 text-xs"><?= $i + 1 ?></td>
                    <td class="border border-gray-300 px-3 py-1.5"><?= e($item['description'] ?? $item['category_name']) ?></td>
                    <td class="border border-gray-300 px-3 py-1.5 text-right"><?= format_currency($item['amount']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <?php if ($invoice['discount_amount'] > 0): ?>
                <tr>
                    <td colspan="2" class="border border-gray-300 px-3 py-1.5 text-right text-xs">Discount</td>
                    <td class="border border-gray-300 px-3 py-1.5 text-right text-green-700">-<?= format_currency($invoice['discount_amount']) ?></td>
                </tr>
            <?php endif; ?>
            <tr class="bg-gray-100 font-bold">
                <td colspan="2" class="border border-gray-300 px-3 py-2 text-right">Total Due</td>
                <td class="border border-gray-300 px-3 py-2 text-right"><?= format_currency($invoice['total_amount']) ?></td>
            </tr>
            <tr>
                <td colspan="2" class="border border-gray-300 px-3 py-1.5 text-right text-green-700">Paid</td>
                <td class="border border-gray-300 px-3 py-1.5 text-right text-green-700"><?= format_currency($invoice['paid_amount']) ?></td>
            </tr>
            <tr class="font-bold text-lg">
                <td colspan="2" class="border border-gray-300 px-3 py-2 text-right text-red-700">Balance</td>
                <td class="border border-gray-300 px-3 py-2 text-right text-red-700"><?= format_currency($due) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="text-xs text-gray-500 mt-6">
        <p>Session: <?= e($invoice['session_name'] ?? '') ?> | Term: <?= e($invoice['term_name'] ?? '') ?></p>
        <?php if ($invoice['notes']): ?>
            <p class="mt-1">Note: <?= e($invoice['notes']) ?></p>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-2 gap-8 mt-12 pt-4">
        <div class="text-center">
            <div class="border-t border-gray-400 pt-1 text-xs text-gray-600">Accountant</div>
        </div>
        <div class="text-center">
            <div class="border-t border-gray-400 pt-1 text-xs text-gray-600">Parent/Guardian</div>
        </div>
    </div>

    <div class="text-center text-xs text-gray-400 mt-6">
        Generated on <?= date('F j, Y') ?> — <?= e($schoolName) ?>
    </div>
</body>
</html>
<?php exit; ?>
