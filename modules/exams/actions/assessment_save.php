<?php
/**
 * Results — Save Assessment
 * Each assessment has its own marks weight. The sum across all assessments
 * for a given class + subject + term must not exceed 100.
 */
csrf_protect();

$name        = trim(input('name'));
$classId     = input_int('class_id');
$subjectId   = input_int('subject_id');
$termId      = input_int('term_id') ?: null;
$totalMarks  = input_int('total_marks');
$description = trim(input('description'));

$activeSession = get_active_session();
$sessionId     = $activeSession['id'] ?? 0;

$backUrl = url('exams', 'add-assessment');

// ── Basic validation ──
if (!$name) {
    set_flash('error', 'Assessment name is required.');
    redirect($backUrl);
}
if (!$classId || !$subjectId || !$sessionId) {
    set_flash('error', 'Class and subject are required.');
    redirect($backUrl);
}
if ($totalMarks < 1 || $totalMarks > 100) {
    set_flash('error', 'Marks must be between 1 and 100.');
    redirect($backUrl);
}

// ── Sum check: existing committed + new marks must not exceed 100 ──
$termWhere  = $termId ? ' AND term_id = ?' : ' AND term_id IS NULL';
$termParams = $termId ? [$sessionId, $classId, $subjectId, $termId] : [$sessionId, $classId, $subjectId];

$currentSum = (int) db_fetch_value(
    "SELECT COALESCE(SUM(total_marks), 0) FROM assessments
     WHERE session_id = ? AND class_id = ? AND subject_id = ?{$termWhere}",
    $termParams
);

$newSum = $currentSum + $totalMarks;

if ($newSum > 100) {
    $remaining = 100 - $currentSum;
    set_flash('error',
        "Cannot save: total marks for all assessments in this subject would be {$newSum}/100. "
        . "Only {$remaining} mark(s) remaining. Reduce the marks for this assessment."
    );
    redirect($backUrl);
}

// ── Insert ──
try {
    db_insert('assessments', [
        'name'        => $name,
        'description' => $description ?: null,
        'class_id'    => $classId,
        'subject_id'  => $subjectId,
        'session_id'  => $sessionId,
        'term_id'     => $termId,
        'total_marks' => $totalMarks,
        'created_by'  => auth_user()['id'],
    ]);

    audit_log('assessment.create', "Created assessment '{$name}' ({$totalMarks} marks) for class {$classId}, subject {$subjectId}");

    if ($newSum === 100) {
        set_flash('success', "Assessment '{$name}' saved. Total is now 100/100 ✓ — subject is fully set up.");
    } else {
        set_flash('success', "Assessment '{$name}' saved ({$newSum}/100 committed). Add more assessments to reach 100.");
    }
} catch (Exception $e) {
    set_flash('error', 'Failed to save assessment: ' . $e->getMessage());
}

redirect($backUrl);
