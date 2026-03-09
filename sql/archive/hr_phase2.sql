-- ============================================================
-- HR Module — Phase 2 Migration
-- Payroll Calculation Engine & Attendance System Enhancements
-- Run AFTER hr.sql
-- ============================================================

SET NAMES utf8mb4;

-- ============================================================
-- 1. Add EC date columns to hr_payroll_periods
-- ============================================================
ALTER TABLE `hr_payroll_periods`
    ADD COLUMN `month_name_ec` VARCHAR(30) DEFAULT NULL COMMENT 'Ethiopian month name (e.g. Meskerem)' AFTER `year_ec`,
    ADD COLUMN `start_date_ec` VARCHAR(20) DEFAULT NULL COMMENT 'Ethiopian start date DD/MM/YYYY' AFTER `end_date`,
    ADD COLUMN `end_date_ec` VARCHAR(20) DEFAULT NULL COMMENT 'Ethiopian end date DD/MM/YYYY' AFTER `start_date_ec`;

-- ============================================================
-- 2. Add overtime, allowances aggregate, payment method, etc.
--    to hr_payroll_records
-- ============================================================
ALTER TABLE `hr_payroll_records`
    ADD COLUMN `overtime` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Overtime pay' AFTER `other_allowance`,
    ADD COLUMN `other_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Additional deductions (loans, penalties, etc.)' AFTER `employer_pension`,
    ADD COLUMN `total_pension` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'employee_pension + employer_pension' AFTER `other_deductions`,
    ADD COLUMN `payment_method` ENUM('bank_transfer','cash') NOT NULL DEFAULT 'bank_transfer' AFTER `payment_status`,
    ADD COLUMN `bank_reference` VARCHAR(100) DEFAULT NULL COMMENT 'Bank transfer reference number' AFTER `payment_method`,
    ADD COLUMN `created_by` BIGINT UNSIGNED DEFAULT NULL AFTER `notes`;

ALTER TABLE `hr_payroll_records`
    ADD CONSTRAINT `fk_hr_pr_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- ============================================================
-- 3. hr_employee_allowances — recurring allowances per employee
--    Allows defining transport, housing, responsibility, etc.
--    separate from the fixed columns on hr_employees
-- ============================================================
CREATE TABLE IF NOT EXISTS `hr_employee_allowances` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` BIGINT UNSIGNED NOT NULL,
    `allowance_type` ENUM('transport','housing','responsibility','hardship','position','overtime','other') NOT NULL,
    `name` VARCHAR(100) NOT NULL COMMENT 'Display label',
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `is_taxable` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = included in taxable income',
    `is_permanent` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = auto-included every payroll',
    `start_date` DATE DEFAULT NULL COMMENT 'When this allowance starts',
    `end_date` DATE DEFAULT NULL COMMENT 'When this allowance ends (NULL = ongoing)',
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_hr_ea_employee` (`employee_id`),
    INDEX `idx_hr_ea_type` (`allowance_type`),
    INDEX `idx_hr_ea_status` (`status`),
    CONSTRAINT `fk_hr_ea_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hr_ea_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. New permission for allowance management
-- ============================================================
INSERT IGNORE INTO `permissions` (`module`, `action`, `description`) VALUES
('hr', 'allowances', 'Manage employee recurring allowances');

-- Grant to super_admin and school_admin
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions` WHERE `module` = 'hr' AND `action` = 'allowances';

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, `id` FROM `permissions` WHERE `module` = 'hr' AND `action` = 'allowances';

-- Accountant gets view-only via existing hr.view
