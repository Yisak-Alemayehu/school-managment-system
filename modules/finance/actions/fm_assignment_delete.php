<?php
/**
 * Fee Management — Delete Assignment
 */

if (!is_post()) { redirect('finance', 'fm-assign-fees'); }
verify_csrf();

$id = input_int('id');
if (!$id) {
    set_flash('error', 'Invalid assignment ID.');
    redirect('finance', 'fm-assign-fees');
}

$assignment = db_fetch_one("SELECT * FROM fee_assignments WHERE id = ?", [$id]);
if (!$assignment) {
    set_flash('error', 'Assignment not found.');
    redirect('finance', 'fm-assign-fees');
}

try {
    db_begin();

    // Cancel pending charges tied to this assignment
    db_query(
        "UPDATE student_fee_charges SET status = 'cancelled' WHERE fee_assignment_id = ? AND status IN ('pending','overdue')",
        [$id]
    );

    db_delete('fee_assignments', 'id = ?', [$id]);

    db_insert('finance_audit_log', [
        'user_id'     => auth_user_id(),
        'action'      => 'assignment_deleted',
        'entity_type' => 'fee_assignment',
        'entity_id'   => $id,
        'details'     => json_encode($assignment),
        'ip_address'  => get_client_ip(),
    ]);

    db_commit();
    set_flash('success', 'Assignment removed successfully.');
} catch (Throwable $e) {
    db_rollback();
    error_log('Assignment delete error: ' . $e->getMessage());
    set_flash('error', 'Failed to remove assignment.');
}

redirect('finance', 'fm-assign-fees', $assignment['fee_id']);
