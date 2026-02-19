<?php
/**
 * Finance — Generate Invoice(s)
 * Creates invoices from fee structures for a class or single student
 */
verify_csrf();

$classId   = input_int('class_id');
$scope     = $_POST['scope'] ?? 'class';
$studentId = input_int('student_id');
$dueDate   = $_POST['due_date'] ?? null;
$termId    = input_int('term_id');
$notes     = trim($_POST['notes'] ?? '');
$sessionId = get_active_session_id();

if (!$classId) {
    set_flash('error', 'Please select a class.');
    redirect(url('finance', 'invoice-create'));
}

// Get fee structures for the class
$feeStructures = db_fetch_all("
    SELECT fs.*, fc.name AS category_name
    FROM fee_structures fs
    JOIN fee_categories fc ON fc.id = fs.fee_category_id
    WHERE fs.class_id = ? AND fs.session_id = ?
", [$classId, $sessionId]);

if (empty($feeStructures)) {
    set_flash('error', 'No fee structures defined for this class. Please create fee structures first.');
    redirect(url('finance', 'invoice-create'));
}

// Get students
if ($scope === 'student' && $studentId) {
    $students = db_fetch_all("
        SELECT s.id, s.first_name, s.last_name, s.admission_no
        FROM students s
        JOIN enrollments en ON en.student_id = s.id
        WHERE en.class_id = ? AND en.session_id = ? AND en.status = 'active' AND s.id = ?
    ", [$classId, $sessionId, $studentId]);
} else {
    $students = db_fetch_all("
        SELECT s.id, s.first_name, s.last_name, s.admission_no
        FROM students s
        JOIN enrollments en ON en.student_id = s.id
        WHERE en.class_id = ? AND en.session_id = ? AND en.status = 'active'
    ", [$classId, $sessionId]);
}

if (empty($students)) {
    set_flash('error', 'No active students found in this class.');
    redirect(url('finance', 'invoice-create'));
}

$pdo = db_connection();
$pdo->beginTransaction();

try {
    $generated = 0;
    $skipped   = 0;

    foreach ($students as $student) {
        // Check if student already has an unpaid/partial invoice for this term
        $existing = db_fetch_one("
            SELECT id FROM invoices
            WHERE student_id = ? AND class_id = ? AND session_id = ? AND term_id = ? AND status != 'cancelled'
        ", [$student['id'], $classId, $sessionId, $termId]);

        if ($existing) {
            $skipped++;
            continue;
        }

        // Calculate total amount
        $totalAmount = 0;
        foreach ($feeStructures as $fs) {
            $totalAmount += (float)$fs['amount'];
        }

        // Check for student-specific discounts
        $discount = db_fetch_one("
            SELECT fd.* FROM fee_discounts fd
            WHERE fd.student_id = ? AND fd.session_id = ? AND fd.is_active = 1
        ", [$student['id'], $sessionId]);

        $discountAmount = 0;
        if ($discount) {
            if ($discount['discount_type'] === 'percentage') {
                $discountAmount = $totalAmount * ($discount['amount'] / 100);
            } else {
                $discountAmount = (float)$discount['amount'];
            }
        }

        $netAmount = max(0, $totalAmount - $discountAmount);

        // Generate invoice number: INV-YYYYMMDD-XXXXX
        $invoiceNo = 'INV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

        $invoiceId = db_insert('invoices', [
            'invoice_no'      => $invoiceNo,
            'student_id'      => $student['id'],
            'class_id'        => $classId,
            'session_id'      => $sessionId,
            'term_id'         => $termId,
            'total_amount'    => $netAmount,
            'paid_amount'     => 0,
            'discount_amount' => $discountAmount,
            'due_date'        => $dueDate,
            'status'          => 'unpaid',
            'notes'           => $notes ?: null,
        ]);

        // Create invoice items
        foreach ($feeStructures as $fs) {
            db_insert('invoice_items', [
                'invoice_id'       => $invoiceId,
                'fee_structure_id' => $fs['id'],
                'fee_category_id'  => $fs['fee_category_id'],
                'description'      => $fs['category_name'],
                'amount'           => $fs['amount'],
            ]);
        }

        $generated++;
    }

    $pdo->commit();

    $msg = "{$generated} invoice(s) generated successfully.";
    if ($skipped > 0) {
        $msg .= " {$skipped} skipped (already invoiced this term).";
    }
    audit_log('invoice_generate', 'invoices', null, "class_id={$classId}, generated={$generated}, skipped={$skipped}");
    set_flash('success', $msg);

} catch (\Exception $ex) {
    $pdo->rollBack();
    set_flash('error', 'Failed to generate invoices: ' . $ex->getMessage());
}

redirect(url('finance', 'invoices') . "&class_id={$classId}");
