<?php
/**
 * Academics — My Timetable (Student/Teacher personal weekly view)
 * Shows timetable scoped to the logged-in user's class (student) or assignments (teacher)
 */

$activeSession = get_active_session();
$sessionId = $activeSession['id'] ?? 0;
$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
$dayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
$periodColors = ['bg-blue-500', 'bg-purple-500', 'bg-amber-500', 'bg-rose-500', 'bg-cyan-500', 'bg-indigo-500', 'bg-emerald-500', 'bg-primary-500'];

$entries = [];
$subtitle = '';

if (auth_has_role('student')) {
    $student = rbac_get_student();
    if ($student && !empty($student['section_id'])) {
        $entries = db_fetch_all("
            SELECT t.*, sub.name AS subject_name, sub.code AS subject_code,
                   CONCAT(u.first_name, ' ', u.last_name) AS teacher_name
            FROM timetables t
            JOIN subjects sub ON sub.id = t.subject_id
            LEFT JOIN users u ON u.id = t.teacher_id
            WHERE t.section_id = ? AND t.session_id = ?
            ORDER BY FIELD(t.day_of_week, 'monday','tuesday','wednesday','thursday','friday','saturday'), t.start_time ASC
        ", [$student['section_id'], $sessionId]);
        $subtitle = ($student['class_name'] ?? '') . ' — ' . ($student['section_name'] ?? '');
    }
} elseif (auth_has_role('teacher')) {
    $user = auth_user();
    $entries = db_fetch_all("
        SELECT t.*, sub.name AS subject_name, sub.code AS subject_code,
               c.name AS class_name, sec.name AS section_name,
               CONCAT(u.first_name, ' ', u.last_name) AS teacher_name
        FROM timetables t
        JOIN subjects sub ON sub.id = t.subject_id
        JOIN sections sec ON sec.id = t.section_id
        JOIN classes c ON c.id = sec.class_id
        LEFT JOIN users u ON u.id = t.teacher_id
        WHERE t.teacher_id = ? AND t.session_id = ?
        ORDER BY FIELD(t.day_of_week, 'monday','tuesday','wednesday','thursday','friday','saturday'), t.start_time ASC
    ", [$user['id'], $sessionId]);
    $subtitle = 'Teaching Schedule';
}

// Group by day
$grid = [];
foreach ($days as $d) $grid[$d] = [];
foreach ($entries as $e) {
    $grid[$e['day_of_week']][] = $e;
}

$todayDay = strtolower(date('l'));

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('dashboard') ?>" class="p-2 hover:bg-gray-100 dark:hover:bg-dark-card2 rounded-lg">
            <svg class="w-5 h-5 text-gray-500 dark:text-dark-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">My Timetable</h1>
            <?php if ($subtitle): ?>
                <p class="text-sm text-gray-500 dark:text-dark-muted"><?= e($subtitle) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($entries)): ?>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-8 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-gray-500 dark:text-dark-muted">No timetable entries found.</p>
        </div>
    <?php else: ?>
        <!-- Day tabs (mobile-friendly) -->
        <div class="flex gap-2 overflow-x-auto pb-2 mb-4 scrollbar-hide">
            <?php foreach ($days as $di => $d): ?>
                <?php $hasEntries = !empty($grid[$d]); $isToday = ($d === $todayDay); ?>
                <button 
                    onclick="document.getElementById('day-<?= $d ?>').scrollIntoView({behavior:'smooth',block:'start'})"
                    class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition
                    <?= $isToday ? 'bg-primary-600 text-white' : ($hasEntries ? 'bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300' : 'bg-gray-50 dark:bg-dark-bg text-gray-400 dark:text-gray-500') ?>">
                    <?= $dayLabels[$di] ?>
                    <?php if ($hasEntries): ?>
                        <span class="ml-1 text-xs opacity-70"><?= count($grid[$d]) ?></span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Daily schedules -->
        <div class="space-y-6">
            <?php foreach ($days as $di => $day):
                if (empty($grid[$day])) continue;
                $isToday = ($day === $todayDay);
            ?>
            <div id="day-<?= $day ?>">
                <h2 class="text-sm font-semibold mb-2 flex items-center gap-2 <?= $isToday ? 'text-primary-700 dark:text-primary-400' : 'text-gray-600 dark:text-gray-400' ?>">
                    <?= $dayLabels[$di] ?>day
                    <?php if ($isToday): ?>
                        <span class="text-xs px-2 py-0.5 bg-primary-100 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 rounded-full">Today</span>
                    <?php endif; ?>
                </h2>
                <div class="space-y-2">
                    <?php foreach ($grid[$day] as $pi => $period):
                        $color = $periodColors[$pi % count($periodColors)];
                    ?>
                    <div class="bg-white dark:bg-dark-card rounded-xl border <?= $isToday ? 'border-primary-200 dark:border-primary-800' : 'border-gray-100 dark:border-dark-border' ?> p-4 flex items-center gap-3">
                        <div class="w-1.5 h-12 <?= $color ?> rounded-full flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text"><?= e($period['subject_name']) ?></p>
                            <p class="text-xs text-gray-500 dark:text-dark-muted">
                                <?php if (auth_has_role('teacher')): ?>
                                    <?= e($period['class_name'] ?? '') ?> — <?= e($period['section_name'] ?? '') ?>
                                <?php else: ?>
                                    <?= e($period['teacher_name'] ?? 'TBD') ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300"><?= date('g:i A', strtotime($period['start_time'])) ?></p>
                            <p class="text-xs text-gray-400 dark:text-dark-muted"><?= date('g:i A', strtotime($period['end_time'])) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
