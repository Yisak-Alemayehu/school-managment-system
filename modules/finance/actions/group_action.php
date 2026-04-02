<?php
/**
 * Finance — Group Bulk Action (assign/remove fee, adjust balance for all members)
 */
csrf_protect();

$groupId   = input_int('group_id');
$action    = input('action_type');
$feeId     = input_int('fee_id');
$amount    = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;

if (!$groupId || !$action) {
    set_flash('error', 'Invalid request.');
    redirect(url('finance', 'groups'));
}

$members = db_fetch_all(
    "SELECT gm.student_id FROM fin_group_members gm WHERE gm.group_id = ?",
    [$groupId]
);

if (empty($members)) {
    set_flash('error', 'Group has no members.');
    redirect(url('finance', 'group-detail', $groupId) . '&tab=action');
}

$user      = auth_user();
$success   = 0;
$skipped   = 0;

db_begin();
try {
    foreach ($members as $m) {
        $sid = $m['student_id'];

        if ($action === 'assign_fee' && $feeId) {
            $fee = db_fetch_one("SELECT * FROM fin_fees WHERE id = ? AND is_active = 1", [$feeId]);
            if (!$fee) continue;

            // Credit-hour fee: multiply by student's credit hours
            $assignedAmount = $fee['amount'];
            if ($fee['is_credit_hour']) {
                $creditHours = (int) db_fetch_value(
                    "SELECT COALESCE(SUM(sub.credit_hours), 0)
                       FROM enrollments e
                       JOIN subjects sub ON sub.class_id = e.class_id
                      WHERE e.student_id = ? AND e.status = 'active'",
                    [$sid]
                );
                if ($creditHours === 0) { $skipped++; continue; }
                $assignedAmount = $fee['amount'] * $creditHours;
            }

            $existing = db_fetch_one(
                "SELECT id FROM fin_student_fees WHERE student_id = ? AND fee_id = ? AND is_active = 1",
                [$sid, $feeId]
            );
            if ($existing) { $skipped++; continue; }

            $sfId = db_insert('fin_student_fees', [
                'student_id'  => $sid,
                'fee_id'      => $feeId,
                'amount'      => $assignedAmount,
                'currency'    => $fee['currency'],
                'balance'     => $assignedAmount,
                'is_active'   => 1,
                'assigned_by' => $user['id'],
            ]);
            db_insert('fin_transactions', [
                'student_id'     => $sid,
                'student_fee_id' => $sfId,
                'type'           => 'fee_assigned',
                'amount'         => $assignedAmount,
                'currency'       => $fee['currency'],
                'balance_before' => 0,
                'balance_after'  => $assignedAmount,
                'description'    => 'Fee assigned via group action: ' . $fee['description'],
                'processed_by'   => $user['id'],
            ]);
            $success++;

        } elseif ($action === 'remove_fee' && $feeId) {
            $sf = db_fetch_one(
                "SELECT * FROM fin_student_fees WHERE student_id = ? AND fee_id = ? AND is_active = 1",
                [$sid, $feeId]
            );
            if (!$sf) { $skipped++; continue; }

            db_update('fin_student_fees', ['is_active' => 0], 'id = ?', [$sf['id']]);
            db_insert('fin_transactions', [
                'student_id'     => $sid,
                'student_fee_id' => $sf['id'],
                'type'           => 'fee_removed',
                'amount'         => $sf['balance'],
                'currency'       => $sf['currency'],
                'balance_before' => $sf['balance'],
                'balance_after'  => 0,
                'description'    => 'Fee removed via group action',
                'processed_by'   => $user['id'],
            ]);
            $success++;

        } elseif ($action === 'adjust_balance' && $feeId && $amount != 0) {
            $sf = db_fetch_one(
                "SELECT * FROM fin_student_fees WHERE student_id = ? AND fee_id = ? AND is_active = 1",
                [$sid, $feeId]
            );
            if (!$sf) { $skipped++; continue; }

            $newBalance = $sf['balance'] + $amount;
            if ($newBalance < 0) $newBalance = 0;

            db_update('fin_student_fees', ['balance' => $newBalance], 'id = ?', [$sf['id']]);
            db_insert('fin_transactions', [
                'student_id'     => $sid,
                'student_fee_id' => $sf['id'],
                'type'           => 'adjustment',
                'amount'         => $amount,
                'currency'       => $sf['currency'],
                'balance_before' => $sf['balance'],
                'balance_after'  => $newBalance,
                'description'    => 'Balance adjusted via group action',
                'processed_by'   => $user['id'],
            ]);
            $success++;
        }
    }

    db_commit();

    $msg = "$success member(s) processed.";
    if ($skipped > 0) $msg .= " $skipped skipped (already assigned or not applicable).";
    set_flash('success', $msg);
} catch (Throwable $e) {
    db_rollback();
    error_log('Group action error: ' . $e->getMessage());
    set_flash('error', 'Failed to process group action.');
}

redirect(url('finance', 'group-detail', $groupId) . '&tab=action');
