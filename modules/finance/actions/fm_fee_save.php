<?php
/**
 * Fee Management — Save Fee (Create / Update)
 * Handles fee creation with optional recurrence and penalty configs
 */

if (!is_post()) {
    redirect('finance', 'fm-manage-fees');
}

verify_csrf();

$id = input_int('id');
$isEdit = (bool) $id;

// ── Validate ─────────────────────────────────────────────────
$errors = validate($_POST, [
    'description'    => 'required|max:500',
    'amount'         => 'required|numeric',
    'fee_type'       => 'required|in:one_time,recurrent',
    'effective_date' => 'required|date',
    'end_date'       => 'required|date',
]);

$amount = (float) ($_POST['amount'] ?? 0);
if ($amount <= 0) {
    $errors['amount'] = 'Amount must be greater than zero.';
}

$effectiveDate = $_POST['effective_date'] ?? '';
$endDate       = $_POST['end_date'] ?? '';
if ($effectiveDate && $endDate && $endDate <= $effectiveDate) {
    $errors['end_date'] = 'End Date must be after Effective Date.';
}

$feeType  = $_POST['fee_type'] ?? 'one_time';
$currency = $_POST['currency'] ?? 'ETB';
$status   = $_POST['save_action'] === 'activate' ? 'active' : 'draft';

// Recurrence validation (only if recurrent)
if ($feeType === 'recurrent') {
    $freqNum = input_int('frequency_number');
    if ($freqNum < 1) {
        $errors['frequency_number'] = 'Frequency number must be at least 1.';
    }
    $freqUnit = $_POST['frequency_unit'] ?? 'months';
    if (!in_array($freqUnit, ['days', 'weeks', 'months', 'years'])) {
        $errors['frequency_unit'] = 'Invalid frequency unit.';
    }
    $maxRecurrences = max(0, input_int('max_recurrences'));
}

// Penalty validation (only if enabled)
$penaltyEnabled = !empty($_POST['penalty_enabled']);
if ($penaltyEnabled) {
    $maxPenaltyAmount = (float) ($_POST['max_penalty_amount'] ?? 0);
    if ($maxPenaltyAmount <= 0) {
        $errors['max_penalty_amount'] = 'Maximum Penalty Amount must be greater than zero.';
    }
    $penaltyType = $_POST['penalty_type'] ?? 'fixed';
    if (!in_array($penaltyType, ['fixed', 'percentage'])) {
        $errors['penalty_type'] = 'Invalid penalty type.';
    }
    $penaltyAmount = (float) ($_POST['penalty_amount'] ?? 0);
    if ($penaltyAmount <= 0) {
        $errors['penalty_amount'] = 'Penalty amount/percentage must be greater than zero.';
    }
}

if (!empty($errors)) {
    set_validation_errors($errors);
    set_old_input();
    set_flash('error', 'Please fix the errors below.');
    if ($isEdit) {
        redirect('finance', 'fm-edit-fee', $id);
    } else {
        redirect('finance', 'fm-create-fee');
    }
}

// ── Save ──────────────────────────────────────────────────────
try {
    db_begin();

    $feeData = [
        'fee_type'       => $feeType,
        'currency'       => $currency,
        'description'    => trim($_POST['description']),
        'amount'         => $amount,
        'effective_date' => $effectiveDate,
        'end_date'       => $endDate,
        'status'         => $status,
    ];

    if ($isEdit) {
        // Check existing
        $existing = db_fetch_one("SELECT * FROM fees WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$existing) {
            set_flash('error', 'Fee not found.');
            redirect('finance', 'fm-manage-fees');
        }
        db_update('fees', $feeData, 'id = ?', [$id]);
        $feeId = $id;

        // Clean existing configs if type changed
        db_delete('recurrence_configs', 'fee_id = ?', [$feeId]);
        db_delete('penalty_configs', 'fee_id = ?', [$feeId]);
    } else {
        $feeData['created_by'] = auth_user_id();
        $feeId = db_insert('fees', $feeData);
    }

    // Save recurrence config
    if ($feeType === 'recurrent') {
        db_insert('recurrence_configs', [
            'fee_id'           => $feeId,
            'frequency_number' => $freqNum,
            'frequency_unit'   => $freqUnit,
            'max_recurrences'  => $maxRecurrences,
        ]);
    }

    // Save penalty config
    if ($penaltyEnabled) {
        $penaltyData = [
            'fee_id'                   => $feeId,
            'grace_period_number'      => max(0, input_int('grace_period_number')),
            'grace_period_unit'        => in_array($_POST['grace_period_unit'] ?? '', ['days', 'weeks', 'months']) ? $_POST['grace_period_unit'] : 'days',
            'penalty_type'             => $penaltyType,
            'penalty_amount'           => $penaltyAmount,
            'penalty_frequency'        => in_array($_POST['penalty_frequency'] ?? '', ['one_time', 'recurrent']) ? $_POST['penalty_frequency'] : 'one_time',
            'penalty_recurrence_unit'  => null,
            'penalty_recurrence_number'=> null,
            'max_penalty_amount'       => $maxPenaltyAmount,
            'max_penalty_applications' => max(0, input_int('max_penalty_applications')),
            'penalty_end_date'         => !empty($_POST['penalty_end_date']) ? $_POST['penalty_end_date'] : null,
        ];

        if ($penaltyData['penalty_frequency'] === 'recurrent') {
            $penaltyData['penalty_recurrence_unit']   = in_array($_POST['penalty_recurrence_unit'] ?? '', ['days', 'weeks', 'months']) ? $_POST['penalty_recurrence_unit'] : 'days';
            $penaltyData['penalty_recurrence_number'] = max(1, input_int('penalty_recurrence_number'));
        }

        db_insert('penalty_configs', $penaltyData);
    }

    // Audit log
    db_insert('finance_audit_log', [
        'user_id'     => auth_user_id(),
        'action'      => $isEdit ? 'fee_updated' : 'fee_created',
        'entity_type' => 'fee',
        'entity_id'   => $feeId,
        'details'     => json_encode(['status' => $status, 'amount' => $amount]),
        'ip_address'  => get_client_ip(),
    ]);

    // If activating fee, generate initial charges for existing assignments
    if ($status === 'active' && !$isEdit) {
        // Charges will be generated when assignments are created
    }

    db_commit();

    set_flash('success', $isEdit ? 'Fee updated successfully.' : 'Fee created successfully.');
    redirect('finance', 'fm-manage-fees');

} catch (Throwable $e) {
    db_rollback();
    error_log('Fee save error: ' . $e->getMessage());
    set_flash('error', 'An error occurred while saving the fee.');
    set_old_input();
    if ($isEdit) {
        redirect('finance', 'fm-edit-fee', $id);
    } else {
        redirect('finance', 'fm-create-fee');
    }
}
