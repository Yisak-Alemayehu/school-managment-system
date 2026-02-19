<?php
/**
 * Exams — Save Marks Action
 */
csrf_protect();

$scheduleId = input_int('exam_schedule_id');
$marksData  = $_POST['marks'] ?? [];

if (!$scheduleId || empty($marksData)) {
    set_flash('error', 'Invalid marks data.');
    redirect_back();
}

$schedule = db_fetch_one("
    SELECT es.*, e.session_id, e.term_id
    FROM exam_schedules es
    JOIN exams e ON e.id = es.exam_id
    WHERE es.id = ?
", [$scheduleId]);

if (!$schedule) {
    set_flash('error', 'Exam schedule not found.');
    redirect_back();
}

db_begin();
try {
    $count = 0;
    foreach ($marksData as $entry) {
        $studentId = (int)($entry['student_id'] ?? 0);
        if (!$studentId) continue;

        $isAbsent = isset($entry['absent']) && $entry['absent'];
        $score    = $isAbsent ? null : (isset($entry['score']) && $entry['score'] !== '' ? (float)$entry['score'] : null);

        // Validate marks don't exceed full marks
        if ($score !== null && $score > $schedule['full_marks']) {
            $score = $schedule['full_marks'];
        }

        // Calculate grade
        $grade = null;
        $gradePoint = null;
        if ($score !== null) {
            $pct = ($score / $schedule['full_marks']) * 100;
            $gradeEntry = db_fetch_one("
                SELECT gse.grade, gse.grade_point
                FROM grade_scale_entries gse
                JOIN grade_scales gs ON gs.id = gse.grade_scale_id
                WHERE gs.is_default = 1 AND ? BETWEEN gse.min_mark AND gse.max_mark
                LIMIT 1
            ", [$pct]);
            if ($gradeEntry) {
                $grade      = $gradeEntry['grade'];
                $gradePoint = $gradeEntry['grade_point'];
            }
        }

        $data = [
            'student_id'       => $studentId,
            'exam_schedule_id' => $scheduleId,
            'exam_id'          => $schedule['exam_id'],
            'subject_id'       => $schedule['subject_id'],
            'class_id'         => $schedule['class_id'],
            'session_id'       => $schedule['session_id'],
            'term_id'          => $schedule['term_id'],
            'marks_obtained'   => $score,
            'is_absent'        => $isAbsent ? 1 : 0,
            'grade'            => $grade,
            'grade_point'      => $gradePoint,
        ];

        // Upsert
        $existing = db_fetch_one(
            "SELECT id FROM marks WHERE student_id = ? AND exam_schedule_id = ?",
            [$studentId, $scheduleId]
        );

        if ($existing) {
            db_update('marks', $data, 'id = ?', [$existing['id']]);
        } else {
            db_insert('marks', $data);
        }
        $count++;
    }

    db_commit();
    audit_log('marks.save', "Saved marks for schedule {$scheduleId}: {$count} students");
    set_flash('success', "Marks saved for {$count} students.");
} catch (Exception $ex) {
    db_rollback();
    set_flash('error', 'Failed to save marks: ' . $ex->getMessage());
}

redirect(url('exams', 'marks') . '&schedule_id=' . $scheduleId);
