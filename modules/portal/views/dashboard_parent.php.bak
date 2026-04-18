<?php
/**
 * Portal — Parent Dashboard View
 */

$guardianId  = portal_linked_id();
$guardian    = portal_guardian();
$children    = portal_children();
$activeChild = portal_active_child();

// Per-child summary (for active child)
$childSummary = null;
if ($activeChild) {
    $childId = (int) $activeChild['id'];

    $enrollment = db_fetch_one(
        "SELECT session_id, class_id FROM enrollments e
         JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
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

    $childSummary = [
        'attendance'  => $att,
        'latest_marks'=> $latestMarks,
        'fee_balance' => [
            'total' => (float) ($feeRow['total'] ?? 0),
            'paid'  => (float) ($feeRow['paid'] ?? 0),
            'due'   => (float) ($feeRow['due'] ?? 0),
        ],
    ];
}

// Notices for parents
$notices = db_fetch_all(
    "SELECT id, title, created_at FROM announcements
     WHERE status = 'published'
       AND (target_roles IS NULL OR target_roles = '' OR target_roles LIKE '%parent%')
     ORDER BY created_at DESC LIMIT 3",
    []
);

portal_head('Home');
$firstName = explode(' ', trim($guardian['name'] ?? 'Parent'))[0];
?>

<!-- Greeting -->
<div class="mb-4">
  <h2 class="text-xl font-bold text-gray-900">Hi, <?= e($firstName) ?>! 👋</h2>
  <p class="text-sm text-gray-500">Here's an overview of your child's progress.</p>
</div>

<!-- Child selector -->
<?php if (count($children) > 1): ?>
<div class="mb-5">
  <p class="section-title">Select Child</p>
  <div class="flex gap-2 flex-wrap">
    <?php foreach ($children as $child):
      $isActive = $activeChild && (int)$activeChild['id'] === (int)$child['id'];
    ?>
    <form method="POST" action="<?= portal_url('switch-child') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="child_id" value="<?= (int)$child['id'] ?>">
      <button type="submit"
              class="px-3 py-2 rounded-xl text-sm font-semibold border-2 transition-all
                     <?= $isActive ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-600 hover:border-primary-300' ?>">
        <?= e(explode(' ', $child['full_name'])[0]) ?>
        <span class="text-xs font-normal opacity-60"><?= e($child['class_name'] ?? '') ?></span>
      </button>
    </form>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if ($activeChild && $childSummary): ?>

<!-- Child info card -->
<div class="card bg-gradient-to-br from-primary-600 to-primary-800 text-white mb-5">
  <div class="flex items-center gap-3">
    <?php if ($activeChild['photo'] ?? null): ?>
    <img src="<?= e($activeChild['photo']) ?>" alt=""
         class="w-14 h-14 rounded-full object-cover border-2 border-primary-400">
    <?php else: ?>
    <div class="w-14 h-14 rounded-full bg-primary-500 flex items-center justify-center text-2xl">
      🎓
    </div>
    <?php endif; ?>
    <div>
      <p class="font-bold text-lg"><?= e($activeChild['full_name']) ?></p>
      <p class="text-primary-200 text-sm">
        <?= e($activeChild['class_name'] ?? 'N/A') ?>
        <?php if ($activeChild['section_name'] ?? null): ?>
          · <?= e($activeChild['section_name']) ?>
        <?php endif; ?>
      </p>
    </div>
  </div>
</div>

<!-- Quick stats row -->
<div class="grid grid-cols-3 gap-3 mb-5">
  <!-- Attendance -->
  <?php $atPct = $childSummary['attendance']['percentage']; ?>
  <div class="card text-center p-3">
    <div class="text-2xl font-bold <?= $atPct >= 75 ? 'text-green-600' : ($atPct >= 60 ? 'text-yellow-600' : 'text-red-600') ?>">
      <?= $atPct ?>%
    </div>
    <div class="text-xs text-gray-500 mt-0.5">Attendance</div>
  </div>
  <!-- Absents -->
  <div class="card text-center p-3">
    <div class="text-2xl font-bold text-red-600"><?= $childSummary['attendance']['absent'] ?></div>
    <div class="text-xs text-gray-500 mt-0.5">Absences</div>
  </div>
  <!-- Fee due -->
  <div class="card text-center p-3">
    <?php $due = $childSummary['fee_balance']['due']; ?>
    <div class="text-xl font-bold <?= $due > 0 ? 'text-red-600' : 'text-green-600' ?>">
      <?= $due > 0 ? number_format($due, 0) : '✓' ?>
    </div>
    <div class="text-xs text-gray-500 mt-0.5">Fee Due (ETB)</div>
  </div>
</div>

<!-- Fee summary -->
<?php if ($childSummary['fee_balance']['total'] > 0): ?>
<div class="mb-5">
  <div class="flex items-center justify-between mb-2">
    <p class="section-title mb-0">Fee Balance</p>
    <a href="<?= portal_url('fees') ?>" class="text-xs text-primary-600 font-semibold">Details →</a>
  </div>
  <div class="card bg-<?= $due > 0 ? 'red' : 'green' ?>-50 border border-<?= $due > 0 ? 'red' : 'green' ?>-100">
    <?php $fb = $childSummary['fee_balance']; ?>
    <div class="flex justify-between text-sm mb-2">
      <span class="text-gray-600">Total Fees</span>
      <span class="font-semibold"><?= number_format($fb['total'], 2) ?> ETB</span>
    </div>
    <div class="flex justify-between text-sm mb-2">
      <span class="text-gray-600">Paid</span>
      <span class="font-semibold text-green-700"><?= number_format($fb['paid'], 2) ?> ETB</span>
    </div>
    <div class="flex justify-between text-sm font-bold border-t border-gray-200 pt-2">
      <span>Balance Due</span>
      <span class="<?= $fb['due'] > 0 ? 'text-red-600' : 'text-green-600' ?>">
        <?= number_format($fb['due'], 2) ?> ETB
      </span>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Latest marks -->
<?php if (!empty($childSummary['latest_marks'])): ?>
<div class="mb-5">
  <p class="section-title">Latest Results</p>
  <div class="card p-0 overflow-hidden divide-y divide-gray-50">
    <?php foreach ($childSummary['latest_marks'] as $m):
      $cls = $m['is_absent'] ? 'text-gray-400'
           : ($m['percentage'] >= 75 ? 'text-green-600' : ($m['percentage'] >= 50 ? 'text-yellow-600' : 'text-red-600'));
    ?>
    <div class="flex items-center justify-between px-4 py-2.5">
      <div>
        <p class="text-sm font-medium text-gray-900"><?= e($m['subject_name']) ?></p>
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
<div class="card text-center py-10 text-gray-400">
  <p class="text-4xl mb-3">👨‍👩‍👧</p>
  <p class="text-sm">No children linked to your account.<br>Please contact the school administration.</p>
</div>
<?php endif; ?>

<!-- Notices -->
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
  <div class="grid grid-cols-3 gap-3">
    <a href="<?= portal_url('attendance') ?>" class="card flex flex-col items-center gap-1.5 py-3 hover:bg-gray-50 transition-colors">
      <span class="text-2xl">📅</span>
      <span class="text-xs font-semibold text-gray-900">Attendance</span>
    </a>
    <a href="<?= portal_url('timetable') ?>" class="card flex flex-col items-center gap-1.5 py-3 hover:bg-gray-50 transition-colors">
      <span class="text-2xl">📋</span>
      <span class="text-xs font-semibold text-gray-900">Timetable</span>
    </a>
    <a href="<?= portal_url('notices') ?>" class="card flex flex-col items-center gap-1.5 py-3 hover:bg-gray-50 transition-colors">
      <span class="text-2xl">🔔</span>
      <span class="text-xs font-semibold text-gray-900">Notices</span>
    </a>
  </div>
</div>

<?php portal_foot('dashboard'); ?>
