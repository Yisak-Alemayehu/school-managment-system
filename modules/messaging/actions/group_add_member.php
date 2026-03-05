<?php
/**
 * Messaging — Add Member to Group (Admin Only)
 */
csrf_protect();

$userId  = auth_user_id();
$groupId = input_int('group_id');
$newUser = input_int('user_id');

if (!$groupId || !$newUser) {
    set_flash('error', 'Invalid request.');
    redirect('messaging', 'groups');
}

// Verify admin
$isAdmin = db_fetch_value("SELECT is_admin FROM msg_group_members WHERE group_id = ? AND user_id = ?", [$groupId, $userId]);
if (!$isAdmin) {
    set_flash('error', 'Only group admins can add members.');
    redirect('messaging', 'group-detail', $groupId);
}

// Check member limit
$group = db_fetch_one("SELECT max_members FROM msg_groups WHERE id = ? AND is_active = 1", [$groupId]);
if (!$group) {
    set_flash('error', 'Group not found.');
    redirect('messaging', 'groups');
}

$currentCount = db_fetch_value("SELECT COUNT(*) FROM msg_group_members WHERE group_id = ?", [$groupId]);
if ($currentCount >= $group['max_members']) {
    set_flash('error', 'This group has reached the maximum number of members (' . $group['max_members'] . ').');
    redirect('messaging', 'group-detail', $groupId);
}

// Check not already a member
$existing = db_exists('msg_group_members', 'group_id = ? AND user_id = ?', [$groupId, $newUser]);
if ($existing) {
    set_flash('error', 'This user is already a member.');
    redirect('messaging', 'group-detail', $groupId);
}

db_begin();
try {
    db_insert('msg_group_members', [
        'group_id' => $groupId,
        'user_id'  => $newUser,
        'is_admin' => 0,
    ]);

    // Add to conversation too
    $convId = db_fetch_value("SELECT id FROM msg_conversations WHERE group_id = ? AND type = 'group'", [$groupId]);
    if ($convId) {
        $participantExists = db_exists('msg_conversation_participants', 'conversation_id = ? AND user_id = ?', [$convId, $newUser]);
        if ($participantExists) {
            db_query("UPDATE msg_conversation_participants SET is_deleted = 0 WHERE conversation_id = ? AND user_id = ?", [$convId, $newUser]);
        } else {
            db_insert('msg_conversation_participants', ['conversation_id' => $convId, 'user_id' => $newUser]);
        }
    }

    db_commit();
    set_flash('success', 'Member added.');
} catch (Exception $ex) {
    db_rollback();
    set_flash('error', 'Failed to add member.');
}

redirect('messaging', 'group-detail', $groupId);
