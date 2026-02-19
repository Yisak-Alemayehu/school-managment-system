<?php
/**
 * Academics — Save Term Action
 */
csrf_protect();

$id   = input_int('id');
$name = input('name');
$slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-'));

$data = [
    'session_id' => input_int('session_id'),
    'name'       => $name,
    'slug'       => $slug,
    'start_date' => input('start_date'),
    'end_date'   => input('end_date'),
];

$errors = validate($data, [
    'session_id' => 'required|integer',
    'name'       => 'required|max:100',
    'start_date' => 'required|date',
    'end_date'   => 'required|date',
]);

if ($data['start_date'] && $data['end_date'] && $data['start_date'] >= $data['end_date']) {
    $errors['end_date'] = 'End date must be after start date.';
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

if ($id) {
    db_update('terms', $data, 'id = ?', [$id]);
    audit_log('term.update', "Updated term: {$data['name']}");
    set_flash('success', 'Term updated.');
} else {
    db_insert('terms', $data);
    audit_log('term.create', "Created term: {$data['name']}");
    set_flash('success', 'Term created.');
}

redirect(url('academics', 'terms') . '&session_id=' . $data['session_id']);
