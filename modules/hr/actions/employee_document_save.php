<?php
/**
 * HR — Upload Employee Document
 */
csrf_protect();

$employeeId = input_int('employee_id');
if (!$employeeId) {
    set_flash('error', 'Invalid employee.');
    redirect(url('hr', 'employees'));
}

$emp = db_fetch_one("SELECT id FROM hr_employees WHERE id = ? AND deleted_at IS NULL", [$employeeId]);
if (!$emp) {
    set_flash('error', 'Employee not found.');
    redirect(url('hr', 'employees'));
}

$docType  = input('document_type') ?: 'other';
$docName  = trim(input('document_name'));
$notes    = trim(input('notes'));

if (!$docName) {
    set_flash('error', 'Document name is required.');
    redirect(url('hr', 'employee-detail', $employeeId));
}

if (empty($_FILES['document_file']['name'])) {
    set_flash('error', 'Please select a file to upload.');
    redirect(url('hr', 'employee-detail', $employeeId));
}

$allowed = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$file = $_FILES['document_file'];

if (!in_array($file['type'], $allowed, true)) {
    set_flash('error', 'Invalid file type. Allowed: PDF, JPEG, PNG, DOC, DOCX.');
    redirect(url('hr', 'employee-detail', $employeeId));
}

if ($file['size'] > 5 * 1024 * 1024) {
    set_flash('error', 'File size must be under 5MB.');
    redirect(url('hr', 'employee-detail', $employeeId));
}

$ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
$name = 'doc_' . $employeeId . '_' . time() . '.' . $ext;
$dir  = APP_ROOT . '/storage/uploads/hr/documents';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$dest = $dir . '/' . $name;
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    set_flash('error', 'Failed to upload file.');
    redirect(url('hr', 'employee-detail', $employeeId));
}

db_insert('hr_employee_documents', [
    'employee_id'   => $employeeId,
    'document_type' => $docType,
    'document_name' => $docName,
    'file_path'     => 'hr/documents/' . $name,
    'notes'         => $notes ?: null,
    'created_by'    => auth_user_id(),
]);

audit_log('hr.document.upload', "Uploaded document for employee ID: {$employeeId}");
set_flash('success', 'Document uploaded successfully.');
redirect(url('hr', 'employee-detail', $employeeId));
