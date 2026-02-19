<?php
/**
 * Finance Module Routes
 * Handles: fee categories, fee structures, invoices, payments, discounts, online payment
 */

$action = $_GET['action'] ?? 'invoices';

switch ($action) {
    // ── Fee Categories ──
    case 'fee-categories':
        require_permission('manage_finance');
        require __DIR__ . '/views/fee_categories.php';
        break;
    case 'fee-category-save':
        require_permission('manage_finance');
        require __DIR__ . '/actions/fee_category_save.php';
        break;
    case 'fee-category-delete':
        require_permission('manage_finance');
        require __DIR__ . '/actions/fee_category_delete.php';
        break;

    // ── Fee Structures ──
    case 'fee-structures':
        require_permission('manage_finance');
        require __DIR__ . '/views/fee_structures.php';
        break;
    case 'fee-structure-create':
    case 'fee-structure-edit':
        require_permission('manage_finance');
        require __DIR__ . '/views/fee_structure_form.php';
        break;
    case 'fee-structure-save':
        require_permission('manage_finance');
        require __DIR__ . '/actions/fee_structure_save.php';
        break;
    case 'fee-structure-delete':
        require_permission('manage_finance');
        require __DIR__ . '/actions/fee_structure_delete.php';
        break;

    // ── Invoices ──
    case 'invoices':
        require_permission('manage_finance');
        require __DIR__ . '/views/invoices.php';
        break;
    case 'invoice-create':
        require_permission('manage_finance');
        require __DIR__ . '/views/invoice_form.php';
        break;
    case 'invoice-generate':
        require_permission('manage_finance');
        require __DIR__ . '/actions/invoice_generate.php';
        break;
    case 'invoice-view':
        require_permission('manage_finance');
        require __DIR__ . '/views/invoice_view.php';
        break;
    case 'invoice-print':
        require_permission('manage_finance');
        require __DIR__ . '/views/invoice_print.php';
        break;
    case 'invoice-delete':
        require_permission('manage_finance');
        require __DIR__ . '/actions/invoice_delete.php';
        break;

    // ── Payments ──
    case 'payments':
        require_permission('manage_finance');
        require __DIR__ . '/views/payments.php';
        break;
    case 'payment-record':
        require_permission('manage_finance');
        require __DIR__ . '/views/payment_form.php';
        break;
    case 'payment-save':
        require_permission('manage_finance');
        require __DIR__ . '/actions/payment_save.php';
        break;
    case 'payment-receipt':
        require_permission('manage_finance');
        require __DIR__ . '/views/payment_receipt.php';
        break;

    // ── Fee Discounts ──
    case 'discounts':
        require_permission('manage_finance');
        require __DIR__ . '/views/discounts.php';
        break;
    case 'discount-create':
    case 'discount-edit':
        require_permission('manage_finance');
        require __DIR__ . '/views/discount_form.php';
        break;
    case 'discount-save':
        require_permission('manage_finance');
        require __DIR__ . '/actions/discount_save.php';
        break;
    case 'discount-delete':
        require_permission('manage_finance');
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
        require_permission('manage_finance');
        require __DIR__ . '/views/fee_report.php';
        break;

    default:
        http_response_code(404);
        require ROOT_PATH . '/templates/errors/404.php';
}
