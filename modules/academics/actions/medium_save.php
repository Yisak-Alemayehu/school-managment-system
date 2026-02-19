<?php
/**
 * Academics — Save Medium Action
 */
csrf_protect();

$id   = input_int('id');
$data = [
    'name'       => input('name'),
    'sort_order' => input_int('sort_order'),
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
    db_update('mediums', $data, 'id = ?', [$id]);
    audit_log('medium.update', "Updated medium: {$data['name']}");
    set_flash('success', 'Medium updated.');
} else {
    db_insert('mediums', $data);
    audit_log('medium.create', "Created medium: {$data['name']}");
    set_flash('success', 'Medium created.');
}

redirect(url('academics', 'mediums'));
