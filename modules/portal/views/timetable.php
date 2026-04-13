<?php
/**
 * Portal — Timetable View (student & parent)
 */

$role = portal_role();
if ($role === 'student') {
    $studentId = portal_linked_id();
} else {
    $child = portal_active_child();
    if (!$child) {
        portal_head('Timetable', portal_url('dashboard'));
        echo '<div class="card text-center py-12 text-gray-400"><p class="text-4xl mb-3">📋</p>';
        echo '<p class="text-sm">No child selected. Go back to the dashboard.</p></div>';
        portal_foot('timetable');
        return;
    }
    $studentId = (int) $child['id'];
}

$enrollment = db_fetch_one(
    "SELECT e.class_id, e.section_id, e.session_id,
            c.name AS class_name, sec.name AS section_name
     FROM enrollments e
     JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
     JOIN classes c ON c.id = e.class_id
     LEFT JOIN sections sec ON sec.id = e.section_id
     WHERE e.student_id = ? AND e.status = 'active'
     ORDER BY e.id DESC LIMIT 1",
    [$studentId]
);

$timetable = [];
$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

if ($enrollment) {
    $rows = db_fetch_all(
        "SELECT tt.day_of_week, tt.start_time, tt.end_time, tt.room,
                s.name AS subject_name, s.code AS subject_code,
                u.full_name AS teacher_name
         FROM timetables tt
         JOIN subjects s ON s.id = tt.subject_id
         LEFT JOIN users u ON u.id = tt.teacher_id
         WHERE tt.class_id = ?
           AND (tt.section_id IS NULL OR tt.section_id = ?)
           AND tt.session_id = ?
         ORDER BY FIELD(tt.day_of_week,'monday','tuesday','wednesday',
                        'thursday','friday','saturday','sunday'),
                  tt.start_time ASC",
        [$enrollment['class_id'], $enrollment['section_id'], $enrollment['session_id']]
    );

    foreach ($days as $day) {
        $daySlots = array_values(array_filter($rows, fn($r) => $r['day_of_week'] === $day));
        if (!empty($daySlots)) {
            $timetable[$day] = $daySlots;
        }
    }
}

$today = strtolower(date('l')); // 'monday', 'tuesday', etc.

portal_head('Timetable', portal_url('dashboard'));
?>

<?php if ($enrollment): ?>
<div class="flex items-center gap-2 mb-5">
  <span class="badge badge-blue"><?= e($enrollment['class_name']) ?></span>
  <?php if ($enrollment['section_name']): ?>
  <span class="badge badge-gray"><?= e($enrollment['section_name']) ?></span>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if (empty($timetable)): ?>
<div class="card text-center py-12 text-gray-400">
  <p class="text-4xl mb-3">📋</p>
  <p class="text-sm">No timetable available for your class.</p>
</div>
<?php else: ?>

<!-- Day tabs -->
<div class="flex gap-1.5 overflow-x-auto pb-2 mb-4 scrollbar-hide">
  <?php foreach (array_keys($timetable) as $day): ?>
  <a href="#day-<?= $day ?>"
     class="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors
            <?= $day === $today ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-600 border-gray-200 hover:border-primary-300' ?>">
    <?= ucfirst($day) ?>
    <?php if ($day === $today): ?> ← Today<?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- Day blocks -->
<?php foreach ($timetable as $day => $slots): ?>
<div id="day-<?= $day ?>" class="mb-5">
  <div class="flex items-center gap-2 mb-2">
    <p class="section-title mb-0"><?= ucfirst($day) ?></p>
    <?php if ($day === $today): ?>
    <span class="badge badge-blue">Today</span>
    <?php endif; ?>
  </div>
  <div class="card p-0 overflow-hidden divide-y divide-gray-50">
    <?php foreach ($slots as $slot): ?>
    <div class="flex items-center gap-4 px-4 py-3">
      <!-- Time column -->
      <div class="flex-shrink-0 text-center w-16">
        <p class="text-xs font-bold text-primary-600"><?= e(substr($slot['start_time'], 0, 5)) ?></p>
        <?php if ($slot['end_time']): ?>
        <p class="text-xs text-gray-400"><?= e(substr($slot['end_time'], 0, 5)) ?></p>
        <?php endif; ?>
      </div>
      <!-- Subject -->
      <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-gray-900"><?= e($slot['subject_name']) ?></p>
        <?php if ($slot['teacher_name']): ?>
        <p class="text-xs text-gray-500">👨‍🏫 <?= e($slot['teacher_name']) ?></p>
        <?php endif; ?>
      </div>
      <!-- Room -->
      <?php if ($slot['room']): ?>
      <div class="flex-shrink-0">
        <span class="badge badge-gray">🚪 <?= e($slot['room']) ?></span>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php portal_foot(); ?>
