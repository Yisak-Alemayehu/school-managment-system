-- ============================================================
-- Migration: Session-Based Architecture, Guardian Management,
--            Student Promotion & Term Management Enhancements
-- Run after db.sql + seed.sql
-- Generated: 2026-03-09
-- ============================================================

SET NAMES utf8mb4;

-- ── Students table: add sub_city, woreda, house_number, country if missing ──
SET @col = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'students' AND column_name = 'country');
SET @sql = IF(@col = 0, "ALTER TABLE `students` ADD COLUMN `country` VARCHAR(100) DEFAULT 'Ethiopian' AFTER `region`", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'students' AND column_name = 'sub_city');
SET @sql = IF(@col = 0, "ALTER TABLE `students` ADD COLUMN `sub_city` VARCHAR(100) DEFAULT NULL AFTER `city`", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'students' AND column_name = 'woreda');
SET @sql = IF(@col = 0, "ALTER TABLE `students` ADD COLUMN `woreda` VARCHAR(100) DEFAULT NULL AFTER `sub_city`", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'students' AND column_name = 'house_number');
SET @sql = IF(@col = 0, "ALTER TABLE `students` ADD COLUMN `house_number` VARCHAR(50) DEFAULT NULL AFTER `woreda`", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── Guardians: add name index for search ──
SET @idx = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'guardians' AND index_name = 'idx_guardians_name');
SET @sql = IF(@idx = 0, "ALTER TABLE `guardians` ADD INDEX `idx_guardians_name` (`first_name`, `last_name`)", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── Enrollments: add index for session-based queries ──
SET @idx = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'enrollments' AND index_name = 'idx_enrollments_student_status');
SET @sql = IF(@idx = 0, "ALTER TABLE `enrollments` ADD INDEX `idx_enrollments_student_status` (`student_id`, `status`)", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── student_guardians: add relationship column if missing ──
SET @col = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'student_guardians' AND column_name = 'relationship');
SET @sql = IF(@col = 0, "ALTER TABLE `student_guardians` ADD COLUMN `relationship` VARCHAR(50) DEFAULT NULL AFTER `is_primary`", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Migration complete: session/guardian/promotion enhancements applied.' AS result;
