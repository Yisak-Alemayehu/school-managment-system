<?php
/**
 * Academics — Save Stream Action
 */
csrf_protect();

$id   = input_int('id');
$data = [
    'name'        => input('name'),
    'description' => input('description'),
    'sort_order'  => input_int('sort_order'),
    'is_active'   => input_int('is_active'),
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
    db_update('streams', $data, 'id = ?', [$id]);
    audit_log('stream.update', "Updated stream: {$data['name']}");
    set_flash('success', 'Stream updated.');
} else {
    db_insert('streams', $data);
    audit_log('stream.create', "Created stream: {$data['name']}");
    set_flash('success', 'Stream created.');
}

redirect(url('academics', 'streams'));
