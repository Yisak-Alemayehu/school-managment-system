<?php
/**
 * HR — Mark Staff Attendance (Bulk)
 * Accepts an array of attendance records for a given date.
 */
csrf_protect();

require_once APP_ROOT . '/core/ethiopian_calendar.php';

$dateGregorian = trim(input('date_gregorian'));
$dateEc        = trim(input('date_ec'));
$employees     = input_array('attendance'); // array of [employee_id => [status, check_in, check_out, notes]]

if (!$dateGregorian) {
    set_flash('error', 'Date is required.');
    redirect(url('hr', 'attendance'));
}

// Auto-convert if EC date provided but no Gregorian
if (!$dateGregorian && $dateEc) {
    $dateGregorian = ec_str_to_gregorian($dateEc);
}
if (!$dateEc && $dateGregorian) {
    $dateEc = gregorian_str_to_ec($dateGregorian);
}

if (empty($employees)) {
    set_flash('error', 'No attendance data submitted.');
    redirect(url('hr', 'attendance'));
}

$userId  = auth_user_id();
$success = 0;

foreach ($employees as $empId => $record) {
    $empId  = (int)$empId;
    $status = $record['status'] ?? 'present';

    // Validate employee exists
    $emp = db_fetch_one("SELECT id FROM hr_employees WHERE id = ? AND deleted_at IS NULL AND status = 'active'", [$empId]);
    if (!$emp) continue;

    $attData = [
        'employee_id'    => $empId,
        'date_ec'        => $dateEc,
        'date_gregorian' => $dateGregorian,
        'check_in'       => !empty($record['check_in']) ? $record['check_in'] : null,
        'check_out'      => !empty($record['check_out']) ? $record['check_out'] : null,
        'status'         => $status,
        'source'         => 'manual',
        'notes'          => !empty($record['notes']) ? trim($record['notes']) : null,
        'marked_by'      => $userId,
    ];

    // Upsert: update if exists for this employee+date
    $existing = db_fetch_one(
        "SELECT id FROM hr_attendance WHERE employee_id = ? AND date_gregorian = ?",
        [$empId, $dateGregorian]
    );

    if ($existing) {
        unset($attData['employee_id'], $attData['date_gregorian'], $attData['date_ec']);
        db_update('hr_attendance', $attData, 'id = ?', [$existing['id']]);
    } else {
        db_insert('hr_attendance', $attData);
    }
    $success++;
}

audit_log('hr.attendance.mark', "Marked attendance for {$success} employee(s) on {$dateGregorian}");
set_flash('success', "Attendance recorded for {$success} employee(s).");
redirect(url('hr', 'attendance'));
