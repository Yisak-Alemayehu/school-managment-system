-- ============================================================
-- Urjiberi School ERP — Migration 004: Teaching & Assessment
-- Attendance, Assignments, Exams, Marks, Grades, Report Cards
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Attendance ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `attendance` (
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

-- ── Assignments ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `assignments` (
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

-- ── Assignment Submissions ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `assignment_submissions` (
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

-- ── Exams ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `exams` (
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

-- ── Exam Schedules ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `exam_schedules` (
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

-- ── Marks ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `marks` (
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

-- ── Grade Scales ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `grade_scales` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `grade_scale_entries` (
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

-- ── Report Cards ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `report_cards` (
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

SET FOREIGN_KEY_CHECKS = 1;
