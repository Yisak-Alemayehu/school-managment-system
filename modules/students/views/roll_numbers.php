<?php
/**
 * Students — Assign Roll Numbers
 * Shows enrolled students per class/section and lets admin enter roll numbers.
 */

$classes  = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");
$classId  = input_int('class_id');
$sectionId = input_int('section_id');

$sections = $classId
    ? db_fetch_all("SELECT id, name FROM sections WHERE class_id = ? AND is_active = 1 ORDER BY name", [$classId])
    : [];

$students = [];
if ($sectionId) {
    $students = db_fetch_all(
        "SELECT s.id, s.full_name, s.admission_no, e.roll_no
           FROM students s
           JOIN enrollments e ON e.student_id = s.id
          WHERE e.section_id = ? AND e.status = 'active' AND s.deleted_at IS NULL
          ORDER BY s.full_name",
        [$sectionId]
    );
}

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-gray-900">Assign Roll Numbers</h1>
    </div>

    <?php if ($msg = get_flash('success')): ?>
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = get_flash('error')): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"><?= e($msg) ?></div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <form method="GET" action="<?= url('students', 'roll-numbers') ?>" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-40">
                <label class="block text-sm font-medium text-gray-700 mb-1">Class <span class="text-red-500">*</span></label>
                <select name="class_id" onchange="this.form.submit()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Class…</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $classId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1 min-w-40">
                <label class="block text-sm font-medium text-gray-700 mb-1">Section <span class="text-red-500">*</span></label>
                <select name="section_id" onchange="this.form.submit()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Section…</option>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= $sec['id'] ?>" <?= $sectionId == $sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if ($sectionId && empty($students)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <p class="text-gray-400 text-sm">No active students found in this section.</p>
    </div>
    <?php elseif (!empty($students)): ?>
    <form method="POST" action="<?= url('students', 'roll-numbers') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="section_id" value="<?= $sectionId ?>">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-4">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">#</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Student Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Admission No.</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Roll Number</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($students as $i => $st): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500"><?= $i + 1 ?></td>
                        <td class="px-4 py-3 font-medium text-gray-900"><?= e($st['full_name']) ?></td>
                        <td class="px-4 py-3 text-gray-600"><?= e($st['admission_no']) ?></td>
                        <td class="px-4 py-3">
                            <input type="number" name="roll[<?= $st['id'] ?>]"
                                   value="<?= e($st['roll_no']) ?>" min="1"
                                   class="w-24 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-primary-500">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="autoFill()"
                    class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Auto-fill (1, 2, 3…)
            </button>
            <button type="submit"
                    class="px-5 py-2 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium">
                Save Roll Numbers
            </button>
        </div>
    </form>
    <?php elseif (!$classId): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p class="text-gray-400 text-sm">Select a class and section to assign roll numbers.</p>
    </div>
    <?php endif; ?>
</div>

<script>
function autoFill() {
    var inputs = document.querySelectorAll('input[name^="roll["]');
    inputs.forEach(function(inp, i) { inp.value = i + 1; });
}
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
