-- ============================================================
-- Urjiberi School ERP — Migration 012: Results Module
-- Assessments (per class/subject, max=100) + Student Results
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Assessments ──────────────────────────────────────────────
-- One assessment per class+subject+term combination.
-- total_marks is always 100 (enforced at app level).
CREATE TABLE IF NOT EXISTS `assessments` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(255)    NOT NULL,
    `description` TEXT            DEFAULT NULL,
    `class_id`    BIGINT UNSIGNED NOT NULL,
    `subject_id`  BIGINT UNSIGNED NOT NULL,
    `session_id`  BIGINT UNSIGNED NOT NULL,
    `term_id`     BIGINT UNSIGNED DEFAULT NULL,
    `total_marks` DECIMAL(8,2)    NOT NULL DEFAULT 100.00 COMMENT 'Always 100',
    `created_by`  BIGINT UNSIGNED DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_assess_class_subject` (`class_id`, `subject_id`, `session_id`, `term_id`),
    CONSTRAINT `fk_assess_class`   FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)            ON DELETE CASCADE,
    CONSTRAINT `fk_assess_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`)           ON DELETE CASCADE,
    CONSTRAINT `fk_assess_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_assess_term`    FOREIGN KEY (`term_id`)    REFERENCES `terms`(`id`)               ON DELETE SET NULL,
    CONSTRAINT `fk_assess_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)              ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Results ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `student_results` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `assessment_id`  BIGINT UNSIGNED NOT NULL,
    `student_id`     BIGINT UNSIGNED NOT NULL,
    `class_id`       BIGINT UNSIGNED NOT NULL,
    `section_id`     BIGINT UNSIGNED DEFAULT NULL,
    `marks_obtained` DECIMAL(8,2)    DEFAULT NULL COMMENT 'NULL = not yet entered',
    `is_absent`      TINYINT(1)      NOT NULL DEFAULT 0,
    `remarks`        VARCHAR(255)    DEFAULT NULL,
    `entered_by`     BIGINT UNSIGNED DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_student_result` (`assessment_id`, `student_id`),
    INDEX `idx_sr_student`   (`student_id`),
    INDEX `idx_sr_class`     (`class_id`, `section_id`),
    CONSTRAINT `fk_sr_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `assessments`(`id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_sr_student`    FOREIGN KEY (`student_id`)    REFERENCES `students`(`id`)     ON DELETE CASCADE,
    CONSTRAINT `fk_sr_class`      FOREIGN KEY (`class_id`)      REFERENCES `classes`(`id`)      ON DELETE CASCADE,
    CONSTRAINT `fk_sr_section`    FOREIGN KEY (`section_id`)    REFERENCES `sections`(`id`)     ON DELETE SET NULL,
    CONSTRAINT `fk_sr_enterer`    FOREIGN KEY (`entered_by`)    REFERENCES `users`(`id`)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
