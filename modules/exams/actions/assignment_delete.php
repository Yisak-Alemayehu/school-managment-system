<?php
/**
 * Exams — Delete Assignment Action
 */
csrf_protect();

$id = input_int('id');
if (!$id) { redirect(url('exams', 'assignments')); }

$assignment = db_fetch_one("SELECT * FROM assignments WHERE id = ?", [$id]);
if (!$assignment) {
    set_flash('error', 'Assignment not found.');
    redirect(url('exams', 'assignments'));
}

// Delete file attachment
if ($assignment['file_path']) {
    delete_upload($assignment['file_path']);
}

db_query("DELETE FROM assignment_submissions WHERE assignment_id = ?", [$id]);
db_query("DELETE FROM assignments WHERE id = ?", [$id]);

audit_log('assignment.delete', "Deleted assignment: {$assignment['title']}");
set_flash('success', 'Assignment deleted.');
redirect(url('exams', 'assignments'));
