<?php
/**
 * Students — Promotion View
 */

$classes  = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");
$sections = db_fetch_all("SELECT id, name, class_id FROM sections WHERE is_active = 1 ORDER BY name");
$sessions = db_fetch_all("SELECT id, name FROM academic_sessions ORDER BY start_date DESC LIMIT 5");

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('students') ?>" class="p-1 text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900">Student Promotion</h1>
    </div>

    <!-- Step 1: Select Source Class -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Select Students to Promote</h2>
        <form method="GET" action="<?= url('students', 'promote') ?>" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <input type="hidden" name="module" value="students">
            <input type="hidden" name="action" value="promote">
            <select name="from_session" required class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">From Session...</option>
                <?php foreach ($sessions as $sess): ?>
                    <option value="<?= $sess['id'] ?>" <?= input_int('from_session') == $sess['id'] ? 'selected' : '' ?>><?= e($sess['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="from_class" required class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">From Class...</option>
                <?php foreach ($classes as $cls): ?>
                    <option value="<?= $cls['id'] ?>" <?= input_int('from_class') == $cls['id'] ? 'selected' : '' ?>><?= e($cls['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="from_section" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">All Sections</option>
                <?php foreach ($sections as $sec): ?>
                    <option value="<?= $sec['id'] ?>" <?= input_int('from_section') == $sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?> (Class <?= $sec['class_id'] ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-900">Load Students</button>
        </form>
    </div>

    <?php
    $fromSession = input_int('from_session');
    $fromClass   = input_int('from_class');
    $fromSection = input_int('from_section');

    if ($fromSession && $fromClass):
        $where  = "e.session_id = ? AND sec.class_id = ? AND e.status = 'active' AND s.deleted_at IS NULL";
        $params = [$fromSession, $fromClass];
        if ($fromSection) {
            $where .= " AND e.section_id = ?";
            $params[] = $fromSection;
        }

        $studentsToPromote = db_fetch_all(
            "SELECT s.id, s.admission_no, s.full_name, s.gender, c.name as class_name, sec.name as section_name
             FROM students s
             JOIN enrollments e ON s.id = e.student_id
             JOIN sections sec ON e.section_id = sec.id
             JOIN classes c ON sec.class_id = c.id
             WHERE $where
             ORDER BY s.full_name",
            $params
        );
    ?>

    <!-- Step 2: Promote -->
    <form method="POST" action="<?= url('students', 'promote') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="from_session" value="<?= $fromSession ?>">

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Promote To</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <select name="to_session" required class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">To Session...</option>
                    <?php foreach ($sessions as $sess): ?>
                        <option value="<?= $sess['id'] ?>"><?= e($sess['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="to_class" required class="px-3 py-2 border border-gray-300 rounded-lg text-sm" onchange="loadToSections(this.value)">
                    <option value="">To Class...</option>
                    <?php foreach ($classes as $cls): ?>
                        <option value="<?= $cls['id'] ?>"><?= e($cls['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="to_section" id="to_section" required class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">To Section...</option>
                </select>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-4">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left"><input type="checkbox" onclick="toggleSelectAll(this, 'promote-cb')" class="rounded"></th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Class</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($studentsToPromote)): ?>
                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">No students found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($studentsToPromote as $sp): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><input type="checkbox" name="student_ids[]" value="<?= $sp['id'] ?>" class="promote-cb rounded"></td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-gray-900"><?= e($sp['full_name']) ?></p>
                                <p class="text-xs text-gray-500"><?= e($sp['admission_no']) ?></p>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700"><?= e($sp['class_name']) ?> <?= e($sp['section_name']) ?></td>
                            <td class="px-4 py-3">
                                <select name="promote_status[<?= $sp['id'] ?>]" class="px-2 py-1 border rounded text-xs">
                                    <option value="promoted">Promote</option>
                                    <option value="repeated">Repeat</option>
                                    <option value="graduated">Graduate</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition" onclick="return confirm('Promote selected students?')">
                Promote Selected
            </button>
        </div>
    </form>

    <script>
    var allSections = <?= json_encode($sections) ?>;
    function loadToSections(classId) {
        var select = document.getElementById('to_section');
        select.innerHTML = '<option value="">To Section...</option>';
        allSections.forEach(function(s) {
            if (s.class_id == classId) {
                var opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name;
                select.appendChild(opt);
            }
        });
    }
    </script>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Student Promotion';
require APP_ROOT . '/templates/layout.php';
