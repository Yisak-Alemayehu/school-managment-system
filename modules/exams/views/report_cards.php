<?php
/**
 * Report Cards — Main View (Term-Based, Redesigned)
 * Dynamic AJAX class→section loading, professional layout
 */

$activeSession = get_active_session();
$sessionId     = $activeSession['id'] ?? 0;

$allClasses = db_fetch_all("SELECT id, name FROM classes WHERE is_active=1 ORDER BY sort_order");
$allTerms   = $sessionId
    ? db_fetch_all("SELECT id, name FROM terms WHERE session_id=? ORDER BY sort_order", [$sessionId])
    : [];

// Restrict class list for teachers
if (auth_has_role('teacher') && !auth_is_super_admin()) {
    $tClassIds = rbac_teacher_class_ids();
    if (!empty($tClassIds)) {
        $allClasses = array_values(array_filter($allClasses, fn($c) => in_array($c['id'], $tClassIds)));
    }
}

$selTerm    = input_int('term_id');
$selClass   = input_int('class_id');
$selSection = input_int('section_id');

// Validate teacher access
if ($selClass && auth_has_role('teacher') && !auth_is_super_admin()) {
    rbac_require_teacher_class($selClass);
}

$students    = [];
$reportCards = [];
$termName    = '';
$className   = '';

foreach ($allTerms as $t) if ($t['id'] == $selTerm) $termName = $t['name'];
foreach ($allClasses as $c) if ($c['id'] == $selClass) $className = $c['name'];

if ($selTerm && $selClass && $sessionId) {
    $where  = "WHERE e.class_id = ? AND e.session_id = ? AND e.status = 'active'";
    $params = [$selClass, $sessionId];
    if ($selSection) {
        $where .= " AND e.section_id = ?";
        $params[] = $selSection;
    }

    $students = db_fetch_all("
        SELECT s.id, s.first_name, s.last_name, s.admission_no, s.photo,
               e.roll_no, sec.name AS section_name
        FROM students s
        JOIN enrollments e ON e.student_id = s.id
        LEFT JOIN sections sec ON sec.id = e.section_id
        {$where}
        ORDER BY e.roll_no, s.first_name, s.last_name
    ", $params);

    // Check generated report cards
    if ($students) {
        $stIds = array_column($students, 'id');
        $ph = implode(',', array_fill(0, count($stIds), '?'));
        $rows = db_fetch_all(
            "SELECT student_id, id, total_marks, total_max_marks, percentage, grade, `rank`, status, generated_at
             FROM report_cards
             WHERE term_id = ? AND class_id = ? AND session_id = ? AND student_id IN ({$ph})",
            array_merge([$selTerm, $selClass, $sessionId], $stIds)
        );
        foreach ($rows as $r) {
            $reportCards[$r['student_id']] = $r;
        }
    }
}

$totalStudents  = count($students);
$totalGenerated = count($reportCards);

ob_start();
?>

<div class="max-w-6xl mx-auto">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-dark-text">Report Cards</h1>
            <p class="text-sm text-gray-500 dark:text-dark-muted mt-1"><?= e($activeSession['name'] ?? 'No Active Session') ?></p>
        </div>
        <?php if ($selTerm && $selClass && !empty($students)): ?>
        <div class="flex items-center gap-3">
            <?php if (auth_has_permission('report_card.manage')): ?>
            <form method="POST" action="<?= url('exams', 'report-card-generate') ?>" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="term_id" value="<?= $selTerm ?>">
                <input type="hidden" name="class_id" value="<?= $selClass ?>">
                <input type="hidden" name="section_id" value="<?= $selSection ?>">
                <button type="submit" onclick="return confirm('Generate/regenerate report cards for all <?= $totalStudents ?> students?')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Generate All
                </button>
            </form>
            <?php endif; ?>
            <?php if ($totalGenerated > 0): ?>
            <button type="button" id="rcPrintAll"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print All (<?= $totalGenerated ?>)
            </button>
            <button type="button" id="rcPrintSelected" disabled
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Print Selected (<span id="rcSelectedCount">0</span>)
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filters Card -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border shadow-sm p-5 mb-6">
        <form method="GET" id="rcFilterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <input type="hidden" name="module" value="exams">
            <input type="hidden" name="action" value="report-cards">

            <!-- Term Selector -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Term <span class="text-red-500">*</span></label>
                <select name="term_id" required
                        class="w-full px-3 py-2.5 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    <option value="">Select Term</option>
                    <?php foreach ($allTerms as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $selTerm == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Class Selector (triggers AJAX) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Class <span class="text-red-500">*</span></label>
                <select name="class_id" id="rcClassSelect" required
                        class="w-full px-3 py-2.5 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                        onchange="ajaxLoadSections(this.value, 'rcSectionSelect', <?= (int)$selSection ?>, 'All Sections')">
                    <option value="">Select Class</option>
                    <?php foreach ($allClasses as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selClass == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Section Selector (AJAX populated) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Section</label>
                <div class="relative">
                    <select name="section_id" id="rcSectionSelect"
                            class="w-full px-3 py-2.5 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                            <?= !$selClass ? 'disabled' : '' ?>>
                        <option value="">All Sections</option>
                    </select>
                    <!-- Loading spinner -->
                    <div id="rcSectionSpinner" class="hidden absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-4 w-4 text-primary-600" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Filter Button -->
            <div>
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    View Students
                </button>
            </div>

            <!-- Stats (only shown when filtered) -->
            <?php if ($selTerm && $selClass && !empty($students)): ?>
            <div class="flex items-center gap-4 text-sm">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 font-medium">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                    <?= $totalStudents ?> Students
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full <?= $totalGenerated === $totalStudents ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300' : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300' ?> font-medium">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <?= $totalGenerated ?>/<?= $totalStudents ?> Generated
                </span>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($selTerm && $selClass): ?>
        <?php if (empty($students)): ?>
        <!-- Empty State -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border shadow-sm p-16 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <p class="text-gray-500 dark:text-dark-muted text-sm">No students found for the selected class and section.</p>
        </div>
        <?php else: ?>
        <!-- Student List Table -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-dark-bg border-b border-gray-200 dark:border-dark-border">
                            <th class="px-3 py-3.5 text-center w-10">
                                <input type="checkbox" id="rcSelectAll" class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer" title="Select / Deselect All">
                            </th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase tracking-wider">#</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase tracking-wider">Student</th>
                            <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase tracking-wider">Roll No</th>
                            <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase tracking-wider">Section</th>
                            <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase tracking-wider">Total</th>
                            <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase tracking-wider">Average</th>
                            <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase tracking-wider">Grade</th>
                            <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase tracking-wider">Rank</th>
                            <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                        <?php foreach ($students as $i => $st): ?>
                        <?php $rc = $reportCards[$st['id']] ?? null; ?>
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-dark-bg/50 transition-colors">
                            <td class="px-3 py-3.5 text-center">
                                <?php if ($rc): ?>
                                <input type="checkbox" class="rc-select-cb w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer" value="<?= $rc['id'] ?>">
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-gray-400 dark:text-dark-muted"><?= $i + 1 ?></td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <?php if ($st['photo']): ?>
                                        <img src="<?= upload_url($st['photo']) ?>" alt="" class="w-9 h-9 rounded-full object-cover border border-gray-200 dark:border-dark-border">
                                    <?php else: ?>
                                        <div class="w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-700 dark:text-primary-300 text-sm font-semibold">
                                            <?= strtoupper(substr($st['first_name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($st['first_name'] . ' ' . $st['last_name']) ?></div>
                                        <div class="text-xs text-gray-500 dark:text-dark-muted"><?= e($st['admission_no']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-center text-gray-600 dark:text-dark-muted"><?= e($st['roll_no'] ?? '—') ?></td>
                            <td class="px-5 py-3.5 text-sm text-center text-gray-600 dark:text-dark-muted"><?= e($st['section_name'] ?? '—') ?></td>
                            <td class="px-5 py-3.5 text-sm text-center text-gray-700 dark:text-dark-text font-medium">
                                <?= $rc ? number_format($rc['total_marks'], 0) . '/' . number_format($rc['total_max_marks'], 0) : '—' ?>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-center font-semibold <?= $rc && $rc['percentage'] >= 50 ? 'text-green-600' : ($rc ? 'text-red-600' : 'text-gray-400') ?>">
                                <?= $rc ? number_format($rc['percentage'], 1) . '%' : '—' ?>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <?php if ($rc && $rc['grade']): ?>
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-bold
                                    <?= match($rc['grade']) {
                                        'A' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                        'B' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                        'C' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                        'D' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                                        default => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                    } ?>">
                                    <?= e($rc['grade']) ?>
                                </span>
                                <?php else: ?>
                                <span class="text-gray-400 dark:text-gray-500">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-center font-semibold text-gray-700 dark:text-dark-text"><?= $rc['rank'] ?? '—' ?></td>
                            <td class="px-5 py-3.5 text-center">
                                <?php if ($rc): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Generated
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-dark-card2 dark:text-dark-muted">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                    Pending
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <?php if ($rc): ?>
                                <a href="<?= url('exams', 'report-card-print') ?>&id=<?= $rc['id'] ?>" target="_blank"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300 rounded-lg text-xs font-medium hover:bg-primary-100 dark:hover:bg-primary-900/40 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    View / Print
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    <?php else: ?>
    <!-- Initial State -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border shadow-sm p-16 text-center">
        <svg class="w-20 h-20 mx-auto text-gray-200 dark:text-gray-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="text-gray-600 dark:text-gray-400 font-medium mb-1">Select Filters to View Report Cards</h3>
        <p class="text-sm text-gray-400 dark:text-gray-500">Choose a term and class above, then click <strong>View Students</strong> to see report cards.</p>
    </div>
    <?php endif; ?>
</div>

<!-- AJAX Section Loading Enhancement -->
<script>
(function() {
    var classSelect = document.getElementById('rcClassSelect');
    if (!classSelect) return;

    var origHandler = classSelect.getAttribute('onchange');
    classSelect.removeAttribute('onchange');

    classSelect.addEventListener('change', function() {
        var spinner = document.getElementById('rcSectionSpinner');
        if (spinner) spinner.classList.remove('hidden');

        ajaxLoadSections(this.value, 'rcSectionSelect', <?= (int)$selSection ?>, 'All Sections');

        // Hide spinner after sections load
        setTimeout(function() {
            if (spinner) spinner.classList.add('hidden');
        }, 1500);
    });

    // Load sections on page load if class is pre-selected
    <?php if ($selClass): ?>
    ajaxLoadSections(<?= (int)$selClass ?>, 'rcSectionSelect', <?= (int)$selSection ?>, 'All Sections');
    <?php endif; ?>
})();
</script>

<?php if ($totalGenerated > 0): ?>
<script>
(function() {
    var printUrl = '<?= url('exams', 'report-card-print') ?>';
    var allIds = [<?= implode(',', array_column($reportCards, 'id')) ?>];

    // Select All checkbox
    var selectAll = document.getElementById('rcSelectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.rc-select-cb').forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
            updateSelectedCount();
        });
    }

    // Individual checkbox changes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('rc-select-cb')) {
            updateSelectedCount();
            if (selectAll) {
                var total = document.querySelectorAll('.rc-select-cb').length;
                var checked = document.querySelectorAll('.rc-select-cb:checked').length;
                selectAll.checked = checked === total;
                selectAll.indeterminate = checked > 0 && checked < total;
            }
        }
    });

    function updateSelectedCount() {
        var count = document.querySelectorAll('.rc-select-cb:checked').length;
        var countEl = document.getElementById('rcSelectedCount');
        var btn = document.getElementById('rcPrintSelected');
        if (countEl) countEl.textContent = count;
        if (btn) btn.disabled = count === 0;
    }

    // Print All button
    var printAllBtn = document.getElementById('rcPrintAll');
    if (printAllBtn) {
        printAllBtn.addEventListener('click', function() {
            if (allIds.length === 0) return;
            window.open(printUrl + '&ids=' + allIds.join(','), '_blank');
        });
    }

    // Print Selected button
    var printSelBtn = document.getElementById('rcPrintSelected');
    if (printSelBtn) {
        printSelBtn.addEventListener('click', function() {
            var selected = [];
            document.querySelectorAll('.rc-select-cb:checked').forEach(function(cb) {
                selected.push(cb.value);
            });
            if (selected.length === 0) return;
            window.open(printUrl + '&ids=' + selected.join(','), '_blank');
        });
    }
})();
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
