<?php
/**
 * Academics — Save Class Action (Fixed)
 * Uses numeric_name instead of level, adds slug + medium/stream/shift.
 */
csrf_protect();

$id   = input_int('id');
$name = trim(input('name'));
$slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-'));

$data = [
    'name'         => $name,
    'slug'         => $slug,
    'numeric_name' => input_int('numeric_name') ?: null,
    'sort_order'   => input_int('numeric_name') ?: 0,
    'medium_id'    => input_int('medium_id') ?: null,
    'stream_id'    => input_int('stream_id') ?: null,
    'shift_id'     => input_int('shift_id') ?: null,
];

$errors = validate($data, [
    'name' => 'required|max:100',
]);

if (!$errors) {
    // Check duplicate name
    $dup = db_fetch_one(
        "SELECT id FROM classes WHERE name = ?" . ($id ? " AND id != ?" : ""),
        $id ? [$name, $id] : [$name]
    );
    if ($dup) {
        $errors['name'] = 'A class with this name already exists.';
    }
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

if ($id) {
    db_update('classes', $data, 'id = ?', [$id]);
    audit_log('class.update', "Updated class: {$name}");
    set_flash('success', 'Class updated.');
} else {
    db_insert('classes', $data);
    audit_log('class.create', "Created class: {$name}");
    set_flash('success', 'Class created.');
}

redirect(url('academics', 'classes'));
