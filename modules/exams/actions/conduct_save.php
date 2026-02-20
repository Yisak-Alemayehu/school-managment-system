<?php
/**
 * Action: Save student conduct grades (behavioral, not academic).
 *
 * Expects POST:
 *   term_id    int
 *   class_id   int
 *   session_id int
 *   section_id int  (optional, for redirect only)
 *   conduct    array  [student_id => 'A'|'B'|'C'|'D'|'F']
 *   remarks    array  [student_id => string]
 */

csrf_protect();
auth_require_permission('marks.manage');

$termId    = (int)($_POST['term_id']    ?? 0);
$classId   = (int)($_POST['class_id']  ?? 0);
$sessionId = (int)($_POST['session_id'] ?? 0);
$sectionId = (int)($_POST['section_id'] ?? 0);
$conductIn = $_POST['conduct'] ?? [];
$remarksIn = $_POST['remarks'] ?? [];

if (!$termId || !$classId || !$sessionId || !is_array($conductIn)) {
    flash('error', 'Invalid request — missing required fields.');
    redirect(url('exams', 'enter-conduct'));
}

$allowed = ['A', 'B', 'C', 'D', 'F'];
$userId  = auth_user()['id'] ?? null;
$saved   = 0;
$errors  = 0;

foreach ($conductIn as $studentId => $grade) {
    $studentId = (int)$studentId;
    $grade     = strtoupper(trim((string)$grade));
    $remark    = trim((string)($remarksIn[$studentId] ?? ''));

    if (!$studentId || !in_array($grade, $allowed, true)) {
        $errors++;
        continue;
    }

    // Upsert: if record exists for this student/class/term/session, update; otherwise insert.
    $existing = db_fetch_value(
        "SELECT id FROM student_conduct
         WHERE student_id=? AND class_id=? AND session_id=? AND term_id=?",
        [$studentId, $classId, $sessionId, $termId]
    );

    if ($existing) {
        db_update('student_conduct', [
            'conduct'    => $grade,
            'remarks'    => $remark ?: null,
            'entered_by' => $userId,
        ], ['id' => $existing]);
    } else {
        db_insert('student_conduct', [
            'student_id' => $studentId,
            'class_id'   => $classId,
            'session_id' => $sessionId,
            'term_id'    => $termId,
            'conduct'    => $grade,
            'remarks'    => $remark ?: null,
            'entered_by' => $userId,
        ]);
    }

    $saved++;
}

if ($errors) {
    flash('warning', "Saved {$saved} conduct grade(s). {$errors} skipped due to invalid data.");
} else {
    flash('success', "Conduct grades saved for {$saved} student(s).");
}

$redir = url('exams', 'enter-conduct')
    . "&term_id={$termId}&class_id={$classId}"
    . ($sectionId ? "&section_id={$sectionId}" : '')
    . '&show=1';

redirect($redir);
