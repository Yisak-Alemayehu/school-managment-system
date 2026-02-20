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

ob_start();
?>

<div class="max-w-6xl mx-auto">

    <h1 class="text-xl font-bold text-gray-900 mb-6">Assign Subject Teachers</h1>

    <?php if (!$sessionId): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
            No active session. Please activate an academic session first.
        </div>
    <?php else: ?>

    <!-- Form -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4"><?= $editing ? 'Edit Assignment' : 'Assign Subject Teacher' ?></h2>
        <form method="POST" action="<?= url('academics', 'subject-teacher-save') ?>" class="grid grid-cols-1 sm:grid-cols-5 gap-4">
            <?= csrf_field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>
            <input type="hidden" name="session_id" value="<?= $sessionId ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teacher <span class="text-red-500">*</span></label>
                <select name="teacher_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Teacher</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= ($editing['teacher_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= e($t['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject <span class="text-red-500">*</span></label>
                <select name="subject_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Subject</option>
                    <?php foreach ($subjects as $sub): ?>
                        <option value="<?= $sub['id'] ?>" <?= ($editing['subject_id'] ?? '') == $sub['id'] ? 'selected' : '' ?>><?= e($sub['name']) ?> (<?= e($sub['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class <span class="text-red-500">*</span></label>
                <select name="class_id" required id="stClassSelect" onchange="filterSTSections(this.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($editing['class_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                <select name="section_id" id="stSectionSelect" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
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
                <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                    <?= $editing ? 'Update' : 'Assign' ?>
                </button>
                <?php if ($editing): ?>
                    <a href="<?= url('academics', 'subject-teachers') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teacher</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Section</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($assignments)): ?>
                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No subject teacher assignments for this session.</td></tr>
                <?php endif; ?>
                <?php foreach ($assignments as $a): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= e($a['teacher_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= e($a['subject_name']) ?> <span class="text-xs text-gray-400">(<?= e($a['subject_code']) ?>)</span></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= e($a['class_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= $a['section_name'] ? e($a['section_name']) : '<span class="text-gray-400">All</span>' ?></td>
                        <td class="px-4 py-3 text-right flex items-center justify-end gap-1">
                            <a href="<?= url('academics', 'subject-teachers') ?>&edit=<?= $a['id'] ?>" class="p-1.5 text-gray-400 hover:text-yellow-600 rounded" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form method="POST" action="<?= url('academics', 'subject-teacher-delete') ?>" class="inline" onsubmit="return confirm('Remove this assignment?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 rounded" title="Remove">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
</div>

<script>
function filterSTSections(classId) {
    const sel = document.getElementById('stSectionSelect');
    sel.value = '';
    Array.from(sel.options).forEach(opt => {
        if (!opt.value) return;
        opt.style.display = (opt.dataset.class === classId) ? '' : 'none';
    });
}
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
