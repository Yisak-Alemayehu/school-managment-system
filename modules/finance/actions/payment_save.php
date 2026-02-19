<?php
/**
 * Finance — Save Payment
 */
verify_csrf();

$invoiceId = input_int('invoice_id');
$amount    = (float)($_POST['amount'] ?? 0);
$date      = $_POST['payment_date'] ?? date('Y-m-d');
$method    = $_POST['payment_method'] ?? 'cash';
$reference = trim($_POST['reference'] ?? '');
$remarks   = trim($_POST['remarks'] ?? '');

$invoice = db_fetch_one("
    SELECT i.*, s.first_name, s.last_name
    FROM invoices i
    JOIN students s ON s.id = i.student_id
    WHERE i.id = ? AND i.status IN ('unpaid', 'partial')
", [$invoiceId]);

if (!$invoice) {
    set_flash('error', 'Invoice not found or already paid.');
    redirect(url('finance', 'payments'));
}

$balance = $invoice['total_amount'] - $invoice['paid_amount'];

if ($amount <= 0 || $amount > $balance) {
    set_flash('error', 'Invalid amount. Must be between 0.01 and ' . format_currency($balance));
    redirect(url('finance', 'payment-record') . "&invoice_id={$invoiceId}");
}

$pdo = db_connection();
$pdo->beginTransaction();

try {
    // Generate receipt number
    $receiptNo = 'RCP-' . date('Ymd') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

    // Insert payment
    $paymentId = db_insert('payments', [
        'invoice_id'     => $invoiceId,
        'student_id'     => $invoice['student_id'],
        'amount'         => $amount,
        'payment_date'   => $date,
        'payment_method' => $method,
        'receipt_no'     => $receiptNo,
        'reference'      => $reference ?: null,
        'remarks'        => $remarks ?: null,
        'received_by'    => current_user_id(),
    ]);

    // Update invoice paid amount and status
    $newPaid = $invoice['paid_amount'] + $amount;
    $newStatus = ($newPaid >= $invoice['total_amount']) ? 'paid' : 'partial';

    db_update('invoices', [
        'paid_amount' => $newPaid,
        'status'      => $newStatus,
    ], 'id = ?', [$invoiceId]);

    $pdo->commit();

    audit_log('payment_record', 'payments', $paymentId, "receipt={$receiptNo}, amount={$amount}, invoice={$invoice['invoice_no']}");
    set_flash('success', "Payment of " . format_currency($amount) . " recorded. Receipt: {$receiptNo}");

    redirect(url('finance', 'payment-receipt') . "&id={$paymentId}");

} catch (\Exception $ex) {
    $pdo->rollBack();
    set_flash('error', 'Payment failed: ' . $ex->getMessage());
    redirect(url('finance', 'payment-record') . "&invoice_id={$invoiceId}");
}
