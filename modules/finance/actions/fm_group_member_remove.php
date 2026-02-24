<?php
/**
 * Fee Management — Remove Member from Group
 */

if (!is_post()) { redirect('finance', 'fm-groups'); }
verify_csrf();

$groupId   = input_int('group_id');
$studentId = input_int('student_id');

if (!$groupId || !$studentId) {
    set_flash('error', 'Invalid request.');
    redirect('finance', 'fm-groups');
}

$deleted = db_delete('student_group_members', 'group_id = ? AND student_id = ?', [$groupId, $studentId]);

if ($deleted) {
    set_flash('success', 'Member removed from group.');
} else {
    set_flash('error', 'Member not found in group.');
}

redirect('finance', 'fm-group-members', $groupId);
