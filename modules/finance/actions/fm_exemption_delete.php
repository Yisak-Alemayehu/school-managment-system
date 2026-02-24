<?php
/**
 * Fee Management — Delete Exemption
 */

if (!is_post()) { redirect('finance', 'fm-assign-fees'); }
verify_csrf();

$id = input_int('id');
if (!$id) {
    set_flash('error', 'Invalid exemption ID.');
    redirect('finance', 'fm-assign-fees');
}

$exemption = db_fetch_one("SELECT * FROM fee_exemptions WHERE id = ?", [$id]);
if (!$exemption) {
    set_flash('error', 'Exemption not found.');
    redirect('finance', 'fm-assign-fees');
}

try {
    db_begin();

    db_delete('fee_exemptions', 'id = ?', [$id]);

    db_insert('finance_audit_log', [
        'user_id'     => auth_user_id(),
        'action'      => 'exemption_deleted',
        'entity_type' => 'fee_exemption',
        'entity_id'   => $id,
        'details'     => json_encode($exemption),
        'ip_address'  => get_client_ip(),
    ]);

    db_commit();
    set_flash('success', 'Exemption removed.');
} catch (Throwable $e) {
    db_rollback();
    error_log('Exemption delete error: ' . $e->getMessage());
    set_flash('error', 'Failed to remove exemption.');
}

redirect('finance', 'fm-assign-fees');
