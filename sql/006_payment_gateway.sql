-- ============================================================
-- Urjiberi School ERP — Migration 006: Payment Gateway
-- Gateway tables, transactions, webhooks, reconciliation
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Payment Gateways (configuration) ────────────────────────
CREATE TABLE IF NOT EXISTS `payment_gateways` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL,
    `display_name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `logo` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
    `environment` ENUM('sandbox','production') NOT NULL DEFAULT 'sandbox',
    `config_json` TEXT DEFAULT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_gw_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payment Transactions ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payment_transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_ref` VARCHAR(100) NOT NULL,
    `gateway_id` BIGINT UNSIGNED NOT NULL,
    `invoice_id` BIGINT UNSIGNED DEFAULT NULL,
    `student_id` BIGINT UNSIGNED DEFAULT NULL,
    `user_id` BIGINT UNSIGNED DEFAULT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'ETB',
    `status` ENUM('pending','success','failed','cancelled','refunded','expired') NOT NULL DEFAULT 'pending',
    `gateway_reference` VARCHAR(255) DEFAULT NULL,
    `gateway_status` VARCHAR(100) DEFAULT NULL,
    `gateway_response` TEXT DEFAULT NULL,
    `checkout_url` VARCHAR(1000) DEFAULT NULL,
    `return_url` VARCHAR(1000) DEFAULT NULL,
    `idempotency_key` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `metadata` JSON DEFAULT NULL,
    `paid_at` DATETIME DEFAULT NULL,
    `expires_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_txn_ref` (`transaction_ref`),
    UNIQUE KEY `uk_txn_idempotency` (`idempotency_key`),
    INDEX `idx_txn_gateway` (`gateway_id`),
    INDEX `idx_txn_invoice` (`invoice_id`),
    INDEX `idx_txn_student` (`student_id`),
    INDEX `idx_txn_status` (`status`),
    INDEX `idx_txn_gateway_ref` (`gateway_reference`),
    INDEX `idx_txn_created` (`created_at`),
    CONSTRAINT `fk_txn_gateway` FOREIGN KEY (`gateway_id`) REFERENCES `payment_gateways`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_txn_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_txn_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_txn_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payment Attempts ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payment_attempts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_id` BIGINT UNSIGNED NOT NULL,
    `attempt_no` INT UNSIGNED NOT NULL DEFAULT 1,
    `status` ENUM('initiated','redirected','success','failed','timeout','error') NOT NULL DEFAULT 'initiated',
    `gateway_request` TEXT DEFAULT NULL,
    `gateway_response` TEXT DEFAULT NULL,
    `error_code` VARCHAR(100) DEFAULT NULL,
    `error_message` VARCHAR(500) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_pa_txn` (`transaction_id`),
    CONSTRAINT `fk_pa_txn` FOREIGN KEY (`transaction_id`) REFERENCES `payment_transactions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payment Webhooks Log ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payment_webhooks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `gateway_id` BIGINT UNSIGNED DEFAULT NULL,
    `gateway_slug` VARCHAR(50) NOT NULL,
    `event_type` VARCHAR(100) DEFAULT NULL,
    `payload` TEXT NOT NULL,
    `headers` TEXT DEFAULT NULL,
    `signature` VARCHAR(500) DEFAULT NULL,
    `signature_valid` TINYINT(1) DEFAULT NULL,
    `transaction_ref` VARCHAR(100) DEFAULT NULL,
    `processing_status` ENUM('received','processed','failed','duplicate','invalid') NOT NULL DEFAULT 'received',
    `processing_notes` VARCHAR(500) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `received_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `processed_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_wh_gateway` (`gateway_slug`),
    INDEX `idx_wh_txn_ref` (`transaction_ref`),
    INDEX `idx_wh_status` (`processing_status`),
    INDEX `idx_wh_received` (`received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payment Reconciliation Logs ──────────────────────────────
CREATE TABLE IF NOT EXISTS `payment_reconciliation_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_id` BIGINT UNSIGNED DEFAULT NULL,
    `gateway_id` BIGINT UNSIGNED DEFAULT NULL,
    `action` ENUM('status_check','mark_success','mark_failed','mark_expired','manual_override') NOT NULL,
    `previous_status` VARCHAR(50) DEFAULT NULL,
    `new_status` VARCHAR(50) DEFAULT NULL,
    `gateway_response` TEXT DEFAULT NULL,
    `notes` VARCHAR(500) DEFAULT NULL,
    `performed_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_recon_txn` (`transaction_id`),
    CONSTRAINT `fk_recon_txn` FOREIGN KEY (`transaction_id`) REFERENCES `payment_transactions`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_recon_performer` FOREIGN KEY (`performed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Invoice → Payment Links (for gateway checkout) ───────────
CREATE TABLE IF NOT EXISTS `invoice_payment_links` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT UNSIGNED NOT NULL,
    `transaction_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `is_partial` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ipl_invoice` (`invoice_id`),
    INDEX `idx_ipl_txn` (`transaction_id`),
    CONSTRAINT `fk_ipl_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ipl_txn` FOREIGN KEY (`transaction_id`) REFERENCES `payment_transactions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
