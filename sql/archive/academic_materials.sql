-- ══════════════════════════════════════════════════════════════
-- Academic Materials Module — Database Schema
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS academic_materials (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title           VARCHAR(255) NOT NULL,
    class_id        BIGINT UNSIGNED NOT NULL,
    subject_id      BIGINT UNSIGNED NOT NULL,
    book_type       ENUM('teachers_guide','student_book','supplementary') NOT NULL,
    cover_image     VARCHAR(500) DEFAULT NULL,
    file_path       VARCHAR(500) NOT NULL,
    file_size       BIGINT UNSIGNED DEFAULT 0,
    uploaded_by     BIGINT UNSIGNED NOT NULL,
    status          ENUM('active','inactive') DEFAULT 'active',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME DEFAULT NULL,

    INDEX idx_materials_class      (class_id),
    INDEX idx_materials_subject    (subject_id),
    INDEX idx_materials_type       (book_type),
    INDEX idx_materials_status     (status),
    INDEX idx_materials_deleted    (deleted_at),
    INDEX idx_materials_class_subj (class_id, subject_id),

    FOREIGN KEY (class_id)    REFERENCES classes(id)  ON DELETE CASCADE,
    FOREIGN KEY (subject_id)  REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Permissions ──────────────────────────────────────────────
INSERT IGNORE INTO permissions (module, action) VALUES
    ('materials', 'view'),
    ('materials', 'create'),
    ('materials', 'edit'),
    ('materials', 'delete');

-- Grant all materials permissions to admin & super_admin roles
INSERT IGNORE INTO role_permissions (role_id, permission_id)
    SELECT r.id, p.id
    FROM roles r
    CROSS JOIN permissions p
    WHERE r.slug IN ('super_admin', 'admin')
      AND p.module = 'materials';

-- Grant view permission to teacher role
INSERT IGNORE INTO role_permissions (role_id, permission_id)
    SELECT r.id, p.id
    FROM roles r
    CROSS JOIN permissions p
    WHERE r.slug = 'teacher'
      AND p.module = 'materials'
      AND p.action = 'view';
