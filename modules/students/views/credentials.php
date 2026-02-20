<?php
/**
 * Students — Generate Username & Password
 * Generate login credentials by class or for an individual student.
 */

$classes   = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");
$classId   = input_int('class_id');
$sections  = $classId
    ? db_fetch_all("SELECT id, name FROM sections WHERE class_id = ? AND is_active = 1 ORDER BY name", [$classId])
    : [];

// Load preview list
$students = [];
$sectionId = input_int('section_id');
$singleId  = input_int('student_id');

if ($sectionId) {
    $students = db_fetch_all(
        "SELECT s.id, s.full_name, s.admission_no, u.username
           FROM students s
           LEFT JOIN users u ON u.id = s.user_id
           JOIN enrollments e ON e.student_id = s.id
          WHERE e.section_id = ? AND e.status = 'active' AND s.deleted_at IS NULL
          ORDER BY s.full_name",
        [$sectionId]
    );
} elseif ($singleId) {
    $students = db_fetch_all(
        "SELECT s.id, s.full_name, s.admission_no, u.username
           FROM students s
           LEFT JOIN users u ON u.id = s.user_id
          WHERE s.id = ? AND s.deleted_at IS NULL",
        [$singleId]
    );
}

ob_start();
?>

<div class="max-w-3xl mx-auto space-y-6">
    <h1 class="text-xl font-bold text-gray-900">Generate Username &amp; Password</h1>

    <?php if ($msg = get_flash('success')): ?>
        <div class="p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = get_flash('error')): ?>
        <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"><?= e($msg) ?></div>
    <?php endif; ?>

    <!-- Mode Tabs -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <div class="flex gap-4 border-b border-gray-200 pb-4">
            <button type="button" onclick="switchMode('class')"
                    id="tab-class"
                    class="tab-btn px-4 py-1.5 text-sm rounded-md font-medium bg-primary-600 text-white">
                By Class / Section
            </button>
            <button type="button" onclick="switchMode('single')"
                    id="tab-single"
                    class="tab-btn px-4 py-1.5 text-sm rounded-md font-medium text-gray-600 hover:bg-gray-100">
                Single Student
            </button>
        </div>

        <!-- By Class Mode -->
        <div id="mode-class">
            <form method="GET" action="<?= url('students', 'credentials') ?>" class="flex flex-wrap gap-3 mb-4">
                <input type="hidden" name="_mode" value="class">
                <select name="class_id" onchange="this.form.submit()"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Class…</option>
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
                <button type="submit" class="px-4 py-2 border border-gray-300 text-sm rounded-lg hover:bg-gray-50">Load</button>
                <?php endif; ?>
            </form>

            <?php if (!empty($students) && $sectionId): ?>
            <form method="POST" action="<?= url('students', 'credentials') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="mode" value="class">
                <input type="hidden" name="section_id" value="<?= $sectionId ?>">
                <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden mb-4">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-700">
                                    <input type="checkbox" id="checkAll" onchange="document.querySelectorAll('input[name=\'ids[]\']').forEach(c=>c.checked=this.checked)">
                                </th>
                                <th class="px-4 py-2 text-left font-medium text-gray-700">Name</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-700">Adm. No.</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-700">Current Username</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($students as $st): ?>
                            <tr class="hover:bg-white">
                                <td class="px-4 py-2"><input type="checkbox" name="ids[]" value="<?= $st['id'] ?>" checked></td>
                                <td class="px-4 py-2 font-medium text-gray-900"><?= e($st['full_name']) ?></td>
                                <td class="px-4 py-2 text-gray-600"><?= e($st['admission_no']) ?></td>
                                <td class="px-4 py-2 text-gray-500 font-mono text-xs"><?= $st['username'] ? e($st['username']) : '<span class="text-yellow-600">Not set</span>' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <label class="text-sm font-medium text-gray-700">
                        Username format:
                        <select name="username_format" class="ml-2 px-2 py-1 border border-gray-300 rounded-md text-sm">
                            <option value="adm_no">Admission No. (e.g. STU-001)</option>
                            <option value="firstlast">firstname.lastname</option>
                            <option value="firstname_roll">firstname_rollno</option>
                        </select>
                    </label>
                    <label class="text-sm font-medium text-gray-700">
                        Password:
                        <select name="password_mode" class="ml-2 px-2 py-1 border border-gray-300 rounded-md text-sm">
                            <option value="adm_no">Admission No.</option>
                            <option value="dob">Date of Birth (DDMMYYYY)</option>
                            <option value="random">Random 8-char</option>
                        </select>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="overwrite" value="1">
                        Overwrite existing credentials
                    </label>
                </div>
                <div class="flex justify-end mt-4">
                    <button type="submit" class="px-5 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
                        Generate Credentials
                    </button>
                </div>
            </form>
            <?php elseif ($classId && !$sectionId && empty($sections)): ?>
                <p class="text-sm text-gray-400">No sections found for this class.</p>
            <?php endif; ?>
        </div>

        <!-- Single Student Mode -->
        <div id="mode-single" class="hidden">
            <form method="GET" action="<?= url('students', 'credentials') ?>" class="flex gap-3 mb-4">
                <input type="hidden" name="_mode" value="single">
                <input type="number" name="student_id" value="<?= e($singleId ?: '') ?>" placeholder="Student DB ID…"
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">Find</button>
            </form>

            <?php if (!empty($students) && $singleId): $st = $students[0]; ?>
            <form method="POST" action="<?= url('students', 'credentials') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="mode" value="single">
                <input type="hidden" name="ids[]" value="<?= $st['id'] ?>">
                <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 mb-4 space-y-3">
                    <p class="font-medium text-gray-900"><?= e($st['full_name']) ?> &mdash; <span class="text-gray-500 text-sm"><?= e($st['admission_no']) ?></span></p>
                    <?php if ($st['username']): ?>
                    <p class="text-sm text-gray-500">Current username: <code class="bg-gray-200 px-1 rounded"><?= e($st['username']) ?></code></p>
                    <?php endif; ?>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <input type="text" name="manual_username" placeholder="Leave blank to auto-generate"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input type="text" name="manual_password" placeholder="Leave blank to auto-generate"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        </div>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-5 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
                        Set Credentials
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function switchMode(mode) {
    document.getElementById('mode-class').classList.toggle('hidden', mode !== 'class');
    document.getElementById('mode-single').classList.toggle('hidden', mode !== 'single');
    document.getElementById('tab-class').className  = 'tab-btn px-4 py-1.5 text-sm rounded-md font-medium ' + (mode === 'class'  ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-100');
    document.getElementById('tab-single').className = 'tab-btn px-4 py-1.5 text-sm rounded-md font-medium ' + (mode === 'single' ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-100');
}
// Restore active tab from URL hint
var qs = new URLSearchParams(location.search);
if (qs.get('_mode') === 'single' || qs.get('student_id')) switchMode('single');
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
