<?php
/**
 * Finance — Toggle Supplementary Fee Active/Inactive
 */
csrf_protect();

$supplementaryFeeId = input_int('supplementary_fee_id');
if (!$supplementaryFeeId) {
    set_flash('error', 'Invalid supplementary fee.');
    redirect(url('finance', 'supplementary-fees'));
}

$sfee = db_fetch_one("SELECT id, is_active FROM fin_supplementary_fees WHERE id = ?", [$supplementaryFeeId]);
if (!$sfee) {
    set_flash('error', 'Supplementary fee not found.');
    redirect(url('finance', 'supplementary-fees'));
}

$newStatus = $sfee['is_active'] ? 0 : 1;

try {
    db_update('fin_supplementary_fees', ['is_active' => $newStatus], 'id = ?', [$supplementaryFeeId]);
    set_flash('success', 'Supplementary fee ' . ($newStatus ? 'activated' : 'deactivated') . ' successfully.');
} catch (Throwable $e) {
    error_log('Supplementary fee toggle error: ' . $e->getMessage());
    set_flash('error', 'Failed to toggle supplementary fee status.');
}

redirect(url('finance', 'supplementary-fees'));
