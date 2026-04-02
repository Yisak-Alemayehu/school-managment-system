<?php
/**
 * Finance — Assign Fee to Student
 */
csrf_protect();

$studentId = input_int('student_id');
$feeId     = input_int('fee_id');

if (!$studentId || !$feeId) {
    set_flash('error', 'Invalid request.');
    redirect(url('finance', 'students'));
}

$fee = db_fetch_one("SELECT * FROM fin_fees WHERE id = ? AND is_active = 1", [$feeId]);
if (!$fee) {
    set_flash('error', 'Fee not found or inactive.');
    redirect(url('finance', 'student-detail', $studentId));
}

// Credit-hour fee: multiply amount by student's total credit hours
$assignedAmount = $fee['amount'];
if ($fee['is_credit_hour']) {
    $creditHours = (int) db_fetch_value(
        "SELECT COALESCE(SUM(sub.credit_hours), 0)
           FROM enrollments e
           JOIN subjects sub ON sub.class_id = e.class_id
          WHERE e.student_id = ? AND e.status = 'active'",
        [$studentId]
    );
    if ($creditHours === 0) {
        set_flash('error', 'Cannot assign credit-hour fee: student has 0 enrolled credit hours.');
        redirect(url('finance', 'student-detail', $studentId));
    }
    $assignedAmount = $fee['amount'] * $creditHours;
}

// Check if already assigned
$existing = db_fetch_one(
    "SELECT id FROM fin_student_fees WHERE student_id = ? AND fee_id = ? AND is_active = 1",
    [$studentId, $feeId]
);
if ($existing) {
    set_flash('error', 'This fee is already assigned to the student.');
    redirect(url('finance', 'student-detail', $studentId));
}

$user = auth_user();

db_begin();
try {
    $sfId = db_insert('fin_student_fees', [
        'student_id'  => $studentId,
        'fee_id'      => $feeId,
        'amount'      => $assignedAmount,
        'currency'    => $fee['currency'],
        'balance'     => $assignedAmount,
        'is_active'   => 1,
        'assigned_by' => $user['id'],
    ]);

    // Log transaction
    db_insert('fin_transactions', [
        'student_id'     => $studentId,
        'student_fee_id' => $sfId,
        'type'           => 'fee_assigned',
        'amount'         => $assignedAmount,
        'currency'       => $fee['currency'],
        'balance_before' => 0,
        'balance_after'  => $assignedAmount,
        'description'    => 'Fee assigned: ' . $fee['description'],
        'processed_by'   => $user['id'],
    ]);

    db_commit();
    set_flash('success', 'Fee assigned successfully.');
} catch (Throwable $e) {
    db_rollback();
    error_log('Assign fee error: ' . $e->getMessage());
    set_flash('error', 'Failed to assign fee.');
}

redirect(url('finance', 'student-detail', $studentId));
