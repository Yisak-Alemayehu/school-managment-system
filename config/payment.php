<?php
/**
 * Payment Gateway Configuration
 * Urjiberi School Management ERP
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// ── Active Payment Gateways ─────────────────────────────────
// List of enabled gateway identifiers
define('PAYMENT_ACTIVE_GATEWAYS', ['telebirr']);

// ── Telebirr Configuration ──────────────────────────────────
define('TELEBIRR_CONFIG', [
    'app_id'      => getenv('TELEBIRR_APP_ID') ?: '',
    'app_key'     => getenv('TELEBIRR_APP_KEY') ?: '',
    'short_code'  => getenv('TELEBIRR_SHORT_CODE') ?: '',
    'public_key'  => getenv('TELEBIRR_PUBLIC_KEY') ?: '',
    'api_url'     => getenv('TELEBIRR_API_URL') ?: 'https://app.ethiomobilemoney.et:2121',
    'notify_url'  => APP_URL . '/payments/webhook/telebirr',
    'return_url'  => APP_URL . '/payments/return/telebirr',
    'timeout_url' => APP_URL . '/payments/timeout/telebirr',
    'timeout'     => 30,
]);

// ── Chapa Configuration (Template) ──────────────────────────
define('CHAPA_CONFIG', [
    'secret_key'  => getenv('CHAPA_SECRET_KEY') ?: '',
    'public_key'  => getenv('CHAPA_PUBLIC_KEY') ?: '',
    'api_url'     => 'https://api.chapa.co/v1',
    'webhook_secret' => getenv('CHAPA_WEBHOOK_SECRET') ?: '',
    'notify_url'  => APP_URL . '/payments/webhook/chapa',
    'return_url'  => APP_URL . '/payments/return/chapa',
    'timeout'     => 30,
]);

// ── Stripe Configuration (Template) ─────────────────────────
define('STRIPE_CONFIG', [
    'secret_key'     => getenv('STRIPE_SECRET_KEY') ?: '',
    'publishable_key'=> getenv('STRIPE_PUBLISHABLE_KEY') ?: '',
    'webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET') ?: '',
    'api_version'    => '2023-10-16',
    'notify_url'     => APP_URL . '/payments/webhook/stripe',
    'return_url'     => APP_URL . '/payments/return/stripe',
    'timeout'        => 30,
]);

// ── General Payment Settings ────────────────────────────────
define('PAYMENT_IDEMPOTENCY_TTL', 86400); // 24 hours
define('PAYMENT_RECONCILIATION_INTERVAL', 900); // 15 minutes
define('PAYMENT_PENDING_TIMEOUT', 3600); // 1 hour before marking stale

// ── Transaction States ──────────────────────────────────────
define('TXN_STATE_PENDING',   'pending');
define('TXN_STATE_SUCCESS',   'success');
define('TXN_STATE_FAILED',    'failed');
define('TXN_STATE_CANCELLED', 'cancelled');
define('TXN_STATE_REFUNDED',  'refunded');
