<?php
/**
 * HR — Save Department Action
 */
csrf_protect();

$id = input_int('id');

$data = [
    'name'                   => trim(input('name')),
    'code'                   => strtoupper(trim(input('code'))) ?: null,
    'head_of_department_id'  => input_int('head_of_department_id') ?: null,
    'description'            => trim(input('description')) ?: null,
    'status'                 => input('status') ?: 'active',
    'updated_by'             => auth_user_id(),
];

$errors = validate($data, [
    'name' => 'required|max:100',
]);

if (!$errors) {
    // Check duplicate name
    $dup = db_fetch_one(
        "SELECT id FROM hr_departments WHERE name = ? AND deleted_at IS NULL" . ($id ? " AND id != ?" : ""),
        $id ? [$data['name'], $id] : [$data['name']]
    );
    if ($dup) {
        $errors['name'] = 'A department with this name already exists.';
    }
}

if (!$errors && $data['code']) {
    // Check duplicate code
    $dupCode = db_fetch_one(
        "SELECT id FROM hr_departments WHERE code = ? AND deleted_at IS NULL" . ($id ? " AND id != ?" : ""),
        $id ? [$data['code'], $id] : [$data['code']]
    );
    if ($dupCode) {
        $errors['code'] = 'A department with this code already exists.';
    }
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

if ($id) {
    db_update('hr_departments', $data, 'id = ?', [$id]);
    audit_log('hr.department.update', "Updated department: {$data['name']}");
    set_flash('success', 'Department updated.');
} else {
    $data['created_by'] = auth_user_id();
    db_insert('hr_departments', $data);
    audit_log('hr.department.create', "Created department: {$data['name']}");
    set_flash('success', 'Department created.');
}

redirect(url('hr', 'departments'));
