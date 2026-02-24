<?php
/**
 * Fee Management — Delete Student Group
 */

if (!is_post()) { redirect('finance', 'fm-groups'); }
verify_csrf();

$id = input_int('id');
if (!$id) {
    set_flash('error', 'Invalid group ID.');
    redirect('finance', 'fm-groups');
}

$group = db_fetch_one("SELECT * FROM student_groups WHERE id = ?", [$id]);
if (!$group) {
    set_flash('error', 'Group not found.');
    redirect('finance', 'fm-groups');
}

// Check for active fee assignments
$activeAssignments = db_count('fee_assignments', "assignment_type = 'group' AND target_id = ?", [$id]);
if ($activeAssignments > 0) {
    set_flash('error', 'Cannot delete group with active fee assignments. Remove assignments first.');
    redirect('finance', 'fm-groups');
}

try {
    db_begin();

    // Members are cascade-deleted
    db_delete('student_groups', 'id = ?', [$id]);

    db_insert('finance_audit_log', [
        'user_id'     => auth_user_id(),
        'action'      => 'group_deleted',
        'entity_type' => 'student_group',
        'entity_id'   => $id,
        'details'     => json_encode(['name' => $group['name']]),
        'ip_address'  => get_client_ip(),
    ]);

    db_commit();
    set_flash('success', 'Group deleted.');
} catch (Throwable $e) {
    db_rollback();
    error_log('Group delete error: ' . $e->getMessage());
    set_flash('error', 'Failed to delete group.');
}

redirect('finance', 'fm-groups');
