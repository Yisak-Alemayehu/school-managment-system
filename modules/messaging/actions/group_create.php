<?php
/**
 * Messaging — Create Group (Student Only)
 */
csrf_protect();

$userId = auth_user_id();

// Must be a student
if (!auth_has_role('student')) {
    set_flash('error', 'Only students can create groups.');
    redirect('messaging', 'groups');
}

$name        = trim(input('name'));
$description = trim(input('description'));
$memberIds   = input_array('member_ids');

// Validate
$errors = [];
if ($name === '') {
    $errors['name'] = 'Group name is required.';
} elseif (mb_strlen($name) > 100) {
    $errors['name'] = 'Group name must be under 100 characters.';
}
if (mb_strlen($description) > 500) {
    $errors['description'] = 'Description must be under 500 characters.';
}

// Check 3-group limit
$myGroupCount = db_fetch_value("SELECT COUNT(*) FROM msg_groups WHERE created_by = ? AND is_active = 1", [$userId]);
if ($myGroupCount >= 3) {
    set_flash('error', 'You can create a maximum of 3 groups.');
    redirect('messaging', 'group-create');
}

// Check member count (doesn't include creator)
if (count($memberIds) > 29) {
    $errors['member_ids'] = 'A group can have a maximum of 30 members including you.';
}

if (!empty($errors)) {
    set_validation_errors($errors);
    set_old_input();
    redirect('messaging', 'group-create');
}

// Get student enrollment for class/section
$enrollment = db_fetch_one("
    SELECT e.class_id, e.section_id
      FROM students s
      JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
     WHERE s.user_id = ? LIMIT 1
", [$userId]);

if (!$enrollment) {
    set_flash('error', 'Could not determine your class enrollment.');
    redirect('messaging', 'groups');
}

// Validate member IDs are real classmates
$validMemberIds = [];
if (!empty($memberIds)) {
    $ph = implode(',', array_fill(0, count($memberIds), '?'));
    $params = array_merge($memberIds, [$enrollment['class_id']]);
    $sectionFilter = '';
    if ($enrollment['section_id']) {
        $sectionFilter = "AND e.section_id = ?";
        $params[] = $enrollment['section_id'];
    }
    $validMembers = db_fetch_all("
        SELECT u.id FROM students s
          JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
          JOIN users u ON s.user_id = u.id
         WHERE u.id IN ($ph) AND e.class_id = ? $sectionFilter
           AND u.status = 'active' AND u.deleted_at IS NULL
    ", $params);
    $validMemberIds = array_column($validMembers, 'id');
}

db_begin();
try {
    // Create group
    $groupId = db_insert('msg_groups', [
        'name'        => $name,
        'description' => $description,
        'created_by'  => $userId,
        'class_id'    => $enrollment['class_id'],
        'section_id'  => $enrollment['section_id'],
        'max_members' => 30,
    ]);

    // Add creator as admin
    db_insert('msg_group_members', [
        'group_id'  => $groupId,
        'user_id'   => $userId,
        'is_admin'  => 1,
    ]);

    // Add selected members
    foreach ($validMemberIds as $mid) {
        if ($mid != $userId) {
            db_insert('msg_group_members', [
                'group_id'  => $groupId,
                'user_id'   => $mid,
                'is_admin'  => 0,
            ]);
        }
    }

    // Create the group conversation
    $convId = db_insert('msg_conversations', [
        'type'       => 'group',
        'subject'    => $name,
        'created_by' => $userId,
        'group_id'   => $groupId,
    ]);

    // Add all members as conversation participants
    $allIds = array_unique(array_merge([$userId], $validMemberIds));
    foreach ($allIds as $uid) {
        db_insert('msg_conversation_participants', [
            'conversation_id' => $convId,
            'user_id'         => $uid,
        ]);
    }

    db_commit();
    set_flash('success', 'Group "' . e($name) . '" created successfully.');
    redirect('messaging', 'group-detail', $groupId);
} catch (Exception $ex) {
    db_rollback();
    set_flash('error', 'Failed to create group. Please try again.');
    set_old_input();
    redirect('messaging', 'group-create');
}
