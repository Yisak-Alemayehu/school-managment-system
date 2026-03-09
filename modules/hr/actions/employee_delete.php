<?php
/**
 * HR — Soft Delete Employee
 */
csrf_protect();

$id = input_int('id');
if (!$id) {
    set_flash('error', 'Invalid employee ID.');
    redirect(url('hr', 'employees'));
}

$emp = db_fetch_one("SELECT employee_id FROM hr_employees WHERE id = ? AND deleted_at IS NULL", [$id]);
if (!$emp) {
    set_flash('error', 'Employee not found.');
    redirect(url('hr', 'employees'));
}

db_soft_delete('hr_employees', 'id = ?', [$id]);
audit_log('hr.employee.delete', "Soft-deleted employee: {$emp['employee_id']}");
set_flash('success', 'Employee removed successfully.');

redirect(url('hr', 'employees'));
