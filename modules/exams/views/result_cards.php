<?php
/**
 * Results — Report Cards (A4 Portrait, QR verification)
 * Filter: term → class → section → generate
 */

$activeSession = get_active_session();
$sessionId     = $activeSession['id'] ?? 0;

$allClasses  = db_fetch_all("SELECT id, name FROM classes WHERE is_active=1 ORDER BY sort_order");
$allSections = db_fetch_all("SELECT id, name, class_id FROM sections WHERE is_active=1 ORDER BY name");
$allTerms    = $sessionId
    ? db_fetch_all("SELECT id, name FROM terms WHERE session_id=? ORDER BY sort_order", [$sessionId])
    : [];

$selTerm    = input_int('term_id');
$selClass   = input_int('class_id');
$selSection = input_int('section_id');
$generate   = ($selTerm && $selClass && isset($_GET['generate']));

function conductGradeRc(float $avg): string {
    return match(true) {
        $avg >= 90 => 'A',
        $avg >= 80 => 'B',
        $avg >= 70 => 'C',
        $avg >= 60 => 'D',
        default    => 'F',
    };
}
function remarkRc(string $grade): string {
    return match($grade) {
        'A' => 'Excellent', 'B' => 'Very Good', 'C' => 'Good', 'D' => 'Pass', default => 'Needs Improvement',
    };
}

$cards       = [];
$subjects    = [];
$termName    = '';
$className   = '';
$sectionName = 'All Sections';

foreach ($allClasses  as $c) if ($c['id'] == $selClass)   $className   = $c['name'];
foreach ($allSections as $s) if ($s['id'] == $selSection)  $sectionName = $s['name'];

if ($generate && $sessionId) {
    $term     = db_fetch_one("SELECT name FROM terms WHERE id=?", [$selTerm]);
    $termName = $term['name'] ?? '';

    // Subjects for the class
    $subjects = db_fetch_all("
        SELECT s.id, s.name FROM subjects s
        JOIN class_subjects cs ON cs.subject_id = s.id
        WHERE cs.class_id = ? AND cs.session_id = ? ORDER BY s.name
    ", [$selClass, $sessionId]);

    // Assessment map: subject_id => assessment_id
    $assessmentMap = [];
    if ($subjects) {
        $subIds = array_column($subjects, 'id');
        $ph = implode(',', array_fill(0, count($subIds), '?'));
        $rows = db_fetch_all(
            "SELECT id, subject_id FROM assessments
             WHERE class_id=? AND term_id=? AND session_id=? AND subject_id IN ({$ph}) ORDER BY id DESC",
            array_merge([$selClass, $selTerm, $sessionId], $subIds)
        );
        foreach ($rows as $r) if (!isset($assessmentMap[$r['subject_id']])) $assessmentMap[$r['subject_id']] = $r['id'];
    }

    // Students
    $where  = "WHERE e.class_id = ? AND e.session_id = ? AND e.status = 'active'";
    $params = [$selClass, $sessionId];
    if ($selSection) { $where .= " AND e.section_id = ?"; $params[] = $selSection; }

    $students = db_fetch_all("
        SELECT s.id, s.first_name, s.last_name, s.admission_no, s.gender, s.date_of_birth, s.photo,
               e.roll_no, sec.name AS section_name
        FROM students s
        JOIN enrollments e ON e.student_id = s.id
        LEFT JOIN sections sec ON sec.id = e.section_id
        {$where}
        ORDER BY e.roll_no, s.first_name
    ", $params);

    if ($students) {
        $studentIds = array_column($students, 'id');
        $idPh = implode(',', array_fill(0, count($studentIds), '?'));

        // All results for this term/class
        $assessmentIds = array_values($assessmentMap);
        $marksGrid = [];
        if ($assessmentIds) {
            $aPh = implode(',', array_fill(0, count($assessmentIds), '?'));
            $resultRows = db_fetch_all(
                "SELECT sr.student_id, a.subject_id, sr.marks_obtained, sr.is_absent
                 FROM student_results sr
                 JOIN assessments a ON a.id = sr.assessment_id
                 WHERE sr.assessment_id IN ({$aPh}) AND sr.student_id IN ({$idPh})",
                array_merge($assessmentIds, $studentIds)
            );
            foreach ($resultRows as $rr) {
                $marksGrid[$rr['student_id']][$rr['subject_id']] =
                    $rr['is_absent'] ? null : $rr['marks_obtained'];
            }
        }

        // Absent days from attendance
        $abMap = [];
        $abRows = db_fetch_all(
            "SELECT student_id, COUNT(*) AS cnt FROM attendance
             WHERE student_id IN ({$idPh}) AND session_id=? AND term_id=? AND status='absent'
             GROUP BY student_id",
            array_merge($studentIds, [$sessionId, $selTerm])
        );
        foreach ($abRows as $r) $abMap[$r['student_id']] = $r['cnt'];

        // Compute totals for ranking
        $totals = [];
        foreach ($students as $st) {
            $total = 0;
            foreach ($subjects as $subj) {
                $mark = $marksGrid[$st['id']][$subj['id']] ?? null;
                if ($mark !== null) $total += (float)$mark;
            }
            $totals[$st['id']] = $total;
        }
        // Rank
        arsort($totals);
        $rankMap = []; $rank = 1;
        foreach ($totals as $sid => $tot) { $rankMap[$sid] = $rank++; }

        // Build cards
        foreach ($students as $st) {
            $subjectMarks = [];
            $total = 0; $subjCount = count($subjects);
            foreach ($subjects as $subj) {
                $mark = $marksGrid[$st['id']][$subj['id']] ?? null;
                $subjectMarks[$subj['id']] = $mark;
                if ($mark !== null) $total += (float)$mark;
            }
            $avg     = $subjCount > 0 ? round($total / $subjCount, 1) : null;
            $conduct = $avg !== null ? conductGradeRc($avg) : '—';
            $age     = $st['date_of_birth']
                ? (int)date_diff(new DateTime($st['date_of_birth']), new DateTime())->y
                : '—';

            $cards[] = [
                'student'  => $st,
                'marks'    => $subjectMarks,
                'total'    => $total,
                'avg'      => $avg,
                'conduct'  => $conduct,
                'remark'   => $conduct !== '—' ? remarkRc($conduct) : '—',
                'rank'     => $rankMap[$st['id']] ?? '—',
                'ab_days'  => $abMap[$st['id']] ?? 0,
                'age'      => $age,
            ];
        }
    }
}

ob_start();
?>

<!-- A4 Portrait print styles -->
<style>
@media print {
    @page { size: A4 portrait; margin: 12mm 10mm; }
    body, html { background: #fff !important; }
    #sidebar, #topbar, .no-print { display: none !important; }
    #main-content { margin: 0 !important; padding: 0 !important; }
    .report-card { page-break-after: always; break-after: page; border: 1px solid #888 !important; }
    .report-card:last-child { page-break-after: avoid; break-after: avoid; }
}
.qrcode-container canvas { display: block; }
</style>

<div class="max-w-3xl mx-auto">

    <div class="flex items-center justify-between mb-6 no-print">
        <h1 class="text-xl font-bold text-gray-900">Report Cards</h1>
        <?php if (!empty($cards)): ?>
        <button onclick="window.print()"
                class="flex items-center gap-2 px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print All Cards (A4)
        </button>
        <?php endif; ?>
    </div>

    <!-- Filters (no-print) -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <input type="hidden" name="module" value="exams">
            <input type="hidden" name="action" value="result-cards">

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
                Generate Cards
            </button>
        </form>
    </div>

    <?php if (!$generate): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-16 text-center text-gray-400 text-sm">
        Select term, class, and optionally a section, then click <strong>Generate Cards</strong>.
    </div>

    <?php elseif (empty($cards)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400 text-sm">
        No students found for the selected class/section.
    </div>

    <?php else: ?>
    <!-- Report Cards -->
    <?php foreach ($cards as $idx => $card): ?>
    <?php $st = $card['student']; ?>
    <div class="report-card bg-white rounded-2xl border-2 border-gray-300 p-8 mb-8 no-break" id="card-<?= $st['id'] ?>">

        <!-- School Header -->
        <div class="text-center mb-6 border-b-2 border-gray-200 pb-4">
            <div class="w-16 h-16 rounded-2xl bg-primary-800 flex items-center justify-center mx-auto mb-2">
                <span class="text-white text-xl font-bold">U</span>
            </div>
            <h1 class="text-xl font-bold text-gray-900 uppercase tracking-wide">Urjiberi School</h1>
            <p class="text-sm text-gray-500 mt-0.5">Student Report Card</p>
        </div>

        <!-- Student Info -->
        <div class="grid grid-cols-2 gap-x-8 gap-y-1.5 mb-6 text-sm">
            <div class="flex gap-2">
                <span class="text-gray-500 w-28 flex-shrink-0">Student Name:</span>
                <span class="font-semibold text-gray-900"><?= e($st['first_name'] . ' ' . $st['last_name']) ?></span>
            </div>
            <div class="flex gap-2">
                <span class="text-gray-500 w-28 flex-shrink-0">Adm. Number:</span>
                <span class="font-semibold text-gray-900"><?= e($st['admission_no']) ?></span>
            </div>
            <div class="flex gap-2">
                <span class="text-gray-500 w-28 flex-shrink-0">Class:</span>
                <span class="font-semibold text-gray-900"><?= e($className) ?></span>
            </div>
            <div class="flex gap-2">
                <span class="text-gray-500 w-28 flex-shrink-0">Section:</span>
                <span class="font-semibold text-gray-900"><?= e($st['section_name'] ?? $sectionName) ?></span>
            </div>
            <div class="flex gap-2">
                <span class="text-gray-500 w-28 flex-shrink-0">Academic Year:</span>
                <span class="font-semibold text-gray-900"><?= e($activeSession['name'] ?? '') ?></span>
            </div>
            <div class="flex gap-2">
                <span class="text-gray-500 w-28 flex-shrink-0">Age:</span>
                <span class="font-semibold text-gray-900"><?= $card['age'] ?></span>
            </div>
        </div>

        <!-- Marks Table -->
        <table class="w-full text-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="px-4 py-2.5 text-left font-medium">Subjects</th>
                    <th class="px-4 py-2.5 text-center font-medium w-24"><?= e($termName) ?></th>
                    <th class="px-4 py-2.5 text-center font-medium w-20">Grade</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($subjects as $subj): ?>
                <?php
                    $mark  = $card['marks'][$subj['id']] ?? null;
                    $grade = $mark !== null ? conductGradeRc((float)$mark) : '—';
                    $markCls = $mark !== null
                        ? ((float)$mark >= 50 ? 'text-gray-900' : 'text-red-600 font-bold')
                        : 'text-gray-400';
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-gray-800"><?= e($subj['name']) ?></td>
                    <td class="px-4 py-2 text-center <?= $markCls ?>">
                        <?= $mark !== null ? number_format((float)$mark, 0) : '—' ?>
                    </td>
                    <td class="px-4 py-2 text-center">
                        <?php if ($mark !== null): ?>
                            <span class="px-2 py-0.5 rounded text-xs font-semibold
                                <?= match(true) { (float)$mark>=90=>'bg-green-100 text-green-800', (float)$mark>=80=>'bg-blue-100 text-blue-800', (float)$mark>=70=>'bg-yellow-100 text-yellow-800', (float)$mark>=60=>'bg-orange-100 text-orange-800', default=>'bg-red-100 text-red-800' } ?>">
                                <?= $grade ?>
                            </span>
                        <?php else: ?>
                            <span class="text-gray-400 text-xs">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                <tr class="font-semibold">
                    <td class="px-4 py-2.5 text-gray-800">Total</td>
                    <td class="px-4 py-2.5 text-center text-gray-900 font-bold"><?= $card['total'] > 0 ? number_format($card['total'], 0) : '—' ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="px-4 py-2.5 text-gray-800">Average</td>
                    <td class="px-4 py-2.5 text-center font-bold <?= $card['avg'] !== null ? ($card['avg'] >= 50 ? 'text-green-700' : 'text-red-700') : 'text-gray-400' ?>">
                        <?= $card['avg'] !== null ? number_format($card['avg'], 1) . '%' : '—' ?>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td class="px-4 py-2.5 text-gray-800">Conduct</td>
                    <td class="px-4 py-2.5 text-center font-bold text-primary-800"><?= e($card['conduct']) ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="px-4 py-2.5 text-gray-800">Rank</td>
                    <td class="px-4 py-2.5 text-center font-bold text-gray-900"><?= $card['rank'] ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="px-4 py-2.5 text-gray-800">Absent Days</td>
                    <td class="px-4 py-2.5 text-center text-gray-700"><?= $card['ab_days'] ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="px-4 py-2.5 text-gray-800">Remark</td>
                    <td class="px-4 py-2.5 text-center text-gray-700"><?= e($card['remark']) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <!-- Bottom: signatures + QR -->
        <div class="flex items-end justify-between mt-8 pt-4 border-t border-gray-200">
            <div class="flex gap-12 text-sm text-gray-500">
                <div class="text-center">
                    <div class="h-10 border-b border-gray-400 w-32 mb-1"></div>
                    <span>Class Teacher</span>
                </div>
                <div class="text-center">
                    <div class="h-10 border-b border-gray-400 w-32 mb-1"></div>
                    <span>Principal</span>
                </div>
            </div>
            <!-- QR Code -->
            <div class="text-center">
                <div class="qrcode-container w-20 h-20 border border-gray-200 rounded-lg overflow-hidden flex items-center justify-center bg-white"
                     id="qr-<?= $st['id'] ?>"
                     data-value="URJIBERI|<?= $st['id'] ?>|<?= $selTerm ?>|<?= urlencode($st['admission_no']) ?>|<?= $card['total'] ?>|<?= $card['avg'] ?>">
                </div>
                <p class="text-xs text-gray-400 mt-1">Scan to verify</p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- QR Code library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" integrity="sha512-CNgIRecGo7nphbeZ04Sc13ka07paqdeTu0WR1IM4kNcpmBAUSHSr7FORullcnMya2d6iievC6We6kNfw/V2YA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
    document.querySelectorAll('.qrcode-container').forEach(function(el) {
        var val = el.getAttribute('data-value');
        new QRCode(el, {
            text: val,
            width: 80,
            height: 80,
            correctLevel: QRCode.CorrectLevel.M
        });
    });
    </script>

    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
