<?php
/**
 * Finance — Collect Payment Save Action
 * Processes the payment form submission.
 * TeleBirr payments are auto-marked as paid.
 * Other methods require manual confirmation checkbox.
 */
csrf_protect();

$studentId    = input_int('student_id');
$studentFeeId = input_int('student_fee_id');
$amount       = (float) input('amount');
$channel      = input('channel');
$notes        = input('notes');
$confirmPaid  = input_int('confirm_paid');

// Validate required fields
if (!$studentId || !$studentFeeId || $amount <= 0 || !$channel) {
    set_flash('error', 'Please fill in all required fields with valid values.');
    redirect(url('finance', 'collect-payment') . '&student_id=' . $studentId);
}

// TeleBirr is auto-confirmed; others require manual confirmation
$isTeleBirr = ($channel === 'telebirr');
if (!$isTeleBirr && !$confirmPaid) {
    set_flash('error', 'Please confirm that the payment has been received.');
    redirect(url('finance', 'collect-payment') . '&student_id=' . $studentId);
}

// Fetch the student fee record
$sf = db_fetch_one(
    "SELECT * FROM fin_student_fees WHERE id = ? AND student_id = ? AND is_active = 1",
    [$studentFeeId, $studentId]
);
if (!$sf) {
    set_flash('error', 'Active fee assignment not found.');
    redirect(url('finance', 'collect-payment') . '&student_id=' . $studentId);
}

// Ensure amount doesn't exceed balance
if ($amount > $sf['balance']) {
    $amount = (float) $sf['balance'];
}

$fee  = db_fetch_one("SELECT description FROM fin_fees WHERE id = ?", [$sf['fee_id']]);
$user = auth_user();
$newBalance = $sf['balance'] - $amount;

// Gather channel-specific details
$channelTxId       = null;
$channelPayType    = null;
$channelDepositor  = null;
$channelBranch     = null;
$payerPhone        = null;
$reference         = input('reference');

if ($isTeleBirr) {
    $channelTxId = input('channel_transaction_id');
    $payerPhone  = input('payer_phone');
    if (!$channelTxId) {
        set_flash('error', 'TeleBirr Transaction ID is required.');
        redirect(url('finance', 'collect-payment') . '&student_id=' . $studentId);
    }
} elseif (in_array($channel, ['bank_transfer', 'bank_deposit'])) {
    $channelPayType   = input('channel_payment_type');
    $channelDepositor = input('channel_depositor_name');
    $channelBranch    = input('channel_depositor_branch');
    $channelTxId      = input('bank_transaction_id');
}

// Generate receipt number
$receiptNo = 'RCP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

db_begin();
try {
    // Update student fee balance
    db_update('fin_student_fees', [
        'balance' => $newBalance,
    ], 'id = ?', [$studentFeeId]);

    // Record transaction
    $txId = db_insert('fin_transactions', [
        'student_id'              => $studentId,
        'student_fee_id'          => $studentFeeId,
        'type'                    => 'payment',
        'amount'                  => -$amount,
        'currency'                => $sf['currency'],
        'balance_before'          => $sf['balance'],
        'balance_after'           => $newBalance,
        'description'             => 'Payment for: ' . ($fee['description'] ?? 'Fee'),
        'channel'                 => $channel,
        'channel_payment_type'    => $channelPayType,
        'channel_depositor_name'  => $channelDepositor,
        'channel_depositor_branch'=> $channelBranch,
        'channel_transaction_id'  => $channelTxId,
        'payer_phone'             => $payerPhone,
        'receipt_no'              => $receiptNo,
        'reference'               => $reference ?: null,
        'notes'                   => $notes ?: null,
        'print_count'             => 0,
        'processed_by'            => $user['id'],
    ]);

    db_commit();

    $channelLabel = ucfirst(str_replace('_', ' ', $channel));
    set_flash('success', "Payment of " . format_money($amount) . " via {$channelLabel} recorded successfully. Receipt: {$receiptNo}");

    // Redirect to collect payment page with student selected, so they can see the print button
    redirect(url('finance', 'collect-payment') . '&student_id=' . $studentId);

} catch (Throwable $e) {
    db_rollback();
    error_log('Collect payment error: ' . $e->getMessage());
    set_flash('error', 'Failed to record payment. Please try again.');
    redirect(url('finance', 'collect-payment') . '&student_id=' . $studentId);
}
