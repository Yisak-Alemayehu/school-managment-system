<?php
/**
 * Telebirr Payment Gateway Adapter
 *
 * Ethiopian Telebirr mobile money integration.
 * API docs: https://developer.ethiotelecom.et
 */

/**
 * Initiate Telebirr payment
 *
 * @return array ['success' => bool, 'redirect_url' => string|null, 'error' => string|null]
 */
function telebirr_initiate(int $txnId, float $amount, string $txnRef, array $config): array
{
    $appId     = $config['app_id'] ?? '';
    $appKey    = $config['app_key'] ?? '';
    $shortCode = $config['short_code'] ?? '';
    $publicKey = $config['public_key'] ?? '';
    $apiUrl    = $config['api_url'] ?? 'https://developer.ethiotelecom.et/checkout/api/payment';
    $returnUrl = APP_URL . '/?module=finance&action=payment-callback&gateway=telebirr&txn_id=' . $txnId;
    $notifyUrl = APP_URL . '/?module=finance&action=payment-callback&gateway=telebirr&notify=1';

    if (!$appId || !$appKey || !$shortCode) {
        return ['success' => false, 'redirect_url' => null, 'error' => 'Telebirr configuration incomplete.'];
    }

    $nonce    = bin2hex(random_bytes(16));
    $timestamp = date('Y-m-d H:i:s');

    $payload = [
        'appId'       => $appId,
        'nonce'       => $nonce,
        'notifyUrl'   => $notifyUrl,
        'outTradeNo'  => $txnRef,
        'receiveName' => SCHOOL_NAME,
        'returnUrl'   => $returnUrl,
        'shortCode'   => $shortCode,
        'subject'     => 'Fee Payment - ' . $txnRef,
        'timeoutExpress' => '30',
        'timestamp'   => $timestamp,
        'totalAmount' => number_format($amount, 2, '.', ''),
    ];

    // Sign payload
    ksort($payload);
    $signStr = implode('&', array_map(fn($k, $v) => "{$k}={$v}", array_keys($payload), $payload));

    // Encrypt with public key
    $encrypted = '';
    if ($publicKey) {
        $pubKeyResource = openssl_pkey_get_public($publicKey);
        if ($pubKeyResource) {
            openssl_public_encrypt($signStr, $encrypted, $pubKeyResource);
            $encrypted = base64_encode($encrypted);
        }
    }

    $requestBody = [
        'appid'   => $appId,
        'sign'    => strtoupper(hash('sha256', $signStr)),
        'ussd'    => $encrypted,
    ];

    // Make API call
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($requestBody),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'redirect_url' => null, 'error' => "Telebirr connection error: {$error}"];
    }

    $result = json_decode($response, true);

    if (isset($result['data']['toPayUrl'])) {
        gateway_update_transaction($txnId, 'processing', null, $result);
        return ['success' => true, 'redirect_url' => $result['data']['toPayUrl'], 'error' => null];
    }

    $msg = $result['message'] ?? $result['msg'] ?? 'Unknown Telebirr error';
    gateway_update_transaction($txnId, 'failed', null, $result);
    return ['success' => false, 'redirect_url' => null, 'error' => $msg];
}

/**
 * Verify Telebirr callback / notification
 * @return array ['verified' => bool, 'gateway_ref' => string|null]
 */
function telebirr_verify(array $data, array $config): array
{
    // Telebirr sends encrypted data in the callback
    $encryptedData = $data['encrypted_data'] ?? $data['data'] ?? '';

    if (!$encryptedData) {
        return ['verified' => false, 'gateway_ref' => null];
    }

    // Decrypt using app key
    $appKey = $config['app_key'] ?? '';
    if ($appKey) {
        $decrypted = openssl_decrypt(
            base64_decode($encryptedData),
            'AES-256-CBC',
            $appKey,
            OPENSSL_RAW_DATA,
            substr($appKey, 0, 16)
        );
        $result = json_decode($decrypted, true);
    } else {
        $result = $data;
    }

    if (!$result) {
        return ['verified' => false, 'gateway_ref' => null];
    }

    $tradeStatus = $result['tradeStatus'] ?? $result['trade_status'] ?? '';
    $gatewayRef  = $result['tradeNo'] ?? $result['trade_no'] ?? null;

    if ($tradeStatus === '2' || strtolower($tradeStatus) === 'success') {
        return ['verified' => true, 'gateway_ref' => $gatewayRef];
    }

    return ['verified' => false, 'gateway_ref' => $gatewayRef];
}
