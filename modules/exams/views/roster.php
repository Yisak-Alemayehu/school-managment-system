<?php
/**
 * Results — Generate Roster
 * Columns: #, Full Name, Sex, Age, Term, [Subjects…], Total, Average, Ab.Day, Conduct, Rank, Remark
 */

$activeSession = get_active_session();
$sessionId     = $activeSession['id'] ?? 0;

$allClasses  = db_fetch_all("SELECT id, name FROM classes WHERE is_active=1 ORDER BY sort_order");
$allSections = db_fetch_all("SELECT id, name, class_id FROM sections WHERE is_active=1 ORDER BY name");
$allTerms    = $sessionId
    ? db_fetch_all("SELECT id, name, start_date, end_date FROM terms WHERE session_id=? ORDER BY sort_order", [$sessionId])
    : [];

$selTerm    = input_int('term_id');
$selClass   = input_int('class_id');
$selSection = input_int('section_id'); // 0 = all sections
$generate   = ($selTerm && $selClass && isset($_GET['generate']));

// Conduct grade labels (behavioral — stored in student_conduct table)
const CONDUCT_LABELS = [
    'A' => 'Excellent',
    'B' => 'Very Good',
    'C' => 'Good',
    'D' => 'Satisfactory',
    'F' => 'Needs Improvement',
];

$subjects    = [];
$rosterRows  = [];
$termName    = '';

if ($generate && $sessionId) {
    // Term name
    $term     = db_fetch_one("SELECT name, start_date, end_date FROM terms WHERE id=?", [$selTerm]);
    $termName = $term['name'] ?? '';

    // Subjects for the class
    $subjects = db_fetch_all("
        SELECT s.id, s.name, s.code FROM subjects s
        JOIN class_subjects cs ON cs.subject_id = s.id
        WHERE cs.class_id = ? AND cs.session_id = ?
        ORDER BY s.name
    ", [$selClass, $sessionId]);

    // Students
    $where  = "WHERE e.class_id = ? AND e.session_id = ? AND e.status = 'active'";
    $params = [$selClass, $sessionId];
    if ($selSection) { $where .= " AND e.section_id = ?"; $params[] = $selSection; }

    $students = db_fetch_all("
        SELECT s.id, s.first_name, s.last_name, s.gender, s.date_of_birth, e.roll_no
        FROM students s
        JOIN enrollments e ON e.student_id = s.id
        {$where}
        ORDER BY e.roll_no, s.first_name, s.last_name
    ", $params);

    if ($students) {
        $studentIds = array_column($students, 'id');
        $idPh = implode(',', array_fill(0, count($studentIds), '?'));

        // Sum ALL assessment marks per student per subject for this class/term
        // marksGrid[student_id][subject_id] = summed marks (null if all absent)
        $marksGrid = [];
        if ($subjects) {
            $subIds = array_column($subjects, 'id');
            $subPh  = implode(',', array_fill(0, count($subIds), '?'));

            // One query: join assessments → student_results, group by student+subject, sum marks
            $resultRows = db_fetch_all(
                "SELECT sr.student_id, a.subject_id,
                        SUM(CASE WHEN sr.is_absent = 0 THEN sr.marks_obtained ELSE 0 END) AS total_marks,
                        MAX(CASE WHEN sr.is_absent = 0 THEN 1 ELSE 0 END) AS has_marks,
                        MIN(sr.is_absent) AS any_present
                 FROM student_results sr
                 JOIN assessments a ON a.id = sr.assessment_id
                 WHERE a.class_id = ? AND a.term_id = ? AND a.session_id = ?
                   AND a.subject_id IN ({$subPh})
                   AND sr.student_id IN ({$idPh})
                 GROUP BY sr.student_id, a.subject_id",
                array_merge([$selClass, $selTerm, $sessionId], $subIds, $studentIds)
            );
            foreach ($resultRows as $rr) {
                // null = all attempts were absent, otherwise sum
                $marksGrid[$rr['student_id']][$rr['subject_id']] =
                    $rr['has_marks'] ? (float)$rr['total_marks'] : null;
            }
        }

        // Conduct grades (behavioral) from student_conduct table
        $conductMap = [];
        {
            $cmRows = db_fetch_all(
                "SELECT student_id, conduct FROM student_conduct
                 WHERE class_id=? AND session_id=? AND term_id=? AND student_id IN ({$idPh})",
                array_merge([$selClass, $sessionId, $selTerm], $studentIds)
            );
            foreach ($cmRows as $cm) $conductMap[$cm['student_id']] = $cm['conduct'];
        }

        // Absent days from attendance table during the term
        $abMapQuery = $sessionId && $term
            ? db_fetch_all(
                "SELECT student_id, COUNT(*) AS absent_days FROM attendance
                 WHERE student_id IN ({$idPh}) AND session_id=? AND term_id=? AND status='absent'
                 GROUP BY student_id",
                array_merge($studentIds, [$sessionId, $selTerm])
              )
            : [];
        $abMap = [];
        foreach ($abMapQuery as $r) $abMap[$r['student_id']] = $r['absent_days'];

        // Build roster rows
        $rawRows = [];
        foreach ($students as $st) {
            $subjectMarks = [];
            $total = 0; $subjectCount = 0;
            foreach ($subjects as $subj) {
                $mark = $marksGrid[$st['id']][$subj['id']] ?? null;
                $subjectMarks[$subj['id']] = $mark;
                if ($mark !== null) { $total += (float)$mark; $subjectCount++; }
            }
            $avg      = $subjectCount > 0 ? round($total / count($subjects), 1) : null;
            $abDays   = $abMap[$st['id']] ?? 0;
            $age      = $st['date_of_birth']
                ? (int)date_diff(new DateTime($st['date_of_birth']), new DateTime())->y
                : null;
            // Conduct: behavioral grade from DB (not derived from marks)
            $conduct  = $conductMap[$st['id']] ?? null;
            $rawRows[] = [
                'student'  => $st,
                'marks'    => $subjectMarks,
                'total'    => $total,
                'avg'      => $avg,
                'ab_days'  => $abDays,
                'conduct'  => $conduct,   // null = not yet entered
                'age'      => $age,
            ];
        }

        // Rank by total descending
        usort($rawRows, fn($a, $b) => $b['total'] <=> $a['total']);
        $rank = 1; $prev = null; $tieCount = 0;
        foreach ($rawRows as &$row) {
            if ($row['total'] === 0 && $row['avg'] === null) {
                $row['rank'] = '—';
            } else {
                if ($prev !== null && $row['total'] == $prev) {
                    $tieCount++;
                    $row['rank'] = $rank - $tieCount;
                } else {
                    $rank += $tieCount;
                    $row['rank'] = $rank++;
                    $tieCount = 0;
                }
                $prev = $row['total'];
            }
        }
        unset($row);

        // Re-sort by roll number / name
        usort($rawRows, function ($a, $b) {
            $ra = $a['student']['roll_no'];
            $rb = $b['student']['roll_no'];
            if ($ra && $rb) return $ra <=> $rb;
            return strcmp($a['student']['first_name'], $b['student']['first_name']);
        });

        $rosterRows = $rawRows;
    }
}

// names for filter labels
$className   = current(array_filter($allClasses, fn($c) => $c['id'] == $selClass))['name'] ?? '';
$sectionName = $selSection
    ? current(array_filter($allSections, fn($s) => $s['id'] == $selSection))['name'] ?? 'All'
    : 'All Sections';

ob_start();
?>

<!-- Landscape A4 print -->
<style>
@media print {
    @page { size: A4 landscape; margin: 8mm 6mm; }
    body, html { background: #fff !important; }
    #sidebar, #topbar, .no-print { display: none !important; }
    #main-content { margin: 0 !important; padding: 0 !important; }
    .print-header { display: block !important; }
    #rosterTable { border: none !important; border-radius: 0 !important; overflow: visible !important; }
    #rosterTable table { font-size: 7pt; }
    a { color: inherit !important; text-decoration: none !important; }
    tr { page-break-inside: avoid; }
}
.print-header { display: none; }
</style>

<div class="max-w-full mx-auto">

    <div class="flex items-center justify-between mb-6 no-print">
        <h1 class="text-xl font-bold text-gray-900">Generate Roster</h1>
        <?php if (!empty($rosterRows)): ?>
        <button onclick="window.print()"
                class="flex items-center gap-2 px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print Roster
        </button>
        <?php endif; ?>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <input type="hidden" name="module" value="exams">
            <input type="hidden" name="action" value="roster">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Term <span class="text-red-500">*</span></label>
                <select name="term_id" required class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Select Term</option>
                    <?php foreach ($allTerms as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $selTerm == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class <span class="text-red-500">*</span></label>
                <select name="class_id" required class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Select Class</option>
                    <?php foreach ($allClasses as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selClass == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                <select name="section_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="0">All Sections</option>
                    <?php foreach ($allSections as $s): ?>
                        <?php if ($s['class_id'] == $selClass): ?>
                            <option value="<?= $s['id'] ?>" <?= $selSection == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" name="generate" value="1"
                    class="px-5 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition">
                Generate Roster
            </button>
        </form>
    </div>

    <?php if (!$generate): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-16 text-center text-gray-400 text-sm">
        Select term, class, and section then click <strong>Generate Roster</strong>.
    </div>

    <?php elseif (empty($rosterRows)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400 text-sm">
        No students found for the selected class/section.
    </div>

    <?php else: ?>

    <!-- Print header -->
    <div class="print-header mb-3">
        <h2 class="text-base font-bold">Urjiberi School — Student Roster</h2>
        <p class="text-xs text-gray-600">
            Class: <strong><?= e($className) ?></strong> &nbsp;|&nbsp;
            Section: <strong><?= e($sectionName) ?></strong> &nbsp;|&nbsp;
            Term: <strong><?= e($termName) ?></strong> &nbsp;|&nbsp;
            Printed: <?= date('d M Y') ?>
        </p>
        <hr class="my-2">
    </div>

    <div id="rosterTable" class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-xs border-collapse">
            <thead>
                <!-- Group header -->
                <tr class="bg-gray-100 border-b border-gray-300">
                    <th colspan="5" class="px-2 py-1.5 text-center text-gray-700 font-semibold border-r border-gray-300">Student Info</th>
                    <?php if ($subjects): ?>
                        <th colspan="<?= count($subjects) ?>" class="px-1 py-1.5 text-center text-gray-700 font-semibold border-r border-gray-300">Subjects</th>
                    <?php endif; ?>
                    <th colspan="6" class="px-2 py-1.5 text-center text-gray-700 font-semibold bg-gray-200">Summary</th>
                </tr>
                <tr class="bg-gray-50 border-b border-gray-200 text-gray-600">
                    <th class="px-2 py-1.5 text-left border-r border-gray-200 w-6">#</th>
                    <th class="px-2 py-1.5 text-left border-r border-gray-200 min-w-[120px]">Full Name</th>
                    <th class="px-2 py-1.5 text-center border-r border-gray-200 w-8">Sex</th>
                    <th class="px-2 py-1.5 text-center border-r border-gray-200 w-8">Age</th>
                    <th class="px-2 py-1.5 text-center border-r border-gray-300 min-w-[60px]">Term</th>
                    <?php foreach ($subjects as $subj): ?>
                        <th class="px-1 py-1.5 text-center border-r border-gray-100 w-14" title="<?= e($subj['name']) ?>">
                            <?= e(mb_substr($subj['name'], 0, 6)) ?>
                        </th>
                    <?php endforeach; ?>
                    <th class="px-2 py-1.5 text-center border-r border-gray-100 w-12 bg-blue-50">Total</th>
                    <th class="px-2 py-1.5 text-center border-r border-gray-100 w-12 bg-blue-50">Avg</th>
                    <th class="px-2 py-1.5 text-center border-r border-gray-100 w-12">Ab.Day</th>
                    <th class="px-2 py-1.5 text-center border-r border-gray-100 w-12">Conduct</th>
                    <th class="px-2 py-1.5 text-center border-r border-gray-100 w-10">Rank</th>
                    <th class="px-2 py-1.5 text-center w-16">Remark</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($rosterRows as $i => $row): ?>
                <?php
                    $st  = $row['student'];
                    $avg = $row['avg'];
                    // Conduct: behavioral grade (null = not yet entered by teacher)
                    $conduct = $row['conduct'];  // 'A','B','C','D','F' or null
                    $conductLabel = $conduct ? (CONDUCT_LABELS[$conduct] ?? $conduct) : '—';
                    // Remark: academic outcome (independent of conduct)
                    $avg = $row['avg'];
                    $remark = $avg !== null ? ($avg >= 50 ? 'Passed' : 'Failed') : '—';
                    $avgClass = $avg !== null
                        ? ($avg >= 50 ? 'text-green-700' : 'text-red-700')
                        : 'text-gray-400';
                ?>
                <tr class="hover:bg-gray-50 <?= $i % 2 !== 0 ? 'bg-gray-50/40' : '' ?>">
                    <td class="px-2 py-1.5 text-gray-400 border-r border-gray-100"><?= $i + 1 ?></td>
                    <td class="px-2 py-1.5 font-medium text-gray-900 border-r border-gray-100">
                        <?= e($st['first_name'] . ' ' . $st['last_name']) ?>
                    </td>
                    <td class="px-2 py-1.5 text-center text-gray-600 border-r border-gray-100">
                        <?= strtoupper(substr($st['gender'] ?? 'M', 0, 1)) ?>
                    </td>
                    <td class="px-2 py-1.5 text-center text-gray-600 border-r border-gray-100">
                        <?= $row['age'] ?? '—' ?>
                    </td>
                    <td class="px-2 py-1.5 text-center text-gray-500 border-r border-gray-200 text-xs">
                        <?= e($termName) ?>
                    </td>
                    <?php foreach ($subjects as $subj): ?>
                        <?php $mark = $row['marks'][$subj['id']] ?? null; ?>
                        <td class="px-1 py-1.5 text-center border-r border-gray-100 <?= $mark !== null && (float)$mark < 50 ? 'text-red-600 font-semibold' : 'text-gray-800' ?>">
                            <?= $mark !== null ? number_format((float)$mark, 0) : '<span class="text-gray-300">—</span>' ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="px-2 py-1.5 text-center font-bold text-blue-700 bg-blue-50 border-r border-gray-100">
                        <?= $row['total'] > 0 ? number_format($row['total'], 0) : '—' ?>
                    </td>
                    <td class="px-2 py-1.5 text-center font-bold <?= $avgClass ?> bg-blue-50/50 border-r border-gray-100">
                        <?= $avg !== null ? number_format($avg, 1) : '—' ?>
                    </td>
                    <td class="px-2 py-1.5 text-center text-gray-600 border-r border-gray-100"><?= $row['ab_days'] ?></td>
                    <td class="px-2 py-1.5 text-center border-r border-gray-100">
                        <?php if ($conduct): ?>
                        <span class="inline-block px-1.5 py-0.5 rounded text-xs font-bold
                            <?= match($conduct) {
                                'A' => 'bg-green-100 text-green-800',
                                'B' => 'bg-blue-100 text-blue-800',
                                'C' => 'bg-indigo-100 text-indigo-800',
                                'D' => 'bg-yellow-100 text-yellow-800',
                                default => 'bg-red-100 text-red-800'
                            } ?>" title="<?= $conductLabel ?>">
                            <?= $conduct ?>
                        </span>
                        <?php else: ?>
                        <span class="text-gray-300 text-xs">N/E</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-2 py-1.5 text-center font-semibold text-gray-700 border-r border-gray-100"><?= $row['rank'] ?></td>
                    <td class="px-2 py-1.5 text-center text-xs text-gray-600"><?= $remark ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Subject key (screen) -->
    <div class="mt-4 no-print flex flex-wrap gap-3 text-xs text-gray-500">
        <?php foreach ($subjects as $s): ?>
            <span><strong><?= e(mb_substr($s['name'],0,6)) ?></strong> = <?= e($s['name']) ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
