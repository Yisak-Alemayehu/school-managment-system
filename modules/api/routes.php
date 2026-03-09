<?php
/**
 * API Module — JSON helper endpoints
 * Consumed by AJAX calls (class → section / class → subject lookups, etc.)
 */

auth_require();

// All responses from this module are JSON
header('Content-Type: application/json; charset=utf-8');
// Prevent caching of dynamic data
header('Cache-Control: no-store');

$action = current_action();

switch ($action) {

    // ── GET /api/sections?class_id=X ───────────────────────────────
    case 'sections':
        $classId = input_int('class_id');
        if (!$classId) {
            echo json_encode([]);
            exit;
        }
        $rows = db_fetch_all(
            "SELECT id, name FROM sections WHERE class_id = ? AND is_active = 1 ORDER BY name",
            [$classId]
        );
        echo json_encode($rows ?: []);
        exit;

    // ── GET /api/subjects?class_id=X&session_id=Y ──────────────────
    case 'subjects':
        $classId   = input_int('class_id');
        $sessionId = input_int('session_id');
        if (!$classId || !$sessionId) {
            echo json_encode([]);
            exit;
        }
        $rows = db_fetch_all(
            "SELECT s.id, s.name, s.code
             FROM subjects s
             JOIN class_subjects cs ON cs.subject_id = s.id
             WHERE cs.class_id = ? AND cs.session_id = ?
             ORDER BY s.name",
            [$classId, $sessionId]
        );
        echo json_encode($rows ?: []);
        exit;

    // ══════════════════════════════════════════════════════════════
    // HR MODULE API ENDPOINTS
    // ══════════════════════════════════════════════════════════════

    // ── GET /api/hr-departments ─────────────────────────────────
    case 'hr-departments':
        auth_require_permission('hr.view');
        $rows = db_fetch_all(
            "SELECT d.id, d.name, d.code, d.status, d.description,
                    CONCAT(e.first_name, ' ', e.father_name) AS head_name,
                    (SELECT COUNT(*) FROM hr_employees WHERE department_id = d.id AND deleted_at IS NULL AND status = 'active') AS employee_count
             FROM hr_departments d
             LEFT JOIN hr_employees e ON d.head_of_department_id = e.id
             WHERE d.deleted_at IS NULL
             ORDER BY d.name"
        );
        echo json_encode(['data' => $rows ?: []]);
        exit;

    // ── GET /api/hr-department?id=X ─────────────────────────────
    case 'hr-department':
        auth_require_permission('hr.view');
        $id = input_int('id');
        if (!$id) { echo json_encode(['error' => 'ID required']); exit; }
        $dept = db_fetch_one(
            "SELECT d.*, CONCAT(e.first_name, ' ', e.father_name) AS head_name
             FROM hr_departments d
             LEFT JOIN hr_employees e ON d.head_of_department_id = e.id
             WHERE d.id = ? AND d.deleted_at IS NULL",
            [$id]
        );
        if (!$dept) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
        echo json_encode(['data' => $dept]);
        exit;

    // ── GET /api/hr-department-employees?department_id=X ────────
    case 'hr-department-employees':
        auth_require_permission('hr.view');
        $deptId = input_int('department_id');
        if (!$deptId) { echo json_encode([]); exit; }
        $rows = db_fetch_all(
            "SELECT id, employee_id, first_name, father_name, grandfather_name,
                    position, role, status, basic_salary
             FROM hr_employees
             WHERE department_id = ? AND deleted_at IS NULL
             ORDER BY first_name",
            [$deptId]
        );
        echo json_encode(['data' => $rows ?: []]);
        exit;

    // ── GET /api/hr-employees?search=X&department_id=X&status=X&page=X ──
    case 'hr-employees':
        auth_require_permission('hr.view');
        $search = trim(input('search'));
        $deptId = input_int('department_id');
        $status = input('status');
        $page   = max(1, input_int('page') ?: 1);
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $where = ["e.deleted_at IS NULL"];
        $params = [];

        if ($search) {
            $where[] = "(e.first_name LIKE ? OR e.father_name LIKE ? OR e.employee_id LIKE ? OR e.phone LIKE ?)";
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($deptId) {
            $where[] = "e.department_id = ?";
            $params[] = $deptId;
        }
        if ($status && in_array($status, ['active', 'left', 'suspended'], true)) {
            $where[] = "e.status = ?";
            $params[] = $status;
        }

        $whereStr = implode(' AND ', $where);

        $total = db_fetch_value("SELECT COUNT(*) FROM hr_employees e WHERE {$whereStr}", $params);
        $rows = db_fetch_all(
            "SELECT e.id, e.employee_id, e.first_name, e.father_name, e.grandfather_name,
                    e.gender, e.phone, e.email, e.position, e.role, e.employment_type,
                    e.status, e.basic_salary, e.department_id, d.name AS department_name
             FROM hr_employees e
             LEFT JOIN hr_departments d ON e.department_id = d.id
             WHERE {$whereStr}
             ORDER BY e.first_name
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        echo json_encode([
            'data'  => $rows ?: [],
            'total' => (int)$total,
            'page'  => $page,
            'pages' => ceil($total / $limit),
        ]);
        exit;

    // ── GET /api/hr-employee?id=X ───────────────────────────────
    case 'hr-employee':
        auth_require_permission('hr.view');
        $id = input_int('id');
        if (!$id) { echo json_encode(['error' => 'ID required']); exit; }
        $emp = db_fetch_one(
            "SELECT e.*, d.name AS department_name
             FROM hr_employees e
             LEFT JOIN hr_departments d ON e.department_id = d.id
             WHERE e.id = ? AND e.deleted_at IS NULL",
            [$id]
        );
        if (!$emp) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
        echo json_encode(['data' => $emp]);
        exit;

    // ── GET /api/hr-payroll-summary?period_id=X ─────────────────
    case 'hr-payroll-summary':
        auth_require_permission('hr.payroll');
        $periodId = input_int('period_id');
        if (!$periodId) { echo json_encode(['error' => 'Period ID required']); exit; }
        $summary = payroll_period_summary($periodId);
        echo json_encode(['data' => $summary]);
        exit;

    // ── GET /api/hr-payroll-calculate?basic_salary=X&transport=X&other=X ──
    case 'hr-payroll-calculate':
        auth_require_permission('hr.payroll');
        $basic = (float)input('basic_salary');
        $transport = (float)input('transport_allowance');
        $other = (float)input('other_allowance');
        $result = payroll_calculate($basic, $transport, $other);
        echo json_encode(['data' => $result]);
        exit;

    // ── GET /api/hr-leave-balance?employee_id=X ─────────────────
    case 'hr-leave-balance':
        auth_require_permission('hr.leave');
        $empId = input_int('employee_id');
        if (!$empId) { echo json_encode(['error' => 'Employee ID required']); exit; }
        $types = db_fetch_all("SELECT * FROM hr_leave_types WHERE status = 'active' ORDER BY name");
        $balances = [];
        foreach ($types as $t) {
            $used = db_fetch_value(
                "SELECT COALESCE(SUM(days), 0) FROM hr_leave_requests
                 WHERE employee_id = ? AND leave_type_id = ? AND status IN ('pending','approved')
                 AND YEAR(start_date_gregorian) = YEAR(CURDATE())",
                [$empId, $t['id']]
            );
            $balances[] = [
                'leave_type'    => $t['name'],
                'code'          => $t['code'],
                'days_allowed'  => (int)$t['days_allowed'],
                'days_used'     => (int)$used,
                'days_remaining'=> max(0, (int)$t['days_allowed'] - (int)$used),
            ];
        }
        echo json_encode(['data' => $balances]);
        exit;

    // ── GET /api/ec-convert?date=YYYY-MM-DD (Gregorian→EC) ─────
    case 'ec-convert':
        $date = trim(input('date'));
        $direction = input('direction') ?: 'to_ec';
        if (!$date) { echo json_encode(['error' => 'Date required']); exit; }
        if ($direction === 'to_ec') {
            $result = gregorian_str_to_ec($date);
            $display = $result ? ec_format_display($result) : '';
            echo json_encode(['ec' => $result, 'display' => $display]);
        } else {
            $result = ec_str_to_gregorian($date);
            echo json_encode(['gregorian' => $result]);
        }
        exit;

    // ══════════════════════════════════════════════════════════════
    // PHASE 2: ATTENDANCE & BIOMETRIC API ENDPOINTS
    // ══════════════════════════════════════════════════════════════

    // ── POST /api/hr-attendance-mark ────────────────────────────
    // Mark attendance for a single employee
    case 'hr-attendance-mark':
        auth_require_permission('hr.attendance');
        if (!is_post()) { http_response_code(405); echo json_encode(['error' => 'POST required']); exit; }
        $empId = input_int('employee_id');
        $dateGregorian = trim(input('date_gregorian'));
        $checkIn = trim(input('check_in')) ?: null;
        $checkOut = trim(input('check_out')) ?: null;
        $statusOverride = trim(input('status')) ?: null;

        if (!$empId || !$dateGregorian) {
            echo json_encode(['error' => 'employee_id and date_gregorian are required']);
            exit;
        }

        $dateEc = gregorian_str_to_ec($dateGregorian);

        // Auto-calculate status if not manually overridden
        $status = $statusOverride ?: attendance_calculate_status($checkIn, $checkOut);

        // Check for holiday
        if (attendance_is_holiday($dateGregorian)) {
            $status = 'holiday';
        }

        $attData = [
            'check_in'  => $checkIn,
            'check_out' => $checkOut,
            'status'    => $status,
            'source'    => 'manual',
            'marked_by' => auth_user_id(),
        ];

        $existing = db_fetch_one(
            "SELECT id FROM hr_attendance WHERE employee_id = ? AND date_gregorian = ?",
            [$empId, $dateGregorian]
        );

        if ($existing) {
            db_update('hr_attendance', $attData, 'id = ?', [$existing['id']]);
        } else {
            $attData['employee_id'] = $empId;
            $attData['date_ec'] = $dateEc;
            $attData['date_gregorian'] = $dateGregorian;
            db_insert('hr_attendance', $attData);
        }

        echo json_encode(['success' => true, 'status' => $status]);
        exit;

    // ── GET /api/hr-attendance-history?employee_id=X&start=YYYY-MM-DD&end=YYYY-MM-DD ──
    case 'hr-attendance-history':
        auth_require_permission('hr.attendance');
        $empId = input_int('employee_id');
        $start = trim(input('start'));
        $end = trim(input('end'));
        if (!$empId || !$start || !$end) {
            echo json_encode(['error' => 'employee_id, start, and end are required']);
            exit;
        }
        $history = attendance_employee_history($empId, $start, $end);
        echo json_encode(['data' => $history]);
        exit;

    // ── GET /api/hr-attendance-report?month_ec=X&year_ec=X ──────
    case 'hr-attendance-report':
        auth_require_permission('hr.attendance');
        $monthEc = input_int('month_ec');
        $yearEc = input_int('year_ec');
        if (!$monthEc || !$yearEc) {
            echo json_encode(['error' => 'month_ec and year_ec are required']);
            exit;
        }
        $report = attendance_monthly_report($monthEc, $yearEc);
        echo json_encode(['data' => $report]);
        exit;

    // ── POST /api/hr-device-sync ────────────────────────────────
    // Sync attendance from a biometric device
    case 'hr-device-sync':
        auth_require_permission('hr.devices');
        if (!is_post()) { http_response_code(405); echo json_encode(['error' => 'POST required']); exit; }
        $deviceId = input_int('device_id');
        if (!$deviceId) {
            echo json_encode(['error' => 'device_id is required']);
            exit;
        }
        $result = attendance_sync_device($deviceId);
        echo json_encode($result);
        exit;

    // ── GET /api/hr-device-status?device_id=X ───────────────────
    case 'hr-device-status':
        auth_require_permission('hr.devices');
        $deviceId = input_int('device_id');
        if (!$deviceId) { echo json_encode(['error' => 'device_id required']); exit; }
        $device = db_fetch_one(
            "SELECT d.*, 
                    (SELECT COUNT(*) FROM hr_employee_biometric WHERE device_id = d.id AND is_active = 1) AS enrolled_employees,
                    (SELECT COUNT(*) FROM hr_attendance_logs WHERE device_id = d.id AND processed = 0) AS pending_logs
             FROM hr_attendance_devices d
             WHERE d.id = ?",
            [$deviceId]
        );
        if (!$device) { http_response_code(404); echo json_encode(['error' => 'Device not found']); exit; }
        echo json_encode(['data' => $device]);
        exit;

    // ── GET /api/hr-devices ─────────────────────────────────────
    case 'hr-devices':
        auth_require_permission('hr.devices');
        $rows = db_fetch_all(
            "SELECT d.*,
                    (SELECT COUNT(*) FROM hr_employee_biometric WHERE device_id = d.id AND is_active = 1) AS enrolled_employees,
                    (SELECT COUNT(*) FROM hr_attendance_logs WHERE device_id = d.id AND processed = 0) AS pending_logs
             FROM hr_attendance_devices d
             ORDER BY d.device_name"
        );
        echo json_encode(['data' => $rows ?: []]);
        exit;

    // ── POST /api/hr-leave-submit ───────────────────────────────
    // Submit a leave request via API
    case 'hr-leave-submit':
        auth_require_permission('hr.leave');
        if (!is_post()) { http_response_code(405); echo json_encode(['error' => 'POST required']); exit; }

        $empId = input_int('employee_id');
        $leaveTypeId = input_int('leave_type_id');
        $startGreg = trim(input('start_date_gregorian'));
        $endGreg = trim(input('end_date_gregorian'));
        $reason = trim(input('reason'));

        if (!$empId || !$leaveTypeId || !$startGreg || !$endGreg) {
            echo json_encode(['error' => 'employee_id, leave_type_id, start_date_gregorian, end_date_gregorian are required']);
            exit;
        }

        $startEc = gregorian_str_to_ec($startGreg);
        $endEc = gregorian_str_to_ec($endGreg);
        $days = ec_working_days($startGreg, $endGreg);
        if ($days < 1) $days = 1;

        // Validate leave balance
        $leaveType = db_fetch_one("SELECT * FROM hr_leave_types WHERE id = ? AND status = 'active'", [$leaveTypeId]);
        if (!$leaveType) {
            echo json_encode(['error' => 'Invalid leave type']);
            exit;
        }
        if ($leaveType['days_allowed'] > 0) {
            $used = (int)db_fetch_value(
                "SELECT COALESCE(SUM(days), 0) FROM hr_leave_requests
                 WHERE employee_id = ? AND leave_type_id = ? AND status IN ('pending','approved')
                 AND YEAR(start_date_gregorian) = YEAR(CURDATE())",
                [$empId, $leaveTypeId]
            );
            $remaining = (int)$leaveType['days_allowed'] - $used;
            if ($days > $remaining) {
                echo json_encode(['error' => "Insufficient leave balance. Remaining: {$remaining} days."]);
                exit;
            }
        }

        // Check overlap
        $overlap = db_fetch_one(
            "SELECT id FROM hr_leave_requests
             WHERE employee_id = ? AND status IN ('pending','approved')
             AND start_date_gregorian <= ? AND end_date_gregorian >= ?",
            [$empId, $endGreg, $startGreg]
        );
        if ($overlap) {
            echo json_encode(['error' => 'Overlapping leave request exists']);
            exit;
        }

        $id = db_insert('hr_leave_requests', [
            'employee_id'          => $empId,
            'leave_type_id'        => $leaveTypeId,
            'start_date_ec'        => $startEc,
            'start_date_gregorian' => $startGreg,
            'end_date_ec'          => $endEc,
            'end_date_gregorian'   => $endGreg,
            'days'                 => $days,
            'reason'               => $reason ?: null,
            'status'               => 'pending',
            'created_by'           => auth_user_id(),
        ]);

        echo json_encode(['success' => true, 'id' => $id, 'days' => $days]);
        exit;

    // ── POST /api/hr-leave-approve ──────────────────────────────
    case 'hr-leave-approve':
        auth_require_permission('hr.leave');
        if (!is_post()) { http_response_code(405); echo json_encode(['error' => 'POST required']); exit; }

        $id = input_int('id');
        $action = input('leave_action'); // 'approve' or 'reject'
        $reason = trim(input('rejection_reason'));

        if (!$id || !in_array($action, ['approve', 'reject'], true)) {
            echo json_encode(['error' => 'id and leave_action (approve/reject) required']);
            exit;
        }

        $request = db_fetch_one("SELECT * FROM hr_leave_requests WHERE id = ? AND status = 'pending'", [$id]);
        if (!$request) {
            echo json_encode(['error' => 'Leave request not found or already processed']);
            exit;
        }

        if ($action === 'approve') {
            db_update('hr_leave_requests', [
                'status'        => 'approved',
                'approved_by'   => auth_user_id(),
                'approval_date' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);
            echo json_encode(['success' => true, 'status' => 'approved']);
        } else {
            db_update('hr_leave_requests', [
                'status'           => 'rejected',
                'approved_by'      => auth_user_id(),
                'approval_date'    => date('Y-m-d H:i:s'),
                'rejection_reason' => $reason ?: null,
            ], 'id = ?', [$id]);
            echo json_encode(['success' => true, 'status' => 'rejected']);
        }
        exit;

    // ── GET /api/hr-leave-balance-detail?employee_id=X ──────────
    // Detailed leave balance with usage history
    case 'hr-leave-balance-detail':
        auth_require_permission('hr.leave');
        $empId = input_int('employee_id');
        if (!$empId) { echo json_encode(['error' => 'Employee ID required']); exit; }
        $balances = attendance_leave_balance($empId);
        echo json_encode(['data' => $balances]);
        exit;

    // ── GET /api/hr-employee-allowances?employee_id=X ───────────
    case 'hr-employee-allowances':
        auth_require_permission('hr.view');
        $empId = input_int('employee_id');
        if (!$empId) { echo json_encode(['error' => 'Employee ID required']); exit; }
        $rows = db_fetch_all(
            "SELECT * FROM hr_employee_allowances
             WHERE employee_id = ? AND status = 'active'
             ORDER BY allowance_type, name",
            [$empId]
        );
        echo json_encode(['data' => $rows ?: []]);
        exit;

    // ── POST /api/hr-process-biometric ──────────────────────────
    // Process biometric scans for a specific date
    case 'hr-process-biometric':
        auth_require_permission('hr.devices');
        if (!is_post()) { http_response_code(405); echo json_encode(['error' => 'POST required']); exit; }
        $dateGregorian = trim(input('date_gregorian'));
        $deviceId = input_int('device_id') ?: null;
        if (!$dateGregorian) {
            echo json_encode(['error' => 'date_gregorian is required']);
            exit;
        }
        $result = attendance_process_biometric_scans($dateGregorian, $deviceId);
        echo json_encode($result);
        exit;

    // ── POST /api/hr-mark-absent ────────────────────────────────
    // Mark absent for employees without attendance records
    case 'hr-mark-absent':
        auth_require_permission('hr.attendance');
        if (!is_post()) { http_response_code(405); echo json_encode(['error' => 'POST required']); exit; }
        $dateGregorian = trim(input('date_gregorian'));
        if (!$dateGregorian) {
            echo json_encode(['error' => 'date_gregorian is required']);
            exit;
        }
        $count = attendance_mark_absent($dateGregorian);
        echo json_encode(['success' => true, 'marked_absent' => $count]);
        exit;

    // ── GET /api/hr-dashboard-stats ─────────────────────────────
    // Summary stats for reports dashboard charts (JSON)
    case 'hr-dashboard-stats':
        auth_require_permission('hr.reports');
        require_once APP_ROOT . '/core/ethiopian_calendar.php';

        $totalActive = (int)db_fetch_value("SELECT COUNT(*) FROM hr_employees WHERE deleted_at IS NULL AND status='active'");
        $presentToday = (int)db_fetch_value("SELECT COUNT(*) FROM hr_attendance WHERE date_gregorian = CURDATE() AND status='present'");
        $onLeaveToday = (int)db_fetch_value("SELECT COUNT(*) FROM hr_leave_requests WHERE status='approved' AND start_date_gregorian <= CURDATE() AND end_date_gregorian >= CURDATE()");
        $pendingLeave = (int)db_fetch_value("SELECT COUNT(*) FROM hr_leave_requests WHERE status='pending'");

        $payrollTrend = db_fetch_all(
            "SELECT pp.month_ec, pp.year_ec, pp.month_name_ec,
                    SUM(pr.gross_salary) AS gross, SUM(pr.net_salary) AS net, SUM(pr.income_tax) AS tax
             FROM hr_payroll_periods pp
             JOIN hr_payroll_records pr ON pr.payroll_period_id = pp.id
             WHERE pp.status IN ('generated','approved','paid')
             GROUP BY pp.id ORDER BY pp.year_ec DESC, pp.month_ec DESC LIMIT 6"
        );

        $deptSalary = db_fetch_all(
            "SELECT d.name, SUM(e.basic_salary) AS total_salary, COUNT(e.id) AS emp_count
             FROM hr_departments d
             JOIN hr_employees e ON e.department_id = d.id AND e.deleted_at IS NULL AND e.status='active'
             WHERE d.deleted_at IS NULL GROUP BY d.id ORDER BY total_salary DESC"
        );

        echo json_encode([
            'data' => [
                'total_active'    => $totalActive,
                'present_today'   => $presentToday,
                'on_leave_today'  => $onLeaveToday,
                'pending_leave'   => $pendingLeave,
                'payroll_trend'   => array_reverse($payrollTrend),
                'dept_salary'     => $deptSalary,
            ]
        ]);
        exit;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        exit;
}
