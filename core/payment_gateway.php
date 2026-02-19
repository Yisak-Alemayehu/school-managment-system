<?php
/**
 * Payment Gateway — Core Abstraction
 * Provides common gateway functions used by Telebirr, Chapa, etc.
 */

/**
 * Initialize a payment transaction record
 */
function gateway_create_transaction(int $invoiceId, float $amount, string $gateway, array $meta = []): int
{
    $txnRef = strtoupper($gateway) . '-' . date('YmdHis') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

    return db_insert('payment_transactions', [
        'invoice_id'    => $invoiceId,
        'gateway'       => $gateway,
        'transaction_ref' => $txnRef,
        'amount'        => $amount,
        'currency'      => CURRENCY,
        'status'        => 'pending',
        'request_payload' => json_encode($meta),
        'created_at'    => date('Y-m-d H:i:s'),
    ]);
}

/**
 * Update transaction status
 */
function gateway_update_transaction(int $txnId, string $status, ?string $gatewayRef = null, ?array $responsePayload = null): void
{
    $data = [
        'status'     => $status,
        'updated_at' => date('Y-m-d H:i:s'),
    ];
    if ($gatewayRef) {
        $data['gateway_ref'] = $gatewayRef;
    }
    if ($responsePayload) {
        $data['response_payload'] = json_encode($responsePayload);
    }
    db_update('payment_transactions', $data, 'id = ?', [$txnId]);
}

/**
 * After successful gateway payment, record the payment against the invoice
 */
function gateway_confirm_payment(int $txnId): bool
{
    $txn = db_fetch_one("SELECT * FROM payment_transactions WHERE id = ? AND status = 'completed'", [$txnId]);
    if (!$txn) return false;

    $invoice = db_fetch_one("SELECT * FROM invoices WHERE id = ?", [$txn['invoice_id']]);
    if (!$invoice) return false;

    $balance = $invoice['total_amount'] - $invoice['paid_amount'];
    $amount  = min((float)$txn['amount'], $balance);

    if ($amount <= 0) return false;

    $pdo = db_connection();
    $pdo->beginTransaction();

    try {
        $receiptNo = 'ORCP-' . date('Ymd') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

        db_insert('payments', [
            'invoice_id'     => $txn['invoice_id'],
            'student_id'     => $invoice['student_id'],
            'amount'         => $amount,
            'payment_date'   => date('Y-m-d'),
            'payment_method' => $txn['gateway'],
            'receipt_no'     => $receiptNo,
            'reference'      => $txn['gateway_ref'] ?? $txn['transaction_ref'],
            'remarks'        => 'Online payment via ' . ucfirst($txn['gateway']),
        ]);

        $newPaid   = $invoice['paid_amount'] + $amount;
        $newStatus = ($newPaid >= $invoice['total_amount']) ? 'paid' : 'partial';

        db_update('invoices', [
            'paid_amount' => $newPaid,
            'status'      => $newStatus,
        ], 'id = ?', [$txn['invoice_id']]);

        $pdo->commit();

        audit_log('online_payment', 'payment_transactions', $txnId, "gateway={$txn['gateway']}, amount={$amount}");
        return true;

    } catch (\Exception $ex) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Get gateway configuration
 */
function gateway_config(string $gateway): array
{
    $gw = db_fetch_one("SELECT * FROM payment_gateways WHERE slug = ? AND is_active = 1", [$gateway]);
    if (!$gw) return [];

    $config = json_decode($gw['config'] ?? '{}', true) ?: [];
    $config['name'] = $gw['name'];
    $config['slug'] = $gw['slug'];
    return $config;
}
