<?php
/**
 * Academic Materials — View Single Material
 */

$material = db_fetch_one(
    "SELECT m.*, c.name AS class_name, s.name AS subject_name, u.full_name AS uploaded_by_name
     FROM academic_materials m
     JOIN classes c ON c.id = m.class_id
     JOIN subjects s ON s.id = m.subject_id
     LEFT JOIN users u ON u.id = m.uploaded_by
     WHERE m.id = ? AND m.deleted_at IS NULL",
    [$id]
);
if (!$material) {
    set_flash('error', 'Material not found.');
    redirect(url('materials'));
}

$bookTypeLabels = [
    'teachers_guide' => "Teacher's Guide",
    'student_book'   => 'Student Book',
    'supplementary'  => 'Supplementary Book',
];

$bookTypeBadge = [
    'teachers_guide' => 'bg-purple-100 text-purple-700',
    'student_book'   => 'bg-blue-100 text-blue-700',
    'supplementary'  => 'bg-green-100 text-green-700',
];

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="<?= url('materials') ?>" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text"><?= e($material['title']) ?></h1>
                <p class="text-sm text-gray-500"><?= e($material['class_name']) ?> · <?= e($material['subject_name']) ?></p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('materials', 'pdf-viewer', $id) ?>"
               class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900 transition-colors flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Open Reader
            </a>
            <a href="<?= url('materials', 'serve', $id) ?>?mode=download"
               class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-1.5 dark:border-dark-border dark:text-gray-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download
            </a>
            <?php if (auth_has_permission('materials.edit')): ?>
            <a href="<?= url('materials', 'edit', $id) ?>"
               class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-dark-border dark:text-gray-300">
                Edit
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($msg = get_flash('success')): ?>
        <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg border border-green-200 text-sm"><?= e($msg) ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Cover Image -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
                <?php if ($material['cover_image']): ?>
                    <img src="<?= upload_url($material['cover_image']) ?>" alt="<?= e($material['title']) ?>"
                         class="w-full aspect-[3/4] object-cover">
                <?php else: ?>
                    <div class="w-full aspect-[3/4] bg-gray-100 flex items-center justify-center text-gray-300">
                        <svg class="w-20 h-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Details -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4 pb-2 border-b border-gray-100 dark:border-dark-border">
                    Material Details
                </h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Title</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-dark-text font-medium"><?= e($material['title']) ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Book Type</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= $bookTypeBadge[$material['book_type']] ?? 'bg-gray-100 text-gray-700' ?>">
                                <?= e($bookTypeLabels[$material['book_type']] ?? $material['book_type']) ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-dark-text"><?= e($material['class_name']) ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-dark-text"><?= e($material['subject_name']) ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">File Size</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-dark-text">
                            <?= $material['file_size'] ? round($material['file_size'] / 1048576, 1) . ' MB' : 'Unknown' ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded By</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-dark-text"><?= e($material['uploaded_by_name'] ?? 'Unknown') ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded On</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-dark-text"><?= date('M j, Y \a\t g:i A', strtotime($material['created_at'])) ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold
                                <?= $material['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                                <?= ucfirst($material['status']) ?>
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>

            <?php if (auth_has_permission('materials.delete')): ?>
            <div class="bg-white dark:bg-dark-card rounded-xl border border-red-200 dark:border-red-900/30 p-6">
                <h2 class="text-sm font-semibold text-red-700 dark:text-red-400 mb-2">Danger Zone</h2>
                <p class="text-xs text-gray-500 mb-3">Deleting this material will remove it from all student portals.</p>
                <form method="POST" action="<?= url('materials', 'delete', $id) ?>"
                      onsubmit="return confirm('Are you sure you want to delete this material?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors">
                        Delete Material
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
?>
