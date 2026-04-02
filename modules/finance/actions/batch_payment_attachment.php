<?php
/**
 * Finance — Batch Payment Attachment (PDF) Action
 * Loads all transactions for a batch_receipt_no from DB and generates
 * an A5 PDF attachment matching the single-receipt style.
 * Tracks print_count on each transaction so subsequent prints show "COPY".
 */

$batchNo = input('batch') ?: (route_id() ? null : null);
if (!$batchNo) {
    // Try from query string
    $batchNo = $_GET['batch'] ?? null;
}
if (!$batchNo) {
    set_flash('error', 'Invalid batch receipt number.');
    redirect(url('finance', 'collect-payment'));
}

// Fetch all payment transactions in this batch
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

// Determine copy status: if any tx has been printed before, mark as COPY
$isCopy = false;
foreach ($txRows as $row) {
    if (((int)($row['print_count'] ?? 0)) > 0) {
        $isCopy = true;
        break;
    }
}

// Increment print_count on all transactions in the batch
foreach ($txRows as $row) {
    db_update('fin_transactions', [
        'print_count' => ((int)($row['print_count'] ?? 0)) + 1,
    ], 'id = ?', [$row['id']]);
}

// Build batchData from first transaction (shared fields)
$first = $txRows[0];
$batchData = [
    'batch_receipt_no'        => $batchNo,
    'student_name'            => $first['student_name'],
    'admission_no'            => $first['admission_no'],
    'class_name'              => $first['class_name'] ?? '—',
    'channel'                 => $first['channel'],
    'created_at'              => $first['created_at'],
    'processed_by_name'       => $first['processed_by_name'],
    'reference'               => $first['reference'],
    'notes'                   => $first['notes'],
    'channel_transaction_id'  => $first['channel_transaction_id'],
    'payer_phone'             => $first['payer_phone'],
    'channel_payment_type'    => $first['channel_payment_type'],
    'channel_depositor_name'  => $first['channel_depositor_name'],
    'channel_depositor_branch'=> $first['channel_depositor_branch'],
    'currency'                => $first['currency'] ?? 'ETB',
];

// Build fee rows for PDF
$feeRows = [];
foreach ($txRows as $row) {
    $feeRows[] = [
        'fee_description' => $row['fee_description'] ?? ($row['description'] ?? 'Fee'),
        'amount'          => $row['amount'],
        'receipt_no'      => $row['receipt_no'],
        'balance_before'  => $row['balance_before'],
        'balance_after'   => $row['balance_after'],
    ];
}

// Load PDF constants if not already defined
if (!defined('SCHOOL_NAME')) {
    define('SCHOOL_NAME',      'Urji Beri School');
    define('SCHOOL_TELEPHONE', '0912097003');
}

// Generate PDF
require_once APP_ROOT . '/core/pdf_batch_payment_attachment.php';

$pdf = new BatchPaymentAttachmentPDF($batchData, $feeRows, $isCopy);
$pdf->generate();
exit;
