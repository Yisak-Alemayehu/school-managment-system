-- ============================================================
-- SMS — MINIMAL SEED DATA FOR A NEW SCHOOL
-- Run AFTER db.sql | Sets up essential system data only
-- No students, no teachers, no sample data — just the skeleton
-- ============================================================
-- Contents:
--   7 roles, 94 permissions, full role-permission matrix
--   1 super-admin user (admin / password)
--   3 mediums, 3 streams, 3 shifts
--   1 academic session with 4 terms (placeholder)
--   Ethiopian Standard grade scale (11 entries)
--   System settings (school info placeholders)
--   6 HR departments, 8 leave types, 13 public holidays
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
(6,78),(6,84),(6,86),(6,88),
(6,90),(6,93);

-- Registrar (role 7)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(7,1),(7,7),(7,8),(7,9),(7,11),(7,12),(7,13),(7,14),(7,15),(7,16),
(7,17),(7,18),(7,19),(7,22),(7,23),(7,38),(7,39),(7,40),(7,47),(7,50),(7,51),(7,52),(7,70),(7,73),
(7,90),(7,93);

-- ============================================================
-- SECTION 5: ADMIN USER
-- ============================================================
-- Default password: password
-- IMPORTANT: Change this immediately after first login!
INSERT IGNORE INTO `users` (`id`,`username`,`email`,`password_hash`,`full_name`,`first_name`,`last_name`,`phone`,`is_active`,`status`,`force_password_change`) VALUES
(1, 'admin', 'admin@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'System', 'Administrator', '0900000000', 1, 'active', 1);

INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`) VALUES (1, 1);


-- ============================================================
-- SECTION 7: GRADE SCALE (Ethiopian Standard)
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
-- SECTION 8: SYSTEM SETTINGS
-- ============================================================
-- Update these values to match your school's information
INSERT IGNORE INTO `settings` (`setting_group`,`setting_key`,`setting_value`,`setting_type`,`description`,`is_public`) VALUES
('school','school_name',      'Urji Beri School',                   'string', 'Official school name',          1),
('school','school_tagline',   'Excellence in Education',     'string', 'School motto / tagline',        1),
('school','school_email',     'info@myschool.com',           'string', 'Primary school email',          1),
('school','school_phone',     '+251-000-000-000',            'string', 'Primary school phone',          1),
('school','school_address',   'City, Region, Ethiopia',      'string', 'School address',                1),
('school','school_city',      'Sheger',                            'string', 'City',                          1),
('school','school_region',    'Oromiya',                            'string', 'Region / state',                1),
('school','school_country',   'Ethiopia',                    'string', 'Country',                       1),
('school','school_website',   'urjiberischool.com',                            'string', 'School website URL',            1),
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
('email','from_email',    '',                                'string', 'Default from email',            0),
('email','from_name',     'My School',                       'string', 'Default from name',             0),
('sms','provider',  '',                                      'string', 'SMS provider name',             0),
('sms','api_key',   '',                                      'string', 'SMS provider API key',          0),
('sms','sender_id', '',                                      'string', 'SMS sender ID',                 0);

-- ============================================================
-- SECTION 9: HR DEPARTMENTS
-- ============================================================
INSERT IGNORE INTO `hr_departments` (`id`, `name`, `code`, `description`, `status`) VALUES
(1, 'Administration',    'ADMIN',   'School administration and management',    'active'),
(2, 'Teaching Staff',    'TEACH',   'All teaching and instructional staff',     'active'),
(3, 'Finance',           'FIN',     'Financial management and accounting',      'active'),
(4, 'Library',           'LIB',     'Library services and management',          'active'),
(5, 'Support Services',  'SUPPORT', 'Maintenance, security, and support staff', 'active'),
(6, 'IT Department',     'IT',      'Information technology and systems',        'active');

-- ============================================================
-- SECTION 10: HR LEAVE TYPES
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
-- SECTION 11: ETHIOPIAN PUBLIC HOLIDAYS
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

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- seed_for_new.sql complete — Summary:
-- ============================================================
--   7 roles, 94 permissions, full role-permission matrix
--   1 admin user (username: admin, password: password)
--     → force_password_change = 1 (must change on first login)
--   3 mediums, 3 streams, 3 shifts
--   1 academic session (2025/2026) with 4 terms
--   Ethiopian Standard grade scale (11 entries)
--   32 system settings (school info as placeholders)
--   6 HR departments, 8 leave types, 13 Ethiopian public holidays
--
--   NO students, teachers, classes, subjects, or sample data.
--   Configure your school info in Settings after first login.
-- ============================================================


