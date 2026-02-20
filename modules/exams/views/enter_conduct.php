<?php
/**
 * Results — Enter Student Conduct Grades
 *
 * Conduct evaluates BEHAVIOR, social skills, and classroom habits —
 * completely separate from academic performance / marks.
 *
 * Grade scale:
 *   A = Excellent         (outstanding behavior in all areas)
 *   B = Very Good         (consistently positive behavior)
 *   C = Good              (generally appropriate behavior)
 *   D = Satisfactory      (meets minimum behavioral expectations)
 *   F = Needs Improvement (persistent behavioral concerns)
 *
 * Filter: Term → Class → Section → Save
 */

$activeSession = get_active_session();
$sessionId     = $activeSession['id'] ?? 0;

$allClasses  = db_fetch_all("SELECT id, name FROM classes WHERE is_active=1 ORDER BY sort_order");
$allSections = db_fetch_all("SELECT id, name, class_id FROM sections WHERE is_active=1 ORDER BY name");
$allTerms    = $sessionId
    ? db_fetch_all("SELECT id, name FROM terms WHERE session_id=? ORDER BY sort_order", [$sessionId])
    : [];

$activeTerm  = get_active_term();
$selTerm     = input_int('term_id') ?: ($activeTerm['id'] ?? 0);
$selClass    = input_int('class_id');
$selSection  = input_int('section_id');
$showTable   = ($selTerm && $selClass && isset($_GET['show']));

// Conduct grade meta
const CONDUCT_GRADES = [
    'A' => 'Excellent',
    'B' => 'Very Good',
    'C' => 'Good',
    'D' => 'Satisfactory',
    'F' => 'Needs Improvement',
];

$students    = [];
$conductMap  = []; // student_id => ['conduct'=>..., 'remarks'=>...]
$termName    = '';
$className   = '';
$sectionName = 'All Sections';

foreach ($allClasses  as $c) if ($c['id'] == $selClass)   $className   = $c['name'];
foreach ($allSections as $s) if ($s['id'] == $selSection)  $sectionName = $s['name'];

if ($showTable && $sessionId) {
    $term     = db_fetch_one("SELECT name FROM terms WHERE id=?", [$selTerm]);
    $termName = $term['name'] ?? '';

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

    if ($students) {
        $studentIds = array_column($students, 'id');
        $idPh = implode(',', array_fill(0, count($studentIds), '?'));

        $conductRows = db_fetch_all(
            "SELECT student_id, conduct, remarks
             FROM student_conduct
             WHERE class_id=? AND session_id=? AND term_id=?
               AND student_id IN ({$idPh})",
            array_merge([$selClass, $sessionId, $selTerm], $studentIds)
        );
        foreach ($conductRows as $cr) {
            $conductMap[$cr['student_id']] = [
                'conduct' => $cr['conduct'],
                'remarks' => $cr['remarks'],
            ];
        }
    }
}

ob_start();
?>

<div class="max-w-5xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Enter Student Conduct</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Conduct grades reflect <strong>behavior, social skills, and classroom habits</strong> —
                independent of academic performance.
            </p>
        </div>
        <?php if ($showTable && $students): ?>
        <button form="conductForm" type="submit"
                class="flex items-center gap-2 px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Save All Conduct Grades
        </button>
        <?php endif; ?>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <input type="hidden" name="module" value="exams">
            <input type="hidden" name="action" value="enter-conduct">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Term <span class="text-red-500">*</span></label>
                <select name="term_id" required class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Select Term</option>
                    <?php foreach ($allTerms as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $selTerm == $t['id'] ? 'selected' : '' ?>>
                            <?= e($t['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class <span class="text-red-500">*</span></label>
                <select name="class_id" required class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Select Class</option>
                    <?php foreach ($allClasses as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selClass == $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                <select name="section_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="0">All Sections</option>
                    <?php foreach ($allSections as $s): ?>
                        <?php if (!$selClass || $s['class_id'] == $selClass): ?>
                        <option value="<?= $s['id'] ?>" <?= $selSection == $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['name']) ?>
                        </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" name="show" value="1"
                    class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-900 transition">
                Load Students
            </button>
        </form>
    </div>

    <?php if ($showTable): ?>

        <!-- Conduct grade legend -->
        <div class="flex flex-wrap gap-3 mb-4">
            <?php
            $legendColors = ['A'=>'bg-green-100 text-green-800','B'=>'bg-blue-100 text-blue-800','C'=>'bg-indigo-100 text-indigo-800','D'=>'bg-yellow-100 text-yellow-800','F'=>'bg-red-100 text-red-800'];
            foreach (CONDUCT_GRADES as $k => $label): ?>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold <?= $legendColors[$k] ?>">
                <strong><?= $k ?></strong> — <?= $label ?>
            </span>
            <?php endforeach; ?>
        </div>

        <?php if (empty($students)): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-10 text-center text-gray-500">
            No active students found for <strong><?= e($className) ?></strong>
            <?= $selSection ? '— ' . e($sectionName) : '' ?>.
        </div>
        <?php else: ?>

        <!-- Context banner -->
        <div class="text-sm text-gray-600 mb-3">
            <span class="font-medium"><?= e($className) ?></span>
            <?= $selSection ? ' &mdash; ' . e($sectionName) : '' ?>
            &nbsp;·&nbsp; <?= e($termName) ?>
            &nbsp;·&nbsp; <?= count($students) ?> students
            <?php $entered = count($conductMap); if ($entered): ?>
            &nbsp;·&nbsp; <span class="text-green-700 font-medium"><?= $entered ?> already saved</span>
            <?php endif; ?>
        </div>

        <form id="conductForm" method="POST" action="<?= url('exams', 'conduct-save') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="term_id"    value="<?= $selTerm ?>">
            <input type="hidden" name="class_id"   value="<?= $selClass ?>">
            <input type="hidden" name="session_id" value="<?= $sessionId ?>">
            <?php if ($selSection): ?>
            <input type="hidden" name="section_id" value="<?= $selSection ?>">
            <?php endif; ?>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide w-8">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide w-16">Adm. No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide w-8">Sex</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide w-44">
                                Conduct Grade
                                <div class="text-gray-400 font-normal normal-case tracking-normal">Behavior / Social / Habits</div>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">
                                Teacher Remark <span class="text-gray-400 font-normal">(optional)</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($students as $i => $st):
                            $saved   = $conductMap[$st['id']] ?? null;
                            $conduct = $saved['conduct'] ?? 'B'; // default Very Good
                            $remarks = $saved['remarks'] ?? '';
                            $isSaved = $saved !== null;
                        ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-2.5 text-gray-400 text-xs"><?= $i + 1 ?></td>
                            <td class="px-4 py-2.5">
                                <div class="font-medium text-gray-900">
                                    <?= e($st['first_name'] . ' ' . $st['last_name']) ?>
                                </div>
                                <?php if ($st['roll_no']): ?>
                                <div class="text-xs text-gray-400">Roll <?= e($st['roll_no']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2.5 text-gray-500 text-xs"><?= e($st['admission_no'] ?? '—') ?></td>
                            <td class="px-4 py-2.5 text-gray-500 text-xs"><?= e($st['gender'][0] ?? '—') ?></td>
                            <td class="px-4 py-2.5 text-center">
                                <select name="conduct[<?= $st['id'] ?>]"
                                        class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium w-full max-w-xs
                                               conduct-select"
                                        data-student="<?= $st['id'] ?>">
                                    <?php foreach (CONDUCT_GRADES as $gk => $gl): ?>
                                    <option value="<?= $gk ?>" <?= $conduct === $gk ? 'selected' : '' ?>>
                                        <?= $gk ?> — <?= $gl ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="px-4 py-2.5">
                                <input type="text"
                                       name="remarks[<?= $st['id'] ?>]"
                                       value="<?= e($remarks) ?>"
                                       maxlength="255"
                                       placeholder="e.g., Participates actively, respectful…"
                                       class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Bottom save button -->
            <div class="flex justify-end mt-4">
                <button type="submit"
                        class="flex items-center gap-2 px-5 py-2.5 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save All Conduct Grades
                </button>
            </div>
        </form>

        <script>
        // Color-code the conduct select on change
        const COLOR_MAP = {
            A: 'bg-green-50 border-green-400 text-green-800',
            B: 'bg-blue-50  border-blue-400  text-blue-800',
            C: 'bg-indigo-50 border-indigo-400 text-indigo-800',
            D: 'bg-yellow-50 border-yellow-400 text-yellow-800',
            F: 'bg-red-50   border-red-400   text-red-800',
        };
        const BASE = 'px-3 py-1.5 border rounded-lg text-sm font-medium w-full max-w-xs conduct-select';

        function applyColor(sel) {
            const val  = sel.value;
            const cols = COLOR_MAP[val] || '';
            sel.className = BASE + ' ' + cols;
        }

        document.querySelectorAll('.conduct-select').forEach(sel => {
            applyColor(sel);
            sel.addEventListener('change', () => applyColor(sel));
        });
        </script>

        <?php endif; ?>
    <?php endif; ?>

</div>

<?php
$pageContent = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
