<?php
/**
 * Finance — Save Supplementary Fee
 */
csrf_protect();

$user = auth_user();

$description = input('description');
$amount      = (float) input('amount');
$currency    = input('currency') ?: 'ETB';
$isActive    = isset($_POST['is_active']) ? 1 : 0;

if (!$description || $amount <= 0) {
    set_flash('error', 'Please fill all required fields.');
    redirect(url('finance', 'supplementary-fees'));
}

try {
    db_insert('fin_supplementary_fees', [
        'description' => $description,
        'amount'      => $amount,
        'currency'    => $currency,
        'is_active'   => $isActive,
        'created_by'  => $user['id'],
    ]);
    set_flash('success', 'Supplementary fee created successfully.');
} catch (Throwable $e) {
    error_log('Supplementary fee save error: ' . $e->getMessage());
    set_flash('error', 'Failed to create supplementary fee.');
}

redirect(url('finance', 'supplementary-fees'));
