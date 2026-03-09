<?php
/**
 * Exams — Generate Report Cards Action (Term-Based)
 * Generates report card records for all students in a class/section for a given term.
 */
csrf_protect();

$termId    = input_int('term_id');
$classId   = input_int('class_id');
$sectionId = input_int('section_id');

$activeSession = get_active_session();
$sessionId = $activeSession['id'] ?? 0;

if (!$termId || !$classId) {
    set_flash('error', 'Missing term or class.');
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
    SELECT s.id, e.section_id
    FROM students s
    JOIN enrollments e ON e.student_id = s.id
    {$where}
", $params);

if (empty($students)) {
    set_flash('error', 'No students found.');
    redirect_back();
}

// Get subjects for this class
$subjects = db_fetch_all("
    SELECT s.id FROM subjects s
    JOIN class_subjects cs ON cs.subject_id = s.id
    WHERE cs.class_id = ? AND cs.session_id = ?
", [$classId, $sessionId]);

$subjectIds = array_column($subjects, 'id');
$numSubjects = count($subjectIds);
$studentIds  = array_column($students, 'id');
$idPh = implode(',', array_fill(0, count($studentIds), '?'));

// Build section map
$sectionMap = [];
foreach ($students as $st) {
    $sectionMap[$st['id']] = $st['section_id'];
}

// Get marks: SUM all assessment marks per student/subject for this term
$marksGrid = [];
if ($subjectIds) {
    $subPh = implode(',', array_fill(0, count($subjectIds), '?'));
    $mkRows = db_fetch_all(
        "SELECT sr.student_id, a.subject_id,
                SUM(CASE WHEN sr.is_absent = 0 THEN sr.marks_obtained ELSE 0 END) AS total_marks,
                MAX(CASE WHEN sr.is_absent = 0 THEN 1 ELSE 0 END) AS has_marks
         FROM student_results sr
         JOIN assessments a ON a.id = sr.assessment_id
         WHERE a.class_id = ? AND a.term_id = ? AND a.session_id = ?
           AND a.subject_id IN ({$subPh})
           AND sr.student_id IN ({$idPh})
         GROUP BY sr.student_id, a.subject_id",
        array_merge([$classId, $termId, $sessionId], $subjectIds, $studentIds)
    );
    foreach ($mkRows as $rr) {
        $marksGrid[$rr['student_id']][$rr['subject_id']] =
            $rr['has_marks'] ? (float)$rr['total_marks'] : null;
    }
}

// Absent days
$abMap = [];
$abRows = db_fetch_all(
    "SELECT student_id, COUNT(*) AS cnt FROM attendance
     WHERE student_id IN ({$idPh}) AND session_id=? AND term_id=? AND status='absent'
     GROUP BY student_id",
    array_merge($studentIds, [$sessionId, $termId])
);
foreach ($abRows as $r) $abMap[$r['student_id']] = (int)$r['cnt'];

// Attendance days total
$attRows = db_fetch_all(
    "SELECT student_id, COUNT(*) AS cnt FROM attendance
     WHERE student_id IN ({$idPh}) AND session_id=? AND term_id=?
     GROUP BY student_id",
    array_merge($studentIds, [$sessionId, $termId])
);
$attMap = [];
foreach ($attRows as $r) $attMap[$r['student_id']] = (int)$r['cnt'];

db_begin();
try {
    $results = [];

    foreach ($students as $st) {
        $sid = $st['id'];
        $totalMarks = 0;
        $totalMaxMarks = $numSubjects * 100; // Each subject out of 100

        foreach ($subjectIds as $subjId) {
            $m = $marksGrid[$sid][$subjId] ?? null;
            if ($m !== null) $totalMarks += $m;
        }

        $percentage = $totalMaxMarks > 0 ? ($totalMarks / $totalMaxMarks) * 100 : 0;

        // Overall grade from percentage
        $overallGrade = match(true) {
            $percentage >= 90 => 'A',
            $percentage >= 80 => 'B',
            $percentage >= 70 => 'C',
            $percentage >= 60 => 'D',
            default           => 'F',
        };

        $results[] = [
            'student_id'     => $sid,
            'section_id'     => $sectionMap[$sid] ?? null,
            'total_marks'    => $totalMarks,
            'total_max_marks'=> $totalMaxMarks,
            'percentage'     => round($percentage, 2),
            'grade'          => $overallGrade,
            'attendance_days'=> $attMap[$sid] ?? 0,
            'absent_days'    => $abMap[$sid] ?? 0,
        ];
    }

    // Sort by percentage DESC for ranking
    usort($results, fn($a, $b) => $b['percentage'] <=> $a['percentage']);

    $rank = 0;
    $prevPct = null;
    foreach ($results as $i => &$r) {
        if ($r['percentage'] !== $prevPct) {
            $rank = $i + 1;
        }
        $r['rank'] = $rank;
        $prevPct = $r['percentage'];
    }

    // Save report cards
    foreach ($results as $r) {
        // Delete existing for this term
        db_query("DELETE FROM report_cards WHERE student_id = ? AND term_id = ? AND class_id = ? AND session_id = ?",
            [$r['student_id'], $termId, $classId, $sessionId]);

        db_insert('report_cards', [
            'student_id'      => $r['student_id'],
            'session_id'      => $sessionId,
            'term_id'         => $termId,
            'class_id'        => $classId,
            'section_id'      => $r['section_id'],
            'total_marks'     => $r['total_marks'],
            'total_max_marks' => $r['total_max_marks'],
            'percentage'      => $r['percentage'],
            'grade'           => $r['grade'],
            'rank'            => $r['rank'],
            'attendance_days' => $r['attendance_days'],
            'absent_days'     => $r['absent_days'],
            'status'          => 'published',
            'generated_by'    => $_SESSION['user_id'] ?? null,
            'generated_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    db_commit();
    audit_log('report_card.generate', "Generated report cards for term {$termId}, class {$classId}: " . count($students) . " students");
    set_flash('success', 'Report cards generated for ' . count($students) . ' students.');
} catch (Exception $ex) {
    db_rollback();
    set_flash('error', 'Failed to generate: ' . $ex->getMessage());
}

$redir = url('exams', 'report-cards') . "&term_id={$termId}&class_id={$classId}";
if ($sectionId) $redir .= "&section_id={$sectionId}";
redirect($redir);
