<?php
/**
 * Students — Generate ID Cards
 * Selection interface with search, class/section filters, checkboxes.
 * Supports Print All / Print Selected → opens id-card-print view.
 */

$classes   = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");
$classId   = input_int('class_id');
$sectionId = input_int('section_id');
$search    = trim(input('search'));

$sections = [];
if ($classId) {
    $sections = db_fetch_all("SELECT id, name FROM sections WHERE class_id = ? AND is_active = 1 ORDER BY name", [$classId]);
}

$students = [];
$where = ["s.deleted_at IS NULL", "s.status = 'active'", "e.status = 'active'"];
$params = [];

if ($sectionId) {
    $where[]  = "e.section_id = ?";
    $params[] = $sectionId;
} elseif ($classId) {
    $where[]  = "c.id = ?";
    $params[] = $classId;
}

if ($search !== '') {
    $where[]  = "(s.full_name LIKE ? OR s.admission_no LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($classId || $search !== '') {
    $whereClause = implode(' AND ', $where);
    $students = db_fetch_all(
        "SELECT s.id, s.full_name, s.admission_no, s.gender, s.date_of_birth, s.photo,
                c.name AS class_name, sec.name AS section_name
           FROM students s
           JOIN enrollments e ON e.student_id = s.id
           JOIN sections sec ON e.section_id = sec.id
           JOIN classes c ON sec.class_id = c.id
          WHERE {$whereClause}
          ORDER BY c.sort_order, sec.name, s.full_name",
        $params
    );
}

ob_start();
?>

<div class="space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Generate ID Cards</h1>
            <?php if (!empty($students)): ?>
            <p class="text-sm text-gray-500 dark:text-dark-muted"><?= count($students) ?> student(s) found</p>
            <?php endif; ?>
        </div>
        <?php if (!empty($students)): ?>
        <div class="flex gap-2 flex-wrap">
            <button type="button" id="btnPrintAll"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-800 text-white text-sm rounded-lg hover:bg-primary-900 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print All (<?= count($students) ?>)
            </button>
            <button type="button" id="btnPrintSelected" disabled
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Print Selected (<span id="selectedCount">0</span>)
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?= url('students', 'id-cards') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search Student</label>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Name or Admission No…"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Class</label>
                <select name="class_id" id="idcClassSel"
                        onchange="ajaxLoadSections(this.value,'idcSecSel',0,'All Sections')"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Class…</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $classId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Section</label>
                <select name="section_id" id="idcSecSel"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    <option value="">All Sections</option>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= $sec['id'] ?>" <?= $sectionId == $sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-900 font-medium">Filter</button>
                <a href="<?= url('students', 'id-cards') ?>" class="px-4 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text hover:bg-gray-50">Clear</a>
            </div>
        </div>
    </form>

    <!-- Student List -->
    <?php if (empty($students) && ($classId || $search !== '')): ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-12 text-center">
        <p class="text-gray-400 dark:text-gray-500 text-sm">No students found.</p>
    </div>
    <?php elseif (!$classId && $search === ''): ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-12 text-center">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2"/>
        </svg>
        <p class="text-gray-400 dark:text-gray-500 text-sm">Select a class or search by name to find students.</p>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full responsive-table">
                <thead class="bg-gray-50 dark:bg-dark-bg border-b">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Admission No</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Class</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Gender</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php foreach ($students as $i => $st): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg">
                        <td class="px-4 py-3">
                            <input type="checkbox" class="student-cb rounded border-gray-300 text-primary-600 focus:ring-primary-500" value="<?= $st['id'] ?>">
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500"><?= $i + 1 ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <?php if ($st['photo']): ?>
                                    <img src="/uploads/students/<?= e($st['photo']) ?>" class="w-8 h-8 rounded-full object-cover border">
                                <?php else: ?>
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-800 text-sm font-bold">
                                        <?= strtoupper(mb_substr($st['full_name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <span class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($st['full_name']) ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300"><?= e($st['admission_no']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300"><?= e($st['class_name']) ?><?= $st['section_name'] ? ' – ' . e($st['section_name']) : '' ?></td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300"><?= e(ucfirst($st['gender'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($students)): ?>
<script>
(function(){
    const allIds = [<?= implode(',', array_column($students, 'id')) ?>];
    const cbs = document.querySelectorAll('.student-cb');
    const selAll = document.getElementById('selectAll');
    const btnAll = document.getElementById('btnPrintAll');
    const btnSel = document.getElementById('btnPrintSelected');
    const countEl = document.getElementById('selectedCount');

    function updateCount() {
        const checked = document.querySelectorAll('.student-cb:checked');
        countEl.textContent = checked.length;
        btnSel.disabled = checked.length === 0;
    }

    selAll.addEventListener('change', function() {
        cbs.forEach(cb => cb.checked = this.checked);
        updateCount();
    });
    cbs.forEach(cb => cb.addEventListener('change', function() {
        selAll.checked = document.querySelectorAll('.student-cb:checked').length === cbs.length;
        updateCount();
    }));

    btnAll.addEventListener('click', function() {
        window.open('<?= url('students', 'id-card-print') ?>&ids=' + allIds.join(','), '_blank');
    });
    btnSel.addEventListener('click', function() {
        const ids = Array.from(document.querySelectorAll('.student-cb:checked')).map(c => c.value);
        if (ids.length) window.open('<?= url('students', 'id-card-print') ?>&ids=' + ids.join(','), '_blank');
    });
})();
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
