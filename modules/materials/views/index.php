<?php
/**
 * Academic Materials — Index View (List)
 */

$search      = input('search');
$classFilter = input_int('class_id');
$subjectFilter = input_int('subject_id');
$typeFilter  = input('book_type');
$page        = max(1, input_int('page') ?: 1);

// ── Build query ──────────────────────────────────────────────────────────────
$where  = ['m.deleted_at IS NULL'];
$params = [];

if ($search) {
    $where[]  = "(m.title LIKE ?)";
    $params[] = "%{$search}%";
}
if ($classFilter) {
    $where[]  = "m.class_id = ?";
    $params[] = $classFilter;
}
if ($subjectFilter) {
    $where[]  = "m.subject_id = ?";
    $params[] = $subjectFilter;
}
if ($typeFilter && in_array($typeFilter, ['teachers_guide', 'student_book', 'supplementary'], true)) {
    $where[]  = "m.book_type = ?";
    $params[] = $typeFilter;
}

$whereClause = implode(' AND ', $where);

$total = (int) db_fetch_value(
    "SELECT COUNT(*) FROM academic_materials m WHERE {$whereClause}",
    $params
);

$perPage    = 15;
$totalPages = max(1, ceil($total / $perPage));
$offset     = ($page - 1) * $perPage;

$materials = db_fetch_all(
    "SELECT m.id, m.title, m.book_type, m.cover_image, m.file_size, m.status, m.created_at,
            c.name AS class_name, s.name AS subject_name, u.full_name AS uploaded_by_name
     FROM academic_materials m
     JOIN classes c ON c.id = m.class_id
     JOIN subjects s ON s.id = m.subject_id
     LEFT JOIN users u ON u.id = m.uploaded_by
     WHERE {$whereClause}
     ORDER BY c.numeric_name ASC, s.name ASC, m.book_type ASC, m.title ASC
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$classes  = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY numeric_name ASC, name ASC");
$subjects = db_fetch_all("SELECT id, name FROM subjects WHERE is_active = 1 ORDER BY name ASC");

$bookTypeLabels = [
    'teachers_guide' => "Teacher's Guide",
    'student_book'   => 'Student Book',
    'supplementary'  => 'Supplementary Book',
];

$bookTypeBadge = [
    'teachers_guide' => 'badge-purple',
    'student_book'   => 'badge-blue',
    'supplementary'  => 'badge-green',
];

ob_start();
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Academic Materials</h1>
        <p class="text-sm text-gray-500"><?= number_format($total) ?> materials found</p>
    </div>
    <div class="flex gap-2">
        <?php if (auth_has_permission('materials.create')): ?>
            <a href="<?= url('materials', 'create') ?>"
               class="px-4 py-2 bg-primary-800 text-white rounded-lg hover:bg-primary-900 transition-colors text-sm font-medium flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Upload Material
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 mb-4">
    <form method="GET" action="<?= url('materials') ?>" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by title..."
               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-dark-bg dark:border-dark-border dark:text-dark-text">

        <select name="class_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm dark:bg-dark-bg dark:border-dark-border dark:text-dark-text">
            <option value="">All Grades</option>
            <?php foreach ($classes as $cls): ?>
                <option value="<?= $cls['id'] ?>" <?= $classFilter == $cls['id'] ? 'selected' : '' ?>>
                    <?= e($cls['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="subject_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm dark:bg-dark-bg dark:border-dark-border dark:text-dark-text">
            <option value="">All Subjects</option>
            <?php foreach ($subjects as $sub): ?>
                <option value="<?= $sub['id'] ?>" <?= $subjectFilter == $sub['id'] ? 'selected' : '' ?>>
                    <?= e($sub['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="book_type" class="px-3 py-2 border border-gray-300 rounded-lg text-sm dark:bg-dark-bg dark:border-dark-border dark:text-dark-text">
            <option value="">All Types</option>
            <option value="teachers_guide" <?= $typeFilter === 'teachers_guide' ? 'selected' : '' ?>>Teacher's Guide</option>
            <option value="student_book" <?= $typeFilter === 'student_book' ? 'selected' : '' ?>>Student Book</option>
            <option value="supplementary" <?= $typeFilter === 'supplementary' ? 'selected' : '' ?>>Supplementary Book</option>
        </select>

        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-900 transition-colors">
            Filter
        </button>
    </form>
</div>

<!-- Materials Grid -->
<?php if (empty($materials)): ?>
<div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-12 text-center">
    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
    </svg>
    <p class="text-gray-500 text-sm">No materials found. Upload your first material to get started.</p>
</div>
<?php else: ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php foreach ($materials as $mat): ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden hover:shadow-md transition-shadow group">
        <!-- Cover Image -->
        <div class="aspect-[3/4] bg-gray-100 relative overflow-hidden">
            <?php if ($mat['cover_image']): ?>
                <img src="<?= upload_url($mat['cover_image']) ?>" alt="<?= e($mat['title']) ?>"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-gray-300">
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
            <?php endif; ?>
            <!-- Badge -->
            <span class="absolute top-2 right-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold
                         <?= $bookTypeBadge[$mat['book_type']] ?? 'badge-gray' ?>"
                  style="backdrop-filter: blur(4px);">
                <?= e($bookTypeLabels[$mat['book_type']] ?? $mat['book_type']) ?>
            </span>
        </div>
        <!-- Info -->
        <div class="p-3">
            <h3 class="text-sm font-bold text-gray-900 dark:text-dark-text truncate"><?= e($mat['title']) ?></h3>
            <p class="text-xs text-gray-500 mt-0.5"><?= e($mat['class_name']) ?> · <?= e($mat['subject_name']) ?></p>
            <p class="text-xs text-gray-400 mt-1">
                <?= $mat['file_size'] ? round($mat['file_size'] / 1048576, 1) . ' MB' : '' ?>
                · <?= date('M j, Y', strtotime($mat['created_at'])) ?>
            </p>
            <!-- Actions -->
            <div class="flex items-center gap-1.5 mt-3 pt-2 border-t border-gray-100 dark:border-dark-border">
                <a href="<?= url('materials', 'pdf-viewer', $mat['id']) ?>"
                   class="flex-1 text-center px-2 py-1.5 text-xs font-medium text-primary-600 bg-primary-50 rounded-lg hover:bg-primary-100 transition-colors"
                   title="View PDF">
                    View
                </a>
                <a href="<?= url('materials', 'serve', $mat['id']) ?>?mode=download"
                   class="flex-1 text-center px-2 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                   title="Download">
                    Download
                </a>
                <?php if (auth_has_permission('materials.edit')): ?>
                <a href="<?= url('materials', 'edit', $mat['id']) ?>"
                   class="px-2 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php
$baseUrl = url('materials') . '?' . http_build_query(array_filter([
    'search'     => $search,
    'class_id'   => $classFilter,
    'subject_id' => $subjectFilter,
    'book_type'  => $typeFilter,
]));
echo pagination_html(['current_page' => $page, 'last_page' => $totalPages], $baseUrl);
?>
<?php endif; ?>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
?>
