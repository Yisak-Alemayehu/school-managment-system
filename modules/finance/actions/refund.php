<?php
/**
 * Finance — Refund Payment
 */
csrf_protect();

$studentId    = input_int('student_id');
$studentFeeId = input_int('student_fee_id');
$amount       = (float) input('amount');
$reason       = trim(input('reason'));

if (!$studentId || !$studentFeeId || $amount <= 0) {
    set_flash('error', 'Invalid refund request. Amount must be greater than zero.');
    redirect(url('finance', 'student-detail', $studentId ?: 0));
}

if (!$reason) {
    set_flash('error', 'Reason is required for refunds.');
    redirect(url('finance', 'student-detail', $studentId));
}

$sf = db_fetch_one(
    "SELECT * FROM fin_student_fees WHERE id = ? AND student_id = ? AND is_active = 1",
    [$studentFeeId, $studentId]
);
if (!$sf) {
    set_flash('error', 'Active fee assignment not found.');
    redirect(url('finance', 'student-detail', $studentId));
}

$user = auth_user();
$balanceBefore = (float) $sf['balance'];
$balanceAfter  = $balanceBefore + $amount;

db_begin();
try {
    // Increase balance
    db_update('fin_student_fees', [
        'balance' => $balanceAfter,
    ], 'id = ?', [$studentFeeId]);

    // Record refund transaction
    db_insert('fin_transactions', [
        'student_id'     => $studentId,
        'student_fee_id' => $studentFeeId,
        'type'           => 'refund',
        'amount'         => $amount,
        'currency'       => $sf['currency'],
        'balance_before' => $balanceBefore,
        'balance_after'  => $balanceAfter,
        'description'    => 'Refund: ' . $reason,
        'processed_by'   => $user['id'],
    ]);

    db_commit();
    set_flash('success', 'Refund of ' . format_money($amount) . ' processed successfully.');
} catch (Throwable $e) {
    db_rollback();
    error_log('Refund error: ' . $e->getMessage());
    set_flash('error', 'Failed to process refund.');
}

redirect(url('finance', 'student-detail', $studentId));
