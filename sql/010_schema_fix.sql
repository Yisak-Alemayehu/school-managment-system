-- ──────────────────────────────────────────────────────────────
-- Schema Fix Migration
-- Run this AFTER the initial migration (001-009) to fix column mismatches
-- ──────────────────────────────────────────────────────────────

-- ── Users table: Add full_name and is_active columns ──────────
ALTER TABLE `users`
    ADD COLUMN `full_name` VARCHAR(200) DEFAULT NULL AFTER `password_hash`,
    ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `address`;

-- Populate full_name from first_name + last_name
UPDATE `users` SET `full_name` = CONCAT(`first_name`, ' ', `last_name`) WHERE `full_name` IS NULL;

-- Make full_name NOT NULL after population
ALTER TABLE `users` MODIFY COLUMN `full_name` VARCHAR(200) NOT NULL;

-- Sync is_active with status
UPDATE `users` SET `is_active` = CASE WHEN `status` = 'active' THEN 1 ELSE 0 END;

-- ── Classes table: Add is_active column ──────────────────────
ALTER TABLE `classes` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `sort_order`;
UPDATE `classes` SET `is_active` = CASE WHEN `status` = 'active' THEN 1 ELSE 0 END;

-- ── Sections table: Add is_active column ─────────────────────
ALTER TABLE `sections` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `capacity`;
UPDATE `sections` SET `is_active` = CASE WHEN `status` = 'active' THEN 1 ELSE 0 END;

-- ── Subjects table: Add is_active column ─────────────────────
ALTER TABLE `subjects` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `type`;
UPDATE `subjects` SET `is_active` = CASE WHEN `status` = 'active' THEN 1 ELSE 0 END;

-- ── Students table: Add full_name generated column ───────────
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'students' AND column_name = 'full_name');
SET @sql = IF(@col_exists = 0, "ALTER TABLE `students` ADD COLUMN `full_name` VARCHAR(200) GENERATED ALWAYS AS (CONCAT(`first_name`, ' ', `last_name`)) STORED AFTER `last_name`", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── Guardians table: Add full_name generated column ──────────
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guardians' AND column_name = 'full_name');
SET @sql = IF(@col_exists = 0, "ALTER TABLE `guardians` ADD COLUMN `full_name` VARCHAR(200) GENERATED ALWAYS AS (CONCAT(`first_name`, ' ', `last_name`)) STORED AFTER `last_name`", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Done!
SELECT 'Schema fix migration complete!' AS result;
