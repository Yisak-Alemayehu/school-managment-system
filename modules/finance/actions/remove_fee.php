<?php
/**
 * Finance — Remove Fee from Student
 */
csrf_protect();

$studentId    = input_int('student_id');
$studentFeeId = input_int('student_fee_id');
$reason       = input('reason');

if (!$studentId || !$studentFeeId) {
    set_flash('error', 'Invalid request.');
    redirect(url('finance', 'students'));
}

$sf = db_fetch_one("SELECT * FROM fin_student_fees WHERE id = ? AND student_id = ? AND is_active = 1", [$studentFeeId, $studentId]);
if (!$sf) {
    set_flash('error', 'Active fee assignment not found.');
    redirect(url('finance', 'student-detail', $studentId));
}

$fee = db_fetch_one("SELECT description FROM fin_fees WHERE id = ?", [$sf['fee_id']]);
$user = auth_user();

db_begin();
try {
    db_update('fin_student_fees', [
        'is_active'  => 0,
        'removed_at' => date('Y-m-d H:i:s'),
        'removed_by' => $user['id'],
    ], 'id = ?', [$studentFeeId]);

    db_insert('fin_transactions', [
        'student_id'     => $studentId,
        'student_fee_id' => $studentFeeId,
        'type'           => 'fee_removed',
        'amount'         => -$sf['balance'],
        'currency'       => $sf['currency'],
        'balance_before' => $sf['balance'],
        'balance_after'  => 0,
        'description'    => 'Fee removed: ' . ($fee['description'] ?? '') . ($reason ? ' — ' . $reason : ''),
        'processed_by'   => $user['id'],
    ]);

    db_commit();
    set_flash('success', 'Fee removed successfully.');
} catch (Throwable $e) {
    db_rollback();
    error_log('Remove fee error: ' . $e->getMessage());
    set_flash('error', 'Failed to remove fee.');
}

redirect(url('finance', 'student-detail', $studentId));
