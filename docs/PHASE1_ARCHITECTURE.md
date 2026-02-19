# Urjiberi School Management ERP — Phase 1: Architecture & Assumptions

## 1. System Overview

**Urjiberi School ERP** is a single-school, single-tenant web-based School Management System built with:
- **Backend:** Procedural PHP 8.2+
- **Database:** MySQL 8+
- **Frontend:** Tailwind CSS + minimal vanilla JS
- **Architecture:** Mobile-first, PWA-capable

The system manages the full school lifecycle: admissions, academics, attendance, exams, finance, communication, and reporting.

---

## 2. Key Assumptions

| Area | Assumption |
|---|---|
| **Tenancy** | Single school only. No multi-tenant logic anywhere. |
| **Language** | English UI. UTF-8 throughout for Amharic/other content in data fields. |
| **Currency** | Ethiopian Birr (ETB) as primary. Configurable in settings. |
| **Academic Calendar** | Supports sessions (e.g., 2025/2026) with terms/semesters. |
| **Grading** | Configurable grade scales per exam or institution-wide. |
| **Users** | All users share a single `users` table with role-based access. |
| **Authentication** | Session-based PHP auth with bcrypt hashing. |
| **File Uploads** | Stored on local disk under `/uploads/` with randomized names. |
| **Payment** | Provider-agnostic gateway architecture. Telebirr adapter included. |
| **Hosting** | Standard LAMP/LEMP stack. Apache or Nginx with PHP-FPM. |
| **HTTPS** | Required in production for PWA and security. |
| **Time Zone** | Africa/Addis_Ababa default. Configurable. |
| **Max Users** | Designed for up to ~5,000 students, ~500 staff. |

---

## 3. Role Definitions

| Role | Description |
|---|---|
| **Super Admin** | Full system access. Can manage all settings, users, and modules. |
| **Admin** | School administration. Manages academics, students, finance, reports. |
| **Teacher** | Manages own classes: attendance, assignments, marks, report cards. |
| **Student** | Views own profile, grades, attendance, assignments, fees. |
| **Parent** | Views linked children's data: grades, attendance, fees, messages. |
| **Accountant** | Manages finance: fees, invoices, payments, financial reports. |
| **Librarian** | Reserved for future library module. Basic user access for now. |

---

## 4. Permission Matrix

Permissions are stored as granular action strings: `module.action`

Example permissions:
```
users.view, users.create, users.update, users.delete
students.view, students.create, students.update, students.delete, students.export
attendance.view, attendance.create, attendance.update
exams.view, exams.create, exams.update, exams.delete
marks.view, marks.create, marks.update
finance.view, finance.create, finance.update, finance.export
reports.view, reports.export
settings.view, settings.update
audit_logs.view
```

Super Admin has wildcard `*` access. Other roles get explicit permission sets.

---

## 5. Module Map

### A) Auth & Access (`/modules/auth`, `/modules/users`)
- Login / Logout / Forgot Password / Reset Password
- User CRUD with role assignment
- Role & permission management
- Session management with security hardening

### B) Academic Foundation (`/modules/academics`)
- Academic sessions (years) CRUD
- Terms/semesters CRUD
- Classes & sections CRUD
- Subjects CRUD
- Teacher-class-subject mapping
- Timetable management

### C) Student Lifecycle (`/modules/students`)
- Admission form and workflow
- Student profile with full details
- Guardian/parent linking
- Enrollment by session/class/section
- Promotion and transfer
- Document metadata tracking

### D) Teaching & Assessment (`/modules/attendance`, `/modules/exams`)
- Daily attendance marking (class-level)
- Subject/period attendance option
- Assignment creation and submission tracking
- Exam creation and scheduling
- Marks entry per exam/subject
- Grade scale configuration
- Report card generation

### E) Finance (`/modules/finance`, `/modules/payments`)
- Fee categories (tuition, transport, lab, etc.)
- Fee structures per class/session
- Invoice generation (manual + bulk)
- Discounts, fines, scholarships
- Payment recording (cash, bank, gateway)
- Payment gateway integration (Telebirr, generic)
- Receipt generation
- Dues and arrears tracking
- Finance dashboard and reports

### F) Communication (`/modules/communication`)
- School-wide announcements
- Internal messaging (user-to-user, basic)
- Notification center (in-app)
- Email/SMS hook integration points

### G) Reporting & Admin (`/modules/reports`, `/modules/dashboard`, `/modules/settings`)
- Role-based KPI dashboard
- Attendance reports (daily, weekly, monthly, term)
- Academic reports (class performance, subject analysis)
- Finance reports (collection, outstanding, trends)
- System settings management
- Audit log viewer
- Backup trigger hooks

---

## 6. Request Lifecycle

```
Browser Request
    │
    ▼
public/index.php (front controller)
    │
    ▼
config/app.php (load config, session, error handling)
    │
    ▼
core/db.php (PDO connection)
    │
    ▼
core/auth.php (session validation, load user)
    │
    ▼
core/router.php (match URL → module route)
    │
    ▼
Module routes.php → actions.php / pages
    │
    ├── core/csrf.php (validate tokens on POST)
    ├── core/validation.php (input validation)
    ├── Module service.php (business logic + DB queries)
    │
    ▼
templates/layout.php + module view
    │
    ▼
HTML Response
```

---

## 7. URL Routing Design

Simple query-parameter routing via front controller:

```
/index.php?mod=students&action=list
/index.php?mod=students&action=create
/index.php?mod=students&action=view&id=42
/index.php?mod=finance&action=invoice&id=15
/index.php?mod=auth&action=login
```

With `.htaccess` rewrite for clean URLs:
```
/students
/students/create
/students/view/42
/finance/invoice/15
/auth/login
```

---

## 8. Database Design Principles

- **Normalization:** 3NF where practical; denormalize only for reporting performance.
- **Primary Keys:** Auto-increment `BIGINT UNSIGNED` named `id`.
- **Foreign Keys:** Enforced with `RESTRICT` (default) or `CASCADE` where logical.
- **Timestamps:** `created_at` and `updated_at` on all main tables.
- **Soft Deletes:** `deleted_at` on key entities (users, students).
- **Indexes:** On FKs, frequently searched/filtered columns, composite where needed.
- **Character Set:** `utf8mb4` / `utf8mb4_unicode_ci` for full Unicode support.
- **Engine:** InnoDB for transaction support.

---

## 9. Security Architecture

| Layer | Implementation |
|---|---|
| Authentication | Session-based, `password_hash(PASSWORD_BCRYPT)` |
| Session | `session_regenerate_id()` on login, secure cookie flags |
| CSRF | Per-form token, validated on all POST/PUT/DELETE |
| SQL Injection | PDO prepared statements exclusively |
| XSS | `htmlspecialchars()` on all output |
| Authorization | Server-side permission check on every action |
| Uploads | Type whitelist, size limit, random filename, outside webroot |
| Brute Force | Login attempt tracking with lockout |
| Audit | All sensitive actions logged with user, IP, timestamp |
| Errors | Logged to file, generic message to user |

---

## 10. Payment Gateway Architecture

```
payment.php config
    │
    ├── gateway_config['telebirr'] = [...]
    ├── gateway_config['chapa'] = [...]
    └── gateway_config['stripe'] = [...]

payment adapter functions:
    gateway_create_payment($gateway, $data)
    gateway_verify_payment($gateway, $reference)
    gateway_handle_webhook($gateway, $payload)
    gateway_get_checkout_url($gateway, $data)

Each gateway adapter implements:
    telebirr_create_payment($data)
    telebirr_verify_payment($reference)
    telebirr_handle_webhook($payload)
    telebirr_validate_signature($payload, $signature)

Transaction states: pending → success | failed | cancelled | refunded
Idempotency: Unique key per payment attempt
Reconciliation: Cron-compatible function to check pending transactions
```

---

## 11. PWA Architecture

- `manifest.webmanifest` — app metadata, icons, theme
- `service-worker.js` — app-shell caching, offline fallback
- `offline.html` — offline page
- Online/offline indicator in UI
- Cache versioning with safe invalidation

---

## 12. Technology Stack Summary

| Component | Technology |
|---|---|
| Language | PHP 8.2+ (procedural) |
| Database | MySQL 8.0+ |
| CSS Framework | Tailwind CSS (CDN for dev, build for prod) |
| JavaScript | Vanilla JS (minimal) |
| Server | Apache 2.4+ or Nginx |
| PWA | Service Worker API, Web App Manifest |
| Payment | Telebirr API + generic adapter pattern |

---

## 13. Next Phase Plan

**Phase 2** will deliver:
- Complete ERD narrative
- Full normalized SQL schema (40+ tables)
- Ordered migration files
- Seed data for testing
- Index and FK optimization
