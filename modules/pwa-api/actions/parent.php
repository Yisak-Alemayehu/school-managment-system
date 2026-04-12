<?php
/**
 * PWA API — Parent / Guardian Data Actions
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Validate that a student belongs to this guardian.
 */
function pwa_assert_child(int $guardianId, int $studentId): array
{
    $student = db_fetch_one(
        "SELECT s.id, s.full_name, s.first_name, s.last_name, s.photo, s.status,
                s.gender, s.date_of_birth, s.admission_no,
                c.name AS class_name, c.id AS class_id,
                sec.name AS section_name, sec.id AS section_id,
                e.roll_no, acs.name AS session_name, acs.id AS session_id
         FROM students s
         JOIN student_guardians sg ON sg.student_id = s.id AND sg.guardian_id = ?
         LEFT JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
         LEFT JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
         LEFT JOIN classes c ON c.id = e.class_id
         LEFT JOIN sections sec ON sec.id = e.section_id
         WHERE s.id = ? AND s.deleted_at IS NULL
         ORDER BY e.id DESC LIMIT 1",
        [$guardianId, $studentId]
    );

    if (!$student) {
        pwa_error('Student not found or not linked to your account.', 404);
    }

    return $student;
}

// ─────────────────────────────────────────────────────────────
// GET /pwa-api/parent-dashboard
// ─────────────────────────────────────────────────────────────
function pwa_parent_dashboard(array $apiUser): never
{
    $guardianId = (int) $apiUser['linked_id'];

    $children = db_fetch_all(
        "SELECT s.id, s.full_name, s.photo, s.status,
                c.name AS class_name, sec.name AS section_name
         FROM students s
         JOIN student_guardians sg ON sg.student_id = s.id AND sg.guardian_id = ?
         LEFT JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
         LEFT JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
         LEFT JOIN classes c ON c.id = e.class_id
         LEFT JOIN sections sec ON sec.id = e.section_id
         WHERE s.deleted_at IS NULL
         ORDER BY sg.is_primary DESC, s.full_name ASC",
        [$guardianId]
    );

    // Recent notices
    $notices = db_fetch_all(
        "SELECT id, title, created_at FROM announcements
         WHERE (audience = 'all' OR audience = 'parents') AND is_active = 1
         ORDER BY created_at DESC LIMIT 5",
        []
    );

    pwa_json([
        'children' => array_map(fn($c) => [
            'id'           => (int) $c['id'],
            'full_name'    => $c['full_name'],
            'photo'        => $c['photo'] ? (APP_URL . '/uploads/students/' . $c['photo']) : null,
            'class_name'   => $c['class_name'],
            'section_name' => $c['section_name'],
            'status'       => $c['status'],
        ], $children),
        'notices' => $notices,
    ]);
}

// ─────────────────────────────────────────────────────────────
// GET /pwa-api/parent-children
// ─────────────────────────────────────────────────────────────
function pwa_parent_children(array $apiUser): never
{
    $guardianId = (int) $apiUser['linked_id'];

    $children = db_fetch_all(
        "SELECT s.id, s.full_name, s.first_name, s.last_name,
                s.photo, s.gender, s.date_of_birth, s.status, s.admission_no,
                c.name AS class_name, sec.name AS section_name,
                sg.is_primary, sg.relationship AS relation_type
         FROM students s
         JOIN student_guardians sg ON sg.student_id = s.id AND sg.guardian_id = ?
         LEFT JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
         LEFT JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
         LEFT JOIN classes c ON c.id = e.class_id
         LEFT JOIN sections sec ON sec.id = e.section_id
         WHERE s.deleted_at IS NULL
         ORDER BY sg.is_primary DESC, s.full_name ASC",
        [$guardianId]
    );

    pwa_json([
        'children' => array_map(fn($c) => [
            'id'           => (int) $c['id'],
            'full_name'    => $c['full_name'],
            'photo'        => $c['photo'] ? (APP_URL . '/uploads/students/' . $c['photo']) : null,
            'gender'       => $c['gender'],
            'dob'          => $c['date_of_birth'],
            'status'       => $c['status'],
            'admission_no' => $c['admission_no'],
            'class_name'   => $c['class_name'],
            'section_name' => $c['section_name'],
            'is_primary'   => (bool) $c['is_primary'],
        ], $children),
    ]);
}

// ─────────────────────────────────────────────────────────────
// GET /pwa-api/parent-student/{student_id}
// Full child dashboard data
// ─────────────────────────────────────────────────────────────
function pwa_parent_student(array $apiUser, ?string $sub): never
{
    $guardianId = (int) $apiUser['linked_id'];
    $studentId  = $sub ? (int) $sub : (int) ($_GET['id'] ?? 0);

    if (!$studentId) {
        pwa_error('Student ID is required.');
    }

    $student = pwa_assert_child($guardianId, $studentId);

    // Attendance summary
    $attSummary = ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0, 'percentage' => 0];
    if (!empty($student['session_id'])) {
        $rows = db_fetch_all(
            "SELECT status, COUNT(*) AS cnt FROM attendance
             WHERE student_id = ? AND session_id = ? AND subject_id IS NULL
             GROUP BY status",
            [$studentId, $student['session_id']]
        );
        foreach ($rows as $r) {
            $attSummary[$r['status']] = (int) $r['cnt'];
        }
        $attSummary['total'] = $attSummary['present'] + $attSummary['absent']
                             + $attSummary['late'];
        $attSummary['percentage'] = $attSummary['total']
            ? round(($attSummary['present'] + $attSummary['late']) / $attSummary['total'] * 100, 1)
            : 0;
    }

    // Latest exam marks
    $latestMarks = db_fetch_all(
        "SELECT m.marks_obtained, m.max_marks, m.is_absent,
                s.name AS subject_name,
                e.name AS exam_name,
                CASE WHEN m.max_marks > 0 AND m.is_absent = 0
                     THEN ROUND(m.marks_obtained / m.max_marks * 100, 1)
                     ELSE NULL END AS percentage
         FROM marks m
         JOIN subjects s ON s.id = m.subject_id
         JOIN exams ex ON ex.id = m.exam_id
         WHERE m.student_id = ?
         ORDER BY m.created_at DESC LIMIT 8",
        [$studentId]
    );

    // Fee balance
    $feeBalance = db_fetch_one(
        "SELECT
            SUM(sf.amount) AS total_amount,
            SUM(sf.amount_paid) AS total_paid,
            SUM(sf.amount - sf.amount_paid) AS balance
         FROM fin_student_fees sf
         WHERE sf.student_id = ?",
        [$studentId]
    );

    pwa_json([
        'student'      => pwa_student_safe($student),
        'attendance'   => $attSummary,
        'latest_marks' => $latestMarks,
        'fee_balance'  => [
            'total'  => (float) ($feeBalance['total_amount'] ?? 0),
            'paid'   => (float) ($feeBalance['total_paid'] ?? 0),
            'due'    => (float) ($feeBalance['balance'] ?? 0),
        ],
    ]);
}

// ─────────────────────────────────────────────────────────────
// GET /pwa-api/parent-fees?student_id=X
// ─────────────────────────────────────────────────────────────
function pwa_parent_fees(array $apiUser): never
{
    $guardianId = (int) $apiUser['linked_id'];
    $studentId  = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;

    if (!$studentId) {
        pwa_error('student_id query parameter is required.');
    }

    // Verify ownership
    pwa_assert_child($guardianId, $studentId);

    $fees = db_fetch_all(
        "SELECT sf.id, sf.amount, sf.amount_paid,
                (sf.amount - sf.amount_paid) AS balance,
                sf.status, sf.due_date, sf.created_at,
                f.name AS fee_name, f.description AS fee_description,
                f.frequency
         FROM fin_student_fees sf
         JOIN fin_fees f ON f.id = sf.fee_id
         WHERE sf.student_id = ?
         ORDER BY sf.due_date DESC",
        [$studentId]
    );

    $totals = [
        'total'   => array_sum(array_column($fees, 'amount')),
        'paid'    => array_sum(array_column($fees, 'amount_paid')),
        'balance' => array_sum(array_column($fees, 'balance')),
    ];

    // Recent transactions
    $transactions = db_fetch_all(
        "SELECT tx.amount, tx.method, tx.reference_no, tx.note, tx.created_at,
                f.name AS fee_name
         FROM fin_transactions tx
         JOIN fin_student_fees sf ON sf.id = tx.student_fee_id
         JOIN fin_fees f ON f.id = sf.fee_id
         WHERE sf.student_id = ?
         ORDER BY tx.created_at DESC LIMIT 10",
        [$studentId]
    );

    pwa_json([
        'fees'         => $fees,
        'totals'       => $totals,
        'transactions' => $transactions,
    ]);
}
