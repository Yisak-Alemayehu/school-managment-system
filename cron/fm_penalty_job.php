<?php
/**
 * Fee Management — Penalty Job
 * Applies penalty charges to overdue student fee charges.
 * 
 * Run via cron daily at 3 AM:
 *   0 3 * * * php /path/to/cron/fm_penalty_job.php >> /path/to/logs/fm_penalty.log 2>&1
 */

// Bootstrap
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/core/env.php';
require APP_ROOT . '/config/app.php';
require APP_ROOT . '/config/database.php';
require APP_ROOT . '/core/db.php';
require APP_ROOT . '/core/helpers.php';

echo "[" . date('Y-m-d H:i:s') . "] Penalty job started.\n";

$today = date('Y-m-d');

// Step 1: Mark overdue charges
$overdue = db_query(
    "UPDATE student_fee_charges 
     SET status = 'overdue' 
     WHERE status = 'pending' AND due_date < ?",
    [$today]
);
echo "  Marked overdue charges.\n";

// Step 2: Find active fees with penalty configs
$penaltyFees = db_fetch_all(
    "SELECT pc.*, f.id AS fee_id, f.amount AS fee_amount, f.description AS fee_desc
     FROM penalty_configs pc
     JOIN fees f ON f.id = pc.fee_id
     WHERE f.status = 'active'
       AND f.deleted_at IS NULL
       AND (pc.penalty_end_date IS NULL OR pc.penalty_end_date >= ?)",
    [$today]
);

echo "  Found " . count($penaltyFees) . " fee(s) with penalty configs.\n";

$totalPenalties = 0;

foreach ($penaltyFees as $pc) {
    $feeId = $pc['fee_id'];

    // Calculate grace period threshold
    $graceDate = calculate_grace_threshold($today, $pc['grace_period_number'], $pc['grace_period_unit']);

    // Find overdue charges past grace period
    $overdueCharges = db_fetch_all(
        "SELECT sfc.* FROM student_fee_charges sfc
         WHERE sfc.fee_id = ?
           AND sfc.status = 'overdue'
           AND sfc.due_date <= ?",
        [$feeId, $graceDate]
    );

    echo "  Fee #{$feeId} ({$pc['fee_desc']}): " . count($overdueCharges) . " overdue charge(s) past grace period.\n";

    foreach ($overdueCharges as $charge) {
        try {
            // Check existing penalty count for this charge
            $penaltyCount = (int)db_fetch_value(
                "SELECT COUNT(*) FROM penalty_charges WHERE charge_id = ?",
                [$charge['id']]
            );

            // For one-time penalty: only apply once
            if ($pc['penalty_frequency'] === 'one_time' && $penaltyCount > 0) {
                continue;
            }

            // For recurrent penalty: check if max applications reached
            if ($pc['max_penalty_applications'] > 0 && $penaltyCount >= $pc['max_penalty_applications']) {
                continue;
            }

            // For recurrent penalty: check if enough time has passed since last penalty
            if ($pc['penalty_frequency'] === 'recurrent' && $penaltyCount > 0) {
                $lastPenalty = db_fetch_one(
                    "SELECT applied_at FROM penalty_charges WHERE charge_id = ? ORDER BY applied_at DESC LIMIT 1",
                    [$charge['id']]
                );
                if ($lastPenalty) {
                    $nextPenaltyDate = calculate_next_penalty_date(
                        $lastPenalty['applied_at'],
                        $pc['penalty_recurrence_number'],
                        $pc['penalty_recurrence_unit']
                    );
                    if ($today < $nextPenaltyDate) {
                        continue; // Not yet time for next penalty
                    }
                }
            }

            // Calculate penalty amount
            $penaltyAmount = calculate_penalty_amount($pc, $charge['amount']);

            // Check max penalty cap
            $existingTotal = (float)db_fetch_value(
                "SELECT COALESCE(SUM(penalty_amount), 0) FROM penalty_charges WHERE charge_id = ?",
                [$charge['id']]
            );

            if (($existingTotal + $penaltyAmount) > (float)$pc['max_penalty_amount']) {
                $penaltyAmount = max(0, (float)$pc['max_penalty_amount'] - $existingTotal);
                if ($penaltyAmount <= 0) continue;
            }

            // Insert penalty charge
            db_insert('penalty_charges', [
                'charge_id'      => $charge['id'],
                'penalty_amount' => $penaltyAmount,
                'applied_at'     => $today . ' ' . date('H:i:s'),
                'status'         => 'pending',
            ]);

            $totalPenalties++;
            echo "    Applied penalty " . number_format($penaltyAmount, 2) . " to charge #{$charge['id']} (student #{$charge['student_id']})\n";

        } catch (Exception $e) {
            echo "    ERROR on charge #{$charge['id']}: " . $e->getMessage() . "\n";
        }
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Penalty job finished. Total penalties: {$totalPenalties}\n\n";

// ── Helpers ──────────────────────────────────────────────────

function calculate_grace_threshold(string $today, int $number, string $unit): string {
    $dt = new DateTime($today);
    switch ($unit) {
        case 'days':   $dt->modify("-{$number} days");   break;
        case 'weeks':  $dt->modify("-{$number} weeks");  break;
        case 'months': $dt->modify("-{$number} months"); break;
    }
    return $dt->format('Y-m-d');
}

function calculate_next_penalty_date(string $lastDate, int $number, string $unit): string {
    $dt = new DateTime($lastDate);
    switch ($unit) {
        case 'days':   $dt->modify("+{$number} days");   break;
        case 'weeks':  $dt->modify("+{$number} weeks");  break;
        case 'months': $dt->modify("+{$number} months"); break;
    }
    return $dt->format('Y-m-d');
}

function calculate_penalty_amount(array $pc, float $chargeAmount): float {
    if ($pc['penalty_type'] === 'percentage') {
        return round(($chargeAmount * (float)$pc['penalty_amount']) / 100, 2);
    }
    return (float)$pc['penalty_amount'];
}
