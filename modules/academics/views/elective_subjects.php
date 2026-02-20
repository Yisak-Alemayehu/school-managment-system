<?php
/**
 * Academics — Assign Elective Subjects to Students
 * Shows a matrix: rows = students, columns = elective subjects for the selected class.
 */

$activeSession = get_active_session();
$sessionId     = $activeSession['id'] ?? 0;
$classes       = db_fetch_all("SELECT id, name FROM classes ORDER BY sort_order ASC");
$filterClass   = input_int('class_id');
$filterSection = input_int('section_id');

$sectionsForClass = [];
if ($filterClass) {
    $sectionsForClass = db_fetch_all("SELECT id, name FROM sections WHERE class_id = ? ORDER BY name ASC", [$filterClass]);
}

// Elective subjects for this class in this session
$electiveSubjects = [];
if ($filterClass && $sessionId) {
    $electiveSubjects = db_fetch_all("
        SELECT cs.id AS class_subject_id, s.name AS subject_name, s.code AS subject_code
        FROM class_subjects cs
        JOIN subjects s ON s.id = cs.subject_id
        WHERE cs.class_id = ? AND cs.is_elective = 1
        ORDER BY s.name ASC
    ", [$filterClass]);
}

// Students enrolled in this class/section
$students = [];
if ($filterClass && $sessionId) {
    $sectionWhere = $filterSection ? "AND e.section_id = {$filterSection}" : '';
    $students = db_fetch_all("
        SELECT st.id, st.first_name, st.last_name, st.admission_no, e.section_id
        FROM students st
        JOIN enrollments e ON e.student_id = st.id
        WHERE e.session_id = ? AND e.class_id = ? AND e.status = 'active' {$sectionWhere}
        ORDER BY st.first_name, st.last_name
    ", [$sessionId, $filterClass]);
}

// Current assignments
$currentAssignments = [];
if (!empty($students) && !empty($electiveSubjects) && $sessionId) {
    $studentIds = array_column($students, 'id');
    $csIds      = array_column($electiveSubjects, 'class_subject_id');
    if ($studentIds && $csIds) {
        $inStudents = implode(',', $studentIds);
        $inCS       = implode(',', $csIds);
        $rows = db_fetch_all("
            SELECT student_id, class_subject_id
            FROM student_elective_subjects
            WHERE student_id IN ({$inStudents}) AND class_subject_id IN ({$inCS}) AND session_id = ?
        ", [$sessionId]);
        foreach ($rows as $r) {
            $currentAssignments[$r['student_id']][$r['class_subject_id']] = true;
        }
    }
}

ob_start();
?>

<div class="max-w-7xl mx-auto">

    <h1 class="text-xl font-bold text-gray-900 mb-6">Assign Elective Subjects to Students</h1>

    <?php if (!$sessionId): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
            No active session. Please activate an academic session first.
        </div>
    <?php else: ?>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <form method="GET" action="" class="flex flex-wrap items-end gap-4">
            <input type="hidden" name="route" value="academics/elective-subjects">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select name="class_id" onchange="this.form.submit()" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filterClass == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($filterClass && !empty($sectionsForClass)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                <select name="section_id" onchange="this.form.submit()" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All Sections</option>
                    <?php foreach ($sectionsForClass as $sec): ?>
                        <option value="<?= $sec['id'] ?>" <?= $filterSection == $sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($filterClass && empty($electiveSubjects)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
            No elective subjects assigned to this class. Go to
            <a href="<?= url('academics', 'class-subjects') ?>&class_id=<?= $filterClass ?>" class="underline font-medium">Class Subjects</a>
            and mark some subjects as elective.
        </div>
    <?php elseif ($filterClass && empty($students)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
            No students enrolled in this class for the active session.
        </div>
    <?php elseif ($filterClass && !empty($electiveSubjects) && !empty($students)): ?>

    <!-- Assignment Matrix -->
    <form method="POST" action="<?= url('academics', 'elective-subjects-save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="class_id" value="<?= $filterClass ?>">
        <input type="hidden" name="section_id" value="<?= $filterSection ?>">
        <input type="hidden" name="session_id" value="<?= $sessionId ?>">
        <?php foreach ($students as $st): ?>
            <input type="hidden" name="student_ids[]" value="<?= $st['id'] ?>">
        <?php endforeach; ?>

        <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase sticky left-0 bg-gray-50">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase sticky left-0 bg-gray-50">Adm No</th>
                        <?php foreach ($electiveSubjects as $es): ?>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                                <?= e($es['subject_name']) ?><br>
                                <span class="text-gray-400 font-normal">(<?= e($es['subject_code']) ?>)</span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($students as $st): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm font-medium text-gray-900 sticky left-0 bg-white">
                                <?= e($st['first_name'] . ' ' . $st['last_name']) ?>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500"><?= e($st['admission_no']) ?></td>
                            <?php foreach ($electiveSubjects as $es): ?>
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox"
                                           name="electives[<?= $st['id'] ?>][]"
                                           value="<?= $es['class_subject_id'] ?>"
                                           <?= isset($currentAssignments[$st['id']][$es['class_subject_id']]) ? 'checked' : '' ?>
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                Save Elective Assignments
            </button>
        </div>
    </form>

    <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
