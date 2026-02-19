<?php
/**
 * Users — Edit View
 */

$userRecord = db_fetch_one(
    "SELECT u.*, ur.role_id FROM users u LEFT JOIN user_roles ur ON u.id = ur.user_id WHERE u.id = ? AND u.deleted_at IS NULL",
    [$id]
);
if (!$userRecord) {
    set_flash('error', 'User not found.');
    redirect(url('users'));
}

$roles = db_fetch_all("SELECT id, name FROM roles ORDER BY id");

ob_start();
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('users') ?>" class="p-1 text-gray-400 hover:text-gray-600 rounded">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900">Edit User: <?= e($userRecord['full_name']) ?></h1>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="<?= url('users', 'edit', $id) ?>" class="space-y-5">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="full_name" name="full_name" value="<?= e(old('full_name') ?: $userRecord['full_name']) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                    <?php if ($err = get_validation_error('full_name')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" value="<?= e($userRecord['username']) ?>" disabled
                           class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-sm text-gray-500">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="<?= e(old('email') ?: $userRecord['email']) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                    <?php if ($err = get_validation_error('email')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?= e(old('phone') ?: $userRecord['phone']) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                    <?php if ($err = get_validation_error('phone')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <label for="role_id" class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                <select id="role_id" name="role_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= (old('role_id') ?: $userRecord['role_id']) == $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Optional: New Password -->
            <div class="pt-4 border-t">
                <p class="text-sm font-medium text-gray-700 mb-3">Change Password <span class="text-xs text-gray-400">(leave blank to keep current)</span></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <input type="password" name="password" placeholder="New password"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        <?php if ($err = get_validation_error('password')): ?>
                            <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <input type="password" name="password_confirmation" placeholder="Confirm new password"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" value="1" <?= $userRecord['is_active'] ? 'checked' : '' ?>
                           class="rounded text-primary-600 focus:ring-primary-500">
                    Active
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="force_password_change" value="1" <?= $userRecord['force_password_change'] ? 'checked' : '' ?>
                           class="rounded text-primary-600 focus:ring-primary-500">
                    Force password change
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <a href="<?= url('users') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Edit User';
require APP_ROOT . '/templates/layout.php';
