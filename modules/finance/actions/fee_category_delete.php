<?php
/**
 * Finance — Delete Fee Category
 */
verify_csrf_get();

$id = input_int('id');
if (!$id) {
    set_flash('error', 'Invalid category.');
    redirect(url('finance', 'fee-categories'));
}

// Check if fee structures use this category
$inUse = db_fetch_one("SELECT COUNT(*) AS cnt FROM fee_structures WHERE fee_category_id = ?", [$id]);
if ($inUse['cnt'] > 0) {
    set_flash('error', 'Cannot delete — category has fee structures assigned.');
    redirect(url('finance', 'fee-categories'));
}

db_delete('fee_categories', 'id = ?', [$id]);
audit_log('fee_category_delete', 'fee_categories', $id);
set_flash('success', 'Category deleted.');
redirect(url('finance', 'fee-categories'));
