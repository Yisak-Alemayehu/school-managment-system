<?php
/**
 * Results — Save Student Results
 */
csrf_protect();

$assessmentId = input_int('assessment_id');
$classId      = input_int('class_id');
$sectionId    = input_int('section_id') ?: null;
$termId       = input_int('term_id');
$subjectId    = input_int('subject_id');
$resultsData  = $_POST['results'] ?? [];

$assessment = $assessmentId
    ? db_fetch_one("SELECT * FROM assessments WHERE id = ?", [$assessmentId])
    : null;

if (!$assessment || !$classId || empty($resultsData)) {
    set_flash('error', 'Invalid data submitted.');
    redirect_back();
}

$maxMarks = (float)$assessment['total_marks'];
$enteredBy = auth_user()['id'];
$saved = 0;

db_begin();
try {
    foreach ($resultsData as $studentId => $entry) {
        $studentId = (int)$studentId;
        $isAbsent  = !empty($entry['is_absent']) ? 1 : 0;
        $remarks   = trim($entry['remarks'] ?? '');

        $marks = null;
        if (!$isAbsent && isset($entry['marks']) && $entry['marks'] !== '') {
            $marks = min((float)$entry['marks'], $maxMarks);
            $marks = max(0, $marks);
        }

        // Upsert
        $existing = db_fetch_one(
            "SELECT id FROM student_results WHERE assessment_id = ? AND student_id = ?",
            [$assessmentId, $studentId]
        );

        if ($existing) {
            db_update('student_results', [
                'marks_obtained' => $marks,
                'is_absent'      => $isAbsent,
                'remarks'        => $remarks ?: null,
                'entered_by'     => $enteredBy,
            ], 'id = ?', [$existing['id']]);
        } else {
            db_insert('student_results', [
                'assessment_id'  => $assessmentId,
                'student_id'     => $studentId,
                'class_id'       => $classId,
                'section_id'     => $sectionId,
                'marks_obtained' => $marks,
                'is_absent'      => $isAbsent,
                'remarks'        => $remarks ?: null,
                'entered_by'     => $enteredBy,
            ]);
        }
        $saved++;
    }

    db_commit();
    audit_log('results.save', "Saved results for assessment {$assessmentId}, class {$classId} ({$saved} students)");
    set_flash('success', "Results saved for {$saved} students.");
} catch (Exception $e) {
    db_rollback();
    set_flash('error', 'Failed to save results: ' . $e->getMessage());
}

$redir = url('exams', 'enter-results')
    . '&term_id=' . $termId
    . '&class_id=' . $classId
    . '&section_id=' . ($sectionId ?? 0)
    . '&subject_id=' . $subjectId
    . '&assessment_id=' . $assessmentId;

redirect($redir);
