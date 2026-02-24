<?php
/**
 * Fee Management — Save Exemption
 */

if (!is_post()) { redirect('finance', 'fm-assign-fees'); }
verify_csrf();

$feeId     = input_int('fee_id');
$studentId = input_int('student_id');
$reason    = trim($_POST['reason'] ?? '');

if (!$feeId || !$studentId) {
    set_flash('error', 'Fee and student are required.');
    redirect('finance', 'fm-assign-fees');
}

$fee = db_fetch_one("SELECT * FROM fees WHERE id = ? AND deleted_at IS NULL", [$feeId]);
if (!$fee) {
    set_flash('error', 'Fee not found.');
    redirect('finance', 'fm-assign-fees');
}

$student = db_fetch_one("SELECT * FROM students WHERE id = ? AND deleted_at IS NULL", [$studentId]);
if (!$student) {
    set_flash('error', 'Student not found.');
    redirect('finance', 'fm-assign-fees');
}

// Check not already exempted
if (db_exists('fee_exemptions', 'fee_id = ? AND student_id = ?', [$feeId, $studentId])) {
    set_flash('error', 'Student is already exempted from this fee.');
    redirect('finance', 'fm-assign-fees');
}

try {
    db_begin();

    db_insert('fee_exemptions', [
        'fee_id'     => $feeId,
        'student_id' => $studentId,
        'reason'     => $reason ?: null,
        'created_by' => auth_user_id(),
    ]);

    // Waive pending charges
    db_query(
        "UPDATE student_fee_charges SET status = 'waived', waived_reason = ? WHERE fee_id = ? AND student_id = ? AND status IN ('pending','overdue')",
        ['Fee exemption: ' . ($reason ?: 'No reason given'), $feeId, $studentId]
    );

    db_insert('finance_audit_log', [
        'user_id'     => auth_user_id(),
        'action'      => 'exemption_created',
        'entity_type' => 'fee_exemption',
        'entity_id'   => $feeId,
        'details'     => json_encode(['student_id' => $studentId, 'reason' => $reason]),
        'ip_address'  => get_client_ip(),
    ]);

    db_commit();
    set_flash('success', 'Exemption added successfully.');
} catch (Throwable $e) {
    db_rollback();
    error_log('Exemption save error: ' . $e->getMessage());
    set_flash('error', 'Failed to add exemption.');
}

redirect('finance', 'fm-assign-fees');
