<?php
/**
 * Students — Reset Student Password
 */

$classes   = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");
$classId   = input_int('class_id');
$sections  = $classId
    ? db_fetch_all("SELECT id, name FROM sections WHERE class_id = ? AND is_active = 1 ORDER BY name", [$classId])
    : [];

ob_start();
?>

<div class="max-w-2xl mx-auto space-y-6">
    <h1 class="text-xl font-bold text-gray-900">Reset Student Password</h1>

    <?php if ($msg = get_flash('success')): ?>
        <div class="p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = get_flash('error')): ?>
        <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"><?= e($msg) ?></div>
    <?php endif; ?>

    <!-- Option 1: Individual -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4 pb-2 border-b">Reset Individual Student Password</h2>
        <form method="POST" action="<?= url('students', 'reset-password') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="mode" value="single">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Admission No. or Username <span class="text-red-500">*</span></label>
                    <input type="text" name="identifier" required placeholder="e.g. STU-001"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password <span class="text-red-500">*</span></label>
                    <input type="text" name="new_password" required placeholder="New password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <p class="mt-1 text-xs text-gray-400">Leave blank to auto-generate.</p>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
                    Reset Password
                </button>
            </div>
        </form>
    </div>

    <!-- Option 2: By Class -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4 pb-2 border-b">Bulk Reset by Class / Section</h2>
        <form method="POST" action="<?= url('students', 'reset-password') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="mode" value="bulk">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class <span class="text-red-500">*</span></label>
                    <select name="class_id" required onchange="this.form.submit()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">Select Class…</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $classId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($sections)): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                    <select name="section_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">All Sections in Class</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?= $sec['id'] ?>"><?= e($sec['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reset Password To</label>
                    <select name="bulk_password_mode"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="adm_no">Admission Number</option>
                        <option value="dob">Date of Birth (DDMMYYYY)</option>
                        <option value="random">Random 8-char</option>
                    </select>
                </div>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4 text-xs text-yellow-800">
                <strong>Warning:</strong> This will reset passwords for all active students in the selected class/section. Students will need to use the new password on their next login.
            </div>
            <div class="flex justify-end">
                <button type="submit"
                        onclick="return confirm('Reset passwords for all students in this class/section?')"
                        class="px-5 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 font-medium">
                    Bulk Reset
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
