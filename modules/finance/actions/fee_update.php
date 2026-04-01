<?php
/**
 * Finance — Update Existing Fee
 */
csrf_protect();

$feeId = input_int('fee_id');
if (!$feeId) {
    set_flash('error', 'Invalid fee.');
    redirect(url('finance', 'fee-due'));
}

$existing = db_fetch_one("SELECT * FROM fin_fees WHERE id = ?", [$feeId]);
if (!$existing) {
    set_flash('error', 'Fee not found.');
    redirect(url('finance', 'fee-due'));
}

$data = [
    'description'          => input('description'),
    'amount'               => (float) input('amount'),
    'currency'             => input('currency') ?: 'ETB',
    'foreign_amount'       => input('currency') !== 'ETB' ? (float) input('foreign_amount') : null,
    'fee_type'             => input_int('fee_type'),
    'effective_date'       => input('effective_date'),
    'end_date'             => input('end_date'),
    'apply_every'          => input_int('fee_type') ? (input_int('apply_every') ?: 1) : null,
    'frequency'            => input_int('fee_type') ? (input('frequency') ?: 'months') : null,
    'has_penalty'          => isset($_POST['has_penalty']) ? 1 : 0,
    'is_credit_hour'       => isset($_POST['is_credit_hour']) ? 1 : 0,
];

if (!$data['description'] || $data['amount'] <= 0 || !$data['effective_date'] || !$data['end_date']) {
    set_flash('error', 'Please fill all required fields.');
    redirect(url('finance', 'fee-due'));
}

// Penalty fields
if ($data['has_penalty']) {
    $data['penalty_unpaid_after'] = input_int('penalty_unpaid_after') ?: 1;
    $data['penalty_unpaid_unit']  = input('penalty_unpaid_unit') ?: 'months';
    $data['penalty_type']         = input('penalty_type') ?: 'fixed_amount';
    $data['penalty_frequency']    = input('penalty_frequency') ?: 'one_time';
    $data['penalty_expiry_date']  = input('penalty_expiry_date') ?: null;
    $data['max_penalty_amount']   = (float) (input('max_penalty_amount') ?? 1000);
    $data['max_penalty_count']    = input_int('max_penalty_count');

    if (in_array($data['penalty_type'], ['fixed_amount', 'fixed_percentage'])) {
        $data['penalty_value'] = (float) input('penalty_value');
    }

    if ($data['penalty_frequency'] === 'recurrent') {
        $data['penalty_reapply_every'] = input_int('penalty_reapply_every') ?: 1;
        $data['penalty_reapply_unit']  = input('penalty_reapply_unit') ?: 'months';
    }
}

$classIds = input_array('class_ids');

db_begin();
try {
    db_update('fin_fees', $data, 'id = ?', [$feeId]);

    // Sync class assignments (delete old, insert new) — does NOT touch fin_student_fees
    db_fetch_one("DELETE FROM fin_fee_classes WHERE fee_id = ?", [$feeId]);
    if (!empty($classIds)) {
        foreach ($classIds as $cid) {
            $cid = (int) $cid;
            if ($cid > 0) {
                db_insert('fin_fee_classes', ['fee_id' => $feeId, 'class_id' => $cid]);
            }
        }
    }

    // Sync varying penalties if applicable
    if ($data['has_penalty'] && in_array($data['penalty_type'] ?? '', ['varying_amount', 'varying_percentage'])) {
        db_fetch_one("DELETE FROM fin_varying_penalties WHERE fee_id = ?", [$feeId]);
        $varyingValues = input_array('varying_values');
        if (!empty($varyingValues)) {
            foreach ($varyingValues as $i => $val) {
                $val = (float) $val;
                if ($val > 0) {
                    db_insert('fin_varying_penalties', [
                        'fee_id'     => $feeId,
                        'sort_order' => $i + 1,
                        'value'      => $val,
                    ]);
                }
            }
        }
    }

    db_commit();
    set_flash('success', 'Fee updated successfully.');
} catch (Throwable $e) {
    db_rollback();
    error_log('Fee update error: ' . $e->getMessage());
    set_flash('error', 'Failed to update fee.');
}

redirect(url('finance', 'fee-due'));
