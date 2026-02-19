<?php
/**
 * Finance — Delete Discount
 */
verify_csrf_get();

$id = input_int('id');
if (!$id) {
    set_flash('error', 'Invalid discount.');
    redirect(url('finance', 'discounts'));
}

db_delete('fee_discounts', 'id = ?', [$id]);
audit_log('discount_delete', 'fee_discounts', $id);
set_flash('success', 'Discount deleted.');
redirect(url('finance', 'discounts'));
