<?php
/**
 * Academics — Save Subject Teacher Assignment
 * Uses class_teachers table with is_class_teacher = 0
 */
csrf_protect();

$id   = input_int('id');
$teacherId    = input_int('teacher_id');
$subjectIds   = array_filter(array_map('intval', $_POST['subject_ids'] ?? []));
$sectionIds   = array_filter(array_map('intval', $_POST['section_ids'] ?? []));
$sessionId    = input_int('session_id');

// Single-edit mode
if ($id) {
    $data = [
        'teacher_id'       => $teacherId,
        'subject_id'       => input_int('subject_id'),
        'class_id'         => input_int('class_id'),
        'section_id'       => input_int('section_id') ?: null,
        'session_id'       => $sessionId,
        'is_class_teacher' => 0,
    ];

    $errors = validate($data, [
        'teacher_id' => 'required|integer',
        'subject_id' => 'required|integer',
        'class_id'   => 'required|integer',
        'session_id' => 'required|integer',
    ]);

    if (!$errors) {
        // Prevent assigning the same subject/class/section to another teacher
        $where = "class_id = ? AND subject_id = ? AND session_id = ? AND is_class_teacher = 0";
        $params = [$data['class_id'], $data['subject_id'], $data['session_id']];
        if ($data['section_id']) {
            $where .= " AND section_id = ?";
            $params[] = $data['section_id'];
        } else {
            $where .= " AND section_id IS NULL";
        }
        $where .= " AND teacher_id != ?";
        $params[] = $data['teacher_id'];
        $where .= " AND id != ?";
        $params[] = $id;

        $conflict = db_fetch_one("SELECT id FROM class_teachers WHERE {$where}", $params);
        if ($conflict) {
            $errors['teacher_id'] = 'Another teacher is already assigned to this subject/class/section.';
        }
    }

    if ($errors) {
        set_validation_errors($errors);
        set_old_input();
        redirect_back();
    }

    db_update('class_teachers', $data, 'id = ?', [$id]);
    audit_log('subject_teacher.update', "Updated subject teacher assignment ID: {$id}");
    set_flash('success', 'Subject teacher assignment updated.');
    redirect(url('academics', 'subject-teachers'));
    exit;
}

// Bulk assignment (multiple subjects + sections)
$errors = [];
if (!$teacherId) {
    $errors['teacher_id'] = 'Please select a teacher.';
}
if (empty($subjectIds)) {
    $errors['subject_ids'] = 'Please select at least one subject.';
}
if (empty($sectionIds)) {
    $errors['section_ids'] = 'Please select at least one class/section.';
}
if (!$sessionId) {
    $errors['session_id'] = 'Invalid academic session.';
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

$assigned = 0;
$conflicts = [];
foreach ($subjectIds as $subjectId) {
    foreach ($sectionIds as $sectionId) {
        $section = db_fetch_one('SELECT class_id FROM sections WHERE id = ?', [$sectionId]);
        if (!$section) continue;
        $classId = $section['class_id'];

        // Check for conflicting assignment (same subject/class/section, different teacher)
        $conflict = db_fetch_one(
            "SELECT teacher_id FROM class_teachers WHERE class_id = ? AND section_id = ? AND subject_id = ? AND session_id = ? AND is_class_teacher = 0",
            [$classId, $sectionId, $subjectId, $sessionId]
        );
        if ($conflict && $conflict['teacher_id'] != $teacherId) {
            $conflicts[] = "Subject ID {$subjectId} is already assigned for section {$sectionId}.";
            continue;
        }

        // Skip if already assigned to this teacher
        $exists = db_fetch_one(
            "SELECT id FROM class_teachers WHERE class_id = ? AND section_id = ? AND subject_id = ? AND teacher_id = ? AND session_id = ? AND is_class_teacher = 0",
            [$classId, $sectionId, $subjectId, $teacherId, $sessionId]
        );
        if ($exists) {
            continue;
        }

        db_insert('class_teachers', [
            'class_id'         => $classId,
            'section_id'       => $sectionId,
            'subject_id'       => $subjectId,
            'teacher_id'       => $teacherId,
            'session_id'       => $sessionId,
            'is_class_teacher' => 0,
        ]);
        $assigned++;
    }
}

$msg = "Assigned subjects to teacher. {$assigned} assignment(s) created.";
if (!empty($conflicts)) {
    $msg .= ' Some selections were skipped: ' . implode(' ', array_unique($conflicts));
}
set_flash('success', $msg);
redirect(url('academics', 'subject-teachers'));
