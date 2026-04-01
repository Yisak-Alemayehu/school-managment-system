<?php
/**
 * Finance — Toggle Fee Active/Inactive
 */
csrf_protect();

$feeId = input_int('fee_id');
if (!$feeId) {
    set_flash('error', 'Invalid fee.');
    redirect(url('finance', 'fee-due'));
}

$fee = db_fetch_one("SELECT id, is_active FROM fin_fees WHERE id = ?", [$feeId]);
if (!$fee) {
    set_flash('error', 'Fee not found.');
    redirect(url('finance', 'fee-due'));
}

$newStatus = $fee['is_active'] ? 0 : 1;

try {
    db_update('fin_fees', ['is_active' => $newStatus], 'id = ?', [$feeId]);
    set_flash('success', 'Fee ' . ($newStatus ? 'activated' : 'deactivated') . ' successfully.');
} catch (Throwable $e) {
    error_log('Fee toggle error: ' . $e->getMessage());
    set_flash('error', 'Failed to toggle fee status.');
}

redirect(url('finance', 'fee-due'));
