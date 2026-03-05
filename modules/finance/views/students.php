<?php
/**
 * Finance — Student List
 * List all students with filters/search. Student Code is clickable.
 */

$search    = input('search');
$classId   = input_int('class_id');
$sectionId = input_int('section_id');
$gender    = input('gender');
$page      = max(1, input_int('page') ?: 1);
$perPage   = 25;

$classes  = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");
$sections = $classId
    ? db_fetch_all("SELECT id, name FROM sections WHERE class_id = ? AND is_active = 1 ORDER BY name", [$classId])
    : [];

$where  = ["s.deleted_at IS NULL", "s.status = 'active'"];
$params = [];

if ($search) {
    $where[]  = "(s.full_name LIKE ? OR s.admission_no LIKE ? OR s.email LIKE ? OR s.phone LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($gender) { $where[] = "s.gender = ?"; $params[] = $gender; }

$joinEnrollment = "LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
                   LEFT JOIN classes c ON e.class_id = c.id
                   LEFT JOIN sections sec ON e.section_id = sec.id";
if ($classId)   { $where[] = "e.class_id = ?";   $params[] = $classId; }
if ($sectionId) { $where[] = "e.section_id = ?"; $params[] = $sectionId; }

$whereClause = implode(' AND ', $where);
$offset      = ($page - 1) * $perPage;

$total    = (int) db_fetch_value("SELECT COUNT(DISTINCT s.id) FROM students s $joinEnrollment WHERE $whereClause", $params);
$students = db_fetch_all(
    "SELECT DISTINCT s.id, s.full_name, s.first_name, s.last_name, s.admission_no, s.gender,
            s.date_of_birth, s.phone, s.email, s.photo, s.status,
            c.name AS class_name, sec.name AS section_name
       FROM students s $joinEnrollment
      WHERE $whereClause
      ORDER BY s.full_name
      LIMIT $perPage OFFSET $offset",
    $params
);

$lastPage = max(1, (int) ceil($total / $perPage));
$pagination = [
    'total' => $total, 'per_page' => $perPage, 'current_page' => $page,
    'last_page' => $lastPage, 'from' => $total > 0 ? $offset + 1 : 0,
    'to' => min($offset + $perPage, $total),
];

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">Finance — Manage Students</h1>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?= url('finance', 'students') ?>" class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex flex-wrap gap-3">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, student code, email, phone…"
                   class="flex-1 min-w-48 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">

            <select name="class_id" onchange="this.form.submit()"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                <option value="">All Classes</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $classId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <?php if (!empty($sections)): ?>
            <select name="section_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                <option value="">All Sections</option>
                <?php foreach ($sections as $sec): ?>
                    <option value="<?= $sec['id'] ?>" <?= $sectionId == $sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <select name="gender" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                <option value="">All Genders</option>
                <option value="male" <?= $gender === 'male' ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= $gender === 'female' ? 'selected' : '' ?>>Female</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">Search</button>
            <a href="<?= url('finance', 'students') ?>" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 font-medium">Clear</a>
        </div>
    </form>

    <!-- Student List -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 responsive-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Student Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Student Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Class</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Gender</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Phone</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Email</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($students)): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No students found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($students as $stu): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm" data-label="Student Code">
                            <a href="<?= url('finance', 'student-detail', $stu['id']) ?>"
                               class="text-primary-600 hover:text-primary-800 font-semibold underline">
                                <?= e($stu['admission_no']) ?>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm" data-label="Name">
                            <div class="flex items-center gap-2">
                                <?php if ($stu['photo']): ?>
                                    <img src="<?= upload_url($stu['photo']) ?>" class="w-7 h-7 rounded-full object-cover" alt="">
                                <?php else: ?>
                                    <div class="w-7 h-7 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-xs font-bold">
                                        <?= strtoupper(substr($stu['first_name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <?= e($stu['full_name']) ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600" data-label="Class"><?= e($stu['class_name'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600" data-label="Gender"><?= ucfirst(e($stu['gender'])) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600" data-label="Phone"><?= e($stu['phone'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600" data-label="Email"><?= e($stu['email'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= pagination_html($pagination, url('finance/students')) ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = $pageTitle;
include TEMPLATES_PATH . '/layout.php';
