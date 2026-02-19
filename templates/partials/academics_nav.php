<?php
/**
 * Academics — Sub-Navigation Partial
 * Include at the top of every academics view.
 */
$currentAction = current_action();

$navItems = [
    // Setup
    ['action' => 'sessions',    'label' => 'Sessions'],
    ['action' => 'terms',       'label' => 'Terms'],
    ['action' => 'mediums',     'label' => 'Mediums'],
    ['action' => 'streams',     'label' => 'Streams'],
    ['action' => 'shifts',      'label' => 'Shifts'],
    // Structure
    ['action' => 'classes',     'label' => 'Classes'],
    ['action' => 'sections',    'label' => 'Sections'],
    ['action' => 'subjects',    'label' => 'Subjects'],
    // Assignments
    ['action' => 'class-subjects',    'label' => 'Class Subjects'],
    ['action' => 'elective-subjects', 'label' => 'Elective Subjects'],
    ['action' => 'class-teachers',    'label' => 'Class Teachers'],
    ['action' => 'subject-teachers',  'label' => 'Subject Teachers'],
    // Promotion
    ['action' => 'promote',     'label' => 'Promote Students'],
    // Timetable
    ['action' => 'timetable',   'label' => 'Timetable'],
];

$groups = [
    0  => 'Setup',       // sessions
    5  => 'Structure',   // classes
    8  => 'Assignments', // class-subjects
    12 => 'Actions',     // promote
    13 => 'Schedule',    // timetable
];
?>

<div class="mb-6 bg-white rounded-xl border border-gray-200 p-2 overflow-x-auto">
    <nav class="flex flex-wrap gap-1 min-w-max" aria-label="Academics Navigation">
        <?php foreach ($navItems as $i => $item):
            $isActive = ($currentAction === $item['action']);
            if (isset($groups[$i])): ?>
                <?php if ($i > 0): ?><span class="hidden sm:block border-l border-gray-200 mx-1"></span><?php endif; ?>
            <?php endif;
            $cls = $isActive
                ? 'bg-primary-100 text-primary-800 font-semibold border-primary-300'
                : 'text-gray-600 hover:bg-gray-100 hover:text-gray-800 border-transparent';
        ?>
            <a href="<?= url('academics', $item['action']) ?>"
               class="whitespace-nowrap px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors <?= $cls ?>">
                <?= $item['label'] ?>
            </a>
        <?php endforeach; ?>
    </nav>
</div>
