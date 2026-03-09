<?php
/**
 * HR — Save Payroll Period
 */
csrf_protect();
require_once APP_ROOT . '/core/ethiopian_calendar.php';

$id = input_int('id');

$monthEc  = input_int('month_ec');
$yearEc   = input_int('year_ec');

// Compute Gregorian start/end from EC month/year
$startEc  = ec_to_gregorian(1, $monthEc, $yearEc);
$lastDay  = ec_days_in_month($monthEc, $yearEc);
$endEc    = ec_to_gregorian($lastDay, $monthEc, $yearEc);

$data = [
    'month_ec'        => $monthEc,
    'year_ec'         => $yearEc,
    'month_gregorian' => (int)date('n', strtotime($startEc['date'])),
    'year_gregorian'  => (int)date('Y', strtotime($startEc['date'])),
    'start_date'      => $startEc['date'],
    'end_date'        => $endEc['date'],
    'notes'           => trim(input('notes')) ?: null,
];

$errors = [];

if ($monthEc < 1 || $monthEc > 13) {
    $errors['month_ec'] = 'Invalid Ethiopian month.';
}
if ($yearEc < 2000) {
    $errors['year_ec'] = 'Invalid Ethiopian year.';
}

// Check duplicate period
if (!$errors) {
    $dup = db_fetch_one(
        "SELECT id FROM hr_payroll_periods WHERE month_ec = ? AND year_ec = ?" . ($id ? " AND id != ?" : ""),
        $id ? [$monthEc, $yearEc, $id] : [$monthEc, $yearEc]
    );
    if ($dup) {
        $errors['month_ec'] = 'A payroll period already exists for this month.';
    }
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

if ($id) {
    db_update('hr_payroll_periods', $data, 'id = ?', [$id]);
    audit_log('hr.payroll_period.update', "Updated payroll period: {$monthEc}/{$yearEc} EC");
    set_flash('success', 'Payroll period updated.');
} else {
    $data['status'] = 'draft';
    db_insert('hr_payroll_periods', $data);
    audit_log('hr.payroll_period.create', "Created payroll period: {$monthEc}/{$yearEc} EC");
    set_flash('success', 'Payroll period created.');
}

redirect(url('hr', 'payroll'));
