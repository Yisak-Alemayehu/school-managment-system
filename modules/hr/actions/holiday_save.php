<?php
/**
 * HR — Save Holiday
 */
csrf_protect();

$id = input_int('id');

$dateEc   = trim(input('date_ec'));
$dateGreg = trim(input('date_gregorian'));

// Auto-convert dates
if ($dateEc && !$dateGreg) {
    $dateGreg = ec_str_to_gregorian($dateEc);
}
if ($dateGreg && !$dateEc) {
    $dateEc = gregorian_str_to_ec($dateGreg);
}

$data = [
    'name'           => trim(input('name')),
    'date_ec'        => $dateEc ?: null,
    'date_gregorian' => $dateGreg,
    'is_recurring'   => input('is_recurring') ? 1 : 0,
    'year'           => input_int('year') ?: null,
    'description'    => trim(input('description')) ?: null,
];

$errors = validate($data, [
    'name'           => 'required|max:100',
    'date_gregorian' => 'required',
]);

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

if ($id) {
    db_update('hr_holidays', $data, 'id = ?', [$id]);
    audit_log('hr.holiday.update', "Updated holiday: {$data['name']}");
    set_flash('success', 'Holiday updated.');
} else {
    db_insert('hr_holidays', $data);
    audit_log('hr.holiday.create', "Created holiday: {$data['name']}");
    set_flash('success', 'Holiday created.');
}

redirect(url('hr', 'holidays'));
