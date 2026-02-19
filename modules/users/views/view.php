<?php
/**
 * Users — View Detail
 */

$userRecord = db_fetch_one(
    "SELECT u.*, r.name as role_name FROM users u
     LEFT JOIN user_roles ur ON u.id = ur.user_id
     LEFT JOIN roles r ON ur.role_id = r.id
     WHERE u.id = ? AND u.deleted_at IS NULL",
    [$id]
);
if (!$userRecord) {
    set_flash('error', 'User not found.');
    redirect(url('users'));
}

// Get audit log
$auditLogs = db_fetch_all(
    "SELECT action, created_at, ip_address FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 20",
    [$id]
);

ob_start();
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="<?= url('users') ?>" class="p-1 text-gray-400 hover:text-gray-600 rounded">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-xl font-bold text-gray-900">User Details</h1>
        </div>
        <?php if (auth_has_permission('users.edit')): ?>
            <a href="<?= url('users', 'edit', $id) ?>" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                Edit User
            </a>
        <?php endif; ?>
    </div>

    <!-- Profile Card -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="flex items-start gap-4">
            <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center text-primary-800 text-2xl font-bold flex-shrink-0">
                <?= e(strtoupper(substr($userRecord['full_name'], 0, 1))) ?>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-semibold text-gray-900"><?= e($userRecord['full_name']) ?></h2>
                    <?php if ($userRecord['is_active']): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                    <?php endif; ?>
                </div>
                <p class="text-sm text-gray-500"><?= e($userRecord['role_name'] ?? 'No role') ?></p>
            </div>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6 text-sm">
            <div>
                <dt class="text-gray-500">Username</dt>
                <dd class="font-medium text-gray-900"><?= e($userRecord['username']) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500">Email</dt>
                <dd class="font-medium text-gray-900"><?= e($userRecord['email']) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500">Phone</dt>
                <dd class="font-medium text-gray-900"><?= e($userRecord['phone'] ?: 'N/A') ?></dd>
            </div>
            <div>
                <dt class="text-gray-500">Created</dt>
                <dd class="font-medium text-gray-900"><?= format_datetime($userRecord['created_at']) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500">Last Login</dt>
                <dd class="font-medium text-gray-900"><?= $userRecord['last_login_at'] ? format_datetime($userRecord['last_login_at']) : 'Never' ?></dd>
            </div>
            <div>
                <dt class="text-gray-500">Last Login IP</dt>
                <dd class="font-medium text-gray-900"><?= e($userRecord['last_login_ip'] ?: 'N/A') ?></dd>
            </div>
            <div>
                <dt class="text-gray-500">Password Changed</dt>
                <dd class="font-medium text-gray-900"><?= $userRecord['password_changed_at'] ? format_datetime($userRecord['password_changed_at']) : 'Never' ?></dd>
            </div>
            <div>
                <dt class="text-gray-500">Force Password Change</dt>
                <dd class="font-medium text-gray-900"><?= $userRecord['force_password_change'] ? 'Yes' : 'No' ?></dd>
            </div>
        </dl>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Recent Activity</h3>
        <?php if (empty($auditLogs)): ?>
            <p class="text-sm text-gray-400">No activity recorded.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($auditLogs as $log): ?>
                    <div class="flex items-center justify-between py-2 border-b last:border-0">
                        <div>
                            <p class="text-sm text-gray-700"><?= e(str_replace('_', ' ', ucfirst($log['action']))) ?></p>
                            <p class="text-xs text-gray-400"><?= e($log['ip_address']) ?></p>
                        </div>
                        <span class="text-xs text-gray-500"><?= time_ago($log['created_at']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'User Details';
require APP_ROOT . '/templates/layout.php';
