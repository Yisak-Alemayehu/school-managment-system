<?php
/**
 * Finance — Make Payment for Student Fee
 */
csrf_protect();

$studentId    = input_int('student_id');
$studentFeeId = input_int('student_fee_id');
$amount       = (float) input('amount');
$channel      = input('channel');
$reference    = input('reference');

if (!$studentId || !$studentFeeId || $amount <= 0) {
    set_flash('error', 'Invalid payment amount.');
    redirect(url('finance', 'student-detail', $studentId ?: 0));
}

$sf = db_fetch_one("SELECT * FROM fin_student_fees WHERE id = ? AND student_id = ? AND is_active = 1", [$studentFeeId, $studentId]);
if (!$sf) {
    set_flash('error', 'Active fee assignment not found.');
    redirect(url('finance', 'student-detail', $studentId));
}

// Overpayment guard: cap amount to outstanding balance
if ($amount > $sf['balance']) {
    $amount = (float) $sf['balance'];
}

$fee = db_fetch_one("SELECT description FROM fin_fees WHERE id = ?", [$sf['fee_id']]);
$user = auth_user();
$newBalance = $sf['balance'] - $amount;

db_begin();
try {
    db_update('fin_student_fees', [
        'balance' => $newBalance,
    ], 'id = ?', [$studentFeeId]);

    db_insert('fin_transactions', [
        'student_id'     => $studentId,
        'student_fee_id' => $studentFeeId,
        'type'           => 'payment',
        'amount'         => -$amount,
        'currency'       => $sf['currency'],
        'balance_before' => $sf['balance'],
        'balance_after'  => $newBalance,
        'description'    => 'Payment for: ' . ($fee['description'] ?? 'Fee'),
        'channel'        => $channel ?: null,
        'reference'      => $reference ?: null,
        'processed_by'   => $user['id'],
    ]);

    db_commit();
    set_flash('success', 'Payment recorded successfully.');
} catch (Throwable $e) {
    db_rollback();
    error_log('Payment error: ' . $e->getMessage());
    set_flash('error', 'Failed to record payment.');
}

redirect(url('finance', 'student-detail', $studentId));
