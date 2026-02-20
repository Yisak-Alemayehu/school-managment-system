<?php
/**
 * Students — Generate ID Cards
 * Preview and print ID cards by class/section or individual student.
 */

$classes   = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");
$classId   = input_int('class_id');
$sectionId = input_int('section_id');
$studentId = input_int('student_id');

$sections = $classId
    ? db_fetch_all("SELECT id, name FROM sections WHERE class_id = ? AND is_active = 1 ORDER BY name", [$classId])
    : [];

$students = [];
if ($sectionId) {
    $students = db_fetch_all(
        "SELECT s.id, s.full_name, s.admission_no, s.gender, s.date_of_birth, s.photo, s.blood_group,
                c.name AS class_name, sec.name AS section_name, e.roll_no
           FROM students s
           JOIN enrollments e ON e.student_id = s.id
           JOIN sections sec ON e.section_id = sec.id
           JOIN classes c ON sec.class_id = c.id
          WHERE e.section_id = ? AND e.status = 'active' AND s.deleted_at IS NULL
          ORDER BY e.roll_no, s.full_name",
        [$sectionId]
    );
} elseif ($studentId) {
    $students = db_fetch_all(
        "SELECT s.id, s.full_name, s.admission_no, s.gender, s.date_of_birth, s.photo, s.blood_group,
                c.name AS class_name, sec.name AS section_name, e.roll_no
           FROM students s
           LEFT JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
           LEFT JOIN sections sec ON e.section_id = sec.id
           LEFT JOIN classes c ON sec.class_id = c.id
          WHERE s.id = ? AND s.deleted_at IS NULL",
        [$studentId]
    );
}

$school = get_school_name();

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">Generate ID Cards</h1>
        <?php if (!empty($students)): ?>
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print ID Cards
        </button>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?= url('students', 'id-cards') ?>" class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-36">
                <label class="block text-sm font-medium text-gray-700 mb-1">By Class</label>
                <select name="class_id" onchange="this.form.submit()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Class…</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $classId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($sections)): ?>
            <div class="flex-1 min-w-36">
                <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                <select name="section_id" onchange="this.form.submit()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">All Sections</option>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= $sec['id'] ?>" <?= $sectionId == $sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <span class="text-gray-400 text-sm">or</span>
            <div class="flex-1 min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Single Student (Admission No.)</label>
                <div class="flex gap-2">
                    <input type="number" name="student_id" value="<?= e($studentId ?: '') ?>" placeholder="Student ID…"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">Load</button>
                </div>
            </div>
        </div>
    </form>

    <!-- ID Cards Preview -->
    <?php if (empty($students) && ($sectionId || $studentId)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <p class="text-gray-400 text-sm">No students found.</p>
    </div>
    <?php elseif (!$sectionId && !$studentId): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2"/>
        </svg>
        <p class="text-gray-400 text-sm">Select a class/section or enter a student ID to preview ID cards.</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4" id="idCardGrid">
        <?php foreach ($students as $st): ?>
        <div class="id-card bg-white rounded-xl border-2 border-primary-600 overflow-hidden shadow-sm" style="width:220px">
            <!-- Header -->
            <div class="bg-primary-600 text-white text-center py-2 px-3">
                <p class="text-xs font-bold tracking-wide uppercase"><?= e($school) ?></p>
                <p class="text-xs opacity-80">Student ID Card</p>
            </div>
            <!-- Photo -->
            <div class="flex justify-center pt-4">
                <?php if ($st['photo']): ?>
                    <img src="/uploads/students/<?= e($st['photo']) ?>"
                         class="w-16 h-16 rounded-full object-cover border-2 border-primary-200">
                <?php else: ?>
                    <div class="w-16 h-16 rounded-full bg-primary-100 flex items-center justify-center text-2xl font-bold text-primary-700 border-2 border-primary-200">
                        <?= strtoupper(mb_substr($st['full_name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Info -->
            <div class="px-3 py-3 text-center space-y-1">
                <p class="font-bold text-gray-900 text-xs leading-tight"><?= e($st['full_name']) ?></p>
                <p class="text-xs text-gray-500"><?= e($st['class_name'] ?? '') ?><?= $st['section_name'] ? ' – ' . e($st['section_name']) : '' ?></p>
                <div class="mt-2 pt-2 border-t border-gray-100 text-left space-y-0.5">
                    <p class="text-xs text-gray-600"><span class="font-medium">Adm No:</span> <?= e($st['admission_no']) ?></p>
                    <?php if ($st['roll_no']): ?>
                    <p class="text-xs text-gray-600"><span class="font-medium">Roll:</span> <?= e($st['roll_no']) ?></p>
                    <?php endif; ?>
                    <?php if ($st['blood_group']): ?>
                    <p class="text-xs text-red-600"><span class="font-medium">Blood:</span> <?= e($st['blood_group']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bg-gray-50 text-center py-1.5 text-xs text-gray-400"><?= date('Y') ?>–<?= date('Y') + 1 ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
@media print {
    #sidebar, header, nav, form, .no-print { display: none !important; }
    .id-card { break-inside: avoid; border: 2px solid #1d4ed8 !important; }
    #idCardGrid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
}
</style>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
