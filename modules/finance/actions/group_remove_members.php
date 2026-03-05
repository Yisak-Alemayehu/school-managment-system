<?php
/**
 * Finance — Remove Members from Group
 */
csrf_protect();

$groupId   = input_int('group_id');
$removeIds = input_array('remove_ids');

if (!$groupId || empty($removeIds)) {
    set_flash('error', 'No members selected.');
    redirect(url('finance', 'group-detail', $groupId) . '&tab=action');
}

$removed = 0;
foreach ($removeIds as $gmId) {
    $gmId = (int) $gmId;
    if ($gmId <= 0) continue;
    $removed += db_delete('fin_group_members', 'id = ? AND group_id = ?', [$gmId, $groupId]);
}

set_flash('success', "$removed member(s) removed from group.");
redirect(url('finance', 'group-detail', $groupId) . '&tab=action');
