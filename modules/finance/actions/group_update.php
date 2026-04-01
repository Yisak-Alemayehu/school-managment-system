<?php
/**
 * Finance — Update Group
 */
csrf_protect();

$groupId = input_int('group_id');
if (!$groupId) {
    set_flash('error', 'Invalid group.');
    redirect(url('finance', 'groups'));
}

$existing = db_fetch_one("SELECT * FROM fin_groups WHERE id = ?", [$groupId]);
if (!$existing) {
    set_flash('error', 'Group not found.');
    redirect(url('finance', 'groups'));
}

$name        = trim(input('name'));
$description = trim(input('description'));
$isActive    = isset($_POST['is_active']) ? 1 : 0;

if (!$name || !$description) {
    set_flash('error', 'Group Name and Description are required.');
    redirect(url('finance', 'group-detail', $groupId) . '&tab=info');
}

try {
    db_update('fin_groups', [
        'name'        => $name,
        'description' => $description,
        'is_active'   => $isActive,
    ], 'id = ?', [$groupId]);

    set_flash('success', 'Group updated successfully.');
} catch (Throwable $e) {
    error_log('Group update error: ' . $e->getMessage());
    set_flash('error', 'Failed to update group.');
}

redirect(url('finance', 'group-detail', $groupId) . '&tab=info');
