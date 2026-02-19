-- ============================================================
-- Urjiberi School ERP — Migration 011: Extended Academics
-- Mediums, Streams, Shifts, Elective Subject Assignments
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Mediums (Language of Instruction) ────────────────────────
CREATE TABLE IF NOT EXISTS `mediums` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO mediums (name, sort_order) VALUES ('English', 1), ('Amharic', 2);

-- ── Streams (Academic Track) ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `streams` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO streams (name, sort_order) VALUES ('Natural Science', 1), ('Social Science', 2);

-- ── Shifts ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `shifts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `start_time` TIME DEFAULT NULL,
    `end_time` TIME DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO shifts (name, start_time, end_time, sort_order) VALUES
('Morning', '08:00:00', '12:30:00', 1),
('Afternoon', '13:00:00', '17:30:00', 2);

-- ── Add medium_id, stream_id, shift_id to classes ────────────
ALTER TABLE `classes` ADD COLUMN `medium_id` BIGINT UNSIGNED DEFAULT NULL AFTER `description`;
ALTER TABLE `classes` ADD COLUMN `stream_id` BIGINT UNSIGNED DEFAULT NULL AFTER `medium_id`;
ALTER TABLE `classes` ADD COLUMN `shift_id` BIGINT UNSIGNED DEFAULT NULL AFTER `stream_id`;

-- ── Make class_teachers.subject_id nullable (class teachers don't need a subject)
ALTER TABLE `class_teachers` MODIFY `subject_id` BIGINT UNSIGNED DEFAULT NULL;

-- ── Student Elective Subject Choices ─────────────────────────
CREATE TABLE IF NOT EXISTS `student_elective_subjects` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `class_subject_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_student_elective` (`student_id`, `class_subject_id`),
    CONSTRAINT `fk_ses_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ses_class_subject` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ses_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
