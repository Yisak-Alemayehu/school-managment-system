<?php
/**
 * Portal — Attendance View (student & parent)
 */

$role = portal_role();
if ($role === 'student') {
    $studentId = portal_linked_id();
} else {
    $child = portal_active_child();
    if (!$child) {
        portal_head('Attendance', portal_url('dashboard'));
        echo '<div class="card text-center py-12 text-gray-400"><p class="text-4xl mb-3">📅</p>';
        echo '<p class="text-sm">No child selected. Go back to the dashboard.</p></div>';
        portal_foot('attendance');
        return;
    }
    $studentId = (int) $child['id'];
}

// Month navigation
$currentMonth = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $currentMonth)) {
    $currentMonth = date('Y-m');
}
$startDate  = $currentMonth . '-01';
$endDate    = date('Y-m-t', strtotime($startDate));
$prevMonth  = date('Y-m', strtotime($startDate . ' -1 month'));
$nextMonth  = date('Y-m', strtotime($startDate . ' +1 month'));
$isThisMonth = $currentMonth === date('Y-m');

// Attendance records for this month
$records = db_fetch_all(
    "SELECT a.date, a.status, a.remarks,
            COALESCE(s.name, 'General') AS subject_name
     FROM attendance a
     LEFT JOIN subjects s ON s.id = a.subject_id
     WHERE a.student_id = ? AND a.date BETWEEN ? AND ?
       AND a.subject_id IS NULL
     ORDER BY a.date ASC",
    [$studentId, $startDate, $endDate]
);

// Session-wide summary
$enrollment = db_fetch_one(
    "SELECT e.session_id FROM enrollments e
     JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
     WHERE e.student_id = ? AND e.status = 'active' LIMIT 1",
    [$studentId]
);

$summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total' => 0, 'percentage' => 0];
if ($enrollment) {
    $rows = db_fetch_all(
        "SELECT status, COUNT(*) AS cnt FROM attendance
         WHERE student_id = ? AND session_id = ? AND subject_id IS NULL
         GROUP BY status",
        [$studentId, $enrollment['session_id']]
    );
    foreach ($rows as $r) {
        $summary[$r['status']] = (int) $r['cnt'];
    }
    $summary['total'] = $summary['present'] + $summary['absent'] + $summary['late'] + $summary['excused'];
    $summary['percentage'] = $summary['total']
        ? round(($summary['present'] + $summary['late']) / $summary['total'] * 100, 1) : 0;
}

// Index by date for quick lookup
$byDate = [];
foreach ($records as $r) {
    $byDate[$r['date']] = $r;
}

// Build calendar days
$daysInMonth = (int) date('t', strtotime($startDate));
$firstDow    = (int) date('N', strtotime($startDate)); // 1=Mon, 7=Sun

portal_head('Attendance', portal_url('dashboard'));
?>

<!-- Month navigator -->
<div class="flex items-center justify-between mb-5">
  <a href="<?= portal_url('attendance', ['month' => $prevMonth]) ?>"
     class="btn-secondary px-3 py-2 text-sm">← Prev</a>
  <h3 class="font-bold text-gray-900"><?= date('F Y', strtotime($startDate)) ?></h3>
  <a href="<?= portal_url('attendance', ['month' => $nextMonth]) ?>"
     class="btn-secondary px-3 py-2 text-sm <?= $isThisMonth ? 'opacity-40 pointer-events-none' : '' ?>">
    Next →
  </a>
</div>

<!-- Session-wide summary -->
<div class="mb-5">
  <p class="section-title">This Session</p>
  <div class="card">
    <div class="flex items-center justify-between mb-3">
      <div>
        <span class="text-3xl font-bold text-gray-900"><?= $summary['percentage'] ?>%</span>
        <span class="text-sm text-gray-500 ml-2">attendance rate</span>
      </div>
      <span class="badge <?= $summary['percentage'] >= 75 ? 'badge-green' : ($summary['percentage'] >= 60 ? 'badge-yellow' : 'badge-red') ?>">
        <?= $summary['percentage'] >= 75 ? 'Good' : ($summary['percentage'] >= 60 ? 'Fair' : 'Low') ?>
      </span>
    </div>
    <div class="progress-bar mb-3">
      <div class="progress-fill <?= $summary['percentage'] >= 75 ? 'bg-green-500' : ($summary['percentage'] >= 60 ? 'bg-yellow-500' : 'bg-red-500') ?>"
           style="width:<?= min(100, $summary['percentage']) ?>%"></div>
    </div>
    <div class="grid grid-cols-4 gap-2 text-center text-xs">
      <div class="bg-green-50 rounded-lg p-2">
        <div class="font-bold text-green-700 text-base"><?= $summary['present'] ?></div>
        <div class="text-green-600">Present</div>
      </div>
      <div class="bg-red-50 rounded-lg p-2">
        <div class="font-bold text-red-700 text-base"><?= $summary['absent'] ?></div>
        <div class="text-red-600">Absent</div>
      </div>
      <div class="bg-yellow-50 rounded-lg p-2">
        <div class="font-bold text-yellow-700 text-base"><?= $summary['late'] ?></div>
        <div class="text-yellow-600">Late</div>
      </div>
      <div class="bg-blue-50 rounded-lg p-2">
        <div class="font-bold text-blue-700 text-base"><?= $summary['excused'] ?></div>
        <div class="text-blue-600">Excused</div>
      </div>
    </div>
  </div>
</div>

<!-- Calendar -->
<div class="mb-5">
  <p class="section-title">This Month</p>
  <div class="card p-3">
    <!-- Day headers -->
    <div class="grid grid-cols-7 mb-1">
      <?php foreach (['Mo','Tu','We','Th','Fr','Sa','Su'] as $d): ?>
      <div class="text-center text-xs font-semibold text-gray-400"><?= $d ?></div>
      <?php endforeach; ?>
    </div>
    <!-- Blank cells before first day -->
    <div class="grid grid-cols-7 gap-1">
      <?php for ($i = 1; $i < $firstDow; $i++): ?>
      <div></div>
      <?php endfor; ?>

      <?php for ($day = 1; $day <= $daysInMonth; $day++):
        $dateStr = $currentMonth . '-' . sprintf('%02d', $day);
        $rec     = $byDate[$dateStr] ?? null;
        $status  = $rec['status'] ?? null;
        $dow     = (int) date('N', strtotime($dateStr)); // 1=Mon
        $isWeekend = $dow >= 6;
        $isFuture  = $dateStr > date('Y-m-d');

        if ($status === 'present') {
            $bg = 'bg-green-500 text-white';
        } elseif ($status === 'absent') {
            $bg = 'bg-red-500 text-white';
        } elseif ($status === 'late') {
            $bg = 'bg-yellow-400 text-white';
        } elseif ($status === 'excused') {
            $bg = 'bg-blue-400 text-white';
        } elseif ($isWeekend) {
            $bg = 'bg-gray-100 text-gray-300';
        } elseif ($isFuture) {
            $bg = 'text-gray-300';
        } else {
            $bg = 'text-gray-500';
        }
      ?>
      <div class="aspect-square flex items-center justify-center text-xs font-semibold rounded-lg <?= $bg ?>"
           title="<?= $status ? ucfirst($status) : ($isWeekend ? 'Weekend' : '') ?>">
        <?= $day ?>
      </div>
      <?php endfor; ?>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap gap-3 mt-3 text-xs">
      <span class="flex items-center gap-1">
        <span class="w-3 h-3 rounded bg-green-500 inline-block"></span> Present
      </span>
      <span class="flex items-center gap-1">
        <span class="w-3 h-3 rounded bg-red-500 inline-block"></span> Absent
      </span>
      <span class="flex items-center gap-1">
        <span class="w-3 h-3 rounded bg-yellow-400 inline-block"></span> Late
      </span>
      <span class="flex items-center gap-1">
        <span class="w-3 h-3 rounded bg-blue-400 inline-block"></span> Excused
      </span>
    </div>
  </div>
</div>

<!-- Daily records list -->
<?php if (!empty($records)): ?>
<div class="mb-5">
  <p class="section-title">Daily Records</p>
  <div class="card p-0 overflow-hidden divide-y divide-gray-50">
    <?php foreach ($records as $r):
      $statusColors = [
          'present' => 'badge-green',
          'absent'  => 'badge-red',
          'late'    => 'badge-yellow',
          'excused' => 'badge-blue',
      ];
      $sc = $statusColors[$r['status']] ?? 'badge-gray';
    ?>
    <div class="flex items-center justify-between px-4 py-2.5">
      <div>
        <p class="text-sm font-medium text-gray-900">
          <?= e(date('l, d M', strtotime($r['date']))) ?>
        </p>
        <?php if ($r['remarks']): ?>
        <p class="text-xs text-gray-400 italic"><?= e($r['remarks']) ?></p>
        <?php endif; ?>
      </div>
      <span class="badge <?= $sc ?>"><?= ucfirst($r['status']) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php elseif (empty($records)): ?>
<div class="card text-center py-8 text-gray-400">
  <p class="text-3xl mb-2">📅</p>
  <p class="text-sm">No attendance records for this month.</p>
</div>
<?php endif; ?>

<?php portal_foot('attendance'); ?>
