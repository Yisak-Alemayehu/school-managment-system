<?php
/**
 * HR — Process Biometric Scans for a Date
 * Converts raw scan logs into attendance records.
 */
csrf_protect();

require_once APP_ROOT . '/core/attendance.php';
require_once APP_ROOT . '/core/ethiopian_calendar.php';

$dateGregorian = trim(input('date'));

if (!$dateGregorian) {
    set_flash('error', 'Date is required.');
    redirect(url('hr', 'attendance'));
}

$result = attendance_process_biometric_scans($dateGregorian);

if ($result['processed'] > 0) {
    audit_log('hr.attendance.biometric', "Processed biometric scans for {$dateGregorian} — {$result['processed']} employee(s)");
    set_flash('success', "Processed biometric attendance for {$result['processed']} employee(s) on {$dateGregorian}.");
} else {
    set_flash('info', 'No unprocessed biometric scans found for this date.');
}

redirect(url('hr', 'attendance'));
