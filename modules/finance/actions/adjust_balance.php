<?php
/**
 * Finance — Adjust Student Fee Balance
 */
csrf_protect();

$studentId    = input_int('student_id');
$studentFeeId = input_int('student_fee_id');
$amount       = (float) input('amount');
$reason       = input('reason');

if (!$studentId || !$studentFeeId || $amount == 0 || !$reason) {
    set_flash('error', 'All fields are required.');
    redirect(url('finance', 'student-detail', $studentId ?: 0));
}

$sf = db_fetch_one("SELECT * FROM fin_student_fees WHERE id = ? AND student_id = ? AND is_active = 1", [$studentFeeId, $studentId]);
if (!$sf) {
    set_flash('error', 'Active fee assignment not found.');
    redirect(url('finance', 'student-detail', $studentId));
}

$user = auth_user();
$newBalance = $sf['balance'] + $amount;

db_begin();
try {
    db_update('fin_student_fees', [
        'balance' => $newBalance,
    ], 'id = ?', [$studentFeeId]);

    db_insert('fin_transactions', [
        'student_id'     => $studentId,
        'student_fee_id' => $studentFeeId,
        'type'           => 'adjustment',
        'amount'         => $amount,
        'currency'       => $sf['currency'],
        'balance_before' => $sf['balance'],
        'balance_after'  => $newBalance,
        'description'    => 'Balance adjustment: ' . $reason,
        'processed_by'   => $user['id'],
    ]);

    db_commit();
    set_flash('success', 'Balance adjusted successfully.');
} catch (Throwable $e) {
    db_rollback();
    error_log('Adjust balance error: ' . $e->getMessage());
    set_flash('error', 'Failed to adjust balance.');
}

redirect(url('finance', 'student-detail', $studentId));
