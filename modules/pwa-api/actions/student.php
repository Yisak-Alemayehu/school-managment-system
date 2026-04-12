<?php
/**
 * PWA API — Student Data Actions
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// ─────────────────────────────────────────────────────────────
// GET /pwa-api/student-dashboard
// ─────────────────────────────────────────────────────────────
function pwa_student_dashboard(array $apiUser): never
{
    $studentId = (int) $apiUser['linked_id'];

    // Active enrollment
    $enrollment = db_fetch_one(
        "SELECT e.*, c.name AS class_name, c.id AS class_id,
                sec.name AS section_name, sec.id AS section_id,
                acs.name AS session_name, acs.id AS session_id,
                t.name AS term_name, t.id AS term_id
         FROM enrollments e
         JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
         JOIN classes c ON c.id = e.class_id
         LEFT JOIN sections sec ON sec.id = e.section_id
         LEFT JOIN terms t ON t.session_id = acs.id AND t.is_active = 1
         WHERE e.student_id = ? AND e.status = 'active'
         ORDER BY e.id DESC LIMIT 1",
        [$studentId]
    );

    // Attendance summary (current session)
    $attSummary = ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0];
    if ($enrollment) {
        $rows = db_fetch_all(
            "SELECT status, COUNT(*) AS cnt
             FROM attendance
             WHERE student_id = ? AND session_id = ? AND subject_id IS NULL
             GROUP BY status",
            [$studentId, $enrollment['session_id']]
        );
        foreach ($rows as $r) {
            $attSummary[$r['status']] = (int) $r['cnt'];
        }
        $attSummary['total'] = array_sum(array_values($attSummary));
        $attSummary['percentage'] = $attSummary['total']
            ? round(($attSummary['present'] + $attSummary['late']) / $attSummary['total'] * 100, 1)
            : 0;
    }

    // Recent exam results (last 5)
    $recentResults = db_fetch_all(
        "SELECT m.marks_obtained, m.max_marks, m.is_absent,
                s.name AS subject_name, e.name AS exam_name, e.type AS exam_type
         FROM marks m
         JOIN subjects s ON s.id = m.subject_id
         JOIN exams e ON e.id = m.exam_id
         WHERE m.student_id = ?
         ORDER BY m.created_at DESC LIMIT 5",
        [$studentId]
    );

    // Recent notices (last 3)
    $notices = db_fetch_all(
        "SELECT id, title, content, created_at
         FROM announcements
         WHERE (audience = 'all' OR audience = 'students')
           AND is_active = 1
         ORDER BY created_at DESC LIMIT 3",
        []
    );

    // Upcoming exams
    $upcomingExams = [];
    if ($enrollment) {
        $upcomingExams = db_fetch_all(
            "SELECT es.exam_date, es.start_time, es.end_time,
                    s.name AS subject_name, e.name AS exam_name
             FROM exam_schedules es
             JOIN exams e ON e.id = es.exam_id
             JOIN subjects s ON s.id = es.subject_id
             WHERE es.class_id = ? AND e.status = 'upcoming'
               AND es.exam_date >= CURDATE()
             ORDER BY es.exam_date ASC LIMIT 5",
            [$enrollment['class_id']]
        );
    }

    pwa_json([
        'enrollment'    => $enrollment,
        'attendance'    => $attSummary,
        'recent_results' => $recentResults,
        'notices'       => $notices,
        'upcoming_exams' => $upcomingExams,
    ]);
}

// ─────────────────────────────────────────────────────────────
// GET /pwa-api/student-attendance?month=YYYY-MM
// ─────────────────────────────────────────────────────────────
function pwa_student_attendance(array $apiUser): never
{
    $studentId = (int) $apiUser['linked_id'];
    $month     = $_GET['month'] ?? date('Y-m');

    // Validate month format
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        $month = date('Y-m');
    }

    $startDate = $month . '-01';
    $endDate   = date('Y-m-t', strtotime($startDate));

    // Daily attendance
    $records = db_fetch_all(
        "SELECT a.date, a.status, a.remarks,
                COALESCE(s.name, 'General') AS subject_name
         FROM attendance a
         LEFT JOIN subjects s ON s.id = a.subject_id
         WHERE a.student_id = ? AND a.date BETWEEN ? AND ?
         ORDER BY a.date ASC, a.period ASC",
        [$studentId, $startDate, $endDate]
    );

    // Monthly summary (current session)
    $enrollment = db_fetch_one(
        "SELECT e.session_id FROM enrollments e
         JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
         WHERE e.student_id = ? AND e.status = 'active' LIMIT 1",
        [$studentId]
    );

    $summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'half_day' => 0];
    if ($enrollment) {
        $rows = db_fetch_all(
            "SELECT status, COUNT(*) AS cnt FROM attendance
             WHERE student_id = ? AND session_id = ? AND subject_id IS NULL
             GROUP BY status",
            [$studentId, $enrollment['session_id']]
        );
        foreach ($rows as $r) {
            $summary[$r['status']] = (int) $r['cnt'];
        }
    }
    $total = array_sum(array_values($summary));
    $summary['total'] = $total;
    $summary['percentage'] = $total
        ? round(($summary['present'] + $summary['late']) / $total * 100, 1)
        : 0;

    pwa_json([
        'month'   => $month,
        'records' => $records,
        'summary' => $summary,
    ]);
}

// ─────────────────────────────────────────────────────────────
// GET /pwa-api/student-results?exam_id=X
// ─────────────────────────────────────────────────────────────
function pwa_student_results(array $apiUser): never
{
    $studentId = (int) $apiUser['linked_id'];
    $examId    = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : null;

    // Get enrollment
    $enrollment = db_fetch_one(
        "SELECT e.class_id, e.session_id, acs.name AS session_name
         FROM enrollments e
         JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
         WHERE e.student_id = ? AND e.status = 'active'
         ORDER BY e.id DESC LIMIT 1",
        [$studentId]
    );

    // Available exams for this student
    $exams = [];
    if ($enrollment) {
        $exams = db_fetch_all(
            "SELECT DISTINCT e.id, e.name, e.type, e.start_date, e.status
             FROM exams e
             JOIN marks m ON m.exam_id = e.id AND m.student_id = ?
             WHERE e.session_id = ?
             ORDER BY e.start_date DESC",
            [$studentId, $enrollment['session_id']]
        );
    }

    // Marks for selected exam
    $marks = [];
    if ($examId) {
        // Verify student has marks in this exam
        $marks = db_fetch_all(
            "SELECT m.marks_obtained, m.max_marks, m.is_absent, m.remarks,
                    s.name AS subject_name, s.code AS subject_code,
                    CASE WHEN m.max_marks > 0 AND m.is_absent = 0
                         THEN ROUND(m.marks_obtained / m.max_marks * 100, 1)
                         ELSE NULL END AS percentage
             FROM marks m
             JOIN subjects s ON s.id = m.subject_id
             WHERE m.exam_id = ? AND m.student_id = ?
             ORDER BY s.name ASC",
            [$examId, $studentId]
        );
    } elseif (!empty($exams)) {
        // Default: latest exam
        $latestExamId = (int) $exams[0]['id'];
        $marks = db_fetch_all(
            "SELECT m.marks_obtained, m.max_marks, m.is_absent, m.remarks,
                    s.name AS subject_name, s.code AS subject_code,
                    CASE WHEN m.max_marks > 0 AND m.is_absent = 0
                         THEN ROUND(m.marks_obtained / m.max_marks * 100, 1)
                         ELSE NULL END AS percentage
             FROM marks m
             JOIN subjects s ON s.id = m.subject_id
             WHERE m.exam_id = ? AND m.student_id = ?
             ORDER BY s.name ASC",
            [$latestExamId, $studentId]
        );
        $examId = $latestExamId;
    }

    // Report card if available
    $reportCard = null;
    if ($examId && $enrollment) {
        $reportCard = db_fetch_one(
            "SELECT percentage, grade, rank, total_marks, total_max_marks,
                    teacher_remarks, status
             FROM report_cards
             WHERE student_id = ? AND exam_id = ? AND status = 'published'",
            [$studentId, $examId]
        );
    }

    pwa_json([
        'exams'       => $exams,
        'selected_exam_id' => $examId,
        'marks'       => $marks,
        'report_card' => $reportCard,
        'session'     => $enrollment,
    ]);
}

// ─────────────────────────────────────────────────────────────
// GET /pwa-api/student-timetable
// ─────────────────────────────────────────────────────────────
function pwa_student_timetable(array $apiUser): never
{
    $studentId = (int) $apiUser['linked_id'];

    // Get current enrollment
    $enrollment = db_fetch_one(
        "SELECT e.class_id, e.section_id, e.session_id
         FROM enrollments e
         JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
         WHERE e.student_id = ? AND e.status = 'active'
         ORDER BY e.id DESC LIMIT 1",
        [$studentId]
    );

    if (!$enrollment) {
        pwa_json(['timetable' => [], 'days' => []]);
    }

    $rows = db_fetch_all(
        "SELECT tt.day_of_week, tt.start_time, tt.end_time, tt.room,
                s.name AS subject_name, s.code AS subject_code,
                u.full_name AS teacher_name
         FROM timetables tt
         JOIN subjects s ON s.id = tt.subject_id
         LEFT JOIN users u ON u.id = tt.teacher_id
         WHERE tt.class_id = ?
           AND (tt.section_id IS NULL OR tt.section_id = ?)
           AND tt.session_id = ?
         ORDER BY FIELD(tt.day_of_week,'monday','tuesday','wednesday',
                        'thursday','friday','saturday','sunday'),
                  tt.start_time ASC",
        [$enrollment['class_id'], $enrollment['section_id'], $enrollment['session_id']]
    );

    // Group by day
    $grouped = [];
    $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    foreach ($days as $day) {
        $daySlots = array_filter($rows, fn($r) => $r['day_of_week'] === $day);
        if (!empty($daySlots)) {
            $grouped[$day] = array_values($daySlots);
        }
    }

    pwa_json([
        'class_id'   => (int) $enrollment['class_id'],
        'section_id' => $enrollment['section_id'] ? (int) $enrollment['section_id'] : null,
        'timetable'  => $grouped,
    ]);
}
