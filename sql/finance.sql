-- ============================================================
-- Finance Module — Database Schema
-- Urji Beri School SMS
-- Run AFTER db.sql and seed.sql
-- Generated: 2026-03-05
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- DROP FINANCE TABLES (reverse FK order)
-- ============================================================
DROP TABLE IF EXISTS `fin_penalty_log`;
DROP TABLE IF EXISTS `fin_varying_penalties`;
DROP TABLE IF EXISTS `fin_transactions`;
DROP TABLE IF EXISTS `fin_student_fees`;
DROP TABLE IF EXISTS `fin_fee_classes`;
DROP TABLE IF EXISTS `fin_fees`;
DROP TABLE IF EXISTS `fin_group_members`;
DROP TABLE IF EXISTS `fin_groups`;
DROP TABLE IF EXISTS `fin_supplementary_transactions`;
DROP TABLE IF EXISTS `fin_supplementary_fees`;

-- ============================================================
-- Fees — Master fee definition
-- ============================================================
CREATE TABLE IF NOT EXISTS `fin_fees` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `description` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'ETB',
    `foreign_amount` DECIMAL(12,2) DEFAULT NULL,
    `fee_type` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Recurrent, 0=One-Time',
    `effective_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `apply_every` INT UNSIGNED DEFAULT 1,
    `frequency` ENUM('months','weeks','days') DEFAULT 'months',
    `has_penalty` TINYINT(1) NOT NULL DEFAULT 1,
    `is_credit_hour` TINYINT(1) NOT NULL DEFAULT 0,
    `penalty_unpaid_after` INT UNSIGNED DEFAULT NULL,
    `penalty_unpaid_unit` ENUM('months','weeks','days') DEFAULT NULL,
    `penalty_type` ENUM('fixed_amount','fixed_percentage','varying_amount','varying_percentage') DEFAULT NULL,
    `penalty_value` DECIMAL(12,4) DEFAULT NULL,
    `penalty_frequency` ENUM('one_time','recurrent') DEFAULT 'one_time',
    `penalty_reapply_every` INT UNSIGNED DEFAULT NULL,
    `penalty_reapply_unit` ENUM('months','weeks','days') DEFAULT NULL,
    `penalty_expiry_date` DATE DEFAULT NULL,
    `max_penalty_amount` DECIMAL(12,2) DEFAULT 1000.00,
    `max_penalty_count` INT UNSIGNED DEFAULT 0,
    `next_applies` DATE DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_fin_fees_active` (`is_active`),
    INDEX `idx_fin_fees_dates` (`effective_date`, `end_date`),
    CONSTRAINT `fk_fin_fees_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Varying penalty values (for varying_amount / varying_percentage)
-- ============================================================
CREATE TABLE IF NOT EXISTS `fin_varying_penalties` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fee_id` BIGINT UNSIGNED NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `value` DECIMAL(12,4) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_fin_vp_fee` (`fee_id`),
    CONSTRAINT `fk_fin_vp_fee` FOREIGN KEY (`fee_id`) REFERENCES `fin_fees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Fee → Class assignment
-- ============================================================
CREATE TABLE IF NOT EXISTS `fin_fee_classes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fee_id` BIGINT UNSIGNED NOT NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_fin_fee_class` (`fee_id`, `class_id`),
    CONSTRAINT `fk_fin_fc_fee` FOREIGN KEY (`fee_id`) REFERENCES `fin_fees`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fin_fc_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Student Fees — individual fee assignments
-- ============================================================
CREATE TABLE IF NOT EXISTS `fin_student_fees` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `fee_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'ETB',
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `assigned_by` BIGINT UNSIGNED DEFAULT NULL,
    `removed_at` DATETIME DEFAULT NULL,
    `removed_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_fin_sf_student` (`student_id`),
    INDEX `idx_fin_sf_fee` (`fee_id`),
    INDEX `idx_fin_sf_active` (`is_active`),
    CONSTRAINT `fk_fin_sf_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fin_sf_fee` FOREIGN KEY (`fee_id`) REFERENCES `fin_fees`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fin_sf_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_fin_sf_removed_by` FOREIGN KEY (`removed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Transactions — all payments, adjustments, fee assignments/removals
-- ============================================================
CREATE TABLE IF NOT EXISTS `fin_transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `student_fee_id` BIGINT UNSIGNED DEFAULT NULL,
    `type` ENUM('payment','adjustment','fee_assigned','fee_removed','penalty','refund') NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'ETB',
    `balance_before` DECIMAL(12,2) DEFAULT NULL,
    `balance_after` DECIMAL(12,2) DEFAULT NULL,
    `description` VARCHAR(500) DEFAULT NULL,
    `channel` VARCHAR(50) DEFAULT NULL,
    `channel_payment_type` VARCHAR(100) DEFAULT NULL,
    `channel_depositor_name` VARCHAR(200) DEFAULT NULL,
    `channel_depositor_branch` VARCHAR(200) DEFAULT NULL,
    `channel_transaction_id` VARCHAR(200) DEFAULT NULL,
    `payer_phone` VARCHAR(20) DEFAULT NULL,
    `receipt_no` VARCHAR(100) DEFAULT NULL,
    `reference` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `processed_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_fin_tx_student` (`student_id`),
    INDEX `idx_fin_tx_sf` (`student_fee_id`),
    INDEX `idx_fin_tx_type` (`type`),
    INDEX `idx_fin_tx_date` (`created_at`),
    INDEX `idx_fin_tx_channel` (`channel`),
    CONSTRAINT `fk_fin_tx_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fin_tx_sf` FOREIGN KEY (`student_fee_id`) REFERENCES `fin_student_fees`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_fin_tx_processor` FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Penalty Log
-- ============================================================
CREATE TABLE IF NOT EXISTS `fin_penalty_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_fee_id` BIGINT UNSIGNED NOT NULL,
    `transaction_id` BIGINT UNSIGNED DEFAULT NULL,
    `penalty_amount` DECIMAL(12,2) NOT NULL,
    `apply_count` INT UNSIGNED NOT NULL DEFAULT 1,
    `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_fin_pl_sf` (`student_fee_id`),
    CONSTRAINT `fk_fin_pl_sf` FOREIGN KEY (`student_fee_id`) REFERENCES `fin_student_fees`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fin_pl_tx` FOREIGN KEY (`transaction_id`) REFERENCES `fin_transactions`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Student Groups
-- ============================================================
CREATE TABLE IF NOT EXISTS `fin_groups` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(200) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `source` ENUM('empty','class') NOT NULL DEFAULT 'empty',
    `source_class_id` BIGINT UNSIGNED DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_fin_grp_active` (`is_active`),
    CONSTRAINT `fk_fin_grp_class` FOREIGN KEY (`source_class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_fin_grp_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Group Members
-- ============================================================
CREATE TABLE IF NOT EXISTS `fin_group_members` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `added_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `added_by` BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_fin_gm` (`group_id`, `student_id`),
    CONSTRAINT `fk_fin_gm_group` FOREIGN KEY (`group_id`) REFERENCES `fin_groups`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fin_gm_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fin_gm_adder` FOREIGN KEY (`added_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Supplementary Fees
-- ============================================================
CREATE TABLE IF NOT EXISTS `fin_supplementary_fees` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `description` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'ETB',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_fin_supfee_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Supplementary Transactions
-- ============================================================
CREATE TABLE IF NOT EXISTS `fin_supplementary_transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `supplementary_fee_id` BIGINT UNSIGNED DEFAULT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'ETB',
    `description` VARCHAR(500) DEFAULT NULL,
    `channel` VARCHAR(50) DEFAULT NULL,
    `channel_payment_type` VARCHAR(100) DEFAULT NULL,
    `channel_depositor_name` VARCHAR(200) DEFAULT NULL,
    `channel_depositor_branch` VARCHAR(200) DEFAULT NULL,
    `channel_transaction_id` VARCHAR(200) DEFAULT NULL,
    `payer_phone` VARCHAR(20) DEFAULT NULL,
    `receipt_no` VARCHAR(100) DEFAULT NULL,
    `processed_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_fin_stx_student` (`student_id`),
    INDEX `idx_fin_stx_date` (`created_at`),
    CONSTRAINT `fk_fin_stx_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fin_stx_fee` FOREIGN KEY (`supplementary_fee_id`) REFERENCES `fin_supplementary_fees`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_fin_stx_processor` FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Finance Permissions (skip if already seeded)
INSERT IGNORE INTO `permissions` (`module`, `action`, `description`) VALUES
('finance', 'view',    'View finance module'),
('finance', 'manage',  'Manage fees, payments, and groups'),
('finance', 'reports', 'View finance reports');

-- Grant finance permissions to Super Admin and School Admin
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM permissions p
CROSS JOIN roles r
WHERE p.module = 'finance' AND r.slug IN ('super_admin', 'school_admin');

-- Grant finance permissions to Accountant
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM permissions p
CROSS JOIN roles r
WHERE p.module = 'finance' AND r.slug = 'accountant';

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Finance schema created successfully!' AS result;
