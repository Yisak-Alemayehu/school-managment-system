<?php
/**
 * Results — Add Assessment
 * Per-assessment marks are user-defined. Sum for a class+subject+term must total 100.
 */

$activeSession = get_active_session();
$sessionId     = $activeSession['id'] ?? 0;

$classes = db_fetch_all("SELECT id, name FROM classes WHERE is_active=1 ORDER BY sort_order");
$terms   = $sessionId
    ? db_fetch_all("SELECT id, name FROM terms WHERE session_id=? ORDER BY sort_order", [$sessionId])
    : [];

// Right-panel list filters (GET-only, no form auto-submit)
$filterClass = input_int('filter_class');
$filterTerm  = input_int('filter_term');

$listWhere  = "WHERE a.session_id = ?";
$listParams = [$sessionId];
if ($filterClass) { $listWhere .= " AND a.class_id = ?"; $listParams[] = $filterClass; }
if ($filterTerm)  { $listWhere .= " AND a.term_id = ?";  $listParams[] = $filterTerm; }

$assessments = $sessionId ? db_fetch_all("
    SELECT a.*, c.name AS class_name, s.name AS subject_name, t.name AS term_name
    FROM assessments a
    JOIN classes  c ON c.id = a.class_id
    JOIN subjects s ON s.id = a.subject_id
    LEFT JOIN terms t ON t.id = a.term_id
    {$listWhere}
    ORDER BY c.name, t.name, s.name, a.created_at
", $listParams) : [];

// Group by class+subject+term to show per-group totals
$grouped = [];
foreach ($assessments as $row) {
    $key = $row['class_id'] . '_' . $row['subject_id'] . '_' . ($row['term_id'] ?? 0);
    if (!isset($grouped[$key])) {
        $grouped[$key] = [
            'class'   => $row['class_name'],
            'subject' => $row['subject_name'],
            'term'    => $row['term_name'] ?? '—',
            'total'   => 0,
            'rows'    => [],
        ];
    }
    $grouped[$key]['total'] += (int)$row['total_marks'];
    $grouped[$key]['rows'][] = $row;
}

ob_start();
?>

<div class="max-w-6xl mx-auto">
    <h1 class="text-xl font-bold text-gray-900 mb-6">Add Assessment</h1>

    <?php partial('flash'); ?>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        <!-- ── Left: Form (2/5) ── -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">New Assessment</h2>

                <form method="POST" action="<?= url('exams', 'assessment-save') ?>" id="assessmentForm" novalidate>
                    <?= csrf_field() ?>

                    <div class="space-y-4">

                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assessment Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="f_name" required placeholder="e.g. Test 1, Final Exam"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>

                        <!-- Term -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Term <span class="text-red-500">*</span></label>
                            <select name="term_id" id="f_term" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                                <option value="">Select Term</option>
                                <?php foreach ($terms as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Class (no auto-submit) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Class <span class="text-red-500">*</span></label>
                            <select name="class_id" id="f_class" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Subject (loaded via AJAX) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subject <span class="text-red-500">*</span></label>
                            <select name="subject_id" id="f_subject" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500" disabled>
                                <option value="">— Select Class First —</option>
                            </select>
                        </div>

                        <!-- Marks for this assessment -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Marks for This Assessment <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="total_marks" id="f_marks" required
                                   min="1" max="100" placeholder="e.g. 50"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                            <p class="text-xs text-gray-400 mt-1">All assessments for a subject must sum to exactly 100.</p>
                        </div>

                        <!-- Live total indicator -->
                        <div id="totalBadge" class="hidden rounded-lg px-3 py-2 text-sm border">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <span class="text-gray-600">Committed so far: <strong id="badgeCommitted">0</strong></span>
                                <span class="text-gray-600">This entry: <strong id="badgeThis">0</strong></span>
                                <span class="font-semibold">Total: <strong id="badgeTotal">0</strong> / 100</span>
                            </div>
                            <!-- progress bar -->
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                <div id="totalBar" class="h-2 rounded-full transition-all" style="width:0%"></div>
                            </div>
                            <p id="totalMsg" class="text-xs mt-1"></p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="description" rows="2" placeholder="Optional notes…"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 resize-none"></textarea>
                        </div>

                        <button type="submit" id="saveBtn"
                                class="w-full py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Save Assessment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── Right: Existing assessments (3/5) ── -->
        <div class="lg:col-span-3">

            <!-- Filter bar (its own GET form — only for filtering the list) -->
            <form method="GET" class="flex flex-wrap gap-3 mb-4 items-end">
                <input type="hidden" name="module" value="exams">
                <input type="hidden" name="action" value="add-assessment">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Filter by Class</label>
                    <select name="filter_class" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $filterClass == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Filter by Term</label>
                    <select name="filter_term" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
                        <option value="">All Terms</option>
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $filterTerm == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="px-4 py-1.5 bg-gray-700 text-white rounded-lg text-sm hover:bg-gray-800 transition">Filter</button>
            </form>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700">Existing Assessments</span>
                    <span class="text-xs text-gray-400"><?= count($assessments) ?> assessment(s) in <?= count($grouped) ?> group(s)</span>
                </div>

                <?php if (empty($grouped)): ?>
                <div class="p-10 text-center text-gray-400 text-sm">No assessments found. Create one using the form.</div>
                <?php else: ?>

                <?php foreach ($grouped as $grp): ?>
                <?php
                    $grpTotal = $grp['total'];
                    $remaining = 100 - $grpTotal;
                    if ($grpTotal === 100) {
                        $badgeCls = 'bg-green-100 text-green-800';
                        $barCls   = 'bg-green-500';
                        $statusTxt = 'Complete ✓';
                    } elseif ($grpTotal > 100) {
                        $badgeCls = 'bg-red-100 text-red-800';
                        $barCls   = 'bg-red-500';
                        $statusTxt = 'Exceeds 100!';
                    } else {
                        $badgeCls = 'bg-amber-100 text-amber-800';
                        $barCls   = 'bg-amber-400';
                        $statusTxt = $remaining . ' remaining';
                    }
                    $barWidth = min(100, $grpTotal);
                ?>
                <!-- Group header -->
                <div class="px-4 py-2.5 bg-gray-50 border-b flex items-center justify-between gap-3">
                    <div class="text-sm">
                        <span class="font-semibold text-gray-800"><?= e($grp['class']) ?></span>
                        <span class="text-gray-400 mx-1">›</span>
                        <span class="text-gray-700"><?= e($grp['subject']) ?></span>
                        <span class="text-gray-400 mx-1">›</span>
                        <span class="text-gray-500 text-xs"><?= e($grp['term']) ?></span>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <div class="w-20 bg-gray-200 rounded-full h-1.5">
                            <div class="<?= $barCls ?> h-1.5 rounded-full" style="width:<?= $barWidth ?>%"></div>
                        </div>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $badgeCls ?>">
                            <?= $grpTotal ?>/100 — <?= $statusTxt ?>
                        </span>
                    </div>
                </div>
                <table class="w-full text-sm border-b">
                    <thead class="bg-white border-b text-xs font-medium text-gray-400 uppercase">
                        <tr>
                            <th class="px-4 py-1.5 text-left pl-8">Assessment</th>
                            <th class="px-4 py-1.5 text-center w-16">Marks</th>
                            <th class="px-4 py-1.5 text-right w-32"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($grp['rows'] as $a): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 pl-8 text-gray-800"><?= e($a['name']) ?>
                                <?php if ($a['description']): ?>
                                    <span class="text-gray-400 text-xs ml-1">— <?= e(mb_strimwidth($a['description'], 0, 40, '…')) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-xs font-medium"><?= (int)$a['total_marks'] ?></span>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <form method="POST" action="<?= url('exams', 'assessment-delete') ?>id=<?= $a['id'] ?>" class="inline"
                                      onsubmit="return confirm('Delete this assessment and all its student results?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-xs text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── JavaScript: AJAX subjects + live total validation ── -->
<script>
(function () {
    var fClass   = document.getElementById('f_class');
    var fTerm    = document.getElementById('f_term');
    var fSubject = document.getElementById('f_subject');
    var fMarks   = document.getElementById('f_marks');
    var badge    = document.getElementById('totalBadge');
    var bCommit  = document.getElementById('badgeCommitted');
    var bThis    = document.getElementById('badgeThis');
    var bTotal   = document.getElementById('badgeTotal');
    var bar      = document.getElementById('totalBar');
    var msg      = document.getElementById('totalMsg');
    var saveBtn  = document.getElementById('saveBtn');
    var form     = document.getElementById('assessmentForm');

    var currentCommitted = 0;

    // ── Load subjects for chosen class ──
    function loadSubjects() {
        var classId = fClass.value;
        fSubject.innerHTML = '<option value="">Loading…</option>';
        fSubject.disabled  = true;
        currentCommitted   = 0;
        updateBadge();

        if (!classId) {
            fSubject.innerHTML = '<option value="">— Select Class First —</option>';
            return;
        }
        fetch('<?= url('exams', 'ajax-subjects') ?>class_id=' + classId)
            .then(function(r){ return r.json(); })
            .then(function(data){
                fSubject.innerHTML = '<option value="">Select Subject</option>';
                if (!data.length) {
                    fSubject.innerHTML = '<option value="">No subjects for this class</option>';
                    return;
                }
                data.forEach(function(s){
                    var o = document.createElement('option');
                    o.value = s.id; o.textContent = s.name;
                    fSubject.appendChild(o);
                });
                fSubject.disabled = false;
            })
            .catch(function(){
                fSubject.innerHTML = '<option value="">Error loading subjects</option>';
            });
    }

    // ── Load committed total for class+subject+term ──
    function loadTotal() {
        var classId   = fClass.value;
        var subjectId = fSubject.value;
        var termId    = fTerm.value;
        currentCommitted = 0;
        if (!classId || !subjectId) { updateBadge(); return; }
        var url = '<?= url('exams', 'ajax-subject-total') ?>class_id=' + classId
                  + '&subject_id=' + subjectId
                  + (termId ? '&term_id=' + termId : '');
        fetch(url)
            .then(function(r){ return r.json(); })
            .then(function(data){
                currentCommitted = data.total || 0;
                updateBadge();
            })
            .catch(function(){ currentCommitted = 0; updateBadge(); });
    }

    // ── Recalculate and render the live badge ──
    function updateBadge() {
        var thisVal  = parseInt(fMarks.value, 10) || 0;
        var combined = currentCommitted + thisVal;
        var remain   = 100 - combined;

        bCommit.textContent = currentCommitted;
        bThis.textContent   = thisVal;
        bTotal.textContent  = combined;

        var pct = Math.min(100, combined);
        bar.style.width = pct + '%';

        badge.classList.remove('hidden', 'border-green-300', 'bg-green-50',
                                         'border-amber-300', 'bg-amber-50',
                                         'border-red-300',   'bg-red-50');

        if (combined === 100) {
            badge.classList.add('border-green-300', 'bg-green-50');
            bar.className   = 'h-2 rounded-full transition-all bg-green-500';
            msg.textContent = '✓ Total is exactly 100. Ready to save.';
            msg.className   = 'text-xs mt-1 text-green-700 font-medium';
            saveBtn.disabled = false;
        } else if (combined > 100) {
            badge.classList.add('border-red-300', 'bg-red-50');
            bar.className   = 'h-2 rounded-full transition-all bg-red-500';
            msg.textContent = '✗ Total would be ' + combined + '. Exceeds 100 by ' + (combined - 100) + ' mark(s).';
            msg.className   = 'text-xs mt-1 text-red-700 font-medium';
            saveBtn.disabled = true;
        } else {
            badge.classList.add('border-amber-300', 'bg-amber-50');
            bar.className   = 'h-2 rounded-full transition-all bg-amber-400';
            msg.textContent = remain + ' mark(s) still available for this subject.';
            msg.className   = 'text-xs mt-1 text-amber-700';
            saveBtn.disabled = false; // allow partial saves
        }
    }

    // ── Prevent submit if total > 100 ──
    form.addEventListener('submit', function(e) {
        var thisVal  = parseInt(fMarks.value, 10) || 0;
        var combined = currentCommitted + thisVal;
        if (combined > 100) {
            e.preventDefault();
            alert('Cannot save: the total marks for this subject would be ' + combined + '/100.\nPlease reduce the marks for this assessment.');
        }
    });

    // ── Event bindings ──
    fClass.addEventListener('change', function() { loadSubjects(); });
    fSubject.addEventListener('change', function() { loadTotal(); });
    fTerm.addEventListener('change', function() { if (fSubject.value) loadTotal(); });
    fMarks.addEventListener('input', function() { updateBadge(); });

    // Init
    updateBadge();
})();
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
