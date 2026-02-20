<?php
/**
 * Results — Result Analysis
 * Breakdown table by grade ranges + gender split
 */

$activeSession = get_active_session();
$sessionId     = $activeSession['id'] ?? 0;

$allClasses = db_fetch_all("SELECT id, name FROM classes WHERE is_active=1 ORDER BY sort_order");
$allTerms   = $sessionId
    ? db_fetch_all("SELECT id, name FROM terms WHERE session_id=? ORDER BY sort_order", [$sessionId])
    : [];

$selTerm    = input_int('term_id');
$selClass   = input_int('class_id');
$selSubject = input_int('subject_id');
$generate   = ($selTerm && $selClass && $selSubject && isset($_GET['generate']));

// Dynamic subjects — loaded when class is selected
$subjectsForClass = $selClass && $sessionId
    ? db_fetch_all("SELECT s.id, s.name FROM subjects s
                    JOIN class_subjects cs ON cs.subject_id = s.id
                    WHERE cs.class_id = ? AND cs.session_id = ? ORDER BY s.name",
                   [$selClass, $sessionId])
    : [];

$termName    = '';
$className   = '';
$subjectName = '';

foreach ($allClasses       as $c) if ($c['id'] == $selClass)   $className   = $c['name'];
foreach ($subjectsForClass as $s) if ($s['id'] == $selSubject)  $subjectName = $s['name'];

// ── Analysis data ──────────────────────────────────────────────────
$rows = [];         // category => [male, female, total]
$printTs = '';

if ($generate && $sessionId) {
    $term     = db_fetch_one("SELECT name FROM terms WHERE id=?", [$selTerm]);
    $termName = $term['name'] ?? '';
    $printTs  = date('d M Y, H:i');

    // Enrolled students (active) with gender
    $enrolled = db_fetch_all("
        SELECT s.id, s.gender
        FROM students s
        JOIN enrollments e ON e.student_id = s.id
        WHERE e.class_id=? AND e.session_id=? AND e.status='active'
    ", [$selClass, $sessionId]);

    $enrolledIds = array_column($enrolled, 'id');
    $genderById  = [];
    $cntM = $cntF = 0;
    foreach ($enrolled as $en) {
        $g = strtolower($en['gender'] ?? '');
        $genderById[$en['id']] = $g;
        if ($g === 'male')   $cntM++;
        if ($g === 'female') $cntF++;
    }
    $cntTotal = count($enrolled);

    // SUM all assessment marks per student for this class/subject/term (same as roster/report cards)
    // A student's subject score = Test1 + Test2 + Assignment + Final Exam (up to 100)
    $marks = []; // student_id => float (summed) | null (all absent / no entry)
    if ($enrolledIds) {
        $sPh = implode(',', array_fill(0, count($enrolledIds), '?'));
        $res = db_fetch_all(
            "SELECT sr.student_id,
                    SUM(CASE WHEN sr.is_absent=0 THEN sr.marks_obtained ELSE 0 END) AS total_marks,
                    MAX(CASE WHEN sr.is_absent=0 THEN 1 ELSE 0 END) AS has_marks
             FROM student_results sr
             JOIN assessments a ON a.id = sr.assessment_id
             WHERE a.class_id=? AND a.term_id=? AND a.session_id=? AND a.subject_id=?
               AND sr.student_id IN ({$sPh})
             GROUP BY sr.student_id",
            array_merge([$selClass, $selTerm, $sessionId, $selSubject], $enrolledIds)
        );
        foreach ($res as $r) {
            $marks[$r['student_id']] = $r['has_marks'] ? (float)$r['total_marks'] : null;
        }
    }

    // Exam-sitting = has a result entry (even if absent recorded, we count only non-absent)
    $sitting = array_filter($marks, fn($v) => $v !== null);

    // Grade bands function
    $band = function(int $lo, ?int $hi) use ($sitting, $genderById): array {
        $m = $f = 0;
        foreach ($sitting as $sid => $mark) {
            if ($mark < $lo) continue;
            if ($hi !== null && $mark > $hi) continue;
            $g = $genderById[$sid] ?? '';
            if ($g === 'male')   $m++;
            if ($g === 'female') $f++;
        }
        return ['male' => $m, 'female' => $f];
    };

    $sittingM = $sittingF = 0;
    foreach ($sitting as $sid => $m) {
        $g = $genderById[$sid] ?? '';
        if ($g === 'male')   $sittingM++;
        if ($g === 'female') $sittingF++;
    }

    $rows = [
        'Enrolled Students'        => ['male' => $cntM,     'female' => $cntF,     'fixed' => true],
        'Exam Sitting Students'    => ['male' => $sittingM, 'female' => $sittingF, 'fixed' => true],
        'Mark < 50'                => $band(0, 49),
        'Mark 50 – 64'             => $band(50, 64),
        'Mark 65 – 79'             => $band(65, 79),
        'Mark 80 – 89'             => $band(80, 89),
        'Mark ≥ 90'                => $band(90, null),
    ];

    // Denominator for percentage = enrolled
    $denom = $cntTotal ?: 1;
}

// ── Helper ──
function pct(int $n, int $denom): string {
    return $denom > 0 ? number_format(($n / $denom) * 100, 1) . '%' : '—';
}

ob_start();
?>

<style>
@media print {
    @page { size: A4 portrait; margin: 12mm 10mm; }
    body, html { background: #fff !important; }
    #sidebar, #topbar, .no-print { display: none !important; }
    #main-content { margin: 0 !important; padding: 0 !important; }
    .print-header { display: block !important; }
    table { border-collapse: collapse !important; }
    th, td { border: 1px solid #888 !important; }
}
.print-header { display: none; }
</style>

<div class="max-w-4xl mx-auto">

    <div class="flex items-center justify-between mb-6 no-print">
        <h1 class="text-xl font-bold text-gray-900">Result Analysis</h1>
        <?php if (!empty($rows)): ?>
        <button onclick="window.print()"
                class="flex items-center gap-2 px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print
        </button>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6 no-print">
        <form method="GET" id="filterForm" class="flex flex-wrap items-end gap-4">
            <input type="hidden" name="module" value="exams">
            <input type="hidden" name="action" value="result-analysis">

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
                <select name="class_id" id="classSelect" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Select Class</option>
                    <?php foreach ($allClasses as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selClass == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject <span class="text-red-500">*</span></label>
                <select name="subject_id" id="subjectSelect" required class="px-3 py-2 border border-gray-300 rounded-lg text-sm min-w-[180px]">
                    <option value="">— Select Class First —</option>
                    <?php foreach ($subjectsForClass as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $selSubject == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" name="generate" value="1"
                    class="px-5 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition">
                Analyse
            </button>
        </form>
    </div>

    <?php if (!$generate): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-16 text-center text-gray-400 text-sm">
        Select term, class, and subject, then click <strong>Analyse</strong>.
    </div>

    <?php else: ?>

    <!-- Print header (hidden on screen) -->
    <div class="print-header text-center mb-6">
        <h2 class="text-xl font-bold uppercase tracking-wide">Urjiberi School</h2>
        <p class="text-sm mt-1">Result Analysis Report</p>
        <p class="text-sm font-medium mt-1">Class: <?= e($className) ?> &nbsp;|&nbsp; Subject: <?= e($subjectName) ?> &nbsp;|&nbsp; Term: <?= e($termName) ?></p>
        <p class="text-xs text-gray-500 mt-0.5">Printed: <?= $printTs ?></p>
    </div>

    <!-- Context info bar -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 no-print flex flex-wrap gap-4 text-sm">
        <div><span class="text-gray-500">Class:</span> <strong><?= e($className) ?></strong></div>
        <div><span class="text-gray-500">Subject:</span> <strong><?= e($subjectName) ?></strong></div>
        <div><span class="text-gray-500">Term:</span> <strong><?= e($termName) ?></strong></div>
        <div><span class="text-gray-500">Session:</span> <strong><?= e($activeSession['name'] ?? '') ?></strong></div>
    </div>

    <!-- Analysis Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Category</th>
                    <th class="px-4 py-3 text-center font-medium">Male</th>
                    <th class="px-4 py-3 text-center font-medium">Female</th>
                    <th class="px-4 py-3 text-center font-medium">Total</th>
                    <th class="px-4 py-3 text-center font-medium">% of Enrolled</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php
                $denomEnrolled = ($rows['Enrolled Students']['male'] ?? 0) + ($rows['Enrolled Students']['female'] ?? 0);
                $bands = ['Mark < 50','Mark 50 – 64','Mark 65 – 79','Mark 80 – 89','Mark ≥ 90'];
                $bandTotalM = $bandTotalF = 0;

                $rowIdx = 0;
                foreach ($rows as $label => $data):
                    $m   = $data['male']   ?? 0;
                    $f   = $data['female'] ?? 0;
                    $tot = $m + $f;
                    $isHeader = in_array($label, ['Enrolled Students', 'Exam Sitting Students']);
                    $isBand   = in_array($label, $bands);
                    if ($isBand) { $bandTotalM += $m; $bandTotalF += $f; }
                    $bgClass  = $rowIdx % 2 === 0 ? 'bg-white' : 'bg-gray-50';
                    $labelCls = $isHeader ? 'font-semibold text-gray-900' : 'pl-6 text-gray-700';
                    if ($label === 'Mark < 50') $labelCls .= ' text-red-700 font-medium';
                    if ($label === 'Mark ≥ 90') $labelCls .= ' text-green-700 font-medium';
                    $rowIdx++;
                ?>
                <tr class="<?= $bgClass ?> <?= $isHeader ? 'border-b-2 border-gray-300' : '' ?>">
                    <td class="px-4 py-2.5 <?= $labelCls ?>">
                        <?php if ($label === 'Mark < 50'): ?>
                        <span class="inline-flex items-center gap-1"><svg class="w-3 h-3 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-11v4a1 1 0 002 0V7a1 1 0 00-2 0zm0 7a1 1 0 112 0 1 1 0 01-2 0z"/></svg>Fail (< 50)</span>
                        <?php elseif ($label === 'Mark ≥ 90'): ?>
                        <span class="inline-flex items-center gap-1"><svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>Distinction (≥ 90)</span>
                        <?php else: ?>
                        <?= e($label) ?>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2.5 text-center font-medium <?= $m > 0 ? 'text-blue-700' : 'text-gray-400' ?>"><?= $m ?: '—' ?></td>
                    <td class="px-4 py-2.5 text-center font-medium <?= $f > 0 ? 'text-pink-700' : 'text-gray-400' ?>"><?= $f ?: '—' ?></td>
                    <td class="px-4 py-2.5 text-center font-bold text-gray-900"><?= $tot ?: '—' ?></td>
                    <td class="px-4 py-2.5 text-center text-gray-600">
                        <?php
                            $pct = $denomEnrolled > 0 ? round(($tot / $denomEnrolled) * 100, 1) : 0;
                            if ($isHeader): ?>
                            <span class="text-gray-500 text-xs"><?= $tot ?> / <?= $denomEnrolled ?></span>
                        <?php elseif ($tot > 0): ?>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                <?= $label==='Mark < 50' ? 'bg-red-100 text-red-800' : ($label==='Mark ≥ 90' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700') ?>">
                                <?= $pct ?>%
                            </span>
                        <?php else: ?>
                            <span class="text-gray-300 text-xs">0%</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>

                <!-- Total students (all grade bands) -->
                <?php
                $bandTotalAll = $bandTotalM + $bandTotalF;
                $bPct = $denomEnrolled > 0 ? round(($bandTotalAll / $denomEnrolled) * 100, 1) : 0;
                ?>
                <tr class="bg-gray-100 border-t-2 border-gray-400 font-semibold">
                    <td class="px-4 py-3 text-gray-900">Total Students (All Grades)</td>
                    <td class="px-4 py-3 text-center text-blue-700"><?= $bandTotalM ?: '—' ?></td>
                    <td class="px-4 py-3 text-center text-pink-700"><?= $bandTotalF ?: '—' ?></td>
                    <td class="px-4 py-3 text-center font-bold text-gray-900"><?= $bandTotalAll ?: '—' ?></td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-primary-100 text-primary-800"><?= $bPct ?>%</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Gender legend -->
    <div class="mt-3 flex gap-6 text-xs text-gray-500 no-print">
        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-blue-200 inline-block"></span>Blue = Male</span>
        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-pink-200 inline-block"></span>Pink = Female</span>
        <span class="text-gray-400">Percentages are relative to total enrolled students.</span>
    </div>

    <!-- Grade scale reference -->
    <div class="mt-6 bg-white border border-gray-200 rounded-xl p-4 no-print">
        <p class="text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide">Grade Scale Reference</p>
        <div class="flex flex-wrap gap-2">
            <?php
            $grades = [
                'A – Distinction'  => ['range' => '90 – 100', 'cls' => 'bg-green-100 text-green-800'],
                'B – Very Good'    => ['range' => '80 – 89',  'cls' => 'bg-blue-100 text-blue-800'],
                'C – Good'         => ['range' => '65 – 79',  'cls' => 'bg-yellow-100 text-yellow-800'],
                'D – Pass'         => ['range' => '50 – 64',  'cls' => 'bg-orange-100 text-orange-800'],
                'F – Fail'         => ['range' => '0 – 49',   'cls' => 'bg-red-100 text-red-800'],
            ];
            foreach ($grades as $g => $info): ?>
            <span class="px-3 py-1 rounded-full text-xs font-medium <?= $info['cls'] ?>">
                <?= $g ?> (<?= $info['range'] ?>)
            </span>
            <?php endforeach; ?>
        </div>
    </div>

    <?php endif; ?>
</div>

<!-- Dynamic subject load on class change -->
<script>
document.getElementById('classSelect')?.addEventListener('change', function () {
    var classId = this.value;
    var subSel  = document.getElementById('subjectSelect');
    subSel.innerHTML = '<option value="">Loading…</option>';
    if (!classId) { subSel.innerHTML = '<option value="">— Select Class First —</option>'; return; }

    fetch('<?= url('exams', 'ajax-subjects') ?>class_id=' + classId)
        .then(function(r){ return r.json(); })
        .then(function(data){
            subSel.innerHTML = '<option value="">Select Subject</option>';
            data.forEach(function(s){
                var o = document.createElement('option');
                o.value = s.id; o.textContent = s.name;
                subSel.appendChild(o);
            });
        })
        .catch(function(){ subSel.innerHTML = '<option value="">Error loading subjects</option>'; });
});
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
