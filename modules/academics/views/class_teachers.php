<?php
/**
 * Academics — Class Teacher Assignment View (Fixed)
 * Uses CONCAT(first_name, last_name), is_class_teacher=1, sub-nav.
 */

$classes  = db_fetch_all("SELECT id, name FROM classes ORDER BY sort_order ASC");
$sections = db_fetch_all("SELECT id, class_id, name FROM sections WHERE is_active = 1 ORDER BY name ASC");
$sessions = db_fetch_all("SELECT id, name FROM academic_sessions ORDER BY start_date DESC");

// Teachers: users with teacher role via user_roles pivot
$teachers = db_fetch_all(
    "SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS full_name
     FROM users u
     JOIN user_roles ur ON ur.user_id = u.id
     JOIN roles r ON r.id = ur.role_id
     WHERE r.slug = 'teacher' AND u.is_active = 1
     ORDER BY u.first_name, u.last_name"
);

$activeSession = get_active_session();
$filterSession = input_int('session_id') ?: ($activeSession['id'] ?? 0);

// Current class-teacher assignments (is_class_teacher = 1) for active session
$assignments = [];
if ($filterSession) {
    $assignments = db_fetch_all(
        "SELECT ct.id, ct.class_id, ct.section_id, ct.teacher_id,
                c.name AS class_name, s.name AS section_name,
                CONCAT(u.first_name, ' ', u.last_name) AS teacher_name
         FROM class_teachers ct
         JOIN classes c ON ct.class_id = c.id
         JOIN sections s ON ct.section_id = s.id
         JOIN users u ON ct.teacher_id = u.id
         WHERE ct.is_class_teacher = 1 AND ct.session_id = ?
         ORDER BY c.sort_order, s.name",
        [$filterSession]
    );
}

// Build section lookup by class_id for JS
$sectionsByClass = [];
foreach ($sections as $sec) {
    $sectionsByClass[$sec['class_id']][] = ['id' => $sec['id'], 'name' => $sec['name']];
}

ob_start();
?>

<div class="max-w-5xl mx-auto">

    <h1 class="text-xl font-bold text-gray-900 mb-6">Assign Class Teachers</h1>

    <!-- Session selector -->
    <div class="flex flex-wrap items-end gap-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Session</label>
            <select onchange="window.location='<?= url('academics', 'class-teachers') ?>&session_id='+this.value"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <?php foreach ($sessions as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $filterSession == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Add form -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4"><?= 'Assign Class Teacher' ?></h2>
        <form method="POST" action="<?= url('academics', 'class-teacher-save') ?>" class="flex flex-wrap items-end gap-4">
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" value="<?= $filterSession ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class <span class="text-red-500">*</span></label>
                <select name="class_id" id="ctClass" required onchange="filterCTSections(this.value)"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm min-w-[150px]">
                    <option value="">-- Select --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Section <span class="text-red-500">*</span></label>
                <select name="section_id" id="ctSection" required class="px-3 py-2 border border-gray-300 rounded-lg text-sm min-w-[120px]">
                    <option value="">-- Select Class First --</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teacher <span class="text-red-500">*</span></label>
                <select name="teacher_id" required class="px-3 py-2 border border-gray-300 rounded-lg text-sm min-w-[200px]">
                    <option value="">-- Select --</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= e($t['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                Assign
            </button>
        </form>
    </div>

    <!-- Current assignments -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b">
            <h2 class="text-sm font-semibold text-gray-900">Current Class Teacher Assignments</h2>
        </div>
        <?php if (empty($assignments)): ?>
            <div class="px-4 py-8 text-center text-gray-500 text-sm">No class teachers assigned for this session yet.</div>
        <?php else: ?>
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Section</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teacher</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($assignments as $a): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900"><?= e($a['class_name']) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-700"><?= e($a['section_name']) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-700"><?= e($a['teacher_name']) ?></td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="<?= url('academics', 'class-teacher-save') ?>&delete=<?= $a['id'] ?>" class="inline"
                                      onsubmit="return confirm('Remove this assignment?')">
                                    <?= csrf_field() ?>
                                    <button class="text-red-600 hover:text-red-800 text-xs font-medium">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
const ctSections = <?= json_encode($sectionsByClass) ?>;
function filterCTSections(classId) {
    const sel = document.getElementById('ctSection');
    sel.innerHTML = '<option value="">-- Select --</option>';
    if (ctSections[classId]) {
        ctSections[classId].forEach(s => {
            sel.innerHTML += '<option value="' + s.id + '">' + s.name + '</option>';
        });
    }
}
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
