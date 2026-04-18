<?php
/**
 * Portal — Enhanced Timetable View (student & parent)
 * Features: Tab navigation, today highlight, weekly overview, smooth scrolling
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
$allDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
$dayLabels = ['monday' => 'Mon', 'tuesday' => 'Tue', 'wednesday' => 'Wed', 'thursday' => 'Thu', 'friday' => 'Fri', 'saturday' => 'Sat', 'sunday' => 'Sun'];
$dayColors = ['monday' => 'blue', 'tuesday' => 'green', 'wednesday' => 'purple', 'thursday' => 'orange', 'friday' => 'pink', 'saturday' => 'yellow', 'sunday' => 'gray'];

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

    foreach ($allDays as $day) {
        $daySlots = array_values(array_filter($rows, fn($r) => $r['day_of_week'] === $day));
        if (!empty($daySlots)) {
            $timetable[$day] = $daySlots;
        }
    }
}

$today = strtolower(date('l'));
$viewMode = $_GET['view'] ?? 'daily';
$selectedDay = $_GET['day'] ?? $today;
if (!in_array($selectedDay, $allDays)) $selectedDay = $today;

// Current time for highlighting active class
$nowTime = date('H:i:s');
$nowDay = $today;

portal_head('Timetable', portal_url('dashboard'));
?>

<?php if ($enrollment): ?>
<div class="flex items-center justify-between mb-4">
  <div class="flex items-center gap-2">
    <span class="badge badge-blue"><?= e($enrollment['class_name']) ?></span>
    <?php if ($enrollment['section_name']): ?>
    <span class="badge badge-gray"><?= e($enrollment['section_name']) ?></span>
    <?php endif; ?>
  </div>
  <!-- View mode toggle -->
  <div class="flex bg-gray-100 rounded-lg p-0.5">
    <a href="<?= portal_url('timetable', ['view' => 'daily', 'day' => $selectedDay]) ?>"
       class="px-3 py-1.5 text-xs font-semibold rounded-md transition-all
              <?= $viewMode === 'daily' ? 'bg-white shadow-sm text-primary-700' : 'text-gray-500' ?>">
      Daily
    </a>
    <a href="<?= portal_url('timetable', ['view' => 'weekly']) ?>"
       class="px-3 py-1.5 text-xs font-semibold rounded-md transition-all
              <?= $viewMode === 'weekly' ? 'bg-white shadow-sm text-primary-700' : 'text-gray-500' ?>">
      Weekly
    </a>
  </div>
</div>
<?php endif; ?>

<?php if (empty($timetable)): ?>
<div class="card text-center py-12 text-gray-400">
  <p class="text-4xl mb-3">📋</p>
  <p class="text-sm">No timetable available for your class.</p>
</div>
<?php elseif ($viewMode === 'daily'): ?>

<!-- ═══ DAILY VIEW ═══ -->

<!-- Day selector -->
<div class="flex gap-1.5 overflow-x-auto pb-3 mb-4 scrollbar-hide" id="day-tabs">
  <?php foreach (array_keys($timetable) as $day):
    $isToday = $day === $today;
    $isSelected = $day === $selectedDay;
    $slotCount = count($timetable[$day]);
  ?>
  <a href="<?= portal_url('timetable', ['view' => 'daily', 'day' => $day]) ?>"
     class="flex-shrink-0 flex flex-col items-center px-3.5 py-2 rounded-xl text-xs font-semibold border-2 transition-all min-w-[60px]
            <?= $isSelected
              ? 'bg-primary-600 text-white border-primary-600 shadow-md shadow-primary-200'
              : ($isToday
                ? 'bg-primary-50 text-primary-700 border-primary-200'
                : 'bg-white text-gray-600 border-gray-200 hover:border-primary-300') ?>">
    <span class="text-[10px] uppercase opacity-75"><?= $dayLabels[$day] ?></span>
    <span class="text-sm font-bold mt-0.5"><?= $slotCount ?></span>
    <?php if ($isToday && !$isSelected): ?>
    <span class="w-1.5 h-1.5 rounded-full bg-primary-500 mt-1"></span>
    <?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- Day header -->
<div class="flex items-center gap-2 mb-3">
  <h3 class="text-lg font-bold text-gray-900"><?= ucfirst($selectedDay) ?></h3>
  <?php if ($selectedDay === $today): ?>
  <span class="badge badge-blue">Today</span>
  <?php endif; ?>
  <?php if (isset($timetable[$selectedDay])): ?>
  <span class="text-xs text-gray-400"><?= count($timetable[$selectedDay]) ?> classes</span>
  <?php endif; ?>
</div>

<!-- Time slots -->
<?php if (isset($timetable[$selectedDay])): ?>
<div class="space-y-2 mb-5">
  <?php foreach ($timetable[$selectedDay] as $i => $slot):
    $isNow = ($selectedDay === $today && $nowTime >= $slot['start_time'] && $nowTime <= ($slot['end_time'] ?? '23:59:59'));
    $isPast = ($selectedDay === $today && $nowTime > ($slot['end_time'] ?? $slot['start_time']));
    $colorKey = $dayColors[$selectedDay] ?? 'blue';
  ?>
  <div class="card flex items-stretch gap-0 p-0 overflow-hidden transition-all animate-fade-in
              <?= $isNow ? 'ring-2 ring-primary-500 shadow-md' : ($isPast ? 'opacity-60' : '') ?>"
       style="animation-delay: <?= $i * 50 ?>ms">
    <!-- Time bar -->
    <div class="flex-shrink-0 w-20 flex flex-col items-center justify-center py-3
                <?= $isNow ? 'bg-primary-600 text-white' : 'bg-gray-50 text-gray-600' ?>">
      <p class="text-sm font-bold"><?= e(substr($slot['start_time'], 0, 5)) ?></p>
      <?php if ($slot['end_time']): ?>
      <p class="text-[10px] opacity-75"><?= e(substr($slot['end_time'], 0, 5)) ?></p>
      <?php endif; ?>
      <?php if ($isNow): ?>
      <span class="mt-1 text-[9px] font-bold uppercase tracking-wider bg-white/20 px-1.5 py-0.5 rounded">NOW</span>
      <?php endif; ?>
    </div>
    <!-- Content -->
    <div class="flex-1 px-4 py-3 flex items-center justify-between gap-3">
      <div class="min-w-0">
        <p class="text-sm font-bold text-gray-900"><?= e($slot['subject_name']) ?></p>
        <?php if ($slot['subject_code']): ?>
        <p class="text-xs text-gray-400"><?= e($slot['subject_code']) ?></p>
        <?php endif; ?>
        <?php if ($slot['teacher_name']): ?>
        <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          <?= e($slot['teacher_name']) ?>
        </p>
        <?php endif; ?>
      </div>
      <?php if ($slot['room']): ?>
      <div class="flex-shrink-0">
        <span class="badge badge-gray flex items-center gap-1">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
          <?= e($slot['room']) ?>
        </span>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card text-center py-10 text-gray-400">
  <p class="text-3xl mb-2">😌</p>
  <p class="text-sm">No classes scheduled for <?= ucfirst($selectedDay) ?>.</p>
</div>
<?php endif; ?>

<?php else: ?>

<!-- ═══ WEEKLY VIEW ═══ -->
<div class="space-y-4 mb-5">
  <?php foreach ($timetable as $day => $slots):
    $isToday = $day === $today;
  ?>
  <div class="animate-fade-in">
    <div class="flex items-center gap-2 mb-2">
      <p class="text-sm font-bold text-gray-900"><?= ucfirst($day) ?></p>
      <?php if ($isToday): ?>
      <span class="badge badge-blue text-[9px]">Today</span>
      <?php endif; ?>
      <span class="text-xs text-gray-400"><?= count($slots) ?> class<?= count($slots) > 1 ? 'es' : '' ?></span>
    </div>
    <div class="card p-0 overflow-hidden divide-y divide-gray-50">
      <?php foreach ($slots as $slot):
        $isNow = ($isToday && $nowTime >= $slot['start_time'] && $nowTime <= ($slot['end_time'] ?? '23:59:59'));
      ?>
      <div class="flex items-center gap-3 px-3 py-2.5 <?= $isNow ? 'bg-primary-50' : '' ?>">
        <div class="flex-shrink-0 text-center w-14">
          <p class="text-xs font-bold <?= $isNow ? 'text-primary-600' : 'text-gray-600' ?>"><?= e(substr($slot['start_time'], 0, 5)) ?></p>
          <?php if ($slot['end_time']): ?>
          <p class="text-[10px] text-gray-400"><?= e(substr($slot['end_time'], 0, 5)) ?></p>
          <?php endif; ?>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-gray-900 truncate"><?= e($slot['subject_name']) ?></p>
          <div class="flex items-center gap-2 mt-0.5">
            <?php if ($slot['teacher_name']): ?>
            <span class="text-[10px] text-gray-500 truncate"><?= e($slot['teacher_name']) ?></span>
            <?php endif; ?>
            <?php if ($slot['room']): ?>
            <span class="text-[10px] text-gray-400">· <?= e($slot['room']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($isNow): ?>
        <span class="flex-shrink-0 w-2 h-2 rounded-full bg-primary-600 animate-pulse"></span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php endif; ?>

<style>
@keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
.animate-fade-in { animation: fadeIn 0.3s ease-out both; }
.scrollbar-hide::-webkit-scrollbar { display:none; }
.scrollbar-hide { -ms-overflow-style:none; scrollbar-width:none; }
</style>

<?php portal_foot(); ?>
