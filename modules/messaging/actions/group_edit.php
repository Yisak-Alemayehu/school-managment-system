<?php
/**
 * Messaging — Edit Group (Admin Only)
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
    set_flash('error', 'Only group admins can edit the group.');
    redirect('messaging', 'groups');
}

$name = trim(input('name'));
if ($name === '' || mb_strlen($name) > 100) {
    set_flash('error', 'Group name is required and must be under 100 characters.');
    redirect('messaging', 'group-detail', $groupId);
}

db_update('msg_groups', ['name' => $name], 'id = ?', [$groupId]);

// Update the conversation subject too
db_query("UPDATE msg_conversations SET subject = ? WHERE group_id = ? AND type = 'group'", [$name, $groupId]);

set_flash('success', 'Group renamed successfully.');
redirect('messaging', 'group-detail', $groupId);
