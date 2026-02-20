<?php
/**
 * Academics — Promote Students
 * Supports: Promote entire class OR individual students.
 */

$sessions = db_fetch_all("SELECT id, name FROM academic_sessions ORDER BY start_date DESC");
$classes  = db_fetch_all("SELECT id, name FROM classes ORDER BY sort_order ASC");
$allSections = db_fetch_all("
    SELECT s.id, s.name, s.class_id, c.name AS class_name
    FROM sections s JOIN classes c ON c.id = s.class_id
    ORDER BY c.sort_order ASC, s.name ASC
");

$activeSession = get_active_session();

// Filters
$fromSession = input_int('from_session') ?: ($activeSession['id'] ?? 0);
$fromClass   = input_int('from_class');
$fromSection = input_int('from_section');

// Load students if class selected
$students = [];
if ($fromSession && $fromClass) {
    $secWhere = $fromSection ? "AND e.section_id = {$fromSection}" : '';
    $students = db_fetch_all("
        SELECT st.id, st.first_name, st.last_name, st.admission_no,
               e.section_id, sec.name AS section_name, e.roll_no
        FROM students st
        JOIN enrollments e ON e.student_id = st.id AND e.status = 'active'
        LEFT JOIN sections sec ON sec.id = e.section_id
        WHERE e.session_id = ? AND e.class_id = ? {$secWhere}
        ORDER BY st.first_name, st.last_name
    ", [$fromSession, $fromClass]);
}

ob_start();
?>

<div class="max-w-6xl mx-auto">

    <h1 class="text-xl font-bold text-gray-900 mb-6">Promote Students</h1>

    <!-- Step 1: Select Source -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Step 1: Select Source Class</h2>
        <form method="GET" action="" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <input type="hidden" name="route" value="academics/promote">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Session</label>
                <select name="from_session" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Session</option>
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $fromSession == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Class</label>
                <select name="from_class" id="promFromClass" onchange="filterPromSections(this.value, 'promFromSection')"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $fromClass == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Section</label>
                <select name="from_section" id="promFromSection"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">All Sections</option>
                    <?php foreach ($allSections as $sec): ?>
                        <option value="<?= $sec['id'] ?>" data-class="<?= $sec['class_id'] ?>"
                                <?= $fromSection == $sec['id'] ? 'selected' : '' ?>
                                style="<?= ($fromClass && $sec['class_id'] == $fromClass) || !$fromClass ? '' : 'display:none' ?>">
                            <?= e($sec['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                    Load Students
                </button>
            </div>
        </form>
    </div>

    <?php if ($fromSession && $fromClass && !empty($students)): ?>
    <!-- Step 2: Select Destination & Promote -->
    <form method="POST" action="<?= url('academics', 'promote-save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="from_session" value="<?= $fromSession ?>">
        <input type="hidden" name="from_class" value="<?= $fromClass ?>">
        <input type="hidden" name="from_section" value="<?= $fromSection ?>">

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Step 2: Select Destination</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Session <span class="text-red-500">*</span></label>
                    <select name="to_session" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">Select Session</option>
                        <?php foreach ($sessions as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Class <span class="text-red-500">*</span></label>
                    <select name="to_class" required id="promToClass" onchange="filterPromSections(this.value, 'promToSection')"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Section</label>
                    <select name="to_section" id="promToSection"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">Select Section</option>
                        <?php foreach ($allSections as $sec): ?>
                            <option value="<?= $sec['id'] ?>" data-class="<?= $sec['class_id'] ?>" style="display:none">
                                <?= e($sec['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Student List -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
            <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" id="selectAllStudents" onclick="toggleAll()" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="font-medium text-gray-700">Select All (<?= count($students) ?> students)</span>
                </label>
                <div class="text-xs text-gray-500">Set status per student or use bulk action below</div>
            </div>
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-10">
                            <span class="sr-only">Select</span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adm No</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Section</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($students as $st): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <input type="checkbox" name="student_ids[]" value="<?= $st['id'] ?>" class="student-cb rounded border-gray-300 text-primary-600 focus:ring-primary-500" checked>
                            </td>
                            <td class="px-4 py-2 text-sm font-medium text-gray-900"><?= e($st['first_name'] . ' ' . $st['last_name']) ?></td>
                            <td class="px-4 py-2 text-sm text-gray-600"><?= e($st['admission_no']) ?></td>
                            <td class="px-4 py-2 text-sm text-gray-600"><?= e($st['section_name'] ?? 'N/A') ?></td>
                            <td class="px-4 py-2">
                                <select name="status[<?= $st['id'] ?>]" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                    <option value="promoted">Promoted</option>
                                    <option value="repeated">Repeated</option>
                                    <option value="graduated">Graduated</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Promoting from the active enrollment to the destination class/session.
            </div>
            <button type="submit" onclick="return confirm('Are you sure you want to promote the selected students?')"
                    class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                Promote Selected Students
            </button>
        </div>
    </form>

    <?php elseif ($fromSession && $fromClass && empty($students)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
            No active enrollments found for the selected class/session.
        </div>
    <?php endif; ?>
</div>

<script>
function filterPromSections(classId, selectId) {
    const sel = document.getElementById(selectId);
    sel.value = '';
    Array.from(sel.options).forEach(opt => {
        if (!opt.value) return;
        opt.style.display = (opt.dataset.class === classId) ? '' : 'none';
    });
}
function toggleAll() {
    const checked = document.getElementById('selectAllStudents').checked;
    document.querySelectorAll('.student-cb').forEach(cb => cb.checked = checked);
}
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
