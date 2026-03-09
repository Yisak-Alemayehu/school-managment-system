<?php
/**
 * Attendance Service
 * Urji Beri School Management System — HR Module
 *
 * Provides attendance status calculation, biometric scan processing,
 * holiday checking, and monthly report generation.
 *
 * Attendance Rules:
 *   No check-in          → Absent
 *   Check-in after 08:15 → Late
 *   Less than 4 hours    → Half Day
 *   Otherwise            → Present
 *
 * Biometric Processing:
 *   First scan of the day  → check-in
 *   Last scan of the day   → check-out
 *   Intermediate scans     → ignored
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Default work schedule constants
define('ATTENDANCE_WORK_START', '08:00');
define('ATTENDANCE_LATE_THRESHOLD', '08:15');
define('ATTENDANCE_HALF_DAY_HOURS', 4);

/**
 * Calculate attendance status from check-in and check-out times.
 *
 * @param string|null $checkIn  Time string HH:MM or HH:MM:SS (null = absent)
 * @param string|null $checkOut Time string HH:MM or HH:MM:SS (null = no checkout)
 * @return string One of: present, absent, late, half_day
 */
function attendance_calculate_status(?string $checkIn, ?string $checkOut): string {
    // No check-in = Absent
    if (empty($checkIn)) {
        return 'absent';
    }

    // Parse check-in time
    $inTime = strtotime($checkIn);
    $lateThreshold = strtotime(ATTENDANCE_LATE_THRESHOLD);

    // Check if less than minimum hours (half day)
    if (!empty($checkOut)) {
        $outTime = strtotime($checkOut);
        $hoursWorked = ($outTime - $inTime) / 3600;
        if ($hoursWorked < ATTENDANCE_HALF_DAY_HOURS) {
            return 'half_day';
        }
    }

    // Check if late
    if ($inTime > $lateThreshold) {
        return 'late';
    }

    return 'present';
}

/**
 * Process raw biometric scan logs for a specific date.
 * Groups scans by employee, uses first scan as check-in and last as check-out.
 *
 * @param string $dateGregorian YYYY-MM-DD
 * @param int|null $deviceId    Optional: limit to a specific device
 * @return array ['processed' => int, 'errors' => array]
 */
function attendance_process_biometric_scans(string $dateGregorian, ?int $deviceId = null): array {
    $where = "DATE(scan_time) = ? AND processed = 0";
    $params = [$dateGregorian];

    if ($deviceId) {
        $where .= " AND device_id = ?";
        $params[] = $deviceId;
    }

    // Get all unprocessed logs for this date grouped by employee
    $logs = db_fetch_all(
        "SELECT id, device_id, employee_id, scan_time, scan_type
         FROM hr_attendance_logs
         WHERE {$where}
         ORDER BY employee_id, scan_time",
        $params
    );

    if (empty($logs)) {
        return ['processed' => 0, 'errors' => []];
    }

    // Group by employee
    $grouped = [];
    foreach ($logs as $log) {
        $grouped[$log['employee_id']][] = $log;
    }

    $dateEc = gregorian_str_to_ec($dateGregorian);
    $processed = 0;
    $errors = [];
    $logIds = [];

    foreach ($grouped as $empId => $empLogs) {
        // Verify employee exists and is active
        $emp = db_fetch_one(
            "SELECT id FROM hr_employees WHERE id = ? AND deleted_at IS NULL AND status = 'active'",
            [$empId]
        );
        if (!$emp) {
            continue;
        }

        // First scan = check-in, last scan = check-out
        $firstScan = $empLogs[0];
        $lastScan = end($empLogs);

        $checkIn = date('H:i:s', strtotime($firstScan['scan_time']));
        $checkOut = (count($empLogs) > 1)
            ? date('H:i:s', strtotime($lastScan['scan_time']))
            : null;

        // Calculate status
        $status = attendance_calculate_status($checkIn, $checkOut);

        // Upsert attendance record
        $existing = db_fetch_one(
            "SELECT id FROM hr_attendance WHERE employee_id = ? AND date_gregorian = ?",
            [$empId, $dateGregorian]
        );

        $attData = [
            'check_in'       => $checkIn,
            'check_out'      => $checkOut,
            'status'         => $status,
            'source'         => 'biometric',
            'device_id'      => $firstScan['device_id'],
            'sync_timestamp' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            db_update('hr_attendance', $attData, 'id = ?', [$existing['id']]);
        } else {
            $attData['employee_id'] = $empId;
            $attData['date_ec'] = $dateEc;
            $attData['date_gregorian'] = $dateGregorian;
            db_insert('hr_attendance', $attData);
        }

        // Collect log IDs for marking as processed
        foreach ($empLogs as $l) {
            $logIds[] = $l['id'];
        }
        $processed++;
    }

    // Mark all processed log IDs
    if (!empty($logIds)) {
        $placeholders = implode(',', array_fill(0, count($logIds), '?'));
        db_fetch_one(
            "UPDATE hr_attendance_logs SET processed = 1 WHERE id IN ({$placeholders})",
            $logIds
        );
    }

    return ['processed' => $processed, 'errors' => $errors];
}

/**
 * Check if a Gregorian date is a holiday.
 *
 * @param string $dateGregorian YYYY-MM-DD
 * @return array|null Holiday record or null
 */
function attendance_is_holiday(string $dateGregorian): ?array {
    $holiday = db_fetch_one(
        "SELECT * FROM hr_holidays WHERE date_gregorian = ?",
        [$dateGregorian]
    );
    return $holiday ?: null;
}

/**
 * Check if a Gregorian date is a weekend (Saturday or Sunday).
 *
 * @param string $dateGregorian YYYY-MM-DD
 * @return bool
 */
function attendance_is_weekend(string $dateGregorian): bool {
    $dayOfWeek = (int)(new DateTime($dateGregorian))->format('N');
    return $dayOfWeek >= 6; // Saturday=6, Sunday=7
}

/**
 * Generate monthly attendance report for all employees.
 *
 * @param int $monthEc  Ethiopian month (1-13)
 * @param int $yearEc   Ethiopian year
 * @return array ['employees' => [...], 'summary' => [...], 'period' => [...]]
 */
function attendance_monthly_report(int $monthEc, int $yearEc): array {
    $range = ec_month_range($monthEc, $yearEc);
    $startDate = $range['start'];
    $endDate = $range['end'];
    $totalWorkingDays = ec_working_days($startDate, $endDate);

    // Get all active employees
    $employees = db_fetch_all(
        "SELECT e.id, e.employee_id, e.first_name, e.father_name, e.grandfather_name,
                e.department_id, d.name AS department_name
         FROM hr_employees e
         LEFT JOIN hr_departments d ON e.department_id = d.id
         WHERE e.deleted_at IS NULL AND e.status = 'active'
         ORDER BY e.first_name",
        []
    );

    $report = [];
    $totalPresent = 0;
    $totalAbsent = 0;
    $totalLate = 0;
    $totalHalf = 0;
    $totalLeave = 0;

    foreach ($employees as $emp) {
        $summary = payroll_attendance_summary((int)$emp['id'], $startDate, $endDate);

        // Calculate effective days worked (present + late count full, half_day counts 0.5)
        $effectiveDays = $summary['days_present'] + $summary['days_late']
                       + ($summary['days_half'] * 0.5)
                       + $summary['days_leave']
                       + $summary['days_holiday'];

        $report[] = [
            'employee'        => $emp,
            'days_present'    => $summary['days_present'],
            'days_absent'     => $summary['days_absent'],
            'days_late'       => $summary['days_late'],
            'days_half'       => $summary['days_half'],
            'days_leave'      => $summary['days_leave'],
            'days_holiday'    => $summary['days_holiday'],
            'effective_days'  => $effectiveDays,
            'attendance_pct'  => $totalWorkingDays > 0
                ? round(($effectiveDays / $totalWorkingDays) * 100, 1)
                : 0,
        ];

        $totalPresent += $summary['days_present'];
        $totalAbsent  += $summary['days_absent'];
        $totalLate    += $summary['days_late'];
        $totalHalf    += $summary['days_half'];
        $totalLeave   += $summary['days_leave'];
    }

    return [
        'employees' => $report,
        'summary'   => [
            'total_employees'   => count($employees),
            'working_days'      => $totalWorkingDays,
            'total_present'     => $totalPresent,
            'total_absent'      => $totalAbsent,
            'total_late'        => $totalLate,
            'total_half_day'    => $totalHalf,
            'total_leave'       => $totalLeave,
        ],
        'period' => [
            'month_ec'   => $monthEc,
            'year_ec'    => $yearEc,
            'month_name' => ec_month_name($monthEc),
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ],
    ];
}

/**
 * Sync attendance from a biometric device.
 * Fetches unprocessed logs from the device and processes them.
 *
 * @param int $deviceId
 * @return array ['status' => string, 'processed' => int, 'message' => string]
 */
function attendance_sync_device(int $deviceId): array {
    $device = db_fetch_one(
        "SELECT * FROM hr_attendance_devices WHERE id = ? AND status = 'active'",
        [$deviceId]
    );

    if (!$device) {
        return ['status' => 'error', 'processed' => 0, 'message' => 'Device not found or inactive.'];
    }

    // Get distinct dates with unprocessed logs
    $dates = db_fetch_all(
        "SELECT DISTINCT DATE(scan_time) as scan_date
         FROM hr_attendance_logs
         WHERE device_id = ? AND processed = 0
         ORDER BY scan_date",
        [$deviceId]
    );

    if (empty($dates)) {
        // Update last_sync even if nothing to process
        db_update('hr_attendance_devices', ['last_sync' => date('Y-m-d H:i:s')], 'id = ?', [$deviceId]);
        return ['status' => 'ok', 'processed' => 0, 'message' => 'No new scans to process.'];
    }

    $totalProcessed = 0;
    $allErrors = [];

    foreach ($dates as $d) {
        $result = attendance_process_biometric_scans($d['scan_date'], $deviceId);
        $totalProcessed += $result['processed'];
        $allErrors = array_merge($allErrors, $result['errors']);
    }

    // Update device last_sync timestamp
    db_update('hr_attendance_devices', ['last_sync' => date('Y-m-d H:i:s')], 'id = ?', [$deviceId]);

    return [
        'status'    => empty($allErrors) ? 'ok' : 'partial',
        'processed' => $totalProcessed,
        'message'   => "Processed {$totalProcessed} employee(s) across " . count($dates) . " date(s).",
        'errors'    => $allErrors,
    ];
}

/**
 * Mark absent for employees who have no attendance record on a working day.
 * Should typically be run at end of day or via cron.
 *
 * @param string $dateGregorian YYYY-MM-DD
 * @return int Number of absent records created
 */
function attendance_mark_absent(string $dateGregorian): int {
    // Skip weekends and holidays
    if (attendance_is_weekend($dateGregorian) || attendance_is_holiday($dateGregorian)) {
        return 0;
    }

    $dateEc = gregorian_str_to_ec($dateGregorian);

    // Get active employees who don't have attendance for this date
    $employees = db_fetch_all(
        "SELECT e.id FROM hr_employees e
         WHERE e.deleted_at IS NULL AND e.status = 'active'
           AND e.start_date_gregorian <= ?
           AND (e.end_date_gregorian IS NULL OR e.end_date_gregorian >= ?)
           AND e.id NOT IN (
               SELECT employee_id FROM hr_attendance WHERE date_gregorian = ?
           )",
        [$dateGregorian, $dateGregorian, $dateGregorian]
    );

    $count = 0;
    foreach ($employees as $emp) {
        // Check if employee is on approved leave
        $onLeave = db_fetch_one(
            "SELECT id FROM hr_leave_requests
             WHERE employee_id = ? AND status = 'approved'
               AND start_date_gregorian <= ? AND end_date_gregorian >= ?",
            [$emp['id'], $dateGregorian, $dateGregorian]
        );

        $status = $onLeave ? 'leave' : 'absent';

        db_insert('hr_attendance', [
            'employee_id'    => $emp['id'],
            'date_ec'        => $dateEc,
            'date_gregorian' => $dateGregorian,
            'status'         => $status,
            'source'         => 'manual',
            'notes'          => $onLeave ? 'Auto-marked: on approved leave' : 'Auto-marked: absent (no attendance record)',
        ]);
        $count++;
    }

    return $count;
}

/**
 * Get employee attendance history for a date range.
 *
 * @param int    $employeeId
 * @param string $startDate YYYY-MM-DD
 * @param string $endDate   YYYY-MM-DD
 * @return array
 */
function attendance_employee_history(int $employeeId, string $startDate, string $endDate): array {
    return db_fetch_all(
        "SELECT a.*, d.device_name
         FROM hr_attendance a
         LEFT JOIN hr_attendance_devices d ON a.device_id = d.id
         WHERE a.employee_id = ? AND a.date_gregorian BETWEEN ? AND ?
         ORDER BY a.date_gregorian DESC",
        [$employeeId, $startDate, $endDate]
    ) ?: [];
}

/**
 * Get leave balance for an employee across all leave types.
 *
 * @param int $employeeId
 * @return array Array of leave type balances
 */
function attendance_leave_balance(int $employeeId): array {
    $types = db_fetch_all("SELECT * FROM hr_leave_types WHERE status = 'active' ORDER BY name");
    $balances = [];

    foreach ($types as $t) {
        $used = (int)db_fetch_value(
            "SELECT COALESCE(SUM(days), 0) FROM hr_leave_requests
             WHERE employee_id = ? AND leave_type_id = ? AND status IN ('pending','approved')
             AND YEAR(start_date_gregorian) = YEAR(CURDATE())",
            [$employeeId, $t['id']]
        );

        $balances[] = [
            'leave_type_id' => (int)$t['id'],
            'leave_type'    => $t['name'],
            'code'          => $t['code'],
            'days_allowed'  => (int)$t['days_allowed'],
            'days_used'     => $used,
            'days_remaining'=> max(0, (int)$t['days_allowed'] - $used),
        ];
    }

    return $balances;
}
