<?php
/**
 * Finance — Collect Supplementary Payment Save
 * Processes the supplementary fee payment form submission.
 */
csrf_protect();

$studentId         = input_int('student_id');
$supplementaryFeeId = input_int('supplementary_fee_id');
$amount            = (float) input('amount');
$channel           = input('channel');
$reference         = input('reference');
$notes             = input('notes');

if (!$studentId || !$supplementaryFeeId || $amount <= 0 || !$channel) {
    set_flash('error', 'Please fill in all required fields with valid values.');
    redirect(url('finance', 'collect-supplementary-payment') . '&student_id=' . $studentId);
}

// Verify the supplementary fee exists and is active
$sfee = db_fetch_one(
    "SELECT * FROM fin_supplementary_fees WHERE id = ? AND is_active = 1",
    [$supplementaryFeeId]
);
if (!$sfee) {
    set_flash('error', 'Supplementary fee not found or inactive.');
    redirect(url('finance', 'collect-supplementary-payment') . '&student_id=' . $studentId);
}

// Verify the student exists
$student = db_fetch_one("SELECT id FROM students WHERE id = ? AND deleted_at IS NULL", [$studentId]);
if (!$student) {
    set_flash('error', 'Student not found.');
    redirect(url('finance', 'collect-supplementary-payment'));
}

$user = auth_user();
$receiptNo = 'RCP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

db_begin();
try {
    db_insert('fin_supplementary_transactions', [
        'student_id'           => $studentId,
        'supplementary_fee_id' => $supplementaryFeeId,
        'amount'               => $amount,
        'currency'             => $sfee['currency'],
        'channel'              => $channel,
        'receipt_no'           => $receiptNo,
        'reference'            => $reference ?: null,
        'notes'                => $notes ?: null,
        'processed_by'         => $user['id'],
        'created_at'           => date('Y-m-d H:i:s'),
    ]);

    db_commit();
    set_flash('success', "Supplementary payment recorded successfully. Receipt: {$receiptNo}");
} catch (Throwable $e) {
    db_rollback();
    error_log('Supplementary payment error: ' . $e->getMessage());
    set_flash('error', 'Failed to record supplementary payment.');
}

redirect(url('finance', 'collect-supplementary-payment') . '&student_id=' . $studentId);
