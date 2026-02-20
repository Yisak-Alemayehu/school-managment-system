-- ============================================================
-- Urjiberi School ERP — COMPLETE SEED DATA
-- Session: 2025/2026  |  4 Terms  |  12 Grades × 2 Sections
-- 5 students per section (120 total)  |  10 subjects per class
-- 5 assessments per subject per term (Terms 1 & 2 filled)
-- Attendance filled: 2025-09-01 → 2026-02-20 (weekdays)
-- Run AFTER schema.sql
-- Generated: 2026-02-20
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. ROLES
-- ============================================================
INSERT INTO `roles` (`name`, `slug`, `description`, `is_system`) VALUES
('Super Admin', 'super_admin', 'Full system access with all privileges',            1),
('Admin',       'admin',       'School administration and management',               1),
('Teacher',     'teacher',     'Teaching staff with class and mark management',      1),
('Student',     'student',     'Student with view access to own data',               1),
('Parent',      'parent',      'Parent/guardian with view access to children data',  1),
('Accountant',  'accountant',  'Financial management and reporting',                 1),
('Librarian',   'librarian',   'Library management',                                 1);

-- ============================================================
-- 2. PERMISSIONS
-- ============================================================
INSERT INTO `permissions` (`module`, `action`, `description`) VALUES
-- Dashboard
('dashboard',      'view',    'View dashboard'),
-- Users
('users',          'view',    'View users list'),
('users',          'create',  'Create new user'),
('users',          'update',  'Update user details'),
('users',          'delete',  'Delete user'),
('users',          'export',  'Export users data'),
-- Students
('students',       'view',    'View students list'),
('students',       'create',  'Admit new student'),
('students',       'update',  'Update student details'),
('students',       'delete',  'Delete student'),
('students',       'export',  'Export students data'),
('students',       'promote', 'Promote students'),
-- Academics
('academics',      'view',    'View academic settings'),
('academics',      'create',  'Create academic entities'),
('academics',      'update',  'Update academic entities'),
('academics',      'delete',  'Delete academic entities'),
-- Attendance
('attendance',     'view',    'View attendance records'),
('attendance',     'create',  'Mark attendance'),
('attendance',     'update',  'Update attendance'),
('attendance',     'export',  'Export attendance data'),
-- Assignments
('assignments',    'view',    'View assignments'),
('assignments',    'create',  'Create assignments'),
('assignments',    'update',  'Update assignments'),
('assignments',    'delete',  'Delete assignments'),
('assignments',    'grade',   'Grade submissions'),
-- Exams
('exams',          'view',    'View exams'),
('exams',          'create',  'Create exams'),
('exams',          'update',  'Update exams'),
('exams',          'delete',  'Delete exams'),
-- Marks
('marks',          'view',    'View marks'),
('marks',          'create',  'Enter marks'),
('marks',          'update',  'Update marks'),
('marks',          'export',  'Export marks'),
('marks',          'manage',  'Manage marks, assessments and conduct'),
-- Report Cards
('report_cards',   'view',    'View report cards'),
('report_cards',   'create',  'Generate report cards'),
('report_cards',   'update',  'Update report cards'),
('report_cards',   'export',  'Export/print report cards'),
-- Finance
('finance',        'view',    'View finance records'),
('finance',        'create',  'Create invoices and fees'),
('finance',        'update',  'Update finance records'),
('finance',        'delete',  'Delete finance records'),
('finance',        'export',  'Export finance data'),
('finance',        'payment', 'Record payments'),
-- Communication
('communication',  'view',    'View announcements and messages'),
('communication',  'create',  'Create announcements and messages'),
('communication',  'update',  'Update announcements'),
('communication',  'delete',  'Delete announcements'),
-- Reports
('reports',        'view',    'View reports'),
('reports',        'export',  'Export reports'),
-- Settings
('settings',       'view',    'View settings'),
('settings',       'update',  'Update settings'),
-- Audit
('audit_logs',     'view',    'View audit logs');

-- ============================================================
-- 3. ROLE → PERMISSION ASSIGNMENTS
-- ============================================================
-- Super Admin: all permissions (role_id = 1, assigned via user_roles to user 1)
-- Admin gets ALL permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions`;

-- Teacher permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions` WHERE
    (`module` = 'dashboard'     AND `action` = 'view') OR
    (`module` = 'students'      AND `action` = 'view') OR
    (`module` = 'academics'     AND `action` = 'view') OR
    (`module` = 'attendance'    AND `action` IN ('view','create','update')) OR
    (`module` = 'assignments'   AND `action` IN ('view','create','update','delete','grade')) OR
    (`module` = 'exams'         AND `action` = 'view') OR
    (`module` = 'marks'         AND `action` IN ('view','create','update','manage')) OR
    (`module` = 'report_cards'  AND `action` IN ('view','create','update','export')) OR
    (`module` = 'communication' AND `action` IN ('view','create'));

-- Student permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM `permissions` WHERE
    (`module` = 'dashboard'    AND `action` = 'view') OR
    (`module` = 'attendance'   AND `action` = 'view') OR
    (`module` = 'assignments'  AND `action` = 'view') OR
    (`module` = 'exams'        AND `action` = 'view') OR
    (`module` = 'marks'        AND `action` = 'view') OR
    (`module` = 'report_cards' AND `action` = 'view') OR
    (`module` = 'finance'      AND `action` = 'view') OR
    (`module` = 'communication' AND `action` = 'view');

-- Parent permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 5, id FROM `permissions` WHERE
    (`module` = 'dashboard'     AND `action` = 'view') OR
    (`module` = 'students'      AND `action` = 'view') OR
    (`module` = 'attendance'    AND `action` = 'view') OR
    (`module` = 'marks'         AND `action` = 'view') OR
    (`module` = 'report_cards'  AND `action` = 'view') OR
    (`module` = 'finance'       AND `action` = 'view') OR
    (`module` = 'communication' AND `action` IN ('view','create'));

-- Accountant permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 6, id FROM `permissions` WHERE
    (`module` = 'dashboard' AND `action` = 'view') OR
    (`module` = 'students'  AND `action` = 'view') OR
    (`module` = 'finance'   AND `action` IN ('view','create','update','delete','export','payment')) OR
    (`module` = 'reports'   AND `action` IN ('view','export'));

-- Librarian permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 7, id FROM `permissions` WHERE
    (`module` = 'dashboard'     AND `action` = 'view') OR
    (`module` = 'students'      AND `action` = 'view') OR
    (`module` = 'communication' AND `action` = 'view');

-- ============================================================
-- 4. MEDIUMS / STREAMS / SHIFTS
-- ============================================================
INSERT INTO `mediums` (`name`, `sort_order`) VALUES ('English', 1), ('Amharic', 2);
INSERT INTO `streams` (`name`, `sort_order`) VALUES ('Natural Science', 1), ('Social Science', 2);
INSERT INTO `shifts`  (`name`, `start_time`, `end_time`, `sort_order`) VALUES
    ('Morning',   '08:00:00', '12:30:00', 1),
    ('Afternoon', '13:00:00', '17:30:00', 2);

-- ============================================================
-- 5. USERS
-- ============================================================
-- Password for all users: Admin@123 (same bcrypt hash)
-- superadmin: Admin@123
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `first_name`, `last_name`,
    `phone`, `gender`, `is_active`, `status`, `email_verified_at`) VALUES
('superadmin',   'admin@urjiberi.edu.et',     '$2y$12$LJ3m4ys3Gql.ZhEc4hDqJOmGrzMFBpOhTBbpMSLj6Y5dXVKyMC5uG',
    'System Administrator', 'System',  'Administrator', '+251900000000', 'male',   1, 'active', NOW()),
('t.abebe',      'abebe.kebede@urjiberi.edu.et',   '$2y$12$LJ3m4ys3Gql.ZhEc4hDqJOmGrzMFBpOhTBbpMSLj6Y5dXVKyMC5uG',
    'Abebe Kebede',   'Abebe',   'Kebede',   '+251911100001', 'male',   1, 'active', NOW()),
('t.bekele',     'bekele.haile@urjiberi.edu.et',    '$2y$12$LJ3m4ys3Gql.ZhEc4hDqJOmGrzMFBpOhTBbpMSLj6Y5dXVKyMC5uG',
    'Bekele Haile',   'Bekele',  'Haile',    '+251911100002', 'male',   1, 'active', NOW()),
('t.chaltu',     'chaltu.tesfaye@urjiberi.edu.et',  '$2y$12$LJ3m4ys3Gql.ZhEc4hDqJOmGrzMFBpOhTBbpMSLj6Y5dXVKyMC5uG',
    'Chaltu Tesfaye', 'Chaltu',  'Tesfaye',  '+251911100003', 'female', 1, 'active', NOW()),
('t.dinke',      'dinke.girma@urjiberi.edu.et',     '$2y$12$LJ3m4ys3Gql.ZhEc4hDqJOmGrzMFBpOhTBbpMSLj6Y5dXVKyMC5uG',
    'Dinke Girma',    'Dinke',   'Girma',    '+251911100004', 'female', 1, 'active', NOW()),
('t.etsub',      'etsub.alemu@urjiberi.edu.et',     '$2y$12$LJ3m4ys3Gql.ZhEc4hDqJOmGrzMFBpOhTBbpMSLj6Y5dXVKyMC5uG',
    'Etsub Alemu',    'Etsub',   'Alemu',    '+251911100005', 'female', 1, 'active', NOW());

-- User Roles
-- user_id 1 = super_admin, users 2-6 = teacher
INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(1, 1),  -- superadmin → Super Admin
(2, 3),  -- Abebe  → Teacher
(3, 3),  -- Bekele → Teacher
(4, 3),  -- Chaltu → Teacher
(5, 3),  -- Dinke  → Teacher
(6, 3);  -- Etsub  → Teacher

-- ============================================================
-- 6. ACADEMIC SESSION & 4 TERMS
-- ============================================================
INSERT INTO `academic_sessions` (`name`, `slug`, `start_date`, `end_date`, `is_active`) VALUES
('2025/2026', '2025-2026', '2025-09-01', '2026-07-31', 1);

-- sort_order drives "previous terms" logic in report cards
INSERT INTO `terms` (`session_id`, `name`, `slug`, `start_date`, `end_date`, `is_active`, `sort_order`) VALUES
(1, 'Term 1', 'term-1', '2025-09-01', '2025-11-30', 0, 1),
(1, 'Term 2', 'term-2', '2025-12-01', '2026-02-28', 1, 2),  -- ACTIVE (current: Feb 2026)
(1, 'Term 3', 'term-3', '2026-03-01', '2026-05-31', 0, 3),
(1, 'Term 4', 'term-4', '2026-06-01', '2026-07-31', 0, 4);

-- ============================================================
-- 7. CLASSES (Grade 1–12) + SECTIONS (A & B each)
-- ============================================================
INSERT INTO `classes` (`name`, `slug`, `numeric_name`, `sort_order`, `is_active`, `status`) VALUES
('Grade 1',  'grade-1',  1,  1,  1, 'active'),
('Grade 2',  'grade-2',  2,  2,  1, 'active'),
('Grade 3',  'grade-3',  3,  3,  1, 'active'),
('Grade 4',  'grade-4',  4,  4,  1, 'active'),
('Grade 5',  'grade-5',  5,  5,  1, 'active'),
('Grade 6',  'grade-6',  6,  6,  1, 'active'),
('Grade 7',  'grade-7',  7,  7,  1, 'active'),
('Grade 8',  'grade-8',  8,  8,  1, 'active'),
('Grade 9',  'grade-9',  9,  9,  1, 'active'),
('Grade 10', 'grade-10', 10, 10, 1, 'active'),
('Grade 11', 'grade-11', 11, 11, 1, 'active'),
('Grade 12', 'grade-12', 12, 12, 1, 'active');

-- Sections: class_id 1 → sec IDs 1(A),2(B); class 2 → 3(A),4(B); ... class c → (2c-1)(A),(2c)(B)
INSERT INTO `sections` (`class_id`, `name`, `capacity`, `is_active`, `status`) VALUES
(1,'A',40,1,'active'),(1,'B',40,1,'active'),
(2,'A',40,1,'active'),(2,'B',40,1,'active'),
(3,'A',40,1,'active'),(3,'B',40,1,'active'),
(4,'A',40,1,'active'),(4,'B',40,1,'active'),
(5,'A',40,1,'active'),(5,'B',40,1,'active'),
(6,'A',40,1,'active'),(6,'B',40,1,'active'),
(7,'A',40,1,'active'),(7,'B',40,1,'active'),
(8,'A',40,1,'active'),(8,'B',40,1,'active'),
(9,'A',45,1,'active'),(9,'B',45,1,'active'),
(10,'A',45,1,'active'),(10,'B',45,1,'active'),
(11,'A',45,1,'active'),(11,'B',45,1,'active'),
(12,'A',45,1,'active'),(12,'B',45,1,'active');

-- ============================================================
-- 8. SUBJECTS (10 subjects — all classes)
-- ============================================================
-- IDs will be 1-10 in this order
INSERT INTO `subjects` (`name`, `code`, `type`, `is_active`, `status`) VALUES
('English',     'ENG',  'theory',    1, 'active'),
('Amharic',     'AMH',  'theory',    1, 'active'),
('Mathematics', 'MATH', 'theory',    1, 'active'),
('Physics',     'PHY',  'both',      1, 'active'),
('Chemistry',   'CHEM', 'both',      1, 'active'),
('Biology',     'BIO',  'both',      1, 'active'),
('History',     'HIST', 'theory',    1, 'active'),
('Geography',   'GEO',  'theory',    1, 'active'),
('Civics',      'CIV',  'theory',    1, 'active'),
('ICT',         'ICT',  'both',      1, 'active');

-- ============================================================
-- 9. GRADE SCALE (Ethiopian Standard)
-- ============================================================
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

-- ============================================================
-- 10. CLASS → SUBJECTS (all 12 classes × 10 subjects, session 1)
-- ============================================================
INSERT INTO `class_subjects` (`class_id`, `subject_id`, `session_id`, `is_elective`)
SELECT c.id, s.id, 1, 0
FROM `classes` c, `subjects` s
WHERE c.id BETWEEN 1 AND 12 AND s.id BETWEEN 1 AND 10;

-- ============================================================
-- 11. CLASS TEACHERS
-- Teacher assignments:
--   subject 1 (English)     → teacher_id 2 (Abebe)
--   subject 2 (Amharic)     → teacher_id 2 (Abebe)
--   subject 3 (Mathematics) → teacher_id 3 (Bekele)
--   subject 4 (Physics)     → teacher_id 4 (Chaltu)
--   subject 5 (Chemistry)   → teacher_id 4 (Chaltu)
--   subject 6 (Biology)     → teacher_id 4 (Chaltu)
--   subject 7 (History)     → teacher_id 5 (Dinke)
--   subject 8 (Geography)   → teacher_id 5 (Dinke)
--   subject 9 (Civics)      → teacher_id 5 (Dinke)
--   subject 10 (ICT)        → teacher_id 6 (Etsub)
-- ============================================================
INSERT INTO `class_teachers` (`class_id`, `subject_id`, `teacher_id`, `session_id`, `is_class_teacher`)
SELECT
    c.id,
    s.id,
    CASE s.id
        WHEN 1  THEN 2  WHEN 2  THEN 2
        WHEN 3  THEN 3
        WHEN 4  THEN 4  WHEN 5  THEN 4  WHEN 6  THEN 4
        WHEN 7  THEN 5  WHEN 8  THEN 5  WHEN 9  THEN 5
        WHEN 10 THEN 6
    END AS teacher_id,
    1,
    0
FROM `classes` c, `subjects` s
WHERE c.id BETWEEN 1 AND 12 AND s.id BETWEEN 1 AND 10;

-- ============================================================
-- 12. FEE CATEGORIES
-- ============================================================
INSERT INTO `fee_categories` (`name`, `code`, `description`, `type`) VALUES
('Tuition Fee',      'TUITION',   'Regular tuition fee per term',   'tuition'),
('Registration Fee', 'REG',       'Annual registration fee',         'registration'),
('Lab Fee',          'LAB',       'Laboratory usage fee',            'lab'),
('Library Fee',      'LIB',       'Library access fee',              'library'),
('Transport Fee',    'TRANSPORT', 'School bus transport',            'transport'),
('Exam Fee',         'EXAM',      'Examination fees per term',       'exam'),
('Uniform Fee',      'UNIFORM',   'School uniform',                  'uniform');

-- ============================================================
-- 13. FEE STRUCTURES (Tuition + Exam per term, per class group)
-- Grade 1-4:  Tuition 1000, Exam 100
-- Grade 5-8:  Tuition 1200, Exam 120
-- Grade 9-12: Tuition 1500, Exam 150
-- Registration (fee_category_id=2): one-time, term_id NULL
-- ============================================================
INSERT INTO `fee_structures` (`session_id`, `class_id`, `fee_category_id`, `term_id`, `amount`, `due_date`, `is_mandatory`)
SELECT
    1,
    c.id,
    1,  -- Tuition
    t.id,
    CASE WHEN c.id <= 4 THEN 1000.00 WHEN c.id <= 8 THEN 1200.00 ELSE 1500.00 END,
    DATE_ADD(t.start_date, INTERVAL 14 DAY),
    1
FROM `classes` c, `terms` t
WHERE c.id BETWEEN 1 AND 12 AND t.session_id = 1;

INSERT INTO `fee_structures` (`session_id`, `class_id`, `fee_category_id`, `term_id`, `amount`, `due_date`, `is_mandatory`)
SELECT
    1,
    c.id,
    6,  -- Exam Fee
    t.id,
    CASE WHEN c.id <= 4 THEN 100.00 WHEN c.id <= 8 THEN 120.00 ELSE 150.00 END,
    DATE_ADD(t.start_date, INTERVAL 21 DAY),
    1
FROM `classes` c, `terms` t
WHERE c.id BETWEEN 1 AND 12 AND t.session_id = 1;

-- Registration fee (one-time, session-level, no term)
INSERT INTO `fee_structures` (`session_id`, `class_id`, `fee_category_id`, `term_id`, `amount`, `due_date`, `is_mandatory`)
SELECT
    1,
    c.id,
    2,  -- Registration
    NULL,
    CASE WHEN c.id <= 4 THEN 300.00 WHEN c.id <= 8 THEN 400.00 ELSE 500.00 END,
    '2025-09-10',
    1
FROM `classes` c
WHERE c.id BETWEEN 1 AND 12;

-- ============================================================
-- 14. PAYMENT GATEWAYS
-- ============================================================
INSERT INTO `payment_gateways` (`name`, `slug`, `display_name`, `description`, `is_active`, `environment`, `sort_order`) VALUES
('Telebirr', 'telebirr', 'Telebirr Mobile Money', 'Pay with Telebirr mobile money',            1, 'sandbox', 1),
('Chapa',    'chapa',    'Chapa Payment',          'Pay with Chapa (cards, mobile, bank)',       0, 'sandbox', 2),
('Stripe',   'stripe',   'Stripe',                 'International card payments via Stripe',     0, 'sandbox', 3);

-- ============================================================
-- 15. SETTINGS
-- ============================================================
INSERT INTO `settings` (`setting_group`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_public`) VALUES
-- School info
('school',     'name',               'Urjiberi School',           'string',  'School name',           1),
('school',     'tagline',            'Excellence in Education',   'string',  'Motto / tagline',       1),
('school',     'email',              'info@urjiberi.edu.et',      'string',  'School email',          1),
('school',     'phone',              '+251111000000',             'string',  'School phone',          1),
('school',     'address',            'Addis Ababa, Ethiopia',     'string',  'School address',        1),
('school',     'city',               'Addis Ababa',               'string',  'City',                  1),
('school',     'region',             'Addis Ababa',               'string',  'Region/State',          1),
('school',     'country',            'Ethiopia',                  'string',  'Country',               1),
('school',     'logo',               '',                          'string',  'School logo path',      1),
('school',     'favicon',            '',                          'string',  'Favicon path',          1),
-- System
('system',     'timezone',           'Africa/Addis_Ababa',        'string',  'Default timezone',      0),
('system',     'date_format',        'd M Y',                     'string',  'Date display format',   0),
('system',     'currency',           'ETB',                       'string',  'Currency code',         0),
('system',     'currency_symbol',    'Br',                        'string',  'Currency symbol',       0),
('system',     'language',           'en',                        'string',  'Default language',      0),
('system',     'pagination_limit',   '20',                        'integer', 'Items per page',        0),
('system',     'maintenance_mode',   '0',                         'boolean', 'Maintenance mode',      0),
-- Attendance
('attendance', 'default_mode',       'daily',                     'string',  'daily or subject',      0),
('attendance', 'allow_past_edit',    '1',                         'boolean', 'Allow past edits',      0),
-- Finance
('finance',    'invoice_prefix',     'INV-',                      'string',  'Invoice number prefix', 0),
('finance',    'receipt_prefix',     'RCP-',                      'string',  'Receipt number prefix', 0),
('finance',    'allow_partial_payment','1',                        'boolean', 'Allow partial payment', 0),
('finance',    'auto_overdue_days',  '30',                        'integer', 'Days until overdue',    0),
-- Email
('email',      'smtp_host',          '',                          'string',  'SMTP host',             0),
('email',      'smtp_port',          '587',                       'integer', 'SMTP port',             0),
('email',      'smtp_user',          '',                          'string',  'SMTP username',         0),
('email',      'smtp_pass',          '',                          'string',  'SMTP password',         0),
('email',      'from_email',         'noreply@urjiberi.edu.et',   'string',  'From email',            0),
('email',      'from_name',          'Urjiberi School',           'string',  'From name',             0),
-- SMS
('sms',        'provider',           '',                          'string',  'SMS provider',          0),
('sms',        'api_key',            '',                          'string',  'SMS API key',           0),
('sms',        'sender_id',          'URJIBERI',                  'string',  'SMS sender ID',         0);

-- ============================================================
-- 16. ANNOUNCEMENTS (sample)
-- ============================================================
INSERT INTO `announcements` (`title`, `content`, `type`, `is_pinned`, `status`, `published_at`, `created_by`) VALUES
('Welcome to 2025/2026 Academic Year',
 'We warmly welcome all students, parents, and staff to the 2025/2026 academic year. Classes begin on 1st September 2025.',
 'academic', 1, 'published', '2025-09-01 07:00:00', 1),
('Term 2 Has Begun',
 'Term 2 officially commenced on 1st December 2025. All students must ensure they have paid their term fees.',
 'academic', 0, 'published', '2025-12-01 07:00:00', 1),
('Mid-Term Assessment Results',
 'Mid-term assessment result cards for Term 1 are ready. Parents are invited to collect them at the school office.',
 'general', 0, 'published', '2025-11-15 08:00:00', 1);

-- ============================================================
-- ════════════════════════════════════════════════════════════
-- STORED PROCEDURES FOR BULK DATA GENERATION
-- ════════════════════════════════════════════════════════════
-- ============================================================

DELIMITER $$

-- ──────────────────────────────────────────────────────────
-- PROCEDURE: sp_gen_students
-- Generates 120 students: 5 per section, 2 sections per class,
-- 12 classes. Also creates one guardian + enrollment per student.
-- ──────────────────────────────────────────────────────────
CREATE PROCEDURE sp_gen_students()
BEGIN
    DECLARE v_class  INT DEFAULT 1;
    DECLARE v_sec    INT DEFAULT 1;   -- 1 = Section A, 2 = Section B
    DECLARE v_snum   INT DEFAULT 1;   -- 1..5 students per section
    DECLARE v_seq    INT DEFAULT 1;   -- global sequence 1..120
    DECLARE v_sid    BIGINT;
    DECLARE v_gid    BIGINT;
    DECLARE v_sec_id INT;
    DECLARE v_fn     VARCHAR(100);
    DECLARE v_ln     VARCHAR(100);
    DECLARE v_gend   VARCHAR(10);
    DECLARE v_dob    DATE;
    DECLARE v_gfn    VARCHAR(100);
    DECLARE v_grel   VARCHAR(20);
    DECLARE v_phone  VARCHAR(20);
    DECLARE v_roll   INT;

    WHILE v_class <= 12 DO
        SET v_sec = 1;
        WHILE v_sec <= 2 DO
            SET v_snum = 1;
            WHILE v_snum <= 5 DO

                -- ── First names (alternating gender per slot) ──────────
                -- Section A: Abel(M), Almaz(F), Biruk(M), Birtukan(F), Chala(M)
                -- Section B: Daniel(M), Dinke(F), Eyob(M), Etsub(F), Fikru(M)
                IF v_sec = 1 THEN
                    SET v_fn   = ELT(v_snum, 'Abel', 'Almaz', 'Biruk', 'Birtukan', 'Chala');
                    SET v_gend = ELT(v_snum, 'male', 'female', 'male', 'female', 'male');
                ELSE
                    SET v_fn   = ELT(v_snum, 'Daniel', 'Dinke', 'Eyob', 'Etsub', 'Fikru');
                    SET v_gend = ELT(v_snum, 'male', 'female', 'male', 'female', 'male');
                END IF;

                -- ── Last name varies by position ───────────────────────
                SET v_ln = ELT(MOD((v_class - 1) * 10 + (v_sec - 1) * 5 + v_snum - 1, 10) + 1,
                    'Tadesse', 'Bekele', 'Haile', 'Tesfaye', 'Girma',
                    'Alemu',   'Kebede', 'Mekonen', 'Assefa', 'Worku');

                -- ── Date of birth (age ≈ 5 + grade) ───────────────────
                SET v_dob = MAKEDATE(2020 - v_class,
                    MOD(v_snum * 47 + v_class * 31 + v_sec * 13, 365) + 1);

                -- ── Section ID: class c → section A = (c-1)*2+1 ───────
                SET v_sec_id = (v_class - 1) * 2 + v_sec;
                SET v_roll   = (v_sec - 1) * 5 + v_snum;

                -- ── INSERT student ─────────────────────────────────────
                INSERT INTO `students`
                    (admission_no, roll_no, first_name, last_name, gender,
                     date_of_birth, nationality, admission_date, status, city, region)
                VALUES (
                    CONCAT('URJ2025-', LPAD(v_seq, 3, '0')),
                    v_roll,
                    v_fn, v_ln,
                    v_gend,
                    v_dob,
                    'Ethiopian', '2025-09-01', 'active',
                    'Addis Ababa', 'Addis Ababa'
                );
                SET v_sid = LAST_INSERT_ID();

                -- ── Guardian ───────────────────────────────────────────
                SET v_grel = IF(MOD(v_seq, 2) = 0, 'mother', 'father');
                SET v_gfn  = IF(v_grel = 'father',
                    ELT(MOD(v_seq, 5) + 1, 'Tesfaye', 'Girma', 'Hailu', 'Bekele', 'Abebe'),
                    ELT(MOD(v_seq, 5) + 1, 'Tigist',  'Selamawit', 'Hiwot', 'Mekdes', 'Azeb')
                );
                -- Phone: +25191XXXXXXXX (8 digit suffix, derived from seq)
                SET v_phone = CONCAT('+25191',
                    LPAD(MOD(v_seq * 1234567 + 10000000, 90000000) + 10000000, 8, '0'));

                INSERT INTO `guardians`
                    (first_name, last_name, relation, phone, is_emergency_contact)
                VALUES (v_gfn, v_ln, v_grel, v_phone, 1);
                SET v_gid = LAST_INSERT_ID();

                INSERT INTO `student_guardians` (student_id, guardian_id, is_primary)
                VALUES (v_sid, v_gid, 1);

                -- ── Enrollment ─────────────────────────────────────────
                INSERT INTO `enrollments`
                    (student_id, session_id, class_id, section_id, roll_no, status, enrolled_at)
                VALUES (v_sid, 1, v_class, v_sec_id, v_roll, 'active', '2025-09-01');

                SET v_seq  = v_seq  + 1;
                SET v_snum = v_snum + 1;
            END WHILE; -- students
            SET v_sec = v_sec + 1;
        END WHILE; -- sections
        SET v_class = v_class + 1;
    END WHILE; -- classes
END$$

-- ──────────────────────────────────────────────────────────
-- PROCEDURE: sp_gen_assessments
-- Creates 5 assessments per (class, subject, term) for
-- Terms 1 and 2 only. 12×10×2×5 = 1200 assessments total.
-- ──────────────────────────────────────────────────────────
CREATE PROCEDURE sp_gen_assessments()
BEGIN
    DECLARE v_class INT DEFAULT 1;
    DECLARE v_subj  INT DEFAULT 1;
    DECLARE v_term  INT DEFAULT 1;
    DECLARE v_anum  INT DEFAULT 1;

    WHILE v_class <= 12 DO
        SET v_subj = 1;
        WHILE v_subj <= 10 DO
            SET v_term = 1;
            WHILE v_term <= 2 DO          -- Terms 1 & 2 only
                SET v_anum = 1;
                WHILE v_anum <= 5 DO
                    INSERT INTO `assessments`
                        (`name`, `class_id`, `subject_id`, `session_id`, `term_id`, `total_marks`, `created_by`)
                    VALUES (
                        ELT(v_anum, 'Test 1', 'Test 2', 'Assignment', 'Mid-Term Exam', 'Final Exam'),
                        v_class, v_subj, 1, v_term, 100.00, 1
                    );
                    SET v_anum = v_anum + 1;
                END WHILE; -- assessments
                SET v_term = v_term + 1;
            END WHILE; -- terms
            SET v_subj = v_subj + 1;
        END WHILE; -- subjects
        SET v_class = v_class + 1;
    END WHILE; -- classes
END$$

-- ──────────────────────────────────────────────────────────
-- PROCEDURE: sp_gen_attendance
-- Loops 2025-09-01 → 2026-02-20 (weekdays only).
-- Each day inserts attendance for all 120 enrolled students.
-- Status distribution: ~93% present, ~4% late, ~3% absent.
-- ──────────────────────────────────────────────────────────
CREATE PROCEDURE sp_gen_attendance()
BEGIN
    DECLARE v_date    DATE    DEFAULT '2025-09-01';
    DECLARE v_term_id TINYINT DEFAULT 1;
    DECLARE v_dow     TINYINT;

    WHILE v_date <= '2026-02-20' DO
        SET v_dow = DAYOFWEEK(v_date);  -- 1=Sun, 7=Sat

        IF v_dow NOT IN (1, 7) THEN
            -- Determine which term this date belongs to
            SET v_term_id = CASE
                WHEN v_date BETWEEN '2025-09-01' AND '2025-11-30' THEN 1
                WHEN v_date BETWEEN '2025-12-01' AND '2026-02-28' THEN 2
                ELSE 2
            END;

            INSERT INTO `attendance`
                (student_id, class_id, section_id, session_id, term_id, `date`, `status`, marked_by)
            SELECT
                e.student_id,
                e.class_id,
                e.section_id,
                1,
                v_term_id,
                v_date,
                CASE MOD(e.student_id * 31 + TO_DAYS(v_date) * 7, 100)
                    WHEN 97 THEN 'absent'
                    WHEN 98 THEN 'absent'
                    WHEN 99 THEN 'absent'
                    WHEN 94 THEN 'late'
                    WHEN 95 THEN 'late'
                    WHEN 96 THEN 'late'
                    WHEN 93 THEN 'late'
                    ELSE         'present'
                END,
                1
            FROM `enrollments` e
            WHERE e.session_id = 1;
        END IF;

        SET v_date = DATE_ADD(v_date, INTERVAL 1 DAY);
    END WHILE;
END$$

DELIMITER ;

-- ============================================================
-- CALL PROCEDURES
-- ============================================================
CALL sp_gen_students();
CALL sp_gen_assessments();
CALL sp_gen_attendance();

-- ============================================================
-- DROP PROCEDURES (clean up)
-- ============================================================
DROP PROCEDURE IF EXISTS sp_gen_students;
DROP PROCEDURE IF EXISTS sp_gen_assessments;
DROP PROCEDURE IF EXISTS sp_gen_attendance;

-- ============================================================
-- 17. STUDENT RESULTS (INSERT...SELECT — no cursor needed)
-- Joins every assessment with every enrolled student in the
-- same class+session. 1200 assessments × 10 students = 12,000 rows.
--
-- Mark tiers (determined by student_id MOD 10):
--   MOD = 0        → weak    32–53  (some failures)
--   MOD = 1 or 2   → strong  80–96
--   everything else → average 55–82
-- ============================================================
INSERT INTO `student_results`
    (assessment_id, student_id, class_id, section_id, marks_obtained, is_absent, entered_by)
SELECT
    a.id,
    e.student_id,
    e.class_id,
    e.section_id,
    ROUND(CASE
        WHEN MOD(e.student_id * 3 + a.id, 10) = 0
            THEN 32 + MOD(e.student_id * 17 + a.id * 13,              22)
        WHEN MOD(e.student_id * 3 + a.id, 10) IN (1, 2)
            THEN 80 + MOD(e.student_id *  7 + a.id *  3,              17)
        ELSE
            55 + MOD(e.student_id * 11 + a.id *  7 + a.subject_id * 5, 28)
    END) AS marks_obtained,
    0,
    1
FROM `assessments` a
JOIN `enrollments` e
    ON e.class_id  = a.class_id
    AND e.session_id = a.session_id
ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained);

-- ============================================================
-- 18. STUDENT CONDUCT (Terms 1 and 2)
-- Grade distribution ≈ A:14%, B:43%, C:29%, D:14%
-- ============================================================
INSERT INTO `student_conduct`
    (student_id, class_id, session_id, term_id, conduct, entered_by)
SELECT
    e.student_id,
    e.class_id,
    1,
    t.id,
    ELT(MOD(e.student_id * 3 + t.id * 7, 7) + 1,
        'A', 'B', 'B', 'B', 'C', 'C', 'D'),
    1
FROM `enrollments` e
CROSS JOIN `terms` t
    ON t.session_id = 1 AND t.sort_order <= 2
ON DUPLICATE KEY UPDATE conduct = VALUES(conduct);

-- ============================================================
-- 19. SAMPLE ANNOUNCEMENTS (already inserted above in section 16)
-- ============================================================

-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- seed.sql complete
-- Summary:
--   Users:       6 (1 admin + 5 teachers)
--   Classes:     12  |  Sections: 24
--   Students:    120 (5 per section × 2 sections × 12 classes)
--   Subjects:    10  (all classes)
--   Terms:       4   (Term 2 is active — Feb 2026)
--   Assessments: 1200 (5 per subject per class × Terms 1 & 2)
--   Results:     12000 rows (all filled)
--   Attendance:  ~15,000 rows (Sep 1 2025 → Feb 20 2026, weekdays)
--   Conduct:     240 rows (120 students × 2 terms)
-- ============================================================
