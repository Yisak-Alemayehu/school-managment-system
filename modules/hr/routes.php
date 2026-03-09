<?php
/**
 * HR Module — Routes
 * Staff management, departments, attendance, leave, payroll, reports, devices
 */

auth_require();

$action = current_action();

switch ($action) {
    // ══════════════════════════════════════════════════════════
    // DASHBOARD
    // ══════════════════════════════════════════════════════════
    case 'index':
        auth_require_permission('hr.view');
        $pageTitle = 'HR Management';
        require __DIR__ . '/views/index.php';
        break;

    // ══════════════════════════════════════════════════════════
    // EMPLOYEES
    // ══════════════════════════════════════════════════════════
    case 'employees':
        auth_require_permission('hr.employees');
        $pageTitle = 'HR — Employees';
        require __DIR__ . '/views/employees.php';
        break;

    case 'employee-detail':
        auth_require_permission('hr.employees');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('hr', 'employees')); }
        $pageTitle = 'Employee Detail';
        require __DIR__ . '/views/employee_detail.php';
        break;

    case 'employee-form':
        auth_require_permission('hr.manage');
        $id = route_id() ?: input_int('id');
        $pageTitle = $id ? 'Edit Employee' : 'Add Employee';
        require __DIR__ . '/views/employee_form.php';
        break;

    case 'employee-save':
        auth_require_permission('hr.manage');
        if (is_post()) { require __DIR__ . '/actions/employee_save.php'; }
        else { redirect(url('hr', 'employees')); }
        break;

    case 'employee-delete':
        auth_require_permission('hr.manage');
        if (is_post()) { require __DIR__ . '/actions/employee_delete.php'; }
        else { redirect(url('hr', 'employees')); }
        break;

    case 'employee-document-save':
        auth_require_permission('hr.manage');
        if (is_post()) { require __DIR__ . '/actions/employee_document_save.php'; }
        else { redirect(url('hr', 'employees')); }
        break;

    case 'employee-document-delete':
        auth_require_permission('hr.manage');
        if (is_post()) { require __DIR__ . '/actions/employee_document_delete.php'; }
        else { redirect(url('hr', 'employees')); }
        break;

    // ══════════════════════════════════════════════════════════
    // EMPLOYEE ALLOWANCES
    // ══════════════════════════════════════════════════════════
    case 'allowance-save':
        auth_require_permission('hr.manage');
        if (is_post()) { require __DIR__ . '/actions/allowance_save.php'; }
        else { redirect(url('hr', 'employees')); }
        break;

    case 'allowance-delete':
        auth_require_permission('hr.manage');
        if (is_post()) { require __DIR__ . '/actions/allowance_delete.php'; }
        else { redirect(url('hr', 'employees')); }
        break;

    // ══════════════════════════════════════════════════════════
    // DEPARTMENTS
    // ══════════════════════════════════════════════════════════
    case 'departments':
        auth_require_permission('hr.departments');
        $pageTitle = 'HR — Departments';
        require __DIR__ . '/views/departments.php';
        break;

    case 'department-save':
        auth_require_permission('hr.departments');
        if (is_post()) { require __DIR__ . '/actions/department_save.php'; }
        else { redirect(url('hr', 'departments')); }
        break;

    case 'department-delete':
        auth_require_permission('hr.departments');
        if (is_post()) { require __DIR__ . '/actions/department_delete.php'; }
        else { redirect(url('hr', 'departments')); }
        break;

    // ══════════════════════════════════════════════════════════
    // ATTENDANCE
    // ══════════════════════════════════════════════════════════
    case 'attendance':
        auth_require_permission('hr.attendance');
        $pageTitle = 'HR — Staff Attendance';
        require __DIR__ . '/views/attendance.php';
        break;

    case 'attendance-mark':
        auth_require_permission('hr.attendance');
        if (is_post()) { require __DIR__ . '/actions/attendance_mark.php'; }
        else { redirect(url('hr', 'attendance')); }
        break;

    case 'attendance-report':
        auth_require_permission('hr.attendance');
        $pageTitle = 'Attendance Report';
        require __DIR__ . '/views/attendance_report.php';
        break;

    case 'attendance-process-biometric':
        auth_require_permission('hr.attendance');
        if (is_post()) { require __DIR__ . '/actions/biometric_process.php'; }
        else { redirect(url('hr', 'attendance')); }
        break;

    case 'attendance-mark-absent':
        auth_require_permission('hr.attendance');
        if (is_post()) { require __DIR__ . '/actions/attendance_mark_absent.php'; }
        else { redirect(url('hr', 'attendance')); }
        break;

    // ══════════════════════════════════════════════════════════
    // LEAVE MANAGEMENT
    // ══════════════════════════════════════════════════════════
    case 'leave-types':
        auth_require_permission('hr.leave');
        $pageTitle = 'Leave Types';
        require __DIR__ . '/views/leave_types.php';
        break;

    case 'leave-type-save':
        auth_require_permission('hr.leave');
        if (is_post()) { require __DIR__ . '/actions/leave_type_save.php'; }
        else { redirect(url('hr', 'leave-types')); }
        break;

    case 'holidays':
        auth_require_permission('hr.leave');
        $pageTitle = 'Holidays';
        require __DIR__ . '/views/holidays.php';
        break;

    case 'holiday-save':
        auth_require_permission('hr.leave');
        if (is_post()) { require __DIR__ . '/actions/holiday_save.php'; }
        else { redirect(url('hr', 'holidays')); }
        break;

    case 'leave-requests':
        auth_require_permission('hr.leave');
        $pageTitle = 'Leave Requests';
        require __DIR__ . '/views/leave_requests.php';
        break;

    case 'leave-request-form':
        auth_require_permission('hr.leave');
        $pageTitle = 'Submit Leave Request';
        require __DIR__ . '/views/leave_request_form.php';
        break;

    case 'leave-balances':
        auth_require_permission('hr.leave');
        $pageTitle = 'Leave Balances';
        require __DIR__ . '/views/leave_balances.php';
        break;

    case 'leave-request-save':
        auth_require_permission('hr.leave');
        if (is_post()) { require __DIR__ . '/actions/leave_request_save.php'; }
        else { redirect(url('hr', 'leave-requests')); }
        break;

    case 'leave-approve':
        auth_require_permission('hr.leave');
        if (is_post()) { require __DIR__ . '/actions/leave_approve.php'; }
        else { redirect(url('hr', 'leave-requests')); }
        break;

    // ══════════════════════════════════════════════════════════
    // PAYROLL
    // ══════════════════════════════════════════════════════════
    case 'payroll':
        auth_require_permission('hr.payroll');
        $pageTitle = 'HR — Payroll Periods';
        require __DIR__ . '/views/payroll.php';
        break;

    case 'payroll-period-save':
        auth_require_permission('hr.payroll');
        if (is_post()) { require __DIR__ . '/actions/payroll_period_save.php'; }
        else { redirect(url('hr', 'payroll')); }
        break;

    case 'payroll-generate':
        auth_require_permission('hr.payroll');
        if (is_post()) { require __DIR__ . '/actions/payroll_generate.php'; }
        else { redirect(url('hr', 'payroll')); }
        break;

    case 'payroll-approve':
        auth_require_permission('hr.payroll_approve');
        if (is_post()) { require __DIR__ . '/actions/payroll_approve.php'; }
        else { redirect(url('hr', 'payroll')); }
        break;

    case 'payroll-detail':
        auth_require_permission('hr.payroll');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('hr', 'payroll')); }
        $pageTitle = 'Payroll Detail';
        require __DIR__ . '/views/payroll_detail.php';
        break;

    case 'payslip':
        auth_require_permission('hr.payroll');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('hr', 'payroll')); }
        $pageTitle = 'Pay Slip';
        require __DIR__ . '/views/payslip.php';
        break;

    case 'payroll-bank-sheet':
        auth_require_permission('hr.payroll');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('hr', 'payroll')); }
        $pageTitle = 'Bank Transfer Sheet';
        require __DIR__ . '/views/payroll_bank_sheet.php';
        break;

    case 'payroll-pension-sheet':
        auth_require_permission('hr.payroll');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('hr', 'payroll')); }
        $pageTitle = 'Pension Report Sheet';
        require __DIR__ . '/views/payroll_pension_sheet.php';
        break;

    // ══════════════════════════════════════════════════════════
    // REPORTS
    // ══════════════════════════════════════════════════════════
    case 'reports':
        auth_require_permission('hr.reports');
        $pageTitle = 'HR Reports';
        require __DIR__ . '/views/reports.php';
        break;

    case 'reports-dashboard':
        auth_require_permission('hr.reports');
        $pageTitle = 'HR Reports Dashboard';
        require __DIR__ . '/views/reports_dashboard.php';
        break;

    // ══════════════════════════════════════════════════════════
    // PAYROLL PRINTING HUB
    // ══════════════════════════════════════════════════════════
    case 'payroll-printing':
        auth_require_permission('hr.print');
        $pageTitle = 'Payroll Printing Hub';
        require __DIR__ . '/views/payroll_printing.php';
        break;

    // ══════════════════════════════════════════════════════════
    // BIOMETRIC DEVICES
    // ══════════════════════════════════════════════════════════
    case 'devices':
        auth_require_permission('hr.devices');
        $pageTitle = 'Attendance Devices';
        require __DIR__ . '/views/devices.php';
        break;

    case 'device-save':
        auth_require_permission('hr.devices');
        if (is_post()) { require __DIR__ . '/actions/device_save.php'; }
        else { redirect(url('hr', 'devices')); }
        break;

    case 'device-sync':
        auth_require_permission('hr.devices');
        if (is_post()) { require __DIR__ . '/actions/device_sync.php'; }
        else { redirect(url('hr', 'devices')); }
        break;

    // ══════════════════════════════════════════════════════════
    // PDF GENERATION — Phase 3
    // ══════════════════════════════════════════════════════════
    case 'print-tax':
        auth_require_permission('hr.print');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('hr', 'payroll')); }
        require_once APP_ROOT . '/core/pdf_tax.php';
        pdf_income_tax($id, 'I');
        exit;

    case 'print-pension':
        auth_require_permission('hr.print');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('hr', 'payroll')); }
        require_once APP_ROOT . '/core/pdf_pension.php';
        pdf_pension($id, 'I');
        exit;

    case 'print-bank':
        auth_require_permission('hr.print');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('hr', 'payroll')); }
        require_once APP_ROOT . '/core/pdf_bank.php';
        pdf_bank_transfer($id, 'I');
        exit;

    case 'print-contract':
        auth_require_permission('hr.print');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('hr', 'employees')); }
        require_once APP_ROOT . '/core/pdf_contract.php';
        pdf_contract($id, 'I');
        exit;

    case 'print-payslip':
        auth_require_permission('hr.print');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('hr', 'payroll')); }
        require_once APP_ROOT . '/core/pdf_payslip.php';
        pdf_payslip($id, 'I');
        exit;

    case 'download-tax':
        auth_require_permission('hr.print');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('hr', 'payroll')); }
        require_once APP_ROOT . '/core/pdf_tax.php';
        pdf_income_tax($id, 'D');
        exit;

    case 'download-pension':
        auth_require_permission('hr.print');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('hr', 'payroll')); }
        require_once APP_ROOT . '/core/pdf_pension.php';
        pdf_pension($id, 'D');
        exit;

    case 'download-bank':
        auth_require_permission('hr.print');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('hr', 'payroll')); }
        require_once APP_ROOT . '/core/pdf_bank.php';
        pdf_bank_transfer($id, 'D');
        exit;

    case 'download-contract':
        auth_require_permission('hr.print');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('hr', 'employees')); }
        require_once APP_ROOT . '/core/pdf_contract.php';
        pdf_contract($id, 'D');
        exit;

    case 'download-payslip':
        auth_require_permission('hr.print');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('hr', 'payroll')); }
        require_once APP_ROOT . '/core/pdf_payslip.php';
        pdf_payslip($id, 'D');
        exit;

    // ══════════════════════════════════════════════════════════
    // DEFAULT
    // ══════════════════════════════════════════════════════════
    default:
        auth_require_permission('hr.view');
        $pageTitle = 'HR Management';
        require __DIR__ . '/views/index.php';
        break;
}
