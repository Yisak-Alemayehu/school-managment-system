<?php
/**
 * Students — Delete (Soft) Action
 */
csrf_protect();
$id = route_id();

$student = db_fetch_one("SELECT id, full_name FROM students WHERE id = ? AND deleted_at IS NULL", [$id]);
if (!$student) {
    set_flash('error', 'Student not found.');
    redirect(url('students'));
}

db_soft_delete('students', 'id = ?', [$id]);

// Deactivate enrollment
db_update('enrollments', ['status' => 'withdrawn'], 'student_id = ? AND status = ?', [$id, 'active']);

audit_log('student_deleted', 'students', $id, null, ['full_name' => $student['full_name']]);

set_flash('success', "Student \"{$student['full_name']}\" has been removed.");
redirect(url('students'));
