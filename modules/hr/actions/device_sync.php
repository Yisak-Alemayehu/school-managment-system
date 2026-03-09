<?php
/**
 * HR — Sync Biometric Device
 * Process unprocessed scan logs from a biometric device.
 */
csrf_protect();

require_once APP_ROOT . '/core/attendance.php';
require_once APP_ROOT . '/core/ethiopian_calendar.php';

$deviceId = input_int('device_id');

if (!$deviceId) {
    set_flash('error', 'Invalid device.');
    redirect(url('hr', 'devices'));
}

$result = attendance_sync_device($deviceId);

if ($result['status'] === 'error') {
    set_flash('error', $result['message']);
} else {
    audit_log('hr.device.sync', "Synced device ID: {$deviceId} — {$result['message']}");
    set_flash('success', $result['message']);
}

redirect(url('hr', 'devices'));
