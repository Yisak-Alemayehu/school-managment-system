<?php
/**
 * HR — Approve Payroll Period
 */
csrf_protect();

$periodId = input_int('period_id');
$action   = input('payroll_action'); // 'approve' or 'mark_paid'

if (!$periodId) {
    set_flash('error', 'Invalid payroll period.');
    redirect(url('hr', 'payroll'));
}

$period = db_fetch_one("SELECT * FROM hr_payroll_periods WHERE id = ?", [$periodId]);
if (!$period) {
    set_flash('error', 'Payroll period not found.');
    redirect(url('hr', 'payroll'));
}

if ($action === 'approve') {
    if ($period['status'] !== 'generated') {
        set_flash('error', 'Payroll must be in "generated" status to approve.');
        redirect(url('hr', 'payroll-detail', $periodId));
    }

    db_update('hr_payroll_periods', [
        'status'      => 'approved',
        'approved_by' => auth_user_id(),
        'approved_at' => date('Y-m-d H:i:s'),
    ], 'id = ?', [$periodId]);

    audit_log('hr.payroll.approve', "Approved payroll period ID: {$periodId}");
    set_flash('success', 'Payroll approved successfully.');

} elseif ($action === 'mark_paid') {
    if ($period['status'] !== 'approved') {
        set_flash('error', 'Payroll must be approved before marking as paid.');
        redirect(url('hr', 'payroll-detail', $periodId));
    }

    db_transaction(function() use ($periodId) {
        db_update('hr_payroll_periods', [
            'status' => 'paid',
        ], 'id = ?', [$periodId]);

        // Mark all payroll records as paid
        $stmt = "UPDATE hr_payroll_records SET payment_status = 'paid', payment_date = CURDATE() WHERE payroll_period_id = ? AND payment_status = 'pending'";
        db_fetch_one($stmt, [$periodId]); // execute update via query
    });

    audit_log('hr.payroll.paid', "Marked payroll period ID: {$periodId} as paid");
    set_flash('success', 'Payroll marked as paid.');

} else {
    set_flash('error', 'Invalid action.');
}

redirect(url('hr', 'payroll-detail', $periodId));
