<?php
/**
 * Portal — Student Dashboard View
 */

$studentId = portal_linked_id();
$student   = portal_student();
$firstName = explode(' ', trim($student['full_name'] ?? 'Student'))[0];

// ── Enrollment ───────────────────────────────────────────────────────────────
$enrollment = db_fetch_one(
    "SELECT e.*, c.name AS class_name, sec.name AS section_name,
            acs.name AS session_name, t.name AS term_name
     FROM enrollments e
     JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
     JOIN classes c ON c.id = e.class_id
     LEFT JOIN sections sec ON sec.id = e.section_id
     LEFT JOIN terms t ON t.session_id = acs.id AND t.is_active = 1
     WHERE e.student_id = ? AND e.status = 'active'
     ORDER BY e.id DESC LIMIT 1",
    [$studentId]
);

// ── Attendance summary ────────────────────────────────────────────────────────
$att = ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0, 'percentage' => 0];
if ($enrollment) {
    $rows = db_fetch_all(
        "SELECT status, COUNT(*) AS cnt FROM attendance
         WHERE student_id = ? AND session_id = ? AND subject_id IS NULL
         GROUP BY status",
        [$studentId, $enrollment['session_id']]
    );
    foreach ($rows as $r) {
        $att[$r['status']] = (int) $r['cnt'];
    }
    $att['total'] = $att['present'] + $att['absent'] + $att['late'];
    $att['percentage'] = $att['total']
        ? round(($att['present'] + $att['late']) / $att['total'] * 100, 1) : 0;
}

// ── Recent results ────────────────────────────────────────────────────────────
$recentResults = db_fetch_all(
    "SELECT m.marks_obtained, m.max_marks, m.is_absent,
            s.name AS subject_name, e.name AS exam_name
     FROM marks m
     JOIN subjects s ON s.id = m.subject_id
     JOIN exams e ON e.id = m.exam_id
     WHERE m.student_id = ?
     ORDER BY m.created_at DESC LIMIT 5",
    [$studentId]
);

// ── Upcoming exams ────────────────────────────────────────────────────────────
$upcomingExams = [];
if ($enrollment) {
    $upcomingExams = db_fetch_all(
        "SELECT es.exam_date, es.start_time, s.name AS subject_name, e.name AS exam_name
         FROM exam_schedules es
         JOIN exams e ON e.id = es.exam_id
         JOIN subjects s ON s.id = es.subject_id
         WHERE es.class_id = ? AND e.status = 'upcoming' AND es.exam_date >= CURDATE()
         ORDER BY es.exam_date ASC LIMIT 5",
        [$enrollment['class_id']]
    );
}

// ── Recent notices ────────────────────────────────────────────────────────────
$notices = db_fetch_all(
    "SELECT id, title, created_at FROM announcements
     WHERE status = 'published'
       AND (target_roles IS NULL OR target_roles = '' OR target_roles LIKE '%student%')
     ORDER BY created_at DESC LIMIT 3",
    []
);

portal_head('Home');
?>

<!-- Greeting -->
<div class="mb-4 flex items-center justify-between">
  <div>
    <h2 class="text-xl font-bold text-gray-900">Hi, <?= e($firstName) ?>! 👋</h2>
    <p class="text-sm text-gray-500">Welcome back to your portal.</p>
  </div>
  <a href="<?= portal_url('timetable') ?>"
     class="text-xs text-primary-600 font-semibold flex items-center gap-1 bg-primary-50 px-3 py-1.5 rounded-full">
    📋 Timetable
  </a>
</div>

<!-- Enrollment card -->
<?php if ($enrollment): ?>
<div class="card bg-gradient-to-br from-primary-600 to-primary-800 text-white mb-5">
  <p class="text-xs text-primary-200 font-semibold uppercase tracking-wide mb-1">Enrolled In</p>
  <p class="text-xl font-bold"><?= e($enrollment['class_name']) ?>
    <?php if ($enrollment['section_name']): ?>
      <span class="text-primary-200 text-base">/ <?= e($enrollment['section_name']) ?></span>
    <?php endif; ?>
  </p>
  <p class="text-sm text-primary-200 mt-1"><?= e($enrollment['session_name']) ?>
    <?php if ($enrollment['term_name']): ?> · <?= e($enrollment['term_name']) ?><?php endif; ?>
  </p>
  <?php if ($enrollment['roll_no']): ?>
  <p class="text-xs text-primary-300 mt-1">Roll No: <?= e($enrollment['roll_no']) ?></p>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="card text-center py-6 text-gray-400 mb-5">
  <p class="text-sm">No active enrollment found.</p>
</div>
<?php endif; ?>

<!-- Attendance summary -->
<div class="mb-5">
  <p class="section-title">Attendance This Session</p>
  <div class="card">
    <div class="flex items-center justify-between mb-3">
      <span class="text-2xl font-bold text-gray-900"><?= $att['percentage'] ?>%</span>
      <span class="badge <?= $att['percentage'] >= 75 ? 'badge-green' : ($att['percentage'] >= 60 ? 'badge-yellow' : 'badge-red') ?>">
        <?= $att['percentage'] >= 75 ? 'Good' : ($att['percentage'] >= 60 ? 'Fair' : 'Low') ?>
      </span>
    </div>
    <div class="progress-bar mb-3">
      <div class="progress-fill <?= $att['percentage'] >= 75 ? 'bg-green-500' : ($att['percentage'] >= 60 ? 'bg-yellow-500' : 'bg-red-500') ?>"
           style="width:<?= min(100, $att['percentage']) ?>%"></div>
    </div>
    <div class="grid grid-cols-3 gap-2 text-center text-xs">
      <div class="bg-green-50 rounded-lg p-2">
        <div class="font-bold text-green-700 text-lg"><?= $att['present'] ?></div>
        <div class="text-green-600">Present</div>
      </div>
      <div class="bg-red-50 rounded-lg p-2">
        <div class="font-bold text-red-700 text-lg"><?= $att['absent'] ?></div>
        <div class="text-red-600">Absent</div>
      </div>
      <div class="bg-yellow-50 rounded-lg p-2">
        <div class="font-bold text-yellow-700 text-lg"><?= $att['late'] ?></div>
        <div class="text-yellow-600">Late</div>
      </div>
    </div>
  </div>
</div>

<!-- Recent results -->
<?php if (!empty($recentResults)): ?>
<div class="mb-5">
  <div class="flex items-center justify-between mb-2">
    <p class="section-title mb-0">Recent Results</p>
    <a href="<?= portal_url('results') ?>" class="text-xs text-primary-600 font-semibold">See all →</a>
  </div>
  <div class="card p-0 overflow-hidden divide-y divide-gray-50">
    <?php foreach ($recentResults as $r):
      $pct = ($r['max_marks'] > 0 && !$r['is_absent'])
             ? round($r['marks_obtained'] / $r['max_marks'] * 100, 1) : null;
      $cls = $pct === null ? 'text-gray-400' : ($pct >= 75 ? 'text-green-600' : ($pct >= 50 ? 'text-yellow-600' : 'text-red-600'));
    ?>
    <div class="flex items-center justify-between px-4 py-3">
      <div>
        <p class="text-sm font-medium text-gray-900"><?= e($r['subject_name']) ?></p>
        <p class="text-xs text-gray-400"><?= e($r['exam_name']) ?></p>
      </div>
      <div class="text-right">
        <?php if ($r['is_absent']): ?>
          <span class="badge badge-red">Absent</span>
        <?php else: ?>
          <span class="font-bold text-sm <?= $cls ?>"><?= e($r['marks_obtained']) ?>/<?= e($r['max_marks']) ?></span>
          <?php if ($pct !== null): ?>
          <p class="text-xs text-gray-400"><?= $pct ?>%</p>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Upcoming exams -->
<?php if (!empty($upcomingExams)): ?>
<div class="mb-5">
  <p class="section-title">Upcoming Exams</p>
  <div class="card p-0 overflow-hidden divide-y divide-gray-50">
    <?php foreach ($upcomingExams as $ex): ?>
    <div class="flex items-center justify-between px-4 py-3">
      <div>
        <p class="text-sm font-medium text-gray-900"><?= e($ex['subject_name']) ?></p>
        <p class="text-xs text-gray-400"><?= e($ex['exam_name']) ?></p>
      </div>
      <div class="text-right">
        <p class="text-xs font-semibold text-primary-600"><?= e(date('d M', strtotime($ex['exam_date']))) ?></p>
        <?php if ($ex['start_time']): ?>
        <p class="text-xs text-gray-400"><?= e(substr($ex['start_time'], 0, 5)) ?></p>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Recent notices -->
<?php if (!empty($notices)): ?>
<div class="mb-5">
  <div class="flex items-center justify-between mb-2">
    <p class="section-title mb-0">Notices</p>
    <a href="<?= portal_url('notices') ?>" class="text-xs text-primary-600 font-semibold">See all →</a>
  </div>
  <div class="space-y-2">
    <?php foreach ($notices as $n): ?>
    <a href="<?= portal_url('notices') ?>"
       class="card flex items-start gap-3 hover:bg-gray-50 transition-colors">
      <span class="text-xl mt-0.5">🔔</span>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-gray-900 truncate"><?= e($n['title']) ?></p>
        <p class="text-xs text-gray-400"><?= e(date('d M Y', strtotime($n['created_at']))) ?></p>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Quick links for features not in bottom nav -->
<div class="mb-5">
  <p class="section-title">Quick Access</p>
  <div class="grid grid-cols-2 gap-3">
    <a href="<?= portal_url('timetable') ?>" class="card flex items-center gap-3 hover:bg-gray-50 transition-colors">
      <span class="text-2xl">📋</span>
      <span class="text-sm font-semibold text-gray-900">Timetable</span>
    </a>
    <a href="<?= portal_url('notices') ?>" class="card flex items-center gap-3 hover:bg-gray-50 transition-colors">
      <span class="text-2xl">🔔</span>
      <span class="text-sm font-semibold text-gray-900">Notices</span>
    </a>
  </div>
</div>

<?php portal_foot('dashboard'); ?>
