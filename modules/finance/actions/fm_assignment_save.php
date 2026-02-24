<?php
/**
 * Fee Management — Save Assignment
 * Assigns a fee to class(es), grade(s), student(s), or group(s)
 * and generates student_fee_charges for active fees.
 */

if (!is_post()) { redirect('finance', 'fm-assign-fees'); }
verify_csrf();

$feeId          = input_int('fee_id');
$assignmentType = $_POST['assignment_type'] ?? '';
$targetIds      = $_POST['target_ids'] ?? [];

if (!$feeId || !$assignmentType || empty($targetIds)) {
    set_flash('error', 'Please select a fee, assignment type, and at least one target.');
    set_old_input();
    redirect('finance', 'fm-assign-fees');
}

if (!in_array($assignmentType, ['class', 'grade', 'individual', 'group'])) {
    set_flash('error', 'Invalid assignment type.');
    redirect('finance', 'fm-assign-fees');
}

$fee = db_fetch_one("SELECT * FROM fees WHERE id = ? AND deleted_at IS NULL", [$feeId]);
if (!$fee) {
    set_flash('error', 'Fee not found.');
    redirect('finance', 'fm-assign-fees');
}

if ($fee['status'] !== 'active' && $fee['status'] !== 'draft') {
    set_flash('error', 'Cannot assign an inactive or archived fee.');
    redirect('finance', 'fm-assign-fees');
}

if (!is_array($targetIds)) {
    $targetIds = [$targetIds];
}
$targetIds = array_filter(array_map('intval', $targetIds));

// Exempted students
$exemptStudentIds = [];
if (!empty($_POST['exempt_student_ids'])) {
    $exemptStudentIds = array_filter(array_map('intval', $_POST['exempt_student_ids']));
}

try {
    db_begin();

    $sessionId = db_fetch_value("SELECT id FROM academic_sessions WHERE is_active = 1 LIMIT 1");
    $created   = 0;
    $skipped   = 0;

    foreach ($targetIds as $targetId) {
        // Check if assignment already exists
        $exists = db_exists('fee_assignments', 
            'fee_id = ? AND assignment_type = ? AND target_id = ?', 
            [$feeId, $assignmentType, $targetId]
        );

        if ($exists) {
            $skipped++;
            continue;
        }

        $assignmentId = db_insert('fee_assignments', [
            'fee_id'          => $feeId,
            'assignment_type' => $assignmentType,
            'target_id'       => $targetId,
            'created_by'      => auth_user_id(),
        ]);

        $created++;

        // Generate charges if fee is active
        if ($fee['status'] === 'active') {
            $studentIds = _fm_resolve_target_students($assignmentType, $targetId, $sessionId);
            $exemptedFromFee = array_column(
                db_fetch_all("SELECT student_id FROM fee_exemptions WHERE fee_id = ?", [$feeId]),
                'student_id'
            );

            foreach ($studentIds as $sid) {
                if (in_array($sid, $exemptedFromFee)) continue;

                $chargeExists = db_exists('student_fee_charges', 
                    'student_id = ? AND fee_id = ? AND occurrence_number = 1', 
                    [$sid, $feeId]
                );
                if (!$chargeExists) {
                    db_insert('student_fee_charges', [
                        'student_id'        => $sid,
                        'fee_id'            => $feeId,
                        'fee_assignment_id' => $assignmentId,
                        'amount'            => $fee['amount'],
                        'due_date'          => $fee['effective_date'],
                        'occurrence_number' => 1,
                        'status'            => 'pending',
                    ]);
                }
            }
        }
    }

    // Save exemptions
    foreach ($exemptStudentIds as $exemptSid) {
        $exemptExists = db_exists('fee_exemptions', 
            'fee_id = ? AND student_id = ?', 
            [$feeId, $exemptSid]
        );
        if (!$exemptExists) {
            db_insert('fee_exemptions', [
                'fee_id'     => $feeId,
                'student_id' => $exemptSid,
                'reason'     => $_POST['exemption_reason'] ?? 'Exempted during assignment',
                'created_by' => auth_user_id(),
            ]);

            // Cancel any pending charges for exempted student
            db_query(
                "UPDATE student_fee_charges SET status = 'waived', waived_reason = 'Fee exemption' WHERE fee_id = ? AND student_id = ? AND status = 'pending'",
                [$feeId, $exemptSid]
            );
        }
    }

    db_insert('finance_audit_log', [
        'user_id'     => auth_user_id(),
        'action'      => 'fee_assigned',
        'entity_type' => 'fee_assignment',
        'entity_id'   => $feeId,
        'details'     => json_encode([
            'type'      => $assignmentType,
            'targets'   => $targetIds,
            'created'   => $created,
            'skipped'   => $skipped,
            'exempted'  => count($exemptStudentIds),
        ]),
        'ip_address'  => get_client_ip(),
    ]);

    db_commit();

    $msg = "{$created} assignment(s) created.";
    if ($skipped > 0) $msg .= " {$skipped} already existed.";
    if (count($exemptStudentIds) > 0) $msg .= " " . count($exemptStudentIds) . " exemption(s) added.";

    set_flash('success', $msg);

} catch (Throwable $e) {
    db_rollback();
    error_log('Assignment save error: ' . $e->getMessage());
    set_flash('error', 'Failed to save assignment: ' . $e->getMessage());
}

redirect('finance', 'fm-assign-fees');

/**
 * Resolve target to student IDs
 */
function _fm_resolve_target_students(string $type, int $targetId, $sessionId): array {
    switch ($type) {
        case 'class':
            return array_column(db_fetch_all(
                "SELECT student_id FROM enrollments WHERE class_id = ? AND session_id = ? AND status = 'active'",
                [$targetId, $sessionId]
            ), 'student_id');

        case 'grade':
            return array_column(db_fetch_all(
                "SELECT e.student_id FROM enrollments e JOIN classes c ON e.class_id = c.id WHERE c.numeric_name = ? AND e.session_id = ? AND e.status = 'active'",
                [$targetId, $sessionId]
            ), 'student_id');

        case 'individual':
            return [$targetId];

        case 'group':
            return array_column(db_fetch_all(
                "SELECT student_id FROM student_group_members WHERE group_id = ?",
                [$targetId]
            ), 'student_id');

        default:
            return [];
    }
}
