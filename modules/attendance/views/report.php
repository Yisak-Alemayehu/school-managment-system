<?php
/**
 * Attendance — Class Report View (date-range, landscape A4 print)
 */

$classes  = db_fetch_all("SELECT id, name FROM classes ORDER BY sort_order ASC");
$sections = db_fetch_all("SELECT id, name, class_id FROM sections ORDER BY name ASC");

$filterClass   = input_int('class_id');
$filterSection = input_int('section_id');
$filterFrom    = input('date_from') ?: date('Y-m-01');   // first of current month
$filterTo      = input('date_to')   ?: date('Y-m-d');    // today

// Clamp: to must not precede from, not future
if ($filterTo < $filterFrom) $filterTo = $filterFrom;
if ($filterTo > date('Y-m-d'))  $filterTo = date('Y-m-d');

$activeSession = get_active_session();
$sessionId     = $activeSession['id'] ?? 0;

$report = [];
$dates  = [];   // array of 'Y-m-d' strings in the chosen range

if ($filterClass && $sessionId) {
    // Enumerate every date in range
    $cursor = new DateTime($filterFrom);
    $end    = new DateTime($filterTo);
    while ($cursor <= $end) {
        $dates[] = $cursor->format('Y-m-d');
        $cursor->modify('+1 day');
    }

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

    // Attendance records in range
    $studentIds = array_column($students, 'id');
    $attMap = [];
    if ($studentIds && $dates) {
        $ph   = implode(',', array_fill(0, count($studentIds), '?'));
        $rows = db_fetch_all(
            "SELECT student_id, date, status FROM attendance
             WHERE student_id IN ({$ph}) AND date BETWEEN ? AND ?",
            array_merge($studentIds, [$filterFrom, $filterTo])
        );
        foreach ($rows as $r) {
            $attMap[$r['student_id']][$r['date']] = $r['status'];
        }
    }

    // Build report rows
    foreach ($students as $st) {
        $present = $absent = $late = $excused = 0;
        $dayData = [];
        foreach ($dates as $dt) {
            $status = $attMap[$st['id']][$dt] ?? null;
            $dayData[$dt] = $status;
            if ($status === 'present') $present++;
            elseif ($status === 'absent') $absent++;
            elseif ($status === 'late') $late++;
            elseif ($status === 'excused') $excused++;
        }
        $marked = $present + $absent + $late + $excused;
        $report[] = [
            'student' => $st,
            'days'    => $dayData,
            'present' => $present,
            'absent'  => $absent,
            'late'    => $late,
            'excused' => $excused,
            'total'   => $marked,
            'pct'     => $marked > 0 ? round(($present + $late) / $marked * 100, 1) : 0,
        ];
    }
}

// Class / section names for print header
$className   = '';
$sectionName = 'All Sections';
foreach ($classes  as $c) if ($c['id'] == $filterClass)   $className   = $c['name'];
foreach ($sections as $s) if ($s['id'] == $filterSection) $sectionName = $s['name'];

ob_start();
?>

<!-- Landscape A4 print styles -->
<style>
@media print {
    @page { size: A4 landscape; margin: 10mm 8mm; }
    body, html { background: #fff !important; }
    #sidebar, #topbar, .no-print { display: none !important; }
    #main-content { margin: 0 !important; padding: 0 !important; }
    .print-wrap { padding: 0 !important; }
    #reportTable { border: none !important; border-radius: 0 !important; overflow: visible !important; }
    #reportTable table { font-size: 7.5pt; }
    .print-header { display: block !important; }
    .print-legend { display: flex !important; }
    a { color: inherit !important; text-decoration: none !important; }
    tr { page-break-inside: avoid; }
}
.print-header { display: none; }
.print-legend { display: none; }
</style>

<div class="max-w-full mx-auto print-wrap">

    <!-- Screen title + Print button -->
    <div class="flex items-center justify-between mb-6 no-print">
        <h1 class="text-xl font-bold text-gray-900">Attendance Report</h1>
        <?php if (!empty($report)): ?>
        <button onclick="window.print()"
                class="flex items-center gap-2 px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print (A4 Landscape)
        </button>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <input type="hidden" name="module" value="attendance">
            <input type="hidden" name="action" value="report">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class <span class="text-red-500">*</span></label>
                <select name="class_id" required
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filterClass == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>

                <select name="class_id" required class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Select</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filterClass == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                <select name="section_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">All Sections</option>
                    <?php foreach ($sections as $s): ?>
                        <?php if ($s['class_id'] == $filterClass): ?>
                            <option value="<?= $s['id'] ?>" <?= $filterSection == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                <input type="date" name="date_from" value="<?= e($filterFrom) ?>" max="<?= date('Y-m-d') ?>"
                       class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                <input type="date" name="date_to" value="<?= e($filterTo) ?>" max="<?= date('Y-m-d') ?>"
                       class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>

            <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition">
                Generate Report
            </button>
        </form>
    </div>

    <?php if (!$filterClass): ?>
    <!-- Initial empty state -->
    <div class="bg-white rounded-xl border border-gray-200 p-16 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-base font-medium text-gray-700 mb-1">Generate an Attendance Report</h3>
        <p class="text-sm text-gray-400">Select a class and date range above, then click <strong>Generate Report</strong>.</p>
    </div>

    <?php elseif (empty($report)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <p class="text-gray-500">No students found for the selected class.</p>
    </div>

    <?php else: ?>

    <!-- Print-only header -->
    <div class="print-header mb-4">
        <h2 class="text-base font-bold">Urjiberi School — Attendance Report</h2>
        <p class="text-xs text-gray-600 mt-0.5">
            Class: <strong><?= e($className) ?></strong>
            &nbsp;|&nbsp; Section: <strong><?= e($sectionName) ?></strong>
            &nbsp;|&nbsp; Period: <strong><?= date('d M Y', strtotime($filterFrom)) ?> – <?= date('d M Y', strtotime($filterTo)) ?></strong>
            &nbsp;|&nbsp; Printed: <?= date('d M Y, H:i') ?>
        </p>
        <hr class="my-2 border-gray-300">
    </div>

    <!-- Summary strip (screen only) -->
    <?php
    $totPresent = array_sum(array_column($report, 'present'));
    $totAbsent  = array_sum(array_column($report, 'absent'));
    $totLate    = array_sum(array_column($report, 'late'));
    $totExcused = array_sum(array_column($report, 'excused'));
    $totMarked  = $totPresent + $totAbsent + $totLate + $totExcused;
    $overallPct = $totMarked > 0 ? round(($totPresent + $totLate) / $totMarked * 100, 1) : 0;
    ?>
    <div class="grid grid-cols-2 sm:grid-cols-6 gap-3 mb-5 no-print">
        <div class="bg-white rounded-xl border p-3 text-center">
            <div class="text-lg font-bold text-gray-800"><?= count($report) ?></div>
            <div class="text-xs text-gray-500">Students</div>
        </div>
        <div class="bg-white rounded-xl border p-3 text-center">
            <div class="text-lg font-bold text-gray-800"><?= count($dates) ?></div>
            <div class="text-xs text-gray-500">Days</div>
        </div>
        <div class="bg-green-50 rounded-xl border border-green-200 p-3 text-center">
            <div class="text-lg font-bold text-green-700"><?= $totPresent ?></div>
            <div class="text-xs text-green-600">Present</div>
        </div>
        <div class="bg-red-50 rounded-xl border border-red-200 p-3 text-center">
            <div class="text-lg font-bold text-red-700"><?= $totAbsent ?></div>
            <div class="text-xs text-red-600">Absent</div>
        </div>
        <div class="bg-yellow-50 rounded-xl border border-yellow-200 p-3 text-center">
            <div class="text-lg font-bold text-yellow-700"><?= $totLate ?></div>
            <div class="text-xs text-yellow-600">Late</div>
        </div>
        <div class="bg-blue-50 rounded-xl border border-blue-200 p-3 text-center">
            <div class="text-lg font-bold <?= $overallPct >= 75 ? 'text-blue-700' : 'text-red-700' ?>"><?= $overallPct ?>%</div>
            <div class="text-xs text-blue-600">Overall</div>
        </div>
    </div>

    <!-- Report Table -->
    <div id="reportTable" class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-xs border-collapse">
            <thead>
                <!-- Month grouping header -->
                <?php
                $monthGroups = [];
                foreach ($dates as $dt) {
                    $mk = date('Y-m', strtotime($dt));
                    $monthGroups[$mk] = ($monthGroups[$mk] ?? 0) + 1;
                }
                ?>
                <tr class="bg-gray-100 border-b border-gray-300">
                    <th class="px-2 py-1.5 text-left text-gray-600 border-r border-gray-200 w-6" rowspan="2">#</th>
                    <th class="px-3 py-1.5 text-left text-gray-600 border-r border-gray-200 min-w-[130px]" rowspan="2">Student</th>
                    <?php foreach ($monthGroups as $mk => $span): ?>
                        <th colspan="<?= $span ?>" class="px-1 py-1.5 text-center text-gray-700 font-semibold border-r border-gray-300">
                            <?= date('F Y', strtotime($mk . '-01')) ?>
                        </th>
                    <?php endforeach; ?>
                    <th colspan="4" class="px-1 py-1.5 text-center text-gray-700 font-semibold bg-gray-200">Summary</th>
                </tr>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <?php foreach ($dates as $dt): ?>
                        <?php $isSun = date('N', strtotime($dt)) == 7; ?>
                        <th class="px-0.5 py-1 text-center w-7 border-r border-gray-100 <?= $isSun ? 'bg-red-50 text-red-500' : 'text-gray-500' ?>">
                            <div class="font-medium"><?= date('j', strtotime($dt)) ?></div>
                            <div class="text-gray-400 font-normal" style="font-size:9px"><?= date('D', strtotime($dt)) ?></div>
                        </th>
                    <?php endforeach; ?>
                    <th class="px-2 py-1 text-center text-green-700 bg-green-50 border-r border-gray-200 w-8">P</th>
                    <th class="px-2 py-1 text-center text-red-700 bg-red-50 border-r border-gray-200 w-8">A</th>
                    <th class="px-2 py-1 text-center text-yellow-700 bg-yellow-50 border-r border-gray-200 w-8">L</th>
                    <th class="px-2 py-1 text-center text-gray-700 w-12">%</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($report as $i => $r): ?>
                <tr class="hover:bg-gray-50 <?= $i % 2 !== 0 ? 'bg-gray-50/50' : '' ?>">
                    <td class="px-2 py-1.5 text-gray-400 border-r border-gray-100"><?= $i + 1 ?></td>
                    <td class="px-3 py-1.5 font-medium text-gray-900 border-r border-gray-100">
                        <a href="<?= url('attendance', 'student') ?>&student_id=<?= $r['student']['id'] ?>"
                           class="hover:text-primary-600">
                            <?= e($r['student']['first_name'] . ' ' . $r['student']['last_name']) ?>
                        </a>
                        <div class="text-gray-400 font-normal" style="font-size:9px"><?= e($r['student']['admission_no']) ?></div>
                    </td>
                    <?php foreach ($dates as $dt): ?>
                        <?php
                        $st    = $r['days'][$dt] ?? null;
                        $isSun = date('N', strtotime($dt)) == 7;
                        $cell  = match($st) {
                            'present' => '<span class="text-green-600 font-bold">P</span>',
                            'absent'  => '<span class="text-red-600 font-bold">A</span>',
                            'late'    => '<span class="text-yellow-600 font-bold">L</span>',
                            'excused' => '<span class="text-blue-600 font-bold">E</span>',
                            default   => '<span class="text-gray-300">·</span>',
                        };
                        ?>
                        <td class="px-0.5 py-1.5 text-center border-r border-gray-100 <?= $isSun ? 'bg-red-50/50' : '' ?>"><?= $cell ?></td>
                    <?php endforeach; ?>
                    <td class="px-2 py-1.5 text-center font-bold text-green-700 bg-green-50 border-r border-gray-100"><?= $r['present'] ?></td>
                    <td class="px-2 py-1.5 text-center font-bold text-red-700 bg-red-50 border-r border-gray-100"><?= $r['absent'] ?></td>
                    <td class="px-2 py-1.5 text-center font-bold text-yellow-700 bg-yellow-50 border-r border-gray-100"><?= $r['late'] ?></td>
                    <td class="px-2 py-1.5 text-center font-bold <?= $r['pct'] >= 75 ? 'text-green-700' : 'text-red-700' ?>">
                        <?= $r['pct'] ?>%
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Legend -->
    <div class="print-legend flex flex-wrap gap-5 mt-3 text-xs text-gray-600">
        <span><strong class="text-green-700">P</strong> = Present</span>
        <span><strong class="text-red-700">A</strong> = Absent</span>
        <span><strong class="text-yellow-700">L</strong> = Late</span>
        <span><strong class="text-blue-700">E</strong> = Excused</span>
        <span>· = Not marked</span>
    </div>
    <div class="flex flex-wrap gap-4 mt-4 text-xs text-gray-500 no-print">
        <span><span class="inline-block w-3 h-3 rounded mr-1 bg-green-500"></span>P = Present</span>
        <span><span class="inline-block w-3 h-3 rounded mr-1 bg-red-500"></span>A = Absent</span>
        <span><span class="inline-block w-3 h-3 rounded mr-1 bg-yellow-500"></span>L = Late</span>
        <span><span class="inline-block w-3 h-3 rounded mr-1 bg-blue-500"></span>E = Excused</span>
        <span>· = Not marked</span>
    </div>

    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
