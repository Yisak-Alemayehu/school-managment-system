<?php
/**
 * Portal — Enhanced Student Dashboard View
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

// ── Today's timetable ────────────────────────────────────────────────────────
$todayClasses = [];
if ($enrollment) {
    $todayDay = strtolower(date('l'));
    $todayClasses = db_fetch_all(
        "SELECT tt.start_time, tt.end_time, tt.room,
                s.name AS subject_name, u.full_name AS teacher_name
         FROM timetables tt
         JOIN subjects s ON s.id = tt.subject_id
         LEFT JOIN users u ON u.id = tt.teacher_id
         WHERE tt.class_id = ? AND (tt.section_id IS NULL OR tt.section_id = ?)
           AND tt.session_id = ? AND tt.day_of_week = ?
         ORDER BY tt.start_time ASC",
        [$enrollment['class_id'], $enrollment['section_id'], $enrollment['session_id'], $todayDay]
    );
}

// ── Unread messages ──────────────────────────────────────────────────────────
$unreadMsgCount = (int) db_fetch_value(
    "SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0",
    [portal_user_id()]
);

// ── Recent notices ────────────────────────────────────────────────────────────
$notices = db_fetch_all(
    "SELECT id, title, created_at FROM announcements
     WHERE status = 'published'
       AND (target_roles IS NULL OR target_roles = '' OR target_roles LIKE '%student%')
     ORDER BY created_at DESC LIMIT 3",
    []
);

// Time-based greeting
$hour = (int) date('G');
if ($hour < 12) $greeting = 'Good morning';
elseif ($hour < 17) $greeting = 'Good afternoon';
else $greeting = 'Good evening';

portal_head('Home');
?>

<!-- Greeting -->
<div class="mb-5 animate-slide-up">
  <h2 class="text-xl font-bold text-gray-900"><?= $greeting ?>, <?= e($firstName) ?>!</h2>
  <p class="text-sm text-gray-500"><?= date('l, d M Y') ?></p>
</div>

<!-- Enrollment card -->
<?php if ($enrollment): ?>
<div class="card bg-gradient-to-br from-primary-600 via-primary-700 to-primary-800 text-white mb-5 animate-slide-up" style="animation-delay: 50ms">
  <div class="flex items-center justify-between">
    <div>
      <p class="text-xs text-primary-200 font-semibold uppercase tracking-wider mb-1">Enrolled In</p>
      <p class="text-xl font-bold tracking-tight"><?= e($enrollment['class_name']) ?>
        <?php if ($enrollment['section_name']): ?>
          <span class="text-primary-200 text-base font-normal">/ <?= e($enrollment['section_name']) ?></span>
        <?php endif; ?>
      </p>
      <p class="text-sm text-primary-200 mt-1"><?= e($enrollment['session_name']) ?>
        <?php if ($enrollment['term_name']): ?> · <?= e($enrollment['term_name']) ?><?php endif; ?>
      </p>
    </div>
    <div class="w-14 h-14 rounded-2xl bg-white/15 flex items-center justify-center backdrop-blur-sm">
      <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
      </svg>
    </div>
  </div>
  <?php if ($enrollment['roll_no']): ?>
  <p class="text-xs text-primary-300 mt-2 pt-2 border-t border-white/10">Roll No: <?= e($enrollment['roll_no']) ?></p>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="card text-center py-6 text-gray-400 mb-5">
  <p class="text-sm">No active enrollment found.</p>
</div>
<?php endif; ?>

<!-- Quick stats row -->
<div class="grid grid-cols-3 gap-2.5 mb-5 animate-slide-up" style="animation-delay: 100ms">
  <!-- Attendance -->
  <div class="card text-center p-3">
    <div class="w-10 h-10 mx-auto mb-2 rounded-xl flex items-center justify-center
                <?= $att['percentage'] >= 75 ? 'bg-green-100 text-green-600' : ($att['percentage'] >= 60 ? 'bg-yellow-100 text-yellow-600' : 'bg-red-100 text-red-600') ?>">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    </div>
    <div class="text-lg font-bold text-gray-900"><?= $att['percentage'] ?>%</div>
    <div class="text-[10px] text-gray-500 font-medium">Attendance</div>
  </div>
  <!-- Messages -->
  <a href="<?= portal_url('messages') ?>" class="card text-center p-3 hover:shadow-md">
    <div class="w-10 h-10 mx-auto mb-2 rounded-xl flex items-center justify-center
                <?= $unreadMsgCount > 0 ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-500' ?>">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
      </svg>
    </div>
    <div class="text-lg font-bold text-gray-900"><?= $unreadMsgCount ?></div>
    <div class="text-[10px] text-gray-500 font-medium">Messages</div>
  </a>
  <!-- Absences -->
  <div class="card text-center p-3">
    <div class="w-10 h-10 mx-auto mb-2 rounded-xl flex items-center justify-center bg-red-100 text-red-500">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    </div>
    <div class="text-lg font-bold text-gray-900"><?= $att['absent'] ?></div>
    <div class="text-[10px] text-gray-500 font-medium">Absences</div>
  </div>
</div>

<!-- Today's schedule -->
<?php if (!empty($todayClasses)): ?>
<div class="mb-5 animate-slide-up" style="animation-delay: 150ms">
  <div class="flex items-center justify-between mb-2">
    <p class="section-title mb-0">Today's Schedule</p>
    <a href="<?= portal_url('timetable') ?>" class="text-xs text-primary-600 font-semibold">Full timetable →</a>
  </div>
  <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
    <?php $now = date('H:i:s'); foreach ($todayClasses as $cls):
      $isNow = ($now >= $cls['start_time'] && $now <= ($cls['end_time'] ?? '23:59:59'));
      $isPast = $now > ($cls['end_time'] ?? $cls['start_time']);
    ?>
    <div class="card flex-shrink-0 w-40 p-3 <?= $isNow ? 'ring-2 ring-primary-500 bg-primary-50' : ($isPast ? 'opacity-50' : '') ?>">
      <p class="text-xs font-bold <?= $isNow ? 'text-primary-600' : 'text-gray-500' ?>">
        <?= e(substr($cls['start_time'], 0, 5)) ?>
        <?php if ($cls['end_time']): ?> - <?= e(substr($cls['end_time'], 0, 5)) ?><?php endif; ?>
      </p>
      <p class="text-sm font-bold text-gray-900 mt-1 truncate"><?= e($cls['subject_name']) ?></p>
      <?php if ($cls['teacher_name']): ?>
      <p class="text-xs text-gray-400 mt-0.5 truncate"><?= e($cls['teacher_name']) ?></p>
      <?php endif; ?>
      <?php if ($isNow): ?>
      <span class="badge badge-blue mt-1.5 text-[9px]">Now</span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Attendance detail -->
<div class="mb-5 animate-slide-up" style="animation-delay: 200ms">
  <div class="flex items-center justify-between mb-2">
    <p class="section-title mb-0">Attendance Overview</p>
    <a href="<?= portal_url('attendance') ?>" class="text-xs text-primary-600 font-semibold">Details →</a>
  </div>
  <div class="card">
    <div class="flex items-center justify-between mb-3">
      <div>
        <span class="text-2xl font-bold text-gray-900"><?= $att['percentage'] ?>%</span>
        <span class="text-xs text-gray-400 ml-1">of <?= $att['total'] ?> days</span>
      </div>
      <span class="badge <?= $att['percentage'] >= 75 ? 'badge-green' : ($att['percentage'] >= 60 ? 'badge-yellow' : 'badge-red') ?>">
        <?= $att['percentage'] >= 75 ? 'Good' : ($att['percentage'] >= 60 ? 'Fair' : 'Low') ?>
      </span>
    </div>
    <div class="progress-bar mb-3">
      <div class="progress-fill <?= $att['percentage'] >= 75 ? 'bg-green-500' : ($att['percentage'] >= 60 ? 'bg-yellow-500' : 'bg-red-500') ?>"
           style="width:<?= min(100, $att['percentage']) ?>%"></div>
    </div>
    <div class="grid grid-cols-3 gap-2 text-center text-xs">
      <div class="bg-green-50 rounded-xl p-2.5">
        <div class="font-bold text-green-700 text-lg"><?= $att['present'] ?></div>
        <div class="text-green-600 font-medium">Present</div>
      </div>
      <div class="bg-red-50 rounded-xl p-2.5">
        <div class="font-bold text-red-700 text-lg"><?= $att['absent'] ?></div>
        <div class="text-red-600 font-medium">Absent</div>
      </div>
      <div class="bg-yellow-50 rounded-xl p-2.5">
        <div class="font-bold text-yellow-700 text-lg"><?= $att['late'] ?></div>
        <div class="text-yellow-600 font-medium">Late</div>
      </div>
    </div>
  </div>
</div>

<!-- Recent results -->
<?php if (!empty($recentResults)): ?>
<div class="mb-5 animate-slide-up" style="animation-delay: 250ms">
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
        <p class="text-sm font-semibold text-gray-900"><?= e($r['subject_name']) ?></p>
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
<div class="mb-5 animate-slide-up" style="animation-delay: 300ms">
  <p class="section-title">Upcoming Exams</p>
  <div class="space-y-2">
    <?php foreach ($upcomingExams as $ex):
      $daysUntil = (int) ((strtotime($ex['exam_date']) - strtotime('today')) / 86400);
    ?>
    <div class="card flex items-center gap-3">
      <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-red-50 flex flex-col items-center justify-center">
        <span class="text-xs font-bold text-red-600"><?= e(date('d', strtotime($ex['exam_date']))) ?></span>
        <span class="text-[9px] text-red-400 uppercase"><?= e(date('M', strtotime($ex['exam_date']))) ?></span>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-gray-900 truncate"><?= e($ex['subject_name']) ?></p>
        <p class="text-xs text-gray-400"><?= e($ex['exam_name']) ?></p>
      </div>
      <div class="flex-shrink-0 text-right">
        <?php if ($daysUntil === 0): ?>
          <span class="badge badge-red">Today</span>
        <?php elseif ($daysUntil === 1): ?>
          <span class="badge badge-yellow">Tomorrow</span>
        <?php else: ?>
          <span class="text-xs text-gray-500"><?= $daysUntil ?> days</span>
        <?php endif; ?>
        <?php if ($ex['start_time']): ?>
        <p class="text-[10px] text-gray-400 mt-0.5"><?= e(substr($ex['start_time'], 0, 5)) ?></p>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Notices -->
<?php if (!empty($notices)): ?>
<div class="mb-5 animate-slide-up" style="animation-delay: 350ms">
  <div class="flex items-center justify-between mb-2">
    <p class="section-title mb-0">Notices</p>
    <a href="<?= portal_url('notices') ?>" class="text-xs text-primary-600 font-semibold">See all →</a>
  </div>
  <div class="space-y-2">
    <?php foreach ($notices as $n): ?>
    <a href="<?= portal_url('notices') ?>"
       class="card flex items-center gap-3 hover:shadow-md transition-all">
      <div class="w-10 h-10 rounded-xl bg-yellow-50 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-gray-900 truncate"><?= e($n['title']) ?></p>
        <p class="text-xs text-gray-400"><?= e(date('d M Y', strtotime($n['created_at']))) ?></p>
      </div>
      <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
      </svg>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Quick access -->
<div class="mb-5 animate-slide-up" style="animation-delay: 400ms">
  <p class="section-title">Quick Access</p>
  <div class="grid grid-cols-4 gap-2">
    <a href="<?= portal_url('timetable') ?>" class="card flex flex-col items-center gap-1.5 py-3 px-2 hover:shadow-md transition-all text-center">
      <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center">
        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
      </div>
      <span class="text-[10px] font-semibold text-gray-700">Timetable</span>
    </a>
    <a href="<?= portal_url('notices') ?>" class="card flex flex-col items-center gap-1.5 py-3 px-2 hover:shadow-md transition-all text-center">
      <div class="w-9 h-9 rounded-xl bg-yellow-50 flex items-center justify-center">
        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
      </div>
      <span class="text-[10px] font-semibold text-gray-700">Notices</span>
    </a>
    <a href="<?= portal_url('results') ?>" class="card flex flex-col items-center gap-1.5 py-3 px-2 hover:shadow-md transition-all text-center">
      <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center">
        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
      </div>
      <span class="text-[10px] font-semibold text-gray-700">Results</span>
    </a>
    <a href="<?= portal_url('profile') ?>" class="card flex flex-col items-center gap-1.5 py-3 px-2 hover:shadow-md transition-all text-center">
      <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center">
        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
      </div>
      <span class="text-[10px] font-semibold text-gray-700">Profile</span>
    </a>
  </div>
</div>

<?php portal_foot('dashboard'); ?>
