<?php
/**
 * HR — Delete Employee Document
 */
csrf_protect();

$docId      = input_int('document_id');
$employeeId = input_int('employee_id');

if (!$docId) {
    set_flash('error', 'Invalid document.');
    redirect(url('hr', 'employees'));
}

$doc = db_fetch_one("SELECT * FROM hr_employee_documents WHERE id = ?", [$docId]);
if (!$doc) {
    set_flash('error', 'Document not found.');
    redirect(url('hr', 'employee-detail', $employeeId));
}

// Delete the physical file
$filePath = APP_ROOT . '/storage/uploads/' . $doc['file_path'];
if (file_exists($filePath)) {
    unlink($filePath);
}

db_delete('hr_employee_documents', 'id = ?', [$docId]);
audit_log('hr.document.delete', "Deleted document ID: {$docId} for employee ID: {$doc['employee_id']}");
set_flash('success', 'Document deleted.');
redirect(url('hr', 'employee-detail', $doc['employee_id']));
