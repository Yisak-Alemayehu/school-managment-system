<?php
/**
 * Academics — Subject Teacher Assignment View
 * Assigns teachers to specific subjects in classes/sections.
 * Uses class_teachers table with is_class_teacher = 0.
 */

$classes  = db_fetch_all("SELECT id, name FROM classes ORDER BY sort_order ASC");
$sections = db_fetch_all("
    SELECT s.id, s.name, s.class_id, c.name AS class_name
    FROM sections s JOIN classes c ON c.id = s.class_id
    ORDER BY c.sort_order ASC, s.name ASC
");
$subjects = db_fetch_all("SELECT id, name, code FROM subjects WHERE is_active = 1 ORDER BY name ASC");
$teachers = db_fetch_all("
    SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS full_name
    FROM users u
    JOIN user_roles ur ON ur.user_id = u.id
    JOIN roles r ON r.id = ur.role_id
    WHERE r.slug = 'teacher' AND u.is_active = 1
    ORDER BY u.first_name, u.last_name
");

$activeSession = get_active_session();
$sessionId = $activeSession['id'] ?? 0;

// Fetch existing subject teacher assignments (not class teachers)
$assignments = db_fetch_all("
    SELECT ct.*,
           CONCAT(u.first_name, ' ', u.last_name) AS teacher_name,
           c.name AS class_name,
           sec.name AS section_name,
           sub.name AS subject_name,
           sub.code AS subject_code
    FROM class_teachers ct
    JOIN users u ON u.id = ct.teacher_id
    JOIN classes c ON c.id = ct.class_id
    LEFT JOIN sections sec ON sec.id = ct.section_id
    LEFT JOIN subjects sub ON sub.id = ct.subject_id
    WHERE ct.session_id = ? AND ct.is_class_teacher = 0 AND ct.subject_id IS NOT NULL
    ORDER BY c.sort_order ASC, sub.name ASC, u.first_name ASC
", [$sessionId]);

$editId  = input_int('edit');
$editing = $editId ? db_fetch_one("SELECT * FROM class_teachers WHERE id = ? AND is_class_teacher = 0", [$editId]) : null;
// Build assignment map for filtering: subject_id => [section_ids...]
$assignedSectionsBySubject = [];
foreach ($assignments as $a) {
    if ($editing && $a['id'] === $editing['id']) continue;
    if (!$a['subject_id'] || !$a['section_id']) continue;
    $assignedSectionsBySubject[$a['subject_id']][] = $a['section_id'];
}
ob_start();
?>

<div class="max-w-6xl mx-auto">

    <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text mb-6">Assign Subject Teachers</h1>

    <?php if (!$sessionId): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
            No active session. Please activate an academic session first.
        </div>
    <?php else: ?>

    <!-- Form -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4"><?= $editing ? 'Edit Assignment' : 'Assign Subject Teacher' ?></h2>

        <?php if ($editing): ?>
            <form method="POST" action="<?= url('academics', 'subject-teacher-save') ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $editing['id'] ?>">
                <input type="hidden" name="session_id" value="<?= $sessionId ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teacher <span class="text-red-500">*</span></label>
                    <select name="teacher_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        <option value="">Select Teacher</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($editing['teacher_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= e($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject <span class="text-red-500">*</span></label>
                    <select name="subject_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?= $sub['id'] ?>" <?= ($editing['subject_id'] ?? '') == $sub['id'] ? 'selected' : '' ?>><?= e($sub['name']) ?> (<?= e($sub['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Class <span class="text-red-500">*</span></label>
                    <select name="class_id" required id="stClassSelect" onchange="filterSTSections(this.value)"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($editing['class_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Section</label>
                    <select name="section_id" id="stSectionSelect" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        <option value="">All Sections</option>
                        <?php foreach ($sections as $s): ?>
                            <option value="<?= $s['id'] ?>" data-class="<?= $s['class_id'] ?>"
                                    <?= ($editing['section_id'] ?? '') == $s['id'] ? 'selected' : '' ?>
                                    style="<?= ($editing && $s['class_id'] == $editing['class_id']) || !$editing ? '' : 'display:none' ?>">
                                <?= e($s['class_name'] . ' - ' . $s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">Update</button>
                    <a href="<?= url('academics', 'subject-teachers') ?>" class="px-4 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text hover:bg-gray-50 dark:bg-dark-bg">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <form method="POST" action="<?= url('academics', 'subject-teacher-save') ?>" class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" value="<?= $sessionId ?>">

                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teacher <span class="text-red-500">*</span></label>
                    <select name="teacher_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        <option value="">Select Teacher</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= e($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subjects <span class="text-red-500">*</span></label>
                    <div class="max-h-56 overflow-y-auto rounded-lg border border-gray-200 dark:border-dark-border bg-white dark:bg-dark-card p-3" id="subjectContainer">
                        <?php foreach ($subjects as $sub): ?>
                            <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-dark-text mb-1">
                                <input type="checkbox" name="subject_ids[]" value="<?= $sub['id'] ?>" class="subject-checkbox form-checkbox h-4 w-4 text-primary-600 border-gray-300 dark:border-dark-border">
                                <?= e($sub['name']) ?> (<?= e($sub['code']) ?>)
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Check one or more subjects.</p>
                </div>

                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Classes &amp; Sections <span class="text-red-500">*</span></label>
                    <div class="max-h-56 overflow-y-auto rounded-lg border border-gray-200 dark:border-dark-border bg-white dark:bg-dark-card p-3" id="classSectionContainer">
                        <?php foreach ($classes as $c): ?>
                            <div class="mb-2">
                                <div class="text-xs font-semibold text-gray-600 dark:text-dark-muted mb-1"><?= e($c['name']) ?></div>
                                <div class="grid grid-cols-2 gap-2">
                                    <?php foreach ($sections as $s): ?>
                                        <?php if ($s['class_id'] !== $c['id']) continue; ?>
                                        <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-dark-text">
                                            <input type="checkbox" name="section_ids[]" value="<?= $s['id'] ?>" data-class="<?= $c['id'] ?>" data-section="<?= $s['id'] ?>" class="form-checkbox h-4 w-4 text-primary-600 border-gray-300 dark:border-dark-border">
                                            <?= e($s['name']) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Select class sections to assign the selected subjects to.</p>
                </div>

                <div class="lg:col-span-12 flex items-end justify-end gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">Assign</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?php
    // Build grouped teacher summaries for aggregation + expand/collapse details
    $teacherSummaries = [];
    foreach ($assignments as $a) {
        $teacherId = $a['teacher_id'];
        if (!isset($teacherSummaries[$teacherId])) {
            $teacherSummaries[$teacherId] = [
                'teacher_name' => $a['teacher_name'],
                'subjects'     => [],
                'classes'      => [],
                'sections'     => [],
                'assignments'  => [],
            ];
        }

        $teacherSummaries[$teacherId]['assignments'][] = $a;
        $teacherSummaries[$teacherId]['subjects'][$a['subject_id']] = $a['subject_name'] . ' (' . $a['subject_code'] . ')';
        $teacherSummaries[$teacherId]['classes'][$a['class_id']] = $a['class_name'];
        if ($a['section_id']) {
            $teacherSummaries[$teacherId]['sections'][$a['section_id']] = $a['section_name'];
        }
    }
    ?>

    <!-- List -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full responsive-table">
            <thead class="bg-gray-50 dark:bg-dark-bg border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Teacher</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Subjects Count</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Classes Count</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Sections Count</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                <?php if (empty($teacherSummaries)): ?>
                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-dark-muted">No subject teacher assignments for this session.</td></tr>
                <?php endif; ?>

                <?php foreach ($teacherSummaries as $teacherId => $teacher): ?>
                    <tr class="hover:bg-gray-50 dark:bg-dark-bg">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($teacher['teacher_name']) ?></td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-dark-muted"><?= count($teacher['subjects']) ?></td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-dark-muted"><?= count($teacher['classes']) ?></td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-dark-muted"><?= count($teacher['sections']) ?></td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" id="toggleBtn-<?= $teacherId ?>" onclick="toggleTeacherDetails(<?= $teacherId ?>)" class="px-3 py-1 text-xs font-medium rounded-lg border border-primary-700 text-primary-700 hover:bg-primary-700 hover:text-white transition">View More</button>
                        </td>
                    </tr>
                    <tr id="details-<?= $teacherId ?>" class="hidden bg-gray-50 dark:bg-dark-bg">
                        <td colspan="5" class="px-4 py-4">
                            <div class="rounded-lg border border-gray-200 dark:border-dark-border bg-white dark:bg-dark-card p-3">
                                <div class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Detailed assignments</div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead class="bg-gray-100 dark:bg-dark-bg border-b">
                                            <tr>
                                                <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Subject</th>
                                                <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Class</th>
                                                <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Section</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                                            <?php foreach ($teacher['assignments'] as $entry): ?>
                                                <tr class="hover:bg-gray-50 dark:bg-dark-bg">
                                                    <td class="px-2 py-1 text-sm text-gray-700 dark:text-gray-300"><?= e($entry['subject_name'] . ' (' . $entry['subject_code'] . ')') ?></td>
                                                    <td class="px-2 py-1 text-sm text-gray-700 dark:text-gray-300"><?= e($entry['class_name']) ?></td>
                                                    <td class="px-2 py-1 text-sm text-gray-700 dark:text-gray-300"><?= $entry['section_name'] ? e($entry['section_name']) : '<span class="text-gray-400 dark:text-dark-muted">All</span>' ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

            </tbody>
        </table>
      </div>
    </div>

    <?php endif; ?>
</div>

<script>
const assignedSectionsBySubject = <?= json_encode($assignedSectionsBySubject) ?>;
const subjectCheckboxes = Array.from(document.querySelectorAll('input[name="subject_ids[]"]'));
const sectionCheckboxes = Array.from(document.querySelectorAll('input[name="section_ids[]"]'));

function updateSectionAvailability() {
    const selectedSubjectIds = subjectCheckboxes.filter(cb => cb.checked).map(cb => cb.value);
    const blocked = new Set();
    selectedSubjectIds.forEach(subId => {
        const list = assignedSectionsBySubject[subId] || [];
        list.forEach(secId => blocked.add(String(secId)));
    });

    sectionCheckboxes.forEach(cb => {
        const row = cb.closest('label');
        const isBlocked = blocked.has(cb.value);
        cb.disabled = isBlocked;
        if (isBlocked) {
            cb.checked = false;
            if (row) {
                row.classList.add('opacity-40', 'cursor-not-allowed');
                row.title = 'This section is already assigned for the selected subject(s).';
            }
        } else if (row) {
            row.classList.remove('opacity-40', 'cursor-not-allowed');
            row.title = '';
        }
    });
}

function filterSTSections(classId) {
    const sectionSelect = document.getElementById('stSectionSelect');
    if (!sectionSelect) return;
    Array.from(sectionSelect.options).forEach(opt => {
        const optClass = opt.dataset.class;
        if (!optClass) return;
        opt.style.display = !classId || optClass === classId ? '' : 'none';
    });

    // If current selection is hidden, reset to default
    if (sectionSelect.value) {
        const selectedOpt = sectionSelect.selectedOptions[0];
        if (selectedOpt && selectedOpt.style.display === 'none') {
            sectionSelect.value = '';
        }
    }
}

subjectCheckboxes.forEach(cb => cb.addEventListener('change', updateSectionAvailability));
updateSectionAvailability();

function toggleTeacherDetails(teacherId) {
    const detailRow = document.getElementById('details-' + teacherId);
    const toggleBtn = document.getElementById('toggleBtn-' + teacherId);
    if (!detailRow || !toggleBtn) return;

    const isExpanded = !detailRow.classList.contains('hidden');
    if (isExpanded) {
        detailRow.classList.add('hidden');
        toggleBtn.textContent = 'View More';
    } else {
        detailRow.classList.remove('hidden');
        toggleBtn.textContent = 'View Less';
    }
}

// Ensure section select is filtered on load for edit form
const classSelect = document.getElementById('stClassSelect');
if (classSelect) {
    filterSTSections(classSelect.value);
    classSelect.addEventListener('change', () => filterSTSections(classSelect.value));
}
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';

