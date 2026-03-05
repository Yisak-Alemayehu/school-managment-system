<?php
/**
 * Finance — Save New Group
 */
csrf_protect();

$name        = trim(input('name'));
$description = trim(input('description'));
$source      = input('source') ?: 'empty';
$classId     = input_int('source_class_id');
$isActive    = input('is_active') ? 1 : 0;

if (!$name || !$description) {
    set_flash('error', 'Group Name and Description are required.');
    redirect(url('finance', 'groups'));
}

$user = auth_user();

db_begin();
try {
    $groupId = db_insert('fin_groups', [
        'name'            => $name,
        'description'     => $description,
        'source'          => $source,
        'source_class_id' => $source === 'class' && $classId ? $classId : null,
        'is_active'       => $isActive,
        'created_by'      => $user['id'],
    ]);

    // If from class, auto-add students enrolled in that class
    if ($source === 'class' && $classId) {
        $students = db_fetch_all(
            "SELECT student_id FROM enrollments WHERE class_id = ? AND status = 'active'",
            [$classId]
        );
        foreach ($students as $s) {
            db_insert('fin_group_members', [
                'group_id'   => $groupId,
                'student_id' => $s['student_id'],
                'added_by'   => $user['id'],
            ]);
        }
    }

    db_commit();
    set_flash('success', 'Group created successfully.');
    redirect(url('finance', 'group-detail', $groupId));
} catch (Throwable $e) {
    db_rollback();
    error_log('Group save error: ' . $e->getMessage());
    set_flash('error', 'Failed to create group.');
    redirect(url('finance', 'groups'));
}
