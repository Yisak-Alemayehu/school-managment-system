<?php
/**
 * Finance — Delete Fee Structure
 */
verify_csrf_get();

$id = input_int('id');
if (!$id) {
    set_flash('error', 'Invalid fee structure.');
    redirect(url('finance', 'fee-structures'));
}

// Check if invoices reference this structure
$inUse = db_fetch_one("SELECT COUNT(*) AS cnt FROM invoice_items WHERE fee_structure_id = ?", [$id]);
if ($inUse['cnt'] > 0) {
    set_flash('error', 'Cannot delete — this fee structure has invoices generated.');
    redirect(url('finance', 'fee-structures'));
}

db_delete('fee_structures', 'id = ?', [$id]);
audit_log('fee_structure_delete', 'fee_structures', $id);
set_flash('success', 'Fee structure deleted.');
redirect(url('finance', 'fee-structures'));
