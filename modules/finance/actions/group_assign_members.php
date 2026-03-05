<?php
/**
 * Finance — Assign Members to Group
 */
csrf_protect();

$groupId    = input_int('group_id');
$studentIds = input_array('student_ids');

if (!$groupId || empty($studentIds)) {
    set_flash('error', 'No students selected.');
    redirect(url('finance', 'group-detail', $groupId) . '&tab=assign');
}

$group = db_fetch_one("SELECT id FROM fin_groups WHERE id = ?", [$groupId]);
if (!$group) {
    set_flash('error', 'Group not found.');
    redirect(url('finance', 'groups'));
}

$user = auth_user();
$added = 0;

foreach ($studentIds as $sid) {
    $sid = (int) $sid;
    if ($sid <= 0) continue;
    $exists = db_exists('fin_group_members', 'group_id = ? AND student_id = ?', [$groupId, $sid]);
    if (!$exists) {
        db_insert('fin_group_members', [
            'group_id'   => $groupId,
            'student_id' => $sid,
            'added_by'   => $user['id'],
        ]);
        $added++;
    }
}

set_flash('success', "$added member(s) assigned to group.");
redirect(url('finance', 'group-detail', $groupId) . '&tab=assign');
