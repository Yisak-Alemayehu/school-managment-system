<?php
/**
 * Finance Module Routes
 * Handles: fee categories, fee structures, invoices, payments, discounts, online payment,
 *          AND the new Fee Management System (fees, assignments, groups, reports)
 */

$action = $_GET['action'] ?? current_action();
if ($action === 'index') $action = 'invoices'; // default landing

switch ($action) {

    // ══════════════════════════════════════════════════════════
    // ── NEW FEE MANAGEMENT SYSTEM ────────────────────────────
    // ══════════════════════════════════════════════════════════

    // ── Generate Invoice (Professional Print) ──
    case 'fm-generate-invoice':
        auth_require_permission('fee_management.view_dashboard');
        require __DIR__ . '/views/fm_generate_invoice.php';
        break;

    // ── Fee Management Dashboard ──
    case 'fm-dashboard':
        auth_require_permission('fee_management.view_dashboard');
        require __DIR__ . '/views/fm_dashboard.php';
        break;

    // ── Create / Edit Fee ──
    case 'fm-create-fee':
    case 'fm-edit-fee':
        auth_require_permission('fee_management.create_fee');
        require __DIR__ . '/views/fm_fee_form.php';
        break;
    case 'fm-fee-save':
        auth_require_permission('fee_management.create_fee');
        require __DIR__ . '/actions/fm_fee_save.php';
        break;

    // ── Manage Fees ──
    case 'fm-manage-fees':
        auth_require_permission('fee_management.view_dashboard');
        require __DIR__ . '/views/fm_manage_fees.php';
        break;
    case 'fm-fee-toggle':
        auth_require_permission('fee_management.activate_fee');
        require __DIR__ . '/actions/fm_fee_toggle.php';
        break;
    case 'fm-fee-delete':
        auth_require_permission('fee_management.delete_fee');
        require __DIR__ . '/actions/fm_fee_delete.php';
        break;
    case 'fm-fee-duplicate':
        auth_require_permission('fee_management.create_fee');
        require __DIR__ . '/actions/fm_fee_duplicate.php';
        break;
    case 'fm-fee-view':
        auth_require_permission('fee_management.view_dashboard');
        require __DIR__ . '/views/fm_fee_view.php';
        break;

    // ── Assign Fees ──
    case 'fm-assign-fees':
        auth_require_permission('fee_management.assign_fee');
        require __DIR__ . '/views/fm_assign_fees.php';
        break;
    case 'fm-assignment-save':
        auth_require_permission('fee_management.assign_fee');
        require __DIR__ . '/actions/fm_assignment_save.php';
        break;
    case 'fm-assignment-delete':
        auth_require_permission('fee_management.assign_fee');
        require __DIR__ . '/actions/fm_assignment_delete.php';
        break;
    case 'fm-exemption-save':
        auth_require_permission('fee_management.manage_exemptions');
        require __DIR__ . '/actions/fm_exemption_save.php';
        break;
    case 'fm-exemption-delete':
        auth_require_permission('fee_management.manage_exemptions');
        require __DIR__ . '/actions/fm_exemption_delete.php';
        break;

    // ── Student Groups ──
    case 'fm-groups':
        auth_require_permission('fee_management.manage_groups');
        require __DIR__ . '/views/fm_groups.php';
        break;
    case 'fm-group-form':
        auth_require_permission('fee_management.manage_groups');
        require __DIR__ . '/views/fm_group_form.php';
        break;
    case 'fm-group-save':
        auth_require_permission('fee_management.manage_groups');
        require __DIR__ . '/actions/fm_group_save.php';
        break;
    case 'fm-group-delete':
        auth_require_permission('fee_management.manage_groups');
        require __DIR__ . '/actions/fm_group_delete.php';
        break;
    case 'fm-group-members':
        auth_require_permission('fee_management.manage_groups');
        require __DIR__ . '/views/fm_group_members.php';
        break;
    case 'fm-group-member-add':
        auth_require_permission('fee_management.manage_groups');
        require __DIR__ . '/actions/fm_group_member_add.php';
        break;
    case 'fm-group-member-remove':
        auth_require_permission('fee_management.manage_groups');
        require __DIR__ . '/actions/fm_group_member_remove.php';
        break;

    // ── Fee Reports ──
    case 'fm-reports':
        auth_require_permission('fee_management.view_reports');
        require __DIR__ . '/views/fm_reports.php';
        break;
    case 'fm-report-export':
        auth_require_permission('fee_management.export_reports');
        require __DIR__ . '/actions/fm_report_export.php';
        break;

    // ── AJAX endpoints ──
    case 'fm-api-students':
        auth_require_permission('fee_management.assign_fee');
        require __DIR__ . '/actions/fm_api_students.php';
        break;
    case 'fm-api-fee-students':
        auth_require_permission('fee_management.assign_fee');
        require __DIR__ . '/actions/fm_api_fee_students.php';
        break;
    case 'fm-charge-waive':
        auth_require_permission('fee_management.manage_charges');
        require __DIR__ . '/actions/fm_charge_waive.php';
        break;

    // ── Record Payment (Fee Charges → Invoice → Payment) ──
    case 'fm-payment':
        auth_require_permission('fee_management.view_dashboard');
        require __DIR__ . '/views/fm_payment.php';
        break;
    case 'fm-payment-save':
        auth_require_permission('fee_management.manage_charges');
        require __DIR__ . '/actions/fm_payment_save.php';
        break;

    // ══════════════════════════════════════════════════════════
    // ── EXISTING FINANCE ROUTES (unchanged) ──────────────────
    // ══════════════════════════════════════════════════════════

    // ── Fee Categories ──
    case 'fee-categories':
        auth_require_permission('finance.view');
        require __DIR__ . '/views/fee_categories.php';
        break;
    case 'fee-category-save':
        auth_require_permission('finance.create');
        require __DIR__ . '/actions/fee_category_save.php';
        break;
    case 'fee-category-delete':
        auth_require_permission('finance.delete');
        require __DIR__ . '/actions/fee_category_delete.php';
        break;

    // ── Fee Structures ──
    case 'fee-structures':
        auth_require_permission('finance.view');
        require __DIR__ . '/views/fee_structures.php';
        break;
    case 'fee-structure-create':
    case 'fee-structure-edit':
        auth_require_permission('finance.create');
        require __DIR__ . '/views/fee_structure_form.php';
        break;
    case 'fee-structure-save':
        auth_require_permission('finance.create');
        require __DIR__ . '/actions/fee_structure_save.php';
        break;
    case 'fee-structure-delete':
        auth_require_permission('finance.delete');
        require __DIR__ . '/actions/fee_structure_delete.php';
        break;

    // ── Invoices ──
    case 'invoices':
        auth_require_permission('finance.view');
        require __DIR__ . '/views/invoices.php';
        break;
    case 'invoice-create':
        auth_require_permission('finance.create');
        require __DIR__ . '/views/invoice_form.php';
        break;
    case 'invoice-generate':
        auth_require_permission('finance.create');
        require __DIR__ . '/actions/invoice_generate.php';
        break;
    case 'invoice-view':
        auth_require_permission('finance.view');
        require __DIR__ . '/views/invoice_view.php';
        break;
    case 'invoice-print':
        auth_require_permission('finance.view');
        require __DIR__ . '/views/invoice_print.php';
        break;
    case 'invoice-delete':
        auth_require_permission('finance.delete');
        require __DIR__ . '/actions/invoice_delete.php';
        break;

    // ── Payments ──
    case 'payments':
        auth_require_permission('finance.view');
        require __DIR__ . '/views/payments.php';
        break;
    case 'payment-record':
        auth_require_permission('finance.payment');
        require __DIR__ . '/views/payment_form.php';
        break;
    case 'payment-save':
        auth_require_permission('finance.payment');
        require __DIR__ . '/actions/payment_save.php';
        break;
    case 'payment-receipt':
        auth_require_permission('finance.view');
        require __DIR__ . '/views/payment_receipt.php';
        break;

    // ── Fee Discounts ──
    case 'discounts':
        auth_require_permission('finance.view');
        require __DIR__ . '/views/discounts.php';
        break;
    case 'discount-create':
    case 'discount-edit':
        auth_require_permission('finance.create');
        require __DIR__ . '/views/discount_form.php';
        break;
    case 'discount-save':
        auth_require_permission('finance.create');
        require __DIR__ . '/actions/discount_save.php';
        break;
    case 'discount-delete':
        auth_require_permission('finance.delete');
        require __DIR__ . '/actions/discount_delete.php';
        break;

    // ── Online Payment ──
    case 'pay-online':
        // Students/parents can access this
        require __DIR__ . '/views/pay_online.php';
        break;
    case 'payment-initiate':
        require __DIR__ . '/actions/payment_initiate.php';
        break;
    case 'payment-callback':
        require __DIR__ . '/actions/payment_callback.php';
        break;

    // ── Fee Report ──
    case 'fee-report':
        auth_require_permission('finance.view');
        require __DIR__ . '/views/fee_report.php';
        break;

    default:
        http_response_code(404);
        require ROOT_PATH . '/templates/errors/404.php';
}
