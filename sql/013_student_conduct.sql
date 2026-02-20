-- ============================================================
-- Urjiberi School ERP — Migration 013: Student Conduct
-- Conduct is a BEHAVIORAL assessment (separate from academics).
-- Grades: A=Excellent, B=Very Good, C=Good, D=Satisfactory, F=Needs Improvement
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `student_conduct` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `class_id`   BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `term_id`    BIGINT UNSIGNED DEFAULT NULL,
    `conduct`    ENUM('A','B','C','D','F') NOT NULL DEFAULT 'B'
                 COMMENT 'A=Excellent, B=Very Good, C=Good, D=Satisfactory, F=Needs Improvement',
    `remarks`    VARCHAR(255) DEFAULT NULL COMMENT 'Optional teacher note on behavior',
    `entered_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_conduct` (`student_id`, `class_id`, `session_id`, `term_id`),
    INDEX `idx_conduct_class_term` (`class_id`, `session_id`, `term_id`),
    CONSTRAINT `fk_conduct_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)           ON DELETE CASCADE,
    CONSTRAINT `fk_conduct_class`   FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)            ON DELETE CASCADE,
    CONSTRAINT `fk_conduct_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_conduct_term`    FOREIGN KEY (`term_id`)    REFERENCES `terms`(`id`)               ON DELETE SET NULL,
    CONSTRAINT `fk_conduct_enterer` FOREIGN KEY (`entered_by`) REFERENCES `users`(`id`)              ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-student behavioral conduct grade per class/term — independent of academic marks.';

SET FOREIGN_KEY_CHECKS = 1;
