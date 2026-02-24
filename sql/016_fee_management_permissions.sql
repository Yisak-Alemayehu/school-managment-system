-- ============================================================
-- Urji Beri School SMS — Migration 016: Fee Management Permissions
-- Add permissions and role mappings for the new fee module
-- ============================================================

SET NAMES utf8mb4;

-- ── New Fee Management Permissions ───────────────────────────
INSERT INTO `permissions` (`module`, `action`, `description`) VALUES
('fee_management', 'view_dashboard', 'View fee management dashboard'),
('fee_management', 'create_fee', 'Create new fee definitions'),
('fee_management', 'edit_fee', 'Edit existing fee definitions'),
('fee_management', 'delete_fee', 'Delete/archive fee definitions'),
('fee_management', 'activate_fee', 'Activate or deactivate fee definitions'),
('fee_management', 'assign_fee', 'Assign fees to classes/groups/students'),
('fee_management', 'manage_exemptions', 'Manage fee exemptions'),
('fee_management', 'manage_groups', 'Create and manage student groups'),
('fee_management', 'view_reports', 'View fee reports'),
('fee_management', 'export_reports', 'Export fee reports'),
('fee_management', 'manage_charges', 'Manage student fee charges (waive, cancel)'),
('fee_management', 'view_audit_log', 'View finance audit log')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- ── Admin (role_id=2) gets all fee_management permissions ────
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions` WHERE `module` = 'fee_management';

-- ── Accountant (role_id=6) gets most fee_management permissions ─
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 6, id FROM `permissions` WHERE `module` = 'fee_management'
AND `action` IN ('view_dashboard','create_fee','edit_fee','assign_fee','manage_exemptions','manage_groups','view_reports','export_reports','manage_charges');

-- ── Teacher (role_id=3) gets view-only ───────────────────────
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions` WHERE `module` = 'fee_management'
AND `action` IN ('view_dashboard','view_reports');

-- ── Finance Settings ─────────────────────────────────────────
INSERT INTO `settings` (`setting_group`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_public`) VALUES
('fee_management', 'penalty_job_enabled', '1', 'boolean', 'Enable automatic penalty processing', 0),
('fee_management', 'recurrence_job_enabled', '1', 'boolean', 'Enable automatic recurring charge generation', 0),
('fee_management', 'default_currency', 'ETB', 'string', 'Default fee currency', 0),
('fee_management', 'penalty_job_hour', '3', 'integer', 'Hour (0-23) to run penalty job daily', 0),
('fee_management', 'recurrence_job_hour', '2', 'integer', 'Hour (0-23) to run recurrence job daily', 0)
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
