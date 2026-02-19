<?php
/**
 * Exams — Generate Report Cards Action
 */
csrf_protect();

$examId    = input_int('exam_id');
$classId   = input_int('class_id');
$sectionId = input_int('section_id');

$activeSession = get_active_session();
$sessionId = $activeSession['id'] ?? 0;
$activeTerm = get_active_term();
$termId = $activeTerm['id'] ?? 0;

if (!$examId || !$classId) {
    set_flash('error', 'Missing exam or class.');
    redirect_back();
}

// Get students
$where  = "WHERE e.class_id = ? AND e.session_id = ? AND e.status = 'active'";
$params = [$classId, $sessionId];
if ($sectionId) {
    $where .= " AND e.section_id = ?";
    $params[] = $sectionId;
}

$students = db_fetch_all("
    SELECT s.id, s.first_name, s.last_name
    FROM students s
    JOIN enrollments e ON e.student_id = s.id
    {$where}
", $params);

if (empty($students)) {
    set_flash('error', 'No students found.');
    redirect_back();
}

// Get exam schedules for this class + exam
$schedules = db_fetch_all("
    SELECT es.id, es.subject_id, es.full_marks, sub.name AS subject_name
    FROM exam_schedules es
    JOIN subjects sub ON sub.id = es.subject_id
    WHERE es.exam_id = ? AND es.class_id = ?
", [$examId, $classId]);

$subjectCount = count($schedules);
$maxTotal     = array_sum(array_column($schedules, 'full_marks'));

db_begin();
try {
    $results = [];

    foreach ($students as $st) {
        // Get all marks for this student in this exam
        $marks = db_fetch_all("
            SELECT m.marks_obtained, m.is_absent, m.grade, m.grade_point, es.full_marks, es.subject_id
            FROM marks m
            JOIN exam_schedules es ON es.id = m.exam_schedule_id
            WHERE m.student_id = ? AND m.exam_id = ? AND es.class_id = ?
        ", [$st['id'], $examId, $classId]);

        $totalMarks = 0;
        $totalFull  = 0;
        $totalGP    = 0;
        $subjectsGraded = 0;

        foreach ($marks as $m) {
            if (!$m['is_absent'] && $m['marks_obtained'] !== null) {
                $totalMarks += $m['marks_obtained'];
                $totalFull  += $m['full_marks'];
                if ($m['grade_point'] !== null) {
                    $totalGP += $m['grade_point'];
                    $subjectsGraded++;
                }
            }
        }

        $average = $totalFull > 0 ? ($totalMarks / $totalFull) * 100 : 0;
        $gpa     = $subjectsGraded > 0 ? $totalGP / $subjectsGraded : 0;

        // Overall grade from average
        $overallGrade = null;
        $gradeEntry = db_fetch_one("
            SELECT grade FROM grade_scale_entries gse
            JOIN grade_scales gs ON gs.id = gse.grade_scale_id
            WHERE gs.is_default = 1 AND ? BETWEEN gse.min_mark AND gse.max_mark
            LIMIT 1
        ", [$average]);
        if ($gradeEntry) {
            $overallGrade = $gradeEntry['grade'];
        }

        $results[] = [
            'student_id'  => $st['id'],
            'total_marks' => $totalMarks,
            'average'     => $average,
            'gpa'         => $gpa,
            'grade'       => $overallGrade,
        ];
    }

    // Sort by average DESC for ranking
    usort($results, fn($a, $b) => $b['average'] <=> $a['average']);

    $rank = 0;
    $prevAvg = null;
    foreach ($results as $i => &$r) {
        if ($r['average'] !== $prevAvg) {
            $rank = $i + 1;
        }
        $r['rank'] = $rank;
        $prevAvg = $r['average'];
    }

    // Save report cards
    foreach ($results as $r) {
        // Delete existing
        db_query("DELETE FROM report_cards WHERE student_id = ? AND exam_id = ? AND class_id = ?",
            [$r['student_id'], $examId, $classId]);

        db_insert('report_cards', [
            'student_id'     => $r['student_id'],
            'exam_id'        => $examId,
            'class_id'       => $classId,
            'session_id'     => $sessionId,
            'term_id'        => $termId,
            'total_marks'    => $r['total_marks'],
            'average'        => $r['average'],
            'gpa'            => $r['gpa'],
            'grade'          => $r['grade'],
            'rank'           => $r['rank'],
            'total_students' => count($students),
            'status'         => 'generated',
        ]);
    }

    db_commit();
    audit_log('report_card.generate', "Generated report cards for exam {$examId}, class {$classId}: " . count($students) . " students");
    set_flash('success', 'Report cards generated for ' . count($students) . ' students.');
} catch (Exception $ex) {
    db_rollback();
    set_flash('error', 'Failed to generate: ' . $ex->getMessage());
}

$redir = url('exams', 'report-cards') . "&exam_id={$examId}&class_id={$classId}";
if ($sectionId) $redir .= "&section_id={$sectionId}";
redirect($redir);
