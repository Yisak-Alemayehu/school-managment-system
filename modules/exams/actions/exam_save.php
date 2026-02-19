<?php
/**
 * Exams — Save Exam Action
 */
csrf_protect();

$id   = input_int('id');
$data = [
    'name'       => input('name'),
    'session_id' => input_int('session_id'),
    'term_id'    => input_int('term_id'),
    'start_date' => input('start_date'),
    'end_date'   => input('end_date'),
];

$errors = validate($data, [
    'name'       => 'required|max:200',
    'session_id' => 'required|integer',
    'term_id'    => 'required|integer',
    'start_date' => 'required|date',
    'end_date'   => 'required|date',
]);

if ($data['start_date'] && $data['end_date'] && $data['start_date'] > $data['end_date']) {
    $errors['end_date'] = 'End date must be on or after start date.';
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

// Auto-determine status
$today = date('Y-m-d');
if ($today < $data['start_date']) {
    $data['status'] = 'upcoming';
} elseif ($today > $data['end_date']) {
    $data['status'] = 'completed';
} else {
    $data['status'] = 'ongoing';
}

if ($id) {
    db_update('exams', $data, 'id = ?', [$id]);
    audit_log('exam.update', "Updated exam: {$data['name']}");
    set_flash('success', 'Exam updated.');
} else {
    db_insert('exams', $data);
    audit_log('exam.create', "Created exam: {$data['name']}");
    set_flash('success', 'Exam created.');
}

redirect(url('exams', 'exams'));
