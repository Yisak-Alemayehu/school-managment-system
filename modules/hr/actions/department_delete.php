<?php
/**
 * HR — Soft Delete Department
 */
csrf_protect();

$id = input_int('id');
if (!$id) {
    set_flash('error', 'Invalid department ID.');
    redirect(url('hr', 'departments'));
}

$dept = db_fetch_one("SELECT name FROM hr_departments WHERE id = ? AND deleted_at IS NULL", [$id]);
if (!$dept) {
    set_flash('error', 'Department not found.');
    redirect(url('hr', 'departments'));
}

// Check if department has active employees
$empCount = db_count('hr_employees', 'department_id = ? AND deleted_at IS NULL AND status = ?', [$id, 'active']);
if ($empCount > 0) {
    set_flash('error', "Cannot delete department \"{$dept['name']}\" — it has {$empCount} active employee(s). Reassign them first.");
    redirect(url('hr', 'departments'));
}

db_soft_delete('hr_departments', 'id = ?', [$id]);
audit_log('hr.department.delete', "Soft-deleted department: {$dept['name']}");
set_flash('success', 'Department removed successfully.');

redirect(url('hr', 'departments'));
