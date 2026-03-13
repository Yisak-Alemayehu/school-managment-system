-- ============================================================
-- Urji Beri School SMS -- ONLINE DEMO SEED
-- Super Admin credentials only
-- Run AFTER db.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Roles
INSERT IGNORE INTO `roles` (`id`, `name`, `slug`, `description`, `is_system`) VALUES
(1, 'Super Admin',  'super_admin',  'Full system access — all modules, all operations',             1),
(2, 'School Admin', 'school_admin', 'Manages school operations, staff, and academic settings',      1),
(3, 'Teacher',      'teacher',      'Manages classes, assignments, grades, and attendance',          0),
(4, 'Student',      'student',      'Views own academic info, grades, fees, and attendance',         0),
(5, 'Parent',       'parent',       'Views child academic info, fees, and communicates with school', 0),
(6, 'Accountant',   'accountant',   'School accountant role',                                        0),
(7, 'Registrar',    'registrar',    'Manages student admissions, enrollments, and records',          0);

-- Super Admin user  |  username: admin  |  password: password
INSERT IGNORE INTO `users` (`id`,`username`,`email`,`password_hash`,`full_name`,`first_name`,`last_name`,`phone`,`is_active`,`status`,`force_password_change`) VALUES
(1, 'admin', 'admin@urjiberischool.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'System', 'Administrator', '0912000000', 1, 'active', 0);

INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`) VALUES (1, 1);

SET FOREIGN_KEY_CHECKS = 1;
