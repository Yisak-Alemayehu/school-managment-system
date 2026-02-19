<?php
/**
 * Academics — Save / Delete Timetable Slot
 */
csrf_protect();

// Delete mode
$deleteId = input_int('delete_id');
if ($deleteId) {
    $slot = db_fetch_one("SELECT * FROM timetables WHERE id = ?", [$deleteId]);
    if ($slot) {
        db_query("DELETE FROM timetables WHERE id = ?", [$deleteId]);
        audit_log('timetable.delete', "Deleted timetable slot ID: {$deleteId}");
        set_flash('success', 'Slot removed.');
    }
    redirect_back();
}

// Create mode
$data = [
    'class_id'    => input_int('class_id'),
    'section_id'  => input_int('section_id') ?: null,
    'subject_id'  => input_int('subject_id'),
    'teacher_id'  => input_int('teacher_id') ?: null,
    'session_id'  => input_int('session_id'),
    'day_of_week' => strtolower(input('day_of_week')),
    'start_time'  => input('start_time'),
    'end_time'    => input('end_time'),
    'room'        => input('room'),
];

$errors = validate($data, [
    'class_id'    => 'required|integer',
    'subject_id'  => 'required|integer',
    'session_id'  => 'required|integer',
    'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
    'start_time'  => 'required',
    'end_time'    => 'required',
]);

if ($data['start_time'] && $data['end_time'] && $data['start_time'] >= $data['end_time']) {
    $errors['end_time'] = 'End time must be after start time.';
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

db_insert('timetables', $data);
audit_log('timetable.create', "Added timetable slot for class {$data['class_id']}");
set_flash('success', 'Timetable slot added.');

$redir = url('academics', 'timetable') . '&class_id=' . $data['class_id'];
if ($data['section_id']) $redir .= '&section_id=' . $data['section_id'];
redirect($redir);
