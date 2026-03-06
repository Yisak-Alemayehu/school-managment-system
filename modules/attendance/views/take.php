<?php
/**
 * Attendance — Take Attendance View
 */

$classes  = db_fetch_all("SELECT id, name FROM classes ORDER BY sort_order ASC");
$sections = db_fetch_all("SELECT id, name, class_id FROM sections ORDER BY name ASC");

$filterClass   = input_int('class_id');
$filterSection = input_int('section_id');
$filterDate    = input('date') ?: date('Y-m-d');

$activeSession = get_active_session();
$sessionId     = $activeSession['id'] ?? 0;

$students = [];
$existing = [];

if ($filterClass && $sessionId) {
    $where  = "WHERE e.class_id = ? AND e.session_id = ? AND e.status = 'active'";
    $params = [$filterClass, $sessionId];
    if ($filterSection) {
        $where .= " AND e.section_id = ?";
        $params[] = $filterSection;
    }

    $students = db_fetch_all("
        SELECT s.id, s.first_name, s.last_name, s.admission_no, s.photo, e.section_id
        FROM students s
        JOIN enrollments e ON e.student_id = s.id
        {$where}
        ORDER BY s.first_name, s.last_name
    ", $params);

    // Existing attendance for this date
    $studentIds = array_column($students, 'id');
    if ($studentIds) {
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $rows = db_fetch_all(
            "SELECT student_id, status, remarks FROM attendance WHERE date = ? AND student_id IN ({$placeholders})",
            array_merge([$filterDate], $studentIds)
        );
        foreach ($rows as $r) {
            $existing[$r['student_id']] = $r;
        }
    }
}

ob_start();
?>

<div class="max-w-5xl mx-auto">
    <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text mb-6">Take Attendance</h1>

    <!-- Filters -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 mb-6">
        <form method="GET" action="" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <input type="hidden" name="module" value="attendance">
            <input type="hidden" name="action" value="index">

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Class</label>
                <select name="class_id" id="attClassSel" required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text"
                        onchange="ajaxLoadSections(this.value,'attSecSel',<?= (int)$filterSection ?>, 'All Sections')">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filterClass == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Section</label>
                <select name="section_id" id="attSecSel" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text"
                        <?= !$filterClass ? 'disabled' : '' ?>>
                    <option value="">All Sections</option>
                    <?php foreach ($sections as $s): ?>
                        <?php if ($s['class_id'] == $filterClass): ?>
                            <option value="<?= $s['id'] ?>" <?= $filterSection == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                <input type="date" name="date" value="<?= e($filterDate) ?>" max="<?= date('Y-m-d') ?>"
                       onchange="this.form.submit()"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 hover:bg-gray-200 text-gray-700 dark:text-gray-300 font-medium rounded-lg text-sm transition">Load</button>
            </div>
        </form>
    </div>

    <?php if ($filterClass && !empty($students)): ?>
    <!-- Quick Actions -->
    <div class="flex items-center gap-2 mb-4">
        <span class="text-sm text-gray-500 dark:text-dark-muted"><?= count($students) ?> students</span>
        <div class="ml-auto flex gap-2">
            <button onclick="markAll('present')" class="px-3 py-1 bg-green-100 text-green-800 rounded-lg text-xs font-medium hover:bg-green-200">All Present</button>
            <button onclick="markAll('absent')" class="px-3 py-1 bg-red-100 text-red-800 rounded-lg text-xs font-medium hover:bg-red-200">All Absent</button>
        </div>
    </div>

    <form method="POST" action="<?= url('attendance', 'save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="class_id" value="<?= $filterClass ?>">
        <input type="hidden" name="section_id" value="<?= $filterSection ?>">
        <input type="hidden" name="date" value="<?= e($filterDate) ?>">
        <input type="hidden" name="session_id" value="<?= $sessionId ?>">

        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-dark-bg border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Student</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php foreach ($students as $i => $st): ?>
                        <?php $ex = $existing[$st['id']] ?? null; ?>
                        <tr class="hover:bg-gray-50 dark:bg-dark-bg" id="row-<?= $st['id'] ?>">
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted"><?= $i + 1 ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xs font-bold">
                                        <?= strtoupper(substr($st['first_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($st['first_name'] . ' ' . $st['last_name']) ?></div>
                                        <div class="text-xs text-gray-500 dark:text-dark-muted"><?= e($st['admission_no']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <input type="hidden" name="students[<?= $st['id'] ?>][id]" value="<?= $st['id'] ?>">
                                <div class="flex items-center justify-center gap-1">
                                    <?php
                                    $statuses = ['present' => 'P', 'absent' => 'A', 'late' => 'L', 'excused' => 'E'];
                                    $colors   = ['present' => 'green', 'absent' => 'red', 'late' => 'yellow', 'excused' => 'blue'];
                                    foreach ($statuses as $val => $label):
                                        $checked = ($ex['status'] ?? 'present') === $val;
                                    ?>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="students[<?= $st['id'] ?>][status]" value="<?= $val ?>" <?= $checked ? 'checked' : '' ?> class="sr-only peer">
                                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-bold border-2 
                                                peer-checked:bg-<?= $colors[$val] ?>-500 peer-checked:text-white peer-checked:border-<?= $colors[$val] ?>-500
                                                border-gray-200 dark:border-dark-border text-gray-400 dark:text-gray-500 hover:border-<?= $colors[$val] ?>-300 transition">
                                                <?= $label ?>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <input type="text" name="students[<?= $st['id'] ?>][remarks]" value="<?= e($ex['remarks'] ?? '') ?>"
                                       placeholder="Optional" class="w-full px-2 py-1 border border-gray-200 dark:border-dark-border rounded text-xs focus:ring-1 focus:ring-primary-500">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
          </div>
        </div>

        <div class="mt-4 mb-20 lg:mb-4 flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                Save Attendance
            </button>
        </div>
    </form>

    <?php elseif ($filterClass): ?>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-8 text-center text-gray-500 dark:text-dark-muted">No students found for the selected class/section.</div>
    <?php endif; ?>
</div>

<script>
function markAll(status) {
    document.querySelectorAll('input[type="radio"][value="' + status + '"]').forEach(r => r.checked = true);
}
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
