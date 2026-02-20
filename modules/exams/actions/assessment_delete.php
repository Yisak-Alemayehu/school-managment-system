<?php
/**
 * Results — Delete Assessment
 */
csrf_protect();

$id = input_int('id');
if (!$id) {
    set_flash('error', 'Invalid assessment.');
    redirect(url('exams', 'add-assessment'));
}

$assessment = db_fetch_one("SELECT * FROM assessments WHERE id = ?", [$id]);
if (!$assessment) {
    set_flash('error', 'Assessment not found.');
    redirect(url('exams', 'add-assessment'));
}

try {
    db_delete('assessments', 'id = ?', [$id]);
    audit_log('assessment.delete', "Deleted assessment ID {$id}: {$assessment['name']}");
    set_flash('success', "Assessment '{$assessment['name']}' deleted.");
} catch (Exception $e) {
    set_flash('error', 'Failed to delete: ' . $e->getMessage());
}

redirect(url('exams', 'add-assessment'));
