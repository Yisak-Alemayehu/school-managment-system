<?php
/**
 * Finance — Collect Payment Save Action (Multi-Fee Batch)
 * Processes multiple fee payments in a single batch submission.
 * TeleBirr payments are auto-marked as paid.
 * Other methods require manual confirmation checkbox.
 */
csrf_protect();

$studentId    = input_int('student_id');
$channel      = input('channel');
$notes        = input('notes');
$confirmPaid  = input_int('confirm_paid');
$feesInput    = input_array('fees');
$adjustedTotal = input('adjusted_total');

// Validate required fields
if (!$studentId || !$channel || empty($feesInput)) {
    set_flash('error', 'Please fill in all required fields with valid values.');
    redirect(url('finance', 'collect-payment') . '&student_id=' . $studentId);
}

// Filter to only selected fees with amount > 0
$selectedFees = [];
foreach ($feesInput as $sfId => $data) {
    if (!empty($data['selected']) && $data['selected'] == '1') {
        $amt = (float)($data['amount'] ?? 0);
        if ($amt > 0) {
            $selectedFees[(int)$sfId] = $amt;
        }
    }
}

if (empty($selectedFees)) {
    set_flash('error', 'Please select at least one fee to pay.');
    redirect(url('finance', 'collect-payment') . '&student_id=' . $studentId);
}

// TeleBirr is auto-confirmed; others require manual confirmation
$isTeleBirr = ($channel === 'telebirr');
if (!$isTeleBirr && !$confirmPaid) {
    set_flash('error', 'Please confirm that the payment has been received.');
    redirect(url('finance', 'collect-payment') . '&student_id=' . $studentId);
}

// Gather channel-specific details
$channelTxId       = null;
$channelPayType    = null;
$channelDepositor  = null;
$channelBranch     = null;
$payerPhone        = null;
$reference         = input('reference');

if ($isTeleBirr) {
    $channelTxId = input('channel_transaction_id');
    $payerPhone  = input('payer_phone');
    if (!$channelTxId) {
        set_flash('error', 'TeleBirr Transaction ID is required.');
        redirect(url('finance', 'collect-payment') . '&student_id=' . $studentId);
    }
} elseif (in_array($channel, ['bank_transfer', 'bank_deposit'])) {
    $channelPayType   = input('channel_payment_type');
    $channelDepositor = input('channel_depositor_name');
    $channelBranch    = input('channel_depositor_branch');
    $channelTxId      = input('bank_transaction_id');
}

$user = auth_user();

// Handle total adjustment — proportionally scale per-fee amounts
$adjustmentExtra = 0;
if ($adjustedTotal !== null && $adjustedTotal !== '') {
    $adjVal = (float)$adjustedTotal;
    if ($adjVal <= 0) {
        set_flash('error', 'Adjusted total must be greater than zero.');
        redirect(url('finance', 'collect-payment') . '&student_id=' . $studentId);
    }
    $feeSum = array_sum($selectedFees);
    if ($feeSum > 0 && abs($adjVal - $feeSum) > 0.005) {
        if ($adjVal < $feeSum) {
            // Proportionally reduce each fee amount
            $ratio = $adjVal / $feeSum;
            foreach ($selectedFees as $sfId => $amt) {
                $selectedFees[$sfId] = round($amt * $ratio, 2);
            }
            // Fix rounding: adjust the last fee to hit the exact total
            $newSum = array_sum($selectedFees);
            $rounding = round($adjVal - $newSum, 2);
            if (abs($rounding) > 0) {
                $lastKey = array_key_last($selectedFees);
                $selectedFees[$lastKey] += $rounding;
            }
        } else {
            // Extra amount goes to wallet credit
            $adjustmentExtra = round($adjVal - $feeSum, 2);
        }
    }
}

// Generate batch receipt number
$batchReceiptNo = 'BRCP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

// Load student info for receipt
$student = db_fetch_one(
    "SELECT s.full_name, s.admission_no, c.name AS class_name
       FROM students s
       LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
       LEFT JOIN classes c ON e.class_id = c.id
      WHERE s.id = ? AND s.deleted_at IS NULL",
    [$studentId]
);
if (!$student) {
    set_flash('error', 'Student not found.');
    redirect(url('finance', 'collect-payment'));
}

db_begin();
try {
    $processedFees = [];
    $totalPaid     = 0;

    foreach ($selectedFees as $sfId => $amount) {
        // Load and verify fee record
        $sf = db_fetch_one(
            "SELECT * FROM fin_student_fees WHERE id = ? AND student_id = ? AND is_active = 1 AND balance > 0",
            [$sfId, $studentId]
        );
        if (!$sf) {
            continue; // skip invalid/already-paid fees
        }

        $fee = db_fetch_one("SELECT description FROM fin_fees WHERE id = ?", [$sf['fee_id']]);
        $feeDesc = $fee['description'] ?? 'Fee';

        // Cap amount to balance; credit overpayment to wallet
        $overpayment = 0;
        if ($amount > $sf['balance']) {
            $overpayment = $amount - $sf['balance'];
            $amount = (float) $sf['balance'];
        }

        $newBalance = $sf['balance'] - $amount;

        // Generate individual receipt number
        $receiptNo = 'RCP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        // Update student fee balance
        db_update('fin_student_fees', [
            'balance' => $newBalance,
        ], 'id = ?', [$sfId]);

        // Insert transaction record
        db_insert('fin_transactions', [
            'student_id'              => $studentId,
            'student_fee_id'          => $sfId,
            'type'                    => 'payment',
            'amount'                  => -$amount,
            'currency'                => $sf['currency'],
            'balance_before'          => $sf['balance'],
            'balance_after'           => $newBalance,
            'description'             => 'Payment for: ' . $feeDesc,
            'channel'                 => $channel,
            'channel_payment_type'    => $channelPayType,
            'channel_depositor_name'  => $channelDepositor,
            'channel_depositor_branch'=> $channelBranch,
            'channel_transaction_id'  => $channelTxId,
            'payer_phone'             => $payerPhone,
            'receipt_no'              => $receiptNo,
            'batch_receipt_no'        => $batchReceiptNo,
            'reference'               => $reference ?: null,
            'notes'                   => $notes ?: null,
            'print_count'             => 0,
            'processed_by'            => $user['id'],
        ]);

        // Credit overpayment to student wallet if any
        if ($overpayment > 0) {
            db_insert('fin_transactions', [
                'student_id'    => $studentId,
                'student_fee_id'=> $sfId,
                'type'          => 'adjustment',
                'amount'        => $overpayment,
                'currency'      => $sf['currency'],
                'description'   => 'Overpayment credit for: ' . $feeDesc,
                'channel'       => $channel,
                'receipt_no'    => null,
                'batch_receipt_no' => $batchReceiptNo,
                'processed_by'  => $user['id'],
            ]);
        }

        $processedFees[] = [
            'fee_desc'   => $feeDesc,
            'amount'     => $amount,
            'receipt_no' => $receiptNo,
        ];
        $totalPaid += $amount;
    }

    if (empty($processedFees)) {
        db_rollback();
        set_flash('error', 'No valid fees could be processed.');
        redirect(url('finance', 'collect-payment') . '&student_id=' . $studentId);
    }

    // Credit adjustment extra to student wallet (when cashier increased total)
    if ($adjustmentExtra > 0) {
        $currency = 'ETB';
        // Use currency from last processed fee if available
        foreach ($selectedFees as $sfId => $amt) {
            $sfRow = db_fetch_one("SELECT currency FROM fin_student_fees WHERE id = ?", [$sfId]);
            if ($sfRow) { $currency = $sfRow['currency']; break; }
        }
        db_insert('fin_transactions', [
            'student_id'       => $studentId,
            'student_fee_id'   => null,
            'type'             => 'adjustment',
            'amount'           => $adjustmentExtra,
            'currency'         => $currency,
            'description'      => 'Extra payment credit (total adjustment)',
            'channel'          => $channel,
            'receipt_no'       => null,
            'batch_receipt_no' => $batchReceiptNo,
            'processed_by'     => $user['id'],
        ]);
        $totalPaid += $adjustmentExtra;
    }

    db_commit();

    // Store batch receipt number in session for initial redirect
    $_SESSION['_batch_receipt'] = [
        'batch_receipt_no' => $batchReceiptNo,
    ];

    $count = count($processedFees);
    set_flash('success', "Payment recorded for {$count} fee(s). Batch Receipt: {$batchReceiptNo}");
    redirect(url('finance', 'collect-payment-batch-receipt') . '&batch=' . urlencode($batchReceiptNo));

} catch (Throwable $e) {
    db_rollback();
    error_log('Collect payment error: ' . $e->getMessage());
    set_flash('error', 'Failed to record payment. Please try again.');
    redirect(url('finance', 'collect-payment') . '&student_id=' . $studentId);
}
