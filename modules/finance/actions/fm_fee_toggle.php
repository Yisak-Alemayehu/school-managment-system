<?php
/**
 * Fee Management — Toggle Fee Status (activate / deactivate)
 */

if (!is_post()) { redirect('finance', 'fm-manage-fees'); }
verify_csrf();

$id     = input_int('id');
$action = $_POST['toggle_action'] ?? '';

if (!$id || !in_array($action, ['activate', 'deactivate'])) {
    set_flash('error', 'Invalid request.');
    redirect('finance', 'fm-manage-fees');
}

$fee = db_fetch_one("SELECT * FROM fees WHERE id = ? AND deleted_at IS NULL", [$id]);
if (!$fee) {
    set_flash('error', 'Fee not found.');
    redirect('finance', 'fm-manage-fees');
}

$newStatus = $action === 'activate' ? 'active' : 'inactive';

try {
    db_begin();

    db_update('fees', ['status' => $newStatus], 'id = ?', [$id]);

    // If activating, generate charges for all current assignments
    if ($newStatus === 'active' && $fee['status'] !== 'active') {
        _fm_generate_charges_for_fee($id);
    }

    // If deactivating, mark pending charges as cancelled
    if ($newStatus === 'inactive') {
        db_query(
            "UPDATE student_fee_charges SET status = 'cancelled' WHERE fee_id = ? AND status = 'pending'",
            [$id]
        );
    }

    db_insert('finance_audit_log', [
        'user_id'     => auth_user_id(),
        'action'      => 'fee_' . $action . 'd',
        'entity_type' => 'fee',
        'entity_id'   => $id,
        'details'     => json_encode(['old_status' => $fee['status'], 'new_status' => $newStatus]),
        'ip_address'  => get_client_ip(),
    ]);

    db_commit();
    set_flash('success', 'Fee ' . $action . 'd successfully.');
} catch (Throwable $e) {
    db_rollback();
    error_log('Fee toggle error: ' . $e->getMessage());
    set_flash('error', 'Failed to ' . $action . ' fee.');
}

redirect('finance', 'fm-manage-fees');

/**
 * Generate initial charges for all assigned students of a fee
 */
function _fm_generate_charges_for_fee(int $feeId): void {
    $fee = db_fetch_one("SELECT * FROM fees WHERE id = ?", [$feeId]);
    if (!$fee) return;

    $assignments = db_fetch_all("SELECT * FROM fee_assignments WHERE fee_id = ?", [$feeId]);
    $exempted = db_fetch_all("SELECT student_id FROM fee_exemptions WHERE fee_id = ?", [$feeId]);
    $exemptedIds = array_column($exempted, 'student_id');

    $studentIds = [];

    foreach ($assignments as $asgn) {
        $ids = _fm_resolve_assignment_students($asgn);
        $studentIds = array_merge($studentIds, $ids);
    }

    $studentIds = array_unique($studentIds);
    $studentIds = array_diff($studentIds, $exemptedIds);

    foreach ($studentIds as $sid) {
        // Check if charge already exists for occurrence 1
        $exists = db_exists('student_fee_charges', 'student_id = ? AND fee_id = ? AND occurrence_number = 1', [$sid, $feeId]);
        if (!$exists) {
            db_insert('student_fee_charges', [
                'student_id'        => $sid,
                'fee_id'            => $feeId,
                'amount'            => $fee['amount'],
                'due_date'          => $fee['effective_date'],
                'occurrence_number' => 1,
                'status'            => 'pending',
            ]);
        }
    }
}

/**
 * Resolve assignment to student IDs
 */
function _fm_resolve_assignment_students(array $asgn): array {
    $sessionId = db_fetch_value("SELECT id FROM academic_sessions WHERE is_active = 1 LIMIT 1");

    switch ($asgn['assignment_type']) {
        case 'class':
            return array_column(db_fetch_all(
                "SELECT student_id FROM enrollments WHERE class_id = ? AND session_id = ? AND status = 'active'",
                [$asgn['target_id'], $sessionId]
            ), 'student_id');

        case 'grade':
            return array_column(db_fetch_all(
                "SELECT e.student_id FROM enrollments e JOIN classes c ON e.class_id = c.id WHERE c.numeric_name = ? AND e.session_id = ? AND e.status = 'active'",
                [$asgn['target_id'], $sessionId]
            ), 'student_id');

        case 'individual':
            return [$asgn['target_id']];

        case 'group':
            return array_column(db_fetch_all(
                "SELECT student_id FROM student_group_members WHERE group_id = ?",
                [$asgn['target_id']]
            ), 'student_id');

        default:
            return [];
    }
}
