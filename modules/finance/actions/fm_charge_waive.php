<?php
/**
 * Fee Management — Waive a student fee charge
 */

if (!is_post()) { redirect('finance', 'fm-manage-fees'); }
verify_csrf();

$chargeId = input_int('charge_id');
$reason   = trim($_POST['reason'] ?? '');

if (!$chargeId) {
    set_flash('error', 'Invalid charge ID.');
    redirect('finance', 'fm-manage-fees');
}

$charge = db_fetch_one("SELECT * FROM student_fee_charges WHERE id = ?", [$chargeId]);
if (!$charge) {
    set_flash('error', 'Charge not found.');
    redirect('finance', 'fm-manage-fees');
}

if (!in_array($charge['status'], ['pending', 'overdue'])) {
    set_flash('error', 'Only pending or overdue charges can be waived.');
    redirect('finance', 'fm-fee-view', $charge['fee_id']);
}

db_update('student_fee_charges', [
    'status'        => 'waived',
    'waived_reason' => $reason ?: 'Manually waived',
], 'id = ?', [$chargeId]);

db_insert('finance_audit_log', [
    'user_id'     => auth_user_id(),
    'action'      => 'charge_waived',
    'entity_type' => 'student_fee_charge',
    'entity_id'   => $chargeId,
    'details'     => json_encode(['student_id' => $charge['student_id'], 'amount' => $charge['amount'], 'reason' => $reason]),
    'ip_address'  => get_client_ip(),
]);

set_flash('success', 'Charge waived successfully.');
redirect('finance', 'fm-fee-view', $charge['fee_id']);
