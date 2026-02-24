-- ============================================================
-- Urji Beri School SMS — Migration 015: Fee Management System
-- Advanced fees, recurrence, penalties, groups, assignments
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Fees (core fee definitions) ──────────────────────────────
CREATE TABLE IF NOT EXISTS `fees` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fee_type` ENUM('one_time','recurrent') NOT NULL DEFAULT 'one_time',
    `currency` VARCHAR(10) NOT NULL DEFAULT 'ETB',
    `description` VARCHAR(500) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `effective_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `status` ENUM('draft','active','inactive','archived') NOT NULL DEFAULT 'draft',
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_fees_status` (`status`),
    INDEX `idx_fees_type` (`fee_type`),
    INDEX `idx_fees_dates` (`effective_date`, `end_date`),
    INDEX `idx_fees_deleted` (`deleted_at`),
    CONSTRAINT `fk_fees_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `chk_fees_end_after_start` CHECK (`end_date` > `effective_date`),
    CONSTRAINT `chk_fees_amount_positive` CHECK (`amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Recurrence Configs (1:1 with fees, only for recurrent) ───
CREATE TABLE IF NOT EXISTS `recurrence_configs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fee_id` BIGINT UNSIGNED NOT NULL,
    `frequency_number` INT UNSIGNED NOT NULL DEFAULT 1,
    `frequency_unit` ENUM('days','weeks','months','years') NOT NULL DEFAULT 'months',
    `max_recurrences` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = unlimited',
    `next_due_date` DATE DEFAULT NULL,
    `current_recurrence` INT UNSIGNED NOT NULL DEFAULT 0,
    `last_generated_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_recurrence_fee` (`fee_id`),
    CONSTRAINT `fk_recurrence_fee` FOREIGN KEY (`fee_id`) REFERENCES `fees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Penalty Configs (1:1 with fees, optional) ────────────────
CREATE TABLE IF NOT EXISTS `penalty_configs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fee_id` BIGINT UNSIGNED NOT NULL,
    `grace_period_number` INT UNSIGNED NOT NULL DEFAULT 0,
    `grace_period_unit` ENUM('days','weeks','months') NOT NULL DEFAULT 'days',
    `penalty_type` ENUM('fixed','percentage') NOT NULL DEFAULT 'fixed',
    `penalty_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Fixed amount or percentage value',
    `penalty_frequency` ENUM('one_time','recurrent') NOT NULL DEFAULT 'one_time',
    `penalty_recurrence_unit` ENUM('days','weeks','months') DEFAULT NULL COMMENT 'Only if penalty_frequency=recurrent',
    `penalty_recurrence_number` INT UNSIGNED DEFAULT NULL,
    `max_penalty_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Total cap across all penalties',
    `max_penalty_applications` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = unlimited',
    `penalty_end_date` DATE DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_penalty_fee` (`fee_id`),
    CONSTRAINT `fk_penalty_fee` FOREIGN KEY (`fee_id`) REFERENCES `fees`(`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_penalty_max_positive` CHECK (`max_penalty_amount` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Groups ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `student_groups` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_student_groups_name` (`name`),
    INDEX `idx_student_groups_status` (`status`),
    INDEX `idx_student_groups_deleted` (`deleted_at`),
    CONSTRAINT `fk_sgroups_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Group Members (M2M) ──────────────────────────────
CREATE TABLE IF NOT EXISTS `student_group_members` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `assigned_by` BIGINT UNSIGNED DEFAULT NULL,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_group_student` (`group_id`, `student_id`),
    INDEX `idx_sgm_student` (`student_id`),
    INDEX `idx_sgm_deleted` (`deleted_at`),
    CONSTRAINT `fk_sgm_group` FOREIGN KEY (`group_id`) REFERENCES `student_groups`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sgm_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sgm_assigner` FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Fee Assignments (polymorphic) ────────────────────────────
CREATE TABLE IF NOT EXISTS `fee_assignments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fee_id` BIGINT UNSIGNED NOT NULL,
    `assignment_type` ENUM('class','grade','individual','group') NOT NULL,
    `target_id` BIGINT UNSIGNED NOT NULL COMMENT 'class_id, numeric_grade, student_id, or group_id',
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_fee_assignment` (`fee_id`, `assignment_type`, `target_id`),
    INDEX `idx_fa_type_target` (`assignment_type`, `target_id`),
    INDEX `idx_fa_deleted` (`deleted_at`),
    CONSTRAINT `fk_fa_fee` FOREIGN KEY (`fee_id`) REFERENCES `fees`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fa_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Fee Exemptions ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `fee_exemptions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fee_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `reason` VARCHAR(500) DEFAULT NULL,
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_fee_exemption` (`fee_id`, `student_id`),
    INDEX `idx_fex_student` (`student_id`),
    INDEX `idx_fex_deleted` (`deleted_at`),
    CONSTRAINT `fk_fex_fee` FOREIGN KEY (`fee_id`) REFERENCES `fees`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fex_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fex_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Fee Charges (actual billed items) ────────────────
CREATE TABLE IF NOT EXISTS `student_fee_charges` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `fee_id` BIGINT UNSIGNED NOT NULL,
    `fee_assignment_id` BIGINT UNSIGNED DEFAULT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'ETB',
    `due_date` DATE NOT NULL,
    `occurrence_number` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Which occurrence for recurrent fees',
    `status` ENUM('pending','paid','overdue','waived','cancelled') NOT NULL DEFAULT 'pending',
    `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `paid_at` DATETIME DEFAULT NULL,
    `waived_reason` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_sfc_student` (`student_id`),
    INDEX `idx_sfc_fee` (`fee_id`),
    INDEX `idx_sfc_status` (`status`),
    INDEX `idx_sfc_due_date` (`due_date`),
    INDEX `idx_sfc_student_fee` (`student_id`, `fee_id`, `occurrence_number`),
    CONSTRAINT `fk_sfc_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sfc_fee` FOREIGN KEY (`fee_id`) REFERENCES `fees`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sfc_assignment` FOREIGN KEY (`fee_assignment_id`) REFERENCES `fee_assignments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Penalty Charges ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `penalty_charges` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `charge_id` BIGINT UNSIGNED NOT NULL,
    `penalty_amount` DECIMAL(12,2) NOT NULL,
    `penalty_number` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Nth penalty applied',
    `status` ENUM('pending','paid','waived') NOT NULL DEFAULT 'pending',
    `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reason` VARCHAR(500) DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_pc_charge` (`charge_id`),
    INDEX `idx_pc_status` (`status`),
    CONSTRAINT `fk_pc_charge` FOREIGN KEY (`charge_id`) REFERENCES `student_fee_charges`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Audit Log for Finance Actions ────────────────────────────
CREATE TABLE IF NOT EXISTS `finance_audit_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` BIGINT UNSIGNED DEFAULT NULL,
    `details` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_fal_user` (`user_id`),
    INDEX `idx_fal_entity` (`entity_type`, `entity_id`),
    INDEX `idx_fal_created` (`created_at`),
    CONSTRAINT `fk_fal_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
