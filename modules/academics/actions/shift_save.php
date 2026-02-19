<?php
/**
 * Academics — Save Shift Action
 */
csrf_protect();

$id   = input_int('id');
$data = [
    'name'       => input('name'),
    'start_time' => input('start_time') ?: null,
    'end_time'   => input('end_time') ?: null,
    'is_active'  => input_int('is_active'),
];

$errors = validate($data, [
    'name' => 'required|max:100',
]);

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

if ($id) {
    db_update('shifts', $data, 'id = ?', [$id]);
    audit_log('shift.update', "Updated shift: {$data['name']}");
    set_flash('success', 'Shift updated.');
} else {
    db_insert('shifts', $data);
    audit_log('shift.create', "Created shift: {$data['name']}");
    set_flash('success', 'Shift created.');
}

redirect(url('academics', 'shifts'));
