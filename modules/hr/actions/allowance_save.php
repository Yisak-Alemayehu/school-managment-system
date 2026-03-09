<?php
/**
 * HR — Save Employee Allowance
 * Create or update a recurring allowance for an employee.
 */
csrf_protect();

$id = input_int('id');

$data = [
    'employee_id'    => input_int('employee_id'),
    'allowance_type' => input('allowance_type') ?: 'other',
    'name'           => trim(input('name')),
    'amount'         => (float)input('amount'),
    'is_taxable'     => input_int('is_taxable') !== 0 ? 1 : 0,
    'is_permanent'   => input_int('is_permanent') !== 0 ? 1 : 0,
    'start_date'     => trim(input('start_date')) ?: null,
    'end_date'       => trim(input('end_date')) ?: null,
    'status'         => input('status') ?: 'active',
    'notes'          => trim(input('notes')) ?: null,
];

$errors = validate($data, [
    'employee_id'    => 'required',
    'allowance_type' => 'required',
    'name'           => 'required|max:100',
    'amount'         => 'required',
]);

if ($data['amount'] <= 0) {
    $errors['amount'] = 'Amount must be greater than zero.';
}

// Validate employee exists
if (!$errors && $data['employee_id']) {
    $emp = db_fetch_one("SELECT id, employee_id FROM hr_employees WHERE id = ? AND deleted_at IS NULL", [$data['employee_id']]);
    if (!$emp) {
        $errors['employee_id'] = 'Employee not found.';
    }
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

if ($id) {
    unset($data['employee_id']); // Don't change employee on update
    db_update('hr_employee_allowances', $data, 'id = ?', [$id]);
    audit_log('hr.allowance.update', "Updated allowance: {$data['name']}");
    set_flash('success', 'Allowance updated.');
} else {
    $data['created_by'] = auth_user_id();
    db_insert('hr_employee_allowances', $data);
    audit_log('hr.allowance.create', "Created allowance: {$data['name']} for employee ID: {$data['employee_id']}");
    set_flash('success', 'Allowance added.');
}

redirect(url('hr', 'employee-detail', $data['employee_id'] ?? input_int('employee_id')));
