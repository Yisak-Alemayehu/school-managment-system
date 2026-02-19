-- ============================================================
-- Urjiberi School ERP — Seed Data
-- Default roles, permissions, admin user, settings, grades
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Roles ────────────────────────────────────────────────────
INSERT INTO `roles` (`name`, `slug`, `description`, `is_system`) VALUES
('Super Admin', 'super_admin', 'Full system access with all privileges', 1),
('Admin', 'admin', 'School administration and management', 1),
('Teacher', 'teacher', 'Teaching staff with class management', 1),
('Student', 'student', 'Student with view access to own data', 1),
('Parent', 'parent', 'Parent/guardian with view access to children data', 1),
('Accountant', 'accountant', 'Financial management and reporting', 1),
('Librarian', 'librarian', 'Library management (future)', 1);

-- ── Permissions ──────────────────────────────────────────────
INSERT INTO `permissions` (`module`, `action`, `description`) VALUES
-- Dashboard
('dashboard', 'view', 'View dashboard'),
-- Users
('users', 'view', 'View users list'),
('users', 'create', 'Create new user'),
('users', 'update', 'Update user details'),
('users', 'delete', 'Delete user'),
('users', 'export', 'Export users data'),
-- Students
('students', 'view', 'View students list'),
('students', 'create', 'Admit new student'),
('students', 'update', 'Update student details'),
('students', 'delete', 'Delete student'),
('students', 'export', 'Export students data'),
('students', 'promote', 'Promote students'),
-- Academics
('academics', 'view', 'View academic settings'),
('academics', 'create', 'Create academic entities'),
('academics', 'update', 'Update academic entities'),
('academics', 'delete', 'Delete academic entities'),
-- Attendance
('attendance', 'view', 'View attendance records'),
('attendance', 'create', 'Mark attendance'),
('attendance', 'update', 'Update attendance'),
('attendance', 'export', 'Export attendance data'),
-- Assignments
('assignments', 'view', 'View assignments'),
('assignments', 'create', 'Create assignments'),
('assignments', 'update', 'Update assignments'),
('assignments', 'delete', 'Delete assignments'),
('assignments', 'grade', 'Grade submissions'),
-- Exams
('exams', 'view', 'View exams'),
('exams', 'create', 'Create exams'),
('exams', 'update', 'Update exams'),
('exams', 'delete', 'Delete exams'),
-- Marks
('marks', 'view', 'View marks'),
('marks', 'create', 'Enter marks'),
('marks', 'update', 'Update marks'),
('marks', 'export', 'Export marks'),
-- Report Cards
('report_cards', 'view', 'View report cards'),
('report_cards', 'create', 'Generate report cards'),
('report_cards', 'update', 'Update report cards'),
('report_cards', 'export', 'Export/print report cards'),
-- Finance
('finance', 'view', 'View finance records'),
('finance', 'create', 'Create invoices/fees'),
('finance', 'update', 'Update finance records'),
('finance', 'delete', 'Delete finance records'),
('finance', 'export', 'Export finance data'),
('finance', 'payment', 'Record payments'),
-- Communication
('communication', 'view', 'View announcements/messages'),
('communication', 'create', 'Create announcements/messages'),
('communication', 'update', 'Update announcements'),
('communication', 'delete', 'Delete announcements'),
-- Reports
('reports', 'view', 'View reports'),
('reports', 'export', 'Export reports'),
-- Settings
('settings', 'view', 'View settings'),
('settings', 'update', 'Update settings'),
-- Audit
('audit_logs', 'view', 'View audit logs');

-- ── Role → Permissions Mapping ───────────────────────────────
-- Admin gets all permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions`;

-- Teacher permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions` WHERE
    (`module` = 'dashboard' AND `action` = 'view') OR
    (`module` = 'students' AND `action` = 'view') OR
    (`module` = 'academics' AND `action` = 'view') OR
    (`module` = 'attendance' AND `action` IN ('view', 'create', 'update')) OR
    (`module` = 'assignments' AND `action` IN ('view', 'create', 'update', 'delete', 'grade')) OR
    (`module` = 'exams' AND `action` = 'view') OR
    (`module` = 'marks' AND `action` IN ('view', 'create', 'update')) OR
    (`module` = 'report_cards' AND `action` IN ('view', 'create', 'update')) OR
    (`module` = 'communication' AND `action` IN ('view', 'create'));

-- Student permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM `permissions` WHERE
    (`module` = 'dashboard' AND `action` = 'view') OR
    (`module` = 'attendance' AND `action` = 'view') OR
    (`module` = 'assignments' AND `action` = 'view') OR
    (`module` = 'exams' AND `action` = 'view') OR
    (`module` = 'marks' AND `action` = 'view') OR
    (`module` = 'report_cards' AND `action` = 'view') OR
    (`module` = 'finance' AND `action` = 'view') OR
    (`module` = 'communication' AND `action` = 'view');

-- Parent permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 5, id FROM `permissions` WHERE
    (`module` = 'dashboard' AND `action` = 'view') OR
    (`module` = 'students' AND `action` = 'view') OR
    (`module` = 'attendance' AND `action` = 'view') OR
    (`module` = 'marks' AND `action` = 'view') OR
    (`module` = 'report_cards' AND `action` = 'view') OR
    (`module` = 'finance' AND `action` = 'view') OR
    (`module` = 'communication' AND `action` IN ('view', 'create'));

-- Accountant permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 6, id FROM `permissions` WHERE
    (`module` = 'dashboard' AND `action` = 'view') OR
    (`module` = 'students' AND `action` = 'view') OR
    (`module` = 'finance' AND `action` IN ('view', 'create', 'update', 'delete', 'export', 'payment')) OR
    (`module` = 'reports' AND `action` IN ('view', 'export'));

-- Librarian permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 7, id FROM `permissions` WHERE
    (`module` = 'dashboard' AND `action` = 'view') OR
    (`module` = 'students' AND `action` = 'view') OR
    (`module` = 'communication' AND `action` = 'view');

-- ── Default Super Admin User ─────────────────────────────────
-- Password: Admin@123 (bcrypt hash)
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `first_name`, `last_name`, `phone`, `gender`, `is_active`, `status`, `email_verified_at`)
VALUES ('superadmin', 'admin@urjiberi.edu.et', '$2y$12$LJ3m4ys3Gql.ZhEc4hDqJOmGrzMFBpOhTBbpMSLj6Y5dXVKyMC5uG', 'System Administrator', 'System', 'Administrator', '+251900000000', 'male', 1, 'active', NOW());

INSERT INTO `user_roles` (`user_id`, `role_id`)
VALUES (1, 1);

-- ── Default Academic Session ─────────────────────────────────
INSERT INTO `academic_sessions` (`name`, `slug`, `start_date`, `end_date`, `is_active`)
VALUES ('2025/2026', '2025-2026', '2025-09-01', '2026-07-15', 1);

INSERT INTO `terms` (`session_id`, `name`, `slug`, `start_date`, `end_date`, `is_active`, `sort_order`) VALUES
(1, 'First Semester', 'first-semester', '2025-09-01', '2026-01-31', 1, 1),
(1, 'Second Semester', 'second-semester', '2026-02-01', '2026-07-15', 0, 2);

-- ── Default Classes ──────────────────────────────────────────
INSERT INTO `classes` (`name`, `slug`, `numeric_name`, `sort_order`) VALUES
('Grade 1', 'grade-1', 1, 1),
('Grade 2', 'grade-2', 2, 2),
('Grade 3', 'grade-3', 3, 3),
('Grade 4', 'grade-4', 4, 4),
('Grade 5', 'grade-5', 5, 5),
('Grade 6', 'grade-6', 6, 6),
('Grade 7', 'grade-7', 7, 7),
('Grade 8', 'grade-8', 8, 8),
('Grade 9', 'grade-9', 9, 9),
('Grade 10', 'grade-10', 10, 10),
('Grade 11', 'grade-11', 11, 11),
('Grade 12', 'grade-12', 12, 12);

-- ── Default Sections ─────────────────────────────────────────
INSERT INTO `sections` (`class_id`, `name`, `capacity`) VALUES
(1, 'A', 40), (1, 'B', 40),
(2, 'A', 40), (2, 'B', 40),
(3, 'A', 40), (3, 'B', 40),
(4, 'A', 40), (4, 'B', 40),
(5, 'A', 40), (5, 'B', 40),
(6, 'A', 40), (6, 'B', 40),
(7, 'A', 40), (7, 'B', 40),
(8, 'A', 40), (8, 'B', 40),
(9, 'A', 45), (9, 'B', 45),
(10, 'A', 45), (10, 'B', 45),
(11, 'A', 45), (11, 'B', 45),
(12, 'A', 45), (12, 'B', 45);

-- ── Default Subjects ─────────────────────────────────────────
INSERT INTO `subjects` (`name`, `code`, `type`) VALUES
('English', 'ENG', 'theory'),
('Amharic', 'AMH', 'theory'),
('Mathematics', 'MATH', 'theory'),
('Physics', 'PHY', 'both'),
('Chemistry', 'CHEM', 'both'),
('Biology', 'BIO', 'both'),
('History', 'HIST', 'theory'),
('Geography', 'GEO', 'theory'),
('Civics', 'CIV', 'theory'),
('ICT', 'ICT', 'both'),
('Physical Education', 'PE', 'practical'),
('Art', 'ART', 'practical');

-- ── Default Grade Scale ──────────────────────────────────────
INSERT INTO `grade_scales` (`name`, `is_default`) VALUES ('Ethiopian Standard', 1);

INSERT INTO `grade_scale_entries` (`grade_scale_id`, `grade`, `min_percentage`, `max_percentage`, `grade_point`, `remark`) VALUES
(1, 'A+', 95.00, 100.00, 4.00, 'Excellent'),
(1, 'A',  90.00,  94.99, 4.00, 'Excellent'),
(1, 'A-', 85.00,  89.99, 3.75, 'Very Good'),
(1, 'B+', 80.00,  84.99, 3.50, 'Very Good'),
(1, 'B',  75.00,  79.99, 3.00, 'Good'),
(1, 'B-', 70.00,  74.99, 2.75, 'Good'),
(1, 'C+', 65.00,  69.99, 2.50, 'Satisfactory'),
(1, 'C',  60.00,  64.99, 2.00, 'Satisfactory'),
(1, 'C-', 50.00,  59.99, 1.75, 'Pass'),
(1, 'D',  40.00,  49.99, 1.00, 'Poor'),
(1, 'F',   0.00,  39.99, 0.00, 'Fail');

-- ── Fee Categories ───────────────────────────────────────────
INSERT INTO `fee_categories` (`name`, `code`, `description`, `type`) VALUES
('Tuition Fee', 'TUITION', 'Regular tuition fee', 'tuition'),
('Registration Fee', 'REG', 'Annual registration fee', 'registration'),
('Lab Fee', 'LAB', 'Laboratory usage fee', 'lab'),
('Library Fee', 'LIB', 'Library access fee', 'library'),
('Transport Fee', 'TRANSPORT', 'School bus transport', 'transport'),
('Exam Fee', 'EXAM', 'Examination fees', 'exam'),
('Uniform Fee', 'UNIFORM', 'School uniform', 'uniform');

-- ── Payment Gateways ────────────────────────────────────────
INSERT INTO `payment_gateways` (`name`, `slug`, `display_name`, `description`, `is_active`, `environment`, `sort_order`) VALUES
('Telebirr', 'telebirr', 'Telebirr Mobile Money', 'Pay with Telebirr mobile money', 1, 'sandbox', 1),
('Chapa', 'chapa', 'Chapa Payment', 'Pay with Chapa (cards, mobile, bank)', 0, 'sandbox', 2),
('Stripe', 'stripe', 'Stripe', 'International card payments via Stripe', 0, 'sandbox', 3);

-- ── Default Settings ─────────────────────────────────────────
INSERT INTO `settings` (`setting_group`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_public`) VALUES
-- School info
('school', 'name', 'Urjiberi School', 'string', 'School name', 1),
('school', 'tagline', 'Excellence in Education', 'string', 'School tagline/motto', 1),
('school', 'email', 'info@urjiberi.edu.et', 'string', 'School email', 1),
('school', 'phone', '+251111000000', 'string', 'School phone', 1),
('school', 'address', 'Addis Ababa, Ethiopia', 'string', 'School address', 1),
('school', 'city', 'Addis Ababa', 'string', 'City', 1),
('school', 'region', 'Addis Ababa', 'string', 'Region/State', 1),
('school', 'country', 'Ethiopia', 'string', 'Country', 1),
('school', 'logo', '', 'string', 'School logo path', 1),
('school', 'favicon', '', 'string', 'Favicon path', 1),
-- System
('system', 'timezone', 'Africa/Addis_Ababa', 'string', 'Default timezone', 0),
('system', 'date_format', 'd M Y', 'string', 'Date display format', 0),
('system', 'currency', 'ETB', 'string', 'Currency code', 0),
('system', 'currency_symbol', 'Br', 'string', 'Currency symbol', 0),
('system', 'language', 'en', 'string', 'Default language', 0),
('system', 'pagination_limit', '20', 'integer', 'Items per page', 0),
('system', 'maintenance_mode', '0', 'boolean', 'Maintenance mode', 0),
-- Attendance
('attendance', 'default_mode', 'daily', 'string', 'daily or subject', 0),
('attendance', 'allow_past_edit', '1', 'boolean', 'Allow editing past attendance', 0),
-- Finance
('finance', 'invoice_prefix', 'INV-', 'string', 'Invoice number prefix', 0),
('finance', 'receipt_prefix', 'RCP-', 'string', 'Receipt number prefix', 0),
('finance', 'allow_partial_payment', '1', 'boolean', 'Allow partial invoice payment', 0),
('finance', 'auto_overdue_days', '30', 'integer', 'Days after which invoice is overdue', 0),
-- Email
('email', 'smtp_host', '', 'string', 'SMTP server host', 0),
('email', 'smtp_port', '587', 'integer', 'SMTP server port', 0),
('email', 'smtp_user', '', 'string', 'SMTP username', 0),
('email', 'smtp_pass', '', 'string', 'SMTP password', 0),
('email', 'from_email', 'noreply@urjiberi.edu.et', 'string', 'From email address', 0),
('email', 'from_name', 'Urjiberi School', 'string', 'From name', 0),
-- SMS
('sms', 'provider', '', 'string', 'SMS provider name', 0),
('sms', 'api_key', '', 'string', 'SMS API key', 0),
('sms', 'sender_id', 'URJIBERI', 'string', 'SMS sender ID', 0);

SET FOREIGN_KEY_CHECKS = 1;
