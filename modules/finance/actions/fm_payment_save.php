<?php
/**
 * Fee Management — Process Payment & Auto-Generate Invoice
 *
 * Flow:
 *  1. Validate input (student, charges, amount, method)
 *  2. Begin transaction
 *  3. Create invoice from selected charges
 *  4. Create payment record
 *  5. Distribute payment across charges (oldest first)
 *  6. Update student_fee_charges status (paid/partial)
 *  7. Update penalty charges if selected
 *  8. Commit → redirect to generated invoice (print view)
 */

if (!is_post()) { redirect('finance', 'fm-payment'); }
verify_csrf();

// ── Gather Input ──
$studentId  = input_int('student_id');
$chargeIds  = array_map('intval', $_POST['charge_ids'] ?? []);
$penaltyIds = array_map('intval', $_POST['penalty_ids'] ?? []);
$amount     = round((float)($_POST['amount'] ?? 0), 2);
$payDate    = trim($_POST['payment_date'] ?? date('Y-m-d'));
$method     = trim($_POST['method'] ?? 'cash');
$reference  = trim($_POST['reference'] ?? '');
$notes      = trim($_POST['notes'] ?? '');

// ── Basic Validation ──
$errors = [];
if (!$studentId) $errors[] = 'Student ID is required.';
if (empty($chargeIds) && empty($penaltyIds)) $errors[] = 'Please select at least one charge.';
if ($amount <= 0) $errors[] = 'Payment amount must be greater than zero.';
if (!in_array($method, ['cash', 'bank_transfer', 'cheque', 'gateway', 'other'])) $errors[] = 'Invalid payment method.';
if (!strtotime($payDate)) $errors[] = 'Invalid payment date.';

if ($errors) {
    set_flash('error', implode(' ', $errors));
    header('Location: ' . url('finance', 'fm-payment') . '&student_id=' . $studentId, true, 302);
    exit;
}

// ── Validate student exists + active enrollment ──
$sessionId = get_active_session_id();
$student = db_fetch_one("
    SELECT s.id, s.first_name, s.last_name, s.admission_no,
           e.class_id, e.session_id, c.name AS class_name
    FROM students s
    JOIN enrollments e ON e.student_id = s.id AND e.session_id = ? AND e.status = 'active'
    JOIN classes c ON c.id = e.class_id
    WHERE s.id = ?
", [$sessionId, $studentId]);

if (!$student) {
    set_flash('error', 'Student not found or not enrolled.');
    redirect('finance', 'fm-payment');
}

// ── Fetch selected charges (validate ownership and status) ──
$charges = [];
$totalChargeBalance = 0;

if (!empty($chargeIds)) {
    $placeholders = implode(',', array_fill(0, count($chargeIds), '?'));
    $charges = db_fetch_all("
        SELECT sfc.*, f.description AS fee_description
        FROM student_fee_charges sfc
        JOIN fees f ON f.id = sfc.fee_id
        WHERE sfc.id IN ({$placeholders})
          AND sfc.student_id = ?
          AND sfc.status IN ('pending', 'overdue')
        ORDER BY sfc.due_date ASC, sfc.id ASC
    ", array_merge($chargeIds, [$studentId]));

    foreach ($charges as $ch) {
        $totalChargeBalance += ($ch['amount'] - $ch['paid_amount']);
    }
}

// ── Fetch selected penalties ──
$penalties = [];
$totalPenaltyBalance = 0;

if (!empty($penaltyIds)) {
    $placeholders = implode(',', array_fill(0, count($penaltyIds), '?'));
    $penalties = db_fetch_all("
        SELECT pc.*, f.description AS fee_description
        FROM penalty_charges pc
        JOIN student_fee_charges sfc ON sfc.id = pc.charge_id
        JOIN fees f ON f.id = sfc.fee_id
        WHERE pc.id IN ({$placeholders})
          AND sfc.student_id = ?
          AND pc.status = 'pending'
        ORDER BY pc.applied_at ASC
    ", array_merge($penaltyIds, [$studentId]));

    foreach ($penalties as $p) {
        $totalPenaltyBalance += $p['penalty_amount'];
    }
}

$totalSelected = $totalChargeBalance + $totalPenaltyBalance;

if (empty($charges) && empty($penalties)) {
    set_flash('error', 'No valid outstanding charges selected.');
    header('Location: ' . url('finance', 'fm-payment') . '&student_id=' . $studentId, true, 302);
    exit;
}

// ── Begin Transaction ──
$pdo = db_connection();
$pdo->beginTransaction();

try {
    // ── 1. Generate Invoice ──
    $invoiceNo = 'INV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

    // Ensure unique invoice number
    while (db_fetch_value("SELECT COUNT(*) FROM invoices WHERE invoice_no = ?", [$invoiceNo])) {
        $invoiceNo = 'INV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    // Get current term (if any)
    $termId = db_fetch_value("SELECT id FROM terms WHERE session_id = ? AND is_active = 1 LIMIT 1", [$sessionId]);
    $termId = $termId ? (int)$termId : null;

    // Subtotal = sum of charge balances
    $subtotal = $totalChargeBalance;
    $fineAmount = $totalPenaltyBalance;
    $totalAmount = $subtotal + $fineAmount;
    $paidAmount = min($amount, $totalAmount);
    $balance = max(0, $totalAmount - $amount);
    $overpayment = max(0, $amount - $totalAmount);

    // Determine invoice status
    if ($amount >= $totalAmount) {
        $invoiceStatus = 'paid';
    } else {
        $invoiceStatus = 'partial';
    }

    $invoiceId = db_insert('invoices', [
        'invoice_no'      => $invoiceNo,
        'student_id'      => $studentId,
        'session_id'      => $sessionId,
        'term_id'         => $termId,
        'class_id'        => $student['class_id'],
        'subtotal'        => $subtotal,
        'discount_amount' => 0,
        'fine_amount'     => $fineAmount,
        'total_amount'    => $totalAmount,
        'paid_amount'     => $paidAmount,
        'balance'         => $balance,
        'status'          => $invoiceStatus,
        'due_date'        => $payDate,
        'issued_date'     => $payDate,
        'notes'           => $overpayment > 0
            ? ($notes ? $notes . ' | ' : '') . 'Overpayment of ' . number_format($overpayment, 2) . ' ETB recorded as advance credit.'
            : ($notes ?: null),
        'created_by'      => auth_user_id(),
    ]);

    // ── 2. Create invoice items from charges ──
    foreach ($charges as $ch) {
        $chargeBalance = $ch['amount'] - $ch['paid_amount'];
        db_insert('invoice_items', [
            'invoice_id'  => $invoiceId,
            'description' => $ch['fee_description'] . ($ch['occurrence_number'] > 1 ? ' (Occurrence #' . $ch['occurrence_number'] . ')' : ''),
            'amount'      => $chargeBalance,
            'quantity'    => 1,
            'total'       => $chargeBalance,
        ]);
    }

    // Insert penalty items
    foreach ($penalties as $p) {
        db_insert('invoice_items', [
            'invoice_id'  => $invoiceId,
            'description' => 'Late Penalty — ' . $p['fee_description'],
            'amount'      => $p['penalty_amount'],
            'quantity'    => 1,
            'total'       => $p['penalty_amount'],
        ]);
    }

    // ── 3. Create payment record ──
    $receiptNo = 'RCP-' . date('Ymd') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    while (db_fetch_value("SELECT COUNT(*) FROM payments WHERE receipt_no = ?", [$receiptNo])) {
        $receiptNo = 'RCP-' . date('Ymd') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    $paymentId = db_insert('payments', [
        'receipt_no'    => $receiptNo,
        'invoice_id'    => $invoiceId,
        'student_id'    => $studentId,
        'amount'        => $amount,
        'method'        => $method,
        'reference'     => $reference ?: null,
        'notes'         => $notes ?: null,
        'payment_date'  => $payDate,
        'received_by'   => auth_user_id(),
        'status'        => 'completed',
    ]);

    // ── 4. Distribute payment across charges (oldest due date first) ──
    $remaining = $amount;

    // First pay fee charges
    foreach ($charges as $ch) {
        if ($remaining <= 0) break;

        $chargeBalance = $ch['amount'] - $ch['paid_amount'];
        $applyAmount = min($remaining, $chargeBalance);
        $newPaid = $ch['paid_amount'] + $applyAmount;

        $updateData = [
            'paid_amount' => $newPaid,
        ];

        // If fully paid, mark as paid
        if ($newPaid >= $ch['amount']) {
            $updateData['status'] = 'paid';
            $updateData['paid_at'] = date('Y-m-d H:i:s');
        }

        db_update('student_fee_charges', $updateData, 'id = ?', [$ch['id']]);
        $remaining -= $applyAmount;
    }

    // Then pay penalties
    foreach ($penalties as $p) {
        if ($remaining <= 0) break;

        if ($remaining >= $p['penalty_amount']) {
            db_update('penalty_charges', ['status' => 'paid'], 'id = ?', [$p['id']]);
            $remaining -= $p['penalty_amount'];
        }
        // If partial penalty payment, leave as pending (penalties are all-or-nothing)
    }

    // ── 5. Audit log ──
    db_insert('finance_audit_log', [
        'user_id'     => auth_user_id(),
        'action'      => 'payment_recorded',
        'entity_type' => 'payment',
        'entity_id'   => $paymentId,
        'details'     => json_encode([
            'invoice_id'     => $invoiceId,
            'invoice_no'     => $invoiceNo,
            'receipt_no'     => $receiptNo,
            'student_id'     => $studentId,
            'amount'         => $amount,
            'method'         => $method,
            'charges_paid'   => count($charges),
            'penalties_paid' => count($penalties),
            'overpayment'    => $overpayment,
        ]),
        'ip_address'  => get_client_ip(),
    ]);

    // ── Commit ──
    $pdo->commit();

    // Build summary message
    $msg = "Payment of " . format_currency($amount) . " recorded successfully.";
    $msg .= " Invoice: {$invoiceNo} | Receipt: {$receiptNo}";
    if ($overpayment > 0) {
        $msg .= " | Advance credit: " . format_currency($overpayment);
    }
    set_flash('success', $msg);

    // Redirect to the professional invoice print page
    header('Location: ' . url('finance', 'fm-generate-invoice') . '&id=' . $invoiceId, true, 302);
    exit;

} catch (\Exception $ex) {
    $pdo->rollBack();
    set_flash('error', 'Payment failed: ' . $ex->getMessage());
    header('Location: ' . url('finance', 'fm-payment') . '&student_id=' . $studentId, true, 302);
    exit;
}
