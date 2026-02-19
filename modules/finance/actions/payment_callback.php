<?php
/**
 * Finance — Payment Gateway Callback / Return URL handler
 * Handles both redirect returns and server-to-server notifications
 */
require_once ROOT_PATH . '/core/payment_gateway.php';

$gateway = $_GET['gateway'] ?? '';
$txnId   = input_int('txn_id');
$isNotify = isset($_GET['notify']);

// For notification callbacks, we don't require user session
if ($isNotify) {
    // Log the raw callback data
    $rawInput = file_get_contents('php://input');
    $callbackData = json_decode($rawInput, true) ?: $_POST ?: $_GET;

    if (!$txnId && !empty($callbackData)) {
        // Try to find txn from the callback data
        $ref = $callbackData['outTradeNo'] ?? $callbackData['tx_ref'] ?? $callbackData['transaction_ref'] ?? '';
        if ($ref) {
            $txnRecord = db_fetch_one("SELECT id FROM payment_transactions WHERE transaction_ref = ?", [$ref]);
            $txnId = $txnRecord['id'] ?? 0;
        }
    }
}

if (!$txnId) {
    if ($isNotify) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing transaction ID']);
        exit;
    }
    set_flash('error', 'Invalid payment callback.');
    redirect(url('finance', 'pay-online'));
}

$txn = db_fetch_one("SELECT * FROM payment_transactions WHERE id = ?", [$txnId]);
if (!$txn) {
    if ($isNotify) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Transaction not found']);
        exit;
    }
    set_flash('error', 'Transaction not found.');
    redirect(url('finance', 'pay-online'));
}

// Skip if already processed
if ($txn['status'] === 'completed') {
    if ($isNotify) {
        echo json_encode(['status' => 'ok']);
        exit;
    }
    set_flash('success', 'Payment already confirmed.');
    redirect(url('finance', 'pay-online'));
}

$config   = gateway_config($gateway);
$verified = false;
$gatewayRef = null;

switch ($gateway) {
    case 'telebirr':
        require_once ROOT_PATH . '/core/gateways/telebirr.php';
        $data = $isNotify ? ($callbackData ?? []) : $_GET;
        $result = telebirr_verify($data, $config);
        $verified   = $result['verified'];
        $gatewayRef = $result['gateway_ref'];
        break;

    case 'chapa':
        require_once ROOT_PATH . '/core/gateways/chapa.php';
        $result = chapa_verify($txn['transaction_ref'], $config);
        $verified   = $result['verified'];
        $gatewayRef = $result['gateway_ref'];
        break;
}

if ($verified) {
    gateway_update_transaction($txnId, 'completed', $gatewayRef, $callbackData ?? $_GET);
    gateway_confirm_payment($txnId);

    if ($isNotify) {
        echo json_encode(['status' => 'ok']);
        exit;
    }

    set_flash('success', 'Payment successful! Your invoice has been updated.');
} else {
    gateway_update_transaction($txnId, 'failed', $gatewayRef, $callbackData ?? $_GET);

    if ($isNotify) {
        echo json_encode(['status' => 'failed']);
        exit;
    }

    set_flash('error', 'Payment verification failed. If you were charged, please contact the school office.');
}

redirect(url('finance', 'pay-online'));
