<?php
/**
 * Academics — Save Subject Action (Fixed)
 * Type uses theory/practical/both to match DB ENUM.
 */
csrf_protect();

$id   = input_int('id');
$data = [
    'name'        => input('name'),
    'code'        => strtoupper(trim(input('code'))),
    'type'        => in_array(input('type'), ['theory', 'practical', 'both']) ? input('type') : 'theory',
    'description' => input('description'),
];

$errors = validate($data, [
    'name' => 'required|max:100',
    'code' => 'required|max:20',
]);

if (!$errors) {
    $dup = db_fetch_one(
        "SELECT id FROM subjects WHERE code = ?" . ($id ? " AND id != ?" : ""),
        $id ? [$data['code'], $id] : [$data['code']]
    );
    if ($dup) {
        $errors['code'] = 'Subject code already exists.';
    }
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

if ($id) {
    db_update('subjects', $data, 'id = ?', [$id]);
    audit_log('subject.update', "Updated subject: {$data['name']}");
    set_flash('success', 'Subject updated.');
} else {
    db_insert('subjects', $data);
    audit_log('subject.create', "Created subject: {$data['name']}");
    set_flash('success', 'Subject created.');
}

redirect(url('academics', 'subjects'));
