-- ============================================================
-- Urjiberi School ERP — Migration 003: Students
-- Students, Guardians, Enrollments, Promotions, Documents
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Students ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `students` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED DEFAULT NULL,
    `admission_no` VARCHAR(50) NOT NULL,
    `roll_no` VARCHAR(50) DEFAULT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `full_name` VARCHAR(200) GENERATED ALWAYS AS (CONCAT(`first_name`, ' ', `last_name`)) STORED,
    `gender` ENUM('male','female','other') NOT NULL,
    `date_of_birth` DATE NOT NULL,
    `blood_group` ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
    `religion` VARCHAR(50) DEFAULT NULL,
    `nationality` VARCHAR(50) DEFAULT 'Ethiopian',
    `mother_tongue` VARCHAR(50) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `region` VARCHAR(100) DEFAULT NULL,
    `photo` VARCHAR(255) DEFAULT NULL,
    `previous_school` VARCHAR(255) DEFAULT NULL,
    `admission_date` DATE NOT NULL,
    `status` ENUM('active','inactive','graduated','transferred','expelled') NOT NULL DEFAULT 'active',
    `medical_notes` TEXT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_students_admission_no` (`admission_no`),
    INDEX `idx_students_user` (`user_id`),
    INDEX `idx_students_status` (`status`),
    INDEX `idx_students_name` (`last_name`, `first_name`),
    INDEX `idx_students_deleted` (`deleted_at`),
    CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Guardians / Parents ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `guardians` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED DEFAULT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `full_name` VARCHAR(200) GENERATED ALWAYS AS (CONCAT(`first_name`, ' ', `last_name`)) STORED,
    `relation` ENUM('father','mother','guardian','uncle','aunt','sibling','other') NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `alt_phone` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `occupation` VARCHAR(100) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `region` VARCHAR(100) DEFAULT NULL,
    `photo` VARCHAR(255) DEFAULT NULL,
    `is_emergency_contact` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_guardians_user` (`user_id`),
    INDEX `idx_guardians_phone` (`phone`),
    CONSTRAINT `fk_guardians_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student ↔ Guardian (M2M) ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `student_guardians` (
    `student_id` BIGINT UNSIGNED NOT NULL,
    `guardian_id` BIGINT UNSIGNED NOT NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`student_id`, `guardian_id`),
    CONSTRAINT `fk_sg_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sg_guardian` FOREIGN KEY (`guardian_id`) REFERENCES `guardians`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Enrollments (session/class/section) ──────────────────────
CREATE TABLE IF NOT EXISTS `enrollments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `section_id` BIGINT UNSIGNED DEFAULT NULL,
    `roll_no` VARCHAR(50) DEFAULT NULL,
    `status` ENUM('active','promoted','transferred','dropped','repeated') NOT NULL DEFAULT 'active',
    `enrolled_at` DATE NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_enrollments` (`student_id`, `session_id`, `class_id`),
    INDEX `idx_enrollments_session_class` (`session_id`, `class_id`, `section_id`),
    CONSTRAINT `fk_enr_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_enr_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_enr_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_enr_section` FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Promotions ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `promotions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `from_session_id` BIGINT UNSIGNED NOT NULL,
    `from_class_id` BIGINT UNSIGNED NOT NULL,
    `from_section_id` BIGINT UNSIGNED DEFAULT NULL,
    `to_session_id` BIGINT UNSIGNED NOT NULL,
    `to_class_id` BIGINT UNSIGNED NOT NULL,
    `to_section_id` BIGINT UNSIGNED DEFAULT NULL,
    `status` ENUM('promoted','repeated','transferred','graduated') NOT NULL,
    `remarks` TEXT DEFAULT NULL,
    `promoted_by` BIGINT UNSIGNED DEFAULT NULL,
    `promoted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_promotions_student` (`student_id`),
    INDEX `idx_promotions_from` (`from_session_id`, `from_class_id`),
    CONSTRAINT `fk_promo_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_promo_from_session` FOREIGN KEY (`from_session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_promo_from_class` FOREIGN KEY (`from_class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_promo_to_session` FOREIGN KEY (`to_session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_promo_to_class` FOREIGN KEY (`to_class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_promo_by` FOREIGN KEY (`promoted_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Documents ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `student_documents` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` INT UNSIGNED DEFAULT NULL,
    `mime_type` VARCHAR(100) DEFAULT NULL,
    `uploaded_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_sdocs_student` (`student_id`),
    CONSTRAINT `fk_sdocs_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sdocs_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
