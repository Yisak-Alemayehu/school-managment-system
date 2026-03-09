<?php
/**
 * HR — Generate Payroll for a Period
 * Uses the payroll calculation service from core/payroll.php
 */
csrf_protect();

require_once APP_ROOT . '/core/payroll.php';
require_once APP_ROOT . '/core/ethiopian_calendar.php';

$periodId = input_int('period_id');

if (!$periodId) {
    set_flash('error', 'Invalid payroll period.');
    redirect(url('hr', 'payroll'));
}

$period = db_fetch_one("SELECT * FROM hr_payroll_periods WHERE id = ?", [$periodId]);
if (!$period) {
    set_flash('error', 'Payroll period not found.');
    redirect(url('hr', 'payroll'));
}

if ($period['status'] !== 'draft') {
    set_flash('error', 'Payroll has already been generated for this period.');
    redirect(url('hr', 'payroll-detail', $periodId));
}

// Run payroll generation
$result = payroll_generate($periodId);

if (!empty($result['errors'])) {
    set_flash('error', implode(' ', $result['errors']));
    redirect(url('hr', 'payroll'));
}

$monthName = ec_month_name($period['month_ec']);
audit_log('hr.payroll.generate', "Generated payroll for {$monthName} {$period['year_ec']} EC — {$result['success']} employees");
set_flash('success', "Payroll generated for {$result['success']} employee(s) — {$monthName} {$period['year_ec']} EC.");

redirect(url('hr', 'payroll-detail', $periodId));
