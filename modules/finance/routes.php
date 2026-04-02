<?php
/**
 * Finance Module — Routes
 * Fee management, payments, grouping, reports
 */

auth_require();

$action = current_action();

switch ($action) {
    // ══════════════════════════════════════════════════════════
    // MANAGE STUDENTS
    // ══════════════════════════════════════════════════════════
    case 'index':
    case 'students':
        auth_require_permission('finance.view');
        $pageTitle = 'Finance — Students';
        require __DIR__ . '/views/students.php';
        break;

    case 'student-detail':
        auth_require_permission('finance.view');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('finance', 'students')); }
        $pageTitle = 'Student Finance Detail';
        require __DIR__ . '/views/student_detail.php';
        break;

    // ── Student fee actions ──
    case 'assign-fee':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/assign_fee.php'; }
        else { redirect(url('finance', 'students')); }
        break;

    case 'remove-fee':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/remove_fee.php'; }
        else { redirect(url('finance', 'students')); }
        break;

    case 'adjust-balance':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/adjust_balance.php'; }
        else { redirect(url('finance', 'students')); }
        break;

    case 'make-payment':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/make_payment.php'; }
        else { redirect(url('finance', 'students')); }
        break;

    case 'refund':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/refund.php'; }
        else { redirect(url('finance', 'students')); }
        break;

    // ══════════════════════════════════════════════════════════
    // GROUPING
    // ══════════════════════════════════════════════════════════
    case 'groups':
        auth_require_permission('finance.view');
        $pageTitle = 'Finance — Groups';
        require __DIR__ . '/views/groups.php';
        break;

    case 'group-detail':
        auth_require_permission('finance.view');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('finance', 'groups')); }
        $pageTitle = 'Group Detail';
        require __DIR__ . '/views/group_detail.php';
        break;

    case 'group-save':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/group_save.php'; }
        else { redirect(url('finance', 'groups')); }
        break;

    case 'group-assign-members':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/group_assign_members.php'; }
        else { redirect(url('finance', 'groups')); }
        break;

    case 'group-remove-members':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/group_remove_members.php'; }
        else { redirect(url('finance', 'groups')); }
        break;

    case 'group-action':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/group_action.php'; }
        else { redirect(url('finance', 'groups')); }
        break;

    case 'group-update':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/group_update.php'; }
        else { redirect(url('finance', 'groups')); }
        break;

    case 'group-delete':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/group_delete.php'; }
        else { redirect(url('finance', 'groups')); }
        break;

    // ══════════════════════════════════════════════════════════
    // COLLECT PAYMENT
    // ══════════════════════════════════════════════════════════
    case 'collect-payment':
        auth_require_permission('finance.manage');
        $pageTitle = 'Collect Payment';
        require __DIR__ . '/views/collect_payment.php';
        break;

    case 'collect-payment-save':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/collect_payment_save.php'; }
        else { redirect(url('finance', 'collect-payment')); }
        break;

    case 'collect-payment-batch-receipt':
        auth_require_permission('finance.view');
        $pageTitle = 'Batch Payment Receipt';
        require __DIR__ . '/views/collect_payment_batch_receipt.php';
        break;

    case 'batch-payment-attachment':
        auth_require_permission('finance.view');
        require __DIR__ . '/actions/batch_payment_attachment.php';
        break;

    case 'collect-supplementary-payment':
        auth_require_permission('finance.manage');
        $pageTitle = 'Collect Supplementary Payment';
        require __DIR__ . '/views/collect_supplementary_payment.php';
        break;

    case 'collect-supplementary-payment-save':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/collect_supplementary_payment_save.php'; }
        else { redirect(url('finance', 'collect-supplementary-payment')); }
        break;

    case 'payment-attachment':
        auth_require_permission('finance.view');
        require __DIR__ . '/actions/payment_attachment.php';
        break;

    // ══════════════════════════════════════════════════════════
    // PAYMENTS
    // ══════════════════════════════════════════════════════════
    case 'payments':
        auth_require_permission('finance.view');
        $pageTitle = 'School Payment History';
        require __DIR__ . '/views/payments.php';
        break;

    case 'supplementary-payments':
        auth_require_permission('finance.view');
        $pageTitle = 'Supplementary Fee Payment History';
        require __DIR__ . '/views/supplementary_payments.php';
        break;

    // ══════════════════════════════════════════════════════════
    // FEE / TUITION MANAGEMENT
    // ══════════════════════════════════════════════════════════
    case 'fee-due':
        auth_require_permission('finance.view');
        $pageTitle = 'Fees Due';
        require __DIR__ . '/views/fee_due.php';
        break;

    case 'fee-save':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/fee_save.php'; }
        else { redirect(url('finance', 'fee-due')); }
        break;

    case 'fee-update':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/fee_update.php'; }
        else { redirect(url('finance', 'fee-due')); }
        break;

    case 'fee-toggle':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/fee_toggle.php'; }
        else { redirect(url('finance', 'fee-due')); }
        break;

    case 'fee-detail':
        auth_require_permission('finance.view');
        $id = route_id() ?: input_int('id');
        if (!$id) { redirect(url('finance', 'fee-due')); }
        $pageTitle = 'Fee Detail';
        require __DIR__ . '/views/fee_detail.php';
        break;

    case 'supplementary-fees':
        auth_require_permission('finance.view');
        $pageTitle = 'Supplementary Fees';
        require __DIR__ . '/views/supplementary_fees.php';
        break;

    case 'supplementary-fee-save':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/supplementary_fee_save.php'; }
        else { redirect(url('finance', 'supplementary-fees')); }
        break;

    case 'supplementary-fee-update':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/supplementary_fee_update.php'; }
        else { redirect(url('finance', 'supplementary-fees')); }
        break;

    case 'supplementary-fee-toggle':
        auth_require_permission('finance.manage');
        if (is_post()) { require __DIR__ . '/actions/supplementary_fee_toggle.php'; }
        else { redirect(url('finance', 'supplementary-fees')); }
        break;

    // ══════════════════════════════════════════════════════════
    // REPORT CENTER
    // ══════════════════════════════════════════════════════════
    case 'report-students':
        auth_require_permission('finance.reports');
        $pageTitle = 'Student Info Report';
        require __DIR__ . '/views/report_students.php';
        break;

    case 'report-penalty':
        auth_require_permission('finance.reports');
        $pageTitle = 'Penalty Report';
        require __DIR__ . '/views/report_penalty.php';
        break;

    case 'report-supplementary':
        auth_require_permission('finance.reports');
        $pageTitle = 'Supplementary Transaction Report';
        require __DIR__ . '/views/report_supplementary.php';
        break;

    case 'report-generate':
        auth_require_permission('finance.reports');
        if (is_post()) { require __DIR__ . '/actions/report_generate.php'; }
        else { redirect(url('finance', 'report-students')); }
        break;

    // ── Export / Downloads ──
    case 'export-pdf':
        auth_require_permission('finance.view');
        require __DIR__ . '/actions/export_pdf.php';
        break;

    case 'export-excel':
        auth_require_permission('finance.view');
        require __DIR__ . '/actions/export_excel.php';
        break;

    case 'apply-penalties':
        auth_require_permission('finance.manage');
        require __DIR__ . '/actions/apply_penalties.php';
        break;

    default:
        auth_require_permission('finance.view');
        $pageTitle = 'Finance — Dashboard';
        require __DIR__ . '/views/dashboard.php';
        break;
}
