<?php
/**
 * Fee Management — Duplicate Fee
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

    // Duplicate fee as draft
    $newFeeId = db_insert('fees', [
        'fee_type'       => $fee['fee_type'],
        'currency'       => $fee['currency'],
        'description'    => $fee['description'] . ' (Copy)',
        'amount'         => $fee['amount'],
        'effective_date' => $fee['effective_date'],
        'end_date'       => $fee['end_date'],
        'status'         => 'draft',
        'created_by'     => auth_user_id(),
    ]);

    // Duplicate recurrence config
    $recurrence = db_fetch_one("SELECT * FROM recurrence_configs WHERE fee_id = ?", [$id]);
    if ($recurrence) {
        db_insert('recurrence_configs', [
            'fee_id'           => $newFeeId,
            'frequency_number' => $recurrence['frequency_number'],
            'frequency_unit'   => $recurrence['frequency_unit'],
            'max_recurrences'  => $recurrence['max_recurrences'],
        ]);
    }

    // Duplicate penalty config
    $penalty = db_fetch_one("SELECT * FROM penalty_configs WHERE fee_id = ?", [$id]);
    if ($penalty) {
        db_insert('penalty_configs', [
            'fee_id'                    => $newFeeId,
            'grace_period_number'       => $penalty['grace_period_number'],
            'grace_period_unit'         => $penalty['grace_period_unit'],
            'penalty_type'              => $penalty['penalty_type'],
            'penalty_amount'            => $penalty['penalty_amount'],
            'penalty_frequency'         => $penalty['penalty_frequency'],
            'penalty_recurrence_unit'   => $penalty['penalty_recurrence_unit'],
            'penalty_recurrence_number' => $penalty['penalty_recurrence_number'],
            'max_penalty_amount'        => $penalty['max_penalty_amount'],
            'max_penalty_applications'  => $penalty['max_penalty_applications'],
            'penalty_end_date'          => $penalty['penalty_end_date'],
        ]);
    }

    db_insert('finance_audit_log', [
        'user_id'     => auth_user_id(),
        'action'      => 'fee_duplicated',
        'entity_type' => 'fee',
        'entity_id'   => $newFeeId,
        'details'     => json_encode(['source_fee_id' => $id]),
        'ip_address'  => get_client_ip(),
    ]);

    db_commit();
    set_flash('success', 'Fee duplicated as draft. You can now edit and activate it.');
    redirect('finance', 'fm-edit-fee', $newFeeId);

} catch (Throwable $e) {
    db_rollback();
    error_log('Fee duplicate error: ' . $e->getMessage());
    set_flash('error', 'Failed to duplicate fee.');
    redirect('finance', 'fm-manage-fees');
}
