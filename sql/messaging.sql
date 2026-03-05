-- ============================================================
-- Messaging Module — Database Schema
-- Supports: Solo, Bulk, Group messaging with attachments
-- Run AFTER db.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- DROP TABLES (reverse FK order)
-- ============================================================
DROP TABLE IF EXISTS `msg_attachments`;
DROP TABLE IF EXISTS `msg_message_status`;
DROP TABLE IF EXISTS `msg_messages`;
DROP TABLE IF EXISTS `msg_group_members`;
DROP TABLE IF EXISTS `msg_groups`;
DROP TABLE IF EXISTS `msg_conversation_participants`;
DROP TABLE IF EXISTS `msg_conversations`;

-- ============================================================
-- Conversations — container for all message threads
-- type: solo (1-to-1), bulk (admin broadcast), group (student groups)
-- ============================================================
CREATE TABLE IF NOT EXISTS `msg_conversations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` ENUM('solo','bulk','group') NOT NULL DEFAULT 'solo',
    `subject` VARCHAR(255) DEFAULT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `group_id` BIGINT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_conv_type` (`type`),
    INDEX `idx_conv_creator` (`created_by`),
    INDEX `idx_conv_group` (`group_id`),
    CONSTRAINT `fk_conv_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Conversation Participants — who is in a conversation
-- ============================================================
CREATE TABLE IF NOT EXISTS `msg_conversation_participants` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `left_at` DATETIME DEFAULT NULL,
    `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
    `last_read_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_conv_participant` (`conversation_id`, `user_id`),
    INDEX `idx_cp_user` (`user_id`),
    CONSTRAINT `fk_cp_conv` FOREIGN KEY (`conversation_id`) REFERENCES `msg_conversations`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cp_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Messages — actual message content
-- ============================================================
CREATE TABLE IF NOT EXISTS `msg_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `sender_id` BIGINT UNSIGNED NOT NULL,
    `body` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_msg_conv` (`conversation_id`, `created_at`),
    INDEX `idx_msg_sender` (`sender_id`),
    CONSTRAINT `fk_msg2_conv` FOREIGN KEY (`conversation_id`) REFERENCES `msg_conversations`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_msg2_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Message Status — per-recipient delivery tracking
-- status: sent, delivered, read
-- ============================================================
CREATE TABLE IF NOT EXISTS `msg_message_status` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `message_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('sent','delivered','read') NOT NULL DEFAULT 'sent',
    `delivered_at` DATETIME DEFAULT NULL,
    `read_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_msg_status` (`message_id`, `user_id`),
    INDEX `idx_ms_user` (`user_id`, `status`),
    CONSTRAINT `fk_ms_msg` FOREIGN KEY (`message_id`) REFERENCES `msg_messages`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ms_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Attachments — files attached to messages
-- ============================================================
CREATE TABLE IF NOT EXISTS `msg_attachments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `message_id` BIGINT UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
    `mime_type` VARCHAR(100) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_att_msg` (`message_id`),
    CONSTRAINT `fk_att_msg` FOREIGN KEY (`message_id`) REFERENCES `msg_messages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Student Groups — for group messaging (students only)
-- ============================================================
CREATE TABLE IF NOT EXISTS `msg_groups` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `class_id` BIGINT UNSIGNED DEFAULT NULL,
    `section_id` BIGINT UNSIGNED DEFAULT NULL,
    `max_members` INT UNSIGNED NOT NULL DEFAULT 30,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_grp_creator` (`created_by`),
    INDEX `idx_grp_class` (`class_id`),
    CONSTRAINT `fk_grp_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_grp_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_grp_section` FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Group Members
-- ============================================================
CREATE TABLE IF NOT EXISTS `msg_group_members` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
    `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_grp_member` (`group_id`, `user_id`),
    INDEX `idx_gm_user` (`user_id`),
    CONSTRAINT `fk_gm_group` FOREIGN KEY (`group_id`) REFERENCES `msg_groups`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_gm_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Messaging Permissions
-- ============================================================
INSERT IGNORE INTO `permissions` (`module`, `action`, `description`) VALUES
('messaging', 'solo',       'Send and receive solo (direct) messages'),
('messaging', 'bulk',       'Send bulk messages to students/teachers'),
('messaging', 'group',      'Create and manage student groups'),
('messaging', 'view',       'View message history and conversations'),
('messaging', 'attachment', 'Attach files to messages');
