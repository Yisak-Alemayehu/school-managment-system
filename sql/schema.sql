-- ============================================================
-- Urjiberi School ERP — COMPLETE SCHEMA (All Tables + Constraints)
-- Fresh install file — run this FIRST, then seed.sql
-- Generated: 2026-02-20
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ============================================================
-- DROP TABLES (reverse FK order)
-- ============================================================
DROP TABLE IF EXISTS `student_conduct`;
DROP TABLE IF EXISTS `student_results`;
DROP TABLE IF EXISTS `assessments`;
DROP TABLE IF EXISTS `student_elective_subjects`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `invoice_payment_links`;
DROP TABLE IF EXISTS `payment_reconciliation_logs`;
DROP TABLE IF EXISTS `payment_webhooks`;
DROP TABLE IF EXISTS `payment_attempts`;
DROP TABLE IF EXISTS `payment_transactions`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `invoice_items`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `student_fee_discounts`;
DROP TABLE IF EXISTS `fee_discounts`;
DROP TABLE IF EXISTS `fee_structures`;
DROP TABLE IF EXISTS `fee_categories`;
DROP TABLE IF EXISTS `payment_gateways`;
DROP TABLE IF EXISTS `report_cards`;
DROP TABLE IF EXISTS `marks`;
DROP TABLE IF EXISTS `exam_schedules`;
DROP TABLE IF EXISTS `exams`;
DROP TABLE IF EXISTS `grade_scale_entries`;
DROP TABLE IF EXISTS `grade_scales`;
DROP TABLE IF EXISTS `assignment_submissions`;
DROP TABLE IF EXISTS `assignments`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `student_documents`;
DROP TABLE IF EXISTS `promotions`;
DROP TABLE IF EXISTS `enrollments`;
DROP TABLE IF EXISTS `student_guardians`;
DROP TABLE IF EXISTS `guardians`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `timetables`;
DROP TABLE IF EXISTS `class_teachers`;
DROP TABLE IF EXISTS `class_subjects`;
DROP TABLE IF EXISTS `sections`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `classes`;
DROP TABLE IF EXISTS `terms`;
DROP TABLE IF EXISTS `academic_sessions`;
DROP TABLE IF EXISTS `user_roles`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `shifts`;
DROP TABLE IF EXISTS `streams`;
DROP TABLE IF EXISTS `mediums`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;

-- ============================================================
-- CREATE TABLES
-- ============================================================

-- ── Roles ────────────────────────────────────────────────────
CREATE TABLE `roles` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(50)     NOT NULL,
    `slug`        VARCHAR(50)     NOT NULL,
    `description` VARCHAR(255)    DEFAULT NULL,
    `is_system`   TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Permissions ──────────────────────────────────────────────
CREATE TABLE `permissions` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `module`      VARCHAR(50)     NOT NULL,
    `action`      VARCHAR(50)     NOT NULL,
    `description` VARCHAR(255)    DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_permissions_module_action` (`module`, `action`),
    INDEX `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Instruction Mediums ──────────────────────────────────────
CREATE TABLE `mediums` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100)    NOT NULL,
    `is_active`  TINYINT(1)      NOT NULL DEFAULT 1,
    `sort_order` INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Academic Streams ─────────────────────────────────────────
CREATE TABLE `streams` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100)    NOT NULL,
    `description` VARCHAR(255)    DEFAULT NULL,
    `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
    `sort_order`  INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Shifts ───────────────────────────────────────────────────
CREATE TABLE `shifts` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100)    NOT NULL,
    `start_time` TIME            DEFAULT NULL,
    `end_time`   TIME            DEFAULT NULL,
    `is_active`  TINYINT(1)      NOT NULL DEFAULT 1,
    `sort_order` INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Users ────────────────────────────────────────────────────
CREATE TABLE `users` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`              VARCHAR(100)    NOT NULL,
    `email`                 VARCHAR(255)    NOT NULL,
    `password_hash`         VARCHAR(255)    NOT NULL,
    `full_name`             VARCHAR(200)    NOT NULL,
    `first_name`            VARCHAR(100)    DEFAULT NULL,
    `last_name`             VARCHAR(100)    DEFAULT NULL,
    `phone`                 VARCHAR(20)     DEFAULT NULL,
    `avatar`                VARCHAR(255)    DEFAULT NULL,
    `gender`                ENUM('male','female','other') DEFAULT NULL,
    `date_of_birth`         DATE            DEFAULT NULL,
    `address`               TEXT            DEFAULT NULL,
    `is_active`             TINYINT(1)      NOT NULL DEFAULT 1,
    `force_password_change` TINYINT(1)      NOT NULL DEFAULT 0,
    `status`                ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    `email_verified_at`     DATETIME        DEFAULT NULL,
    `last_login_at`         DATETIME        DEFAULT NULL,
    `last_login_ip`         VARCHAR(45)     DEFAULT NULL,
    `password_reset_token`  VARCHAR(255)    DEFAULT NULL,
    `password_reset_expires` DATETIME       DEFAULT NULL,
    `login_attempts`        INT UNSIGNED    NOT NULL DEFAULT 0,
    `locked_until`          DATETIME        DEFAULT NULL,
    `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`            DATETIME        DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_username` (`username`),
    UNIQUE KEY `uk_users_email`    (`email`),
    INDEX `idx_users_status`  (`status`),
    INDEX `idx_users_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Login Attempts Log ───────────────────────────────────────
CREATE TABLE `login_attempts` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username_or_email` VARCHAR(255)    NOT NULL,
    `ip_address`        VARCHAR(45)     NOT NULL,
    `user_agent`        VARCHAR(500)    DEFAULT NULL,
    `success`           TINYINT(1)      NOT NULL DEFAULT 0,
    `attempted_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_login_attempts_identifier` (`username_or_email`, `attempted_at`),
    INDEX `idx_login_attempts_ip`         (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Role → Permissions ───────────────────────────────────────
CREATE TABLE `role_permissions` (
    `role_id`       BIGINT UNSIGNED NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_rp_role`       FOREIGN KEY (`role_id`)       REFERENCES `roles`(`id`)       ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── User → Roles ─────────────────────────────────────────────
CREATE TABLE `user_roles` (
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `role_id`     BIGINT UNSIGNED NOT NULL,
    `assigned_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `role_id`),
    CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Academic Sessions ────────────────────────────────────────
CREATE TABLE `academic_sessions` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100)    NOT NULL,
    `slug`       VARCHAR(100)    NOT NULL,
    `start_date` DATE            NOT NULL,
    `end_date`   DATE            NOT NULL,
    `is_active`  TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_sessions_slug` (`slug`),
    INDEX `idx_sessions_active`   (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Terms / Semesters ────────────────────────────────────────
CREATE TABLE `terms` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `name`       VARCHAR(100)    NOT NULL,
    `slug`       VARCHAR(100)    NOT NULL,
    `start_date` DATE            NOT NULL,
    `end_date`   DATE            NOT NULL,
    `is_active`  TINYINT(1)      NOT NULL DEFAULT 0,
    `sort_order` INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_terms_session_slug` (`session_id`, `slug`),
    INDEX `idx_terms_active`           (`is_active`),
    CONSTRAINT `fk_terms_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Classes ──────────────────────────────────────────────────
-- medium_id, stream_id, shift_id are soft references (no FK constraint — managed at app level)
CREATE TABLE `classes` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`         VARCHAR(100)    NOT NULL,
    `slug`         VARCHAR(100)    NOT NULL,
    `numeric_name` INT UNSIGNED    DEFAULT NULL,
    `description`  VARCHAR(255)    DEFAULT NULL,
    `medium_id`    BIGINT UNSIGNED DEFAULT NULL COMMENT 'Soft ref to mediums.id',
    `stream_id`    BIGINT UNSIGNED DEFAULT NULL COMMENT 'Soft ref to streams.id',
    `shift_id`     BIGINT UNSIGNED DEFAULT NULL COMMENT 'Soft ref to shifts.id',
    `sort_order`   INT UNSIGNED    NOT NULL DEFAULT 0,
    `is_active`    TINYINT(1)      NOT NULL DEFAULT 1,
    `status`       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_classes_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Sections ─────────────────────────────────────────────────
CREATE TABLE `sections` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id`   BIGINT UNSIGNED NOT NULL,
    `name`       VARCHAR(50)     NOT NULL,
    `capacity`   INT UNSIGNED    DEFAULT NULL,
    `is_active`  TINYINT(1)      NOT NULL DEFAULT 1,
    `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_sections_class_name` (`class_id`, `name`),
    CONSTRAINT `fk_sections_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Subjects ─────────────────────────────────────────────────
CREATE TABLE `subjects` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100)    NOT NULL,
    `code`        VARCHAR(20)     NOT NULL,
    `description` TEXT            DEFAULT NULL,
    `type`        ENUM('theory','practical','both') NOT NULL DEFAULT 'theory',
    `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_subjects_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Grade Scales ─────────────────────────────────────────────
CREATE TABLE `grade_scales` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100)    NOT NULL,
    `is_default` TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Grade Scale Entries ──────────────────────────────────────
CREATE TABLE `grade_scale_entries` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `grade_scale_id`  BIGINT UNSIGNED NOT NULL,
    `grade`           VARCHAR(5)      NOT NULL,
    `min_percentage`  DECIMAL(5,2)    NOT NULL,
    `max_percentage`  DECIMAL(5,2)    NOT NULL,
    `grade_point`     DECIMAL(4,2)    DEFAULT NULL,
    `remark`          VARCHAR(50)     DEFAULT NULL,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_gse_scale` (`grade_scale_id`),
    CONSTRAINT `fk_gse_scale` FOREIGN KEY (`grade_scale_id`) REFERENCES `grade_scales`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Class → Subjects ─────────────────────────────────────────
CREATE TABLE `class_subjects` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id`    BIGINT UNSIGNED NOT NULL,
    `subject_id`  BIGINT UNSIGNED NOT NULL,
    `session_id`  BIGINT UNSIGNED NOT NULL,
    `is_elective` TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_class_subjects` (`class_id`, `subject_id`, `session_id`),
    INDEX `idx_cs_session` (`session_id`),
    CONSTRAINT `fk_cs_class`   FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)            ON DELETE CASCADE,
    CONSTRAINT `fk_cs_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`)           ON DELETE CASCADE,
    CONSTRAINT `fk_cs_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Class Teachers (class-subject-teacher mapping) ────────────
CREATE TABLE `class_teachers` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id`        BIGINT UNSIGNED NOT NULL,
    `section_id`      BIGINT UNSIGNED DEFAULT NULL,
    `subject_id`      BIGINT UNSIGNED DEFAULT NULL,
    `teacher_id`      BIGINT UNSIGNED NOT NULL,
    `session_id`      BIGINT UNSIGNED NOT NULL,
    `is_class_teacher` TINYINT(1)     NOT NULL DEFAULT 0,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_class_teachers` (`class_id`, `section_id`, `subject_id`, `teacher_id`, `session_id`),
    INDEX `idx_ct_teacher` (`teacher_id`),
    INDEX `idx_ct_session` (`session_id`),
    CONSTRAINT `fk_ct_class`    FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)           ON DELETE CASCADE,
    CONSTRAINT `fk_ct_section`  FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`)          ON DELETE SET NULL,
    CONSTRAINT `fk_ct_subject`  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`)          ON DELETE CASCADE,
    CONSTRAINT `fk_ct_teacher`  FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`)             ON DELETE CASCADE,
    CONSTRAINT `fk_ct_session`  FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Timetables ───────────────────────────────────────────────
CREATE TABLE `timetables` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id`  BIGINT UNSIGNED NOT NULL,
    `term_id`     BIGINT UNSIGNED DEFAULT NULL,
    `class_id`    BIGINT UNSIGNED NOT NULL,
    `section_id`  BIGINT UNSIGNED DEFAULT NULL,
    `subject_id`  BIGINT UNSIGNED NOT NULL,
    `teacher_id`  BIGINT UNSIGNED DEFAULT NULL,
    `day_of_week` ENUM('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
    `start_time`  TIME            NOT NULL,
    `end_time`    TIME            NOT NULL,
    `room`        VARCHAR(50)     DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_tt_class_day`   (`class_id`, `section_id`, `day_of_week`),
    INDEX `idx_tt_teacher_day` (`teacher_id`, `day_of_week`),
    CONSTRAINT `fk_tt_session`  FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tt_term`     FOREIGN KEY (`term_id`)    REFERENCES `terms`(`id`)              ON DELETE SET NULL,
    CONSTRAINT `fk_tt_class`    FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)            ON DELETE CASCADE,
    CONSTRAINT `fk_tt_section`  FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`)           ON DELETE SET NULL,
    CONSTRAINT `fk_tt_subject`  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`)           ON DELETE CASCADE,
    CONSTRAINT `fk_tt_teacher`  FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`)              ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Students ─────────────────────────────────────────────────
CREATE TABLE `students` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`         BIGINT UNSIGNED DEFAULT NULL,
    `admission_no`    VARCHAR(50)     NOT NULL,
    `roll_no`         VARCHAR(50)     DEFAULT NULL,
    `first_name`      VARCHAR(100)    NOT NULL,
    `last_name`       VARCHAR(100)    NOT NULL,
    `full_name`       VARCHAR(200)    GENERATED ALWAYS AS (CONCAT(`first_name`, ' ', `last_name`)) STORED,
    `gender`          ENUM('male','female','other') NOT NULL,
    `date_of_birth`   DATE            NOT NULL,
    `blood_group`     ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
    `religion`        VARCHAR(50)     DEFAULT NULL,
    `nationality`     VARCHAR(50)     DEFAULT 'Ethiopian',
    `mother_tongue`   VARCHAR(50)     DEFAULT NULL,
    `phone`           VARCHAR(20)     DEFAULT NULL,
    `email`           VARCHAR(255)    DEFAULT NULL,
    `address`         TEXT            DEFAULT NULL,
    `city`            VARCHAR(100)    DEFAULT NULL,
    `region`          VARCHAR(100)    DEFAULT NULL,
    `photo`           VARCHAR(255)    DEFAULT NULL,
    `previous_school` VARCHAR(255)    DEFAULT NULL,
    `admission_date`  DATE            NOT NULL,
    `status`          ENUM('active','inactive','graduated','transferred','expelled') NOT NULL DEFAULT 'active',
    `medical_notes`   TEXT            DEFAULT NULL,
    `notes`           TEXT            DEFAULT NULL,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`      DATETIME        DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_students_admission_no` (`admission_no`),
    INDEX `idx_students_user`    (`user_id`),
    INDEX `idx_students_status`  (`status`),
    INDEX `idx_students_name`    (`last_name`, `first_name`),
    INDEX `idx_students_deleted` (`deleted_at`),
    CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Guardians / Parents ──────────────────────────────────────
CREATE TABLE `guardians` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`              BIGINT UNSIGNED DEFAULT NULL,
    `first_name`           VARCHAR(100)    NOT NULL,
    `last_name`            VARCHAR(100)    NOT NULL,
    `full_name`            VARCHAR(200)    GENERATED ALWAYS AS (CONCAT(`first_name`, ' ', `last_name`)) STORED,
    `relation`             ENUM('father','mother','guardian','uncle','aunt','sibling','other') NOT NULL,
    `phone`                VARCHAR(20)     NOT NULL,
    `alt_phone`            VARCHAR(20)     DEFAULT NULL,
    `email`                VARCHAR(255)    DEFAULT NULL,
    `occupation`           VARCHAR(100)    DEFAULT NULL,
    `address`              TEXT            DEFAULT NULL,
    `city`                 VARCHAR(100)    DEFAULT NULL,
    `region`               VARCHAR(100)    DEFAULT NULL,
    `photo`                VARCHAR(255)    DEFAULT NULL,
    `is_emergency_contact` TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_guardians_user`  (`user_id`),
    INDEX `idx_guardians_phone` (`phone`),
    CONSTRAINT `fk_guardians_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student ↔ Guardian (M2M) ─────────────────────────────────
CREATE TABLE `student_guardians` (
    `student_id`  BIGINT UNSIGNED NOT NULL,
    `guardian_id` BIGINT UNSIGNED NOT NULL,
    `is_primary`  TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`student_id`, `guardian_id`),
    CONSTRAINT `fk_sg_student`  FOREIGN KEY (`student_id`)  REFERENCES `students`(`id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_sg_guardian` FOREIGN KEY (`guardian_id`) REFERENCES `guardians`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Enrollments ──────────────────────────────────────────────
CREATE TABLE `enrollments` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`  BIGINT UNSIGNED NOT NULL,
    `session_id`  BIGINT UNSIGNED NOT NULL,
    `class_id`    BIGINT UNSIGNED NOT NULL,
    `section_id`  BIGINT UNSIGNED DEFAULT NULL,
    `roll_no`     VARCHAR(50)     DEFAULT NULL,
    `status`      ENUM('active','promoted','transferred','dropped','repeated') NOT NULL DEFAULT 'active',
    `enrolled_at` DATE            NOT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_enrollments` (`student_id`, `session_id`, `class_id`),
    INDEX `idx_enrollments_session_class` (`session_id`, `class_id`, `section_id`),
    CONSTRAINT `fk_enr_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)            ON DELETE CASCADE,
    CONSTRAINT `fk_enr_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_enr_class`   FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)             ON DELETE CASCADE,
    CONSTRAINT `fk_enr_section` FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Promotions ───────────────────────────────────────────────
CREATE TABLE `promotions` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`      BIGINT UNSIGNED NOT NULL,
    `from_session_id` BIGINT UNSIGNED NOT NULL,
    `from_class_id`   BIGINT UNSIGNED NOT NULL,
    `from_section_id` BIGINT UNSIGNED DEFAULT NULL,
    `to_session_id`   BIGINT UNSIGNED NOT NULL,
    `to_class_id`     BIGINT UNSIGNED NOT NULL,
    `to_section_id`   BIGINT UNSIGNED DEFAULT NULL,
    `status`          ENUM('promoted','repeated','transferred','graduated') NOT NULL,
    `remarks`         TEXT            DEFAULT NULL,
    `promoted_by`     BIGINT UNSIGNED DEFAULT NULL,
    `promoted_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_promotions_student` (`student_id`),
    INDEX `idx_promotions_from`    (`from_session_id`, `from_class_id`),
    CONSTRAINT `fk_promo_student`      FOREIGN KEY (`student_id`)      REFERENCES `students`(`id`)            ON DELETE CASCADE,
    CONSTRAINT `fk_promo_from_session` FOREIGN KEY (`from_session_id`) REFERENCES `academic_sessions`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_promo_from_class`   FOREIGN KEY (`from_class_id`)   REFERENCES `classes`(`id`)             ON DELETE CASCADE,
    CONSTRAINT `fk_promo_to_session`   FOREIGN KEY (`to_session_id`)   REFERENCES `academic_sessions`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_promo_to_class`     FOREIGN KEY (`to_class_id`)     REFERENCES `classes`(`id`)             ON DELETE CASCADE,
    CONSTRAINT `fk_promo_by`           FOREIGN KEY (`promoted_by`)     REFERENCES `users`(`id`)               ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Documents ────────────────────────────────────────
CREATE TABLE `student_documents` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`  BIGINT UNSIGNED NOT NULL,
    `title`       VARCHAR(255)    NOT NULL,
    `type`        VARCHAR(50)     NOT NULL,
    `file_path`   VARCHAR(500)    NOT NULL,
    `file_size`   INT UNSIGNED    DEFAULT NULL,
    `mime_type`   VARCHAR(100)    DEFAULT NULL,
    `uploaded_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_sdocs_student` (`student_id`),
    CONSTRAINT `fk_sdocs_student`   FOREIGN KEY (`student_id`)  REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sdocs_uploader`  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Elective Subject Choices ─────────────────────────
CREATE TABLE `student_elective_subjects` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`       BIGINT UNSIGNED NOT NULL,
    `class_subject_id` BIGINT UNSIGNED NOT NULL,
    `session_id`       BIGINT UNSIGNED NOT NULL,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_student_elective` (`student_id`, `class_subject_id`),
    CONSTRAINT `fk_ses_student`      FOREIGN KEY (`student_id`)       REFERENCES `students`(`id`)           ON DELETE CASCADE,
    CONSTRAINT `fk_ses_class_subject` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects`(`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_ses_session`      FOREIGN KEY (`session_id`)       REFERENCES `academic_sessions`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Attendance ───────────────────────────────────────────────
CREATE TABLE `attendance` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `class_id`   BIGINT UNSIGNED NOT NULL,
    `section_id` BIGINT UNSIGNED DEFAULT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `term_id`    BIGINT UNSIGNED DEFAULT NULL,
    `subject_id` BIGINT UNSIGNED DEFAULT NULL,
    `date`       DATE            NOT NULL,
    `period`     TINYINT UNSIGNED DEFAULT NULL,
    `status`     ENUM('present','absent','late','excused','half_day') NOT NULL,
    `remarks`    VARCHAR(255)    DEFAULT NULL,
    `marked_by`  BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_attendance_daily` (`student_id`, `class_id`, `date`, `subject_id`, `period`),
    INDEX `idx_att_date`         (`date`),
    INDEX `idx_att_class_date`   (`class_id`, `section_id`, `date`),
    INDEX `idx_att_session_term` (`session_id`, `term_id`),
    CONSTRAINT `fk_att_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)            ON DELETE CASCADE,
    CONSTRAINT `fk_att_class`   FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)             ON DELETE CASCADE,
    CONSTRAINT `fk_att_section` FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`)            ON DELETE SET NULL,
    CONSTRAINT `fk_att_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_att_term`    FOREIGN KEY (`term_id`)    REFERENCES `terms`(`id`)               ON DELETE SET NULL,
    CONSTRAINT `fk_att_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`)            ON DELETE SET NULL,
    CONSTRAINT `fk_att_marker`  FOREIGN KEY (`marked_by`)  REFERENCES `users`(`id`)               ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Assignments ──────────────────────────────────────────────
CREATE TABLE `assignments` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(255)    NOT NULL,
    `description` TEXT            DEFAULT NULL,
    `class_id`    BIGINT UNSIGNED NOT NULL,
    `section_id`  BIGINT UNSIGNED DEFAULT NULL,
    `subject_id`  BIGINT UNSIGNED NOT NULL,
    `session_id`  BIGINT UNSIGNED NOT NULL,
    `term_id`     BIGINT UNSIGNED DEFAULT NULL,
    `teacher_id`  BIGINT UNSIGNED NOT NULL,
    `max_score`   DECIMAL(8,2)    DEFAULT NULL,
    `due_date`    DATETIME        NOT NULL,
    `attachment`  VARCHAR(500)    DEFAULT NULL,
    `status`      ENUM('draft','published','closed') NOT NULL DEFAULT 'draft',
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_assign_class`   (`class_id`, `section_id`, `subject_id`),
    INDEX `idx_assign_teacher` (`teacher_id`),
    INDEX `idx_assign_due`     (`due_date`),
    CONSTRAINT `fk_assign_class`   FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)            ON DELETE CASCADE,
    CONSTRAINT `fk_assign_section` FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`)           ON DELETE SET NULL,
    CONSTRAINT `fk_assign_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`)           ON DELETE CASCADE,
    CONSTRAINT `fk_assign_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_assign_term`    FOREIGN KEY (`term_id`)    REFERENCES `terms`(`id`)              ON DELETE SET NULL,
    CONSTRAINT `fk_assign_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`)              ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Assignment Submissions ───────────────────────────────────
CREATE TABLE `assignment_submissions` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `assignment_id` BIGINT UNSIGNED NOT NULL,
    `student_id`    BIGINT UNSIGNED NOT NULL,
    `content`       TEXT            DEFAULT NULL,
    `attachment`    VARCHAR(500)    DEFAULT NULL,
    `score`         DECIMAL(8,2)    DEFAULT NULL,
    `feedback`      TEXT            DEFAULT NULL,
    `graded_by`     BIGINT UNSIGNED DEFAULT NULL,
    `graded_at`     DATETIME        DEFAULT NULL,
    `submitted_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status`        ENUM('submitted','graded','returned','late') NOT NULL DEFAULT 'submitted',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_submission` (`assignment_id`, `student_id`),
    CONSTRAINT `fk_sub_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_sub_student`    FOREIGN KEY (`student_id`)    REFERENCES `students`(`id`)     ON DELETE CASCADE,
    CONSTRAINT `fk_sub_grader`     FOREIGN KEY (`graded_by`)     REFERENCES `users`(`id`)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Exams ────────────────────────────────────────────────────
CREATE TABLE `exams` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(255)    NOT NULL,
    `description` TEXT            DEFAULT NULL,
    `session_id`  BIGINT UNSIGNED NOT NULL,
    `term_id`     BIGINT UNSIGNED DEFAULT NULL,
    `type`        ENUM('midterm','final','quiz','test','practical','mock') NOT NULL DEFAULT 'midterm',
    `start_date`  DATE            NOT NULL,
    `end_date`    DATE            NOT NULL,
    `status`      ENUM('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
    `created_by`  BIGINT UNSIGNED DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_exams_session_term` (`session_id`, `term_id`),
    INDEX `idx_exams_status`       (`status`),
    CONSTRAINT `fk_exams_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_exams_term`    FOREIGN KEY (`term_id`)    REFERENCES `terms`(`id`)             ON DELETE SET NULL,
    CONSTRAINT `fk_exams_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Exam Schedules ───────────────────────────────────────────
CREATE TABLE `exam_schedules` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exam_id`    BIGINT UNSIGNED NOT NULL,
    `class_id`   BIGINT UNSIGNED NOT NULL,
    `subject_id` BIGINT UNSIGNED NOT NULL,
    `exam_date`  DATE            NOT NULL,
    `start_time` TIME            NOT NULL,
    `end_time`   TIME            NOT NULL,
    `room`       VARCHAR(50)     DEFAULT NULL,
    `max_marks`  DECIMAL(8,2)    NOT NULL,
    `pass_marks` DECIMAL(8,2)    NOT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_exam_schedule` (`exam_id`, `class_id`, `subject_id`),
    INDEX `idx_es_class` (`class_id`),
    CONSTRAINT `fk_es_exam`    FOREIGN KEY (`exam_id`)    REFERENCES `exams`(`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_es_class`   FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_es_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Marks ────────────────────────────────────────────────────
CREATE TABLE `marks` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exam_id`          BIGINT UNSIGNED NOT NULL,
    `exam_schedule_id` BIGINT UNSIGNED DEFAULT NULL,
    `student_id`       BIGINT UNSIGNED NOT NULL,
    `class_id`         BIGINT UNSIGNED NOT NULL,
    `subject_id`       BIGINT UNSIGNED NOT NULL,
    `marks_obtained`   DECIMAL(8,2)    DEFAULT NULL,
    `max_marks`        DECIMAL(8,2)    NOT NULL,
    `is_absent`        TINYINT(1)      NOT NULL DEFAULT 0,
    `remarks`          VARCHAR(255)    DEFAULT NULL,
    `entered_by`       BIGINT UNSIGNED DEFAULT NULL,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_marks` (`exam_id`, `student_id`, `subject_id`),
    INDEX `idx_marks_student`        (`student_id`),
    INDEX `idx_marks_class_subject`  (`class_id`, `subject_id`),
    CONSTRAINT `fk_marks_exam`      FOREIGN KEY (`exam_id`)          REFERENCES `exams`(`id`)          ON DELETE CASCADE,
    CONSTRAINT `fk_marks_schedule`  FOREIGN KEY (`exam_schedule_id`) REFERENCES `exam_schedules`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_marks_student`   FOREIGN KEY (`student_id`)       REFERENCES `students`(`id`)       ON DELETE CASCADE,
    CONSTRAINT `fk_marks_class`     FOREIGN KEY (`class_id`)         REFERENCES `classes`(`id`)        ON DELETE CASCADE,
    CONSTRAINT `fk_marks_subject`   FOREIGN KEY (`subject_id`)       REFERENCES `subjects`(`id`)       ON DELETE CASCADE,
    CONSTRAINT `fk_marks_enterer`   FOREIGN KEY (`entered_by`)       REFERENCES `users`(`id`)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Report Cards ─────────────────────────────────────────────
CREATE TABLE `report_cards` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`         BIGINT UNSIGNED NOT NULL,
    `session_id`         BIGINT UNSIGNED NOT NULL,
    `term_id`            BIGINT UNSIGNED DEFAULT NULL,
    `class_id`           BIGINT UNSIGNED NOT NULL,
    `section_id`         BIGINT UNSIGNED DEFAULT NULL,
    `exam_id`            BIGINT UNSIGNED DEFAULT NULL,
    `total_marks`        DECIMAL(10,2)   DEFAULT NULL,
    `total_max_marks`    DECIMAL(10,2)   DEFAULT NULL,
    `percentage`         DECIMAL(5,2)    DEFAULT NULL,
    `grade`              VARCHAR(5)      DEFAULT NULL,
    `rank`               INT UNSIGNED    DEFAULT NULL,
    `attendance_days`    INT UNSIGNED    DEFAULT NULL,
    `absent_days`        INT UNSIGNED    DEFAULT NULL,
    `teacher_remarks`    TEXT            DEFAULT NULL,
    `principal_remarks`  TEXT            DEFAULT NULL,
    `status`             ENUM('draft','published') NOT NULL DEFAULT 'draft',
    `generated_by`       BIGINT UNSIGNED DEFAULT NULL,
    `generated_at`       DATETIME        DEFAULT NULL,
    `created_at`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_report_cards` (`student_id`, `session_id`, `term_id`, `exam_id`),
    INDEX `idx_rc_class` (`class_id`, `section_id`),
    CONSTRAINT `fk_rc_student`    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)            ON DELETE CASCADE,
    CONSTRAINT `fk_rc_session`    FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_rc_term`       FOREIGN KEY (`term_id`)    REFERENCES `terms`(`id`)               ON DELETE SET NULL,
    CONSTRAINT `fk_rc_class`      FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)             ON DELETE CASCADE,
    CONSTRAINT `fk_rc_section`    FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`)            ON DELETE SET NULL,
    CONSTRAINT `fk_rc_exam`       FOREIGN KEY (`exam_id`)    REFERENCES `exams`(`id`)               ON DELETE SET NULL,
    CONSTRAINT `fk_rc_generator`  FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Fee Categories ───────────────────────────────────────────
CREATE TABLE `fee_categories` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100)    NOT NULL,
    `code`        VARCHAR(30)     NOT NULL,
    `description` VARCHAR(255)    DEFAULT NULL,
    `type`        ENUM('tuition','registration','transport','lab','library','exam','uniform','other') NOT NULL DEFAULT 'other',
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_fee_categories_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Fee Structures ───────────────────────────────────────────
CREATE TABLE `fee_structures` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id`      BIGINT UNSIGNED NOT NULL,
    `class_id`        BIGINT UNSIGNED NOT NULL,
    `fee_category_id` BIGINT UNSIGNED NOT NULL,
    `term_id`         BIGINT UNSIGNED DEFAULT NULL,
    `amount`          DECIMAL(12,2)   NOT NULL,
    `due_date`        DATE            DEFAULT NULL,
    `is_mandatory`    TINYINT(1)      NOT NULL DEFAULT 1,
    `description`     VARCHAR(255)    DEFAULT NULL,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_fee_structures` (`session_id`, `class_id`, `fee_category_id`, `term_id`),
    INDEX `idx_fs_class` (`class_id`),
    CONSTRAINT `fk_fs_session`   FOREIGN KEY (`session_id`)      REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fs_class`     FOREIGN KEY (`class_id`)        REFERENCES `classes`(`id`)           ON DELETE CASCADE,
    CONSTRAINT `fk_fs_category`  FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_fs_term`      FOREIGN KEY (`term_id`)         REFERENCES `terms`(`id`)             ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Fee Discounts / Scholarships ─────────────────────────────
CREATE TABLE `fee_discounts` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100)    NOT NULL,
    `type`        ENUM('percentage','fixed') NOT NULL,
    `value`       DECIMAL(12,2)   NOT NULL,
    `description` VARCHAR(255)    DEFAULT NULL,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Fee Discounts ────────────────────────────────────
CREATE TABLE `student_fee_discounts` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`      BIGINT UNSIGNED NOT NULL,
    `fee_discount_id` BIGINT UNSIGNED NOT NULL,
    `session_id`      BIGINT UNSIGNED NOT NULL,
    `fee_category_id` BIGINT UNSIGNED DEFAULT NULL,
    `notes`           VARCHAR(255)    DEFAULT NULL,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_sfd_student` (`student_id`),
    CONSTRAINT `fk_sfd_student`   FOREIGN KEY (`student_id`)      REFERENCES `students`(`id`)           ON DELETE CASCADE,
    CONSTRAINT `fk_sfd_discount`  FOREIGN KEY (`fee_discount_id`) REFERENCES `fee_discounts`(`id`)      ON DELETE CASCADE,
    CONSTRAINT `fk_sfd_session`   FOREIGN KEY (`session_id`)      REFERENCES `academic_sessions`(`id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_sfd_category`  FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Invoices ─────────────────────────────────────────────────
CREATE TABLE `invoices` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_no`       VARCHAR(50)     NOT NULL,
    `student_id`       BIGINT UNSIGNED NOT NULL,
    `session_id`       BIGINT UNSIGNED NOT NULL,
    `term_id`          BIGINT UNSIGNED DEFAULT NULL,
    `class_id`         BIGINT UNSIGNED DEFAULT NULL,
    `subtotal`         DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `discount_amount`  DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `fine_amount`      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `total_amount`     DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `paid_amount`      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `balance`          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `status`           ENUM('draft','issued','partial','paid','overdue','cancelled','refunded') NOT NULL DEFAULT 'draft',
    `due_date`         DATE            DEFAULT NULL,
    `issued_date`      DATE            DEFAULT NULL,
    `notes`            TEXT            DEFAULT NULL,
    `discount_reason`  VARCHAR(255)    DEFAULT NULL,
    `fine_reason`      VARCHAR(255)    DEFAULT NULL,
    `created_by`       BIGINT UNSIGNED DEFAULT NULL,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_invoices_no` (`invoice_no`),
    INDEX `idx_inv_student` (`student_id`),
    INDEX `idx_inv_status`  (`status`),
    INDEX `idx_inv_session` (`session_id`, `term_id`),
    INDEX `idx_inv_due`     (`due_date`),
    CONSTRAINT `fk_inv_student`  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)            ON DELETE CASCADE,
    CONSTRAINT `fk_inv_session`  FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_inv_term`     FOREIGN KEY (`term_id`)    REFERENCES `terms`(`id`)               ON DELETE SET NULL,
    CONSTRAINT `fk_inv_class`    FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)             ON DELETE SET NULL,
    CONSTRAINT `fk_inv_creator`  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)               ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Invoice Items ────────────────────────────────────────────
CREATE TABLE `invoice_items` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id`         BIGINT UNSIGNED NOT NULL,
    `fee_category_id`    BIGINT UNSIGNED DEFAULT NULL,
    `fee_structure_id`   BIGINT UNSIGNED DEFAULT NULL,
    `description`        VARCHAR(255)    NOT NULL,
    `amount`             DECIMAL(12,2)   NOT NULL,
    `quantity`           INT UNSIGNED    NOT NULL DEFAULT 1,
    `total`              DECIMAL(12,2)   NOT NULL,
    `created_at`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ii_invoice` (`invoice_id`),
    CONSTRAINT `fk_ii_invoice`    FOREIGN KEY (`invoice_id`)       REFERENCES `invoices`(`id`)        ON DELETE CASCADE,
    CONSTRAINT `fk_ii_category`   FOREIGN KEY (`fee_category_id`)  REFERENCES `fee_categories`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ii_structure`  FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payments ─────────────────────────────────────────────────
CREATE TABLE `payments` (
    `id`                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `receipt_no`              VARCHAR(50)     NOT NULL,
    `invoice_id`              BIGINT UNSIGNED NOT NULL,
    `student_id`              BIGINT UNSIGNED NOT NULL,
    `amount`                  DECIMAL(12,2)   NOT NULL,
    `method`                  ENUM('cash','bank_transfer','cheque','gateway','other') NOT NULL DEFAULT 'cash',
    `reference`               VARCHAR(255)    DEFAULT NULL,
    `notes`                   TEXT            DEFAULT NULL,
    `payment_date`            DATE            NOT NULL,
    `received_by`             BIGINT UNSIGNED DEFAULT NULL,
    `gateway_transaction_id`  BIGINT UNSIGNED DEFAULT NULL COMMENT 'Soft ref to payment_transactions.id',
    `status`                  ENUM('completed','pending','failed','reversed') NOT NULL DEFAULT 'completed',
    `created_at`              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_payments_receipt` (`receipt_no`),
    INDEX `idx_pay_invoice` (`invoice_id`),
    INDEX `idx_pay_student` (`student_id`),
    INDEX `idx_pay_date`    (`payment_date`),
    CONSTRAINT `fk_pay_invoice`   FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_pay_student`   FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_pay_receiver`  FOREIGN KEY (`received_by`) REFERENCES `users`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payment Gateways ────────────────────────────────────────
CREATE TABLE `payment_gateways` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`         VARCHAR(50)     NOT NULL,
    `slug`         VARCHAR(50)     NOT NULL,
    `display_name` VARCHAR(100)    NOT NULL,
    `description`  VARCHAR(255)    DEFAULT NULL,
    `logo`         VARCHAR(255)    DEFAULT NULL,
    `is_active`    TINYINT(1)      NOT NULL DEFAULT 0,
    `environment`  ENUM('sandbox','production') NOT NULL DEFAULT 'sandbox',
    `config_json`  TEXT            DEFAULT NULL,
    `sort_order`   INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_gw_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payment Transactions ─────────────────────────────────────
CREATE TABLE `payment_transactions` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_ref`   VARCHAR(100)    NOT NULL,
    `gateway_id`        BIGINT UNSIGNED NOT NULL,
    `invoice_id`        BIGINT UNSIGNED DEFAULT NULL,
    `student_id`        BIGINT UNSIGNED DEFAULT NULL,
    `user_id`           BIGINT UNSIGNED DEFAULT NULL,
    `amount`            DECIMAL(12,2)   NOT NULL,
    `currency`          VARCHAR(10)     NOT NULL DEFAULT 'ETB',
    `status`            ENUM('pending','success','failed','cancelled','refunded','expired') NOT NULL DEFAULT 'pending',
    `gateway_reference` VARCHAR(255)    DEFAULT NULL,
    `gateway_status`    VARCHAR(100)    DEFAULT NULL,
    `gateway_response`  TEXT            DEFAULT NULL,
    `checkout_url`      VARCHAR(1000)   DEFAULT NULL,
    `return_url`        VARCHAR(1000)   DEFAULT NULL,
    `idempotency_key`   VARCHAR(100)    NOT NULL,
    `description`       VARCHAR(255)    DEFAULT NULL,
    `metadata`          JSON            DEFAULT NULL,
    `paid_at`           DATETIME        DEFAULT NULL,
    `expires_at`        DATETIME        DEFAULT NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_txn_ref`          (`transaction_ref`),
    UNIQUE KEY `uk_txn_idempotency`  (`idempotency_key`),
    INDEX `idx_txn_gateway`     (`gateway_id`),
    INDEX `idx_txn_invoice`     (`invoice_id`),
    INDEX `idx_txn_student`     (`student_id`),
    INDEX `idx_txn_status`      (`status`),
    INDEX `idx_txn_gateway_ref` (`gateway_reference`),
    INDEX `idx_txn_created`     (`created_at`),
    CONSTRAINT `fk_txn_gateway` FOREIGN KEY (`gateway_id`) REFERENCES `payment_gateways`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_txn_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`)          ON DELETE SET NULL,
    CONSTRAINT `fk_txn_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)          ON DELETE SET NULL,
    CONSTRAINT `fk_txn_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)             ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payment Attempts ─────────────────────────────────────────
CREATE TABLE `payment_attempts` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_id`   BIGINT UNSIGNED NOT NULL,
    `attempt_no`       INT UNSIGNED    NOT NULL DEFAULT 1,
    `status`           ENUM('initiated','redirected','success','failed','timeout','error') NOT NULL DEFAULT 'initiated',
    `gateway_request`  TEXT            DEFAULT NULL,
    `gateway_response` TEXT            DEFAULT NULL,
    `error_code`       VARCHAR(100)    DEFAULT NULL,
    `error_message`    VARCHAR(500)    DEFAULT NULL,
    `ip_address`       VARCHAR(45)     DEFAULT NULL,
    `user_agent`       VARCHAR(500)    DEFAULT NULL,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_pa_txn` (`transaction_id`),
    CONSTRAINT `fk_pa_txn` FOREIGN KEY (`transaction_id`) REFERENCES `payment_transactions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payment Webhooks Log ─────────────────────────────────────
CREATE TABLE `payment_webhooks` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `gateway_id`         BIGINT UNSIGNED DEFAULT NULL,
    `gateway_slug`       VARCHAR(50)     NOT NULL,
    `event_type`         VARCHAR(100)    DEFAULT NULL,
    `payload`            TEXT            NOT NULL,
    `headers`            TEXT            DEFAULT NULL,
    `signature`          VARCHAR(500)    DEFAULT NULL,
    `signature_valid`    TINYINT(1)      DEFAULT NULL,
    `transaction_ref`    VARCHAR(100)    DEFAULT NULL,
    `processing_status`  ENUM('received','processed','failed','duplicate','invalid') NOT NULL DEFAULT 'received',
    `processing_notes`   VARCHAR(500)    DEFAULT NULL,
    `ip_address`         VARCHAR(45)     DEFAULT NULL,
    `received_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `processed_at`       DATETIME        DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_wh_gateway`  (`gateway_slug`),
    INDEX `idx_wh_txn_ref`  (`transaction_ref`),
    INDEX `idx_wh_status`   (`processing_status`),
    INDEX `idx_wh_received` (`received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payment Reconciliation Logs ──────────────────────────────
CREATE TABLE `payment_reconciliation_logs` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_id`   BIGINT UNSIGNED DEFAULT NULL,
    `gateway_id`       BIGINT UNSIGNED DEFAULT NULL,
    `action`           ENUM('status_check','mark_success','mark_failed','mark_expired','manual_override') NOT NULL,
    `previous_status`  VARCHAR(50)     DEFAULT NULL,
    `new_status`       VARCHAR(50)     DEFAULT NULL,
    `gateway_response` TEXT            DEFAULT NULL,
    `notes`            VARCHAR(500)    DEFAULT NULL,
    `performed_by`     BIGINT UNSIGNED DEFAULT NULL,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_recon_txn` (`transaction_id`),
    CONSTRAINT `fk_recon_txn`       FOREIGN KEY (`transaction_id`) REFERENCES `payment_transactions`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_recon_performer` FOREIGN KEY (`performed_by`)   REFERENCES `users`(`id`)               ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Invoice → Payment Links ───────────────────────────────────
CREATE TABLE `invoice_payment_links` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id`     BIGINT UNSIGNED NOT NULL,
    `transaction_id` BIGINT UNSIGNED NOT NULL,
    `amount`         DECIMAL(12,2)   NOT NULL,
    `is_partial`     TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ipl_invoice` (`invoice_id`),
    INDEX `idx_ipl_txn`     (`transaction_id`),
    CONSTRAINT `fk_ipl_invoice` FOREIGN KEY (`invoice_id`)     REFERENCES `invoices`(`id`)             ON DELETE CASCADE,
    CONSTRAINT `fk_ipl_txn`     FOREIGN KEY (`transaction_id`) REFERENCES `payment_transactions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Announcements ────────────────────────────────────────────
CREATE TABLE `announcements` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`           VARCHAR(255)    NOT NULL,
    `content`         TEXT            NOT NULL,
    `type`            ENUM('general','academic','event','emergency','other') NOT NULL DEFAULT 'general',
    `target_roles`    VARCHAR(255)    DEFAULT NULL,
    `target_classes`  VARCHAR(255)    DEFAULT NULL,
    `attachment`      VARCHAR(500)    DEFAULT NULL,
    `is_pinned`       TINYINT(1)      NOT NULL DEFAULT 0,
    `status`          ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    `published_at`    DATETIME        DEFAULT NULL,
    `expires_at`      DATETIME        DEFAULT NULL,
    `created_by`      BIGINT UNSIGNED DEFAULT NULL,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ann_status`    (`status`),
    INDEX `idx_ann_published` (`published_at`),
    INDEX `idx_ann_type`      (`type`),
    CONSTRAINT `fk_ann_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Messages (Internal) ──────────────────────────────────────
CREATE TABLE `messages` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sender_id`   BIGINT UNSIGNED NOT NULL,
    `receiver_id` BIGINT UNSIGNED NOT NULL,
    `subject`     VARCHAR(255)    DEFAULT NULL,
    `body`        TEXT            NOT NULL,
    `attachment`  VARCHAR(500)    DEFAULT NULL,
    `is_read`     TINYINT(1)      NOT NULL DEFAULT 0,
    `read_at`     DATETIME        DEFAULT NULL,
    `parent_id`   BIGINT UNSIGNED DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_msg_sender`   (`sender_id`),
    INDEX `idx_msg_receiver` (`receiver_id`, `is_read`),
    INDEX `idx_msg_parent`   (`parent_id`),
    CONSTRAINT `fk_msg_sender`   FOREIGN KEY (`sender_id`)   REFERENCES `users`(`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_msg_parent`   FOREIGN KEY (`parent_id`)   REFERENCES `messages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Notifications ────────────────────────────────────────────
CREATE TABLE `notifications` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `type`       VARCHAR(50)     NOT NULL,
    `title`      VARCHAR(255)    NOT NULL,
    `message`    TEXT            DEFAULT NULL,
    `link`       VARCHAR(500)    DEFAULT NULL,
    `data`       JSON            DEFAULT NULL,
    `is_read`    TINYINT(1)      NOT NULL DEFAULT 0,
    `read_at`    DATETIME        DEFAULT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_notif_user`    (`user_id`, `is_read`),
    INDEX `idx_notif_type`    (`type`),
    INDEX `idx_notif_created` (`created_at`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Audit Logs ───────────────────────────────────────────────
CREATE TABLE `audit_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED DEFAULT NULL,
    `action`      VARCHAR(100)    NOT NULL,
    `module`      VARCHAR(50)     NOT NULL,
    `entity_type` VARCHAR(50)     DEFAULT NULL,
    `entity_id`   BIGINT UNSIGNED DEFAULT NULL,
    `old_values`  JSON            DEFAULT NULL,
    `new_values`  JSON            DEFAULT NULL,
    `ip_address`  VARCHAR(45)     DEFAULT NULL,
    `user_agent`  VARCHAR(500)    DEFAULT NULL,
    `description` TEXT            DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_audit_user`    (`user_id`),
    INDEX `idx_audit_action`  (`action`),
    INDEX `idx_audit_module`  (`module`),
    INDEX `idx_audit_entity`  (`entity_type`, `entity_id`),
    INDEX `idx_audit_created` (`created_at`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Settings ─────────────────────────────────────────────────
CREATE TABLE `settings` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_group` VARCHAR(50)     NOT NULL DEFAULT 'general',
    `setting_key`   VARCHAR(100)    NOT NULL,
    `setting_value` TEXT            DEFAULT NULL,
    `setting_type`  ENUM('string','integer','boolean','json','text','textarea','number') NOT NULL DEFAULT 'string',
    `description`   VARCHAR(255)    DEFAULT NULL,
    `is_public`     TINYINT(1)      NOT NULL DEFAULT 0,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_settings_key`    (`setting_group`, `setting_key`),
    INDEX `idx_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Assessments (Results Module) ─────────────────────────────
CREATE TABLE `assessments` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(255)    NOT NULL,
    `description` TEXT            DEFAULT NULL,
    `class_id`    BIGINT UNSIGNED NOT NULL,
    `subject_id`  BIGINT UNSIGNED NOT NULL,
    `session_id`  BIGINT UNSIGNED NOT NULL,
    `term_id`     BIGINT UNSIGNED DEFAULT NULL,
    `total_marks` DECIMAL(8,2)    NOT NULL DEFAULT 100.00 COMMENT 'Always 100',
    `created_by`  BIGINT UNSIGNED DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_assess_class_subject` (`class_id`, `subject_id`, `session_id`, `term_id`),
    CONSTRAINT `fk_assess_class`   FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)            ON DELETE CASCADE,
    CONSTRAINT `fk_assess_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`)           ON DELETE CASCADE,
    CONSTRAINT `fk_assess_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_assess_term`    FOREIGN KEY (`term_id`)    REFERENCES `terms`(`id`)              ON DELETE SET NULL,
    CONSTRAINT `fk_assess_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)              ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Results ──────────────────────────────────────────
CREATE TABLE `student_results` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `assessment_id`  BIGINT UNSIGNED NOT NULL,
    `student_id`     BIGINT UNSIGNED NOT NULL,
    `class_id`       BIGINT UNSIGNED NOT NULL,
    `section_id`     BIGINT UNSIGNED DEFAULT NULL,
    `marks_obtained` DECIMAL(8,2)    DEFAULT NULL COMMENT 'NULL = not yet entered',
    `is_absent`      TINYINT(1)      NOT NULL DEFAULT 0,
    `remarks`        VARCHAR(255)    DEFAULT NULL,
    `entered_by`     BIGINT UNSIGNED DEFAULT NULL,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_student_result` (`assessment_id`, `student_id`),
    INDEX `idx_sr_student` (`student_id`),
    INDEX `idx_sr_class`   (`class_id`, `section_id`),
    CONSTRAINT `fk_sr_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `assessments`(`id`)       ON DELETE CASCADE,
    CONSTRAINT `fk_sr_student`    FOREIGN KEY (`student_id`)    REFERENCES `students`(`id`)          ON DELETE CASCADE,
    CONSTRAINT `fk_sr_class`      FOREIGN KEY (`class_id`)      REFERENCES `classes`(`id`)           ON DELETE CASCADE,
    CONSTRAINT `fk_sr_section`    FOREIGN KEY (`section_id`)    REFERENCES `sections`(`id`)          ON DELETE SET NULL,
    CONSTRAINT `fk_sr_enterer`    FOREIGN KEY (`entered_by`)    REFERENCES `users`(`id`)             ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Conduct ──────────────────────────────────────────
-- Behavioral assessment — completely independent of academic marks.
-- A=Excellent, B=Very Good, C=Good, D=Satisfactory, F=Needs Improvement
CREATE TABLE `student_conduct` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `class_id`   BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `term_id`    BIGINT UNSIGNED DEFAULT NULL,
    `conduct`    ENUM('A','B','C','D','F') NOT NULL DEFAULT 'B'
                 COMMENT 'A=Excellent, B=Very Good, C=Good, D=Satisfactory, F=Needs Improvement',
    `remarks`    VARCHAR(255)    DEFAULT NULL,
    `entered_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_conduct` (`student_id`, `class_id`, `session_id`, `term_id`),
    INDEX `idx_conduct_class_term` (`class_id`, `session_id`, `term_id`),
    CONSTRAINT `fk_conduct_student`  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`)            ON DELETE CASCADE,
    CONSTRAINT `fk_conduct_class`    FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)             ON DELETE CASCADE,
    CONSTRAINT `fk_conduct_session`  FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_conduct_term`     FOREIGN KEY (`term_id`)    REFERENCES `terms`(`id`)               ON DELETE SET NULL,
    CONSTRAINT `fk_conduct_enterer`  FOREIGN KEY (`entered_by`) REFERENCES `users`(`id`)               ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-student behavioral conduct grade per class/term — independent of academic marks.';

-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- schema.sql complete — 54 tables
-- ============================================================
