<?php
/**
 * Fee Management — Recurrence Job
 * Generates recurring fee charges based on recurrence_configs.
 * 
 * Run via cron daily at 2 AM:
 *   0 2 * * * php /path/to/cron/fm_recurrence_job.php >> /path/to/logs/fm_recurrence.log 2>&1
 */

// Bootstrap
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/core/env.php';
require APP_ROOT . '/config/app.php';
require APP_ROOT . '/config/database.php';
require APP_ROOT . '/core/db.php';
require APP_ROOT . '/core/helpers.php';

echo "[" . date('Y-m-d H:i:s') . "] Recurrence job started.\n";

$today = date('Y-m-d');

// Find all recurrence configs where:
// - Fee is active, not deleted, and end_date >= today
// - next_due_date <= today
// - max_recurrences = 0 OR current_recurrence < max_recurrences
$configs = db_fetch_all(
    "SELECT rc.*, f.amount, f.currency, f.description AS fee_desc, f.end_date
     FROM recurrence_configs rc
     JOIN fees f ON f.id = rc.fee_id
     WHERE f.status = 'active'
       AND f.deleted_at IS NULL
       AND f.end_date >= ?
       AND rc.next_due_date IS NOT NULL
       AND rc.next_due_date <= ?
       AND (rc.max_recurrences = 0 OR rc.current_recurrence < rc.max_recurrences)",
    [$today, $today]
);

echo "  Found " . count($configs) . " recurrence config(s) to process.\n";

$totalCharges = 0;

foreach ($configs as $rc) {
    $feeId  = $rc['fee_id'];
    $dueDate = $rc['next_due_date'];
    
    echo "  Processing fee #{$feeId} ({$rc['fee_desc']}), due: {$dueDate}\n";

    try {
        db_begin();

        // Get all assigned students (resolve from fee_assignments)
        $studentIds = _resolve_fee_students($feeId);
        
        // Get exempted students
        $exempted = db_fetch_all(
            "SELECT student_id FROM fee_exemptions WHERE fee_id = ? AND deleted_at IS NULL",
            [$feeId]
        );
        $exemptedIds = array_column($exempted, 'student_id');

        $generated = 0;
        foreach ($studentIds as $studentId) {
            // Skip exempted
            if (in_array($studentId, $exemptedIds)) continue;

            // Check if charge already exists for this due date
            $exists = db_fetch_value(
                "SELECT COUNT(*) FROM student_fee_charges WHERE fee_id = ? AND student_id = ? AND due_date = ?",
                [$feeId, $studentId, $dueDate]
            );
            if ($exists > 0) continue;

            db_insert('student_fee_charges', [
                'fee_id'     => $feeId,
                'student_id' => $studentId,
                'amount'     => $rc['amount'],
                'currency'   => $rc['currency'],
                'due_date'   => $dueDate,
                'status'     => 'pending',
            ]);
            $generated++;
        }

        // Calculate next due date
        $nextDue = calculate_next_due($dueDate, $rc['frequency_number'], $rc['frequency_unit']);
        $newRecurrence = $rc['current_recurrence'] + 1;

        // If next due exceeds fee end_date or max_recurrences reached, set next_due to null
        $reachedMax = ($rc['max_recurrences'] > 0 && $newRecurrence >= $rc['max_recurrences']);
        $pastEnd    = ($nextDue > $rc['end_date']);

        db_query(
            "UPDATE recurrence_configs SET current_recurrence = ?, next_due_date = ?, last_generated_at = NOW() WHERE id = ?",
            [$newRecurrence, ($reachedMax || $pastEnd) ? null : $nextDue, $rc['id']]
        );

        db_commit();
        $totalCharges += $generated;
        echo "    Generated {$generated} charge(s), next due: " . (($reachedMax || $pastEnd) ? 'DONE' : $nextDue) . "\n";

    } catch (Exception $e) {
        db_rollback();
        echo "    ERROR: " . $e->getMessage() . "\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Recurrence job finished. Total charges: {$totalCharges}\n\n";

// ── Helpers ──────────────────────────────────────────────────

function _resolve_fee_students(int $feeId): array {
    $assignments = db_fetch_all(
        "SELECT * FROM fee_assignments WHERE fee_id = ? AND deleted_at IS NULL",
        [$feeId]
    );

    $studentIds = [];
    foreach ($assignments as $a) {
        switch ($a['assignment_type']) {
            case 'individual':
                $studentIds[] = (int)$a['target_id'];
                break;
            case 'class':
                $rows = db_fetch_all(
                    "SELECT student_id FROM enrollments WHERE section_id = ? AND status = 'active'",
                    [$a['target_id']]
                );
                foreach ($rows as $r) $studentIds[] = (int)$r['student_id'];
                break;
            case 'grade':
                $rows = db_fetch_all(
                    "SELECT student_id FROM enrollments WHERE class_id = ? AND status = 'active'",
                    [$a['target_id']]
                );
                foreach ($rows as $r) $studentIds[] = (int)$r['student_id'];
                break;
            case 'group':
                $rows = db_fetch_all(
                    "SELECT student_id FROM student_group_members WHERE group_id = ? AND deleted_at IS NULL",
                    [$a['target_id']]
                );
                foreach ($rows as $r) $studentIds[] = (int)$r['student_id'];
                break;
        }
    }

    return array_unique($studentIds);
}

function calculate_next_due(string $currentDue, int $number, string $unit): string {
    $dt = new DateTime($currentDue);
    switch ($unit) {
        case 'days':   $dt->modify("+{$number} days");   break;
        case 'weeks':  $dt->modify("+{$number} weeks");  break;
        case 'months': $dt->modify("+{$number} months"); break;
        case 'years':  $dt->modify("+{$number} years");  break;
    }
    return $dt->format('Y-m-d');
}
