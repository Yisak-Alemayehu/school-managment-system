<?php
/**
 * Messaging — Delete Group (Admin Only)
 */
csrf_protect();

$userId  = auth_user_id();
$groupId = input_int('group_id');

if (!$groupId) {
    set_flash('error', 'Invalid group.');
    redirect('messaging', 'groups');
}

// Verify admin membership
$isAdmin = db_fetch_value("SELECT is_admin FROM msg_group_members WHERE group_id = ? AND user_id = ?", [$groupId, $userId]);
if (!$isAdmin) {
    set_flash('error', 'Only group admins can delete the group.');
    redirect('messaging', 'groups');
}

db_update('msg_groups', ['is_active' => 0], 'id = ?', [$groupId]);

set_flash('success', 'Group deleted.');
redirect('messaging', 'groups');
