<?php
/**
 * Exams — Save Exam Schedule Action
 */
csrf_protect();

$id     = input_int('id');
$examId = input_int('exam_id');

$data = [
    'exam_id'    => $examId,
    'class_id'   => input_int('class_id'),
    'subject_id' => input_int('subject_id'),
    'exam_date'  => input('exam_date'),
    'start_time' => input('start_time'),
    'end_time'   => input('end_time') ?: null,
    'full_marks' => input_int('full_marks') ?: 100,
    'pass_marks' => input_int('pass_marks') ?: (int)(input_int('full_marks') * 0.4),
    'room'       => input('room'),
];

$errors = validate($data, [
    'exam_id'    => 'required|integer',
    'class_id'   => 'required|integer',
    'subject_id' => 'required|integer',
    'exam_date'  => 'required|date',
    'start_time' => 'required',
    'full_marks' => 'required|integer',
]);

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

if ($id) {
    db_update('exam_schedules', $data, 'id = ?', [$id]);
    audit_log('exam_schedule.update', "Updated exam schedule ID: {$id}");
    set_flash('success', 'Schedule updated.');
} else {
    db_insert('exam_schedules', $data);
    audit_log('exam_schedule.create', "Created exam schedule for exam {$examId}");
    set_flash('success', 'Schedule entry added.');
}

redirect(url('exams', 'exam-schedule') . '&exam_id=' . $examId);
