<?php
/**
 * Save Roll Numbers Action
 */
csrf_protect();

$sectionId = (int)($_POST['section_id'] ?? 0);
$rolls     = $_POST['roll'] ?? [];

if (!$sectionId || empty($rolls)) {
    set_flash('error', 'Invalid request.');
    redirect(url('students', 'roll-numbers'));
}

$updated = 0;
foreach ($rolls as $studentId => $rollNo) {
    $studentId = (int)$studentId;
    $rollNo    = trim($rollNo) !== '' ? (int)$rollNo : null;
    if (!$studentId) continue;

    db_query(
        "UPDATE enrollments SET roll_no = ? WHERE student_id = ? AND section_id = ? AND status = 'active'",
        [$rollNo, $studentId, $sectionId]
    );
    $updated++;
}

set_flash('success', "Roll numbers saved for $updated student(s).");
redirect(url('students', 'roll-numbers') . '&section_id=' . $sectionId);
