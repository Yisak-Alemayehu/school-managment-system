<?php
/**
 * Results — Enter Students' Results (step-by-step)
 * Steps: Term → Class → Section → Subject → Assessment → Enter Marks
 */

$activeSession = get_active_session();
$sessionId     = $activeSession['id'] ?? 0;

$allClasses  = db_fetch_all("SELECT id, name FROM classes WHERE is_active=1 ORDER BY sort_order");
$allSections = db_fetch_all("SELECT id, name, class_id FROM sections WHERE is_active=1 ORDER BY name");
$allTerms    = $sessionId
    ? db_fetch_all("SELECT id, name FROM terms WHERE session_id=? ORDER BY sort_order", [$sessionId])
    : [];

// Current selections — default term to the active term
$activeTerm = get_active_term();
$selTerm    = input_int('term_id') ?: ($activeTerm['id'] ?? 0);
$selClass   = input_int('class_id');
$selSection    = input_int('section_id');
$selSubject    = input_int('subject_id');
$selAssessment = input_int('assessment_id');

// Step resolved dynamically
$step = 1;
if ($selTerm)       $step = 2;
if ($selTerm && $selClass) $step = 3;
if ($selTerm && $selClass && $selSection !== null) $step = 4;
if ($selTerm && $selClass && $selSubject) $step = 5;
if ($selTerm && $selClass && $selSubject && $selAssessment) $step = 6;

// Load subjects for class
$subjects = [];
if ($selClass && $sessionId) {
    $subjects = db_fetch_all("
        SELECT s.id, s.name, s.code FROM subjects s
        JOIN class_subjects cs ON cs.subject_id = s.id
        WHERE cs.class_id = ? AND cs.session_id = ? ORDER BY s.name
    ", [$selClass, $sessionId]);
}

// Load assessments for class+subject+term
$assessments = [];
if ($selClass && $selSubject && $selTerm && $sessionId) {
    $assessments = db_fetch_all("
        SELECT id, name, total_marks, description FROM assessments
        WHERE class_id=? AND subject_id=? AND term_id=? AND session_id=?
        ORDER BY name
    ", [$selClass, $selSubject, $selTerm, $sessionId]);
}

// Load students + existing marks for step 6
$students   = [];
$marksMap   = [];
$assessment = null;

if ($selAssessment && $selClass) {
    $assessment = db_fetch_one("
        SELECT a.*, s.name AS subject_name, c.name AS class_name, t.name AS term_name
        FROM assessments a
        JOIN subjects s ON s.id = a.subject_id
        JOIN classes c ON c.id = a.class_id
        LEFT JOIN terms t ON t.id = a.term_id
        WHERE a.id = ?
    ", [$selAssessment]);

    if ($assessment) {
        $where  = "WHERE e.class_id = ? AND e.session_id = ? AND e.status = 'active'";
        $params = [$selClass, $sessionId];
        if ($selSection) { $where .= " AND e.section_id = ?"; $params[] = $selSection; }

        $students = db_fetch_all("
            SELECT s.id, s.first_name, s.last_name, s.admission_no, s.gender, e.roll_no
            FROM students s
            JOIN enrollments e ON e.student_id = s.id
            {$where}
            ORDER BY e.roll_no, s.first_name, s.last_name
        ", $params);

        $studentIds = array_column($students, 'id');
        if ($studentIds) {
            $ph   = implode(',', array_fill(0, count($studentIds), '?'));
            $rows = db_fetch_all(
                "SELECT student_id, marks_obtained, is_absent, remarks FROM student_results
                 WHERE assessment_id = ? AND student_id IN ({$ph})",
                array_merge([$selAssessment], $studentIds)
            );
            foreach ($rows as $r) $marksMap[$r['student_id']] = $r;
        }
    }
}

// Helper: step badge
$stepLabels = ['Term','Class','Section','Subject','Assessment','Enter Marks'];
$stepUrls = [
    url('exams','enter-results'),
    url('exams','enter-results').'&term_id='.$selTerm,
    url('exams','enter-results').'&term_id='.$selTerm.'&class_id='.$selClass,
    url('exams','enter-results').'&term_id='.$selTerm.'&class_id='.$selClass.'&section_id='.$selSection,
    url('exams','enter-results').'&term_id='.$selTerm.'&class_id='.$selClass.'&section_id='.$selSection.'&subject_id='.$selSubject,
    '#',
];

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <h1 class="text-xl font-bold text-gray-900 mb-6">Enter Students' Results</h1>

    <?php partial('flash'); ?>

    <!-- Step progress bar -->
    <div class="flex items-center gap-1 mb-8 overflow-x-auto pb-2">
        <?php foreach ($stepLabels as $i => $lbl): ?>
            <?php
            $num      = $i + 1;
            $done     = $step > $num;
            $current  = $step === $num;
            $pending  = $step < $num;
            $dotCls   = $done ? 'bg-green-600 text-white' : ($current ? 'bg-primary-800 text-white' : 'bg-gray-200 text-gray-500');
            $lblCls   = $done ? 'text-green-600 font-semibold' : ($current ? 'text-primary-800 font-bold' : 'text-gray-400');
            ?>
            <?php if ($i > 0): ?><div class="flex-1 h-0.5 <?= $done ? 'bg-green-400' : 'bg-gray-200' ?> min-w-4"></div><?php endif; ?>
            <div class="flex flex-col items-center gap-1 flex-shrink-0">
                <?php if ($done): ?>
                    <a href="<?= $stepUrls[$i] ?>" class="w-7 h-7 rounded-full <?= $dotCls ?> flex items-center justify-center text-xs font-bold transition hover:opacity-80">✓</a>
                <?php else: ?>
                    <div class="w-7 h-7 rounded-full <?= $dotCls ?> flex items-center justify-center text-xs font-bold"><?= $num ?></div>
                <?php endif; ?>
                <span class="text-xs <?= $lblCls ?> whitespace-nowrap"><?= $lbl ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">

    <?php if ($step <= 5): ?>
    <!-- Steps 1-5: selection form -->
    <form method="GET" class="space-y-5">
        <input type="hidden" name="module" value="exams">
        <input type="hidden" name="action" value="enter-results">

        <!-- Step 1: Term -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Step 1 — Select Term</label>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($allTerms as $t): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="term_id" value="<?= $t['id'] ?>"
                               <?= $selTerm == $t['id'] ? 'checked' : '' ?>
                               onchange="this.form.submit()" class="sr-only peer">
                        <span class="px-4 py-2 rounded-lg border text-sm font-medium transition
                                     peer-checked:bg-primary-800 peer-checked:text-white peer-checked:border-primary-800
                                     border-gray-300 text-gray-700 hover:bg-gray-50">
                            <?= e($t['name']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
                <?php if (empty($allTerms)): ?>
                    <p class="text-sm text-amber-600">No terms found for the active session.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($selTerm): ?>
        <!-- Step 2: Class -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Step 2 — Select Class</label>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($allClasses as $c): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="class_id" value="<?= $c['id'] ?>"
                               <?= $selClass == $c['id'] ? 'checked' : '' ?>
                               onchange="this.form.submit()" class="sr-only peer">
                        <span class="px-4 py-2 rounded-lg border text-sm font-medium transition
                                     peer-checked:bg-primary-800 peer-checked:text-white peer-checked:border-primary-800
                                     border-gray-300 text-gray-700 hover:bg-gray-50">
                            <?= e($c['name']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if ($selTerm): ?><input type="hidden" name="term_id" value="<?= $selTerm ?>"> <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($selClass): ?>
        <!-- Step 3: Section -->
        <?php $classSections = array_filter($allSections, fn($s) => $s['class_id'] == $selClass); ?>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Step 3 — Select Section</label>
            <div class="flex flex-wrap gap-2">
                <label class="cursor-pointer">
                    <input type="radio" name="section_id" value="0"
                           <?= $selSection === 0 ? 'checked' : '' ?>
                           onchange="this.form.submit()" class="sr-only peer">
                    <span class="px-4 py-2 rounded-lg border text-sm font-medium transition
                                 peer-checked:bg-primary-800 peer-checked:text-white peer-checked:border-primary-800
                                 border-gray-300 text-gray-700 hover:bg-gray-50">
                        All Sections
                    </span>
                </label>
                <?php foreach ($classSections as $s): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="section_id" value="<?= $s['id'] ?>"
                               <?= $selSection == $s['id'] ? 'checked' : '' ?>
                               onchange="this.form.submit()" class="sr-only peer">
                        <span class="px-4 py-2 rounded-lg border text-sm font-medium transition
                                     peer-checked:bg-primary-800 peer-checked:text-white peer-checked:border-primary-800
                                     border-gray-300 text-gray-700 hover:bg-gray-50">
                            <?= e($s['name']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="class_id" value="<?= $selClass ?>">
            <?php if ($selTerm): ?><input type="hidden" name="term_id" value="<?= $selTerm ?>"> <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($selClass && $selSection !== null && $step >= 4): ?>
        <!-- Step 4: Subject -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Step 4 — Select Subject</label>
            <?php if (empty($subjects)): ?>
                <p class="text-sm text-amber-600">No subjects assigned to this class. Go to Academics → Class Subjects first.</p>
            <?php else: ?>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($subjects as $s): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="subject_id" value="<?= $s['id'] ?>"
                               <?= $selSubject == $s['id'] ? 'checked' : '' ?>
                               onchange="this.form.submit()" class="sr-only peer">
                        <span class="px-4 py-2 rounded-lg border text-sm font-medium transition
                                     peer-checked:bg-primary-800 peer-checked:text-white peer-checked:border-primary-800
                                     border-gray-300 text-gray-700 hover:bg-gray-50">
                            <?= e($s['name']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <input type="hidden" name="class_id" value="<?= $selClass ?>">
            <input type="hidden" name="section_id" value="<?= $selSection ?>">
            <?php if ($selTerm): ?><input type="hidden" name="term_id" value="<?= $selTerm ?>"> <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($selSubject && $step >= 5): ?>
        <!-- Step 5: Assessment -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Step 5 — Select Assessment</label>
            <?php if (empty($assessments)): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-700">
                    No assessments found for this class/subject/term combination.
                    <a href="<?= url('exams','add-assessment') ?>&class_id=<?= $selClass ?>&term_id=<?= $selTerm ?>"
                       class="underline font-medium ml-1">Create one →</a>
                </div>
            <?php else: ?>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($assessments as $a): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="assessment_id" value="<?= $a['id'] ?>"
                               <?= $selAssessment == $a['id'] ? 'checked' : '' ?>
                               onchange="this.form.submit()" class="sr-only peer">
                        <span class="px-4 py-2 rounded-lg border text-sm font-medium transition
                                     peer-checked:bg-primary-800 peer-checked:text-white peer-checked:border-primary-800
                                     border-gray-300 text-gray-700 hover:bg-gray-50">
                            <?= e($a['name']) ?> <span class="text-xs opacity-70">(/ <?= (int)$a['total_marks'] ?>)</span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <input type="hidden" name="class_id"   value="<?= $selClass ?>">
            <input type="hidden" name="section_id"  value="<?= $selSection ?>">
            <input type="hidden" name="subject_id"  value="<?= $selSubject ?>">
            <?php if ($selTerm): ?><input type="hidden" name="term_id" value="<?= $selTerm ?>"> <?php endif; ?>
        </div>
        <?php endif; ?>
    </form>

    <?php elseif ($step === 6 && $assessment && !empty($students)): ?>
    <!-- Step 6: Enter marks -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-base font-bold text-gray-900"><?= e($assessment['name']) ?></h2>
            <p class="text-sm text-gray-500">
                <?= e($assessment['class_name']) ?> &nbsp;·&nbsp;
                <?= e($assessment['subject_name']) ?> &nbsp;·&nbsp;
                <?= e($assessment['term_name'] ?? 'No Term') ?>
                &nbsp;·&nbsp; Out of <strong><?= (int)$assessment['total_marks'] ?></strong>
            </p>
        </div>
        <a href="<?= url('exams','enter-results') ?>&term_id=<?= $selTerm ?>&class_id=<?= $selClass ?>&section_id=<?= $selSection ?>&subject_id=<?= $selSubject ?>"
           class="text-sm text-primary-700 hover:underline">← Change Assessment</a>
    </div>

    <!-- Mark all absent / clear -->
    <div class="flex items-center gap-3 mb-4 text-sm">
        <button type="button" onclick="setAllAbsent(true)"
                class="px-3 py-1.5 border border-red-300 text-red-700 rounded-lg hover:bg-red-50 text-xs font-medium">
            Mark All Absent
        </button>
        <button type="button" onclick="setAllAbsent(false)"
                class="px-3 py-1.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-xs font-medium">
            Clear All Absent
        </button>
        <span class="text-gray-400 ml-auto text-xs"><?= count($students) ?> students</span>
    </div>

    <form method="POST" action="<?= url('exams', 'results-save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="assessment_id" value="<?= $selAssessment ?>">
        <input type="hidden" name="class_id"      value="<?= $selClass ?>">
        <input type="hidden" name="section_id"    value="<?= $selSection ?>">
        <input type="hidden" name="term_id"       value="<?= $selTerm ?>">
        <input type="hidden" name="subject_id"    value="<?= $selSubject ?>">

        <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase border-b">
                <tr>
                    <th class="px-3 py-2 text-left w-8">#</th>
                    <th class="px-3 py-2 text-left">Student</th>
                    <th class="px-3 py-2 text-left w-20">Adm No</th>
                    <th class="px-3 py-2 text-center w-28">Marks <span class="text-gray-400 normal-case">(0–<?= (int)$assessment['total_marks'] ?>)</span></th>
                    <th class="px-3 py-2 text-center w-20">Absent</th>
                    <th class="px-3 py-2 text-left">Remarks</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" id="resultTbody">
                <?php foreach ($students as $i => $st): ?>
                <?php $existing = $marksMap[$st['id']] ?? null; ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 text-gray-400"><?= $i + 1 ?></td>
                    <td class="px-3 py-2 font-medium text-gray-900">
                        <?= e($st['first_name'] . ' ' . $st['last_name']) ?>
                        <?php if ($st['roll_no']): ?>
                            <span class="text-xs text-gray-400 ml-1">#<?= e($st['roll_no']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2 text-gray-500 text-xs"><?= e($st['admission_no']) ?></td>
                    <td class="px-3 py-2">
                        <input type="number" name="results[<?= $st['id'] ?>][marks]"
                               min="0" max="<?= (int)$assessment['total_marks'] ?>" step="0.5"
                               value="<?= $existing && !$existing['is_absent'] ? e($existing['marks_obtained']) : '' ?>"
                               class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm text-center focus:ring-2 focus:ring-primary-400 focus:border-primary-400 marks-input"
                               data-max="<?= (int)$assessment['total_marks'] ?>">
                    </td>
                    <td class="px-3 py-2 text-center">
                        <input type="checkbox" name="results[<?= $st['id'] ?>][is_absent]" value="1"
                               <?= ($existing && $existing['is_absent']) ? 'checked' : '' ?>
                               class="absent-check w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-red-500"
                               onchange="toggleAbsent(this)">
                    </td>
                    <td class="px-3 py-2">
                        <input type="text" name="results[<?= $st['id'] ?>][remarks]"
                               value="<?= e($existing['remarks'] ?? '') ?>"
                               placeholder="Optional"
                               class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-primary-400 focus:border-primary-400">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <div class="flex justify-end gap-3 mt-5">
            <a href="<?= url('exams','enter-results') ?>&term_id=<?= $selTerm ?>&class_id=<?= $selClass ?>&section_id=<?= $selSection ?>&subject_id=<?= $selSubject ?>"
               class="px-5 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                Back
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition">
                Save Results
            </button>
        </div>
    </form>

    <?php elseif ($step === 6 && empty($students)): ?>
    <div class="text-center py-12 text-gray-400">No students found for the selected class/section.</div>

    <?php else: ?>
    <div class="text-center py-12 text-gray-400 text-sm">Complete the steps above to enter results.</div>
    <?php endif; ?>

    </div><!-- /card -->
</div>

<script>
function toggleAbsent(cb) {
    const row = cb.closest('tr');
    const marksInput = row.querySelector('.marks-input');
    if (cb.checked) {
        marksInput.value = '';
        marksInput.disabled = true;
        marksInput.classList.add('opacity-40');
    } else {
        marksInput.disabled = false;
        marksInput.classList.remove('opacity-40');
    }
}

function setAllAbsent(val) {
    document.querySelectorAll('.absent-check').forEach(cb => {
        cb.checked = val;
        toggleAbsent(cb);
    });
}

// On load: grey out already-absent rows
document.querySelectorAll('.absent-check:checked').forEach(cb => toggleAbsent(cb));

// Validate marks ≤ max on blur
document.querySelectorAll('.marks-input').forEach(inp => {
    inp.addEventListener('blur', () => {
        const max = parseFloat(inp.dataset.max);
        if (inp.value !== '' && parseFloat(inp.value) > max) {
            inp.value = max;
            inp.classList.add('border-red-400');
        } else {
            inp.classList.remove('border-red-400');
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
