<?php
/**
 * Portal — Subject Materials List
 * Displays all materials for a selected subject, grouped by book type.
 * Only shows materials for the student's enrolled class.
 */

if (!function_exists('format_file_size')) {
    function format_file_size(int $bytes): string {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}

$student   = portal_student();
$classId   = $student['class_id'] ?? null;
$subjectId = (int) ($_GET['subject_id'] ?? 0);

if (!$classId || !$subjectId) {
    set_flash('error', 'Invalid subject.');
    redirect(portal_url('materials'));
}

// Verify subject exists
$subject = db_fetch_one(
    "SELECT id, name FROM subjects WHERE id = ?",
    [$subjectId]
);
if (!$subject) {
    set_flash('error', 'Subject not found.');
    redirect(portal_url('materials'));
}

// Fetch materials for this class + subject
$materials = db_fetch_all(
    "SELECT m.id, m.title, m.book_type, m.cover_image, m.file_size, m.created_at
     FROM academic_materials m
     WHERE m.class_id = ? AND m.subject_id = ? AND m.deleted_at IS NULL AND m.status = 'active'
     ORDER BY m.book_type ASC, m.title ASC",
    [$classId, $subjectId]
);

// Group by book type
$grouped = [
    'student_book'   => [],
    'teachers_guide' => [],
    'supplementary'  => [],
];
foreach ($materials as $m) {
    $grouped[$m['book_type']][] = $m;
}

$typeLabels = [
    'student_book'   => "Student's Book",
    'teachers_guide' => "Teacher's Guide",
    'supplementary'  => 'Supplementary',
];
$typeIcons = [
    'student_book'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
    'teachers_guide' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 01-1.806-2.236l.735-4.408A2 2 0 016.956 7.04l2.387.477a6 6 0 003.86-.517l.318-.158a6 6 0 013.86-.517l1.932.386a2 2 0 011.806 2.236l-.735 4.408a2 2 0 01-.978 1.473z"/>',
    'supplementary'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
];
$typeColors = [
    'student_book'   => ['bg-blue-100', 'text-blue-600'],
    'teachers_guide' => ['bg-purple-100', 'text-purple-600'],
    'supplementary'  => ['bg-green-100', 'text-green-600'],
];

portal_head($subject['name'], portal_url('materials'));
?>

<!-- Subject header -->
<div class="mb-5 animate-slide-up">
    <h2 class="text-lg font-bold text-gray-900"><?= e($subject['name']) ?></h2>
    <p class="text-sm text-gray-500"><?= count($materials) ?> material<?= count($materials) !== 1 ? 's' : '' ?> available</p>
</div>

<?php if (empty($materials)): ?>
<div class="card text-center py-10 animate-slide-up" style="animation-delay: 50ms">
    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-100 flex items-center justify-center">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
        </svg>
    </div>
    <p class="text-sm font-semibold text-gray-700">No materials yet</p>
    <p class="text-xs text-gray-400 mt-1">Materials for this subject have not been uploaded yet.</p>
</div>
<?php else: ?>

<?php $delay = 0; foreach ($grouped as $type => $items):
    if (empty($items)) continue;
    $label = $typeLabels[$type];
    $icon  = $typeIcons[$type];
    $color = $typeColors[$type];
?>
<!-- <?= $label ?> -->
<div class="mb-5 animate-slide-up" style="animation-delay: <?= $delay ?>ms">
    <div class="flex items-center gap-2 mb-2.5">
        <div class="w-7 h-7 rounded-lg <?= $color[0] ?> <?= $color[1] ?> flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $icon ?></svg>
        </div>
        <p class="section-title mb-0"><?= e($label) ?></p>
        <span class="badge badge-gray ml-auto"><?= count($items) ?></span>
    </div>

    <div class="space-y-2">
        <?php foreach ($items as $mat): ?>
        <a href="<?= portal_url('materials-viewer', ['id' => $mat['id']]) ?>"
           class="card flex items-center gap-3 p-3 hover:shadow-card-hover transition-all">
            <!-- Cover thumbnail -->
            <?php if ($mat['cover_image']): ?>
            <img src="/uploads.php?file=<?= urlencode($mat['cover_image']) ?>"
                 alt="" class="w-12 h-16 rounded-lg object-cover flex-shrink-0 bg-gray-100">
            <?php else: ?>
            <div class="w-12 h-16 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <?php endif; ?>

            <!-- Info -->
            <div class="flex-1 min-w-0">
                <h4 class="text-sm font-bold text-gray-900 line-clamp-1"><?= e($mat['title']) ?></h4>
                <div class="flex items-center gap-2 mt-1 text-xs text-gray-400">
                    <span><?= format_file_size((int)$mat['file_size']) ?></span>
                    <span>·</span>
                    <span><?= date('M j, Y', strtotime($mat['created_at'])) ?></span>
                </div>
            </div>

            <!-- Arrow -->
            <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php $delay += 80; endforeach; ?>

<?php endif; ?>

<?php portal_foot(''); ?>
