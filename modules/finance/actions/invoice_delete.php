<?php
/**
 * Finance — Delete Invoice
 */
verify_csrf_get();

$id = input_int('id');

$invoice = db_fetch_one("SELECT * FROM invoices WHERE id = ?", [$id]);
if (!$invoice) {
    set_flash('error', 'Invoice not found.');
    redirect(url('finance', 'invoices'));
}

if ($invoice['paid_amount'] > 0) {
    set_flash('error', 'Cannot delete an invoice with recorded payments. Cancel it instead.');
    redirect(url('finance', 'invoice-view') . "&id={$id}");
}

// Delete items first, then invoice
db_delete('invoice_items', 'invoice_id = ?', [$id]);
db_delete('invoices', 'id = ?', [$id]);
audit_log('invoice_delete', 'invoices', $id, "invoice_no={$invoice['invoice_no']}");
set_flash('success', 'Invoice deleted.');
redirect(url('finance', 'invoices'));
