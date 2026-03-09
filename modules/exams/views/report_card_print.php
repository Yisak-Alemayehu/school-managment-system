<?php
/**
 * Report Card — Professional A4 Print View (Redesigned)
 * Features: School logo, QR verification, print watermark, cumulative terms
 * Supports single (?id=X) and batch (?ids=X,Y,Z) printing
 */

// Parse single or multiple IDs
$cardIds = [];
if (!empty($_GET['ids'])) {
    $cardIds = array_values(array_unique(array_filter(
        array_map('intval', explode(',', $_GET['ids'])),
        fn($v) => $v > 0
    )));
} else {
    $singleId = input_int('id');
    if ($singleId) $cardIds = [$singleId];
}

if (empty($cardIds)) {
    set_flash('error', 'No report cards specified.');
    redirect(url('exams', 'report-cards'));
}

$isBatch = count($cardIds) > 1;

// Shared setup
$schoolName = get_school_name();
$schoolLogo = '/img/Logo.png';

// Grade helper (defined once, outside loop)
function printGrade(float $mark): string {
    return match(true) {
        $mark >= 90 => 'A', $mark >= 80 => 'B', $mark >= 70 => 'C',
        $mark >= 60 => 'D', default => 'F',
    };
}

// Get first card info for the back link
$_firstRc = db_fetch_one("SELECT term_id, class_id FROM report_cards WHERE id = ?", [$cardIds[0]]);
$_backTermId  = (int)($_firstRc['term_id'] ?? 0);
$_backClassId = (int)($_firstRc['class_id'] ?? 0);
$_isFirstCard = true;

foreach ($cardIds as $_cardIndex => $cardId):

// Fetch report card for this student
$id = $cardId;
$rc = db_fetch_one("
    SELECT rc.*, s.first_name, s.last_name, s.admission_no, s.date_of_birth, s.gender, s.photo,
           s.phone, s.religion,
           c.name AS class_name, sec.name AS section_name,
           sess.name AS session_name, t.name AS term_name, t.id AS t_id
    FROM report_cards rc
    JOIN students s ON s.id = rc.student_id
    JOIN classes c ON c.id = rc.class_id
    LEFT JOIN sections sec ON sec.id = rc.section_id
    LEFT JOIN academic_sessions sess ON sess.id = rc.session_id
    LEFT JOIN terms t ON t.id = rc.term_id
    WHERE rc.id = ?
", [$id]);

if (!$rc) continue;

// Access control
if (auth_has_role('student') && (int)$rc['student_id'] !== rbac_student_id()) continue;
if (auth_has_role('parent') && !rbac_parent_has_child((int)$rc['student_id'])) continue;
if (auth_has_role('teacher') && !rbac_teacher_has_class((int)$rc['class_id'])) continue;

$sessionId  = (int)$rc['session_id'];
$termId     = (int)$rc['term_id'];
$classId    = (int)$rc['class_id'];
$studentId  = (int)$rc['student_id'];

// All terms for this session, ordered
$allTerms = db_fetch_all("SELECT id, name FROM terms WHERE session_id=? ORDER BY sort_order", [$sessionId]);
$prevTerms = [];
$currentTermName = $rc['term_name'] ?? '';
foreach ($allTerms as $_t) {
    if ($_t['id'] == $termId) break;
    $prevTerms[] = $_t;
}
$allCardTerms = array_merge($prevTerms, [['id' => $termId, 'name' => $currentTermName]]);
$hasPrevTerms = count($prevTerms) > 0;

// Subjects for this class
$subjects = db_fetch_all("
    SELECT s.id, s.name FROM subjects s
    JOIN class_subjects cs ON cs.subject_id = s.id
    WHERE cs.class_id = ? AND cs.session_id = ?
    ORDER BY s.name
", [$classId, $sessionId]);
$subjectIds = array_column($subjects, 'id');
$numSubjects = count($subjects);

// Current term marks
$marksGrid = [];
if ($subjectIds) {
    $subPh = implode(',', array_fill(0, count($subjectIds), '?'));
    $mkRows = db_fetch_all(
        "SELECT a.subject_id,
                SUM(CASE WHEN sr.is_absent = 0 THEN sr.marks_obtained ELSE 0 END) AS total_marks,
                MAX(CASE WHEN sr.is_absent = 0 THEN 1 ELSE 0 END) AS has_marks
         FROM student_results sr
         JOIN assessments a ON a.id = sr.assessment_id
         WHERE a.class_id = ? AND a.term_id = ? AND a.session_id = ?
           AND a.subject_id IN ({$subPh}) AND sr.student_id = ?
         GROUP BY a.subject_id",
        array_merge([$classId, $termId, $sessionId], $subjectIds, [$studentId])
    );
    foreach ($mkRows as $rr) {
        $marksGrid[$termId][$rr['subject_id']] = $rr['has_marks'] ? (float)$rr['total_marks'] : null;
    }
}

// Previous term marks
if ($prevTerms && $subjectIds) {
    $prevTermIds = array_column($prevTerms, 'id');
    $prevTPh = implode(',', array_fill(0, count($prevTermIds), '?'));
    $subPh = implode(',', array_fill(0, count($subjectIds), '?'));
    $prevRows = db_fetch_all(
        "SELECT a.term_id, a.subject_id,
                SUM(CASE WHEN sr.is_absent=0 THEN sr.marks_obtained ELSE 0 END) AS total_marks,
                MAX(CASE WHEN sr.is_absent=0 THEN 1 ELSE 0 END) AS has_marks
         FROM student_results sr
         JOIN assessments a ON a.id=sr.assessment_id
         WHERE a.class_id=? AND a.session_id=?
           AND a.term_id IN ({$prevTPh})
           AND a.subject_id IN ({$subPh})
           AND sr.student_id=?
         GROUP BY a.term_id, a.subject_id",
        array_merge([$classId, $sessionId], $prevTermIds, $subjectIds, [$studentId])
    );
    foreach ($prevRows as $r) {
        $marksGrid[$r['term_id']][$r['subject_id']] = $r['has_marks'] ? (float)$r['total_marks'] : null;
    }
}

// Compute per-term totals & averages
$termTotals = [];
$termAvgs = [];
foreach ($allCardTerms as $ct) {
    $tid = $ct['id'];
    $tot = 0;
    foreach ($subjects as $subj) {
        $m = $marksGrid[$tid][$subj['id']] ?? null;
        if ($m !== null) $tot += $m;
    }
    $termTotals[$tid] = $tot;
    $termAvgs[$tid] = $numSubjects > 0 ? round($tot / $numSubjects, 1) : null;
}

// Per-subject cumulative average
$subjCumAvg = [];
foreach ($subjects as $subj) {
    $vals = [];
    foreach ($allCardTerms as $ct) {
        $m = $marksGrid[$ct['id']][$subj['id']] ?? null;
        if ($m !== null) $vals[] = $m;
    }
    $subjCumAvg[$subj['id']] = count($allCardTerms) > 0
        ? round(array_sum($vals) / count($allCardTerms), 1) : null;
}

// Conduct
$conduct = db_fetch_one(
    "SELECT conduct, remarks FROM student_conduct WHERE student_id=? AND class_id=? AND session_id=? AND term_id=?",
    [$studentId, $classId, $sessionId, $termId]
);
$conductLabels = ['A'=>'Excellent','B'=>'Very Good','C'=>'Good','D'=>'Satisfactory','F'=>'Needs Improvement'];
$conductGrade = $conduct['conduct'] ?? null;
$conductLabel = $conductGrade ? ($conductLabels[$conductGrade] ?? $conductGrade) : 'Not Entered';

// Attendance
$attendSummary = db_fetch_one(
    "SELECT COUNT(*) AS total,
            SUM(status = 'present') AS present,
            SUM(status = 'absent') AS absent
     FROM attendance WHERE student_id=? AND session_id=? AND term_id=?",
    [$studentId, $sessionId, $termId]
);

// Total students for rank context
$totalStudents = db_fetch_value(
    "SELECT COUNT(*) FROM report_cards WHERE term_id=? AND class_id=? AND session_id=?",
    [$termId, $classId, $sessionId]
);

// Age calculation
$age = $rc['date_of_birth'] ? (int)date_diff(new DateTime($rc['date_of_birth']), new DateTime())->y : '—';

// Current term average for pass/fail
$curAvg = $termAvgs[$termId] ?? null;
$remark = $curAvg !== null ? ($curAvg >= 50 ? 'Passed' : 'Failed') : '—';

// QR Code verification data
$verifyToken = hash_hmac('sha256', $id . '|' . $studentId . '|' . $sessionId, 'urjiberi_report_card_secret_2026');
$verifyUrl = rtrim(APP_URL, '/') . '/exams/report-card-verify?' . http_build_query(['id' => $id, 'sig' => $verifyToken]);
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&format=png&margin=1&data=' . rawurlencode($verifyUrl);
?>
<?php if ($_isFirstCard): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isBatch ? 'Report Cards — Batch Print' : 'Report Card — ' . e($rc['first_name'] . ' ' . $rc['last_name']) ?></title>
    <style>
        /* ── Reset & Base ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1a1a2e;
            background: #f0f2f5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── A4 Page Container ── */
        .report-card-page {
            width: 210mm;
            height: 287mm;
            margin: 20px auto;
            background: #ffffff;
            padding: 10mm 14mm 8mm 14mm;
            box-shadow: 0 4px 24px rgba(0,0,0,0.12);
            position: relative;
            overflow: hidden;
        }

        /* ── Header Section ── */
        .rc-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding-bottom: 8px;
            border-bottom: 3px solid #1a1a2e;
            margin-bottom: 10px;
        }
        .rc-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .rc-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            border-radius: 8px;
        }
        .rc-logo-fallback {
            width: 72px;
            height: 72px;
            background: #1a1a2e;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 28px;
            font-weight: 700;
        }
        .rc-school-info {
            text-align: left;
        }
        .rc-school-name {
            font-size: 20px;
            font-weight: 800;
            color: #1a1a2e;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .rc-school-tagline {
            font-size: 13px;
            color: #4a5568;
            font-weight: 500;
            margin-top: 2px;
        }
        .rc-doc-title {
            font-size: 14px;
            color: #2d6a4f;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
            padding: 3px 12px;
            background: #d8f3dc;
            border-radius: 4px;
            display: inline-block;
        }
        .rc-header-right {
            text-align: center;
        }
        .rc-qr-img {
            width: 90px;
            height: 90px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 3px;
            background: #fff;
        }
        .rc-qr-label {
            font-size: 8px;
            color: #a0aec0;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── Student Info Section ── */
        .rc-student-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 24px;
            padding: 8px 14px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin-bottom: 10px;
        }
        .rc-info-row {
            display: flex;
            align-items: baseline;
            font-size: 11.5px;
        }
        .rc-info-label {
            color: #6c757d;
            width: 110px;
            flex-shrink: 0;
            font-weight: 500;
        }
        .rc-info-value {
            color: #212529;
            font-weight: 600;
        }

        /* ── Marks Table ── */
        .rc-marks-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10.5px;
        }
        .rc-marks-table thead th {
            background: #1a1a2e;
            color: #ffffff;
            padding: 5px 8px;
            text-align: center;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid #2d2d44;
        }
        .rc-marks-table thead th:first-child {
            text-align: left;
            border-top-left-radius: 6px;
        }
        .rc-marks-table thead th:last-child {
            border-top-right-radius: 6px;
        }
        .rc-marks-table thead th.current-term {
            background: #2d6a4f;
        }
        .rc-marks-table thead th.avg-col {
            background: #b45309;
        }
        .rc-marks-table tbody td {
            padding: 4px 8px;
            text-align: center;
            border: 1px solid #e9ecef;
            font-size: 10.5px;
        }
        .rc-marks-table tbody td:first-child {
            text-align: left;
            font-weight: 500;
        }
        .rc-marks-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        .rc-marks-table tbody tr:hover {
            background: #edf2f7;
        }
        .rc-marks-table tfoot td {
            padding: 4px 8px;
            border: 1px solid #dee2e6;
            font-weight: 600;
            font-size: 10.5px;
        }
        .rc-marks-table tfoot tr:first-child td {
            border-top: 2px solid #1a1a2e;
            background: #f1f3f5;
        }
        .mark-fail { color: #dc2626; font-weight: 700; }
        .mark-pass { color: #1a1a2e; }
        .mark-absent { color: #dc2626; font-style: italic; }
        .td-current { background: #ecfdf5 !important; }
        .td-avg { background: #fffbeb !important; }

        /* Grade badges */
        .grade-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .grade-a { background: #d1fae5; color: #065f46; }
        .grade-b { background: #dbeafe; color: #1e40af; }
        .grade-c { background: #fef3c7; color: #92400e; }
        .grade-d { background: #fed7aa; color: #9a3412; }
        .grade-f { background: #fee2e2; color: #991b1b; }

        /* ── Summary Section ── */
        .rc-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 8px;
        }
        .rc-summary-card {
            text-align: center;
            padding: 6px 6px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .rc-summary-value {
            font-size: 15px;
            font-weight: 800;
            line-height: 1.2;
        }
        .rc-summary-label {
            font-size: 9px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        /* ── Remarks ── */
        .rc-remarks {
            margin-bottom: 8px;
            padding: 6px 12px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .rc-remarks-title {
            font-size: 10px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .rc-remark-line {
            border-bottom: 1px dashed #ced4da;
            height: 22px;
            margin-bottom: 4px;
        }
        .rc-remark-text {
            font-size: 11px;
            color: #333;
            min-height: 22px;
            line-height: 22px;
        }

        /* ── Signatures ── */
        .rc-signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 12px;
            padding-top: 6px;
        }
        .rc-sig-block {
            text-align: center;
            width: 140px;
        }
        .rc-sig-line {
            border-top: 1.5px solid #495057;
            margin-bottom: 4px;
            margin-top: 18px;
        }
        .rc-sig-label {
            font-size: 10px;
            color: #6c757d;
            font-weight: 500;
        }

        /* ── Footer ── */
        .rc-footer {
            text-align: center;
            font-size: 9px;
            color: #adb5bd;
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px solid #e9ecef;
        }

        /* ── Print Button Bar ── */
        .no-print {
            max-width: 210mm;
            margin: 20px auto 0;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-primary { background: #1a1a2e; color: #fff; }
        .btn-primary:hover { background: #2d2d44; }
        .btn-outline { background: #fff; color: #495057; border: 1px solid #ced4da; }
        .btn-outline:hover { background: #f8f9fa; }

        /* ── Print-Only Styles ── */
        @media print {
            body { background: #fff; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .report-card-page {
                margin: 0;
                padding: 8mm 12mm;
                box-shadow: none;
                width: 100%;
                height: auto;
            }
            @page {
                size: A4 portrait;
                margin: 5mm;
            }
            .report-card-page {
                page-break-after: always;
            }
            .report-card-page:last-of-type {
                page-break-after: auto;
            }
            <?php if (!empty($_isCopy)): ?>
            .report-card-page::after {
                content: 'COPY';
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 120px;
                font-weight: 900;
                color: rgba(0, 0, 0, 0.06);
                letter-spacing: 20px;
                z-index: 1000;
                pointer-events: none;
            }
            <?php endif; ?>
        }

        <?php if (!empty($_isCopy)): ?>
        /* COPY watermark on screen */
        .report-card-page { position: relative; }
        .report-card-page::after {
            content: 'COPY';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            font-weight: 900;
            color: rgba(0, 0, 0, 0.06);
            letter-spacing: 20px;
            z-index: 1000;
            pointer-events: none;
        }
        <?php endif; ?>

    </style>
</head>
<body>

    <!-- Action Bar -->
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print <?= $isBatch ? 'All Report Cards (' . count($cardIds) . ')' : 'Report Card' ?>
        </button>
        <?php if (empty($_isCopy)): ?>
        <a href="<?= url('exams', 'report-cards') ?>&term_id=<?= $_backTermId ?>&class_id=<?= $_backClassId ?>" class="btn btn-outline">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to List
        </a>
        <?php endif; ?>
    </div>
<?php endif; $_isFirstCard = false; ?>

    <!-- A4 Report Card -->
    <div class="report-card-page">

        <!-- ═══ Header: Logo | School Name | QR Code ═══ -->
        <div class="rc-header">
            <div class="rc-header-left">
                <?php if ($schoolLogo && file_exists(APP_ROOT . '/public' . $schoolLogo)): ?>
                    <img src="<?= e($schoolLogo) ?>" alt="School Logo" class="rc-logo">
                <?php else: ?>
                    <div class="rc-logo-fallback"><?= strtoupper(substr($schoolName, 0, 1)) ?></div>
                <?php endif; ?>
                <div class="rc-school-info">
                    <div class="rc-school-name">Urji Beri School</div>
                    <div class="rc-doc-title">Student Report Card</div>
                </div>
            </div>
            <div class="rc-header-right">
                <img src="<?= e($qrCodeUrl) ?>" alt="QR Verification" class="rc-qr-img"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div style="display:none; width:90px; height:90px; border:2px solid #e2e8f0; border-radius:8px; align-items:center; justify-content:center; background:#f8f9fa; font-size:9px; color:#a0aec0;">QR Unavailable</div>
                <div class="rc-qr-label">Scan to Verify</div>
            </div>
        </div>

        <!-- ═══ Session & Term Info ═══ -->
        <div style="text-align:center; margin-bottom:8px;">
            <span style="font-size:12px; color:#4a5568; font-weight:600;">
                <?= e($rc['session_name'] ?? '') ?> &mdash; <?= e($rc['term_name'] ?? '') ?>
            </span>
        </div>

        <!-- ═══ Student Information ═══ -->
        <div class="rc-student-info">
            <div class="rc-info-row">
                <span class="rc-info-label">Student Name:</span>
                <span class="rc-info-value"><?= e($rc['first_name'] . ' ' . $rc['last_name']) ?></span>
            </div>
            <div class="rc-info-row">
                <span class="rc-info-label">Admission No:</span>
                <span class="rc-info-value"><?= e($rc['admission_no']) ?></span>
            </div>
            <div class="rc-info-row">
                <span class="rc-info-label">Class:</span>
                <span class="rc-info-value"><?= e($rc['class_name']) ?></span>
            </div>
            <div class="rc-info-row">
                <span class="rc-info-label">Section:</span>
                <span class="rc-info-value"><?= e($rc['section_name'] ?? '—') ?></span>
            </div>
            <div class="rc-info-row">
                <span class="rc-info-label">Gender:</span>
                <span class="rc-info-value"><?= ucfirst($rc['gender'] ?? '') ?></span>
            </div>
            <div class="rc-info-row">
                <span class="rc-info-label">Date of Birth:</span>
                <span class="rc-info-value"><?= $rc['date_of_birth'] ? format_date($rc['date_of_birth']) : '—' ?></span>
            </div>
            <div class="rc-info-row">
                <span class="rc-info-label">Age:</span>
                <span class="rc-info-value"><?= $age ?></span>
            </div>
            <div class="rc-info-row">
                <span class="rc-info-label">Rank:</span>
                <span class="rc-info-value" style="color:#2d6a4f; font-size:13px;"><?= $rc['rank'] ?? '—' ?> / <?= $totalStudents ?></span>
            </div>
        </div>

        <!-- ═══ Marks Table ═══ -->
        <table class="rc-marks-table">
            <thead>
                <tr>
                    <th style="text-align:left; min-width:120px;">Subjects</th>
                    <?php foreach ($allCardTerms as $ct): ?>
                    <th class="<?= $ct['id'] == $termId ? 'current-term' : '' ?>" style="min-width:60px;">
                        <?= e($ct['name']) ?>
                    </th>
                    <?php endforeach; ?>
                    <?php if ($hasPrevTerms): ?>
                    <th class="avg-col" style="min-width:60px;">Avg</th>
                    <?php endif; ?>
                    <th style="min-width:50px;">Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $subj): ?>
                <?php
                    $cumAvgMark = $subjCumAvg[$subj['id']] ?? null;
                    $gradeMark = $hasPrevTerms ? $cumAvgMark : ($marksGrid[$termId][$subj['id']] ?? null);
                    $grade = $gradeMark !== null ? printGrade((float)$gradeMark) : '—';
                    $gradeClass = $gradeMark !== null ? 'grade-' . strtolower($grade) : '';
                ?>
                <tr>
                    <td><?= e($subj['name']) ?></td>
                    <?php foreach ($allCardTerms as $ct): ?>
                    <?php
                        $m = $marksGrid[$ct['id']][$subj['id']] ?? null;
                        $cls = $m !== null ? ((float)$m >= 50 ? 'mark-pass' : 'mark-fail') : '';
                        $tdCls = $ct['id'] == $termId ? 'td-current' : '';
                    ?>
                    <td class="<?= $tdCls ?> <?= $cls ?>">
                        <?= $m !== null ? number_format((float)$m, 0) : '—' ?>
                    </td>
                    <?php endforeach; ?>
                    <?php if ($hasPrevTerms): ?>
                    <td class="td-avg" style="font-weight:600;">
                        <?php if ($cumAvgMark !== null): ?>
                            <span class="<?= (float)$cumAvgMark >= 50 ? 'mark-pass' : 'mark-fail' ?>">
                                <?= number_format($cumAvgMark, 1) ?>
                            </span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td>
                        <?php if ($gradeMark !== null): ?>
                            <span class="grade-badge <?= $gradeClass ?>"><?= $grade ?></span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <!-- Total Row -->
                <tr>
                    <td style="text-align:left; font-weight:700;">Total</td>
                    <?php foreach ($allCardTerms as $ct): ?>
                    <td class="<?= $ct['id'] == $termId ? 'td-current' : '' ?>" style="font-weight:700;">
                        <?= ($termTotals[$ct['id']] ?? 0) > 0 ? number_format($termTotals[$ct['id']], 0) : '—' ?>
                    </td>
                    <?php endforeach; ?>
                    <?php if ($hasPrevTerms): ?>
                    <td class="td-avg" style="font-weight:700;">
                        <?= array_sum($termTotals) > 0 ? number_format(array_sum($termTotals), 0) : '—' ?>
                    </td>
                    <?php endif; ?>
                    <td></td>
                </tr>
                <!-- Average Row -->
                <tr style="background:#f8f9fa;">
                    <td style="text-align:left;">Average</td>
                    <?php foreach ($allCardTerms as $ct): ?>
                    <?php $tAvg = $termAvgs[$ct['id']] ?? null; ?>
                    <td class="<?= $ct['id'] == $termId ? 'td-current' : '' ?>"
                        style="font-weight:700; color:<?= $tAvg !== null ? ($tAvg >= 50 ? '#059669' : '#dc2626') : '#adb5bd' ?>;">
                        <?= $tAvg !== null ? number_format($tAvg, 1) . '%' : '—' ?>
                    </td>
                    <?php endforeach; ?>
                    <?php if ($hasPrevTerms): ?>
                    <?php
                        $cumAll = ($numSubjects > 0 && count($allCardTerms) > 0)
                            ? round(array_sum($termTotals) / ($numSubjects * count($allCardTerms)), 1) : null;
                    ?>
                    <td class="td-avg" style="font-weight:700; color:<?= $cumAll !== null ? ($cumAll >= 50 ? '#b45309' : '#dc2626') : '#adb5bd' ?>;">
                        <?= $cumAll !== null ? number_format($cumAll, 1) . '%' : '—' ?>
                    </td>
                    <?php endif; ?>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <!-- ═══ Summary Cards ═══ -->
        <div class="rc-summary">
            <div class="rc-summary-card" style="background:#ecfdf5; border-color:#a7f3d0;">
                <div class="rc-summary-value" style="color:#065f46;"><?= number_format($rc['percentage'] ?? 0, 1) ?>%</div>
                <div class="rc-summary-label">Average</div>
            </div>
            <div class="rc-summary-card" style="background:#eff6ff; border-color:#bfdbfe;">
                <div class="rc-summary-value" style="color:#1e40af;"><?= e($rc['grade'] ?? '—') ?></div>
                <div class="rc-summary-label">Overall Grade</div>
            </div>
            <div class="rc-summary-card" style="background:#fef3c7; border-color:#fde68a;">
                <div class="rc-summary-value" style="color:#92400e;">
                    <?= ($attendSummary['total'] ?? 0) > 0
                        ? round(($attendSummary['present'] / $attendSummary['total']) * 100) . '%'
                        : 'N/A' ?>
                </div>
                <div class="rc-summary-label">Attendance</div>
            </div>
            <div class="rc-summary-card" style="background:#fce7f3; border-color:#fbcfe8;">
                <div class="rc-summary-value" style="color:#9d174d;">
                    <?= $conductGrade ? e($conductGrade) : '—' ?>
                </div>
                <div class="rc-summary-label">Conduct — <?= e($conductLabel) ?></div>
            </div>
        </div>

        <!-- ═══ Additional Info ═══ -->
        <div style="display:flex; gap:8px; margin-bottom:8px; font-size:10.5px;">
            <div style="flex:1; padding:8px 12px; background:#f8f9fa; border-radius:6px; border:1px solid #e9ecef;">
                <span style="color:#6c757d;">Absent Days:</span>
                <strong style="color:#dc2626; margin-left:4px;"><?= $attendSummary['absent'] ?? 0 ?></strong>
            </div>
            <div style="flex:1; padding:8px 12px; background:#f8f9fa; border-radius:6px; border:1px solid #e9ecef;">
                <span style="color:#6c757d;">Result:</span>
                <strong style="color:<?= $remark === 'Passed' ? '#059669' : '#dc2626' ?>; margin-left:4px;"><?= e($remark) ?></strong>
            </div>
            <div style="flex:1; padding:8px 12px; background:#f8f9fa; border-radius:6px; border:1px solid #e9ecef;">
                <span style="color:#6c757d;">Rank:</span>
                <strong style="margin-left:4px;"><?= $rc['rank'] ?? '—' ?> of <?= $totalStudents ?></strong>
            </div>
        </div>

        <!-- ═══ Remarks ═══ -->
        <div class="rc-remarks">
            <div class="rc-remarks-title">Teacher's Remarks</div>
            <?php if (!empty($rc['teacher_remarks'])): ?>
                <div class="rc-remark-text"><?= e($rc['teacher_remarks']) ?></div>
            <?php else: ?>
                <div class="rc-remark-line"></div>
                <div class="rc-remark-line"></div>
            <?php endif; ?>
        </div>

        <div class="rc-remarks">
            <div class="rc-remarks-title">Principal's Remarks</div>
            <?php if (!empty($rc['principal_remarks'])): ?>
                <div class="rc-remark-text"><?= e($rc['principal_remarks']) ?></div>
            <?php else: ?>
                <div class="rc-remark-line"></div>
            <?php endif; ?>
        </div>

        <!-- ═══ Signatures ═══ -->
        <div class="rc-signatures">
            <div class="rc-sig-block">
                <div class="rc-sig-line"></div>
                <div class="rc-sig-label">Class Teacher</div>
            </div>
            <div class="rc-sig-block">
                <div class="rc-sig-line"></div>
                <div class="rc-sig-label">Principal</div>
            </div>
            <div class="rc-sig-block">
                <div class="rc-sig-line"></div>
                <div class="rc-sig-label">Parent / Guardian</div>
            </div>
        </div>

        <!-- ═══ Footer ═══ -->
        <div class="rc-footer">
            Generated on <?= date('F j, Y') ?> &mdash; <?= e($schoolName) ?> &mdash; This is a computer-generated report card.
        </div>
    </div>

<?php endforeach; ?>
<?php if ($_isFirstCard): ?>
<!DOCTYPE html><html lang="en"><head><title>Error</title></head>
<body style="font-family:sans-serif;padding:40px;text-align:center;">
<p>No report cards available or access denied.</p>
<a href="<?= url('exams', 'report-cards') ?>">Back to List</a>
</body></html>
<?php exit; endif; ?>

</body>
</html>
<?php exit; ?>
