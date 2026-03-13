-- ============================================================
-- Urji Beri School SMS — COMPLETE SEED DATA
-- Production-Ready | Realistic Ethiopian School Data
-- Run AFTER db.sql | Requires MySQL 8.0+ (recursive CTEs)
-- Generated: 2026-03-09
-- ============================================================
-- Contents:
--   7 roles, 94+ permissions, full role-permission matrix
--   3 mediums, 3 streams, 3 shifts
--   18 users: 1 admin, 12 teachers, 1 accountant, 1 registrar,
--             1 librarian, 1 security guard, 1 IT technician
--   1 academic session (2025/2026) with 4 terms
--   12 classes × 2 sections = 24 sections
--   14 subjects, 10 per class = 120 class-subject mappings
--   300 students, 300 guardians, 300 enrollments
--   120 Term 1 assessments, 3,000 student results
--   ~16,500 attendance records (Sep–Nov 2025)
--   6 HR departments, 18 employees, 8 leave types, 13 holidays
--   39+ system settings, 3 announcements
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET @now = NOW();

-- ============================================================
-- SECTION 1: ROLES
-- ============================================================
INSERT IGNORE INTO `roles` (`id`, `name`, `slug`, `description`, `is_system`) VALUES
(1, 'Super Admin',   'super_admin',   'Full system access — all modules, all operations',             1),
(2, 'School Admin',  'school_admin',  'Manages school operations, staff, and academic settings',      1),
(3, 'Teacher',       'teacher',       'Manages classes, assignments, grades, and attendance',          0),
(4, 'Student',       'student',       'Views own academic info, grades, fees, and attendance',         0),
(5, 'Parent',        'parent',        'Views child academic info, fees, and communicates with school', 0),
(6, 'Accountant',    'accountant',    'School accountant role',                                        0),
(7, 'Registrar',     'registrar',     'Manages student admissions, enrollments, and records',          0);

-- ============================================================
-- SECTION 2: PERMISSIONS (94 total: 77 core/finance + 12 HR + 5 messaging)
-- ============================================================
INSERT IGNORE INTO `permissions` (`id`, `module`, `action`, `description`) VALUES
-- ── Dashboard ──
( 1, 'dashboard',      'view',            'View dashboard overview'),
-- ── Users ──
( 2, 'users',          'list',            'View users list'),
( 3, 'users',          'create',          'Create a new user'),
( 4, 'users',          'edit',            'Edit any user'),
( 5, 'users',          'delete',          'Delete a user'),
( 6, 'users',          'view',            'View user details'),
-- ── Academics ──
( 7, 'academics',      'list',            'View academic configuration'),
( 8, 'academics',      'create',          'Create classes, sections, subjects'),
( 9, 'academics',      'edit',            'Edit academic configuration'),
(10, 'academics',      'delete',          'Delete academic data'),
(11, 'academics',      'view',            'View academic detail'),
-- ── Students ──
(12, 'students',       'list',            'View students list'),
(13, 'students',       'create',          'Register a new student'),
(14, 'students',       'edit',            'Edit student information'),
(15, 'students',       'delete',          'Delete a student record'),
(16, 'students',       'view',            'View individual student details'),
(17, 'students',       'import',          'Bulk-import students from CSV'),
(18, 'students',       'export',          'Export student data'),
-- ── Attendance ──
(19, 'attendance',     'list',            'View attendance records'),
(20, 'attendance',     'mark',            'Mark daily attendance'),
(21, 'attendance',     'edit',            'Edit attendance records'),
(22, 'attendance',     'report',          'View attendance reports'),
(23, 'attendance',     'view',            'View attendance details'),
-- ── Exams ──
(24, 'exams',          'list',            'View exams list'),
(25, 'exams',          'create',          'Create exams and schedules'),
(26, 'exams',          'edit',            'Edit exam details'),
(27, 'exams',          'delete',          'Delete an exam'),
(28, 'exams',          'view',            'View exam details'),
(29, 'exams',          'marks_entry',     'Enter student marks'),
(30, 'exams',          'report_cards',    'Generate / view report cards'),
-- ── Communication ──
(38, 'communication',  'announcements',   'Manage announcements'),
(39, 'communication',  'messages',        'Send and read messages'),
(40, 'communication',  'view',            'View communications'),
-- ── Settings ──
(41, 'settings',       'view',            'View system settings'),
(42, 'settings',       'edit',            'Modify system settings'),
-- ── Roles ──
(43, 'roles',          'list',            'View roles list'),
(44, 'roles',          'create',          'Create a new role'),
(45, 'roles',          'edit',            'Edit a role'),
(46, 'roles',          'delete',          'Delete a role'),
-- ── Reports ──
(47, 'reports',        'academic',        'View academic reports'),
(48, 'reports',        'financial',       'View financial reports'),
(49, 'reports',        'attendance',      'View attendance summary reports'),
-- ── Profile ──
(50, 'profile',        'view',            'View own profile'),
(51, 'profile',        'edit',            'Edit own profile'),
(52, 'profile',        'change_password', 'Change own password'),
-- ── Timetable ──
(54, 'timetable',      'list',            'View timetable list'),
(55, 'timetable',      'create',          'Create timetable entries'),
(56, 'timetable',      'edit',            'Edit timetable entries'),
(57, 'timetable',      'delete',          'Delete timetable entries'),
(58, 'timetable',      'view',            'View timetable detail'),
-- ── Individual views ──
(59, 'assignment',     'view',            'View a single assignment'),
(60, 'exam',           'view',            'View a single exam'),
(61, 'marks',          'view',            'View marks detail'),
(62, 'report_card',    'view',            'View a single report card'),
-- ── Manage-level ──
(63, 'attendance',     'manage',          'Take and edit attendance'),
(64, 'academics',      'manage',          'Manage academic configuration'),
(65, 'timetable',      'manage',          'Create/edit/delete timetable entries'),
(66, 'exam',           'manage',          'Create/edit/delete exams and assessments'),
(67, 'marks',          'manage',          'Enter and edit student marks'),
(68, 'assignment',     'manage',          'Create/edit/delete assignments'),
(69, 'report_card',    'manage',          'Generate and manage report cards'),
(70, 'students',       'promote',         'Promote students to next class'),
(71, 'settings',       'update',          'Update system settings'),
(72, 'audit_logs',     'view',            'View audit logs'),
(73, 'communication',  'create',          'Create announcements and messages'),
(74, 'communication',  'delete',          'Delete announcements'),
-- ── Finance ──
(75, 'finance',        'view',            'View finance data'),
(76, 'finance',        'manage',          'Manage fees and payments'),
(77, 'finance',        'reports',         'View financial reports'),
-- ── HR (78-89) ──
(78, 'hr',             'view',            'View HR module and employee data'),
(79, 'hr',             'manage',          'Full HR management access'),
(80, 'hr',             'employees',       'Manage employee records'),
(81, 'hr',             'departments',     'Manage departments'),
(82, 'hr',             'attendance',      'Manage staff attendance'),
(83, 'hr',             'leave',           'Manage leave requests'),
(84, 'hr',             'payroll',         'Generate and manage payroll'),
(85, 'hr',             'payroll_approve', 'Approve payroll for payment'),
(86, 'hr',             'reports',         'View HR reports and analytics'),
(87, 'hr',             'devices',         'Manage biometric devices'),
(88, 'hr',             'print',           'Print payroll forms and reports'),
(89, 'hr',             'allowances',      'Manage employee recurring allowances'),
-- ── Messaging (90-94) ──
(90, 'messaging',      'solo',            'Send and receive solo (direct) messages'),
(91, 'messaging',      'bulk',            'Send bulk messages to students/teachers'),
(92, 'messaging',      'group',           'Create and manage student groups'),
(93, 'messaging',      'view',            'View message history and conversations'),
(94, 'messaging',      'attachment',      'Attach files to messages');

-- ============================================================
-- SECTION 3: ROLE → PERMISSION MAPPING
-- ============================================================
-- Super Admin (role 1) — ALL permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`;

-- School Admin (role 2) — all except role create/delete
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, `id` FROM `permissions` WHERE `id` NOT IN (44, 46);

-- Teacher (role 3)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(3,1),(3,7),(3,11),(3,12),(3,16),(3,19),(3,20),(3,21),(3,22),(3,23),
(3,24),(3,25),(3,26),(3,28),(3,29),(3,30),(3,38),(3,39),(3,40),
(3,47),(3,49),(3,50),(3,51),(3,52),(3,54),(3,58),(3,59),(3,60),(3,61),(3,62),
(3,63),(3,65),(3,66),(3,67),(3,68),(3,69),(3,73),
-- Messaging for teachers
(3,90),(3,93),(3,94);

-- Student (role 4)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(4,1),(4,7),(4,11),(4,19),(4,23),(4,24),(4,28),(4,30),
(4,40),(4,50),(4,51),(4,52),(4,54),(4,58),(4,59),(4,60),(4,61),(4,62),
(4,90),(4,93);

-- Parent (role 5)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(5,1),(5,7),(5,11),(5,12),(5,16),(5,19),(5,23),(5,24),(5,28),(5,30),
(5,39),(5,40),(5,50),(5,51),(5,52),(5,59),(5,60),(5,61),(5,62),(5,75),
(5,90),(5,93);

-- Accountant (role 6)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(6,1),(6,7),(6,11),(6,12),(6,16),(6,19),(6,23),(6,39),(6,40),
(6,50),(6,51),(6,52),(6,75),(6,76),(6,77),
-- HR: view, payroll, reports, print
(6,78),(6,84),(6,86),(6,88),
-- Messaging
(6,90),(6,93);

-- Registrar (role 7)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(7,1),(7,7),(7,8),(7,9),(7,11),(7,12),(7,13),(7,14),(7,15),(7,16),
(7,17),(7,18),(7,19),(7,22),(7,23),(7,38),(7,39),(7,40),(7,47),(7,50),(7,51),(7,52),(7,70),(7,73),
(7,90),(7,93);

-- ============================================================
-- SECTION 4: MEDIUMS, STREAMS, SHIFTS
-- ============================================================
INSERT IGNORE INTO `mediums` (`id`, `name`, `is_active`, `sort_order`) VALUES
(1, 'English Medium',    1, 1),
(2, 'Afan Oromo Medium', 1, 2),
(3, 'Amharic Medium',    1, 3);

INSERT IGNORE INTO `streams` (`id`, `name`, `description`, `is_active`, `sort_order`) VALUES
(1, 'Natural Science', 'For Grades 9-12: Physics, Chemistry, Biology',        1, 1),
(2, 'Social Science',  'For Grades 9-12: History, Geography, Economics',       1, 2),
(3, 'General',         'For Grades 1-8: All general education subjects apply', 1, 3);

INSERT IGNORE INTO `shifts` (`id`, `name`, `start_time`, `end_time`, `is_active`, `sort_order`) VALUES
(1, 'Morning Shift',   '08:00:00', '12:30:00', 1, 1),
(2, 'Afternoon Shift', '13:00:00', '17:30:00', 1, 2),
(3, 'Full Day',        '08:00:00', '17:30:00', 1, 3);

-- ============================================================
-- SECTION 5: USERS — Admin (1) + 12 Teachers (2-13) + 5 Staff (14-18)
-- ============================================================
-- All accounts password: password
INSERT IGNORE INTO `users` (`id`,`username`,`email`,`password_hash`,`full_name`,`first_name`,`last_name`,`phone`,`is_active`,`status`,`force_password_change`) VALUES
( 1, 'admin',      'admin@urjiberischool.com',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator',  'System',     'Administrator', '0912000000', 1, 'active', 0),
( 2, 'teacher1',   'tadesse.m@urjiberischool.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tadesse Mekonnen',      'Tadesse',    'Mekonnen',      '0913000001', 1, 'active', 1),
( 3, 'teacher2',   'almaz.b@urjiberischool.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Almaz Bekele',          'Almaz',      'Bekele',        '0913000002', 1, 'active', 1),
( 4, 'teacher3',   'dereje.h@urjiberischool.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dereje Haile',          'Dereje',     'Haile',         '0913000003', 1, 'active', 1),
( 5, 'teacher4',   'tigist.w@urjiberischool.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tigist Worku',          'Tigist',     'Worku',         '0913000004', 1, 'active', 1),
( 6, 'teacher5',   'mulugeta.a@urjiberischool.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mulugeta Assefa',       'Mulugeta',   'Assefa',        '0913000005', 1, 'active', 1),
( 7, 'teacher6',   'meron.g@urjiberischool.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Meron Getahun',         'Meron',      'Getahun',       '0913000006', 1, 'active', 1),
( 8, 'teacher7',   'hailu.n@urjiberischool.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hailu Negash',          'Hailu',      'Negash',        '0913000007', 1, 'active', 1),
( 9, 'teacher8',   'ayelech.d@urjiberischool.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ayelech Desta',         'Ayelech',    'Desta',         '0913000008', 1, 'active', 1),
(10, 'teacher9',   'tekle.b@urjiberischool.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tekle Berhane',         'Tekle',      'Berhane',       '0913000009', 1, 'active', 1),
(11, 'teacher10',  'girma.z@urjiberischool.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Girma Zeleke',          'Girma',      'Zeleke',        '0913000010', 1, 'active', 1),
(12, 'teacher11',  'meseret.t@urjiberischool.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Meseret Tadesse',       'Meseret',    'Tadesse',       '0913000011', 1, 'active', 1),
(13, 'teacher12',  'birhanu.al@urjiberischool.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Birhanu Alemayehu',     'Birhanu',    'Alemayehu',     '0913000012', 1, 'active', 1),
-- Additional staff
(14, 'accountant', 'abel.w@urjiberischool.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Abel Worku',            'Abel',       'Worku',         '0913000013', 1, 'active', 1),
(15, 'registrar',  'selamawit.d@urjiberischool.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Selamawit Demissie',    'Selamawit',  'Demissie',      '0913000014', 1, 'active', 1),
(16, 'librarian',  'fantaye.g@urjiberischool.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fantaye Girma',         'Fantaye',    'Girma',         '0913000015', 1, 'active', 1),
(17, 'guard1',     'kebede.t@urjiberischool.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kebede Teshome',        'Kebede',     'Teshome',       '0913000016', 1, 'active', 1),
(18, 'ittech',     'nahom.f@urjiberischool.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nahom Fikre',           'Nahom',      'Fikre',         '0913000017', 1, 'active', 1),
-- Demo student account
(19, 'abebe',      'abebe@urjiberischool.com',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Abebe Girma',           'Abebe',      'Girma',         '0913000018', 1, 'active', 0);

-- Assign roles
INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`) VALUES
( 1, 1),  -- admin → super_admin
( 2, 3),( 3, 3),( 4, 3),( 5, 3),( 6, 3),( 7, 3),  -- teachers
( 8, 3),( 9, 3),(10, 3),(11, 3),(12, 3),(13, 3),
(14, 6),  -- accountant
(15, 7),  -- registrar
(16, 3),  -- librarian (teacher role for simplicity)
(17, 3),  -- guard (basic role)
(18, 3),  -- IT tech (basic role)
(19, 4);  -- abebe → student

-- ============================================================
-- SECTION 6: ACADEMIC SESSION & TERMS
-- ============================================================
INSERT IGNORE INTO `academic_sessions` (`id`, `name`, `slug`, `start_date`, `end_date`, `is_active`) VALUES
(1, '2025/2026 Academic Year', '2025-2026', '2025-09-01', '2026-06-30', 1);

INSERT IGNORE INTO `terms` (`id`, `session_id`, `name`, `slug`, `start_date`, `end_date`, `is_active`, `sort_order`) VALUES
(1, 1, 'Term 1', 'term-1-2025-2026', '2025-09-01', '2025-11-15', 0, 1),
(2, 1, 'Term 2', 'term-2-2025-2026', '2025-11-16', '2026-01-31', 0, 2),
(3, 1, 'Term 3', 'term-3-2025-2026', '2026-02-01', '2026-04-15', 1, 3),
(4, 1, 'Term 4', 'term-4-2025-2026', '2026-04-16', '2026-06-30', 0, 4);

-- ============================================================
-- SECTION 7: CLASSES & SECTIONS (Grade 1-12, A & B = 24)
-- ============================================================
INSERT IGNORE INTO `classes` (`id`,`name`,`slug`,`numeric_name`,`description`,`medium_id`,`stream_id`,`shift_id`,`sort_order`,`is_active`) VALUES
( 1, 'Grade 1',  'grade-1',   1, 'First grade',    1, 3, 1,  1, 1),
( 2, 'Grade 2',  'grade-2',   2, 'Second grade',   1, 3, 1,  2, 1),
( 3, 'Grade 3',  'grade-3',   3, 'Third grade',    1, 3, 1,  3, 1),
( 4, 'Grade 4',  'grade-4',   4, 'Fourth grade',   1, 3, 1,  4, 1),
( 5, 'Grade 5',  'grade-5',   5, 'Fifth grade',    1, 3, 1,  5, 1),
( 6, 'Grade 6',  'grade-6',   6, 'Sixth grade',    1, 3, 1,  6, 1),
( 7, 'Grade 7',  'grade-7',   7, 'Seventh grade',  1, 3, 1,  7, 1),
( 8, 'Grade 8',  'grade-8',   8, 'Eighth grade',   1, 3, 1,  8, 1),
( 9, 'Grade 9',  'grade-9',   9, 'Ninth grade',    1, 1, 3,  9, 1),
(10, 'Grade 10', 'grade-10', 10, 'Tenth grade',    1, 1, 3, 10, 1),
(11, 'Grade 11', 'grade-11', 11, 'Eleventh grade', 1, 1, 3, 11, 1),
(12, 'Grade 12', 'grade-12', 12, 'Twelfth grade',  1, 1, 3, 12, 1);

INSERT IGNORE INTO `sections` (`id`, `class_id`, `name`, `capacity`, `is_active`) VALUES
( 1,  1,'A',40,1),( 2,  1,'B',40,1),( 3,  2,'A',40,1),( 4,  2,'B',40,1),
( 5,  3,'A',40,1),( 6,  3,'B',40,1),( 7,  4,'A',40,1),( 8,  4,'B',40,1),
( 9,  5,'A',40,1),(10,  5,'B',40,1),(11,  6,'A',40,1),(12,  6,'B',40,1),
(13,  7,'A',45,1),(14,  7,'B',45,1),(15,  8,'A',45,1),(16,  8,'B',45,1),
(17,  9,'A',45,1),(18,  9,'B',45,1),(19, 10,'A',45,1),(20, 10,'B',45,1),
(21, 11,'A',45,1),(22, 11,'B',45,1),(23, 12,'A',45,1),(24, 12,'B',45,1);

-- ============================================================
-- SECTION 8: SUBJECTS (14 — ensures 10 per class)
-- ============================================================
INSERT IGNORE INTO `subjects` (`id`,`name`,`code`,`description`,`type`,`is_active`) VALUES
( 1, 'English',               'ENG',  'English Language',                   'theory', 1),
( 2, 'Amharic',               'AMH',  'Amharic Language',                   'theory', 1),
( 3, 'Afan Oromo',            'ORO',  'Oromo Language',                     'theory', 1),
( 4, 'Mathematics',           'MATH', 'General Mathematics',                'both',   1),
( 5, 'Physics',               'PHY',  'Physics (Grades 7-12)',              'both',   1),
( 6, 'Chemistry',             'CHEM', 'Chemistry (Grades 7-12)',            'both',   1),
( 7, 'Biology',               'BIO',  'Biology (Grades 7-12)',              'both',   1),
( 8, 'Geography',             'GEO',  'Geography / Environmental Science',  'theory', 1),
( 9, 'History',               'HIST', 'History and Civics',                 'theory', 1),
(10, 'ICT',                   'ICT',  'Information & Communication Tech',   'both',   1),
(11, 'Physical Education',    'PE',   'Physical Education & Health',        'practical', 1),
(12, 'Art & Music',           'ART',  'Creative Arts and Music',            'practical', 1),
(13, 'Civic Education',       'CIV',  'Civic and Ethical Education',        'theory', 1),
(14, 'Environmental Science', 'ENV',  'Environmental Science (Grades 1-6)', 'theory', 1);

-- ============================================================
-- SECTION 9: CLASS-SUBJECT ASSIGNMENTS (exactly 10 per class)
-- ============================================================
-- Grade 1-4:  ENG,AMH,ORO,MATH, GEO,HIST, PE,ART,CIV,ENV  = 10
-- Grade 5-6:  ENG,AMH,ORO,MATH, GEO,HIST, ICT,PE,CIV,ENV  = 10
-- Grade 7-12: ENG,AMH,ORO,MATH, GEO,HIST, ICT,PHY,CHEM,BIO = 10

-- Core languages & math → all 12 grades
INSERT IGNORE INTO `class_subjects` (`class_id`,`subject_id`,`session_id`,`is_elective`)
SELECT c.id, s.id, 1, 0 FROM `classes` c CROSS JOIN `subjects` s WHERE s.id IN (1,2,3,4);

-- Geography & History → all 12 grades
INSERT IGNORE INTO `class_subjects` (`class_id`,`subject_id`,`session_id`,`is_elective`)
SELECT c.id, s.id, 1, 0 FROM `classes` c CROSS JOIN `subjects` s WHERE s.id IN (8,9);

-- Physical Education → grades 1-6
INSERT IGNORE INTO `class_subjects` (`class_id`,`subject_id`,`session_id`,`is_elective`)
SELECT c.id, 11, 1, 0 FROM `classes` c WHERE c.numeric_name <= 6;

-- Art & Music → grades 1-4
INSERT IGNORE INTO `class_subjects` (`class_id`,`subject_id`,`session_id`,`is_elective`)
SELECT c.id, 12, 1, 0 FROM `classes` c WHERE c.numeric_name <= 4;

-- Civic Education → grades 1-6
INSERT IGNORE INTO `class_subjects` (`class_id`,`subject_id`,`session_id`,`is_elective`)
SELECT c.id, 13, 1, 0 FROM `classes` c WHERE c.numeric_name <= 6;

-- Environmental Science → grades 1-6
INSERT IGNORE INTO `class_subjects` (`class_id`,`subject_id`,`session_id`,`is_elective`)
SELECT c.id, 14, 1, 0 FROM `classes` c WHERE c.numeric_name <= 6;

-- ICT → grades 5-12
INSERT IGNORE INTO `class_subjects` (`class_id`,`subject_id`,`session_id`,`is_elective`)
SELECT c.id, 10, 1, 0 FROM `classes` c WHERE c.numeric_name >= 5;

-- Physics, Chemistry, Biology → grades 7-12
INSERT IGNORE INTO `class_subjects` (`class_id`,`subject_id`,`session_id`,`is_elective`)
SELECT c.id, s.id, 1, 0 FROM `classes` c CROSS JOIN `subjects` s
WHERE c.numeric_name >= 7 AND s.id IN (5,6,7);

-- ============================================================
-- SECTION 10: CLASS TEACHERS (1 teacher per grade)
-- ============================================================
INSERT IGNORE INTO `class_teachers` (`class_id`,`teacher_id`,`session_id`,`is_class_teacher`) VALUES
(1,2,1,1),(2,3,1,1),(3,4,1,1),(4,5,1,1),(5,6,1,1),(6,7,1,1),
(7,8,1,1),(8,9,1,1),(9,10,1,1),(10,11,1,1),(11,12,1,1),(12,13,1,1);

-- ============================================================
-- SECTION 11: GRADE SCALE (Ethiopian Standard)
-- ============================================================
INSERT IGNORE INTO `grade_scales` (`id`, `name`, `is_default`) VALUES (1, 'Ethiopian Standard', 1);

INSERT IGNORE INTO `grade_scale_entries` (`grade_scale_id`,`grade`,`min_percentage`,`max_percentage`,`grade_point`,`remark`) VALUES
(1,'A+',95.00,100.00,4.00,'Outstanding'),
(1,'A', 90.00, 94.99,4.00,'Excellent'),
(1,'A-',85.00, 89.99,3.75,'Very Good'),
(1,'B+',80.00, 84.99,3.50,'Good Plus'),
(1,'B', 75.00, 79.99,3.00,'Good'),
(1,'B-',70.00, 74.99,2.75,'Above Average'),
(1,'C+',65.00, 69.99,2.50,'Average Plus'),
(1,'C', 60.00, 64.99,2.00,'Average'),
(1,'C-',50.00, 59.99,1.75,'Below Average'),
(1,'D', 40.00, 49.99,1.00,'Pass'),
(1,'F',  0.00, 39.99,0.00,'Fail');

-- ============================================================
-- SECTION 12: SYSTEM SETTINGS (39 entries)
-- ============================================================
INSERT IGNORE INTO `settings` (`setting_group`,`setting_key`,`setting_value`,`setting_type`,`description`,`is_public`) VALUES
('school','school_name',      'Urji Beri School',            'string', 'Official school name',          1),
('school','school_tagline',   'Excellence in Education',     'string', 'School motto / tagline',        1),
('school','school_email',     'info@urjiberischool.com',     'string', 'Primary school email',          1),
('school','school_phone',     '+251-123-456-789',            'string', 'Primary school phone',          1),
('school','school_address',   'Adama, Oromia, Ethiopia',     'string', 'School address',                1),
('school','school_city',      'Adama',                       'string', 'City',                          1),
('school','school_region',    'Oromia',                      'string', 'Region / state',                1),
('school','school_country',   'Ethiopia',                    'string', 'Country',                       1),
('school','school_website',   'https://urjiberischool.com',  'string', 'School website URL',            1),
('school','school_logo',      '/assets/img/logo.png',        'string', 'Path to school logo',           1),
('school','school_favicon',   '/assets/img/favicon.ico',     'string', 'Path to favicon',               1),
('system','timezone',         'Africa/Addis_Ababa',          'string', 'Server timezone',               0),
('system','date_format',      'Y-m-d',                       'string', 'PHP date format',               0),
('system','time_format',      'H:i',                         'string', 'PHP time format',               0),
('system','currency',         'ETB',                         'string', 'Default currency code',         0),
('system','currency_symbol',  'Br',                          'string', 'Currency symbol (Birr)',         0),
('system','language',         'en',                          'string', 'Default UI language',           0),
('system','items_per_page',   '25',                          'integer','Default pagination size',       0),
('system','maintenance_mode', '0',                           'boolean','Enable maintenance mode',       0),
('system','session_lifetime', '120',                         'integer','Session timeout in minutes',    0),
('attendance','default_status',        'present',            'string', 'Default attendance status',     0),
('attendance','late_threshold_minutes','15',                  'integer','Minutes threshold for late',    0),
('attendance','weekend_days',          '6,7',                 'string', 'Weekend days (6=Sat,7=Sun)',    0),
('email','smtp_host',     '',                                'string', 'SMTP server hostname',          0),
('email','smtp_port',     '587',                             'integer','SMTP server port',              0),
('email','smtp_username', '',                                'string', 'SMTP username',                 0),
('email','smtp_password', '',                                'string', 'SMTP password (encrypted)',     0),
('email','from_email',    'noreply@urjiberischool.com',      'string', 'Default from email',            0),
('email','from_name',     'Urji Beri School',                'string', 'Default from name',             0),
('sms','provider',  '',                                      'string', 'SMS provider name',             0),
('sms','api_key',   '',                                      'string', 'SMS provider API key',          0),
('sms','sender_id', 'UrjiBeri',                              'string', 'SMS sender ID',                 0);

-- ============================================================
-- SECTION 13: ANNOUNCEMENTS
-- ============================================================
INSERT IGNORE INTO `announcements` (`title`,`content`,`type`,`target_roles`,`is_pinned`,`status`,`published_at`,`created_by`) VALUES
('Welcome to 2025/2026 Academic Year',
 'We are pleased to welcome all students, parents, and staff to the new academic year. Classes begin September 1, 2025.',
 'general',NULL,1,'published',@now,1),
('First Term Examination Schedule',
 'First term examinations were held November 1-15, 2025. Results are now available.',
 'academic','teacher,student,parent',0,'published',@now,1),
('Parent-Teacher Meeting',
 'A parent-teacher meeting is scheduled for February 28, 2026. All parents are kindly requested to attend.',
 'event','parent,teacher',0,'published',@now,1);


-- ############################################################
-- SECTION 14: HR MODULE SEED DATA
-- ############################################################

-- ── Departments ──
INSERT IGNORE INTO `hr_departments` (`id`, `name`, `code`, `description`, `status`) VALUES
(1, 'Administration',    'ADMIN',   'School administration and management',        'active'),
(2, 'Teaching Staff',    'TEACH',   'All teaching and instructional staff',         'active'),
(3, 'Finance',           'FIN',     'Financial management and accounting',          'active'),
(4, 'Library',           'LIB',     'Library services and management',              'active'),
(5, 'Support Services',  'SUPPORT', 'Maintenance, security, and support staff',     'active'),
(6, 'IT Department',     'IT',      'Information technology and systems',            'active');

-- ── Leave Types ──
INSERT IGNORE INTO `hr_leave_types` (`name`, `code`, `days_allowed`, `description`, `status`) VALUES
('Annual Leave',      'ANNUAL',     16, 'Annual paid leave entitlement per Ethiopian labor law', 'active'),
('Sick Leave',        'SICK',        6, 'Paid sick leave with medical certificate',              'active'),
('Maternity Leave',   'MATERNITY', 120, 'Maternity leave (30 prenatal + 90 postnatal)',           'active'),
('Paternity Leave',   'PATERNITY',   5, 'Paternity leave for new fathers',                       'active'),
('Bereavement Leave', 'BEREAVE',     3, 'Leave for death of immediate family member',             'active'),
('Marriage Leave',    'MARRIAGE',    3, 'Leave for employee marriage',                            'active'),
('Unpaid Leave',      'UNPAID',      0, 'Unpaid leave (deducted from salary)',                    'active'),
('Study Leave',       'STUDY',      10, 'Leave for examinations and study',                       'active');

-- ── Ethiopian Public Holidays ──
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

-- ── Employees — 18 staff linked to users ──
-- Salary levels:  Director ~25,000 | Senior Teacher ~12,000-15,000 | Teacher ~8,000-11,000
--                 Accountant ~10,000 | Registrar ~8,000 | Support ~5,000-7,000
INSERT IGNORE INTO `hr_employees`
    (`id`, `employee_id`, `first_name`, `father_name`, `grandfather_name`,
     `first_name_am`, `father_name_am`, `grandfather_name_am`,
     `gender`, `date_of_birth_ec`, `date_of_birth_gregorian`,
     `phone`, `email`, `department_id`, `position`, `qualification`, `role`,
     `employment_type`, `start_date_ec`, `start_date_gregorian`,
     `basic_salary`, `transport_allowance`, `position_allowance`,
     `tin_number`, `pension_number`, `bank_name`, `bank_account`,
     `user_id`, `status`) VALUES
-- Director / Admin
( 1, 'EMP-2025-0001', 'Yohannes',   'Gebre',     'Meskel',
  'ዮሐንስ',           'ገብሬ',        'መስቀል',
  'male',   '15/03/1975', '1982-11-25',
  '0912000000', 'admin@urjiberischool.com', 1, 'School Director', 'MA Education', 'admin',
  'permanent', '01/01/2010', '2017-09-11',
  25000.00, 1500.00, 3000.00,
  'TIN-001-001', 'PEN-001-001', 'Commercial Bank of Ethiopia', '1000001234561', 1, 'active'),
-- Teachers (users 2-13)
( 2, 'EMP-2025-0002', 'Tadesse',    'Mekonnen',  'Haile',
  'ታደሰ',            'መኮንን',       'ሐይሌ',
  'male',   '23/07/1982', '1990-03-15',
  '0913000001', 'tadesse.m@urjiberischool.com', 2, 'Senior Teacher - Grade 1', 'BSc Education', 'teacher',
  'permanent', '01/01/2015', '2022-09-11',
  12000.00, 800.00, 1000.00,
  'TIN-002-001', 'PEN-002-001', 'Commercial Bank of Ethiopia', '1000001234562', 2, 'active'),
( 3, 'EMP-2025-0003', 'Almaz',      'Bekele',    'Tadesse',
  'አልማዝ',           'በቀለ',        'ታደሰ',
  'female', '12/04/1985', '1992-12-22',
  '0913000002', 'almaz.b@urjiberischool.com', 2, 'Teacher - Grade 2', 'BEd', 'teacher',
  'permanent', '01/01/2016', '2023-09-11',
  10000.00, 800.00, 500.00,
  'TIN-003-001', 'PEN-003-001', 'Dashen Bank', '2000001234563', 3, 'active'),
( 4, 'EMP-2025-0004', 'Dereje',     'Haile',     'Gebre',
  'ደረጀ',            'ሐይሌ',        'ገብሬ',
  'male',   '05/11/1980', '1988-07-15',
  '0913000003', 'dereje.h@urjiberischool.com', 2, 'Senior Teacher - Grade 3', 'BSc Education', 'teacher',
  'permanent', '01/01/2014', '2021-09-11',
  13000.00, 800.00, 1000.00,
  'TIN-004-001', 'PEN-004-001', 'Awash Bank', '3000001234564', 4, 'active'),
( 5, 'EMP-2025-0005', 'Tigist',     'Worku',     'Assefa',
  'ትግስት',           'ወርቁ',        'አሰፋ',
  'female', '18/02/1988', '1995-10-28',
  '0913000004', 'tigist.w@urjiberischool.com', 2, 'Teacher - Grade 4', 'BEd', 'teacher',
  'permanent', '01/01/2017', '2024-09-11',
  9500.00, 800.00, 500.00,
  'TIN-005-001', 'PEN-005-001', 'Commercial Bank of Ethiopia', '1000001234565', 5, 'active'),
( 6, 'EMP-2025-0006', 'Mulugeta',   'Assefa',    'Wolde',
  'ሙሉጌታ',           'አሰፋ',        'ወልዴ',
  'male',   '09/06/1983', '1991-02-17',
  '0913000005', 'mulugeta.a@urjiberischool.com', 2, 'Teacher - Grade 5', 'BSc Physics', 'teacher',
  'permanent', '01/01/2016', '2023-09-11',
  11000.00, 800.00, 500.00,
  'TIN-006-001', 'PEN-006-001', 'Dashen Bank', '2000001234566', 6, 'active'),
( 7, 'EMP-2025-0007', 'Meron',      'Getahun',   'Zeleke',
  'ሜሮን',            'ጌታሁን',       'ዘለቀ',
  'female', '27/09/1990', '1998-06-06',
  '0913000006', 'meron.g@urjiberischool.com', 2, 'Teacher - Grade 6', 'BEd', 'teacher',
  'contract', '01/01/2019', '2026-09-11',
  8500.00, 800.00, 0.00,
  'TIN-007-001', 'PEN-007-001', 'Abyssinia Bank', '4000001234567', 7, 'active'),
( 8, 'EMP-2025-0008', 'Hailu',      'Negash',    'Berhane',
  'ሐይሉ',            'ነጋሽ',        'ብርሃኔ',
  'male',   '14/12/1979', '1987-08-24',
  '0913000007', 'hailu.n@urjiberischool.com', 2, 'Senior Teacher - Grade 7', 'MSc Mathematics', 'teacher',
  'permanent', '01/01/2012', '2019-09-11',
  15000.00, 800.00, 1500.00,
  'TIN-008-001', 'PEN-008-001', 'Commercial Bank of Ethiopia', '1000001234568', 8, 'active'),
( 9, 'EMP-2025-0009', 'Ayelech',    'Desta',     'Negash',
  'አየለች',           'ደስታ',        'ነጋሽ',
  'female', '03/08/1986', '1994-04-11',
  '0913000008', 'ayelech.d@urjiberischool.com', 2, 'Teacher - Grade 8', 'BSc Biology', 'teacher',
  'permanent', '01/01/2017', '2024-09-11',
  10500.00, 800.00, 500.00,
  'TIN-009-001', 'PEN-009-001', 'Awash Bank', '3000001234569', 9, 'active'),
(10, 'EMP-2025-0010', 'Tekle',      'Berhane',   'Gebre',
  'ተክሌ',            'ብርሃኔ',       'ገብሬ',
  'male',   '22/05/1984', '1991-01-30',
  '0913000009', 'tekle.b@urjiberischool.com', 2, 'Teacher - Grade 9', 'BSc Chemistry', 'teacher',
  'permanent', '01/01/2015', '2022-09-11',
  12500.00, 800.00, 1000.00,
  'TIN-010-001', 'PEN-010-001', 'Dashen Bank', '2000001234570', 10, 'active'),
(11, 'EMP-2025-0011', 'Girma',      'Zeleke',    'Tadesse',
  'ግርማ',            'ዘለቀ',        'ታደሰ',
  'male',   '16/01/1981', '1988-09-25',
  '0913000010', 'girma.z@urjiberischool.com', 2, 'Senior Teacher - Grade 10', 'MSc Physics', 'teacher',
  'permanent', '01/01/2013', '2020-09-11',
  14000.00, 800.00, 1500.00,
  'TIN-011-001', 'PEN-011-001', 'Commercial Bank of Ethiopia', '1000001234571', 11, 'active'),
(12, 'EMP-2025-0012', 'Meseret',    'Tadesse',   'Worku',
  'መሰረት',           'ታደሰ',        'ወርቁ',
  'female', '08/10/1987', '1995-06-15',
  '0913000011', 'meseret.t@urjiberischool.com', 2, 'Teacher - Grade 11', 'BEd English', 'teacher',
  'permanent', '01/01/2018', '2025-09-11',
  9000.00, 800.00, 500.00,
  'TIN-012-001', 'PEN-012-001', 'Abyssinia Bank', '4000001234572', 12, 'active'),
(13, 'EMP-2025-0013', 'Birhanu',    'Alemayehu', 'Gebre',
  'ብርሃኑ',           'አለማየሁ',      'ገብሬ',
  'male',   '30/03/1983', '1990-12-09',
  '0913000012', 'birhanu.al@urjiberischool.com', 2, 'Teacher - Grade 12', 'MSc Biology', 'teacher',
  'permanent', '01/01/2014', '2021-09-11',
  13500.00, 800.00, 1000.00,
  'TIN-013-001', 'PEN-013-001', 'Commercial Bank of Ethiopia', '1000001234573', 13, 'active'),
-- Accountant
(14, 'EMP-2025-0014', 'Abel',       'Worku',     'Ayele',
  'አቤል',            'ወርቁ',        'አየለ',
  'male',   '11/07/1986', '1994-03-19',
  '0913000013', 'abel.w@urjiberischool.com', 3, 'Senior Accountant', 'BA Accounting', 'accountant',
  'permanent', '01/01/2016', '2023-09-11',
  10000.00, 800.00, 800.00,
  'TIN-014-001', 'PEN-014-001', 'Commercial Bank of Ethiopia', '1000001234574', 14, 'active'),
-- Registrar
(15, 'EMP-2025-0015', 'Selamawit',  'Demissie',  'Berhane',
  'ሰላማዊት',          'ደምሴ',        'ብርሃኔ',
  'female', '25/11/1989', '1997-07-31',
  '0913000014', 'selamawit.d@urjiberischool.com', 1, 'Student Registrar', 'BA Management', 'admin',
  'permanent', '01/01/2018', '2025-09-11',
  8000.00, 800.00, 500.00,
  'TIN-015-001', 'PEN-015-001', 'Dashen Bank', '2000001234575', 15, 'active'),
-- Librarian
(16, 'EMP-2025-0016', 'Fantaye',    'Girma',     'Teshome',
  'ፋንታየ',           'ግርማ',        'ተሾመ',
  'female', '07/05/1991', '1998-01-16',
  '0913000015', 'fantaye.g@urjiberischool.com', 4, 'Head Librarian', 'Diploma Library Science', 'librarian',
  'permanent', '01/01/2019', '2026-09-11',
  7000.00, 600.00, 300.00,
  'TIN-016-001', 'PEN-016-001', 'Awash Bank', '3000001234576', 16, 'active'),
-- Security Guard
(17, 'EMP-2025-0017', 'Kebede',     'Teshome',   'Abera',
  'ከበደ',            'ተሾመ',        'አበራ',
  'male',   '19/09/1978', '1986-05-28',
  '0913000016', 'kebede.t@urjiberischool.com', 5, 'Security Guard', NULL, 'support_staff',
  'contract', '01/06/2020', '2028-01-15',
  5000.00, 400.00, 0.00,
  'TIN-017-001', 'PEN-017-001', 'Awash Bank', '3000001234577', 17, 'active'),
-- IT Technician
(18, 'EMP-2025-0018', 'Nahom',      'Fikre',     'Gebre',
  'ናሆም',            'ፍቅሬ',        'ገብሬ',
  'male',   '02/02/1993', '2000-10-13',
  '0913000017', 'nahom.f@urjiberischool.com', 6, 'IT Support Technician', 'BSc Computer Science', 'support_staff',
  'contract', '01/01/2021', '2028-09-11',
  7500.00, 600.00, 500.00,
  'TIN-018-001', 'PEN-018-001', 'Commercial Bank of Ethiopia', '1000001234578', 18, 'active');

-- ── Sample Payroll Period (Meskerem 2018 EC / Sep 2025) ──
INSERT IGNORE INTO `hr_payroll_periods` (`id`, `month_ec`, `year_ec`, `month_name_ec`,
    `month_gregorian`, `year_gregorian`, `start_date`, `end_date`,
    `start_date_ec`, `end_date_ec`, `status`, `generated_by`, `generated_at`) VALUES
(1, 1, 2018, 'Meskerem', 9, 2025, '2025-09-11', '2025-10-10', '01/01/2018', '30/01/2018', 'paid', 1, '2025-10-11 09:00:00'),
(2, 2, 2018, 'Tikimt',   10, 2025, '2025-10-11', '2025-11-09', '01/02/2018', '30/02/2018', 'paid', 1, '2025-11-10 09:00:00'),
(3, 3, 2018, 'Hidar',    11, 2025, '2025-11-10', '2025-12-09', '01/03/2018', '30/03/2018', 'paid', 1, '2025-12-10 09:00:00');

-- ── Sample Payroll Records (3 months × 18 employees = 54 rows) ──
-- Using Ethiopian income tax brackets and 7% employee / 11% employer pension
INSERT IGNORE INTO `hr_payroll_records`
    (`payroll_period_id`, `employee_id`, `working_days`, `days_worked`,
     `basic_salary`, `prorated_salary`, `transport_allowance`, `other_allowance`,
     `gross_salary`, `taxable_income`, `income_tax`, `employee_pension`, `employer_pension`,
     `total_pension`, `total_deductions`, `net_salary`,
     `payment_status`, `payment_method`, `payment_date`, `created_by`)
SELECT
    pp.id,
    e.id,
    30, 30,
    e.basic_salary,
    e.basic_salary,
    e.transport_allowance,
    e.position_allowance + e.other_allowance,
    e.basic_salary + e.transport_allowance + e.position_allowance + e.other_allowance,
    e.basic_salary + e.position_allowance + e.other_allowance,
    -- Approximate Ethiopian income tax (simplified)
    CASE
        WHEN (e.basic_salary + e.position_allowance) <= 600  THEN 0
        WHEN (e.basic_salary + e.position_allowance) <= 1650 THEN (e.basic_salary + e.position_allowance - 600) * 0.10
        WHEN (e.basic_salary + e.position_allowance) <= 3200 THEN 105 + (e.basic_salary + e.position_allowance - 1650) * 0.15
        WHEN (e.basic_salary + e.position_allowance) <= 5250 THEN 337.50 + (e.basic_salary + e.position_allowance - 3200) * 0.20
        WHEN (e.basic_salary + e.position_allowance) <= 7800 THEN 747.50 + (e.basic_salary + e.position_allowance - 5250) * 0.25
        WHEN (e.basic_salary + e.position_allowance) <= 10900 THEN 1385.00 + (e.basic_salary + e.position_allowance - 7800) * 0.30
        ELSE 2315.00 + (e.basic_salary + e.position_allowance - 10900) * 0.35
    END,
    ROUND(e.basic_salary * 0.07, 2),
    ROUND(e.basic_salary * 0.11, 2),
    ROUND(e.basic_salary * 0.18, 2),
    -- total_deductions = income_tax + employee_pension + other_deductions
    CASE
        WHEN (e.basic_salary + e.position_allowance) <= 600  THEN 0
        WHEN (e.basic_salary + e.position_allowance) <= 1650 THEN (e.basic_salary + e.position_allowance - 600) * 0.10
        WHEN (e.basic_salary + e.position_allowance) <= 3200 THEN 105 + (e.basic_salary + e.position_allowance - 1650) * 0.15
        WHEN (e.basic_salary + e.position_allowance) <= 5250 THEN 337.50 + (e.basic_salary + e.position_allowance - 3200) * 0.20
        WHEN (e.basic_salary + e.position_allowance) <= 7800 THEN 747.50 + (e.basic_salary + e.position_allowance - 5250) * 0.25
        WHEN (e.basic_salary + e.position_allowance) <= 10900 THEN 1385.00 + (e.basic_salary + e.position_allowance - 7800) * 0.30
        ELSE 2315.00 + (e.basic_salary + e.position_allowance - 10900) * 0.35
    END + ROUND(e.basic_salary * 0.07, 2) + e.other_deductions,
    -- net_salary = gross - total_deductions
    (e.basic_salary + e.transport_allowance + e.position_allowance + e.other_allowance)
    - (
        CASE
            WHEN (e.basic_salary + e.position_allowance) <= 600  THEN 0
            WHEN (e.basic_salary + e.position_allowance) <= 1650 THEN (e.basic_salary + e.position_allowance - 600) * 0.10
            WHEN (e.basic_salary + e.position_allowance) <= 3200 THEN 105 + (e.basic_salary + e.position_allowance - 1650) * 0.15
            WHEN (e.basic_salary + e.position_allowance) <= 5250 THEN 337.50 + (e.basic_salary + e.position_allowance - 3200) * 0.20
            WHEN (e.basic_salary + e.position_allowance) <= 7800 THEN 747.50 + (e.basic_salary + e.position_allowance - 5250) * 0.25
            WHEN (e.basic_salary + e.position_allowance) <= 10900 THEN 1385.00 + (e.basic_salary + e.position_allowance - 7800) * 0.30
            ELSE 2315.00 + (e.basic_salary + e.position_allowance - 10900) * 0.35
        END + ROUND(e.basic_salary * 0.07, 2) + e.other_deductions
    ),
    'paid', 'bank_transfer', pp.end_date, 1
FROM `hr_payroll_periods` pp
CROSS JOIN `hr_employees` e
WHERE pp.id <= 3 AND e.status = 'active';


-- ############################################################
--        BULK DATA GENERATION (Students, Results, etc.)
-- ############################################################

-- ── Helper: numbers 1-300 ──
CREATE TEMPORARY TABLE IF NOT EXISTS `tmp_nums` (`n` INT PRIMARY KEY);
INSERT IGNORE INTO `tmp_nums` (`n`)
WITH RECURSIVE r AS (SELECT 1 AS n UNION ALL SELECT n+1 FROM r WHERE n < 300)
SELECT n FROM r;

-- ── Helper: weekday dates Sep 1 – Nov 15, 2025 (~55 days) ──
CREATE TEMPORARY TABLE IF NOT EXISTS `tmp_dates` (`d` DATE PRIMARY KEY);
INSERT IGNORE INTO `tmp_dates` (`d`)
WITH RECURSIVE r AS (SELECT DATE('2025-09-01') AS d UNION ALL SELECT DATE_ADD(d, INTERVAL 1 DAY) FROM r WHERE d < '2025-11-15')
SELECT d FROM r WHERE DAYOFWEEK(d) NOT IN (1, 7);

-- ============================================================
-- SECTION 15: 300 STUDENTS
-- ============================================================
-- 25 students per grade (300 / 12 = 25)
-- 13 in section A, 12 in section B per grade
-- Odd n = male, Even n = female
INSERT IGNORE INTO `students` (`id`, `admission_no`, `first_name`, `last_name`, `gender`,
    `date_of_birth`, `nationality`, `phone`, `admission_date`, `status`)
SELECT
    n,
    CONCAT('UBS/2025/', LPAD(n, 3, '0')),
    IF(MOD(n,2)=1,
        ELT(MOD(n-1,25)+1,
            'Abebe','Kebede','Tadesse','Girma','Tesfaye',
            'Dawit','Yohannes','Biruk','Henok','Abel',
            'Samuel','Daniel','Solomon','Nahom','Eyob',
            'Yared','Bereket','Robel','Natnael','Fitsum',
            'Biniam','Tewodros','Ephrem','Ermias','Kaleb'),
        ELT(MOD(n-1,25)+1,
            'Tigist','Meron','Hanna','Sara','Ruth',
            'Bethlehem','Selam','Kidist','Mahlet','Feven',
            'Liya','Rahel','Tsion','Yordanos','Aida',
            'Helen','Hiwot','Eden','Samrawit','Abeba',
            'Meseret','Aster','Seble','Bilen','Nardos')
    ),
    ELT(MOD(n-1,25)+1,
        'Tekle','Gebre','Haile','Bogale','Worku',
        'Tadesse','Mekonnen','Bekele','Zeleke','Ayele',
        'Desta','Negash','Alemayehu','Wolde','Assefa',
        'Berhane','Demissie','Getahun','Kebede','Abera',
        'Teshome','Girma','Mulugeta','Tesfaye','Fikre'),
    IF(MOD(n,2)=1, 'male', 'female'),
    DATE(CONCAT(
        2019 - FLOOR((n-1)/25),
        '-', LPAD(MOD(n-1,12)+1, 2, '0'),
        '-', LPAD(MOD(n*3,28)+1, 2, '0')
    )),
    'Ethiopian',
    CONCAT('09', LPAD(12000000+n, 8, '0')),
    '2025-09-01',
    'active'
FROM `tmp_nums`;

-- ============================================================
-- SECTION 16: 300 GUARDIANS (1 per student)
-- ============================================================
INSERT IGNORE INTO `guardians` (`id`, `first_name`, `last_name`, `relation`, `phone`,
    `is_emergency_contact`)
SELECT
    n,
    IF(MOD(n,2)=1,
        ELT(MOD(n-1,25)+1,
            'Getachew','Mulugeta','Hailu','Dereje','Birhanu',
            'Alemayehu','Mesfin','Yilma','Fekadu','Zerihun',
            'Teshome','Worku','Gebremedhin','Petros','Assefa',
            'Berhane','Sisay','Tesfaye','Asfaw','Moges',
            'Desalegn','Kefale','Legesse','Debebe','Negussie'),
        ELT(MOD(n-1,25)+1,
            'Almaz','Ayelech','Worknesh','Tsedale','Aberash',
            'Etaferahu','Genet','Lakech','Tiruwork','Fantaye',
            'Tejitu','Zenebech','Belaynesh','Askale','Dinkinesh',
            'Yetemwork','Nigist','Mulunesh','Abebech','Tsehay',
            'Alemnesh','Emawayish','Aregash','Etenesh','Hirut')
    ),
    ELT(MOD(n-1,25)+1,
        'Tekle','Gebre','Haile','Bogale','Worku',
        'Tadesse','Mekonnen','Bekele','Zeleke','Ayele',
        'Desta','Negash','Alemayehu','Wolde','Assefa',
        'Berhane','Demissie','Getahun','Kebede','Abera',
        'Teshome','Girma','Mulugeta','Tesfaye','Fikre'),
    IF(MOD(n,2)=1, 'father', 'mother'),
    CONCAT('09', LPAD(11000000+n, 8, '0')),
    1
FROM `tmp_nums`;

-- ============================================================
-- SECTION 17: STUDENT ↔ GUARDIAN LINKS
-- ============================================================
INSERT IGNORE INTO `student_guardians` (`student_id`, `guardian_id`, `is_primary`)
SELECT n, n, 1 FROM `tmp_nums`;

-- ============================================================
-- SECTION 18: ENROLLMENTS (300 students → session 1)
-- ============================================================
INSERT IGNORE INTO `enrollments` (`student_id`, `session_id`, `class_id`, `section_id`,
    `roll_no`, `status`, `enrolled_at`)
SELECT
    n, 1,
    FLOOR((n-1)/25) + 1,
    FLOOR((n-1)/25) * 2 + IF(MOD(n-1,25) < 13, 1, 2),
    LPAD(IF(MOD(n-1,25) < 13, MOD(n-1,25)+1, MOD(n-1,25)-12), 2, '0'),
    'active',
    '2025-09-01'
FROM `tmp_nums`;

-- ============================================================
-- SECTION 19: TERM 1 ASSESSMENTS (1 midterm per class-subject)
-- ============================================================
INSERT IGNORE INTO `assessments` (`name`, `class_id`, `subject_id`, `session_id`,
    `term_id`, `total_marks`, `created_by`)
SELECT
    CONCAT('Term 1 Midterm - ', s.name, ' - ', c.name),
    cs.class_id, cs.subject_id, 1, 1, 100.00, 1
FROM `class_subjects` cs
JOIN `classes` c ON c.id = cs.class_id
JOIN `subjects` s ON s.id = cs.subject_id
WHERE cs.session_id = 1;

-- ============================================================
-- SECTION 20: STUDENT RESULTS (300 × 10 = 3,000 rows)
-- ============================================================
INSERT IGNORE INTO `student_results` (`assessment_id`, `student_id`, `class_id`,
    `section_id`, `marks_obtained`, `is_absent`, `entered_by`)
SELECT
    sub.aid, sub.sid, sub.cid, sub.secid,
    IF(sub.r < 0.03, NULL, FLOOR(35 + RAND() * 66)),
    IF(sub.r < 0.03, 1, 0),
    1
FROM (
    SELECT a.id AS aid, e.student_id AS sid, e.class_id AS cid,
           e.section_id AS secid, RAND() AS r
    FROM `assessments` a
    JOIN `enrollments` e ON e.class_id = a.class_id AND e.session_id = 1
    WHERE a.session_id = 1 AND a.term_id = 1
) sub;

-- ============================================================
-- SECTION 21: ATTENDANCE — 3 months (Sep 1 – Nov 15, 2025)
-- ============================================================
INSERT IGNORE INTO `attendance` (`student_id`, `class_id`, `section_id`, `session_id`,
    `term_id`, `date`, `status`, `marked_by`)
SELECT
    e.student_id, e.class_id, e.section_id, 1, 1, td.d,
    ELT(1 + FLOOR(RAND() * 20),
        'present','present','present','present','present',
        'present','present','present','present','present',
        'present','present','present','present','present',
        'present','present','late','late','absent'),
    e.class_id + 1
FROM `enrollments` e
CROSS JOIN `tmp_dates` td
WHERE e.session_id = 1;

-- ============================================================
-- CLEANUP
-- ============================================================
DROP TEMPORARY TABLE IF EXISTS `tmp_nums`;
DROP TEMPORARY TABLE IF EXISTS `tmp_dates`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- seed.sql complete — Summary:
-- ============================================================
--   7 roles, 94 permissions (77 core + 12 HR + 5 messaging)
--   Full role-permission matrix with HR & messaging grants
--   3 mediums, 3 streams, 3 shifts
--   18 users: 1 admin, 12 teachers, 1 accountant, 1 registrar,
--             1 librarian, 1 guard, 1 IT technician
--   1 academic session (2025/2026) with 4 terms
--   12 classes × 2 sections = 24 sections
--   14 subjects, 10 assigned per class = 120 class-subject mappings
--   12 class-teacher assignments
--   Ethiopian Standard grade scale (11 entries)
--   39+ system settings, 3 announcements
--   6 HR departments, 18 HR employees (linked to users)
--   8 leave types, 13 Ethiopian public holidays
--   3 payroll periods (Sep-Nov 2025), 54 payroll records
--   300 students, 300 guardians, 300 enrollments
--   120 assessments (Term 1), ~3,000 student results
--   ~16,500 attendance records (Sep-Nov 2025)
-- ============================================================
