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

    // Auto-deduct wallet credit (from overpayments) against the new fee
    $walletCredit = (float) db_fetch_value(
        "SELECT COALESCE(SUM(amount), 0) FROM fin_transactions WHERE student_id = ? AND type = 'adjustment' AND amount > 0",
        [$studentId]
    );
    $walletDebit = (float) db_fetch_value(
        "SELECT COALESCE(ABS(SUM(amount)), 0) FROM fin_transactions WHERE student_id = ? AND type = 'adjustment' AND amount < 0",
        [$studentId]
    );
    $walletBalance = $walletCredit - $walletDebit;
    $walletApplied = 0;

    if ($walletBalance > 0.005 && $assignedAmount > 0) {
        $walletApplied = min($walletBalance, $assignedAmount);
        $newFeeBalance = $assignedAmount - $walletApplied;

        // Reduce the fee balance
        db_update('fin_student_fees', [
            'balance' => $newFeeBalance,
        ], 'id = ?', [$sfId]);

        // Record wallet debit (negative adjustment)
        db_insert('fin_transactions', [
            'student_id'     => $studentId,
            'student_fee_id' => $sfId,
            'type'           => 'adjustment',
            'amount'         => -$walletApplied,
            'currency'       => $fee['currency'],
            'description'    => 'Wallet credit applied to: ' . $fee['description'],
            'processed_by'   => $user['id'],
        ]);

        // Record the payment against the fee
        $receiptNo = 'RCP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        db_insert('fin_transactions', [
            'student_id'      => $studentId,
            'student_fee_id'  => $sfId,
            'type'            => 'payment',
            'amount'          => -$walletApplied,
            'currency'        => $fee['currency'],
            'balance_before'  => $assignedAmount,
            'balance_after'   => $newFeeBalance,
            'description'     => 'Auto-paid from wallet credit: ' . $fee['description'],
            'channel'         => 'wallet',
            'receipt_no'      => $receiptNo,
            'print_count'     => 0,
            'processed_by'    => $user['id'],
        ]);
    }

    db_commit();

    if ($walletApplied > 0) {
        $remaining = $assignedAmount - $walletApplied;
        $msg = 'Fee assigned. ' . format_money($walletApplied) . ' auto-deducted from wallet credit.';
        if ($remaining > 0) {
            $msg .= ' Remaining balance: ' . format_money($remaining) . '.';
        } else {
            $msg .= ' Fee fully paid from wallet.';
        }
        set_flash('success', $msg);
    } else {
        set_flash('success', 'Fee assigned successfully.');
    }
} catch (Throwable $e) {
    db_rollback();
    error_log('Assign fee error: ' . $e->getMessage());
    set_flash('error', 'Failed to assign fee.');
}

redirect(url('finance', 'student-detail', $studentId));
