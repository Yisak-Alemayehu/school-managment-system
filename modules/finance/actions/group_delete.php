<?php
/**
 * Finance — Delete Group
 */
csrf_protect();

$groupId = input_int('group_id');
if (!$groupId) {
    set_flash('error', 'Invalid group.');
    redirect(url('finance', 'groups'));
}

$group = db_fetch_one("SELECT id FROM fin_groups WHERE id = ?", [$groupId]);
if (!$group) {
    set_flash('error', 'Group not found.');
    redirect(url('finance', 'groups'));
}

db_begin();
try {
    // Remove all group members first
    db_fetch_one("DELETE FROM fin_group_members WHERE group_id = ?", [$groupId]);
    // Delete the group
    db_fetch_one("DELETE FROM fin_groups WHERE id = ?", [$groupId]);

    db_commit();
    set_flash('success', 'Group deleted successfully.');
} catch (Throwable $e) {
    db_rollback();
    error_log('Group delete error: ' . $e->getMessage());
    set_flash('error', 'Failed to delete group.');
}

redirect(url('finance', 'groups'));
