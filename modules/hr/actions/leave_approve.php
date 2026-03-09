<?php
/**
 * HR — Approve/Reject Leave Request
 */
csrf_protect();

$id     = input_int('id');
$action = input('leave_action'); // 'approve' or 'reject'
$reason = trim(input('rejection_reason'));

if (!$id || !in_array($action, ['approve', 'reject'], true)) {
    set_flash('error', 'Invalid request.');
    redirect(url('hr', 'leave-requests'));
}

$request = db_fetch_one("SELECT * FROM hr_leave_requests WHERE id = ? AND status = 'pending'", [$id]);
if (!$request) {
    set_flash('error', 'Leave request not found or already processed.');
    redirect(url('hr', 'leave-requests'));
}

if ($action === 'approve') {
    db_update('hr_leave_requests', [
        'status'        => 'approved',
        'approved_by'   => auth_user_id(),
        'approval_date' => date('Y-m-d H:i:s'),
    ], 'id = ?', [$id]);

    // Mark attendance as 'leave' for the approved period
    $start = new DateTime($request['start_date_gregorian']);
    $end   = new DateTime($request['end_date_gregorian']);
    $current = clone $start;

    while ($current <= $end) {
        $dayOfWeek = (int)$current->format('N');
        if ($dayOfWeek < 6) { // Weekday only
            $dateStr = $current->format('Y-m-d');
            $dateEc  = gregorian_str_to_ec($dateStr);
            $existing = db_fetch_one(
                "SELECT id FROM hr_attendance WHERE employee_id = ? AND date_gregorian = ?",
                [$request['employee_id'], $dateStr]
            );
            if (!$existing) {
                db_insert('hr_attendance', [
                    'employee_id'    => $request['employee_id'],
                    'date_ec'        => $dateEc,
                    'date_gregorian' => $dateStr,
                    'status'         => 'leave',
                    'source'         => 'manual',
                    'notes'          => 'Auto-marked from approved leave request #' . $id,
                    'marked_by'      => auth_user_id(),
                ]);
            }
        }
        $current->modify('+1 day');
    }

    audit_log('hr.leave.approve', "Approved leave request ID: {$id}");
    set_flash('success', 'Leave request approved.');
} else {
    db_update('hr_leave_requests', [
        'status'           => 'rejected',
        'approved_by'      => auth_user_id(),
        'approval_date'    => date('Y-m-d H:i:s'),
        'rejection_reason' => $reason ?: null,
    ], 'id = ?', [$id]);

    audit_log('hr.leave.reject', "Rejected leave request ID: {$id}");
    set_flash('success', 'Leave request rejected.');
}

redirect(url('hr', 'leave-requests'));
