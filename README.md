# Urjiberi School ERP

A complete, production-ready School Management System built with procedural PHP 8.2+, MySQL 8+, Tailwind CSS, and vanilla JavaScript. Designed for Ethiopian schools with mobile-first PWA capabilities.

---

## Features

### Authentication & Authorization
- Role-based access control (7 roles: Super Admin, Admin, Teacher, Student, Parent, Accountant, Librarian)
- 53 granular permissions
- Brute-force protection (5 attempts / 15 min lockout)
- Session management with auto-regeneration
- Password reset flow
- Profile management

### Dashboard
- Role-specific dashboards with KPI cards
- Admin: student count, revenue, attendance rate, pending fees
- Teacher: classes, upcoming assignments, attendance
- Student: grades, attendance, upcoming exams
- Parent: children overview

### Student Management
- Complete admission form with auto-generated admission numbers
- Guardian/parent linking
- Class enrollment management
- Promotion system (bulk promote to next grade)
- CSV export
- Photo upload
- Search and filter by class/section/status

### Academic Management
- Academic sessions & terms
- Classes (Grade 1-12) with sections (A/B/C)
- Subject management (12 subjects)
- Class-subject assignments
- Class-teacher assignments
- Timetable scheduling (day/period grid)

### Attendance
- Daily attendance marking (Present/Absent/Late/Excused)
- Visual radio-button interface per student
- Monthly class attendance report with day grid
- Individual student attendance history
- Statistics and trends

### Examinations
- Exam creation and scheduling
- Per-subject exam schedule management
- Marks entry with auto-grade calculation
- Ethiopian standard grade scale (A+ through F)
- Report card generation with GPA, rank, and remarks
- Printable report cards
- Assignment management with submissions

### Finance
- Fee categories and structures
- Invoice generation (individual or bulk by class)
- Payment recording with receipt generation
- Partial payment support
- Fee discounts (percentage or fixed)
- Online payment integration (Telebirr + Chapa)
- Payment gateway abstraction layer
- Financial reports (class-wise, method-wise, collection rate)
- Printable invoices and receipts

### Communication
- School announcements (by role/class targeting)
- Internal messaging system (inbox/compose/sent)
- Notification system with read/unread tracking
- Real-time notification bell in header

### Settings & Administration
- System-wide settings management (grouped by category)
- Audit log viewer with filters
- Database backup (mysqldump + PHP fallback)
- Backup download and management

---

## Tech Stack

| Component      | Technology                          |
|----------------|-------------------------------------|
| Backend        | PHP 8.2+ (Procedural, no framework)|
| Database       | MySQL 8.0+ (InnoDB, utf8mb4)       |
| Frontend CSS   | Tailwind CSS (CDN)                  |
| Frontend JS    | Vanilla JavaScript (ES5 compatible) |
| PWA            | Service Worker + Web App Manifest   |
| Architecture   | Single front controller, module-based |
| Authentication | Session-based with bcrypt (cost 12) |
| Security       | CSRF tokens, prepared statements, XSS escaping |

---

## Project Structure

```
urjiberischool system 2026/
├── config/
│   ├── app.php              # Application constants
│   ├── database.php         # Database credentials
│   └── payment.php          # Payment gateway config
├── core/
│   ├── auth.php             # Authentication & authorization
│   ├── csrf.php             # CSRF protection
│   ├── db.php               # Database abstraction (PDO)
│   ├── helpers.php          # Utility functions
│   ├── payment_gateway.php  # Payment gateway abstraction
│   ├── pwa.php              # PWA helpers
│   ├── response.php         # Response helpers (redirect, json)
│   ├── router.php           # URL routing dispatcher
│   ├── security.php         # Security utilities
│   ├── validation.php       # Form validation
│   └── gateways/
│       ├── telebirr.php     # Telebirr adapter
│       └── chapa.php        # Chapa adapter
├── modules/
│   ├── auth/                # Login, logout, password reset
│   ├── dashboard/           # Role-based dashboard
│   ├── users/               # User CRUD
│   ├── students/            # Student management + promotion
│   ├── academics/           # Sessions, classes, subjects, timetable
│   ├── attendance/          # Daily attendance
│   ├── exams/               # Exams, marks, report cards
│   ├── finance/             # Fees, invoices, payments
│   ├── communication/       # Announcements, messages, notifications
│   └── settings/            # System settings, audit logs, backup
├── public/                  # Web root (point server here)
│   ├── index.php            # Front controller
│   ├── .htaccess            # URL rewriting
│   ├── manifest.webmanifest # PWA manifest
│   ├── service-worker.js    # PWA service worker
│   ├── offline.html         # Offline fallback page
│   └── assets/
│       ├── css/app.css      # Custom styles
│       ├── js/app.js        # Core JavaScript
│       └── icons/
│           └── icon.php     # Dynamic PNG icon generator
├── templates/
│   ├── layout.php           # Main layout template
│   ├── partials/
│   │   ├── sidebar.php      # Role-based sidebar navigation
│   │   ├── header.php       # Top header with notifications
│   │   ├── flash.php        # Flash messages
│   │   └── mobile_nav.php   # Bottom mobile navigation
│   └── errors/
│       ├── 403.php          # Forbidden page
│       └── 404.php          # Not found page
├── sql/
│   ├── 001_core_auth.sql    # Roles, permissions, users
│   ├── 002_academics.sql    # Sessions, terms, classes, subjects
│   ├── 003_students.sql     # Students, guardians, enrollments
│   ├── 004_assessment.sql   # Assignments, exams, marks, grades
│   ├── 005_finance.sql      # Fees, invoices, payments, discounts
│   ├── 006_payment_gateway.sql # Payment transactions
│   ├── 007_communication.sql   # Announcements, messages, notifications
│   ├── 008_system.sql       # Audit logs, settings
│   └── 009_seed_data.sql    # Default data (roles, admin, grades, etc.)
├── storage/                 # Runtime storage (uploads, backups)
│   ├── uploads/
│   └── backups/
├── docs/
│   ├── PHASE1_ARCHITECTURE.md
│   ├── DEPLOYMENT.md
│   └── SECURITY_CHECKLIST.md
├── .htaccess                # Root protection
└── README.md                # This file
```

---

## Quick Start

### Prerequisites
- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `gd`, `openssl`, `json`, `curl`, `fileinfo`
- MySQL 8.0+
- Apache 2.4+ with `mod_rewrite` enabled (or Nginx)

### Installation

1. **Clone the project**
   ```bash
   git clone <repo-url> urjiberi
   cd urjiberi
   ```

2. **Create the database**
   ```sql
   CREATE DATABASE urjiberi_school CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Run migrations** (in order)
   ```bash
   mysql -u root -p urjiberi_school < sql/001_core_auth.sql
   mysql -u root -p urjiberi_school < sql/002_academics.sql
   mysql -u root -p urjiberi_school < sql/003_students.sql
   mysql -u root -p urjiberi_school < sql/004_assessment.sql
   mysql -u root -p urjiberi_school < sql/005_finance.sql
   mysql -u root -p urjiberi_school < sql/006_payment_gateway.sql
   mysql -u root -p urjiberi_school < sql/007_communication.sql
   mysql -u root -p urjiberi_school < sql/008_system.sql
   mysql -u root -p urjiberi_school < sql/009_seed_data.sql
   ```

4. **Configure** — Edit `config/database.php` with your DB credentials.

5. **Set permissions**
   ```bash
   mkdir -p storage/uploads storage/backups
   chmod -R 755 storage/
   ```

6. **Point web server** to the `public/` directory.

7. **Login**
   - URL: `http://your-domain/`
   - Username: **superadmin**
   - Password: **Admin@123**
   - ⚠️ Change this password immediately after first login!

---

## Default Users & Roles

| Role         | Capabilities |
|-------------|-------------|
| Super Admin  | Full system access, all permissions |
| Admin        | School management, user management |
| Teacher      | Classes, attendance, marks, assignments |
| Student      | View grades, attendance, assignments |
| Parent       | View children's progress, pay fees |
| Accountant   | Finance module, invoices, payments |
| Librarian    | (Reserved for future library module) |

---

## Ethiopian Context

- **Currency**: Ethiopian Birr (ETB / Br)
- **Timezone**: Africa/Addis_Ababa (UTC+3)
- **Phone format**: +251XXXXXXXXX
- **Grade scale**: Ethiopian standard (A+ to F)
- **Payment gateways**: Telebirr, Chapa
- **School email domain**: @urjiberi.edu.et

---

## API / Routing

Routes use query parameters via the single front controller:

```
/                          → Dashboard
/?module=students          → Students list
/?module=students&action=create  → Create student
/?module=students&action=view&id=5  → View student #5
/?module=finance&action=invoices     → Invoice list
/?module=settings&action=audit-logs  → Audit logs
```

Clean URLs are enabled via `.htaccess` rewrite rules.

---

## Database Schema

**40+ tables** organized by module:

- **Auth**: `roles`, `permissions`, `role_permissions`, `users`, `user_permissions`
- **Academics**: `academic_sessions`, `terms`, `classes`, `sections`, `subjects`, `class_subjects`, `class_teachers`, `timetables`
- **Students**: `students`, `guardians`, `student_guardian`, `enrollments`, `promotions`
- **Assessment**: `assignments`, `assignment_submissions`, `exams`, `exam_schedules`, `marks`, `grade_scales`, `grade_scale_entries`, `report_cards`, `attendance`
- **Finance**: `fee_categories`, `fee_structures`, `invoices`, `invoice_items`, `payments`, `fee_discounts`
- **Payment**: `payment_gateways`, `payment_transactions`
- **Communication**: `announcements`, `messages`, `notifications`
- **System**: `audit_logs`, `settings`

---

## Security Features

- CSRF token protection on all forms
- bcrypt password hashing (cost 12)
- Brute-force login protection
- Session regeneration every 30 minutes
- Prepared statements for all SQL queries
- HTML output escaping via `e()` function
- Rate limiting
- Security headers (X-Frame-Options, X-Content-Type-Options, etc.)
- Soft deletes where applicable
- Audit logging on all CRUD operations
- File upload validation (type, size, random filename)
- IP-based tracking in audit logs

---

## PWA Features

- Install-to-home-screen prompt
- Offline fallback page
- Service worker with app-shell caching
- Network-first for pages, cache-first for assets
- Online/offline status indicator
- Web app manifest with shortcuts

---

## Payment Gateways

### Telebirr
Configure in `config/payment.php`:
```php
define('TELEBIRR_APP_ID', 'your-app-id');
define('TELEBIRR_APP_KEY', 'your-app-key');
define('TELEBIRR_SHORT_CODE', 'your-short-code');
define('TELEBIRR_PUBLIC_KEY', 'your-public-key');
```

### Chapa
```php
define('CHAPA_SECRET_KEY', 'your-secret-key');
define('CHAPA_PUBLIC_KEY', 'your-public-key');
```

---

## License

This software is proprietary to Urjiberi School. All rights reserved.

---

## Support

For technical support, contact the system administrator or email admin@urjiberi.edu.et.
#   s c h o o l - m a n a g m e n t - s y s t e m  
 