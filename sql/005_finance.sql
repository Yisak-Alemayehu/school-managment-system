-- ============================================================
-- Urjiberi School ERP — Migration 005: Finance
-- Fee categories, structures, invoices, payments
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Fee Categories ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `fee_categories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(30) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `type` ENUM('tuition','registration','transport','lab','library','exam','uniform','other') NOT NULL DEFAULT 'other',
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_fee_categories_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Fee Structures ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `fee_structures` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `fee_category_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED DEFAULT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `due_date` DATE DEFAULT NULL,
    `is_mandatory` TINYINT(1) NOT NULL DEFAULT 1,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_fee_structures` (`session_id`, `class_id`, `fee_category_id`, `term_id`),
    INDEX `idx_fs_class` (`class_id`),
    CONSTRAINT `fk_fs_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fs_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fs_category` FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fs_term` FOREIGN KEY (`term_id`) REFERENCES `terms`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Invoices ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `invoices` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_no` VARCHAR(50) NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED DEFAULT NULL,
    `class_id` BIGINT UNSIGNED DEFAULT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `fine_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('draft','issued','partial','paid','overdue','cancelled','refunded') NOT NULL DEFAULT 'draft',
    `due_date` DATE DEFAULT NULL,
    `issued_date` DATE DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `discount_reason` VARCHAR(255) DEFAULT NULL,
    `fine_reason` VARCHAR(255) DEFAULT NULL,
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_invoices_no` (`invoice_no`),
    INDEX `idx_inv_student` (`student_id`),
    INDEX `idx_inv_status` (`status`),
    INDEX `idx_inv_session` (`session_id`, `term_id`),
    INDEX `idx_inv_due` (`due_date`),
    CONSTRAINT `fk_inv_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_inv_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_inv_term` FOREIGN KEY (`term_id`) REFERENCES `terms`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_inv_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_inv_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Invoice Items ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `invoice_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT UNSIGNED NOT NULL,
    `fee_category_id` BIGINT UNSIGNED DEFAULT NULL,
    `fee_structure_id` BIGINT UNSIGNED DEFAULT NULL,
    `description` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `total` DECIMAL(12,2) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ii_invoice` (`invoice_id`),
    CONSTRAINT `fk_ii_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ii_category` FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ii_structure` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payments (manual/cash/bank) ──────────────────────────────
CREATE TABLE IF NOT EXISTS `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `receipt_no` VARCHAR(50) NOT NULL,
    `invoice_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `method` ENUM('cash','bank_transfer','cheque','gateway','other') NOT NULL DEFAULT 'cash',
    `reference` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `payment_date` DATE NOT NULL,
    `received_by` BIGINT UNSIGNED DEFAULT NULL,
    `gateway_transaction_id` BIGINT UNSIGNED DEFAULT NULL,
    `status` ENUM('completed','pending','failed','reversed') NOT NULL DEFAULT 'completed',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_payments_receipt` (`receipt_no`),
    INDEX `idx_pay_invoice` (`invoice_id`),
    INDEX `idx_pay_student` (`student_id`),
    INDEX `idx_pay_date` (`payment_date`),
    CONSTRAINT `fk_pay_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pay_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pay_receiver` FOREIGN KEY (`received_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Discounts / Scholarships ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `fee_discounts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM('percentage','fixed') NOT NULL,
    `value` DECIMAL(12,2) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Discounts ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `student_fee_discounts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `fee_discount_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `fee_category_id` BIGINT UNSIGNED DEFAULT NULL,
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_sfd_student` (`student_id`),
    CONSTRAINT `fk_sfd_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sfd_discount` FOREIGN KEY (`fee_discount_id`) REFERENCES `fee_discounts`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sfd_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sfd_category` FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
