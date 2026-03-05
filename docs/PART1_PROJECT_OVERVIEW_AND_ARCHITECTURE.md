# Urji Beri School Management System — Pre-Development Documentation

## PART 1: PROJECT OVERVIEW & SYSTEM ARCHITECTURE

**Document Version:** 1.0.0  
**Date:** February 27, 2026  
**Status:** Pre-Development Planning  
**Classification:** Internal — Development Team  

---

# TABLE OF CONTENTS — PART 1

1. [Project Overview](#1-project-overview)
   - 1.1 Project Name
   - 1.2 Project Description
   - 1.3 Objectives
   - 1.4 Scope
   - 1.5 Target Users
   - 1.6 Stakeholders
   - 1.7 Assumptions and Constraints
2. [System Architecture](#2-system-architecture)
   - 2.1 High-Level Architecture Overview
   - 2.2 Logical Architecture Description
   - 2.3 Physical Architecture Description
   - 2.4 Architecture Diagrams
   - 2.5 Technology Stack
   - 2.6 Design Patterns Used

---

# 1. PROJECT OVERVIEW

## 1.1 Project Name

**Urji Beri School Management System (Urji Beri SMS)**

- **Internal Code Name:** `urjiberi_school`
- **Version:** 1.0.0
- **Database Identifier:** `urjiberi_school`
- **Session Identifier:** `urjiberi_session`

## 1.2 Project Description

The Urji Beri School Management System is a comprehensive, web-based school administration platform designed specifically for the Ethiopian K-12 educational context. The system provides end-to-end management of academic operations, student lifecycle, financial transactions, examination & assessment processes, attendance tracking, and internal communication for Urji Beri School.

The system is built as a monolithic PHP application following a modular architecture pattern with a custom front-controller routing mechanism. It is designed to operate as a Progressive Web Application (PWA) supporting offline functionality, mobile-first responsive design, and installability on mobile devices.

The platform integrates with Ethiopian payment gateways (Telebirr, Chapa) for online fee collection, supports the Ethiopian academic calendar and grading standards, and uses the Ethiopian Birr (ETB) as the default currency with the Africa/Addis_Ababa timezone.

## 1.3 Objectives

### Primary Objectives

| ID | Objective | Success Criteria |
|----|-----------|-----------------|
| OBJ-01 | Digitize student admission and registration | 100% of new admissions processed through the system |
| OBJ-02 | Automate academic management (sessions, terms, classes, subjects) | All academic structures managed via system |
| OBJ-03 | Implement examination and assessment tracking with Ethiopian grade scales | All marks, assessments, and report cards generated digitally |
| OBJ-04 | Enable online and offline fee management and payment collection | All fee structures, invoices, and payments tracked; Telebirr/Chapa integration active |
| OBJ-05 | Provide daily attendance management and reporting | Teachers can take and view attendance daily; reports generated per class/student |
| OBJ-06 | Deliver role-based dashboards and analytics | Each role (admin, teacher, student, parent, accountant) sees relevant data |
| OBJ-07 | Establish secure internal communication (announcements, messages, notifications) | All school announcements and user-to-user messaging through the system |
| OBJ-08 | Implement robust security with RBAC, CSRF protection, and audit logging | All actions logged; role-based access enforced system-wide |

### Secondary Objectives

| ID | Objective | Success Criteria |
|----|-----------|-----------------|
| OBJ-09 | Support PWA functionality for mobile-first access | Service worker registered; offline page available; app installable |
| OBJ-10 | Generate printable report cards, invoices, receipts, and ID cards | A4/A5 print-ready outputs for all document types |
| OBJ-11 | Provide CSV bulk import/export for student data | Bulk upload of student records via CSV template |
| OBJ-12 | Enable timetable management per class/section | Weekly timetable grid created and managed per academic session |
| OBJ-13 | Support student promotion between academic sessions | Bulk promotion with promoted/repeated/graduated statuses |
| OBJ-14 | Implement advanced fee management with recurrence and penalties | Automated recurring fees and late payment penalty calculations |

## 1.4 Scope

### 1.4.1 In-Scope

| # | Feature Area | Description |
|---|-------------|-------------|
| 1 | **Authentication & Authorization** | Login, logout, password reset, password change, profile management, session management, brute-force lockout |
| 2 | **Role-Based Access Control (RBAC)** | 7 predefined roles (Super Admin, Admin, Teacher, Student, Parent, Accountant, Librarian); granular permission system with ~75 permissions; wildcard permission (`*`) for Super Admin |
| 3 | **Academic Management** | Academic sessions, terms/semesters, classes (Grade 1-12), sections (A, B, etc.), subjects, mediums (language of instruction), streams (Natural/Social Science), shifts (Morning/Afternoon) |
| 4 | **Student Management** | Admission form, student profiles, guardian management (multiple guardians per student), enrollment tracking, student promotion, roll number assignment, ID card generation, credential generation, bulk CSV import, student password reset |
| 5 | **Examination & Assessment** | Exam creation, exam scheduling, marks entry, assessment management (total marks capped at 100 per subject), student conduct grades (A-F behavioral), grade scales (Ethiopian standard A+ through F), report card generation (printable A4 with QR), result analysis, class roster |
| 6 | **Attendance Management** | Daily attendance (present/absent/late/excused), class-level attendance taking, date-range attendance reports, individual student attendance history |
| 7 | **Finance Module (Legacy)** | Fee categories, fee structures per class/session, invoice generation, payment recording (cash/bank/online), fee discounts/scholarships, payment receipts, fee collection reports |
| 8 | **Advanced Fee Management** | Fee creation (one-time/recurrent), recurrence configuration (monthly/termly/annually), penalty configuration (fixed/percentage), fee assignment (to class/grade/individual/group), student groups management, fee exemptions, student fee charges tracking, penalty charge calculation, fee reports with CSV export, professional A5 invoice printing |
| 9 | **Payment Gateway Integration** | Telebirr (Ethiopian Mobile Money) and Chapa payment processing, webhook handling, transaction tracking with idempotency, payment reconciliation |
| 10 | **Communication** | School-wide announcements (with audience targeting), internal messaging (inbox, sent, compose, reply), user notifications with read tracking |
| 11 | **Dashboard** | Role-specific dashboards: Super Admin/Admin (total students, teachers, classes, revenue, recent activity), Teacher (assigned classes, attendance summary, pending tasks), Student (grades, attendance, fees), Parent (children overview), Accountant (financial summary) |
| 12 | **Settings & Administration** | School settings (grouped key-value pairs), audit log viewer with filters, database backup management |
| 13 | **PWA / Offline Support** | Service worker for app shell caching, offline fallback page, web app manifest, installability |
| 14 | **API Endpoints** | Internal AJAX JSON endpoints for dynamic dropdowns (sections by class, subjects by class) |
| 15 | **Cron Jobs** | Automated penalty calculation job, automated recurrence fee generation job |
| 16 | **Class Teacher & Subject Teacher Assignment** | Homeroom teacher assignment per class/section, subject teacher assignment with duplicate prevention |
| 17 | **Elective Subject Management** | Mark subjects as elective per class, assign elective choices per student |
| 18 | **Student Export** | Export student data to downloadable format |

### 1.4.2 Out-of-Scope

| # | Feature | Reason |
|---|---------|--------|
| 1 | Email/SMS notification delivery | Infrastructure-dependent; marked as TODO in codebase; requires SMTP/SMS gateway integration |
| 2 | Library management module | Librarian role exists but no library module is planned for v1.0 |
| 3 | Transport management | Not included in initial scope |
| 4 | Hostel/dormitory management | Not included in initial scope |
| 5 | Multi-school/multi-tenant support | Single-school deployment only |
| 6 | Mobile native applications (iOS/Android) | PWA serves mobile users; native apps not planned |
| 7 | Video conferencing / online classes | Not included |
| 8 | Stripe payment processing | Configuration template exists but not implemented for Ethiopian market |
| 9 | Multi-language/i18n support | English only for v1.0 |
| 10 | Advanced analytics / BI dashboards | Basic stats only; no charting libraries |
| 11 | Student/Parent self-registration | Admin-controlled user creation only |
| 12 | API for third-party integrations | Internal AJAX only; no public REST API |

## 1.5 Target Users

| Role | Slug | Description | Primary Functions | Estimated Count |
|------|------|-------------|-------------------|----------------|
| **Super Administrator** | `super_admin` | System owner with unrestricted access (`*` wildcard permission) | Full system configuration, user management, all modules, database backup | 1-2 |
| **School Administrator** | `admin` | School management staff | Academic setup, student management, staff management, reports, settings | 2-5 |
| **Teacher** | `teacher` | Teaching staff | Attendance, marks entry, assignments, view timetable, view student info | 10-50 |
| **Student** | `student` | Enrolled students | View grades, attendance, fees, announcements, pay online | 100-1000+ |
| **Parent/Guardian** | `parent` | Student guardians | View children's grades, attendance, fees, pay online, messaging | 100-500+ |
| **Accountant** | `accountant` | Finance staff | Fee management, invoicing, payment recording, financial reports | 1-3 |
| **Librarian** | `librarian` | Library staff (reserved) | Reserved role for future library module | 1-2 |

## 1.6 Stakeholders

| Stakeholder | Role | Interest | Involvement Level |
|------------|------|----------|------------------|
| School Owner / Director | Project Sponsor | System success, ROI, operational efficiency | High — Approval authority |
| School Principal | Primary End User | Academic operations, reporting | High — Requirements validation |
| IT Administrator | Technical Owner | Deployment, maintenance, backups | High — Infrastructure management |
| Teachers | End Users | Ease of use, attendance/marks entry | Medium — User acceptance testing |
| Accountant(s) | End Users | Financial accuracy, payment tracking | Medium — Finance module validation |
| Students | End Users | Access to grades, fees, announcements | Low — Read-only consumers |
| Parents/Guardians | End Users | Children's academic and financial transparency | Low — Read-only consumers |
| Development Team | Builders | Clear requirements, technical decisions | High — Full development lifecycle |

## 1.7 Assumptions and Constraints

### Assumptions

| ID | Assumption |
|----|-----------|
| A-01 | The school operates on a standard academic session calendar (e.g., 2025/2026) divided into terms/semesters |
| A-02 | The school follows the Ethiopian grading standard (A+ = 95-100 through F = 0-39) |
| A-03 | All users will have access to modern web browsers (Chrome, Firefox, Edge, Safari) |
| A-04 | The deployment server has PHP 8.1+ with PDO MySQL, OpenSSL, and mbstring extensions |
| A-05 | MySQL 8.0+ or MariaDB 10.5+ is available as the database server |
| A-06 | The school uses Ethiopian Birr (ETB) as the sole currency |
| A-07 | Internet connectivity is available for initial page loads (PWA handles brief offline periods) |
| A-08 | File uploads will not exceed 5MB per file |
| A-09 | The school has a maximum of 12 grade levels (Grade 1-12) with 2 or more sections each |
| A-10 | Telebirr is the primary online payment gateway for the Ethiopian market |
| A-11 | A `.env` file will be configured on the server with database credentials and payment gateway keys |
| A-12 | The school administrator is capable of configuring system settings through the web interface |

### Constraints

| ID | Constraint | Impact |
|----|-----------|--------|
| C-01 | **No ORM or Framework** — Pure PHP with custom core libraries | All database operations use raw PDO with prepared statements; no migration tooling |
| C-02 | **Single-server deployment** — Designed for shared hosting / cPanel | No horizontal scaling; no load balancing; single MySQL instance |
| C-03 | **No email/SMS service** — Email delivery is a TODO | Password reset links are logged to audit log instead of sent; no notification delivery |
| C-04 | **Ethiopian payment gateways only** — Telebirr and Chapa | Stripe template exists but is not functional for the Ethiopian market |
| C-05 | **English language only** — No i18n framework | All UI text is hardcoded in English |
| C-06 | **Session-based authentication only** — No JWT/token-based auth | Stateful sessions using PHP session handler; 2-hour session lifetime |
| C-07 | **No front-end framework** — Vanilla JS with Tailwind CSS | No React/Vue/Angular; all rendering is server-side PHP with output buffering |
| C-08 | **Assessment total marks capped at 100** — Per subject per term | System enforces that all assessments for a subject in a term sum to ≤ 100 |
| C-09 | **Maximum upload size: 5MB** — Server + application limit | `UPLOAD_MAX_SIZE` = 5,242,880 bytes |
| C-10 | **PHP 8.1+ required** — Uses match expressions, named arguments, union types | Cannot run on PHP 7.x |

---

# 2. SYSTEM ARCHITECTURE

## 2.1 High-Level Architecture Overview

The Urji Beri SMS uses a **Monolithic Modular Architecture** with a **Front Controller Pattern**. All HTTP requests are routed through a single entry point (`public/index.php`), which bootstraps the application, initializes sessions, and dispatches requests to the appropriate module based on URL parameters.

The system follows a **functional programming paradigm** — there are no classes or objects in the application code. All logic is implemented as pure PHP functions organized into core libraries and module-specific files.

```
┌─────────────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                                 │
│  ┌───────────┐  ┌───────────┐  ┌───────────┐  ┌───────────┐       │
│  │ Desktop   │  │ Mobile    │  │ Tablet    │  │ PWA       │       │
│  │ Browser   │  │ Browser   │  │ Browser   │  │ (Offline) │       │
│  └─────┬─────┘  └─────┬─────┘  └─────┬─────┘  └─────┬─────┘       │
│        │              │              │              │               │
│        └──────────────┴──────────────┴──────────────┘               │
│                              │                                      │
│                     HTTP/HTTPS Requests                             │
│                              │                                      │
├──────────────────────────────┼──────────────────────────────────────┤
│                     WEB SERVER LAYER                                │
│                    ┌─────────┴─────────┐                           │
│                    │  Apache / Nginx   │                           │
│                    │  (.htaccess URL   │                           │
│                    │   rewriting)      │                           │
│                    └─────────┬─────────┘                           │
│                              │                                      │
├──────────────────────────────┼──────────────────────────────────────┤
│                   APPLICATION LAYER                                 │
│                              │                                      │
│                    ┌─────────┴─────────┐                           │
│                    │ public/index.php  │ ← Front Controller        │
│                    │ (Entry Point)     │                           │
│                    └─────────┬─────────┘                           │
│                              │                                      │
│          ┌───────────────────┼───────────────────┐                 │
│          │                   │                   │                 │
│    ┌─────┴─────┐    ┌───────┴───────┐    ┌──────┴──────┐         │
│    │  Config   │    │   Core Libs   │    │   Modules   │         │
│    │  Layer    │    │   Layer       │    │   Layer     │         │
│    ├───────────┤    ├───────────────┤    ├─────────────┤         │
│    │ app.php   │    │ env.php       │    │ academics/  │         │
│    │ database  │    │ db.php        │    │ attendance/ │         │
│    │ .php      │    │ security.php  │    │ auth/       │         │
│    │ payment   │    │ auth.php      │    │ communica-  │         │
│    │ .php      │    │ csrf.php      │    │   tion/     │         │
│    └───────────┘    │ validation    │    │ dashboard/  │         │
│                     │   .php        │    │ exams/      │         │
│                     │ helpers.php   │    │ finance/    │         │
│                     │ response.php  │    │ settings/   │         │
│                     │ router.php    │    │ students/   │         │
│                     │ rbac.php      │    │ users/      │         │
│                     │ pwa.php       │    │ api/        │         │
│                     │ payment_      │    └─────────────┘         │
│                     │   gateway.php │                             │
│                     │ gateways/     │                             │
│                     │  chapa.php    │                             │
│                     │  telebirr.php │                             │
│                     └───────────────┘                             │
│                              │                                      │
├──────────────────────────────┼──────────────────────────────────────┤
│                    TEMPLATE LAYER                                   │
│          ┌───────────────────┼───────────────────┐                 │
│    ┌─────┴─────┐    ┌───────┴───────┐    ┌──────┴──────┐         │
│    │ layout    │    │  partials/    │    │  errors/    │         │
│    │ .php      │    │  header.php   │    │  403.php    │         │
│    │           │    │  sidebar.php  │    │  404.php    │         │
│    │           │    │  flash.php    │    │             │         │
│    │           │    │  mobile_nav   │    │             │         │
│    │           │    │  academics_   │    │             │         │
│    │           │    │    nav.php    │    │             │         │
│    └───────────┘    └───────────────┘    └─────────────┘         │
│                              │                                      │
├──────────────────────────────┼──────────────────────────────────────┤
│                     DATA LAYER                                      │
│                    ┌─────────┴─────────┐                           │
│                    │  MySQL 8.0+       │                           │
│                    │  (54 Tables)      │                           │
│                    │  utf8mb4 charset  │                           │
│                    └───────────────────┘                           │
│                                                                     │
├─────────────────────────────────────────────────────────────────────┤
│                 EXTERNAL SERVICES                                   │
│    ┌──────────┐    ┌──────────┐    ┌──────────┐                   │
│    │ Telebirr │    │ Chapa    │    │ SMTP     │                   │
│    │ Payment  │    │ Payment  │    │ (Future) │                   │
│    │ Gateway  │    │ Gateway  │    │          │                   │
│    └──────────┘    └──────────┘    └──────────┘                   │
└─────────────────────────────────────────────────────────────────────┘
```

## 2.2 Logical Architecture Description

### 2.2.1 Request Lifecycle

```
Browser Request
    │
    ▼
public/index.php (Front Controller)
    │
    ├── 1. Define APP_ROOT constant
    ├── 2. Load core/env.php → env_load(.env)
    ├── 3. Load config/app.php (30+ constants)
    ├── 4. Load config/database.php (8 constants)
    ├── 5. Load config/payment.php (12 constants)
    ├── 6. Load core/db.php (16 functions)
    ├── 7. Load core/security.php (7 functions)
    ├── 8. Load core/helpers.php (30+ functions)
    ├── 9. Load core/auth.php (20 functions)
    ├── 10. Load core/csrf.php (7 functions)
    ├── 11. Load core/validation.php (7 functions)
    ├── 12. Load core/response.php (7 functions)
    ├── 13. Load core/router.php (12 functions)
    ├── 14. Load core/rbac.php (17 functions)
    ├── 15. Load core/pwa.php (3 functions)
    ├── 16. set_security_headers()
    ├── 17. auth_init_session()
    ├── 18. csrf_generate()
    └── 19. router_dispatch()
              │
              ├── Parse URL: ?module=XXX&action=YYY&id=ZZZ
              ├── Sanitize module name (a-z, underscore only)
              ├── Check module directory exists: modules/{module}/routes.php
              ├── Load module routes.php
              │     │
              │     ├── Check auth_require() / auth_require_permission()
              │     ├── switch($action) → match to route handler
              │     │     │
              │     │     ├── GET → Load view file (modules/{module}/views/{page}.php)
              │     │     │         │
              │     │     │         ├── View queries database
              │     │     │         ├── View starts output buffer: ob_start()
              │     │     │         ├── View generates HTML with Tailwind CSS
              │     │     │         ├── View captures content: $content = ob_get_clean()
              │     │     │         └── View includes template: require layout.php
              │     │     │
              │     │     └── POST → Load action file (modules/{module}/actions/{action}.php)
              │     │               │
              │     │               ├── csrf_protect() — validate CSRF token
              │     │               ├── validate() — validate input data
              │     │               ├── Database operations (insert/update/delete)
              │     │               ├── audit_log() — record action
              │     │               ├── set_flash() — set success/error message
              │     │               └── redirect() — redirect to next page
              │     │
              │     └── default → redirect or 404
              │
              └── If module not found → 404 error page
```

### 2.2.2 Module Architecture

Each module follows an identical internal structure:

```
modules/{module_name}/
├── routes.php          ← Router (switch/case on action parameter)
├── actions/            ← POST handlers (form processing, data mutation)
│   ├── {action}_save.php
│   ├── {action}_delete.php
│   └── ...
└── views/              ← GET handlers (data retrieval + HTML rendering)
    ├── {page}.php
    ├── {page}_form.php
    └── ...
```

**Module List & Responsibility Matrix:**

| Module | Route Count | Views | Actions | Primary Responsibility |
|--------|------------|-------|---------|----------------------|
| `academics` | 27 | 14 | 14 | Sessions, terms, classes, sections, subjects, mediums, streams, shifts, timetable, promotion, class/subject teachers, electives |
| `api` | 2 | 0 | 0 | Internal JSON endpoints for AJAX (sections, subjects lookups) |
| `attendance` | 5 | 4 | 1 | Daily attendance taking, viewing, reporting, student history |
| `auth` | 6 | 5 | 5 | Login, logout, password reset, password change, profile |
| `communication` | 11 | 8 | 5 | Announcements CRUD, messaging (inbox/sent/compose), notifications |
| `dashboard` | 1 | 1 | 0 | Role-specific dashboard with stats and quick actions |
| `exams` | 19 | 15 | 8 | Exams, marks, assessments, conduct, results, report cards, grade scales, roster |
| `finance` | 36 | 25 | 18 | Fee management (legacy + advanced), invoicing, payments, discounts, groups, reports, online payment |
| `settings` | 4 | 3 | 1 | General settings, audit logs, database backup |
| `students` | 14 | 11 | 8 | Student CRUD, admission, enrollment, promotion, credentials, bulk import, ID cards |
| `users` | 6 | 4 | 4 | User CRUD, role assignment, status toggle |
| **TOTAL** | **131** | **90** | **64** | — |

### 2.2.3 Core Library Layer

The core layer provides shared infrastructure functions used by all modules:

```
core/
├── env.php              ← .env file parser (2 functions)
├── db.php               ← PDO database abstraction (16 functions)
├── security.php         ← XSS escaping, file upload, audit log, rate limiting, security headers (7 functions)
├── auth.php             ← Session management, login/logout, password reset, permission checking (20 functions)
├── csrf.php             ← CSRF token generation, validation, protection (7 functions)
├── validation.php       ← Input validation engine, sanitization, input helpers (7 functions)
├── helpers.php          ← Flash messages, formatting, pagination, settings, notifications (30+ functions)
├── response.php         ← JSON responses, AJAX detection, view rendering (7 functions)
├── router.php           ← URL routing, module dispatch, redirect helpers (12 functions)
├── rbac.php             ← Role-based data filtering (teacher classes, student records, parent children) (17 functions)
├── pwa.php              ← PWA meta tags, service worker registration (3 functions)
├── payment_gateway.php  ← Payment transaction abstraction (4 functions)
└── gateways/
    ├── chapa.php         ← Chapa payment initiation and verification (2 functions)
    └── telebirr.php      ← Telebirr payment initiation and verification (2 functions)
```

**Total Core Functions: ~134**

### 2.2.4 Configuration Layer

Three configuration files define all application constants:

| File | Constants Defined | Purpose |
|------|------------------|---------|
| `config/app.php` | 36 | Application identity, paths, session, security, uploads, pagination, currency, date formats, error handling |
| `config/database.php` | 8 | MySQL connection parameters (host, port, name, user, password, charset, collation, PDO options) |
| `config/payment.php` | 12 | Payment gateway configurations (Telebirr, Chapa, Stripe), transaction states, timing settings |

**Total Constants: 56**

## 2.3 Physical Architecture Description

### 2.3.1 Single-Server Deployment

```
┌─────────────────────────────────────────────────────────────┐
│                    PRODUCTION SERVER                         │
│              (Shared Hosting / VPS / cPanel)                 │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              Web Server (Apache 2.4+)                │   │
│  │  ┌────────────────────────────────────────────────┐  │   │
│  │  │  .htaccess (URL Rewriting)                     │  │   │
│  │  │  - RewriteEngine On                            │  │   │
│  │  │  - Route all to public/index.php               │  │   │
│  │  └────────────────────────────────────────────────┘  │   │
│  │              │                                       │   │
│  │  ┌───────────┴──────────────────────────────────┐   │   │
│  │  │  Document Root: /public/                      │   │   │
│  │  │  ├── index.php (entry point)                  │   │   │
│  │  │  ├── uploads.php (secure file server)         │   │   │
│  │  │  ├── assets/css/app.css                       │   │   │
│  │  │  ├── assets/js/app.js                         │   │   │
│  │  │  ├── manifest.webmanifest                     │   │   │
│  │  │  ├── service-worker.js                        │   │   │
│  │  │  └── offline.html                             │   │   │
│  │  └──────────────────────────────────────────────┘   │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐   │
│  │           PHP 8.1+ (CGI/FPM)                         │   │
│  │  Required Extensions:                                │   │
│  │  - pdo_mysql                                         │   │
│  │  - openssl (for Telebirr encryption)                 │   │
│  │  - mbstring                                          │   │
│  │  - fileinfo (for MIME type detection)                │   │
│  │  - json                                              │   │
│  │  - session                                           │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐   │
│  │           MySQL 8.0+ / MariaDB 10.5+                 │   │
│  │  Database: urjiberi_school                           │   │
│  │  Charset: utf8mb4                                    │   │
│  │  Collation: utf8mb4_unicode_ci                       │   │
│  │  Tables: 54                                          │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐   │
│  │           File System                                │   │
│  │  /config/        ← Configuration (outside docroot)  │   │
│  │  /core/          ← Core libraries (outside docroot) │   │
│  │  /modules/       ← Feature modules (outside docroot)│   │
│  │  /templates/     ← Layout/partials (outside docroot)│   │
│  │  /uploads/       ← User files (outside docroot)     │   │
│  │  /storage/       ← Backups (outside docroot)        │   │
│  │  /logs/          ← Error/access logs                │   │
│  │  /.env           ← Environment variables            │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐   │
│  │           Cron Jobs                                  │   │
│  │  cron/fm_penalty_job.php    ← Daily penalty calc    │   │
│  │  cron/fm_recurrence_job.php ← Monthly fee gen       │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
         │                              │
         │  HTTPS                       │  HTTPS (Webhooks)
         ▼                              ▼
┌────────────────┐           ┌────────────────────┐
│ User Browsers  │           │ Payment Gateways   │
│ (Desktop/      │           │ - Telebirr API     │
│  Mobile/PWA)   │           │ - Chapa API        │
└────────────────┘           └────────────────────┘
```

### 2.3.2 Directory Security Model

```
Accessible from web (document root):
  public/
    ├── index.php          ← Only PHP entry point
    ├── uploads.php        ← Secure file proxy
    ├── assets/            ← Static CSS/JS
    ├── manifest.webmanifest
    ├── service-worker.js
    └── offline.html

NOT accessible from web (above document root):
  config/                  ← Database credentials, API keys
  core/                    ← Application logic
  modules/                 ← Business logic
  templates/               ← HTML templates
  uploads/                 ← User-uploaded files (served via uploads.php)
  storage/                 ← Database backups
  logs/                    ← Error logs
  sql/                     ← Migration files
  .env                     ← Environment secrets
```

## 2.4 Architecture Diagrams

### 2.4.1 Component Interaction Diagram

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Browser    │────▶│  Apache +    │────▶│ index.php    │
│              │◀────│  .htaccess   │◀────│ (bootstrap)  │
└──────────────┘     └──────────────┘     └──────┬───────┘
                                                  │
                          ┌───────────────────────┼───────────────────────┐
                          ▼                       ▼                       ▼
                   ┌──────────────┐       ┌──────────────┐       ┌──────────────┐
                   │   Config     │       │  Core Libs   │       │   Router     │
                   │  (constants) │       │ (functions)  │       │  (dispatch)  │
                   └──────────────┘       └──────────────┘       └──────┬───────┘
                                                                        │
                              ┌────────────────────────────────────────┼┐
                              ▼            ▼           ▼              ▼▼
                       ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
                       │academics │ │ students │ │  exams   │ │ finance  │
                       │ module   │ │  module  │ │  module  │ │  module  │
                       └────┬─────┘ └────┬─────┘ └────┬─────┘ └────┬─────┘
                            │            │            │            │
                            └────────────┴────────────┴────────────┘
                                                │
                                          ┌─────┴─────┐
                                          │  MySQL DB  │
                                          │ (54 tables)│
                                          └───────────┘
```

### 2.4.2 Authentication & Authorization Flow

```
User Request
    │
    ▼
auth_init_session()
    │
    ├── Session active? → Continue
    ├── New session → Set cookie params, start session
    └── Session > 30 min old? → Regenerate ID
    │
    ▼
auth_require() [on protected routes]
    │
    ├── $_SESSION['logged_in'] === true? → Continue
    └── Not logged in → Flash error → Redirect to /auth/login
    │
    ▼
auth_require_permission('module.action')
    │
    ├── $_SESSION['permissions'] contains '*'? → Allow (Super Admin)
    ├── $_SESSION['permissions'] contains 'module.action'? → Allow
    └── No permission → HTTP 403 → templates/errors/403.php
    │
    ▼
[Route Handler Executes]
    │
    ├── RBAC filtering (rbac.php)
    │   ├── Teacher? → Filter by assigned classes/sections
    │   ├── Student? → Filter by own student record
    │   ├── Parent? → Filter by linked children
    │   └── Admin/Super Admin? → No filter
    │
    └── Action completes → audit_log() → Response
```

### 2.4.3 Payment Processing Flow

```
Student/Parent selects invoice to pay online
    │
    ▼
POST /finance?action=payment-initiate
    │
    ├── Validate invoice exists and has balance
    ├── gateway_create_transaction() → Insert into payment_transactions
    │
    ├── [Telebirr Path]
    │   ├── telebirr_initiate()
    │   ├── Build USSD params + RSA encrypt with Telebirr public key
    │   ├── POST to Telebirr API
    │   └── Redirect user to Telebirr payment page
    │
    └── [Chapa Path]
        ├── chapa_initiate()
        ├── POST to Chapa API with payment details
        └── Redirect user to Chapa checkout
    │
    ▼
Payment Gateway processes payment
    │
    ▼
Webhook callback: POST /finance?action=payment-callback
    │
    ├── Verify webhook signature
    ├── gateway_update_transaction() → Update status
    ├── gateway_confirm_payment() → 
    │   ├── Find linked invoice
    │   ├── Update invoice paid_amount and balance
    │   ├── Create payment record
    │   └── Audit log
    └── Return 200 OK to gateway
```

## 2.5 Technology Stack

### 2.5.1 Frontend

| Technology | Version | Purpose |
|-----------|---------|---------|
| **HTML5** | — | Page structure, semantic markup |
| **Tailwind CSS** | 3.x (CDN) | Utility-first CSS framework for responsive, mobile-first design |
| **Vanilla JavaScript** | ES6+ | Client-side interactivity (AJAX calls, form validation, dynamic dropdowns) |
| **PWA APIs** | — | Service Worker, Web App Manifest, Cache API for offline support |
| **Font Awesome** | 6.x (CDN) | Icon library for UI elements |

### 2.5.2 Backend

| Technology | Version | Purpose |
|-----------|---------|---------|
| **PHP** | 8.1+ | Server-side application logic |
| **PDO (PHP Data Objects)** | — | Database abstraction layer with prepared statements |
| **OpenSSL Extension** | — | RSA encryption for Telebirr payment gateway |
| **Session Extension** | — | Server-side session management |
| **Fileinfo Extension** | — | MIME type detection for secure file uploads |
| **mbstring Extension** | — | Multi-byte string handling (UTF-8) |
| **cURL** | — | HTTP client for payment gateway API calls |

### 2.5.3 Database

| Technology | Version | Purpose |
|-----------|---------|---------|
| **MySQL** | 8.0+ | Primary relational database |
| **MariaDB** | 10.5+ (alternative) | MySQL-compatible alternative |
| **Character Set** | utf8mb4 | Full Unicode support (including emoji) |
| **Collation** | utf8mb4_unicode_ci | Case-insensitive Unicode comparison |
| **Engine** | InnoDB | ACID-compliant, foreign key support, row-level locking |
| **Stored Procedures** | — | Data generation in seed files (sp_gen_students, sp_gen_assessments, sp_gen_attendance) |

### 2.5.4 Server / Infrastructure

| Technology | Version | Purpose |
|-----------|---------|---------|
| **Apache** | 2.4+ | Web server with mod_rewrite |
| **Nginx** | 1.18+ (alternative) | Web server (requires custom config instead of .htaccess) |
| **cPanel** | Any | Hosting control panel (deployment target) |
| **Linux** | Any modern | Server operating system |
| **Cron** | System cron | Scheduled job execution (penalty calc, fee recurrence) |
| **SSL/TLS** | Let's Encrypt | HTTPS encryption |

### 2.5.5 External Services

| Service | Purpose | Integration Method |
|---------|---------|-------------------|
| **Telebirr** | Ethiopian mobile money payment | REST API + RSA encryption + webhooks |
| **Chapa** | Online payment gateway for Ethiopia | REST API + webhooks |
| **SMTP** (Future) | Email delivery for password resets, notifications | PHP mail() or SMTP library |
| **SMS Gateway** (Future) | SMS notifications | HTTP API |

## 2.6 Design Patterns Used

### 2.6.1 Front Controller Pattern

**Location:** `public/index.php`

All HTTP requests are routed through a single entry point. This ensures consistent bootstrapping (config loading, session init, CSRF token, security headers) before any module logic executes.

```
All Requests → public/index.php → router_dispatch() → modules/{module}/routes.php
```

### 2.6.2 Module Pattern (Modular Architecture)

**Location:** `modules/` directory

Each functional area is isolated in its own directory with a consistent internal structure (routes.php, actions/, views/). Modules are self-contained but share core libraries.

### 2.6.3 Repository Pattern (Implicit)

**Location:** `core/db.php`

Database access is centralized through a set of functions (`db_fetch_one()`, `db_insert()`, `db_update()`, etc.) that abstract away raw PDO operations. All modules use these functions rather than direct PDO calls.

### 2.6.4 Template Method Pattern

**Location:** `templates/layout.php` + module views

Views use output buffering (`ob_start()` / `ob_get_clean()`) to capture content, then inject it into a master layout template. The layout defines the page skeleton (header, sidebar, footer) and the view provides the content.

```php
// In view file:
ob_start();
// ... HTML content ...
$content = ob_get_clean();
$pageTitle = 'Page Title';
require TEMPLATES_PATH . '/layout.php';
```

### 2.6.5 Middleware Pattern (Implicit)

**Location:** `public/index.php` + `core/auth.php` + `core/csrf.php`

Before any module executes, the front controller runs a series of "middleware" operations:
1. `set_security_headers()` — HTTP security headers
2. `auth_init_session()` — Session initialization and regeneration
3. `csrf_generate()` — CSRF token preparation

Route-level middleware is implemented within `routes.php` files:
- `auth_require()` — Require authentication
- `auth_require_permission()` — Require specific permission
- `csrf_protect()` — Validate CSRF on POST

### 2.6.6 Singleton Pattern (Static Variable)

**Location:** `core/db.php` → `db_connect()`

The database connection uses a static variable to ensure only one PDO instance exists per request:

```php
function db_connect(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
    }
    return $pdo;
}
```

### 2.6.7 Strategy Pattern (Payment Gateways)

**Location:** `core/payment_gateway.php` + `core/gateways/`

Payment processing is abstracted through a common interface (`gateway_create_transaction()`, `gateway_update_transaction()`, `gateway_confirm_payment()`) with concrete implementations for each gateway (`chapa_initiate()`, `telebirr_initiate()`).

### 2.6.8 PRG (Post-Redirect-Get) Pattern

**Location:** All action files

Every POST action ends with a `redirect()` call to prevent form resubmission:

```php
// Action file:
csrf_protect();
// ... process data ...
set_flash('success', 'Saved.');
redirect(url('module', 'action'));
```

### 2.6.9 Flash Message Pattern

**Location:** `core/helpers.php` → `set_flash()` / `get_flash()`

Success/error messages are stored in `$_SESSION` and displayed on the next page load, then automatically cleared. This works with the PRG pattern.

### 2.6.10 Guard Clause Pattern

**Location:** All files

Direct access to any file (except `public/index.php`) is prevented:

```php
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}
```

---

**End of Part 1**

| Document | Contents |
|----------|----------|
| **PART 1** (this file) | Project Overview, System Architecture |
| **PART 2** | Complete File & Folder Structure, Variable Documentation |
| **PART 3** | Functional Requirements, Non-Functional Requirements, Database Design |
| **PART 4** | API Design, Development Checklist |
| **PART 5** | Development Plan, Coding Standards, Testing Strategy, Deployment Plan |
