<?php
/**
 * Parents — Reset Parent Password
 * Admin-only: reset a single guardian's or bulk guardians' passwords.
 */

ob_start();
?>

<div class="max-w-2xl mx-auto space-y-6">
    <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Reset Parent Password</h1>

    <?php if ($msg = get_flash('success')): ?>
        <div class="p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = get_flash('error')): ?>
        <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"><?= e($msg) ?></div>
    <?php endif; ?>

    <!-- Option 1: Individual -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4 pb-2 border-b">Reset Individual Parent Password</h2>
        <form method="POST" action="<?= url('students', 'parent-reset-password') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="mode" value="single">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Username or Phone <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="identifier" required placeholder="e.g. getachew.tekle or 0911…"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Password</label>
                    <input type="text" name="new_password" placeholder="Leave blank to auto-generate"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Leave blank to auto-generate a random password.</p>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
                    Reset Password
                </button>
            </div>
        </form>
    </div>

    <!-- Option 2: Bulk — all parents with accounts -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4 pb-2 border-b">Bulk Reset — All Parents</h2>
        <form method="POST" action="<?= url('students', 'parent-reset-password') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="mode" value="bulk">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reset Password To</label>
                <select id="bulkParentPasswordMode" name="bulk_password_mode"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    <option value="phone">Phone number</option>
                    <option value="random">Random 8-char</option>
                    <option value="custom">Custom</option>
                </select>
                <div id="bulkParentCustomPassword" class="hidden mt-2">
                    <input type="text" name="custom_password" placeholder="Custom password"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4 text-xs text-yellow-800">
                <strong>Warning:</strong> This resets passwords for <strong>all</strong> parent accounts that have login credentials set. Parents will need to use the new password on their next login.
            </div>
            <div class="flex justify-end">
                <button type="submit"
                        onclick="return confirm('Reset passwords for all parent accounts?')"
                        class="px-5 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 font-medium">
                    Bulk Reset
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var mode   = document.getElementById('bulkParentPasswordMode');
    var custom = document.getElementById('bulkParentCustomPassword');
    if (!mode || !custom) return;
    function sync() { custom.classList.toggle('hidden', mode.value !== 'custom'); }
    mode.addEventListener('change', sync);
    sync();
})();
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
