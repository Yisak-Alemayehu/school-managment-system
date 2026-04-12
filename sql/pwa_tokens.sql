-- ============================================================
-- PWA Token Table Migration
-- Run this after db.sql to enable PWA authentication
-- ============================================================

CREATE TABLE IF NOT EXISTS `pwa_tokens` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `token_hash`  CHAR(64) NOT NULL COMMENT 'SHA-256 hex of the raw token',
    `role`        ENUM('student','parent') NOT NULL,
    `linked_id`   BIGINT UNSIGNED DEFAULT NULL COMMENT 'student.id or guardian.id',
    `device_name` VARCHAR(255) DEFAULT NULL,
    `expires_at`  DATETIME NOT NULL,
    `last_used_at` DATETIME DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pwa_tokens_hash` (`token_hash`),
    INDEX `idx_pwa_tokens_user` (`user_id`),
    INDEX `idx_pwa_tokens_expires` (`expires_at`),
    CONSTRAINT `fk_pwa_tokens_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Stateless bearer tokens for the PWA (Student & Parent app)';
