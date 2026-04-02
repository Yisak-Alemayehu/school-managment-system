<?php
/**
 * Finance — Apply Penalties
 * Finds overdue student fees and applies penalty per fee configuration.
 * Works from both web (POST) and CLI (CRON_MODE).
 */

if (!defined('CRON_MODE')) {
    csrf_protect();
}

$today = date('Y-m-d');
$now   = date('Y-m-d H:i:s');

// Step 1: Get all active fees that have penalties enabled
$fees = db_fetch_all(
    "SELECT * FROM fin_fees
      WHERE is_active = 1
        AND has_penalty = 1
        AND penalty_type IS NOT NULL
        AND penalty_unpaid_after IS NOT NULL
        AND (penalty_expiry_date IS NULL OR penalty_expiry_date >= ?)",
    [$today]
);

$applied = 0;
$skipped = 0;
$errors  = 0;

foreach ($fees as $fee) {
    // Step 2: Calculate the overdue threshold date
    $interval = (int) $fee['penalty_unpaid_after'];
    $unit     = $fee['penalty_unpaid_unit'] ?? 'days';
    $thresholdDate = date('Y-m-d', strtotime("-{$interval} {$unit}"));

    // Step 3: Find student fees that are overdue with outstanding balance
    $studentFees = db_fetch_all(
        "SELECT sf.* FROM fin_student_fees sf
          WHERE sf.fee_id = ?
            AND sf.is_active = 1
            AND sf.balance > 0
            AND sf.assigned_at <= ?",
        [$fee['id'], $thresholdDate . ' 23:59:59']
    );

    // Load varying penalty tiers if needed
    $varyingTiers = [];
    if (in_array($fee['penalty_type'], ['varying_amount', 'varying_percentage'])) {
        $varyingTiers = db_fetch_all(
            "SELECT value FROM fin_varying_penalties WHERE fee_id = ? ORDER BY sort_order ASC",
            [$fee['id']]
        );
    }

    foreach ($studentFees as $sf) {
        // Step 4: Count how many penalties already applied
        $penaltyCount = (int) db_fetch_value(
            "SELECT COALESCE(SUM(apply_count), 0) FROM fin_penalty_log WHERE student_fee_id = ?",
            [$sf['id']]
        );

        // Step 5: Check max_penalty_count (0 = unlimited)
        if ($fee['max_penalty_count'] > 0 && $penaltyCount >= $fee['max_penalty_count']) {
            $skipped++;
            continue;
        }

        // Step 6: For recurrent penalties, check if it's time to reapply
        if ($fee['penalty_frequency'] === 'recurrent' && $penaltyCount > 0) {
            $lastApplied = db_fetch_value(
                "SELECT MAX(applied_at) FROM fin_penalty_log WHERE student_fee_id = ?",
                [$sf['id']]
            );
            if ($lastApplied) {
                $reapplyEvery = (int) ($fee['penalty_reapply_every'] ?? 1);
                $reapplyUnit  = $fee['penalty_reapply_unit'] ?? 'months';
                $nextApplyDate = date('Y-m-d', strtotime("+{$reapplyEvery} {$reapplyUnit}", strtotime($lastApplied)));
                if ($today < $nextApplyDate) {
                    $skipped++;
                    continue;
                }
            }
        }

        // Step 7: For one_time penalties, skip if already applied once
        if ($fee['penalty_frequency'] === 'one_time' && $penaltyCount > 0) {
            $skipped++;
            continue;
        }

        // Step 8: Calculate penalty amount based on type
        $penaltyAmount = 0;
        switch ($fee['penalty_type']) {
            case 'fixed_amount':
                $penaltyAmount = (float) $fee['penalty_value'];
                break;
            case 'fixed_percentage':
                $penaltyAmount = $sf['balance'] * ((float) $fee['penalty_value'] / 100);
                break;
            case 'varying_amount':
                $tierIndex = min($penaltyCount, count($varyingTiers) - 1);
                if ($tierIndex >= 0 && isset($varyingTiers[$tierIndex])) {
                    $penaltyAmount = (float) $varyingTiers[$tierIndex]['value'];
                }
                break;
            case 'varying_percentage':
                $tierIndex = min($penaltyCount, count($varyingTiers) - 1);
                if ($tierIndex >= 0 && isset($varyingTiers[$tierIndex])) {
                    $penaltyAmount = $sf['balance'] * ((float) $varyingTiers[$tierIndex]['value'] / 100);
                }
                break;
        }

        if ($penaltyAmount <= 0) {
            $skipped++;
            continue;
        }

        // Step 8b: Enforce max_penalty_amount cap on cumulative total
        if ($fee['max_penalty_amount'] > 0) {
            $totalApplied = (float) db_fetch_value(
                "SELECT COALESCE(SUM(penalty_amount), 0) FROM fin_penalty_log WHERE student_fee_id = ?",
                [$sf['id']]
            );
            $remaining = $fee['max_penalty_amount'] - $totalApplied;
            if ($remaining <= 0) { $skipped++; continue; }
            $penaltyAmount = min($penaltyAmount, $remaining);
        }

        // Step 9: Apply penalty in a transaction
        db_begin();
        try {
            $newBalance = $sf['balance'] + $penaltyAmount;

            // Update student fee balance
            db_update('fin_student_fees', ['balance' => $newBalance], 'id = ?', [$sf['id']]);

            // Insert transaction record
            $txId = db_insert('fin_transactions', [
                'student_id'     => $sf['student_id'],
                'student_fee_id' => $sf['id'],
                'type'           => 'penalty',
                'amount'         => $penaltyAmount,
                'currency'       => $sf['currency'],
                'balance_before' => $sf['balance'],
                'balance_after'  => $newBalance,
                'description'    => 'Penalty applied: ' . $fee['description'] . ' (#' . ($penaltyCount + 1) . ')',
            ]);

            // Insert penalty log
            db_insert('fin_penalty_log', [
                'student_fee_id' => $sf['id'],
                'transaction_id' => $txId,
                'penalty_amount' => $penaltyAmount,
                'apply_count'    => $penaltyCount + 1,
                'applied_at'     => $now,
            ]);

            db_commit();
            $applied++;
        } catch (Throwable $e) {
            db_rollback();
            error_log('Penalty apply error [sf_id=' . $sf['id'] . ']: ' . $e->getMessage());
            $errors++;
        }
    }
}

$msg = "$applied penalty(ies) applied.";
if ($skipped) $msg .= " $skipped skipped.";
if ($errors)  $msg .= " $errors errors.";

if (!defined('CRON_MODE')) {
    set_flash('success', $msg);
    redirect(url('finance', 'report-penalty'));
}

// Return message for cron logging
return $msg;
