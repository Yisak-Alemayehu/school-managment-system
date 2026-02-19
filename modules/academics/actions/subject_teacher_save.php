<?php
/**
 * Academics — Save Subject Teacher Assignment
 * Uses class_teachers table with is_class_teacher = 0
 */
csrf_protect();

$id   = input_int('id');
$data = [
    'teacher_id'       => input_int('teacher_id'),
    'subject_id'       => input_int('subject_id'),
    'class_id'         => input_int('class_id'),
    'section_id'       => input_int('section_id') ?: null,
    'session_id'       => input_int('session_id'),
    'is_class_teacher' => 0,
];

$errors = validate($data, [
    'teacher_id' => 'required|integer',
    'subject_id' => 'required|integer',
    'class_id'   => 'required|integer',
    'session_id' => 'required|integer',
]);

if (!$errors) {
    // Check duplicate: same teacher+subject+class+section+session
    $where = "class_id = ? AND subject_id = ? AND teacher_id = ? AND session_id = ? AND is_class_teacher = 0";
    $params = [$data['class_id'], $data['subject_id'], $data['teacher_id'], $data['session_id']];
    if ($data['section_id']) {
        $where .= " AND section_id = ?";
        $params[] = $data['section_id'];
    } else {
        $where .= " AND section_id IS NULL";
    }
    if ($id) {
        $where .= " AND id != ?";
        $params[] = $id;
    }
    $dup = db_fetch_one("SELECT id FROM class_teachers WHERE {$where}", $params);
    if ($dup) {
        $errors['teacher_id'] = 'This teacher is already assigned to this subject/class/section.';
    }
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

if ($id) {
    db_update('class_teachers', $data, 'id = ?', [$id]);
    audit_log('subject_teacher.update', "Updated subject teacher assignment ID: {$id}");
    set_flash('success', 'Subject teacher assignment updated.');
} else {
    db_insert('class_teachers', $data);
    audit_log('subject_teacher.create', "Assigned subject teacher");
    set_flash('success', 'Subject teacher assigned.');
}

redirect(url('academics', 'subject-teachers'));
