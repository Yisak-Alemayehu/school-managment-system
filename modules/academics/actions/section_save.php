<?php
/**
 * Academics — Save Section Action
 */
csrf_protect();

$id   = input_int('id');
$data = [
    'class_id' => input_int('class_id'),
    'name'     => input('name'),
    'capacity' => input_int('capacity') ?: 40,
];

$errors = validate($data, [
    'class_id' => 'required|integer',
    'name'     => 'required|max:20',
]);

if (!$errors) {
    // Check duplicate section name in same class
    $dup = db_fetch_one(
        "SELECT id FROM sections WHERE class_id = ? AND name = ?" . ($id ? " AND id != ?" : ""),
        $id ? [$data['class_id'], $data['name'], $id] : [$data['class_id'], $data['name']]
    );
    if ($dup) {
        $errors['name'] = 'This section already exists in the selected class.';
    }
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

if ($id) {
    db_update('sections', $data, 'id = ?', [$id]);
    audit_log('section.update', "Updated section: {$data['name']}");
    set_flash('success', 'Section updated.');
} else {
    db_insert('sections', $data);
    audit_log('section.create', "Created section: {$data['name']}");
    set_flash('success', 'Section created.');
}

redirect(url('academics', 'sections') . '&class_id=' . $data['class_id']);
