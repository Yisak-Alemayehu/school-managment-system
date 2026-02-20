<?php
/**
 * Students — Detailed Records View
 * Full student info with class, section, guardian, and status columns.
 */

$search        = input('search');
$classId       = input_int('class_id');
$sectionId     = input_int('section_id');
$statusFilter  = input('status') ?: 'active';
$page          = max(1, input_int('page') ?: 1);
$perPage       = 25;

$classes  = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");
$sections = $classId
    ? db_fetch_all("SELECT id, name FROM sections WHERE class_id = ? AND is_active = 1 ORDER BY name", [$classId])
    : [];

$where  = ["s.deleted_at IS NULL"];
$params = [];

if ($search) {
    $where[]  = "(s.full_name LIKE ? OR s.admission_no LIKE ? OR s.phone LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($statusFilter) { $where[] = "s.status = ?"; $params[] = $statusFilter; }

$joinEnrollment = "LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
                   LEFT JOIN sections sec ON e.section_id = sec.id
                   LEFT JOIN classes c ON sec.class_id = c.id";
if ($classId)   { $where[] = "c.id = ?";   $params[] = $classId; }
if ($sectionId) { $where[] = "sec.id = ?"; $params[] = $sectionId; }

$whereClause = implode(' AND ', $where);
$offset      = ($page - 1) * $perPage;

$total    = db_fetch_value("SELECT COUNT(DISTINCT s.id) FROM students s $joinEnrollment WHERE $whereClause", $params);
$students = db_fetch_all(
    "SELECT DISTINCT s.id, s.full_name, s.admission_no, s.gender, s.date_of_birth,
            s.phone, s.status, s.photo, s.religion, s.blood_group,
            c.name AS class_name, sec.name AS section_name, e.roll_no,
            s.created_at
       FROM students s $joinEnrollment
      WHERE $whereClause
      ORDER BY s.full_name
      LIMIT $perPage OFFSET $offset",
    $params
);

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">Student Details</h1>
        <a href="<?= url('students', 'admission') ?>"
           class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Admission
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?= url('students', 'details') ?>" class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex flex-wrap gap-3">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, admission no, phone…"
                   class="flex-1 min-w-48 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            <select name="class_id" onchange="this.form.submit()"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                <option value="">All Classes</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $classId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($sections)): ?>
            <select name="section_id"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                <option value="">All Sections</option>
                <?php foreach ($sections as $sec): ?>
                    <option value="<?= $sec['id'] ?>" <?= $sectionId == $sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <select name="status"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                <option value="active"    <?= $statusFilter === 'active'    ? 'selected' : '' ?>>Active</option>
                <option value="inactive"  <?= $statusFilter === 'inactive'  ? 'selected' : '' ?>>Inactive</option>
                <option value="graduated" <?= $statusFilter === 'graduated' ? 'selected' : '' ?>>Graduated</option>
                <option value=""          <?= $statusFilter === ''          ? 'selected' : '' ?>>All</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">Search</button>
        </div>
    </form>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <?php if (empty($students)): ?>
        <div class="p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-gray-400 text-sm">No students found.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Photo</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Adm. No.</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Class</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Section</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Roll</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Gender</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">DOB</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Phone</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Blood</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($students as $st): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <?php if ($st['photo']): ?>
                                <img src="/uploads/students/<?= e($st['photo']) ?>" class="w-8 h-8 rounded-full object-cover">
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-xs font-bold text-primary-700">
                                    <?= strtoupper(mb_substr($st['full_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900"><?= e($st['full_name']) ?></td>
                        <td class="px-4 py-3 text-gray-600"><?= e($st['admission_no']) ?></td>
                        <td class="px-4 py-3 text-gray-600"><?= e($st['class_name'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-gray-600"><?= e($st['section_name'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-gray-600"><?= e($st['roll_no'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-gray-600 capitalize"><?= e($st['gender']) ?></td>
                        <td class="px-4 py-3 text-gray-600"><?= $st['date_of_birth'] ? e(date('d M Y', strtotime($st['date_of_birth']))) : '—' ?></td>
                        <td class="px-4 py-3 text-gray-600"><?= e($st['phone'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-gray-600"><?= e($st['blood_group'] ?? '—') ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 text-xs rounded-full font-medium
                                <?= $st['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                                <?= ucfirst($st['status']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="<?= url('students', 'view') ?>&id=<?= $st['id'] ?>"
                                   class="text-primary-600 hover:text-primary-800 text-xs font-medium">View</a>
                                <a href="<?= url('students', 'edit') ?>&id=<?= $st['id'] ?>"
                                   class="text-gray-500 hover:text-gray-700 text-xs font-medium">Edit</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <?php if ($total > $perPage): ?>
        <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
            <span>Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> of <?= $total ?></span>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
                       class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50">Previous</a>
                <?php endif; ?>
                <?php if ($offset + $perPage < $total): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
                       class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
