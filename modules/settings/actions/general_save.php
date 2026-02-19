<?php
/**
 * Settings — Save General Settings
 */
verify_csrf();
require_permission('settings_manage');

$settings = $_POST['settings'] ?? [];

if (!is_array($settings)) {
    set_flash('error', 'Invalid settings data.');
    redirect('settings', 'general');
}

$pdo = db_connect();
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");

    foreach ($settings as $key => $value) {
        $key = trim($key);
        $value = trim($value);
        $stmt->execute([$value, $key]);
    }

    $pdo->commit();

    // Clear cached settings
    if (isset($_SESSION['settings_cache'])) {
        unset($_SESSION['settings_cache']);
    }

    audit_log('update', 'settings', 0, 'Updated system settings');
    set_flash('success', 'Settings saved successfully.');
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log("Settings save error: " . $e->getMessage());
    set_flash('error', 'Failed to save settings.');
}

redirect('settings', 'general');
