<?php $pageTitle = 'School Profile'; $currentPage = 'profile'; include __DIR__ . '/layout_top.php'; ?>

<div class="max-w-2xl">
    <form method="POST" action="<?= base_url('customer/profile/update') ?>" class="bg-white rounded-xl border border-gray-100 p-6">
        <?= csrf_field() ?>
        <h3 class="text-sm font-bold text-gray-900 mb-6">Update School Information</h3>

        <div class="space-y-5">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Your Name</label>
                    <input type="text" name="name" value="<?= e($user['name']) ?>" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                    <input type="email" value="<?= e($user['email']) ?>" disabled class="w-full px-3 py-2 rounded-lg border border-gray-100 text-sm bg-gray-50 text-gray-500">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">School Name</label>
                    <input type="text" name="school_name" value="<?= e($school['name']) ?>" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                    <input type="text" name="phone" value="<?= e($school['phone'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Address</label>
                <input type="text" name="address" value="<?= e($school['address'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20">
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Student Count</label>
                    <input type="number" name="student_count" value="<?= e($school['student_count']) ?>" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">School Type</label>
                    <select name="school_type" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                        <?php foreach (['private', 'government', 'ngo', 'religious'] as $type): ?>
                        <option value="<?= $type ?>" <?= ($school['school_type'] ?? '') === $type ? 'selected' : '' ?>><?= ucfirst($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Change Password -->
            <div class="pt-5 border-t border-gray-100">
                <h4 class="text-xs font-bold text-gray-900 mb-3 uppercase tracking-wider">Change Password</h4>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">New Password</label>
                        <input type="password" name="password" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20" placeholder="Leave blank to keep current">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Confirm Password</label>
                        <input type="password" name="password_confirm" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    </div>
                </div>
            </div>

            <button type="submit" class="px-6 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-xl hover:bg-primary-700 transition-colors">Save Changes</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
