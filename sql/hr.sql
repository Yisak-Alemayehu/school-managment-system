-- ============================================================
-- HR & Payroll Module — Database Schema
-- Urji Beri School SMS
-- Run AFTER db.sql and seed.sql
-- Generated: 2026-03-06
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- DROP HR TABLES (reverse FK order)
-- ============================================================
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

-- ============================================================
-- Departments — organizational units
-- ============================================================
CREATE TABLE IF NOT EXISTS `hr_departments` (
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

-- ============================================================
-- Employees — staff records
-- Employee ID format: EMP-YYYY-XXXX
-- ============================================================
CREATE TABLE IF NOT EXISTS `hr_employees` (
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
    `biometric_id` VARCHAR(50) DEFAULT NULL COMMENT 'For future biometric device mapping',
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

-- Add FK for department head (circular ref, added after hr_employees exists)
ALTER TABLE `hr_departments`
    ADD CONSTRAINT `fk_hr_dept_head` FOREIGN KEY (`head_of_department_id`) REFERENCES `hr_employees`(`id`) ON DELETE SET NULL;

-- ============================================================
-- Employee Documents — contracts, certificates, IDs
-- ============================================================
CREATE TABLE IF NOT EXISTS `hr_employee_documents` (
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

-- ============================================================
-- Leave Types — Annual, Sick, Maternity, etc.
-- ============================================================
CREATE TABLE IF NOT EXISTS `hr_leave_types` (
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

-- ============================================================
-- Holidays — Ethiopian public and school holidays
-- ============================================================
CREATE TABLE IF NOT EXISTS `hr_holidays` (
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

-- ============================================================
-- Attendance Devices — biometric device registration
-- ============================================================
CREATE TABLE IF NOT EXISTS `hr_attendance_devices` (
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

-- ============================================================
-- Employee Biometric — device-employee mapping
-- ============================================================
CREATE TABLE IF NOT EXISTS `hr_employee_biometric` (
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

-- ============================================================
-- Attendance — daily staff attendance
-- ============================================================
CREATE TABLE IF NOT EXISTS `hr_attendance` (
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

-- ============================================================
-- Attendance Logs — raw biometric device logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `hr_attendance_logs` (
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

-- ============================================================
-- Leave Requests — employee leave applications
-- ============================================================
CREATE TABLE IF NOT EXISTS `hr_leave_requests` (
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

-- ============================================================
-- Payroll Periods — monthly payroll batches
-- ============================================================
CREATE TABLE IF NOT EXISTS `hr_payroll_periods` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `month_ec` INT UNSIGNED NOT NULL COMMENT '1-13 Ethiopian month',
    `year_ec` INT UNSIGNED NOT NULL COMMENT 'Ethiopian year',
    `month_gregorian` INT UNSIGNED NOT NULL COMMENT '1-12 Gregorian month',
    `year_gregorian` INT UNSIGNED NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
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

-- ============================================================
-- Payroll Records — individual employee payroll entries
-- ============================================================
CREATE TABLE IF NOT EXISTS `hr_payroll_records` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `payroll_period_id` BIGINT UNSIGNED NOT NULL,
    `employee_id` BIGINT UNSIGNED NOT NULL,
    `working_days` INT UNSIGNED NOT NULL DEFAULT 30 COMMENT 'Days in pay period',
    `days_worked` INT UNSIGNED NOT NULL DEFAULT 30 COMMENT 'Actual days worked (pro-rated)',
    `basic_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `prorated_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Salary after pro-rating',
    `transport_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `other_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `gross_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'prorated_salary + allowances',
    `taxable_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `income_tax` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `employee_pension` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '7% of basic',
    `employer_pension` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '11% of basic',
    `total_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'tax + employee pension',
    `net_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_status` ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
    `payment_date` DATE DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_hr_pr_period_emp` (`payroll_period_id`, `employee_id`),
    INDEX `idx_hr_pr_employee` (`employee_id`),
    INDEX `idx_hr_pr_status` (`payment_status`),
    CONSTRAINT `fk_hr_pr_period` FOREIGN KEY (`payroll_period_id`) REFERENCES `hr_payroll_periods`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hr_pr_employee` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- HR Permissions
-- ============================================================
INSERT IGNORE INTO `permissions` (`module`, `action`, `description`) VALUES
('hr', 'view',           'View HR module and employee data'),
('hr', 'manage',         'Full HR management access'),
('hr', 'employees',      'Manage employee records'),
('hr', 'departments',    'Manage departments'),
('hr', 'attendance',     'Manage staff attendance'),
('hr', 'leave',          'Manage leave requests'),
('hr', 'payroll',        'Generate and manage payroll'),
('hr', 'payroll_approve','Approve payroll for payment'),
('hr', 'reports',        'View HR reports and analytics'),
('hr', 'devices',        'Manage biometric devices'),
('hr', 'print',          'Print payroll forms and reports');

-- ============================================================
-- Assign HR permissions to Super Admin and School Admin
-- ============================================================
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions` WHERE `module` = 'hr';

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, `id` FROM `permissions` WHERE `module` = 'hr';

-- Accountant gets view, payroll, reports, print
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 6, `id` FROM `permissions` WHERE `module` = 'hr' AND `action` IN ('view', 'payroll', 'reports', 'print');

-- ============================================================
-- Seed: Default Leave Types
-- ============================================================
INSERT IGNORE INTO `hr_leave_types` (`name`, `code`, `days_allowed`, `description`, `status`) VALUES
('Annual Leave',      'ANNUAL',     16, 'Annual paid leave entitlement per Ethiopian labor law', 'active'),
('Sick Leave',        'SICK',        6, 'Paid sick leave with medical certificate',              'active'),
('Maternity Leave',   'MATERNITY', 120, 'Maternity leave (30 prenatal + 90 postnatal)',           'active'),
('Paternity Leave',   'PATERNITY',   5, 'Paternity leave for new fathers',                       'active'),
('Bereavement Leave', 'BEREAVE',     3, 'Leave for death of immediate family member',             'active'),
('Marriage Leave',    'MARRIAGE',    3, 'Leave for employee marriage',                            'active'),
('Unpaid Leave',      'UNPAID',      0, 'Unpaid leave (deducted from salary)',                    'active'),
('Study Leave',       'STUDY',      10, 'Leave for examinations and study',                       'active');

-- ============================================================
-- Seed: Default Departments
-- ============================================================
INSERT IGNORE INTO `hr_departments` (`name`, `code`, `description`, `status`) VALUES
('Administration',    'ADMIN',   'School administration and management',        'active'),
('Teaching Staff',    'TEACH',   'All teaching and instructional staff',         'active'),
('Finance',           'FIN',     'Financial management and accounting',          'active'),
('Library',           'LIB',     'Library services and management',              'active'),
('Support Services',  'SUPPORT', 'Maintenance, security, and support staff',     'active'),
('IT Department',     'IT',      'Information technology and systems',            'active');

-- ============================================================
-- Seed: Ethiopian Public Holidays (recurring)
-- ============================================================
INSERT IGNORE INTO `hr_holidays` (`name`, `date_ec`, `date_gregorian`, `is_recurring`, `description`) VALUES
('Enkutatash (Ethiopian New Year)',  '01/01', '2025-09-11', 1, 'Ethiopian New Year - Meskerem 1'),
('Meskel (Finding of True Cross)',   '17/01', '2025-09-27', 1, 'Finding of the True Cross - Meskerem 17'),
('Ethiopian Christmas (Genna)',      '29/04', '2026-01-07', 1, 'Ethiopian Christmas - Tahsas 29'),
('Ethiopian Epiphany (Timket)',      '11/05', '2026-01-19', 1, 'Timket celebration - Tir 11'),
('Adwa Victory Day',                '23/06', '2026-03-02', 1, 'Battle of Adwa - Yekatit 23'),
('Ethiopian Good Friday',           NULL,    '2026-04-10', 0, 'Moveable feast - varies each year'),
('Ethiopian Easter (Fasika)',        NULL,    '2026-04-12', 0, 'Moveable feast - varies each year'),
('International Labour Day',        '23/08', '2026-05-01', 1, 'Workers Day - Miazia 23'),
('Ethiopian Patriots Day',          '27/08', '2026-05-05', 1, 'Patriots Victory Day - Miazia 27'),
('Downfall of the Derg',            '20/09', '2026-05-28', 1, 'Ginbot 20 - Fall of the Derg regime'),
('Eid al-Fitr',                     NULL,    '2026-03-20', 0, 'End of Ramadan - varies each year'),
('Eid al-Adha',                     NULL,    '2026-05-27', 0, 'Feast of Sacrifice - varies each year'),
('Mawlid (Prophet Birthday)',       NULL,    '2026-06-06', 0, 'Prophet Muhammad birthday - varies each year');
