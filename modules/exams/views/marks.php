<?php
/**
 * Exams — Enter Marks View
 */

$scheduleId = input_int('schedule_id');
$schedule = null;
if ($scheduleId) {
    $schedule = db_fetch_one("
        SELECT es.*, e.name AS exam_name, c.name AS class_name, sub.name AS subject_name
        FROM exam_schedules es
        JOIN exams e ON e.id = es.exam_id
        JOIN classes c ON c.id = es.class_id
        JOIN subjects sub ON sub.id = es.subject_id
        WHERE es.id = ?
    ", [$scheduleId]);

    // Teacher can only access marks for their assigned classes
    if ($schedule && auth_has_role('teacher') && !auth_is_super_admin()) {
        rbac_require_teacher_class((int)$schedule['class_id']);
    }
}

// If no schedule selected, show selector
$exams = db_fetch_all("SELECT id, name FROM exams WHERE session_id = ? ORDER BY start_date DESC", [get_active_session()['id'] ?? 0]);
$schedules = [];
$students  = [];
$existingMarks = [];

$filterExam = input_int('exam_id');

if ($filterExam) {
    $schedSql = "SELECT es.id, es.class_id, c.name AS class_name, sub.name AS subject_name
        FROM exam_schedules es
        JOIN classes c ON c.id = es.class_id
        JOIN subjects sub ON sub.id = es.subject_id
        WHERE es.exam_id = ?";
    $schedParams = [$filterExam];

    // Restrict to teacher's classes
    if (auth_has_role('teacher') && !auth_is_super_admin()) {
        $tClassIds = rbac_teacher_class_ids();
        if (!empty($tClassIds)) {
            $ph = implode(',', array_fill(0, count($tClassIds), '?'));
            $schedSql .= " AND es.class_id IN ({$ph})";
            $schedParams = array_merge($schedParams, $tClassIds);
        }
    }

    $schedSql .= " ORDER BY c.level ASC, sub.name ASC";
    $schedules = db_fetch_all($schedSql, $schedParams);
}

if ($schedule) {
    $activeSession = get_active_session();
    $students = db_fetch_all("
        SELECT s.id, s.first_name, s.last_name, s.admission_no
        FROM students s
        JOIN enrollments e ON e.student_id = s.id
        WHERE e.class_id = ? AND e.session_id = ? AND e.status = 'active'
        ORDER BY s.first_name, s.last_name
    ", [$schedule['class_id'], $activeSession['id'] ?? 0]);

    // Existing marks
    $rows = db_fetch_all("SELECT student_id, marks_obtained, is_absent FROM marks WHERE exam_schedule_id = ?", [$scheduleId]);
    foreach ($rows as $r) {
        $existingMarks[$r['student_id']] = $r;
    }
}

ob_start();
?>

<div class="max-w-5xl mx-auto">
    <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text mb-6">Enter Marks</h1>

    <?php if (!$schedule): ?>
    <!-- Selection -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <input type="hidden" name="module" value="exams">
            <input type="hidden" name="action" value="marks">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Exam</label>
                <select name="exam_id" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                    <option value="">Select Exam</option>
                    <?php foreach ($exams as $ex): ?>
                        <option value="<?= $ex['id'] ?>" <?= $filterExam == $ex['id'] ? 'selected' : '' ?>><?= e($ex['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($schedules)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Class / Subject</label>
                <select name="schedule_id" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                    <option value="">Select</option>
                    <?php foreach ($schedules as $sc): ?>
                        <option value="<?= $sc['id'] ?>" <?= $scheduleId == $sc['id'] ? 'selected' : '' ?>><?= e($sc['class_name'] . ' — ' . $sc['subject_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div>
                <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">Load</button>
            </div>
        </form>
    </div>

    <?php else: ?>
    <!-- Info bar -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
        <div class="flex flex-wrap items-center gap-4 text-sm">
            <span class="font-semibold text-blue-900"><?= e($schedule['exam_name']) ?></span>
            <span class="text-blue-700"><?= e($schedule['class_name']) ?></span>
            <span class="text-blue-700"><?= e($schedule['subject_name']) ?></span>
            <span class="text-blue-600">Full Marks: <?= $schedule['full_marks'] ?></span>
            <span class="text-blue-600">Pass: <?= $schedule['pass_marks'] ?></span>
        </div>
    </div>

    <form method="POST" action="<?= url('exams', 'marks-save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="exam_schedule_id" value="<?= $scheduleId ?>">

        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
            <div class="overflow-x-auto"><table class="w-full">
                <thead class="bg-gray-50 dark:bg-dark-bg border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Student</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-dark-muted uppercase w-32">Marks (<?= $schedule['full_marks'] ?>)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-dark-muted uppercase w-20">Absent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php foreach ($students as $i => $st): ?>
                        <?php $em = $existingMarks[$st['id']] ?? null; ?>
                        <tr class="hover:bg-gray-50 dark:bg-dark-bg">
                            <td class="px-4 py-2.5 text-sm text-gray-500 dark:text-dark-muted"><?= $i + 1 ?></td>
                            <td class="px-4 py-2.5">
                                <input type="hidden" name="marks[<?= $st['id'] ?>][student_id]" value="<?= $st['id'] ?>">
                                <div class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($st['first_name'] . ' ' . $st['last_name']) ?></div>
                                <div class="text-xs text-gray-500 dark:text-dark-muted"><?= e($st['admission_no']) ?></div>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <input type="number" name="marks[<?= $st['id'] ?>][score]"
                                       value="<?= $em['marks_obtained'] ?? '' ?>"
                                       min="0" max="<?= $schedule['full_marks'] ?>" step="0.5"
                                       class="w-24 px-2 py-1.5 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text text-center focus:ring-2 focus:ring-primary-500 marks-input"
                                       data-student="<?= $st['id'] ?>">
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <input type="checkbox" name="marks[<?= $st['id'] ?>][absent]" value="1"
                                       <?= ($em['is_absent'] ?? 0) ? 'checked' : '' ?>
                                       class="rounded border-gray-300 dark:border-dark-border text-red-600 focus:ring-red-500"
                                       onchange="if(this.checked) this.closest('tr').querySelector('.marks-input').value = ''">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>

        <div class="mt-4 flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                Save Marks
            </button>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
