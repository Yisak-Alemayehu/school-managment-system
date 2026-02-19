<?php
/**
 * Auth — Profile View
 */
$user = auth_user();
$userRecord = db_fetch_one("SELECT * FROM users WHERE id = ?", [$user['id']]);

ob_start();
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center text-primary-800 text-2xl font-bold">
                <?= e(strtoupper(substr($userRecord['full_name'], 0, 1))) ?>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900"><?= e($userRecord['full_name']) ?></h2>
                <p class="text-sm text-gray-500"><?= e($user['role_name'] ?? 'User') ?></p>
            </div>
        </div>

        <form method="POST" action="<?= url('auth', 'profile') ?>" enctype="multipart/form-data" class="space-y-5">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?= e(old('full_name') ?: $userRecord['full_name']) ?>"
                           required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                    <?php if ($err = get_validation_error('full_name')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" value="<?= e(old('email') ?: $userRecord['email']) ?>"
                           required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                    <?php if ($err = get_validation_error('email')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?= e(old('phone') ?: $userRecord['phone']) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                           placeholder="+251...">
                    <?php if ($err = get_validation_error('phone')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" value="<?= e($userRecord['username']) ?>" disabled
                           class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-sm text-gray-500">
                </div>
            </div>

            <div>
                <label for="avatar" class="block text-sm font-medium text-gray-700 mb-1">Profile Photo</label>
                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:bg-primary-50 file:text-primary-700 file:text-sm file:font-medium">
                <p class="mt-1 text-xs text-gray-500">JPG, PNG, or WebP. Max 2MB.</p>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="<?= url('dashboard') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                    Update Profile
                </button>
            </div>
        </form>
    </div>

    <!-- Account Info -->
    <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Account Information</h3>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-gray-500">Member Since</dt>
                <dd class="font-medium text-gray-900"><?= format_date($userRecord['created_at']) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500">Last Login</dt>
                <dd class="font-medium text-gray-900"><?= $userRecord['last_login_at'] ? format_datetime($userRecord['last_login_at']) : 'Never' ?></dd>
            </div>
            <div>
                <dt class="text-gray-500">Last Login IP</dt>
                <dd class="font-medium text-gray-900"><?= e($userRecord['last_login_ip'] ?? 'N/A') ?></dd>
            </div>
            <div>
                <dt class="text-gray-500">Password Changed</dt>
                <dd class="font-medium text-gray-900"><?= $userRecord['password_changed_at'] ? format_datetime($userRecord['password_changed_at']) : 'Never' ?></dd>
            </div>
        </dl>
        <div class="mt-4 pt-4 border-t">
            <a href="<?= url('auth', 'change-password') ?>" class="text-sm text-primary-600 hover:text-primary-700 font-medium">Change Password &rarr;</a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'My Profile';
require APP_ROOT . '/templates/layout.php';
