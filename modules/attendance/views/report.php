<?php
/**
 * Attendance — Class Report View
 */

$classes  = db_fetch_all("SELECT id, name FROM classes ORDER BY sort_order ASC");
$sections = db_fetch_all("SELECT id, name, class_id FROM sections ORDER BY name ASC");

$filterClass   = input_int('class_id');
$filterSection = input_int('section_id');
$filterMonth   = input('month') ?: date('Y-m');

$activeSession = get_active_session();
$sessionId     = $activeSession['id'] ?? 0;

$report  = [];
$days    = [];

if ($filterClass && $sessionId) {
    // Get days in selected month
    $startDate = $filterMonth . '-01';
    $endDate   = date('Y-m-t', strtotime($startDate));
    $totalDays = (int)date('t', strtotime($startDate));

    // Students
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

    // Attendance records for the month
    $studentIds = array_column($students, 'id');
    $records = [];
    if ($studentIds) {
        $ph = implode(',', array_fill(0, count($studentIds), '?'));
        $rows = db_fetch_all(
            "SELECT student_id, date, status FROM attendance WHERE student_id IN ({$ph}) AND date BETWEEN ? AND ?",
            array_merge($studentIds, [$startDate, $endDate])
        );
        foreach ($rows as $r) {
            $day = (int)date('j', strtotime($r['date']));
            $records[$r['student_id']][$day] = $r['status'];
        }
    }

    // Build report
    for ($d = 1; $d <= $totalDays; $d++) $days[] = $d;

    foreach ($students as $st) {
        $present = $absent = $late = $excused = 0;
        $dayData = [];
        foreach ($days as $d) {
            $status = $records[$st['id']][$d] ?? null;
            $dayData[$d] = $status;
            if ($status === 'present') $present++;
            elseif ($status === 'absent') $absent++;
            elseif ($status === 'late') $late++;
            elseif ($status === 'excused') $excused++;
        }
        $report[] = [
            'student'  => $st,
            'days'     => $dayData,
            'present'  => $present,
            'absent'   => $absent,
            'late'     => $late,
            'excused'  => $excused,
            'total'    => $present + $absent + $late + $excused,
            'pct'      => ($present + $absent + $late + $excused) > 0
                ? round(($present + $late) / ($present + $absent + $late + $excused) * 100, 1) : 0,
        ];
    }
}

ob_start();
?>

<div class="max-w-full mx-auto">
    <h1 class="text-xl font-bold text-gray-900 mb-6">Attendance Report</h1>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <input type="hidden" name="module" value="attendance">
            <input type="hidden" name="action" value="report">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select name="class_id" required class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Select</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filterClass == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                <select name="section_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All</option>
                    <?php foreach ($sections as $s): ?>
                        <?php if ($s['class_id'] == $filterClass): ?>
                            <option value="<?= $s['id'] ?>" <?= $filterSection == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                <input type="month" name="month" value="<?= e($filterMonth) ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition">View</button>
            <?php if (!empty($report)): ?>
                <button type="button" onclick="printContent('reportTable')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
                    Print
                </button>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($report)): ?>
    <div id="reportTable" class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-2 py-2 text-left font-medium text-gray-500 sticky left-0 bg-gray-50 z-10">#</th>
                    <th class="px-2 py-2 text-left font-medium text-gray-500 sticky left-6 bg-gray-50 z-10 min-w-[140px]">Student</th>
                    <?php foreach ($days as $d): ?>
                        <th class="px-1 py-2 text-center font-medium text-gray-500 w-7"><?= $d ?></th>
                    <?php endforeach; ?>
                    <th class="px-2 py-2 text-center font-medium text-gray-500 bg-green-50">P</th>
                    <th class="px-2 py-2 text-center font-medium text-gray-500 bg-red-50">A</th>
                    <th class="px-2 py-2 text-center font-medium text-gray-500 bg-yellow-50">L</th>
                    <th class="px-2 py-2 text-center font-medium text-gray-500">%</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($report as $i => $r): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-2 py-1.5 text-gray-400 sticky left-0 bg-white"><?= $i + 1 ?></td>
                        <td class="px-2 py-1.5 font-medium text-gray-900 sticky left-6 bg-white">
                            <a href="<?= url('attendance', 'student') ?>&student_id=<?= $r['student']['id'] ?>" class="hover:text-primary-600">
                                <?= e($r['student']['first_name'] . ' ' . $r['student']['last_name']) ?>
                            </a>
                        </td>
                        <?php foreach ($days as $d): ?>
                            <?php
                            $st = $r['days'][$d] ?? null;
                            $badge = match($st) {
                                'present' => '<span class="text-green-600 font-bold">P</span>',
                                'absent'  => '<span class="text-red-600 font-bold">A</span>',
                                'late'    => '<span class="text-yellow-600 font-bold">L</span>',
                                'excused' => '<span class="text-blue-600 font-bold">E</span>',
                                default   => '<span class="text-gray-300">-</span>',
                            };
                            ?>
                            <td class="px-1 py-1.5 text-center"><?= $badge ?></td>
                        <?php endforeach; ?>
                        <td class="px-2 py-1.5 text-center font-bold text-green-700 bg-green-50"><?= $r['present'] ?></td>
                        <td class="px-2 py-1.5 text-center font-bold text-red-700 bg-red-50"><?= $r['absent'] ?></td>
                        <td class="px-2 py-1.5 text-center font-bold text-yellow-700 bg-yellow-50"><?= $r['late'] ?></td>
                        <td class="px-2 py-1.5 text-center font-bold <?= $r['pct'] >= 75 ? 'text-green-700' : 'text-red-700' ?>"><?= $r['pct'] ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap gap-4 mt-4 text-xs text-gray-500">
        <span><span class="inline-block w-3 h-3 bg-green-500 rounded mr-1"></span>Present</span>
        <span><span class="inline-block w-3 h-3 bg-red-500 rounded mr-1"></span>Absent</span>
        <span><span class="inline-block w-3 h-3 bg-yellow-500 rounded mr-1"></span>Late</span>
        <span><span class="inline-block w-3 h-3 bg-blue-500 rounded mr-1"></span>Excused</span>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
