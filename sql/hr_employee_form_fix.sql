-- ============================================================
-- HR Module — Employee Form Fix Migration
-- Adds missing columns to hr_employees and fixes ENUM values
-- to align with the employee form view.
-- Run AFTER hr.sql and hr_phase2.sql
-- ============================================================

SET NAMES utf8mb4;

-- ============================================================
-- 1. Add Amharic name columns (skip if already exist)
-- ============================================================
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'hr_employees' AND COLUMN_NAME = 'first_name_am' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `hr_employees` ADD COLUMN `first_name_am` VARCHAR(100) DEFAULT NULL COMMENT ''First name in Amharic'' AFTER `grandfather_name`, ADD COLUMN `father_name_am` VARCHAR(100) DEFAULT NULL COMMENT ''Father name in Amharic'' AFTER `first_name_am`, ADD COLUMN `grandfather_name_am` VARCHAR(100) DEFAULT NULL COMMENT ''Grandfather name in Amharic'' AFTER `father_name_am`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 2. Add emergency contact columns
-- ============================================================
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'hr_employees' AND COLUMN_NAME = 'emergency_contact_name' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `hr_employees` ADD COLUMN `emergency_contact_name` VARCHAR(200) DEFAULT NULL AFTER `address`, ADD COLUMN `emergency_contact_phone` VARCHAR(20) DEFAULT NULL AFTER `emergency_contact_name`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 3. Add qualification column
-- ============================================================
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'hr_employees' AND COLUMN_NAME = 'qualification' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `hr_employees` ADD COLUMN `qualification` VARCHAR(50) DEFAULT NULL AFTER `position`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 4. Add pension_number column
-- ============================================================
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'hr_employees' AND COLUMN_NAME = 'pension_number' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `hr_employees` ADD COLUMN `pension_number` VARCHAR(50) DEFAULT NULL AFTER `tin_number`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 5. Add position_allowance column
-- ============================================================
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'hr_employees' AND COLUMN_NAME = 'position_allowance' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `hr_employees` ADD COLUMN `position_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `transport_allowance`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 6. Add other_deductions column
-- ============================================================
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'hr_employees' AND COLUMN_NAME = 'other_deductions' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `hr_employees` ADD COLUMN `other_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `other_allowance`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 7. Fix employment_type ENUM to include permanent & temporary
-- ============================================================
ALTER TABLE `hr_employees`
    MODIFY COLUMN `employment_type` ENUM('permanent','full_time','contract','part_time','temporary') NOT NULL DEFAULT 'permanent';
