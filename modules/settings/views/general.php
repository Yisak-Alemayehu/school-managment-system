<?php
/**
 * Settings — General Settings
 */
$pageTitle = 'System Settings';

$settings = db_fetch_all("SELECT * FROM settings ORDER BY setting_group, setting_key");

// Group settings
$groups = [];
foreach ($settings as $s) {
    $groups[$s['setting_group'] ?? 'general'][] = $s;
}

ob_start();
?>
<div class="max-w-4xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">System Settings</h1>

    <form method="POST" action="<?= url('settings', 'general-save') ?>">
        <?= csrf_field() ?>

        <?php foreach ($groups as $group => $items): ?>
            <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 capitalize"><?= e(str_replace('_', ' ', $group)) ?> Settings</h2>
                <div class="space-y-4">
                    <?php foreach ($items as $s): ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                            <div>
                                <label class="block text-sm font-medium text-gray-700"><?= e(ucwords(str_replace('_', ' ', $s['setting_key']))) ?></label>
                                <?php if ($s['description']): ?>
                                    <p class="text-xs text-gray-500"><?= e($s['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="md:col-span-2">
                                <?php if ($s['setting_type'] === 'boolean'): ?>
                                    <select name="settings[<?= e($s['setting_key']) ?>]"
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                        <option value="1" <?= $s['setting_value'] == '1' ? 'selected' : '' ?>>Yes</option>
                                        <option value="0" <?= $s['setting_value'] == '0' ? 'selected' : '' ?>>No</option>
                                    </select>
                                <?php elseif ($s['setting_type'] === 'textarea'): ?>
                                    <textarea name="settings[<?= e($s['setting_key']) ?>]" rows="3"
                                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"><?= e($s['setting_value']) ?></textarea>
                                <?php elseif ($s['setting_type'] === 'number'): ?>
                                    <input type="number" name="settings[<?= e($s['setting_key']) ?>]"
                                           value="<?= e($s['setting_value']) ?>"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <?php else: ?>
                                    <input type="text" name="settings[<?= e($s['setting_key']) ?>]"
                                           value="<?= e($s['setting_value']) ?>"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
                Save Settings
            </button>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
