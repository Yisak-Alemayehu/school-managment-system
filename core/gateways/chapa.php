<?php
/**
 * Chapa Payment Gateway Adapter
 *
 * Ethiopian Chapa payment integration.
 * API docs: https://developer.chapa.co/docs
 */

/**
 * Initiate Chapa payment
 *
 * @return array ['success' => bool, 'redirect_url' => string|null, 'error' => string|null]
 */
function chapa_initiate(int $txnId, float $amount, string $txnRef, array $config): array
{
    $secretKey = $config['secret_key'] ?? '';
    $apiUrl    = $config['api_url'] ?? 'https://api.chapa.co/v1/transaction/initialize';
    $returnUrl = APP_URL . '/?module=finance&action=payment-callback&gateway=chapa&txn_id=' . $txnId;
    $callbackUrl = APP_URL . '/?module=finance&action=payment-callback&gateway=chapa&notify=1';

    if (!$secretKey) {
        return ['success' => false, 'redirect_url' => null, 'error' => 'Chapa secret key not configured.'];
    }

    // Get student info for the transaction
    $txn = db_fetch_one("
        SELECT pt.*, s.first_name, s.last_name, s.phone
        FROM payment_transactions pt
        JOIN invoices i ON i.id = pt.invoice_id
        JOIN students s ON s.id = i.student_id
        WHERE pt.id = ?
    ", [$txnId]);

    $payload = [
        'amount'       => number_format($amount, 2, '.', ''),
        'currency'     => 'ETB',
        'email'        => '',
        'first_name'   => $txn['first_name'] ?? 'Student',
        'last_name'    => $txn['last_name'] ?? '',
        'phone_number' => $txn['phone'] ?? '',
        'tx_ref'       => $txnRef,
        'callback_url' => $callbackUrl,
        'return_url'   => $returnUrl,
        'customization' => [
            'title'       => SCHOOL_NAME . ' - Fee Payment',
            'description' => 'Fee Payment - ' . $txnRef,
        ],
    ];

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $secretKey,
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'redirect_url' => null, 'error' => "Chapa connection error: {$error}"];
    }

    $result = json_decode($response, true);

    if (($result['status'] ?? '') === 'success' && !empty($result['data']['checkout_url'])) {
        gateway_update_transaction($txnId, 'processing', null, $result);
        return ['success' => true, 'redirect_url' => $result['data']['checkout_url'], 'error' => null];
    }

    $msg = $result['message'] ?? 'Unknown Chapa error';
    gateway_update_transaction($txnId, 'failed', null, $result);
    return ['success' => false, 'redirect_url' => null, 'error' => $msg];
}

/**
 * Verify Chapa transaction
 * @return array ['verified' => bool, 'gateway_ref' => string|null]
 */
function chapa_verify(string $txnRef, array $config): array
{
    $secretKey = $config['secret_key'] ?? '';
    $verifyUrl = ($config['api_url'] ?? 'https://api.chapa.co/v1') . '/transaction/verify/' . $txnRef;

    $ch = curl_init($verifyUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $secretKey,
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if (($result['status'] ?? '') === 'success' && ($result['data']['status'] ?? '') === 'success') {
        return [
            'verified'    => true,
            'gateway_ref' => $result['data']['reference'] ?? null,
        ];
    }

    return ['verified' => false, 'gateway_ref' => null];
}
