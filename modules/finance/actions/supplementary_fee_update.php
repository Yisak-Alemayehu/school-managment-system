<?php
/**
 * Finance — Update Supplementary Fee
 */
csrf_protect();

$supplementaryFeeId = input_int('supplementary_fee_id');
if (!$supplementaryFeeId) {
    set_flash('error', 'Invalid supplementary fee.');
    redirect(url('finance', 'supplementary-fees'));
}

$existing = db_fetch_one("SELECT * FROM fin_supplementary_fees WHERE id = ?", [$supplementaryFeeId]);
if (!$existing) {
    set_flash('error', 'Supplementary fee not found.');
    redirect(url('finance', 'supplementary-fees'));
}

$description = input('description');
$amount      = (float) input('amount');
$currency    = input('currency') ?: 'ETB';
$isActive    = isset($_POST['is_active']) ? 1 : 0;

if (!$description || $amount <= 0) {
    set_flash('error', 'Please fill all required fields.');
    redirect(url('finance', 'supplementary-fees'));
}

try {
    db_update('fin_supplementary_fees', [
        'description' => $description,
        'amount'      => $amount,
        'currency'    => $currency,
        'is_active'   => $isActive,
    ], 'id = ?', [$supplementaryFeeId]);

    set_flash('success', 'Supplementary fee updated successfully.');
} catch (Throwable $e) {
    error_log('Supplementary fee update error: ' . $e->getMessage());
    set_flash('error', 'Failed to update supplementary fee.');
}

redirect(url('finance', 'supplementary-fees'));
