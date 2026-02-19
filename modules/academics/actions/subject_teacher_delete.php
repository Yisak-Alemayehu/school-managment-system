<?php
/**
 * Academics — Delete Subject Teacher Assignment
 */
csrf_protect();

$id = input_int('id');
if ($id) {
    $row = db_fetch_one("SELECT * FROM class_teachers WHERE id = ? AND is_class_teacher = 0", [$id]);
    if ($row) {
        db_delete('class_teachers', 'id = ?', [$id]);
        audit_log('subject_teacher.delete', "Removed subject teacher assignment ID: {$id}");
        set_flash('success', 'Subject teacher assignment removed.');
    }
}
redirect(url('academics', 'subject-teachers'));
