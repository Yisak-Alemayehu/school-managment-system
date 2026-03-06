-- ============================================================
-- Urji Beri School SMS â€” COMPLETE SEED DATA (Full)
-- Production-Ready | 300 Students | Term 1 Results
-- 3 Months Attendance | 3 Monthly Payments Paid
-- 10 Subjects per Class | Run AFTER db.sql
-- Generated: 2026-02-27
-- ============================================================
-- NOTE: Requires MySQL 8.0+ (uses recursive CTEs)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET @now = NOW();

-- ============================================================
-- SECTION 1: ROLES
-- ============================================================
INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `is_system`) VALUES
(1, 'Super Admin',   'super_admin',   'Full system access â€” all modules, all operations',             1),
(2, 'School Admin',  'school_admin',  'Manages school operations, staff, and academic settings',      1),
(3, 'Teacher',       'teacher',       'Manages classes, assignments, grades, and attendance',          0),
(4, 'Student',       'student',       'Views own academic info, grades, fees, and attendance',         0),
(5, 'Parent',        'parent',        'Views child academic info, fees, and communicates with school', 0),
(6, 'Accountant',    'accountant',    'School accountant role',          0),
(7, 'Registrar',     'registrar',     'Manages student admissions, enrollments, and records',          0);

-- ============================================================
-- SECTION 2: PERMISSIONS (74 total)
-- ============================================================
INSERT INTO `permissions` (`id`, `module`, `action`, `description`) VALUES
( 1, 'dashboard',      'view',            'View dashboard overview'),
( 2, 'users',          'list',            'View users list'),
( 3, 'users',          'create',          'Create a new user'),
( 4, 'users',          'edit',            'Edit any user'),
( 5, 'users',          'delete',          'Delete a user'),
( 6, 'users',          'view',            'View user details'),
( 7, 'academics',      'list',            'View academic configuration'),
( 8, 'academics',      'create',          'Create classes, sections, subjects'),
( 9, 'academics',      'edit',            'Edit academic configuration'),
(10, 'academics',      'delete',          'Delete academic data'),
(11, 'academics',      'view',            'View academic detail'),
(12, 'students',       'list',            'View students list'),
(13, 'students',       'create',          'Register a new student'),
(14, 'students',       'edit',            'Edit student information'),
(15, 'students',       'delete',          'Delete a student record'),
(16, 'students',       'view',            'View individual student details'),
(17, 'students',       'import',          'Bulk-import students from CSV'),
(18, 'students',       'export',          'Export student data'),
(19, 'attendance',     'list',            'View attendance records'),
(20, 'attendance',     'mark',            'Mark daily attendance'),
(21, 'attendance',     'edit',            'Edit attendance records'),
(22, 'attendance',     'report',          'View attendance reports'),
(23, 'attendance',     'view',            'View attendance details'),
(24, 'exams',          'list',            'View exams list'),
(25, 'exams',          'create',          'Create exams and schedules'),
(26, 'exams',          'edit',            'Edit exam details'),
(27, 'exams',          'delete',          'Delete an exam'),
(28, 'exams',          'view',            'View exam details'),
(29, 'exams',          'marks_entry',     'Enter student marks'),
(30, 'exams',          'report_cards',    'Generate / view report cards'),
(38, 'communication',  'announcements',   'Manage announcements'),
(39, 'communication',  'messages',        'Send and read messages'),
(40, 'communication',  'view',            'View communications'),
(41, 'settings',       'view',            'View system settings'),
(42, 'settings',       'edit',            'Modify system settings'),
(43, 'roles',          'list',            'View roles list'),
(44, 'roles',          'create',          'Create a new role'),
(45, 'roles',          'edit',            'Edit a role'),
(46, 'roles',          'delete',          'Delete a role'),
(47, 'reports',        'academic',        'View academic reports'),
(48, 'reports',        'financial',       'View financial reports'),
(49, 'reports',        'attendance',      'View attendance summary reports'),
(50, 'profile',        'view',            'View own profile'),
(51, 'profile',        'edit',            'Edit own profile'),
(52, 'profile',        'change_password', 'Change own password'),
(54, 'timetable',      'list',            'View timetable list'),
(55, 'timetable',      'create',          'Create timetable entries'),
(56, 'timetable',      'edit',            'Edit timetable entries'),
(57, 'timetable',      'delete',          'Delete timetable entries'),
(58, 'timetable',      'view',            'View timetable detail'),
(59, 'assignment',     'view',            'View a single assignment'),
(60, 'exam',           'view',            'View a single exam'),
(61, 'marks',          'view',            'View marks detail'),
(62, 'report_card',    'view',            'View a single report card'),
-- Manage-level permissions (used by routes)
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
(75, 'finance',        'view',            'View finance data'),
(76, 'finance',        'manage',          'Manage fees and payments'),
(77, 'finance',        'reports',         'View financial reports');
-- ============================================================
-- SECTION 3: ROLE â†’ PERMISSION MAPPING
-- ============================================================
-- Super Admin (role 1) â€” ALL permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`;

-- School Admin (role 2) â€” all except role create/delete & gateway manage
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, `id` FROM `permissions` WHERE `id` NOT IN (44, 46);

-- Teacher (role 3)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(3,1),(3,7),(3,11),(3,12),(3,16),(3,19),(3,20),(3,21),(3,22),(3,23),
(3,24),(3,25),(3,26),(3,28),(3,29),(3,30),(3,38),(3,39),(3,40),
(3,47),(3,49),(3,50),(3,51),(3,52),(3,54),(3,58),(3,59),(3,60),(3,61),(3,62),
(3,63),(3,65),(3,66),(3,67),(3,68),(3,69),(3,73);

-- Student (role 4)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(4,1),(4,7),(4,11),(4,19),(4,23),(4,24),(4,28),(4,30),
(4,40),(4,50),(4,51),(4,52),(4,54),(4,58),(4,59),(4,60),(4,61),(4,62);

-- Parent (role 5)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(5,1),(5,7),(5,11),(5,12),(5,16),(5,19),(5,23),(5,24),(5,28),(5,30),
(5,39),(5,40),(5,50),(5,51),(5,52),(5,59),(5,60),(5,61),(5,62),(5,75);

-- Registrar (role 7)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(7,1),(7,7),(7,8),(7,9),(7,11),(7,12),(7,13),(7,14),(7,15),(7,16),
(7,17),(7,18),(7,19),(7,22),(7,23),(7,38),(7,39),(7,40),(7,47),(7,50),(7,51),(7,52),(7,70),(7,73);

-- Accountant (role 6)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(6,1),(6,7),(6,11),(6,12),(6,16),(6,19),(6,23),(6,39),(6,40),
(6,50),(6,51),(6,52),(6,75),(6,76),(6,77);

-- ============================================================
-- SECTION 4: MEDIUMS, STREAMS, SHIFTS
-- ============================================================
INSERT INTO `mediums` (`id`, `name`, `is_active`, `sort_order`) VALUES
(1, 'English Medium',    1, 1),
(2, 'Afan Oromo Medium', 1, 2),
(3, 'Amharic Medium',    1, 3);

INSERT INTO `streams` (`id`, `name`, `description`, `is_active`, `sort_order`) VALUES
(1, 'Natural Science', 'For Grades 9-12: Physics, Chemistry, Biology',        1, 1),
(2, 'Social Science',  'For Grades 9-12: History, Geography, Economics',       1, 2),
(3, 'General',         'For Grades 1-8: All general education subjects apply', 1, 3);

INSERT INTO `shifts` (`id`, `name`, `start_time`, `end_time`, `is_active`, `sort_order`) VALUES
(1, 'Morning Shift',   '08:00:00', '12:30:00', 1, 1),
(2, 'Afternoon Shift', '13:00:00', '17:30:00', 1, 2),
(3, 'Full Day',        '08:00:00', '17:30:00', 1, 3);

-- ============================================================
-- SECTION 5: USERS â€” Admin (1) + 12 Teachers (2-13)
-- ============================================================
-- Admin password: Admin@123   |   Teacher password: Teacher@123
INSERT INTO `users` (`id`,`username`,`email`,`password_hash`,`full_name`,`first_name`,`last_name`,`phone`,`is_active`,`status`,`force_password_change`) VALUES
( 1, 'admin',     'admin@urjiberischool.com',      '$2y$12$LJ3m4yS6YE5Ks0FMwRNsNuXBeqJKlC9UVPlyuFJV5kBO2.WMfyRrm', 'System Administrator',  'System',    'Administrator', '0912000000', 1, 'active', 0),
( 2, 'teacher1',  'tadesse.m@urjiberischool.com',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tadesse Mekonnen',      'Tadesse',   'Mekonnen',      '0913000001', 1, 'active', 1),
( 3, 'teacher2',  'almaz.b@urjiberischool.com',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Almaz Bekele',          'Almaz',     'Bekele',        '0913000002', 1, 'active', 1),
( 4, 'teacher3',  'dereje.h@urjiberischool.com',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dereje Haile',          'Dereje',    'Haile',         '0913000003', 1, 'active', 1),
( 5, 'teacher4',  'tigist.w@urjiberischool.com',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tigist Worku',          'Tigist',    'Worku',         '0913000004', 1, 'active', 1),
( 6, 'teacher5',  'mulugeta.a@urjiberischool.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mulugeta Assefa',       'Mulugeta',  'Assefa',        '0913000005', 1, 'active', 1),
( 7, 'teacher6',  'meron.g@urjiberischool.com',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Meron Getahun',         'Meron',     'Getahun',       '0913000006', 1, 'active', 1),
( 8, 'teacher7',  'hailu.n@urjiberischool.com',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hailu Negash',          'Hailu',     'Negash',        '0913000007', 1, 'active', 1),
( 9, 'teacher8',  'ayelech.d@urjiberischool.com',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ayelech Desta',         'Ayelech',   'Desta',         '0913000008', 1, 'active', 1),
(10, 'teacher9',  'tekle.b@urjiberischool.com',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tekle Berhane',         'Tekle',     'Berhane',       '0913000009', 1, 'active', 1),
(11, 'teacher10', 'girma.z@urjiberischool.com',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Girma Zeleke',          'Girma',     'Zeleke',        '0913000010', 1, 'active', 1),
(12, 'teacher11', 'meseret.t@urjiberischool.com',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Meseret Tadesse',       'Meseret',   'Tadesse',       '0913000011', 1, 'active', 1),
(13, 'teacher12', 'birhanu.al@urjiberischool.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Birhanu Alemayehu',     'Birhanu',   'Alemayehu',     '0913000012', 1, 'active', 1);

-- Assign roles: admin=super_admin, teachers=teacher role
INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(1,1),(2,3),(3,3),(4,3),(5,3),(6,3),(7,3),(8,3),(9,3),(10,3),(11,3),(12,3),(13,3);

-- ============================================================
-- SECTION 6: ACADEMIC SESSION & TERMS
-- ============================================================
INSERT INTO `academic_sessions` (`id`, `name`, `slug`, `start_date`, `end_date`, `is_active`) VALUES
(1, '2025/2026 Academic Year', '2025-2026', '2025-09-01', '2026-06-30', 1);

INSERT INTO `terms` (`id`, `session_id`, `name`, `slug`, `start_date`, `end_date`, `is_active`, `sort_order`) VALUES
(1, 1, 'Term 1', 'term-1-2025-2026', '2025-09-01', '2025-11-15', 0, 1),
(2, 1, 'Term 2', 'term-2-2025-2026', '2025-11-16', '2026-01-31', 0, 2),
(3, 1, 'Term 3', 'term-3-2025-2026', '2026-02-01', '2026-04-15', 1, 3),
(4, 1, 'Term 4', 'term-4-2025-2026', '2026-04-16', '2026-06-30', 0, 4);

-- ============================================================
-- SECTION 7: CLASSES & SECTIONS (Grade 1-12, A & B = 24)
-- ============================================================
INSERT INTO `classes` (`id`,`name`,`slug`,`numeric_name`,`description`,`medium_id`,`stream_id`,`shift_id`,`sort_order`,`is_active`) VALUES
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

INSERT INTO `sections` (`id`, `class_id`, `name`, `capacity`, `is_active`) VALUES
( 1,  1,'A',40,1),( 2,  1,'B',40,1),( 3,  2,'A',40,1),( 4,  2,'B',40,1),
( 5,  3,'A',40,1),( 6,  3,'B',40,1),( 7,  4,'A',40,1),( 8,  4,'B',40,1),
( 9,  5,'A',40,1),(10,  5,'B',40,1),(11,  6,'A',40,1),(12,  6,'B',40,1),
(13,  7,'A',45,1),(14,  7,'B',45,1),(15,  8,'A',45,1),(16,  8,'B',45,1),
(17,  9,'A',45,1),(18,  9,'B',45,1),(19, 10,'A',45,1),(20, 10,'B',45,1),
(21, 11,'A',45,1),(22, 11,'B',45,1),(23, 12,'A',45,1),(24, 12,'B',45,1);

-- ============================================================
-- SECTION 8: SUBJECTS (14 â€” ensures 10 per class)
-- ============================================================
INSERT INTO `subjects` (`id`,`name`,`code`,`description`,`type`,`is_active`) VALUES
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

-- Core languages & math (1,2,3,4) â†’ all 12 grades
INSERT INTO `class_subjects` (`class_id`,`subject_id`,`session_id`,`is_elective`)
SELECT c.id, s.id, 1, 0 FROM `classes` c CROSS JOIN `subjects` s WHERE s.id IN (1,2,3,4);

-- Geography & History (8,9) â†’ all 12 grades
INSERT INTO `class_subjects` (`class_id`,`subject_id`,`session_id`,`is_elective`)
SELECT c.id, s.id, 1, 0 FROM `classes` c CROSS JOIN `subjects` s WHERE s.id IN (8,9);

-- Physical Education (11) â†’ grades 1-6
INSERT INTO `class_subjects` (`class_id`,`subject_id`,`session_id`,`is_elective`)
SELECT c.id, 11, 1, 0 FROM `classes` c WHERE c.numeric_name <= 6;

-- Art & Music (12) â†’ grades 1-4
INSERT INTO `class_subjects` (`class_id`,`subject_id`,`session_id`,`is_elective`)
SELECT c.id, 12, 1, 0 FROM `classes` c WHERE c.numeric_name <= 4;

-- Civic Education (13) â†’ grades 1-6
INSERT INTO `class_subjects` (`class_id`,`subject_id`,`session_id`,`is_elective`)
SELECT c.id, 13, 1, 0 FROM `classes` c WHERE c.numeric_name <= 6;

-- Environmental Science (14) â†’ grades 1-6
INSERT INTO `class_subjects` (`class_id`,`subject_id`,`session_id`,`is_elective`)
SELECT c.id, 14, 1, 0 FROM `classes` c WHERE c.numeric_name <= 6;

-- ICT (10) â†’ grades 5-12
INSERT INTO `class_subjects` (`class_id`,`subject_id`,`session_id`,`is_elective`)
SELECT c.id, 10, 1, 0 FROM `classes` c WHERE c.numeric_name >= 5;

-- Physics, Chemistry, Biology (5,6,7) â†’ grades 7-12
INSERT INTO `class_subjects` (`class_id`,`subject_id`,`session_id`,`is_elective`)
SELECT c.id, s.id, 1, 0 FROM `classes` c CROSS JOIN `subjects` s
WHERE c.numeric_name >= 7 AND s.id IN (5,6,7);

-- ============================================================
-- SECTION 10: CLASS TEACHERS (1 teacher per grade)
-- ============================================================
-- Teacher user_id = class_id + 1 (user 2 â†’ grade 1, user 13 â†’ grade 12)
INSERT INTO `class_teachers` (`class_id`,`teacher_id`,`session_id`,`is_class_teacher`) VALUES
(1,2,1,1),(2,3,1,1),(3,4,1,1),(4,5,1,1),(5,6,1,1),(6,7,1,1),
(7,8,1,1),(8,9,1,1),(9,10,1,1),(10,11,1,1),(11,12,1,1),(12,13,1,1);

-- ============================================================
-- SECTION 11: GRADE SCALE (Ethiopian Standard)
-- ============================================================
INSERT INTO `grade_scales` (`id`, `name`, `is_default`) VALUES (1, 'Ethiopian Standard', 1);

INSERT INTO `grade_scale_entries` (`grade_scale_id`,`grade`,`min_percentage`,`max_percentage`,`grade_point`,`remark`) VALUES
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
-- SECTION 15: SYSTEM SETTINGS (39 entries)
-- ============================================================
INSERT INTO `settings` (`setting_group`,`setting_key`,`setting_value`,`setting_type`,`description`,`is_public`) VALUES
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
('sms','sender_id', 'UrjiBeri',                              'string', 'SMS sender ID',                 0),
);
-- ============================================================
-- SECTION 16: ANNOUNCEMENTS
-- ============================================================
INSERT INTO `announcements` (`title`,`content`,`type`,`target_roles`,`is_pinned`,`status`,`published_at`,`created_by`) VALUES
('Welcome to 2025/2026 Academic Year',
 'We are pleased to welcome all students, parents, and staff to the new academic year. Classes begin September 1, 2025.',
 'general',NULL,1,'published',@now,1),
('First Term Examination Schedule',
 'First term examinations were held November 1-15, 2025. Results are now available.',
 'academic','teacher,student,parent',0,'published',@now,1),
('Parent-Teacher Meeting',
 'A parent-teacher meeting is scheduled for February 28, 2026. All parents are kindly requested to attend.',
 'event','parent,teacher',0,'published',@now,1);

-- ============================================================
-- ============================================================
--        BULK DATA GENERATION (Students, Results, etc.)
-- ============================================================
-- ============================================================

-- â”€â”€ Helper: numbers 1-300 â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
CREATE TEMPORARY TABLE IF NOT EXISTS `tmp_nums` (`n` INT PRIMARY KEY);
INSERT INTO `tmp_nums` (`n`)
WITH RECURSIVE r AS (SELECT 1 AS n UNION ALL SELECT n+1 FROM r WHERE n < 300)
SELECT n FROM r;

-- â”€â”€ Helper: weekday dates Sep 1 â€“ Nov 15, 2025 (â‰ˆ55 days) â”€â”€
CREATE TEMPORARY TABLE IF NOT EXISTS `tmp_dates` (`d` DATE PRIMARY KEY);
INSERT INTO `tmp_dates` (`d`)
WITH RECURSIVE r AS (SELECT DATE('2025-09-01') AS d UNION ALL SELECT DATE_ADD(d, INTERVAL 1 DAY) FROM r WHERE d < '2025-11-15')
SELECT d FROM r WHERE DAYOFWEEK(d) NOT IN (1, 7);

-- ============================================================
-- SECTION 17: 300 STUDENTS
-- ============================================================
-- 25 students per grade (300 / 12 = 25)
-- 13 in section A, 12 in section B per grade
-- Odd n = male, Even n = female
INSERT INTO `students` (`id`, `admission_no`, `first_name`, `last_name`, `gender`,
    `date_of_birth`, `nationality`, `phone`, `admission_date`, `status`)
SELECT
    n,
    CONCAT('UBS/2025/', LPAD(n, 3, '0')),
    -- First name: male or female based on odd/even
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
    -- Last name
    ELT(MOD(n-1,25)+1,
        'Tekle','Gebre','Haile','Bogale','Worku',
        'Tadesse','Mekonnen','Bekele','Zeleke','Ayele',
        'Desta','Negash','Alemayehu','Wolde','Assefa',
        'Berhane','Demissie','Getahun','Kebede','Abera',
        'Teshome','Girma','Mulugeta','Tesfaye','Fikre'),
    IF(MOD(n,2)=1, 'male', 'female'),
    -- DOB: age-appropriate (Grade 1 â†’ born 2019, Grade 12 â†’ born 2008)
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
-- SECTION 18: 300 GUARDIANS (1 per student)
-- ============================================================
INSERT INTO `guardians` (`id`, `first_name`, `last_name`, `relation`, `phone`,
    `is_emergency_contact`)
SELECT
    n,
    -- Guardian first name
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
    -- Same family name as student
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
-- SECTION 19: STUDENT â†” GUARDIAN LINKS
-- ============================================================
INSERT INTO `student_guardians` (`student_id`, `guardian_id`, `is_primary`)
SELECT n, n, 1 FROM `tmp_nums`;

-- ============================================================
-- SECTION 20: ENROLLMENTS (300 students â†’ session 1)
-- ============================================================
INSERT INTO `enrollments` (`student_id`, `session_id`, `class_id`, `section_id`,
    `roll_no`, `status`, `enrolled_at`)
SELECT
    n, 1,
    -- class_id: 25 students per grade
    FLOOR((n-1)/25) + 1,
    -- section_id: first 13 â†’ section A, remainder â†’ section B
    FLOOR((n-1)/25) * 2 + IF(MOD(n-1,25) < 13, 1, 2),
    -- roll_no within section
    LPAD(IF(MOD(n-1,25) < 13, MOD(n-1,25)+1, MOD(n-1,25)-12), 2, '0'),
    'active',
    '2025-09-01'
FROM `tmp_nums`;

-- ============================================================
-- SECTION 21: TERM 1 ASSESSMENTS (1 midterm per class-subject)
-- ============================================================
-- Creates 120 assessments (12 classes Ã— 10 subjects each)
INSERT INTO `assessments` (`name`, `class_id`, `subject_id`, `session_id`,
    `term_id`, `total_marks`, `created_by`)
SELECT
    CONCAT('Term 1 Midterm - ', s.name, ' - ', c.name),
    cs.class_id, cs.subject_id, 1, 1, 100.00, 1
FROM `class_subjects` cs
JOIN `classes` c ON c.id = cs.class_id
JOIN `subjects` s ON s.id = cs.subject_id
WHERE cs.session_id = 1;

-- ============================================================
-- SECTION 22: STUDENT RESULTS (300 Ã— 10 = 3,000 rows)
-- ============================================================
-- Each student gets marks 35-100 for all 10 of their class's subjects
-- ~3% of students marked absent per assessment (NULL marks)
INSERT INTO `student_results` (`assessment_id`, `student_id`, `class_id`,
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
-- SECTION 23: ATTENDANCE â€” 3 months (Sep 1 â€“ Nov 15, 2025)
-- ============================================================
-- ~55 weekdays Ã— 300 students = ~16,500 rows
-- Distribution: ~85% present, ~10% late, ~5% absent
INSERT INTO `attendance` (`student_id`, `class_id`, `section_id`, `session_id`,
    `term_id`, `date`, `status`, `marked_by`)
SELECT
    e.student_id, e.class_id, e.section_id, 1, 1, td.d,
    ELT(1 + FLOOR(RAND() * 20),
        'present','present','present','present','present',
        'present','present','present','present','present',
        'present','present','present','present','present',
        'present','present','late','late','absent'),
    -- marked_by = teacher for that grade (user_id = class_id + 1)
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
-- seed.sql complete â€” Summary of seeded data:
-- ============================================================
--   7 roles, 74 permissions, full role-permission matrix
--   3 mediums, 3 streams, 3 shifts
--   13 users: 1 admin + 12 teachers (1 per grade)
--   1 academic session (2025/2026) with 4 terms
--   12 classes Ã— 2 sections = 24 sections
--   14 subjects, 10 assigned per class = 120 class-subject mappings
--   12 class-teacher assignments
--   Ethiopian Standard grade scale (11 entries)
--   39+ system settings
--   3 announcements
--   300 students with 300 guardians and enrollments
--   120 assessments (Term 1 Midterm, 10 per class)
--   3,000 student results (marks 35-100, ~3% absent)
--   ~16,500 attendance records (Sep-Nov 2025, 85% present)
-- ============================================================


