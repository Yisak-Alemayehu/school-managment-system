<?php
/**
 * Students — List View
 */

$search       = input('search');
$classFilter  = input_int('class_id');
$sectionFilter = input_int('section_id');
$statusFilter = input('status');
$page         = max(1, input_int('page') ?: 1);

$where  = ["s.deleted_at IS NULL"];
$params = [];

if ($search) {
    $where[]  = "(s.full_name LIKE ? OR s.admission_no LIKE ? OR s.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($statusFilter) {
    $where[]  = "s.status = ?";
    $params[] = $statusFilter;
} else {
    $where[]  = "s.status = 'active'";
}

$joinEnrollment = "";
if ($classFilter || $sectionFilter) {
    $joinEnrollment = "JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
                       JOIN sections sec ON e.section_id = sec.id
                       JOIN classes c ON sec.class_id = c.id";
    if ($classFilter) {
        $where[]  = "c.id = ?";
        $params[] = $classFilter;
    }
    if ($sectionFilter) {
        $where[]  = "sec.id = ?";
        $params[] = $sectionFilter;
    }
} else {
    $joinEnrollment = "LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
                       LEFT JOIN sections sec ON e.section_id = sec.id
                       LEFT JOIN classes c ON sec.class_id = c.id";
}

$whereClause = implode(' AND ', $where);

$totalStudents = db_fetch_value(
    "SELECT COUNT(DISTINCT s.id) FROM students s $joinEnrollment WHERE $whereClause",
    $params
);

$perPage    = ITEMS_PER_PAGE;
$totalPages = max(1, ceil($totalStudents / $perPage));
$offset     = ($page - 1) * $perPage;

$students = db_fetch_all(
    "SELECT s.id, s.admission_no, s.full_name, s.gender, s.date_of_birth, s.phone, s.status, s.photo,
            MAX(c.name) as class_name, MAX(sec.name) as section_name
     FROM students s
     $joinEnrollment
     WHERE $whereClause
     GROUP BY s.id, s.admission_no, s.full_name, s.gender, s.date_of_birth, s.phone, s.status, s.photo
     ORDER BY s.full_name ASC
     LIMIT $perPage OFFSET $offset",
    $params
);

$classes = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");
$sections = [];
if ($classFilter) {
    $sections = db_fetch_all("SELECT id, name FROM sections WHERE class_id = ? AND is_active = 1 ORDER BY name", [$classFilter]);
}

ob_start();
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900">Students</h1>
        <p class="text-sm text-gray-500"><?= number_format($totalStudents) ?> students found</p>
    </div>
    <div class="flex gap-2">
        <?php if (auth_has_permission('students.create')): ?>
            <a href="<?= url('students', 'create') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Admission
            </a>
        <?php endif; ?>
        <a href="<?= url('students', 'export') ?>&class_id=<?= $classFilter ?>&section_id=<?= $sectionFilter ?>" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export
        </a>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
    <form method="GET" action="<?= url('students') ?>" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
        <input type="hidden" name="module" value="students">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, admission no..."
               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
        <select name="class_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500" onchange="this.form.submit()">
            <option value="">All Classes</option>
            <?php foreach ($classes as $cls): ?>
                <option value="<?= $cls['id'] ?>" <?= $classFilter == $cls['id'] ? 'selected' : '' ?>><?= e($cls['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="section_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            <option value="">All Sections</option>
            <?php foreach ($sections as $sec): ?>
                <option value="<?= $sec['id'] ?>" <?= $sectionFilter == $sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            <option value="active" <?= $statusFilter === 'active' || !$statusFilter ? 'selected' : '' ?>>Active</option>
            <option value="graduated" <?= $statusFilter === 'graduated' ? 'selected' : '' ?>>Graduated</option>
            <option value="transferred" <?= $statusFilter === 'transferred' ? 'selected' : '' ?>>Transferred</option>
            <option value="withdrawn" <?= $statusFilter === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
            <option value="">All Status</option>
        </select>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-900">Filter</button>
            <a href="<?= url('students') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Clear</a>
        </div>
    </form>
</div>

<!-- Student Cards (Mobile) / Table (Desktop) -->
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full table-responsive">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admission #</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gender</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($students)): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400">No students found.</td></tr>
                <?php endif; ?>
                <?php foreach ($students as $s): ?>
                    <tr class="hover:bg-gray-50">
                        <td data-label="Student" class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-800 text-sm font-bold flex-shrink-0">
                                    <?= e(strtoupper(substr($s['full_name'], 0, 1))) ?>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900"><?= e($s['full_name']) ?></p>
                                    <?php if ($s['phone']): ?>
                                        <p class="text-xs text-gray-500"><?= e($s['phone']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td data-label="Admission #" class="px-4 py-3 text-sm text-gray-700"><?= e($s['admission_no']) ?></td>
                        <td data-label="Class" class="px-4 py-3 text-sm text-gray-700"><?= e(($s['class_name'] ?? 'N/A') . ' ' . ($s['section_name'] ?? '')) ?></td>
                        <td data-label="Gender" class="px-4 py-3 text-sm text-gray-700"><?= e(ucfirst($s['gender'])) ?></td>
                        <td data-label="Status" class="px-4 py-3">
                            <?php
                            $statusColors = ['active' => 'green', 'graduated' => 'blue', 'transferred' => 'yellow', 'withdrawn' => 'red'];
                            $color = $statusColors[$s['status']] ?? 'gray';
                            ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-<?= $color ?>-100 text-<?= $color ?>-800">
                                <?= e(ucfirst($s['status'])) ?>
                            </span>
                        </td>
                        <td data-label="Actions" class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= url('students', 'view', $s['id']) ?>" title="View" class="p-1.5 text-gray-400 hover:text-primary-600 rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <?php if (auth_has_permission('students.edit')): ?>
                                    <a href="<?= url('students', 'edit', $s['id']) ?>" title="Edit" class="p-1.5 text-gray-400 hover:text-yellow-600 rounded">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 border-t flex items-center justify-between">
            <p class="text-xs text-gray-500">Showing <?= ($offset + 1) ?>-<?= min($offset + $perPage, $totalStudents) ?> of <?= $totalStudents ?></p>
            <div class="flex gap-1">
                <?php if ($page > 1): ?>
                    <a href="<?= url('students') ?>&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&class_id=<?= $classFilter ?>&section_id=<?= $sectionFilter ?>&status=<?= urlencode($statusFilter) ?>"
                       class="px-3 py-1 border rounded text-xs hover:bg-gray-50">Prev</a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="<?= url('students') ?>&page=<?= $i ?>&search=<?= urlencode($search) ?>&class_id=<?= $classFilter ?>&section_id=<?= $sectionFilter ?>&status=<?= urlencode($statusFilter) ?>"
                       class="px-3 py-1 border rounded text-xs <?= $i === $page ? 'bg-primary-800 text-white border-primary-800' : 'hover:bg-gray-50' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="<?= url('students') ?>&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&class_id=<?= $classFilter ?>&section_id=<?= $sectionFilter ?>&status=<?= urlencode($statusFilter) ?>"
                       class="px-3 py-1 border rounded text-xs hover:bg-gray-50">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Students';
require APP_ROOT . '/templates/layout.php';
