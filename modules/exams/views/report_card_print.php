<?php
/**
 * Exams — Print Report Card View (standalone printable page)
 */

$id = input_int('id');
$rc = db_fetch_one("
    SELECT rc.*, s.first_name, s.last_name, s.admission_no, s.date_of_birth, s.gender, s.photo,
           c.name AS class_name, e.name AS exam_name,
           sess.name AS session_name, t.name AS term_name
    FROM report_cards rc
    JOIN students s ON s.id = rc.student_id
    JOIN classes c ON c.id = rc.class_id
    JOIN exams e ON e.id = rc.exam_id
    LEFT JOIN academic_sessions sess ON sess.id = rc.session_id
    LEFT JOIN terms t ON t.id = rc.term_id
    WHERE rc.id = ?
", [$id]);

if (!$rc) {
    set_flash('error', 'Report card not found.');
    redirect(url('exams', 'report-cards'));
}

// Get subject-wise marks
$marks = db_fetch_all("
    SELECT m.marks_obtained, m.is_absent, m.grade, m.grade_point,
           sub.name AS subject_name, sub.code AS subject_code,
           es.full_marks, es.pass_marks
    FROM marks m
    JOIN exam_schedules es ON es.id = m.exam_schedule_id
    JOIN subjects sub ON sub.id = m.subject_id
    WHERE m.student_id = ? AND m.exam_id = ? AND es.class_id = ?
    ORDER BY sub.name ASC
", [$rc['student_id'], $rc['exam_id'], $rc['class_id']]);

// Attendance summary
$attendSummary = db_fetch_one("
    SELECT COUNT(*) AS total,
           SUM(status = 'present') AS present,
           SUM(status = 'absent') AS absent
    FROM attendance
    WHERE student_id = ? AND session_id = ?
", [$rc['student_id'], $rc['session_id']]);

$schoolName = get_school_name();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card — <?= e($rc['first_name'] . ' ' . $rc['last_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            @page { margin: 1cm; size: A4; }
        }
    </style>
</head>
<body class="bg-white p-4 max-w-3xl mx-auto text-sm">

    <!-- Print button -->
    <div class="no-print mb-4 flex gap-3">
        <button onclick="window.print()" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium">Print</button>
        <a href="<?= url('exams', 'report-cards') ?>&exam_id=<?= $rc['exam_id'] ?>&class_id=<?= $rc['class_id'] ?>"
           class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Back</a>
    </div>

    <!-- Header -->
    <div class="text-center border-b-2 border-gray-800 pb-4 mb-4">
        <h1 class="text-xl font-bold text-gray-900"><?= e($schoolName) ?></h1>
        <p class="text-gray-600">Student Report Card</p>
        <p class="text-xs text-gray-500 mt-1"><?= e($rc['session_name'] ?? '') ?> — <?= e($rc['term_name'] ?? '') ?> — <?= e($rc['exam_name']) ?></p>
    </div>

    <!-- Student Info -->
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="space-y-1">
            <div><span class="text-gray-500">Name:</span> <strong><?= e($rc['first_name'] . ' ' . $rc['last_name']) ?></strong></div>
            <div><span class="text-gray-500">Admission No:</span> <?= e($rc['admission_no']) ?></div>
            <div><span class="text-gray-500">Class:</span> <?= e($rc['class_name']) ?></div>
        </div>
        <div class="space-y-1 text-right">
            <div><span class="text-gray-500">Gender:</span> <?= ucfirst($rc['gender']) ?></div>
            <div><span class="text-gray-500">DOB:</span> <?= format_date($rc['date_of_birth']) ?></div>
            <div><span class="text-gray-500">Rank:</span> <strong><?= $rc['rank'] ?></strong> / <?= $rc['total_students'] ?></div>
        </div>
    </div>

    <!-- Marks Table -->
    <table class="w-full border border-gray-300 mb-6">
        <thead>
            <tr class="bg-gray-100">
                <th class="border border-gray-300 px-3 py-2 text-left text-xs font-medium text-gray-600">Subject</th>
                <th class="border border-gray-300 px-3 py-2 text-center text-xs font-medium text-gray-600">Full Marks</th>
                <th class="border border-gray-300 px-3 py-2 text-center text-xs font-medium text-gray-600">Pass Marks</th>
                <th class="border border-gray-300 px-3 py-2 text-center text-xs font-medium text-gray-600">Obtained</th>
                <th class="border border-gray-300 px-3 py-2 text-center text-xs font-medium text-gray-600">Grade</th>
                <th class="border border-gray-300 px-3 py-2 text-center text-xs font-medium text-gray-600">Points</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($marks as $m): ?>
                <tr>
                    <td class="border border-gray-300 px-3 py-1.5"><?= e($m['subject_name']) ?></td>
                    <td class="border border-gray-300 px-3 py-1.5 text-center"><?= $m['full_marks'] ?></td>
                    <td class="border border-gray-300 px-3 py-1.5 text-center"><?= $m['pass_marks'] ?></td>
                    <td class="border border-gray-300 px-3 py-1.5 text-center font-semibold">
                        <?= $m['is_absent'] ? '<span class="text-red-600">Abs</span>' : ($m['marks_obtained'] ?? '—') ?>
                    </td>
                    <td class="border border-gray-300 px-3 py-1.5 text-center font-bold"><?= e($m['grade'] ?? '—') ?></td>
                    <td class="border border-gray-300 px-3 py-1.5 text-center"><?= $m['grade_point'] !== null ? number_format($m['grade_point'], 1) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="bg-gray-50 font-semibold">
                <td class="border border-gray-300 px-3 py-2">Total / Average</td>
                <td class="border border-gray-300 px-3 py-2 text-center"><?= array_sum(array_column($marks, 'full_marks')) ?></td>
                <td class="border border-gray-300 px-3 py-2 text-center">—</td>
                <td class="border border-gray-300 px-3 py-2 text-center"><?= number_format($rc['total_marks'], 1) ?></td>
                <td class="border border-gray-300 px-3 py-2 text-center text-lg"><?= e($rc['grade'] ?? '') ?></td>
                <td class="border border-gray-300 px-3 py-2 text-center"><?= number_format($rc['gpa'], 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Summary -->
    <div class="grid grid-cols-3 gap-4 mb-6 text-center">
        <div class="border border-gray-300 rounded p-3">
            <div class="text-lg font-bold"><?= number_format($rc['average'], 1) ?>%</div>
            <div class="text-xs text-gray-500">Average</div>
        </div>
        <div class="border border-gray-300 rounded p-3">
            <div class="text-lg font-bold"><?= number_format($rc['gpa'], 2) ?></div>
            <div class="text-xs text-gray-500">GPA</div>
        </div>
        <div class="border border-gray-300 rounded p-3">
            <div class="text-lg font-bold"><?= ($attendSummary['total'] ?? 0) > 0 ? round(($attendSummary['present'] / $attendSummary['total']) * 100) . '%' : 'N/A' ?></div>
            <div class="text-xs text-gray-500">Attendance</div>
        </div>
    </div>

    <!-- Remarks + Signatures -->
    <div class="mb-8">
        <div class="mb-4">
            <label class="text-xs text-gray-500">Teacher's Remarks:</label>
            <div class="border-b border-gray-300 mt-2 h-6"><?= e($rc['teacher_remarks'] ?? '') ?></div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-8 mt-12 pt-4">
        <div class="text-center">
            <div class="border-t border-gray-400 pt-1 text-xs text-gray-600">Class Teacher</div>
        </div>
        <div class="text-center">
            <div class="border-t border-gray-400 pt-1 text-xs text-gray-600">Principal</div>
        </div>
        <div class="text-center">
            <div class="border-t border-gray-400 pt-1 text-xs text-gray-600">Parent/Guardian</div>
        </div>
    </div>

    <div class="text-center text-xs text-gray-400 mt-8">
        Generated on <?= date('F j, Y') ?> — <?= e($schoolName) ?>
    </div>
</body>
</html>
<?php exit; ?>
