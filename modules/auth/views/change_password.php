<?php
/**
 * Auth — Change Password View (authenticated)
 */
$user = auth_user();
ob_start();
?>

<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Change Password</h2>

        <form method="POST" action="<?= url('auth', 'change-password') ?>" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                <input type="password" id="current_password" name="current_password" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                <?php if ($err = get_validation_error('current_password')): ?>
                    <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                       placeholder="Min 8 chars, upper, lower, number, symbol">
                <?php if ($err = get_validation_error('password')): ?>
                    <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
            </div>

            <div class="flex justify-end gap-3">
                <a href="<?= url('dashboard') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Change Password';
require APP_ROOT . '/templates/layout.php';
