<?php
/**
 * Exams — Report Cards View
 */

$classes  = db_fetch_all("SELECT id, name FROM classes ORDER BY sort_order ASC");
$sections = db_fetch_all("SELECT id, name, class_id FROM sections ORDER BY name ASC");
$exams    = db_fetch_all("SELECT id, name FROM exams WHERE session_id = ? ORDER BY start_date DESC", [get_active_session()['id'] ?? 0]);

$filterClass   = input_int('class_id');
$filterSection = input_int('section_id');
$filterExam    = input_int('exam_id');

$activeSession = get_active_session();
$sessionId = $activeSession['id'] ?? 0;

$students = [];
$reportCards = [];

if ($filterClass && $filterExam && $sessionId) {
    $where  = "WHERE e.class_id = ? AND e.session_id = ? AND e.status = 'active'";
    $params = [$filterClass, $sessionId];
    if ($filterSection) {
        $where .= " AND e.section_id = ?";
        $params[] = $filterSection;
    }

    $students = db_fetch_all("
        SELECT s.id, s.first_name, s.last_name, s.admission_no
        FROM students s
        JOIN enrollments e ON e.student_id = s.id
        {$where}
        ORDER BY s.first_name, s.last_name
    ", $params);

    // Check for generated report cards
    $stIds = array_column($students, 'id');
    if ($stIds) {
        $ph = implode(',', array_fill(0, count($stIds), '?'));
        $rows = db_fetch_all(
            "SELECT student_id, id, total_marks, average, rank, grade FROM report_cards WHERE exam_id = ? AND student_id IN ({$ph})",
            array_merge([$filterExam], $stIds)
        );
        foreach ($rows as $r) {
            $reportCards[$r['student_id']] = $r;
        }
    }
}

ob_start();
?>

<div class="max-w-5xl mx-auto">
    <h1 class="text-xl font-bold text-gray-900 mb-6">Report Cards</h1>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <form method="GET" class="grid grid-cols-2 sm:grid-cols-5 gap-4 items-end">
            <input type="hidden" name="module" value="exams">
            <input type="hidden" name="action" value="report-cards">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Exam</label>
                <select name="exam_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Select Exam</option>
                    <?php foreach ($exams as $ex): ?>
                        <option value="<?= $ex['id'] ?>" <?= $filterExam == $ex['id'] ? 'selected' : '' ?>><?= e($ex['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select name="class_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Select</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filterClass == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                <select name="section_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All</option>
                    <?php foreach ($sections as $s): ?>
                        <?php if ($s['class_id'] == $filterClass): ?>
                            <option value="<?= $s['id'] ?>" <?= $filterSection == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition">View</button>
            </div>
            <?php if ($filterClass && $filterExam && auth_has_permission('report_card.manage')): ?>
            <div>
                <form method="POST" action="<?= url('exams', 'report-card-generate') ?>" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="exam_id" value="<?= $filterExam ?>">
                    <input type="hidden" name="class_id" value="<?= $filterClass ?>">
                    <input type="hidden" name="section_id" value="<?= $filterSection ?>">
                    <button type="submit" onclick="return confirm('Generate/Regenerate report cards for all students?')"
                            class="px-4 py-2 bg-green-700 text-white rounded-lg text-sm font-medium hover:bg-green-800 transition">
                        Generate All
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($filterClass && $filterExam && !empty($students)): ?>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Average</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Grade</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Rank</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($students as $i => $st): ?>
                    <?php $rc = $reportCards[$st['id']] ?? null; ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500"><?= $i + 1 ?></td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= e($st['first_name'] . ' ' . $st['last_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-center text-gray-600"><?= $rc ? number_format($rc['total_marks'], 1) : '—' ?></td>
                        <td class="px-4 py-3 text-sm text-center text-gray-600"><?= $rc ? number_format($rc['average'], 1) . '%' : '—' ?></td>
                        <td class="px-4 py-3 text-sm text-center font-bold"><?= e($rc['grade'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm text-center text-gray-600"><?= $rc['rank'] ?? '—' ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($rc): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Generated</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <?php if ($rc): ?>
                                <a href="<?= url('exams', 'report-card-print') ?>&id=<?= $rc['id'] ?>" target="_blank"
                                   class="px-2 py-1 bg-primary-100 text-primary-700 rounded text-xs font-medium hover:bg-primary-200">
                                    Print
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
