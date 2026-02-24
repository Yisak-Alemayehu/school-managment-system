<?php
/**
 * Fee Management — Soft Delete Fee
 */

if (!is_post()) { redirect('finance', 'fm-manage-fees'); }
verify_csrf();

$id = input_int('id');
if (!$id) {
    set_flash('error', 'Invalid fee ID.');
    redirect('finance', 'fm-manage-fees');
}

$fee = db_fetch_one("SELECT * FROM fees WHERE id = ? AND deleted_at IS NULL", [$id]);
if (!$fee) {
    set_flash('error', 'Fee not found.');
    redirect('finance', 'fm-manage-fees');
}

try {
    db_begin();

    // Soft delete
    db_soft_delete('fees', 'id = ?', [$id]);

    // Cancel all pending charges
    db_query("UPDATE student_fee_charges SET status = 'cancelled' WHERE fee_id = ? AND status IN ('pending','overdue')", [$id]);

    db_insert('finance_audit_log', [
        'user_id'     => auth_user_id(),
        'action'      => 'fee_deleted',
        'entity_type' => 'fee',
        'entity_id'   => $id,
        'details'     => json_encode(['description' => $fee['description']]),
        'ip_address'  => get_client_ip(),
    ]);

    db_commit();
    set_flash('success', 'Fee archived successfully.');
} catch (Throwable $e) {
    db_rollback();
    error_log('Fee delete error: ' . $e->getMessage());
    set_flash('error', 'Failed to delete fee.');
}

redirect('finance', 'fm-manage-fees');
