<?php
/**
 * Messaging — Remove Member from Group (Admin Only)
 */
csrf_protect();

$userId  = auth_user_id();
$groupId = input_int('group_id');
$removeUser = input_int('user_id');

if (!$groupId || !$removeUser) {
    set_flash('error', 'Invalid request.');
    redirect('messaging', 'groups');
}

// Cannot remove yourself
if ($removeUser == $userId) {
    set_flash('error', 'You cannot remove yourself.');
    redirect('messaging', 'group-detail', $groupId);
}

// Verify admin
$isAdmin = db_fetch_value("SELECT is_admin FROM msg_group_members WHERE group_id = ? AND user_id = ?", [$groupId, $userId]);
if (!$isAdmin) {
    set_flash('error', 'Only group admins can remove members.');
    redirect('messaging', 'group-detail', $groupId);
}

// Cannot remove another admin
$targetIsAdmin = db_fetch_value("SELECT is_admin FROM msg_group_members WHERE group_id = ? AND user_id = ?", [$groupId, $removeUser]);
if ($targetIsAdmin) {
    set_flash('error', 'Cannot remove a group admin.');
    redirect('messaging', 'group-detail', $groupId);
}

db_begin();
try {
    db_delete('msg_group_members', 'group_id = ? AND user_id = ?', [$groupId, $removeUser]);

    // Soft-delete from conversation
    $convId = db_fetch_value("SELECT id FROM msg_conversations WHERE group_id = ? AND type = 'group'", [$groupId]);
    if ($convId) {
        db_query("UPDATE msg_conversation_participants SET is_deleted = 1 WHERE conversation_id = ? AND user_id = ?", [$convId, $removeUser]);
    }

    db_commit();
    set_flash('success', 'Member removed.');
} catch (Exception $ex) {
    db_rollback();
    set_flash('error', 'Failed to remove member.');
}

redirect('messaging', 'group-detail', $groupId);
