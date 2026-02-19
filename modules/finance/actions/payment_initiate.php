<?php
/**
 * Finance — Initiate Online Payment
 */
verify_csrf();

require_once ROOT_PATH . '/core/payment_gateway.php';

$invoiceId = input_int('invoice_id');
$amount    = (float)($_POST['amount'] ?? 0);
$gateway   = $_POST['gateway'] ?? '';

$invoice = db_fetch_one("
    SELECT * FROM invoices WHERE id = ? AND status IN ('unpaid', 'partial')
", [$invoiceId]);

if (!$invoice) {
    set_flash('error', 'Invoice not found or already paid.');
    redirect(url('finance', 'pay-online'));
}

$balance = $invoice['total_amount'] - $invoice['paid_amount'];
if ($amount <= 0 || $amount > $balance) {
    set_flash('error', 'Invalid payment amount.');
    redirect(url('finance', 'pay-online'));
}

$config = gateway_config($gateway);
if (empty($config)) {
    set_flash('error', 'Payment gateway not available.');
    redirect(url('finance', 'pay-online'));
}

// Create transaction record
$txnId = gateway_create_transaction($invoiceId, $amount, $gateway, [
    'initiated_by' => current_user_id(),
]);

$txn = db_fetch_one("SELECT * FROM payment_transactions WHERE id = ?", [$txnId]);

// Load gateway adapter and initiate
$result = ['success' => false, 'error' => 'Unsupported gateway.'];

switch ($gateway) {
    case 'telebirr':
        require_once ROOT_PATH . '/core/gateways/telebirr.php';
        $result = telebirr_initiate($txnId, $amount, $txn['transaction_ref'], $config);
        break;

    case 'chapa':
        require_once ROOT_PATH . '/core/gateways/chapa.php';
        $result = chapa_initiate($txnId, $amount, $txn['transaction_ref'], $config);
        break;

    default:
        gateway_update_transaction($txnId, 'failed', null, ['error' => 'Unsupported gateway']);
        break;
}

if ($result['success'] && $result['redirect_url']) {
    header('Location: ' . $result['redirect_url']);
    exit;
}

set_flash('error', 'Payment initiation failed: ' . ($result['error'] ?? 'Unknown error'));
redirect(url('finance', 'pay-online'));
