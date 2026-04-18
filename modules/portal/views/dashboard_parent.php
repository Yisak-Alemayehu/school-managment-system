<?php
/**
 * Portal — Enhanced Parent Dashboard View
 */

$guardianId  = portal_linked_id();
$guardian    = portal_guardian();
$children    = portal_children();
$activeChild = portal_active_child();

$childSummary = null;
if ($activeChild) {
    $childId = (int) $activeChild['id'];

    $enrollment = db_fetch_one(
        "SELECT e.session_id, e.class_id, c.name AS class_name, sec.name AS section_name,
                acs.name AS session_name, t.name AS term_name
         FROM enrollments e
         JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
         JOIN classes c ON c.id = e.class_id
         LEFT JOIN sections sec ON sec.id = e.section_id
         LEFT JOIN terms t ON t.session_id = acs.id AND t.is_active = 1
         WHERE e.student_id = ? AND e.status = 'active'
         ORDER BY e.id DESC LIMIT 1",
        [$childId]
    );

    // Attendance
    $att = ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0, 'percentage' => 0];
    if ($enrollment) {
        $rows = db_fetch_all(
            "SELECT status, COUNT(*) AS cnt FROM attendance
             WHERE student_id = ? AND session_id = ? AND subject_id IS NULL
             GROUP BY status",
            [$childId, $enrollment['session_id']]
        );
        foreach ($rows as $r) {
            $att[$r['status']] = (int) $r['cnt'];
        }
        $att['total'] = $att['present'] + $att['absent'] + $att['late'];
        $att['percentage'] = $att['total']
            ? round(($att['present'] + $att['late']) / $att['total'] * 100, 1) : 0;
    }

    // Latest marks
    $latestMarks = db_fetch_all(
        "SELECT m.marks_obtained, m.max_marks, m.is_absent,
                s.name AS subject_name, ex.name AS exam_name,
                CASE WHEN m.max_marks > 0 AND m.is_absent = 0
                     THEN ROUND(m.marks_obtained / m.max_marks * 100, 1)
                     ELSE NULL END AS percentage
         FROM marks m
         JOIN subjects s ON s.id = m.subject_id
         JOIN exams ex ON ex.id = m.exam_id
         WHERE m.student_id = ?
         ORDER BY m.created_at DESC LIMIT 6",
        [$childId]
    );

    // Fee balance
    $feeRow = db_fetch_one(
        "SELECT SUM(sf.amount) AS total, SUM(sf.balance) AS due,
                SUM(sf.amount - sf.balance) AS paid
         FROM fin_student_fees sf WHERE sf.student_id = ? AND sf.is_active = 1",
        [$childId]
    );

    // Today's classes
    $todayClasses = [];
    if ($enrollment) {
        $todayDay = strtolower(date('l'));
        $todayClasses = db_fetch_all(
            "SELECT tt.start_time, tt.end_time, s.name AS subject_name
             FROM timetables tt
             JOIN subjects s ON s.id = tt.subject_id
             WHERE tt.class_id = ? AND (tt.section_id IS NULL OR tt.section_id = ?)
               AND tt.session_id = ? AND tt.day_of_week = ?
             ORDER BY tt.start_time ASC",
            [$enrollment['class_id'], $enrollment['section_id'] ?? null, $enrollment['session_id'], $todayDay]
        );
    }

    $childSummary = [
        'enrollment'   => $enrollment,
        'attendance'   => $att,
        'latest_marks' => $latestMarks,
        'today_classes'=> $todayClasses,
        'fee_balance'  => [
            'total' => (float) ($feeRow['total'] ?? 0),
            'paid'  => (float) ($feeRow['paid'] ?? 0),
            'due'   => (float) ($feeRow['due'] ?? 0),
        ],
    ];
}

// Unread messages
$unreadMsgCount = (int) db_fetch_value(
    "SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0",
    [portal_user_id()]
);

// Notices
$notices = db_fetch_all(
    "SELECT id, title, created_at FROM announcements
     WHERE status = 'published'
       AND (target_roles IS NULL OR target_roles = '' OR target_roles LIKE '%parent%')
     ORDER BY created_at DESC LIMIT 3",
    []
);

portal_head('Home');
$firstName = explode(' ', trim($guardian['name'] ?? 'Parent'))[0];
$hour = (int) date('G');
if ($hour < 12) $greeting = 'Good morning';
elseif ($hour < 17) $greeting = 'Good afternoon';
else $greeting = 'Good evening';
?>

<!-- Greeting -->
<div class="mb-5 animate-slide-up">
  <h2 class="text-xl font-bold text-gray-900"><?= $greeting ?>, <?= e($firstName) ?>!</h2>
  <p class="text-sm text-gray-500"><?= date('l, d M Y') ?></p>
</div>

<!-- Child selector -->
<?php if (count($children) > 1): ?>
<div class="mb-5 animate-slide-up" style="animation-delay: 50ms">
  <p class="section-title">Your Children</p>
  <div class="flex gap-2.5 overflow-x-auto pb-2 scrollbar-hide">
    <?php foreach ($children as $child):
      $isActive = $activeChild && (int)$activeChild['id'] === (int)$child['id'];
    ?>
    <form method="POST" action="<?= portal_url('switch-child') ?>" class="flex-shrink-0">
      <?= csrf_field() ?>
      <input type="hidden" name="child_id" value="<?= (int)$child['id'] ?>">
      <button type="submit"
              class="flex items-center gap-2.5 px-4 py-2.5 rounded-2xl text-sm font-semibold border-2 transition-all
                     <?= $isActive
                       ? 'border-primary-600 bg-primary-50 text-primary-700 shadow-md shadow-primary-100'
                       : 'border-gray-200 bg-white text-gray-600 hover:border-primary-300' ?>">
        <div class="w-8 h-8 rounded-full <?= $isActive ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-600' ?> flex items-center justify-center text-xs font-bold">
          <?= e(mb_substr($child['full_name'], 0, 1)) ?>
        </div>
        <div class="text-left">
          <p class="text-sm font-bold"><?= e(explode(' ', $child['full_name'])[0]) ?></p>
          <p class="text-[10px] font-normal opacity-60"><?= e($child['class_name'] ?? '') ?></p>
        </div>
      </button>
    </form>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if ($activeChild && $childSummary): ?>

<!-- Child info card -->
<div class="card bg-gradient-to-br from-primary-600 via-primary-700 to-primary-800 text-white mb-5 animate-slide-up" style="animation-delay: 100ms">
  <div class="flex items-center gap-4">
    <?php if ($activeChild['photo'] ?? null): ?>
    <img src="<?= e($activeChild['photo']) ?>" alt=""
         class="w-16 h-16 rounded-2xl object-cover border-2 border-white/20">
    <?php else: ?>
    <div class="w-16 h-16 rounded-2xl bg-white/15 flex items-center justify-center text-2xl backdrop-blur-sm">
      <?= e(mb_substr($activeChild['full_name'], 0, 1)) ?>
    </div>
    <?php endif; ?>
    <div class="flex-1 min-w-0">
      <p class="font-bold text-lg tracking-tight"><?= e($activeChild['full_name']) ?></p>
      <p class="text-primary-200 text-sm">
        <?= e($activeChild['class_name'] ?? 'N/A') ?>
        <?php if ($activeChild['section_name'] ?? null): ?> · <?= e($activeChild['section_name']) ?><?php endif; ?>
      </p>
      <?php if ($childSummary['enrollment']): ?>
      <p class="text-primary-300 text-xs mt-0.5"><?= e($childSummary['enrollment']['session_name']) ?>
        <?php if ($childSummary['enrollment']['term_name']): ?> · <?= e($childSummary['enrollment']['term_name']) ?><?php endif; ?>
      </p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Quick stats -->
<div class="grid grid-cols-4 gap-2 mb-5 animate-slide-up" style="animation-delay: 150ms">
  <?php $atPct = $childSummary['attendance']['percentage']; $due = $childSummary['fee_balance']['due']; ?>
  <div class="card text-center p-2.5">
    <div class="text-lg font-bold <?= $atPct >= 75 ? 'text-green-600' : ($atPct >= 60 ? 'text-yellow-600' : 'text-red-600') ?>">
      <?= $atPct ?>%
    </div>
    <div class="text-[9px] text-gray-500 font-medium mt-0.5">Attendance</div>
  </div>
  <div class="card text-center p-2.5">
    <div class="text-lg font-bold text-red-600"><?= $childSummary['attendance']['absent'] ?></div>
    <div class="text-[9px] text-gray-500 font-medium mt-0.5">Absences</div>
  </div>
  <div class="card text-center p-2.5">
    <div class="text-lg font-bold <?= $due > 0 ? 'text-red-600' : 'text-green-600' ?>">
      <?= $due > 0 ? number_format($due, 0) : '✓' ?>
    </div>
    <div class="text-[9px] text-gray-500 font-medium mt-0.5">Fee Due</div>
  </div>
  <a href="<?= portal_url('messages') ?>" class="card text-center p-2.5 hover:shadow-md">
    <div class="text-lg font-bold <?= $unreadMsgCount > 0 ? 'text-primary-600' : 'text-gray-400' ?>"><?= $unreadMsgCount ?></div>
    <div class="text-[9px] text-gray-500 font-medium mt-0.5">Messages</div>
  </a>
</div>

<!-- Today's classes (horizontal scroll) -->
<?php if (!empty($childSummary['today_classes'])): ?>
<div class="mb-5 animate-slide-up" style="animation-delay: 175ms">
  <div class="flex items-center justify-between mb-2">
    <p class="section-title mb-0">Today's Classes</p>
    <a href="<?= portal_url('timetable') ?>" class="text-xs text-primary-600 font-semibold">Timetable →</a>
  </div>
  <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
    <?php foreach ($childSummary['today_classes'] as $cls): ?>
    <div class="card flex-shrink-0 w-32 p-2.5">
      <p class="text-[10px] font-bold text-primary-600"><?= e(substr($cls['start_time'], 0, 5)) ?></p>
      <p class="text-xs font-bold text-gray-900 truncate mt-0.5"><?= e($cls['subject_name']) ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Fee summary -->
<?php if ($childSummary['fee_balance']['total'] > 0): ?>
<div class="mb-5 animate-slide-up" style="animation-delay: 200ms">
  <div class="flex items-center justify-between mb-2">
    <p class="section-title mb-0">Fee Balance</p>
    <a href="<?= portal_url('fees') ?>" class="text-xs text-primary-600 font-semibold">Details →</a>
  </div>
  <?php $fb = $childSummary['fee_balance']; ?>
  <div class="card <?= $due > 0 ? 'border-red-100' : 'border-green-100' ?>">
    <div class="flex justify-between text-sm mb-2">
      <span class="text-gray-600">Total Fees</span>
      <span class="font-semibold"><?= number_format($fb['total'], 2) ?> ETB</span>
    </div>
    <div class="flex justify-between text-sm mb-2">
      <span class="text-gray-600">Paid</span>
      <span class="font-semibold text-green-700"><?= number_format($fb['paid'], 2) ?> ETB</span>
    </div>
    <div class="flex justify-between text-sm font-bold border-t border-gray-100 pt-2">
      <span>Balance Due</span>
      <span class="<?= $fb['due'] > 0 ? 'text-red-600' : 'text-green-600' ?>">
        <?= number_format($fb['due'], 2) ?> ETB
      </span>
    </div>
    <!-- Payment progress -->
    <?php $payPct = $fb['total'] > 0 ? round($fb['paid'] / $fb['total'] * 100) : 0; ?>
    <div class="progress-bar mt-3">
      <div class="progress-fill bg-green-500" style="width:<?= $payPct ?>%"></div>
    </div>
    <p class="text-[10px] text-gray-400 mt-1 text-right"><?= $payPct ?>% paid</p>
  </div>
</div>
<?php endif; ?>

<!-- Latest marks -->
<?php if (!empty($childSummary['latest_marks'])): ?>
<div class="mb-5 animate-slide-up" style="animation-delay: 250ms">
  <div class="flex items-center justify-between mb-2">
    <p class="section-title mb-0">Latest Results</p>
    <a href="<?= portal_url('results') ?>" class="text-xs text-primary-600 font-semibold">See all →</a>
  </div>
  <div class="card p-0 overflow-hidden divide-y divide-gray-50">
    <?php foreach ($childSummary['latest_marks'] as $m):
      $cls = $m['is_absent'] ? 'text-gray-400'
           : ($m['percentage'] >= 75 ? 'text-green-600' : ($m['percentage'] >= 50 ? 'text-yellow-600' : 'text-red-600'));
    ?>
    <div class="flex items-center justify-between px-4 py-3">
      <div>
        <p class="text-sm font-semibold text-gray-900"><?= e($m['subject_name']) ?></p>
        <p class="text-xs text-gray-400"><?= e($m['exam_name']) ?></p>
      </div>
      <div class="text-right">
        <?php if ($m['is_absent']): ?>
          <span class="badge badge-red">Absent</span>
        <?php else: ?>
          <span class="font-bold text-sm <?= $cls ?>"><?= e($m['marks_obtained']) ?>/<?= e($m['max_marks']) ?></span>
          <?php if ($m['percentage'] !== null): ?>
          <p class="text-xs text-gray-400"><?= $m['percentage'] ?>%</p>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php elseif (empty($children)): ?>
<div class="card text-center py-12 text-gray-400 animate-slide-up">
  <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
  </div>
  <p class="text-sm font-medium text-gray-500">No children linked to your account</p>
  <p class="text-xs text-gray-400 mt-1">Please contact the school administration.</p>
</div>
<?php endif; ?>

<!-- Notices -->
<?php if (!empty($notices)): ?>
<div class="mb-5 animate-slide-up" style="animation-delay: 300ms">
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

<!-- Quick links -->
<div class="mb-5 animate-slide-up" style="animation-delay: 350ms">
  <p class="section-title">Quick Access</p>
  <div class="grid grid-cols-4 gap-2">
    <a href="<?= portal_url('attendance') ?>" class="card flex flex-col items-center gap-1.5 py-3 px-2 hover:shadow-md transition-all">
      <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center">
        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <span class="text-[10px] font-semibold text-gray-700">Attendance</span>
    </a>
    <a href="<?= portal_url('timetable') ?>" class="card flex flex-col items-center gap-1.5 py-3 px-2 hover:shadow-md transition-all">
      <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center">
        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
      </div>
      <span class="text-[10px] font-semibold text-gray-700">Timetable</span>
    </a>
    <a href="<?= portal_url('results') ?>" class="card flex flex-col items-center gap-1.5 py-3 px-2 hover:shadow-md transition-all">
      <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center">
        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
      </div>
      <span class="text-[10px] font-semibold text-gray-700">Results</span>
    </a>
    <a href="<?= portal_url('notices') ?>" class="card flex flex-col items-center gap-1.5 py-3 px-2 hover:shadow-md transition-all">
      <div class="w-9 h-9 rounded-xl bg-yellow-50 flex items-center justify-center">
        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
      </div>
      <span class="text-[10px] font-semibold text-gray-700">Notices</span>
    </a>
  </div>
</div>

<?php portal_foot('dashboard'); ?>
