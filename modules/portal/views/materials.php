<?php
/**
 * Portal — Academic Materials (My Subjects view)
 * Displays subjects grouped as a grid for the student's enrolled class.
 * Each card links to the materials for that subject.
 */

$student   = portal_student();
$classId   = $student['class_id'] ?? null;
$className = $student['class_name'] ?? 'N/A';

// Subject colors for visual variety
$subjectColors = [
    ['bg-blue-50',   'text-blue-600',   'bg-blue-100'],
    ['bg-purple-50', 'text-purple-600', 'bg-purple-100'],
    ['bg-green-50',  'text-green-600',  'bg-green-100'],
    ['bg-red-50',    'text-red-600',    'bg-red-100'],
    ['bg-yellow-50', 'text-yellow-600', 'bg-yellow-100'],
    ['bg-pink-50',   'text-pink-600',   'bg-pink-100'],
    ['bg-indigo-50', 'text-indigo-600', 'bg-indigo-100'],
    ['bg-teal-50',   'text-teal-600',   'bg-teal-100'],
    ['bg-orange-50', 'text-orange-600', 'bg-orange-100'],
    ['bg-cyan-50',   'text-cyan-600',   'bg-cyan-100'],
];

// Subject icons (first letter mapping or generic)
$subjectIcons = [
    'M' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
    'E' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>',
    'S' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 01-1.806-2.236l.735-4.408A2 2 0 016.956 7.04l2.387.477a6 6 0 003.86-.517l.318-.158a6 6 0 013.86-.517l1.932.386a2 2 0 011.806 2.236l-.735 4.408a2 2 0 01-.978 1.473z"/>',
    'default' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
];

// Fetch subjects that have at least one active material for this class
$subjects = [];
if ($classId) {
    $subjects = db_fetch_all(
        "SELECT s.id, s.name, COUNT(m.id) AS material_count
         FROM subjects s
         JOIN academic_materials m ON m.subject_id = s.id AND m.class_id = ? AND m.deleted_at IS NULL AND m.status = 'active'
         GROUP BY s.id, s.name
         ORDER BY s.name ASC",
        [$classId]
    );
}

portal_head('My Materials', portal_url('dashboard'));
?>

<!-- Class header -->
<div class="mb-5 animate-slide-up">
    <h2 class="text-lg font-bold text-gray-900">Academic Materials</h2>
    <p class="text-sm text-gray-500"><?= e($className) ?> — Select a subject to view materials</p>
</div>

<?php if (empty($subjects)): ?>
<!-- Empty state -->
<div class="card text-center py-10 animate-slide-up" style="animation-delay: 50ms">
    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-100 flex items-center justify-center">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
        </svg>
    </div>
    <p class="text-sm font-semibold text-gray-700">No Materials Available</p>
    <p class="text-xs text-gray-400 mt-1">Materials for your class have not been uploaded yet.</p>
</div>
<?php else: ?>
<!-- Subject grid -->
<div class="grid grid-cols-2 gap-3">
    <?php foreach ($subjects as $i => $subj):
        $colors = $subjectColors[$i % count($subjectColors)];
        $firstLetter = strtoupper(mb_substr($subj['name'], 0, 1));
        $iconPath = $subjectIcons[$firstLetter] ?? $subjectIcons['default'];
    ?>
    <a href="<?= portal_url('materials-subject', ['subject_id' => $subj['id']]) ?>"
       class="card p-4 hover:shadow-card-hover transition-all duration-200 animate-slide-up"
       style="animation-delay: <?= ($i * 50) ?>ms">
        <!-- Icon -->
        <div class="w-12 h-12 rounded-xl <?= $colors[2] ?> <?= $colors[1] ?> flex items-center justify-center mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <?= $iconPath ?>
            </svg>
        </div>
        <!-- Subject info -->
        <h3 class="text-sm font-bold text-gray-900 line-clamp-2 leading-tight"><?= e($subj['name']) ?></h3>
        <p class="text-xs text-gray-400 mt-1.5">
            <?= (int)$subj['material_count'] ?> material<?= (int)$subj['material_count'] !== 1 ? 's' : '' ?>
        </p>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php portal_foot(''); ?>
