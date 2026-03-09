<?php
/**
 * HR — Mark Absent for Employees Without Attendance
 * Auto-marks employees as absent or on-leave if no record exists for the given date.
 */
csrf_protect();

require_once APP_ROOT . '/core/attendance.php';
require_once APP_ROOT . '/core/ethiopian_calendar.php';

$dateGregorian = trim(input('date'));

if (!$dateGregorian) {
    set_flash('error', 'Date is required.');
    redirect(url('hr', 'attendance'));
}

$count = attendance_mark_absent($dateGregorian);

if ($count > 0) {
    audit_log('hr.attendance.mark_absent', "Auto-marked {$count} employee(s) absent on {$dateGregorian}");
    set_flash('success', "Marked {$count} employee(s) as absent/leave on {$dateGregorian}.");
} else {
    set_flash('info', 'No employees needed to be marked absent for this date (all have records, or it is a weekend/holiday).');
}

redirect(url('hr', 'attendance'));
