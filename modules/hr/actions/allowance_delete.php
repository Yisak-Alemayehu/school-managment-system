<?php
/**
 * HR — Delete Employee Allowance (soft-deactivate)
 */
csrf_protect();

$id = input_int('id');

if (!$id) {
    set_flash('error', 'Invalid allowance.');
    redirect_back();
}

$allowance = db_fetch_one("SELECT * FROM hr_employee_allowances WHERE id = ?", [$id]);
if (!$allowance) {
    set_flash('error', 'Allowance not found.');
    redirect_back();
}

db_update('hr_employee_allowances', ['status' => 'inactive'], 'id = ?', [$id]);

audit_log('hr.allowance.delete', "Deactivated allowance: {$allowance['name']} for employee ID: {$allowance['employee_id']}");
set_flash('success', 'Allowance removed.');

redirect(url('hr', 'employee-detail', $allowance['employee_id']));
