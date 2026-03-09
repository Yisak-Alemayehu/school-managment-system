<?php
/**
 * Finance — Payment Attachment Download / Print Action
 * Generates A5 PDF attachment for a specific transaction.
 * Tracks print_count to show "Copy" on subsequent prints.
 */

$txId = route_id() ?: input_int('id');
if (!$txId) {
    set_flash('error', 'Invalid transaction.');
    redirect(url('finance', 'collect-payment'));
}

// Fetch transaction with related data
$tx = db_fetch_one(
    "SELECT t.*,
            s.full_name AS student_name, s.admission_no,
            c.name AS class_name,
            f.description AS fee_description,
            u.full_name AS processed_by_name
       FROM fin_transactions t
       JOIN students s ON t.student_id = s.id
       LEFT JOIN fin_student_fees sf ON t.student_fee_id = sf.id
       LEFT JOIN fin_fees f ON sf.fee_id = f.id
       LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
       LEFT JOIN classes c ON e.class_id = c.id
       LEFT JOIN users u ON t.processed_by = u.id
      WHERE t.id = ? AND t.type = 'payment'",
    [$txId]
);

if (!$tx) {
    set_flash('error', 'Payment transaction not found.');
    redirect(url('finance', 'collect-payment'));
}

// Determine if this is a copy (print_count > 0 means it has been printed before)
$isCopy = ((int)($tx['print_count'] ?? 0)) > 0;

// Increment print count
db_update('fin_transactions', [
    'print_count' => ((int)($tx['print_count'] ?? 0)) + 1,
], 'id = ?', [$txId]);

// Load PDF constants (SCHOOL_NAME etc.) if not already defined
if (!defined('SCHOOL_NAME')) {
    define('SCHOOL_NAME',      'Urji Beri School');
    define('SCHOOL_TELEPHONE', '0912097003');
}

// Generate PDF
require_once APP_ROOT . '/core/pdf_payment_attachment.php';

$pdf = new PaymentAttachmentPDF($tx, $isCopy);
$pdf->generate();
exit;
