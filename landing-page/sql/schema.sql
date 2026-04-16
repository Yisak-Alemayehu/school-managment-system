-- Eduelevate Landing Page & Client System Database Schema
-- Run this script against your MySQL database

CREATE DATABASE IF NOT EXISTS eduelevate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eduelevate;

-- ─── Users ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('admin','school') NOT NULL DEFAULT 'school',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ─── Schools ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS schools (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(255) NULL,
    student_count INT UNSIGNED DEFAULT 0,
    school_type ENUM('kindergarten','primary','secondary','preparatory','mixed') DEFAULT 'mixed',
    package ENUM('basic','standard','premium') NULL,
    pipeline_stage ENUM('requested','demo_scheduled','demo_completed','interested','agreement_sent','payment_pending','active','churned') NOT NULL DEFAULT 'requested',
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_pipeline (pipeline_stage),
    INDEX idx_package (package)
) ENGINE=InnoDB;

-- ─── Demo Slots ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS demo_slots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slot_date DATE NOT NULL,
    time_start TIME NOT NULL,
    time_end TIME NOT NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (slot_date),
    INDEX idx_available (is_available)
) ENGINE=InnoDB;

-- ─── Demos ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS demos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,
    demo_slot_id INT UNSIGNED NULL,
    scheduled_at DATETIME NULL,
    status ENUM('pending','scheduled','completed','cancelled','no_show') NOT NULL DEFAULT 'pending',
    notes TEXT NULL,
    admin_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (demo_slot_id) REFERENCES demo_slots(id) ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ─── Agreements ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS agreements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL DEFAULT 'Service Agreement',
    content LONGTEXT NOT NULL,
    status ENUM('draft','sent','viewed','accepted','rejected') NOT NULL DEFAULT 'draft',
    sent_at DATETIME NULL,
    viewed_at DATETIME NULL,
    responded_at DATETIME NULL,
    signature_data TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ─── Payments ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_type ENUM('setup','monthly','installment') NOT NULL,
    installment_number TINYINT UNSIGNED NULL,
    total_installments TINYINT UNSIGNED NULL,
    status ENUM('pending','paid','verified','overdue','refunded') NOT NULL DEFAULT 'pending',
    due_date DATE NULL,
    paid_at DATETIME NULL,
    verified_at DATETIME NULL,
    transaction_ref VARCHAR(100) NULL,
    payment_method VARCHAR(50) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB;

-- ─── Notifications ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info','success','warning','error') NOT NULL DEFAULT 'info',
    link VARCHAR(500) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ─── CMS Content ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS content (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(500) NULL,
    subtitle VARCHAR(500) NULL,
    body LONGTEXT NULL,
    image_path VARCHAR(500) NULL,
    extra_data JSON NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section (section_key),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- ─── Features ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS features (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(100) NOT NULL DEFAULT 'star',
    image_path VARCHAR(500) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── Pricing Packages ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pricing_packages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    school_size VARCHAR(100) NOT NULL,
    student_range VARCHAR(50) NOT NULL,
    setup_fee_min DECIMAL(12,2) NOT NULL,
    setup_fee_max DECIMAL(12,2) NOT NULL,
    monthly_fee_min DECIMAL(12,2) NOT NULL,
    monthly_fee_max DECIMAL(12,2) NOT NULL,
    features_list JSON NULL,
    badge_text VARCHAR(50) NULL,
    is_popular TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── Testimonials ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS testimonials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    person_name VARCHAR(255) NOT NULL,
    person_role VARCHAR(100) NULL,
    content TEXT NOT NULL,
    avatar_path VARCHAR(500) NULL,
    rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── FAQ ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS faqs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(100) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── SEO Settings ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS seo_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_slug VARCHAR(100) NOT NULL UNIQUE,
    meta_title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    keywords VARCHAR(500) NULL,
    og_title VARCHAR(255) NULL,
    og_description TEXT NULL,
    og_image VARCHAR(500) NULL,
    canonical_url VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── Contact Submissions ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS contact_submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NULL,
    school_name VARCHAR(255) NULL,
    message TEXT NULL,
    type ENUM('demo_request','contact','get_started') NOT NULL DEFAULT 'contact',
    status ENUM('new','contacted','closed') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ─── Activity Log ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS activity_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id INT UNSIGNED NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB;
