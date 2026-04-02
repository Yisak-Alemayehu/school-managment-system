-- ============================================================
-- Urji Beri School SMS — COMPLETE DATABASE SCHEMA
-- Consolidated from all modules: Core, Finance, HR, Messaging
-- Fresh install: run this file first, then seed.sql
-- Generated: 2026-03-09
-- ============================================================
-- Requirements: MySQL 8.0+ / MariaDB 10.5+
-- Charset: utf8mb4 with unicode_ci collation
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ############################################################
--  SECTION 1: DROP ALL TABLES (reverse FK order)
-- ############################################################

-- ── Messaging Module ──
DROP TABLE IF EXISTS `msg_attachments`;
DROP TABLE IF EXISTS `msg_message_status`;
DROP TABLE IF EXISTS `msg_messages`;
DROP TABLE IF EXISTS `msg_group_members`;
DROP TABLE IF EXISTS `msg_groups`;
DROP TABLE IF EXISTS `msg_conversation_participants`;
DROP TABLE IF EXISTS `msg_conversations`;

-- ── HR & Payroll Module ──
DROP TABLE IF EXISTS `hr_employee_allowances`;
DROP TABLE IF EXISTS `hr_attendance_logs`;
DROP TABLE IF EXISTS `hr_employee_biometric`;
DROP TABLE IF EXISTS `hr_attendance`;
DROP TABLE IF EXISTS `hr_attendance_devices`;
DROP TABLE IF EXISTS `hr_leave_requests`;
DROP TABLE IF EXISTS `hr_leave_types`;
DROP TABLE IF EXISTS `hr_holidays`;
DROP TABLE IF EXISTS `hr_payroll_records`;
DROP TABLE IF EXISTS `hr_payroll_periods`;
DROP TABLE IF EXISTS `hr_employee_documents`;
DROP TABLE IF EXISTS `hr_employees`;
DROP TABLE IF EXISTS `hr_departments`;

-- ── Finance Module ──
DROP TABLE IF EXISTS `fin_penalty_log`;
DROP TABLE IF EXISTS `fin_supplementary_transactions`;
DROP TABLE IF EXISTS `fin_supplementary_fees`;
DROP TABLE IF EXISTS `fin_varying_penalties`;
DROP TABLE IF EXISTS `fin_transactions`;
DROP TABLE IF EXISTS `fin_student_fees`;
DROP TABLE IF EXISTS `fin_fee_classes`;
DROP TABLE IF EXISTS `fin_fees`;
DROP TABLE IF EXISTS `fin_group_members`;
DROP TABLE IF EXISTS `fin_groups`;

-- ── Core Module ──
DROP TABLE IF EXISTS `student_conduct`;
DROP TABLE IF EXISTS `student_results`;
DROP TABLE IF EXISTS `assessments`;
DROP TABLE IF EXISTS `student_elective_subjects`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `report_cards`;
DROP TABLE IF EXISTS `marks`;
DROP TABLE IF EXISTS `exam_schedules`;
DROP TABLE IF EXISTS `exams`;
DROP TABLE IF EXISTS `grade_scale_entries`;
DROP TABLE IF EXISTS `grade_scales`;
DROP TABLE IF EXISTS `assignment_submissions`;
DROP TABLE IF EXISTS `assignments`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `timetables`;
DROP TABLE IF EXISTS `student_documents`;
DROP TABLE IF EXISTS `promotions`;
DROP TABLE IF EXISTS `enrollments`;
DROP TABLE IF EXISTS `student_guardians`;
DROP TABLE IF EXISTS `guardians`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `class_teachers`;
DROP TABLE IF EXISTS `class_subjects`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `sections`;
DROP TABLE IF EXISTS `classes`;
DROP TABLE IF EXISTS `shifts`;
DROP TABLE IF EXISTS `streams`;
DROP TABLE IF EXISTS `mediums`;
DROP TABLE IF EXISTS `terms`;
DROP TABLE IF EXISTS `academic_sessions`;
DROP TABLE IF EXISTS `user_roles`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;


-- ############################################################
--  SECTION 2: CORE TABLES — Auth & RBAC
-- ############################################################

-- ── Roles ──
CREATE TABLE `roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Permissions ──
CREATE TABLE `permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `module` VARCHAR(50) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_permissions_module_action` (`module`, `action`),
    INDEX `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Role → Permissions ──
CREATE TABLE `role_permissions` (
    `role_id` BIGINT UNSIGNED NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Users ──
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(200) NOT NULL,
    `first_name` VARCHAR(100) DEFAULT NULL,
    `last_name` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `gender` ENUM('male','female','other') DEFAULT NULL,
    `date_of_birth` DATE DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `force_password_change` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    `email_verified_at` DATETIME DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    `password_reset_token` VARCHAR(255) DEFAULT NULL,
    `password_reset_expires` DATETIME DEFAULT NULL,
    `login_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_username` (`username`),
    UNIQUE KEY `uk_users_email` (`email`),
    INDEX `idx_users_status` (`status`),
    INDEX `idx_users_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── User → Roles ──
CREATE TABLE `user_roles` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `role_id`),
    CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Login Attempts Log ──
CREATE TABLE `login_attempts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username_or_email` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_login_attempts_identifier` (`username_or_email`, `attempted_at`),
    INDEX `idx_login_attempts_ip` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ############################################################
--  SECTION 3: CORE TABLES — Academics
-- ############################################################

-- ── Academic Sessions ──
CREATE TABLE `academic_sessions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_sessions_slug` (`slug`),
    INDEX `idx_sessions_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Terms / Semesters ──
CREATE TABLE `terms` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_terms_session_slug` (`session_id`, `slug`),
    INDEX `idx_terms_active` (`is_active`),
    CONSTRAINT `fk_terms_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Mediums (Language of Instruction) ──
CREATE TABLE `mediums` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Streams (Academic Track) ──
CREATE TABLE `streams` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Shifts ──
CREATE TABLE `shifts` (
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

-- ── Classes ──
CREATE TABLE `classes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `numeric_name` INT UNSIGNED DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `medium_id` BIGINT UNSIGNED DEFAULT NULL,
    `stream_id` BIGINT UNSIGNED DEFAULT NULL,
    `shift_id` BIGINT UNSIGNED DEFAULT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_classes_slug` (`slug`),
    INDEX `idx_classes_medium` (`medium_id`),
    INDEX `idx_classes_stream` (`stream_id`),
    INDEX `idx_classes_shift` (`shift_id`),
    CONSTRAINT `fk_classes_medium` FOREIGN KEY (`medium_id`) REFERENCES `mediums`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_classes_stream` FOREIGN KEY (`stream_id`) REFERENCES `streams`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_classes_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Sections ──
CREATE TABLE `sections` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(50) NOT NULL,
    `capacity` INT UNSIGNED DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_sections_class_name` (`class_id`, `name`),
    CONSTRAINT `fk_sections_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Subjects ──
CREATE TABLE `subjects` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `type` ENUM('theory','practical','both') NOT NULL DEFAULT 'theory',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_subjects_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Class → Subjects ──
CREATE TABLE `class_subjects` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `subject_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `is_elective` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_class_subjects` (`class_id`, `subject_id`, `session_id`),
    INDEX `idx_cs_session` (`session_id`),
    CONSTRAINT `fk_cs_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cs_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cs_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Class → Teachers (teacher-class-subject mapping) ──
CREATE TABLE `class_teachers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `section_id` BIGINT UNSIGNED DEFAULT NULL,
    `subject_id` BIGINT UNSIGNED DEFAULT NULL,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `is_class_teacher` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_class_teachers` (`class_id`, `section_id`, `subject_id`, `teacher_id`, `session_id`),
    INDEX `idx_ct_teacher` (`teacher_id`),
    INDEX `idx_ct_session` (`session_id`),
    CONSTRAINT `fk_ct_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ct_section` FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ct_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ct_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ct_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Timetables ──
CREATE TABLE `timetables` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED DEFAULT NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `section_id` BIGINT UNSIGNED DEFAULT NULL,
    `subject_id` BIGINT UNSIGNED NOT NULL,
    `teacher_id` BIGINT UNSIGNED DEFAULT NULL,
    `day_of_week` ENUM('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `room` VARCHAR(50) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_tt_class_day` (`class_id`, `section_id`, `day_of_week`),
    INDEX `idx_tt_teacher_day` (`teacher_id`, `day_of_week`),
    CONSTRAINT `fk_tt_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tt_term` FOREIGN KEY (`term_id`) REFERENCES `terms`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tt_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tt_section` FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tt_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tt_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ############################################################
--  SECTION 4: CORE TABLES — Students & Guardians
-- ############################################################

-- ── Students ──
CREATE TABLE `students` (
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
    `sub_city` VARCHAR(100) DEFAULT NULL,
    `woreda` VARCHAR(100) DEFAULT NULL,
    `house_number` VARCHAR(50) DEFAULT NULL,
    `region` VARCHAR(100) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT 'Ethiopian',
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

-- ── Guardians / Parents ──
CREATE TABLE `guardians` (
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
    INDEX `idx_guardians_name` (`first_name`, `last_name`),
    CONSTRAINT `fk_guardians_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student ↔ Guardian (M2M) ──
CREATE TABLE `student_guardians` (
    `student_id` BIGINT UNSIGNED NOT NULL,
    `guardian_id` BIGINT UNSIGNED NOT NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `relationship` VARCHAR(50) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`student_id`, `guardian_id`),
    CONSTRAINT `fk_sg_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sg_guardian` FOREIGN KEY (`guardian_id`) REFERENCES `guardians`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Enrollments (session/class/section) ──
CREATE TABLE `enrollments` (
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
    INDEX `idx_enrollments_student_status` (`student_id`, `status`),
    CONSTRAINT `fk_enr_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_enr_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_enr_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_enr_section` FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Promotions ──
CREATE TABLE `promotions` (
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

-- ── Student Documents ──
CREATE TABLE `student_documents` (
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

-- ── Student Elective Subject Choices ──
CREATE TABLE `student_elective_subjects` (
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


-- ############################################################
--  SECTION 5: CORE TABLES — Attendance & Assignments
-- ############################################################

-- ── Attendance ──
CREATE TABLE `attendance` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `section_id` BIGINT UNSIGNED DEFAULT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED DEFAULT NULL,
    `subject_id` BIGINT UNSIGNED DEFAULT NULL,
    `date` DATE NOT NULL,
    `period` TINYINT UNSIGNED DEFAULT NULL,
    `status` ENUM('present','absent','late','excused','half_day') NOT NULL,
    `remarks` VARCHAR(255) DEFAULT NULL,
    `marked_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_attendance_daily` (`student_id`, `class_id`, `date`, `subject_id`, `period`),
    INDEX `idx_att_date` (`date`),
    INDEX `idx_att_class_date` (`class_id`, `section_id`, `date`),
    INDEX `idx_att_session_term` (`session_id`, `term_id`),
    CONSTRAINT `fk_att_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_att_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_att_section` FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_att_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_att_term` FOREIGN KEY (`term_id`) REFERENCES `terms`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_att_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_att_marker` FOREIGN KEY (`marked_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Assignments ──
CREATE TABLE `assignments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `section_id` BIGINT UNSIGNED DEFAULT NULL,
    `subject_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED DEFAULT NULL,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `max_score` DECIMAL(8,2) DEFAULT NULL,
    `due_date` DATETIME NOT NULL,
    `attachment` VARCHAR(500) DEFAULT NULL,
    `status` ENUM('draft','published','closed') NOT NULL DEFAULT 'draft',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_assign_class` (`class_id`, `section_id`, `subject_id`),
    INDEX `idx_assign_teacher` (`teacher_id`),
    INDEX `idx_assign_due` (`due_date`),
    CONSTRAINT `fk_assign_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_assign_section` FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_assign_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_assign_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_assign_term` FOREIGN KEY (`term_id`) REFERENCES `terms`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_assign_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Assignment Submissions ──
CREATE TABLE `assignment_submissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `assignment_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `content` TEXT DEFAULT NULL,
    `attachment` VARCHAR(500) DEFAULT NULL,
    `score` DECIMAL(8,2) DEFAULT NULL,
    `feedback` TEXT DEFAULT NULL,
    `graded_by` BIGINT UNSIGNED DEFAULT NULL,
    `graded_at` DATETIME DEFAULT NULL,
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('submitted','graded','returned','late') NOT NULL DEFAULT 'submitted',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_submission` (`assignment_id`, `student_id`),
    CONSTRAINT `fk_sub_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sub_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sub_grader` FOREIGN KEY (`graded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ############################################################
--  SECTION 6: CORE TABLES — Exams, Marks & Assessments
-- ############################################################

-- ── Exams ──
CREATE TABLE `exams` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED DEFAULT NULL,
    `type` ENUM('midterm','final','quiz','test','practical','mock') NOT NULL DEFAULT 'midterm',
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `status` ENUM('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_exams_session_term` (`session_id`, `term_id`),
    INDEX `idx_exams_status` (`status`),
    CONSTRAINT `fk_exams_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_exams_term` FOREIGN KEY (`term_id`) REFERENCES `terms`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_exams_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Exam Schedules ──
CREATE TABLE `exam_schedules` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exam_id` BIGINT UNSIGNED NOT NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `subject_id` BIGINT UNSIGNED NOT NULL,
    `exam_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `room` VARCHAR(50) DEFAULT NULL,
    `max_marks` DECIMAL(8,2) NOT NULL,
    `pass_marks` DECIMAL(8,2) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_exam_schedule` (`exam_id`, `class_id`, `subject_id`),
    INDEX `idx_es_class` (`class_id`),
    CONSTRAINT `fk_es_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_es_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_es_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Marks ──
CREATE TABLE `marks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exam_id` BIGINT UNSIGNED NOT NULL,
    `exam_schedule_id` BIGINT UNSIGNED DEFAULT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `subject_id` BIGINT UNSIGNED NOT NULL,
    `marks_obtained` DECIMAL(8,2) DEFAULT NULL,
    `max_marks` DECIMAL(8,2) NOT NULL,
    `is_absent` TINYINT(1) NOT NULL DEFAULT 0,
    `remarks` VARCHAR(255) DEFAULT NULL,
    `entered_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_marks` (`exam_id`, `student_id`, `subject_id`),
    INDEX `idx_marks_student` (`student_id`),
    INDEX `idx_marks_class_subject` (`class_id`, `subject_id`),
    CONSTRAINT `fk_marks_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_marks_schedule` FOREIGN KEY (`exam_schedule_id`) REFERENCES `exam_schedules`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_marks_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_marks_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_marks_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_marks_enterer` FOREIGN KEY (`entered_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Grade Scales ──
CREATE TABLE `grade_scales` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `grade_scale_entries` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `grade_scale_id` BIGINT UNSIGNED NOT NULL,
    `grade` VARCHAR(5) NOT NULL,
    `min_percentage` DECIMAL(5,2) NOT NULL,
    `max_percentage` DECIMAL(5,2) NOT NULL,
    `grade_point` DECIMAL(4,2) DEFAULT NULL,
    `remark` VARCHAR(50) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_gse_scale` (`grade_scale_id`),
    CONSTRAINT `fk_gse_scale` FOREIGN KEY (`grade_scale_id`) REFERENCES `grade_scales`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Report Cards ──
CREATE TABLE `report_cards` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED DEFAULT NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `section_id` BIGINT UNSIGNED DEFAULT NULL,
    `exam_id` BIGINT UNSIGNED DEFAULT NULL,
    `total_marks` DECIMAL(10,2) DEFAULT NULL,
    `total_max_marks` DECIMAL(10,2) DEFAULT NULL,
    `percentage` DECIMAL(5,2) DEFAULT NULL,
    `grade` VARCHAR(5) DEFAULT NULL,
    `rank` INT UNSIGNED DEFAULT NULL,
    `attendance_days` INT UNSIGNED DEFAULT NULL,
    `absent_days` INT UNSIGNED DEFAULT NULL,
    `teacher_remarks` TEXT DEFAULT NULL,
    `principal_remarks` TEXT DEFAULT NULL,
    `status` ENUM('draft','published') NOT NULL DEFAULT 'draft',
    `generated_by` BIGINT UNSIGNED DEFAULT NULL,
    `generated_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_report_cards` (`student_id`, `session_id`, `term_id`, `exam_id`),
    INDEX `idx_rc_class` (`class_id`, `section_id`),
    CONSTRAINT `fk_rc_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rc_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rc_term` FOREIGN KEY (`term_id`) REFERENCES `terms`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rc_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rc_section` FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rc_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rc_generator` FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Assessments ──
CREATE TABLE `assessments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `subject_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED DEFAULT NULL,
    `total_marks` DECIMAL(8,2) NOT NULL DEFAULT 100.00 COMMENT 'Always 100',
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_assessment` (`name`(100), `class_id`, `subject_id`, `session_id`, `term_id`),
    INDEX `idx_assess_class_subject` (`class_id`, `subject_id`, `session_id`, `term_id`),
    CONSTRAINT `fk_assess_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_assess_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_assess_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_assess_term` FOREIGN KEY (`term_id`) REFERENCES `terms`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_assess_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Results ──
CREATE TABLE `student_results` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `assessment_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `section_id` BIGINT UNSIGNED DEFAULT NULL,
    `marks_obtained` DECIMAL(8,2) DEFAULT NULL COMMENT 'NULL = not yet entered',
    `is_absent` TINYINT(1) NOT NULL DEFAULT 0,
    `remarks` VARCHAR(255) DEFAULT NULL,
    `entered_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_student_result` (`assessment_id`, `student_id`),
    INDEX `idx_sr_student` (`student_id`),
    INDEX `idx_sr_class` (`class_id`, `section_id`),
    CONSTRAINT `fk_sr_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `assessments`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sr_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sr_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sr_section` FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_sr_enterer` FOREIGN KEY (`entered_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Conduct ──
CREATE TABLE `student_conduct` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED DEFAULT NULL,
    `conduct` ENUM('A','B','C','D','F') NOT NULL DEFAULT 'B'
              COMMENT 'A=Excellent, B=Very Good, C=Good, D=Satisfactory, F=Needs Improvement',
    `remarks` VARCHAR(255) DEFAULT NULL COMMENT 'Optional teacher note on behavior',
    `entered_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_conduct` (`student_id`, `class_id`, `session_id`, `term_id`),
    INDEX `idx_conduct_class_term` (`class_id`, `session_id`, `term_id`),
    CONSTRAINT `fk_conduct_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_conduct_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_conduct_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_conduct_term` FOREIGN KEY (`term_id`) REFERENCES `terms`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_conduct_enterer` FOREIGN KEY (`entered_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-student behavioral conduct grade per class/term.';


-- ############################################################
--  SECTION 7: CORE TABLES — Communication & System
-- ############################################################

-- ── Announcements ──
CREATE TABLE `announcements` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `type` ENUM('general','academic','event','emergency','other') NOT NULL DEFAULT 'general',
    `target_roles` VARCHAR(255) DEFAULT NULL,
    `target_classes` VARCHAR(255) DEFAULT NULL,
    `attachment` VARCHAR(500) DEFAULT NULL,
    `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    `published_at` DATETIME DEFAULT NULL,
    `expires_at` DATETIME DEFAULT NULL,
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ann_status` (`status`),
    INDEX `idx_ann_published` (`published_at`),
    INDEX `idx_ann_type` (`type`),
    CONSTRAINT `fk_ann_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Messages (Legacy Internal Chat) ──
CREATE TABLE `messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sender_id` BIGINT UNSIGNED NOT NULL,
    `receiver_id` BIGINT UNSIGNED NOT NULL,
    `subject` VARCHAR(255) DEFAULT NULL,
    `body` TEXT NOT NULL,
    `attachment` VARCHAR(500) DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` DATETIME DEFAULT NULL,
    `parent_id` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_msg_sender` (`sender_id`),
    INDEX `idx_msg_receiver` (`receiver_id`, `is_read`),
    INDEX `idx_msg_parent` (`parent_id`),
    CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_msg_parent` FOREIGN KEY (`parent_id`) REFERENCES `messages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Notifications ──
CREATE TABLE `notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT DEFAULT NULL,
    `link` VARCHAR(500) DEFAULT NULL,
    `data` JSON DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_notif_user` (`user_id`, `is_read`),
    INDEX `idx_notif_type` (`type`),
    INDEX `idx_notif_created` (`created_at`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Audit Logs ──
CREATE TABLE `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(50) DEFAULT NULL,
    `entity_id` BIGINT UNSIGNED DEFAULT NULL,
    `old_values` JSON DEFAULT NULL,
    `new_values` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_module` (`module`),
    INDEX `idx_audit_entity` (`entity_type`, `entity_id`),
    INDEX `idx_audit_created` (`created_at`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Settings ──
CREATE TABLE `settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM('string','integer','boolean','json','text','textarea','number') NOT NULL DEFAULT 'string',
    `description` VARCHAR(255) DEFAULT NULL,
    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_settings_key` (`setting_group`, `setting_key`),
    INDEX `idx_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ############################################################
--  SECTION 8: FINANCE MODULE
-- ############################################################

-- ── Fees — Master fee definition ──
CREATE TABLE `fin_fees` (
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

-- ── Varying penalty values ──
CREATE TABLE `fin_varying_penalties` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fee_id` BIGINT UNSIGNED NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `value` DECIMAL(12,4) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_fin_vp_fee` (`fee_id`),
    CONSTRAINT `fk_fin_vp_fee` FOREIGN KEY (`fee_id`) REFERENCES `fin_fees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Fee → Class assignment ──
CREATE TABLE `fin_fee_classes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fee_id` BIGINT UNSIGNED NOT NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_fin_fee_class` (`fee_id`, `class_id`),
    CONSTRAINT `fk_fin_fc_fee` FOREIGN KEY (`fee_id`) REFERENCES `fin_fees`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fin_fc_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Fees — individual fee assignments ──
CREATE TABLE `fin_student_fees` (
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

-- ── Transactions — all payments, adjustments, fee assignments/removals ──
CREATE TABLE `fin_transactions` (
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
    `batch_receipt_no` VARCHAR(100) DEFAULT NULL,
    `reference` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `print_count` INT UNSIGNED NOT NULL DEFAULT 0,
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

-- ── Penalty Log ──
CREATE TABLE `fin_penalty_log` (
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

-- ── Student Groups ──
CREATE TABLE `fin_groups` (
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

-- ── Group Members ──
CREATE TABLE `fin_group_members` (
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

-- ── Supplementary Fees ──
CREATE TABLE `fin_supplementary_fees` (
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

-- ── Supplementary Transactions ──
CREATE TABLE `fin_supplementary_transactions` (
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


-- ############################################################
--  SECTION 9: HR & PAYROLL MODULE
-- ############################################################

-- ── Departments — organizational units ──
CREATE TABLE `hr_departments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) DEFAULT NULL,
    `head_of_department_id` BIGINT UNSIGNED DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    `updated_by` BIGINT UNSIGNED DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_hr_dept_code` (`code`),
    INDEX `idx_hr_dept_status` (`status`),
    INDEX `idx_hr_dept_deleted` (`deleted_at`),
    CONSTRAINT `fk_hr_dept_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_hr_dept_updater` FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Employees — staff records (ID format: EMP-YYYY-XXXX) ──
CREATE TABLE `hr_employees` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` VARCHAR(20) NOT NULL COMMENT 'Format: EMP-YYYY-XXXX',
    `first_name` VARCHAR(100) NOT NULL,
    `father_name` VARCHAR(100) NOT NULL,
    `grandfather_name` VARCHAR(100) NOT NULL,
    `first_name_am` VARCHAR(100) DEFAULT NULL COMMENT 'First name in Amharic',
    `father_name_am` VARCHAR(100) DEFAULT NULL COMMENT 'Father name in Amharic',
    `grandfather_name_am` VARCHAR(100) DEFAULT NULL COMMENT 'Grandfather name in Amharic',
    `gender` ENUM('male','female') NOT NULL,
    `date_of_birth_ec` VARCHAR(20) DEFAULT NULL COMMENT 'Ethiopian calendar DD/MM/YYYY',
    `date_of_birth_gregorian` DATE DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `emergency_contact_name` VARCHAR(200) DEFAULT NULL,
    `emergency_contact_phone` VARCHAR(20) DEFAULT NULL,
    `photo` VARCHAR(500) DEFAULT NULL,
    `department_id` BIGINT UNSIGNED DEFAULT NULL,
    `position` VARCHAR(100) DEFAULT NULL,
    `qualification` VARCHAR(50) DEFAULT NULL,
    `role` ENUM('teacher','admin','accountant','librarian','support_staff') NOT NULL DEFAULT 'support_staff',
    `employment_type` ENUM('permanent','full_time','contract','part_time','temporary') NOT NULL DEFAULT 'permanent',
    `start_date_ec` VARCHAR(20) DEFAULT NULL COMMENT 'Hire date Ethiopian calendar DD/MM/YYYY',
    `start_date_gregorian` DATE DEFAULT NULL,
    `end_date_ec` VARCHAR(20) DEFAULT NULL,
    `end_date_gregorian` DATE DEFAULT NULL,
    `basic_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `transport_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `position_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `other_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `other_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('active','left','suspended') NOT NULL DEFAULT 'active',
    `tin_number` VARCHAR(30) DEFAULT NULL,
    `pension_number` VARCHAR(50) DEFAULT NULL,
    `national_id` VARCHAR(50) DEFAULT NULL,
    `bank_name` VARCHAR(100) DEFAULT NULL,
    `bank_account` VARCHAR(50) DEFAULT NULL,
    `biometric_id` VARCHAR(50) DEFAULT NULL COMMENT 'For biometric device mapping',
    `fingerprint_registered` TINYINT(1) NOT NULL DEFAULT 0,
    `user_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Link to users table if employee has login',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    `updated_by` BIGINT UNSIGNED DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_hr_emp_employee_id` (`employee_id`),
    UNIQUE KEY `uk_hr_emp_email` (`email`),
    UNIQUE KEY `uk_hr_emp_tin` (`tin_number`),
    INDEX `idx_hr_emp_department` (`department_id`),
    INDEX `idx_hr_emp_status` (`status`),
    INDEX `idx_hr_emp_role` (`role`),
    INDEX `idx_hr_emp_deleted` (`deleted_at`),
    INDEX `idx_hr_emp_user` (`user_id`),
    CONSTRAINT `fk_hr_emp_dept` FOREIGN KEY (`department_id`) REFERENCES `hr_departments`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_hr_emp_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_hr_emp_updater` FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_hr_emp_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add FK for department head (circular ref, must be added after hr_employees exists)
ALTER TABLE `hr_departments`
    ADD CONSTRAINT `fk_hr_dept_head` FOREIGN KEY (`head_of_department_id`) REFERENCES `hr_employees`(`id`) ON DELETE SET NULL;

-- ── Employee Documents — contracts, certificates, IDs ──
CREATE TABLE `hr_employee_documents` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` BIGINT UNSIGNED NOT NULL,
    `document_type` ENUM('contract','certificate','identification','other') NOT NULL DEFAULT 'other',
    `document_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT DEFAULT NULL,
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_hr_doc_employee` (`employee_id`),
    CONSTRAINT `fk_hr_doc_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hr_doc_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Leave Types — Annual, Sick, Maternity, etc. ──
CREATE TABLE `hr_leave_types` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL,
    `days_allowed` INT UNSIGNED NOT NULL DEFAULT 0,
    `description` TEXT DEFAULT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_hr_lt_code` (`code`),
    INDEX `idx_hr_lt_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Holidays — Ethiopian public and school holidays ──
CREATE TABLE `hr_holidays` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `date_ec` VARCHAR(20) DEFAULT NULL COMMENT 'Ethiopian calendar DD/MM/YYYY',
    `date_gregorian` DATE NOT NULL,
    `is_recurring` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'true if same date every year',
    `year` INT UNSIGNED DEFAULT NULL COMMENT 'null if recurring',
    `description` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_hr_hol_date` (`date_gregorian`),
    INDEX `idx_hr_hol_recurring` (`is_recurring`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Attendance Devices — biometric device registration ──
CREATE TABLE `hr_attendance_devices` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `device_name` VARCHAR(100) NOT NULL,
    `device_model` ENUM('ZKTeco','DigitalPersona','Suprema','other') NOT NULL DEFAULT 'ZKTeco',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `port` INT UNSIGNED DEFAULT 4370,
    `location` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('active','inactive','maintenance') NOT NULL DEFAULT 'active',
    `last_sync` DATETIME DEFAULT NULL,
    `connection_type` ENUM('api','sdk','database','csv') NOT NULL DEFAULT 'api',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_hr_dev_status` (`status`),
    CONSTRAINT `fk_hr_dev_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Employee Biometric — device-employee mapping ──
CREATE TABLE `hr_employee_biometric` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` BIGINT UNSIGNED NOT NULL,
    `device_user_id` VARCHAR(50) NOT NULL COMMENT 'ID from biometric device',
    `device_id` BIGINT UNSIGNED NOT NULL,
    `fingerprint_data_hash` TEXT DEFAULT NULL COMMENT 'Hash of fingerprint data',
    `registered_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_hr_bio_emp_device` (`employee_id`, `device_id`),
    INDEX `idx_hr_bio_device` (`device_id`),
    CONSTRAINT `fk_hr_bio_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hr_bio_device` FOREIGN KEY (`device_id`) REFERENCES `hr_attendance_devices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── HR Attendance — daily staff attendance ──
CREATE TABLE `hr_attendance` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` BIGINT UNSIGNED NOT NULL,
    `date_ec` VARCHAR(20) DEFAULT NULL COMMENT 'Ethiopian calendar DD/MM/YYYY',
    `date_gregorian` DATE NOT NULL,
    `check_in` TIME DEFAULT NULL,
    `check_out` TIME DEFAULT NULL,
    `status` ENUM('present','absent','late','half_day','leave','holiday') NOT NULL DEFAULT 'present',
    `source` ENUM('manual','biometric','mobile') NOT NULL DEFAULT 'manual',
    `device_id` BIGINT UNSIGNED DEFAULT NULL,
    `sync_timestamp` DATETIME DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `marked_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_hr_att_emp_date` (`employee_id`, `date_gregorian`),
    INDEX `idx_hr_att_date` (`date_gregorian`),
    INDEX `idx_hr_att_status` (`status`),
    CONSTRAINT `fk_hr_att_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hr_att_device` FOREIGN KEY (`device_id`) REFERENCES `hr_attendance_devices`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_hr_att_marker` FOREIGN KEY (`marked_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Attendance Logs — raw biometric device logs ──
CREATE TABLE `hr_attendance_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `device_id` BIGINT UNSIGNED NOT NULL,
    `employee_id` BIGINT UNSIGNED NOT NULL,
    `scan_time` DATETIME NOT NULL,
    `scan_type` VARCHAR(10) DEFAULT NULL COMMENT 'in, out, or unknown',
    `raw_data` TEXT DEFAULT NULL COMMENT 'Original data from device',
    `processed` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_hr_alog_device` (`device_id`),
    INDEX `idx_hr_alog_employee` (`employee_id`),
    INDEX `idx_hr_alog_time` (`scan_time`),
    INDEX `idx_hr_alog_processed` (`processed`),
    CONSTRAINT `fk_hr_alog_device` FOREIGN KEY (`device_id`) REFERENCES `hr_attendance_devices`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hr_alog_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Leave Requests — employee leave applications ──
CREATE TABLE `hr_leave_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` BIGINT UNSIGNED NOT NULL,
    `leave_type_id` BIGINT UNSIGNED NOT NULL,
    `start_date_ec` VARCHAR(20) DEFAULT NULL,
    `start_date_gregorian` DATE NOT NULL,
    `end_date_ec` VARCHAR(20) DEFAULT NULL,
    `end_date_gregorian` DATE NOT NULL,
    `days` INT UNSIGNED NOT NULL DEFAULT 1,
    `reason` TEXT DEFAULT NULL,
    `attachment` VARCHAR(500) DEFAULT NULL,
    `status` ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    `approved_by` BIGINT UNSIGNED DEFAULT NULL,
    `approval_date` DATETIME DEFAULT NULL,
    `rejection_reason` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_hr_lr_employee` (`employee_id`),
    INDEX `idx_hr_lr_type` (`leave_type_id`),
    INDEX `idx_hr_lr_status` (`status`),
    INDEX `idx_hr_lr_dates` (`start_date_gregorian`, `end_date_gregorian`),
    CONSTRAINT `fk_hr_lr_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hr_lr_type` FOREIGN KEY (`leave_type_id`) REFERENCES `hr_leave_types`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hr_lr_approver` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_hr_lr_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payroll Periods — monthly payroll batches ──
CREATE TABLE `hr_payroll_periods` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `month_ec` INT UNSIGNED NOT NULL COMMENT '1-13 Ethiopian month',
    `year_ec` INT UNSIGNED NOT NULL COMMENT 'Ethiopian year',
    `month_name_ec` VARCHAR(30) DEFAULT NULL COMMENT 'Ethiopian month name (e.g. Meskerem)',
    `month_gregorian` INT UNSIGNED NOT NULL COMMENT '1-12 Gregorian month',
    `year_gregorian` INT UNSIGNED NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `start_date_ec` VARCHAR(20) DEFAULT NULL COMMENT 'Ethiopian start date DD/MM/YYYY',
    `end_date_ec` VARCHAR(20) DEFAULT NULL COMMENT 'Ethiopian end date DD/MM/YYYY',
    `status` ENUM('draft','generated','approved','paid') NOT NULL DEFAULT 'draft',
    `generated_by` BIGINT UNSIGNED DEFAULT NULL,
    `approved_by` BIGINT UNSIGNED DEFAULT NULL,
    `generated_at` DATETIME DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_hr_pp_period` (`month_ec`, `year_ec`),
    INDEX `idx_hr_pp_status` (`status`),
    INDEX `idx_hr_pp_dates` (`start_date`, `end_date`),
    CONSTRAINT `fk_hr_pp_generator` FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_hr_pp_approver` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payroll Records — individual employee payroll entries ──
CREATE TABLE `hr_payroll_records` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `payroll_period_id` BIGINT UNSIGNED NOT NULL,
    `employee_id` BIGINT UNSIGNED NOT NULL,
    `working_days` INT UNSIGNED NOT NULL DEFAULT 30 COMMENT 'Days in pay period',
    `days_worked` INT UNSIGNED NOT NULL DEFAULT 30 COMMENT 'Actual days worked (pro-rated)',
    `basic_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `prorated_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Salary after pro-rating',
    `transport_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `other_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `overtime` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Overtime pay',
    `gross_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'prorated_salary + allowances',
    `taxable_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `income_tax` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `employee_pension` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '7% of basic',
    `employer_pension` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '11% of basic',
    `other_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Additional deductions',
    `total_pension` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'employee_pension + employer_pension',
    `total_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'tax + employee pension + other',
    `net_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_status` ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
    `payment_method` ENUM('bank_transfer','cash') NOT NULL DEFAULT 'bank_transfer',
    `bank_reference` VARCHAR(100) DEFAULT NULL COMMENT 'Bank transfer reference number',
    `payment_date` DATE DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_hr_pr_period_emp` (`payroll_period_id`, `employee_id`),
    INDEX `idx_hr_pr_employee` (`employee_id`),
    INDEX `idx_hr_pr_status` (`payment_status`),
    CONSTRAINT `fk_hr_pr_period` FOREIGN KEY (`payroll_period_id`) REFERENCES `hr_payroll_periods`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hr_pr_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hr_pr_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Employee Allowances — recurring allowances per employee ──
CREATE TABLE `hr_employee_allowances` (
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


-- ############################################################
--  SECTION 10: MESSAGING MODULE
-- ############################################################

-- ── Conversations — container for all message threads ──
CREATE TABLE `msg_conversations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` ENUM('solo','bulk','group') NOT NULL DEFAULT 'solo',
    `subject` VARCHAR(255) DEFAULT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `group_id` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_conv_type` (`type`),
    INDEX `idx_conv_creator` (`created_by`),
    INDEX `idx_conv_group` (`group_id`),
    CONSTRAINT `fk_conv_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Conversation Participants ──
CREATE TABLE `msg_conversation_participants` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `left_at` DATETIME DEFAULT NULL,
    `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
    `last_read_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_conv_participant` (`conversation_id`, `user_id`),
    INDEX `idx_cp_user` (`user_id`),
    CONSTRAINT `fk_cp_conv` FOREIGN KEY (`conversation_id`) REFERENCES `msg_conversations`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cp_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Messages — actual message content ──
CREATE TABLE `msg_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `sender_id` BIGINT UNSIGNED NOT NULL,
    `body` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_msg_conv` (`conversation_id`, `created_at`),
    INDEX `idx_msg_sender` (`sender_id`),
    CONSTRAINT `fk_msg2_conv` FOREIGN KEY (`conversation_id`) REFERENCES `msg_conversations`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_msg2_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Message Status — per-recipient delivery tracking ──
CREATE TABLE `msg_message_status` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `message_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('sent','delivered','read') NOT NULL DEFAULT 'sent',
    `delivered_at` DATETIME DEFAULT NULL,
    `read_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_msg_status` (`message_id`, `user_id`),
    INDEX `idx_ms_user` (`user_id`, `status`),
    CONSTRAINT `fk_ms_msg` FOREIGN KEY (`message_id`) REFERENCES `msg_messages`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ms_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Attachments — files attached to messages ──
CREATE TABLE `msg_attachments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `message_id` BIGINT UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
    `mime_type` VARCHAR(100) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_att_msg` (`message_id`),
    CONSTRAINT `fk_att_msg` FOREIGN KEY (`message_id`) REFERENCES `msg_messages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Groups — for group messaging ──
CREATE TABLE `msg_groups` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `class_id` BIGINT UNSIGNED DEFAULT NULL,
    `section_id` BIGINT UNSIGNED DEFAULT NULL,
    `max_members` INT UNSIGNED NOT NULL DEFAULT 30,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_grp_creator` (`created_by`),
    INDEX `idx_grp_class` (`class_id`),
    CONSTRAINT `fk_grp_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_grp_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_grp_section` FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Group Members ──
CREATE TABLE `msg_group_members` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
    `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_grp_member` (`group_id`, `user_id`),
    INDEX `idx_gm_user` (`user_id`),
    CONSTRAINT `fk_gm_group` FOREIGN KEY (`group_id`) REFERENCES `msg_groups`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_gm_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ############################################################
--  FINALIZE
-- ############################################################
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Schema complete — 71 tables across 4 modules:
--   Core:      35 tables (auth, academics, students, exams, etc.)
--   Finance:   10 tables (fees, transactions, penalties, groups)
--   HR:        13 tables (employees, payroll, attendance, leaves)
--   Messaging:  7 tables (conversations, messages, groups)
--
-- Next: run seed.sql to populate initial data.
-- ============================================================
