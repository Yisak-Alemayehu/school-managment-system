<?php
/**
 * Attendance — View Attendance (read-only daily records)
 */

$classes  = db_fetch_all("SELECT id, name FROM classes ORDER BY sort_order ASC");
$sections = db_fetch_all("SELECT id, name, class_id FROM sections ORDER BY name ASC");

$filterClass   = input_int('class_id');
$filterSection = input_int('section_id');
$filterDate    = input('date') ?: date('Y-m-d');

$activeSession = get_active_session();
$sessionId     = $activeSession['id'] ?? 0;

$records  = [];
$summary  = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total' => 0];
$loaded   = false;

if ($filterClass && $sessionId) {
    $loaded = true;
    $where  = "e.class_id = ? AND e.session_id = ? AND e.status = 'active'";
    $params = [$filterClass, $sessionId];
    if ($filterSection) {
        $where .= " AND e.section_id = ?";
        $params[] = $filterSection;
    }

    $students = db_fetch_all("
        SELECT s.id, s.first_name, s.last_name, s.admission_no, s.photo,
               sec.name AS section_name
        FROM students s
        JOIN enrollments e ON e.student_id = s.id
        LEFT JOIN sections sec ON sec.id = e.section_id
        WHERE {$where}
        ORDER BY s.first_name, s.last_name
    ", $params);

    $studentIds = array_column($students, 'id');
    $attMap = [];
    if ($studentIds) {
        $ph   = implode(',', array_fill(0, count($studentIds), '?'));
        $rows = db_fetch_all(
            "SELECT student_id, status, remarks FROM attendance
             WHERE date = ? AND class_id = ? AND student_id IN ({$ph})",
            array_merge([$filterDate, $filterClass], $studentIds)
        );
        foreach ($rows as $r) {
            $attMap[$r['student_id']] = $r;
        }
    }

    foreach ($students as $st) {
        $att = $attMap[$st['id']] ?? ['status' => null, 'remarks' => ''];
        $records[] = array_merge($st, ['att_status' => $att['status'], 'remarks' => $att['remarks']]);
        if ($att['status']) {
            $summary[$att['status']] = ($summary[$att['status']] ?? 0) + 1;
            $summary['total']++;
        }
    }

    // If no attendance marked yet, total = 0 but we still show students
    if ($summary['total'] === 0 && count($students) > 0) {
        $summary['total'] = 0; // attendance not marked
    }
}

$pct = $summary['total'] > 0
    ? round(($summary['present'] + $summary['late']) / $summary['total'] * 100, 1)
    : 0;

$statusConfig = [
    'present' => ['label' => 'Present',  'bg' => 'bg-green-100',  'text' => 'text-green-800',  'dot' => 'bg-green-500'],
    'absent'  => ['label' => 'Absent',   'bg' => 'bg-red-100',    'text' => 'text-red-800',    'dot' => 'bg-red-500'],
    'late'    => ['label' => 'Late',     'bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'dot' => 'bg-yellow-500'],
    'excused' => ['label' => 'Excused',  'bg' => 'bg-blue-100',   'text' => 'text-blue-800',   'dot' => 'bg-blue-500'],
];

ob_start();
?>

<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-gray-900">View Attendance</h1>
        <?php if ($loaded && !empty($records)): ?>
        <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print
        </button>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <form method="GET" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <input type="hidden" name="module" value="attendance">
            <input type="hidden" name="action" value="view">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class <span class="text-red-500">*</span></label>
                <select name="class_id" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filterClass == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                <select name="section_id" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="">All Sections</option>
                    <?php foreach ($sections as $s): ?>
                        <?php if ($s['class_id'] == $filterClass): ?>
                            <option value="<?= $s['id'] ?>" <?= $filterSection == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date" value="<?= e($filterDate) ?>" max="<?= date('Y-m-d') ?>"
                       onchange="this.form.submit()"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition">
                    Load
                </button>
            </div>
        </form>
    </div>

    <?php if (!$loaded): ?>
    <!-- Empty state -->
    <div class="bg-white rounded-xl border border-gray-200 p-16 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <h3 class="text-base font-medium text-gray-700 mb-1">Select a class to view attendance</h3>
        <p class="text-sm text-gray-400">Choose a class and date above to see the attendance record.</p>
    </div>

    <?php elseif (empty($records)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <p class="text-gray-500">No students found for the selected class.</p>
    </div>

    <?php else: ?>
    <!-- Summary Cards -->
    <?php if ($summary['total'] > 0): ?>
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6 no-print">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-gray-900"><?= count($records) ?></div>
            <div class="text-xs text-gray-500 mt-1">Total Students</div>
        </div>
        <div class="bg-green-50 rounded-xl border border-green-200 p-4 text-center">
            <div class="text-2xl font-bold text-green-700"><?= $summary['present'] ?></div>
            <div class="text-xs text-green-600 mt-1">Present</div>
        </div>
        <div class="bg-red-50 rounded-xl border border-red-200 p-4 text-center">
            <div class="text-2xl font-bold text-red-700"><?= $summary['absent'] ?></div>
            <div class="text-xs text-red-600 mt-1">Absent</div>
        </div>
        <div class="bg-yellow-50 rounded-xl border border-yellow-200 p-4 text-center">
            <div class="text-2xl font-bold text-yellow-700"><?= $summary['late'] ?></div>
            <div class="text-xs text-yellow-600 mt-1">Late</div>
        </div>
        <div class="bg-blue-50 rounded-xl border border-blue-200 p-4 text-center">
            <div class="text-2xl font-bold <?= $pct >= 75 ? 'text-blue-700' : 'text-red-700' ?>"><?= $pct ?>%</div>
            <div class="text-xs text-blue-600 mt-1">Attendance %</div>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6 flex items-center gap-3">
        <svg class="w-5 h-5 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm text-yellow-700">Attendance has <strong>not been marked</strong> for this class on
            <strong><?= date('D, d M Y', strtotime($filterDate)) ?></strong>.
            <a href="<?= url('attendance', 'index') ?>&class_id=<?= $filterClass ?>&section_id=<?= $filterSection ?>&date=<?= $filterDate ?>"
               class="underline font-medium ml-1">Mark attendance now →</a>
        </p>
    </div>
    <?php endif; ?>

    <!-- Print Header -->
    <div class="hidden print:block mb-4">
        <h2 class="text-lg font-bold">Attendance Record — <?= date('D, d M Y', strtotime($filterDate)) ?></h2>
        <p class="text-sm text-gray-600">
            Class: <?= e(current(array_filter($classes, fn($c) => $c['id'] == $filterClass))['name'] ?? '') ?>
            <?php if ($filterSection): ?>
                — Section: <?= e(current(array_filter($sections, fn($s) => $s['id'] == $filterSection))['name'] ?? '') ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" id="attendanceTable">
        <!-- Table Header Bar -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-700">
                    <?= date('D, d M Y', strtotime($filterDate)) ?>
                </span>
                <?php if ($summary['total'] > 0): ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Marked</span>
                <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Not Marked</span>
                <?php endif; ?>
            </div>
            <?php if ($summary['total'] > 0): ?>
            <a href="<?= url('attendance', 'index') ?>&class_id=<?= $filterClass ?>&section_id=<?= $filterSection ?>&date=<?= $filterDate ?>"
               class="text-xs font-medium text-primary-700 hover:underline no-print">
                Edit Attendance →
            </a>
            <?php endif; ?>
        </div>

        <!-- Search bar (hidden on print) -->
        <div class="px-4 py-2 border-b border-gray-100 no-print">
            <input type="text" id="searchInput" placeholder="Search student..." onkeyup="filterTable()"
                   class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-400">
        </div>

        <table class="w-full" id="viewTable">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide w-10">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Student</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Adm No</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Section</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Remarks</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" id="viewTableBody">
                <?php foreach ($records as $i => $r): ?>
                <?php
                    $st = $r['att_status'];
                    $cfg = $st ? ($statusConfig[$st] ?? null) : null;
                ?>
                <tr class="hover:bg-gray-50 transition view-row">
                    <td class="px-4 py-3 text-sm text-gray-500"><?= $i + 1 ?></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <?php if (!empty($r['photo'])): ?>
                                <img src="/uploads/students/<?= e($r['photo']) ?>" class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-primary-700"><?= strtoupper(substr($r['first_name'], 0, 1)) ?></span>
                                </div>
                            <?php endif; ?>
                            <span class="text-sm font-medium text-gray-900 student-name">
                                <?= e($r['first_name'] . ' ' . $r['last_name']) ?>
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500"><?= e($r['admission_no']) ?></td>
                    <td class="px-4 py-3 text-sm text-gray-500"><?= e($r['section_name'] ?? '—') ?></td>
                    <td class="px-4 py-3 text-center">
                        <?php if ($cfg): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?= $cfg['bg'] ?> <?= $cfg['text'] ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $cfg['dot'] ?>"></span>
                                <?= $cfg['label'] ?>
                            </span>
                        <?php else: ?>
                            <span class="text-xs text-gray-400 italic">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500"><?= e($r['remarks'] ?: '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Stats Bar -->
        <?php if ($summary['total'] > 0): ?>
        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex flex-wrap gap-4 text-xs text-gray-600">
            <span>Total: <strong><?= count($records) ?></strong></span>
            <span class="text-green-700">Present: <strong><?= $summary['present'] ?></strong></span>
            <span class="text-red-700">Absent: <strong><?= $summary['absent'] ?></strong></span>
            <span class="text-yellow-700">Late: <strong><?= $summary['late'] ?></strong></span>
            <span class="text-blue-700">Excused: <strong><?= $summary['excused'] ?></strong></span>
            <span class="ml-auto font-medium <?= $pct >= 75 ? 'text-green-700' : 'text-red-700' ?>">
                Attendance: <?= $pct ?>%
            </span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick filter chips (no-print) -->
    <?php if ($summary['total'] > 0): ?>
    <div class="flex flex-wrap gap-2 mt-4 no-print" id="filterChips">
        <button onclick="filterByStatus('')"    class="chip-btn active px-3 py-1 rounded-full text-xs border transition chip-all">All (<?= count($records) ?>)</button>
        <button onclick="filterByStatus('present')"  class="chip-btn px-3 py-1 rounded-full text-xs border border-green-200 bg-green-50 text-green-800 hover:bg-green-100 transition">Present (<?= $summary['present'] ?>)</button>
        <button onclick="filterByStatus('absent')"   class="chip-btn px-3 py-1 rounded-full text-xs border border-red-200 bg-red-50 text-red-800 hover:bg-red-100 transition">Absent (<?= $summary['absent'] ?>)</button>
        <button onclick="filterByStatus('late')"     class="chip-btn px-3 py-1 rounded-full text-xs border border-yellow-200 bg-yellow-50 text-yellow-800 hover:bg-yellow-100 transition">Late (<?= $summary['late'] ?>)</button>
        <button onclick="filterByStatus('excused')"  class="chip-btn px-3 py-1 rounded-full text-xs border border-blue-200 bg-blue-50 text-blue-800 hover:bg-blue-100 transition">Excused (<?= $summary['excused'] ?>)</button>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.view-row').forEach(row => {
        const name = row.querySelector('.student-name')?.textContent.toLowerCase() ?? '';
        row.style.display = name.includes(q) ? '' : 'none';
    });
}

let currentStatusFilter = '';
function filterByStatus(status) {
    currentStatusFilter = status;
    document.querySelectorAll('.view-row').forEach(row => {
        if (!status) {
            row.style.display = '';
        } else {
            const badge = row.querySelector('td:nth-child(5) span.inline-flex');
            const label = badge ? badge.textContent.trim().toLowerCase() : '';
            row.style.display = label.startsWith(status) ? '' : 'none';
        }
    });
    // update chip active state
    document.querySelectorAll('.chip-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-gray-800', 'text-white', 'border-gray-800');
        btn.classList.add('border-gray-200', 'bg-gray-50', 'text-gray-700');
    });
    const activeChip = status
        ? document.querySelector(`button[onclick="filterByStatus('${status}')"]`)
        : document.querySelector('.chip-all');
    if (activeChip) {
        activeChip.classList.add('bg-gray-800', 'text-white', 'border-gray-800');
        activeChip.classList.remove('bg-gray-50', 'text-gray-700', 'border-gray-200',
            'bg-green-50','text-green-800','border-green-200',
            'bg-red-50','text-red-800','border-red-200',
            'bg-yellow-50','text-yellow-800','border-yellow-200',
            'bg-blue-50','text-blue-800','border-blue-200');
    }
}
// Init: activate "All" chip
document.addEventListener('DOMContentLoaded', () => filterByStatus(''));
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
