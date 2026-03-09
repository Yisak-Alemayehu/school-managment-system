<?php
/**
 * HR — Save Leave Type
 */
csrf_protect();

$id = input_int('id');

$data = [
    'name'         => trim(input('name')),
    'code'         => strtoupper(trim(input('code'))),
    'days_allowed' => input_int('days_allowed'),
    'description'  => trim(input('description')) ?: null,
    'status'       => input('status') ?: 'active',
];

$errors = validate($data, [
    'name' => 'required|max:100',
    'code' => 'required|max:20',
]);

if (!$errors) {
    $dup = db_fetch_one(
        "SELECT id FROM hr_leave_types WHERE code = ?" . ($id ? " AND id != ?" : ""),
        $id ? [$data['code'], $id] : [$data['code']]
    );
    if ($dup) {
        $errors['code'] = 'A leave type with this code already exists.';
    }
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

if ($id) {
    db_update('hr_leave_types', $data, 'id = ?', [$id]);
    audit_log('hr.leave_type.update', "Updated leave type: {$data['name']}");
    set_flash('success', 'Leave type updated.');
} else {
    db_insert('hr_leave_types', $data);
    audit_log('hr.leave_type.create', "Created leave type: {$data['name']}");
    set_flash('success', 'Leave type created.');
}

redirect(url('hr', 'leave-types'));
