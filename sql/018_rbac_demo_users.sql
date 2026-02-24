-- ============================================================
-- 018 — RBAC Demo Users & Permission Alignment
-- Adds demo credentials, aligns permissions with routes,
-- and sets up the complete role-permission matrix.
-- Safe to re-run (uses INSERT IGNORE / ON DUPLICATE KEY).
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. ADD MISSING PERMISSIONS (used in routes but not in seed)
-- ============================================================
INSERT IGNORE INTO `permissions` (`module`, `action`, `description`) VALUES
-- Academics  (routes use academics.manage as catch-all)
('academics',       'manage',           'Manage academic settings (create/update/delete)'),
-- Timetable
('timetable',       'view',             'View timetables'),
('timetable',       'manage',           'Manage timetables'),
-- Students (routes use students.edit, seed has students.update)
('students',        'edit',             'Edit student details'),
-- Users (routes use users.edit, seed has users.update)
('users',           'edit',             'Edit user details'),
-- Attendance (routes use attendance.manage)
('attendance',      'manage',           'Manage/submit attendance'),
-- Singular assignment/exam/marks/report_card (routes use singular)
('assignment',      'view',             'View assignments'),
('assignment',      'manage',           'Manage assignments'),
('exam',            'view',             'View exams'),
('exam',            'manage',           'Manage exams'),
('marks',           'manage',           'Manage marks entry'),
('report_card',     'view',             'View report cards'),
('report_card',     'manage',           'Manage report cards'),
-- Legacy permission strings used in old routes
('manage_finance',       'access',      'Legacy: access finance management'),
('manage_settings',      'access',      'Legacy: access settings management'),
('manage_communication', 'access',      'Legacy: access communication management'),
('super_admin',          'access',      'Legacy: super admin access check'),
-- Fee Management module permissions (used in finance/routes.php)
('fee_management',  'view_dashboard',   'View fee management dashboard'),
('fee_management',  'create_fee',       'Create new fees'),
('fee_management',  'activate_fee',     'Activate/deactivate fees'),
('fee_management',  'delete_fee',       'Delete fees'),
('fee_management',  'assign_fee',       'Assign fees to students/classes'),
('fee_management',  'manage_exemptions','Manage fee exemptions'),
('fee_management',  'manage_groups',    'Manage student fee groups'),
('fee_management',  'view_reports',     'View fee reports'),
('fee_management',  'export_reports',   'Export fee reports'),
('fee_management',  'manage_charges',   'Manage fee charges and payments');

-- ============================================================
-- 2. REBUILD ROLE → PERMISSION ASSIGNMENTS
--    Clear and reassign for roles 2-6 to match the new matrix
-- ============================================================

-- Keep super_admin (role_id=1) as wildcard via code

-- ── ADMIN (role_id = 2): ALL permissions ──
DELETE FROM `role_permissions` WHERE `role_id` = 2;
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions`;

-- ── TEACHER (role_id = 3) ──
DELETE FROM `role_permissions` WHERE `role_id` = 3;
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions` WHERE
    -- Dashboard
    (`module` = 'dashboard'     AND `action` = 'view') OR
    -- Academics (view only for assigned classes)
    (`module` = 'academics'     AND `action` = 'view') OR
    -- Students (view only for assigned classes)
    (`module` = 'students'      AND `action` = 'view') OR
    -- Attendance (view + create, but NOT update after submission — enforced in code)
    (`module` = 'attendance'    AND `action` IN ('view', 'create', 'manage')) OR
    -- Assignments (full CRUD)
    (`module` = 'assignments'   AND `action` IN ('view', 'create', 'update', 'delete', 'grade')) OR
    (`module` = 'assignment'    AND `action` IN ('view', 'manage')) OR
    -- Exams (view only)
    (`module` = 'exams'         AND `action` = 'view') OR
    (`module` = 'exam'          AND `action` = 'view') OR
    -- Marks (full entry)
    (`module` = 'marks'         AND `action` IN ('view', 'create', 'update', 'manage')) OR
    -- Report Cards (view + generate)
    (`module` = 'report_cards'  AND `action` IN ('view', 'create', 'update', 'export')) OR
    (`module` = 'report_card'   AND `action` IN ('view', 'manage')) OR
    -- Communication (view + create messages)
    (`module` = 'communication' AND `action` IN ('view', 'create')) OR
    -- Timetable (view only)
    (`module` = 'timetable'     AND `action` = 'view');

-- ── STUDENT (role_id = 4) ──
DELETE FROM `role_permissions` WHERE `role_id` = 4;
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM `permissions` WHERE
    -- Dashboard
    (`module` = 'dashboard'     AND `action` = 'view') OR
    -- Attendance (view own only — enforced in code)
    (`module` = 'attendance'    AND `action` = 'view') OR
    -- Assignments (view only)
    (`module` = 'assignments'   AND `action` = 'view') OR
    (`module` = 'assignment'    AND `action` = 'view') OR
    -- Exams (view only)
    (`module` = 'exams'         AND `action` = 'view') OR
    (`module` = 'exam'          AND `action` = 'view') OR
    -- Marks (view own)
    (`module` = 'marks'         AND `action` = 'view') OR
    -- Report Cards (view own)
    (`module` = 'report_cards'  AND `action` = 'view') OR
    (`module` = 'report_card'   AND `action` = 'view') OR
    -- Finance (view own fees)
    (`module` = 'finance'       AND `action` = 'view') OR
    -- Communication (view announcements)
    (`module` = 'communication' AND `action` = 'view') OR
    -- Timetable (view)
    (`module` = 'timetable'     AND `action` = 'view') OR
    -- Academics (view subjects)
    (`module` = 'academics'     AND `action` = 'view');

-- ── PARENT (role_id = 5) ──
DELETE FROM `role_permissions` WHERE `role_id` = 5;
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 5, id FROM `permissions` WHERE
    -- Dashboard
    (`module` = 'dashboard'     AND `action` = 'view') OR
    -- Students (view children)
    (`module` = 'students'      AND `action` = 'view') OR
    -- Attendance (view children)
    (`module` = 'attendance'    AND `action` = 'view') OR
    -- Marks (view children)
    (`module` = 'marks'         AND `action` = 'view') OR
    -- Report Cards (view children)
    (`module` = 'report_cards'  AND `action` = 'view') OR
    (`module` = 'report_card'   AND `action` = 'view') OR
    -- Finance (view invoices)
    (`module` = 'finance'       AND `action` = 'view') OR
    -- Communication (view + create messages)
    (`module` = 'communication' AND `action` IN ('view', 'create')) OR
    -- Timetable (view)
    (`module` = 'timetable'     AND `action` = 'view') OR
    -- Academics (view subjects)
    (`module` = 'academics'     AND `action` = 'view') OR
    -- Exams/assignments view
    (`module` = 'exams'         AND `action` = 'view') OR
    (`module` = 'exam'          AND `action` = 'view') OR
    (`module` = 'assignments'   AND `action` = 'view') OR
    (`module` = 'assignment'    AND `action` = 'view');

-- ── ACCOUNTANT (role_id = 6) ──
DELETE FROM `role_permissions` WHERE `role_id` = 6;
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 6, id FROM `permissions` WHERE
    -- Dashboard
    (`module` = 'dashboard'    AND `action` = 'view') OR
    -- Students (view for fee lookup)
    (`module` = 'students'     AND `action` = 'view') OR
    -- Finance (full access)
    (`module` = 'finance'      AND `action` IN ('view', 'create', 'update', 'delete', 'export', 'payment')) OR
    -- Fee Management (full access)
    (`module` = 'fee_management' AND `action` IN ('view_dashboard', 'create_fee', 'activate_fee', 'delete_fee',
        'assign_fee', 'manage_exemptions', 'manage_groups', 'view_reports', 'export_reports', 'manage_charges')) OR
    -- Legacy finance permission
    (`module` = 'manage_finance' AND `action` = 'access') OR
    -- Reports
    (`module` = 'reports'      AND `action` IN ('view', 'export'));

-- ── LIBRARIAN (role_id = 7) — unchanged ──
DELETE FROM `role_permissions` WHERE `role_id` = 7;
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 7, id FROM `permissions` WHERE
    (`module` = 'dashboard'     AND `action` = 'view') OR
    (`module` = 'students'      AND `action` = 'view') OR
    (`module` = 'communication' AND `action` = 'view');

-- ============================================================
-- 3. DEMO USERS
-- ============================================================

-- 3a. Super Admin Demo
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `first_name`, `last_name`,
    `phone`, `gender`, `is_active`, `status`, `email_verified_at`)
VALUES ('demo_superadmin', 'superadmin@demo.com',
    '$2y$12$yDzeya3MNqFgGmMw9ewfeuq5xDDBt3qaxGQFP2T5uhin51gfzK7kK',
    'Demo Super Admin', 'Demo', 'SuperAdmin', '+251900100001', 'male', 1, 'active', NOW())
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), is_active = 1, status = 'active';

-- 3b. School Admin Demo
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `first_name`, `last_name`,
    `phone`, `gender`, `is_active`, `status`, `email_verified_at`)
VALUES ('demo_schooladmin', 'schooladmin@demo.com',
    '$2y$12$qEgts0CZKFmMO0sGST07oeLZZNdyxiMfAV1c9h4umIr0z5H5WoIi6',
    'Demo School Admin', 'Demo', 'SchoolAdmin', '+251900100002', 'male', 1, 'active', NOW())
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), is_active = 1, status = 'active';

-- 3c. Teacher Demo
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `first_name`, `last_name`,
    `phone`, `gender`, `is_active`, `status`, `email_verified_at`)
VALUES ('demo_teacher', 'teacher@demo.com',
    '$2y$12$XBWVWXJfoFdy2U4zCjj22enkJyHrisSEukCDKWBvvkaSt5IFRDgrq',
    'Demo Teacher', 'Demo', 'Teacher', '+251900100003', 'female', 1, 'active', NOW())
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), is_active = 1, status = 'active';

-- 3d. Student Demo
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `first_name`, `last_name`,
    `phone`, `gender`, `is_active`, `status`, `email_verified_at`)
VALUES ('demo_student', 'student@demo.com',
    '$2y$12$k2C3z/Z0reiPrSHpEj3dv.5epVRV1sngqGeaE.yi5u4q2ZIWeEN8y',
    'Demo Student', 'Demo', 'Student', '+251900100004', 'male', 1, 'active', NOW())
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), is_active = 1, status = 'active';

-- 3e. Parent Demo
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `first_name`, `last_name`,
    `phone`, `gender`, `is_active`, `status`, `email_verified_at`)
VALUES ('demo_parent', 'parent@demo.com',
    '$2y$12$9v0nceRKOmflzZBl/kKzDObzvAzJAXa4oMPCrPoeyrFmCWzT9BZc6',
    'Demo Parent', 'Demo', 'Parent', '+251900100005', 'female', 1, 'active', NOW())
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), is_active = 1, status = 'active';

-- 3f. Accountant Demo
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `first_name`, `last_name`,
    `phone`, `gender`, `is_active`, `status`, `email_verified_at`)
VALUES ('demo_accountant', 'accountant@demo.com',
    '$2y$12$PRKTL4WBPoBT3sey2RYgcew6xrUvpLIlq4yl3rdh3EW3vvSYzXLLy',
    'Demo Accountant', 'Demo', 'Accountant', '+251900100006', 'male', 1, 'active', NOW())
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), is_active = 1, status = 'active';

-- ============================================================
-- 4. ASSIGN ROLES TO DEMO USERS
-- ============================================================
-- Use subqueries to get user IDs by email (safe even after re-runs)
INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
SELECT id, 1 FROM `users` WHERE email = 'superadmin@demo.com';

INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
SELECT id, 2 FROM `users` WHERE email = 'schooladmin@demo.com';

INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
SELECT id, 3 FROM `users` WHERE email = 'teacher@demo.com';

INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
SELECT id, 4 FROM `users` WHERE email = 'student@demo.com';

INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
SELECT id, 5 FROM `users` WHERE email = 'parent@demo.com';

INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
SELECT id, 6 FROM `users` WHERE email = 'accountant@demo.com';

-- ============================================================
-- 5. LINK DEMO TEACHER TO CLASS ASSIGNMENTS (Grade 1 & 2)
-- ============================================================
-- Assign demo teacher to Grade 1 (class_id=1) for English & Amharic
INSERT IGNORE INTO `class_teachers` (`class_id`, `subject_id`, `teacher_id`, `session_id`, `is_class_teacher`)
SELECT 1, s.id, u.id, 1, 1
FROM `users` u, `subjects` s
WHERE u.email = 'teacher@demo.com' AND s.id IN (1, 2);

-- Assign demo teacher to Grade 2 (class_id=2) for English & Amharic
INSERT IGNORE INTO `class_teachers` (`class_id`, `subject_id`, `teacher_id`, `session_id`, `is_class_teacher`)
SELECT 2, s.id, u.id, 1, 0
FROM `users` u, `subjects` s
WHERE u.email = 'teacher@demo.com' AND s.id IN (1, 2);

-- ============================================================
-- 6. LINK DEMO STUDENT USER TO FIRST STUDENT RECORD
-- ============================================================
UPDATE `students` SET `user_id` = (SELECT id FROM `users` WHERE email = 'student@demo.com' LIMIT 1)
WHERE id = 1 AND `user_id` IS NULL;

-- ============================================================
-- 7. LINK DEMO PARENT USER TO FIRST GUARDIAN RECORD
-- ============================================================
UPDATE `guardians` SET `user_id` = (SELECT id FROM `users` WHERE email = 'parent@demo.com' LIMIT 1)
WHERE id = 1 AND `user_id` IS NULL;

-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- Migration 018 complete.
-- Demo Credentials:
--   superadmin@demo.com  / superadmin123   → Super Admin
--   schooladmin@demo.com / schooladmin123  → School Admin
--   teacher@demo.com     / teacher123      → Teacher (Grade 1-2)
--   student@demo.com     / student123      → Student (linked to student #1)
--   parent@demo.com      / parent123       → Parent  (linked to guardian #1)
--   accountant@demo.com  / accountant123   → Accountant
-- ============================================================
