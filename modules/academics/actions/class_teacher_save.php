<?php
/**
 * Academics — Save/Delete Class Teacher Assignment (Fixed)
 * Sets is_class_teacher=1, subject_id=NULL for class (homeroom) teachers.
 */
csrf_protect();
auth_require_permission('academics_manage');

// Handle delete
$deleteId = input_int('delete');
if ($deleteId) {
    db_delete('class_teachers', 'id = ? AND is_class_teacher = 1', [$deleteId]);
    set_flash('success', 'Class teacher assignment removed.');
    redirect_back();
}

// Handle add
$class_id   = input_int('class_id');
$section_id = input_int('section_id');
$teacher_id = input_int('teacher_id');
$session_id = input_int('session_id');

if (!$class_id || !$section_id || !$teacher_id || !$session_id) {
    set_flash('error', 'All fields are required.');
    redirect_back();
}

// Check for duplicate: same class+section+session as class teacher
$existing = db_fetch_one(
    "SELECT id FROM class_teachers WHERE class_id = ? AND section_id = ? AND session_id = ? AND is_class_teacher = 1",
    [$class_id, $section_id, $session_id]
);

if ($existing) {
    // Update existing assignment
    db_update('class_teachers', [
        'teacher_id' => $teacher_id,
    ], 'id = ?', [$existing['id']]);
    set_flash('success', 'Class teacher updated for this section.');
} else {
    db_insert('class_teachers', [
        'class_id'         => $class_id,
        'section_id'       => $section_id,
        'teacher_id'       => $teacher_id,
        'session_id'       => $session_id,
        'subject_id'       => null,
        'is_class_teacher' => 1,
    ]);
    set_flash('success', 'Class teacher assigned successfully.');
}

audit_log('class_teacher_assign', "Assigned teacher {$teacher_id} as class teacher for class {$class_id} section {$section_id}");

redirect('academics', 'class-teachers', ['session_id' => $session_id]);
