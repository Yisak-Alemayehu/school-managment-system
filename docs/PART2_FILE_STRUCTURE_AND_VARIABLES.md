# Urji Beri School Management System — Pre-Development Documentation

## PART 2: COMPLETE FILE & FOLDER STRUCTURE + VARIABLE DOCUMENTATION

**Document Version:** 1.0.0  
**Date:** February 27, 2026  
**Status:** Pre-Development Planning  

---

# TABLE OF CONTENTS — PART 2

3. [Complete File and Folder Structure](#3-complete-file-and-folder-structure)
   - 3.1 Root-Level Structure
   - 3.2 Configuration Files
   - 3.3 Core Library Files
   - 3.4 Module Files (All 11 Modules)
   - 3.5 Template Files
   - 3.6 Public / Static Files
   - 3.7 SQL / Migration Files
   - 3.8 Cron Job Files
   - 3.9 Storage & Utility Files
4. [Complete Variable Documentation](#4-complete-variable-documentation)
   - 4.1 Environment Variables (.env)
   - 4.2 Configuration Constants
   - 4.3 Core Library Variables & Functions
   - 4.4 Session Variables
   - 4.5 Module Action Variables
   - 4.6 Module View Variables

---

# 3. COMPLETE FILE AND FOLDER STRUCTURE

## 3.1 Root-Level Structure

```
urjiberischool system 2026/
├── .env                           ← Environment variables (secrets, credentials)
├── README.md                      ← Project readme
├── config/                        ← Application configuration constants
├── core/                          ← Core library functions (shared infrastructure)
├── cron/                          ← Scheduled background jobs
├── docs/                          ← Documentation files
├── logs/                          ← Application and PHP error logs
├── modules/                       ← Feature modules (business logic)
├── public/                        ← Web-accessible document root
├── sampleCSV/                     ← Sample import files
├── sql/                           ← Database schema and migration files
├── storage/                       ← Server-side storage (backups, temp files)
├── templates/                     ← Shared layout and UI templates
└── uploads/                       ← User-uploaded files (photos, documents)
```

## 3.2 Configuration Files

### config/app.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `config/app.php` |
| **Purpose** | Define all application-wide constants: identity, paths, session, security, upload, pagination, currency, date formats, error handling |
| **Responsibilities** | Set timezone, configure error reporting, define path constants for all directories, set session parameters, define upload limits |
| **Dependencies** | Requires `APP_ROOT` constant (set by `public/index.php`); uses `getenv()` for environment-dependent values; uses `$_SERVER` superglobal |

### config/database.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `config/database.php` |
| **Purpose** | Define MySQL connection constants (host, port, database name, credentials, charset, PDO options) |
| **Responsibilities** | Provide all parameters needed by `core/db.php` to establish a database connection |
| **Dependencies** | Requires `APP_ROOT`; uses `getenv()` for overrides from `.env`; uses `PDO` class constants |

### config/payment.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `config/payment.php` |
| **Purpose** | Define payment gateway configurations (Telebirr, Chapa, Stripe template), transaction states, and timing settings |
| **Responsibilities** | Provide API credentials, callback URLs, timeout values, and transaction state constants for all payment processing |
| **Dependencies** | Requires `APP_ROOT` and `APP_URL` (from `config/app.php`); uses `getenv()` for secret keys |

## 3.3 Core Library Files

### core/env.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `core/env.php` |
| **Purpose** | Parse `.env` files and populate environment variables |
| **Responsibilities** | Read key-value pairs from `.env`, handle quoted values, set `putenv()`, `$_ENV`, `$_SERVER` |
| **Dependencies** | Requires `APP_ROOT`; no other file dependencies |
| **Functions Defined** | `env_load(string $path): void`, `env(string $key, mixed $default = null): mixed` |

### core/db.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `core/db.php` |
| **Purpose** | Centralized database access layer wrapping PDO |
| **Responsibilities** | Connection management (singleton), query execution, CRUD operations, transactions, pagination |
| **Dependencies** | `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`, `DB_OPTIONS` from `config/database.php`; `APP_DEBUG`, `DEFAULT_PER_PAGE`, `MAX_PER_PAGE` from `config/app.php` |
| **Functions Defined** | `db_connect()`, `db_query()`, `db_fetch_all()`, `db_fetch_one()`, `db_fetch_value()`, `db_insert()`, `db_update()`, `db_delete()`, `db_soft_delete()`, `db_count()`, `db_exists()`, `db_begin()`, `db_commit()`, `db_rollback()`, `db_transaction()`, `db_paginate()`, `db_connection()` |

### core/security.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `core/security.php` |
| **Purpose** | Security utilities: XSS prevention, file upload handling, audit logging, rate limiting, HTTP security headers |
| **Responsibilities** | HTML escaping, IP detection, secure file upload with MIME validation, audit trail recording, request rate limiting |
| **Dependencies** | `UPLOAD_PATH`, `UPLOAD_MAX_SIZE`, `UPLOAD_ALLOWED_TYPES`, `SESSION_SECURE` from `config/app.php`; `db_insert()` from `core/db.php`; `auth_user_id()` from `core/auth.php` |
| **Functions Defined** | `e()`, `get_client_ip()`, `handle_upload()`, `delete_upload()`, `audit_log()`, `rate_limit()`, `set_security_headers()` |

### core/auth.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `core/auth.php` |
| **Purpose** | Authentication and session management for all user operations |
| **Responsibilities** | Session initialization, login attempt validation with lockout, permission loading, session data management, password reset, logout, brute-force protection |
| **Dependencies** | `db_fetch_one()`, `db_fetch_all()`, `db_fetch_value()`, `db_query()`, `db_insert()`, `db_update()` from `core/db.php`; `get_client_ip()` from `core/security.php`; `set_flash()`, `redirect()` from `core/helpers.php`/`core/router.php`; session/security constants from `config/app.php` |
| **Functions Defined** | `auth_init_session()`, `auth_attempt()`, `auth_load_permissions()`, `auth_check()`, `auth_user_id()`, `auth_user()`, `auth_has_permission()`, `auth_has_any_permission()`, `auth_has_role()`, `auth_is_super_admin()`, `auth_require()`, `auth_require_permission()`, `auth_logout()`, `auth_create_reset_token()`, `auth_reset_password()`, `auth_log_attempt()`, `auth_check_lockout()`, `require_permission()`, `current_user()`, `current_user_id()` |

### core/csrf.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `core/csrf.php` |
| **Purpose** | Cross-Site Request Forgery protection for all state-changing operations |
| **Responsibilities** | Generate CSRF tokens, create hidden form fields and meta tags, validate tokens on POST/PUT/PATCH/DELETE, regenerate tokens after validation |
| **Dependencies** | `CSRF_TOKEN_NAME` from `config/app.php`; `is_ajax_request()`, `json_response()` from `core/response.php`; `set_flash()` from `core/helpers.php`; `redirect()` from `core/router.php` |
| **Functions Defined** | `csrf_generate()`, `csrf_field()`, `csrf_meta()`, `csrf_validate()`, `csrf_protect()`, `verify_csrf()`, `verify_csrf_get()` |

### core/validation.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `core/validation.php` |
| **Purpose** | Input validation engine and sanitization utilities |
| **Responsibilities** | Validate form data against rules (required, email, min, max, numeric, date, unique, in, confirmed, password), sanitize input, provide typed input helpers |
| **Dependencies** | `db_fetch_value()` from `core/db.php`; `PASSWORD_MIN_LENGTH`, `UPLOAD_MAX_SIZE`, `UPLOAD_ALLOWED_TYPES` from `config/app.php` |
| **Functions Defined** | `validate()`, `validate_rule()`, `validate_file()`, `sanitize_input()`, `input()`, `input_int()`, `input_array()` |

### core/helpers.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `core/helpers.php` |
| **Purpose** | General-purpose utility functions used across all modules |
| **Responsibilities** | Flash messages, old input preservation, form error handling, date/currency formatting, pagination HTML, settings retrieval, active session/term lookup, notification creation, string utilities |
| **Dependencies** | `db_fetch_value()`, `db_fetch_one()`, `db_count()`, `db_insert()` from `core/db.php`; `auth_user_id()`, `auth_has_permission()` from `core/auth.php`; `route_is()` from `core/router.php`; constants from `config/app.php` |
| **Functions Defined** | `set_flash()`, `get_flash()`, `has_flash()`, `get_all_flash()`, `set_old_input()`, `old()`, `clear_old_input()`, `set_validation_errors()`, `get_validation_errors()`, `get_error()`, `get_validation_error()`, `has_error()`, `format_date()`, `format_datetime()`, `time_ago()`, `format_money()`, `upload_url()`, `slugify()`, `truncate()`, `generate_code()`, `generate_invoice_no()`, `generate_receipt_no()`, `pagination_html()`, `get_setting()`, `get_school_name()`, `get_active_session()`, `get_active_session_id()`, `get_active_term()`, `get_active_term_id()`, `create_notification()`, `get_unread_notification_count()`, `dd()`, `array_pick()`, `is_active_nav()`, `format_currency()`, `render_pagination()`, `has_permission()` |

### core/response.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `core/response.php` |
| **Purpose** | HTTP response utilities for JSON, AJAX detection, and view rendering |
| **Responsibilities** | Send JSON responses with status codes, detect AJAX requests, check HTTP method, render views with layout wrapping |
| **Dependencies** | `TEMPLATES_PATH`, `MODULES_PATH` from `config/app.php` |
| **Functions Defined** | `json_response()`, `is_ajax_request()`, `is_post()`, `is_get()`, `render()`, `partial()`, `module_view()` |

### core/router.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `core/router.php` |
| **Purpose** | URL routing and request dispatching to modules |
| **Responsibilities** | Parse `$_GET['module']` and `$_GET['action']`, sanitize module name, load module routes.php, provide URL generation and redirect helpers |
| **Dependencies** | `APP_URL`, `MODULES_PATH`, `TEMPLATES_PATH` from `config/app.php`; `auth_require()` from `core/auth.php`; `is_ajax_request()`, `json_response()` from `core/response.php` |
| **Functions Defined** | `router_dispatch()`, `route_info()`, `current_module()`, `current_action()`, `route_id()`, `sanitize_module_name()`, `router_not_found()`, `url()`, `module_url()`, `redirect()`, `redirect_back()`, `route_is()` |

### core/rbac.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `core/rbac.php` |
| **Purpose** | Role-based data access control — filter data based on user role |
| **Responsibilities** | Determine teacher's assigned classes/sections/subjects, retrieve student's own record, retrieve parent's linked children, check accountant finance access, provide SQL filter fragments |
| **Dependencies** | `auth_is_super_admin()`, `auth_has_role()`, `auth_user_id()`, `auth_has_permission()` from `core/auth.php`; `db_fetch_all()`, `db_fetch_one()` from `core/db.php`; `get_active_session()` from `core/helpers.php` |
| **Functions Defined** | `rbac_teacher_class_ids()`, `rbac_teacher_section_ids()`, `rbac_teacher_subject_ids()`, `rbac_teacher_has_class()`, `rbac_require_teacher_class()`, `rbac_get_student()`, `rbac_student_id()`, `rbac_get_children()`, `rbac_children_ids()`, `rbac_parent_has_child()`, `rbac_can_access_finance()`, `rbac_can_manage_finance()`, `rbac_class_filter()`, `rbac_student_filter()`, `rbac_clear_cache()` |

### core/pwa.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `core/pwa.php` |
| **Purpose** | Progressive Web App integration helpers |
| **Responsibilities** | Generate PWA meta tags (manifest link, theme color, apple icons), service worker registration script, PWA install status indicator |
| **Dependencies** | `e()` from `core/security.php`; `get_school_name()` from `core/helpers.php`; `url()` from `core/router.php` |
| **Functions Defined** | `pwa_meta_tags()`, `pwa_register_script()`, `pwa_status_indicator()` |

### core/payment_gateway.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `core/payment_gateway.php` |
| **Purpose** | Abstract payment transaction management |
| **Responsibilities** | Create transaction records with idempotency keys, update transaction status, confirm payment and update invoice/payment records, retrieve gateway configuration |
| **Dependencies** | `db_insert()`, `db_update()`, `db_fetch_one()`, `db_connection()` from `core/db.php`; `audit_log()` from `core/security.php`; `CURRENCY` from `config/app.php` |
| **Functions Defined** | `gateway_create_transaction()`, `gateway_update_transaction()`, `gateway_confirm_payment()`, `gateway_config()` |

### core/gateways/chapa.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `core/gateways/chapa.php` |
| **Purpose** | Chapa payment gateway integration |
| **Responsibilities** | Initiate Chapa payment (POST to Chapa API), verify Chapa payment status |
| **Dependencies** | `db_fetch_one()` from `core/db.php`; `gateway_update_transaction()` from `core/payment_gateway.php`; `APP_URL` from `config/app.php`; `CHAPA_CONFIG` from `config/payment.php` |
| **Functions Defined** | `chapa_initiate()`, `chapa_verify()` |

### core/gateways/telebirr.php

| Attribute | Value |
|-----------|-------|
| **Full Path** | `core/gateways/telebirr.php` |
| **Purpose** | Telebirr mobile money payment gateway integration |
| **Responsibilities** | Initiate Telebirr payment (RSA encryption of parameters, POST to Telebirr API), verify/decrypt Telebirr callback payloads |
| **Dependencies** | `gateway_update_transaction()` from `core/payment_gateway.php`; `APP_URL` from `config/app.php`; `TELEBIRR_CONFIG` from `config/payment.php`; OpenSSL PHP extension |
| **Functions Defined** | `telebirr_initiate()`, `telebirr_verify()` |

## 3.4 Module Files (All 11 Modules)

### 3.4.1 Academics Module

```
modules/academics/
├── routes.php
├── actions/
│   ├── class_save.php
│   ├── class_subjects_save.php
│   ├── class_teacher_save.php
│   ├── elective_subjects_save.php
│   ├── medium_save.php
│   ├── promote_save.php
│   ├── section_save.php
│   ├── session_save.php
│   ├── session_toggle.php
│   ├── shift_save.php
│   ├── stream_save.php
│   ├── subject_save.php
│   ├── subject_teacher_delete.php
│   ├── subject_teacher_save.php
│   ├── term_save.php
│   └── timetable_save.php
└── views/
    ├── classes.php
    ├── class_subjects.php
    ├── class_teachers.php
    ├── elective_subjects.php
    ├── mediums.php
    ├── promote.php
    ├── sections.php
    ├── sessions.php
    ├── shifts.php
    ├── streams.php
    ├── subjects.php
    ├── subject_teachers.php
    ├── terms.php
    └── timetable.php
```

**File Details — Academics Module:**

| File | Purpose | Responsibilities | Dependencies |
|------|---------|-----------------|-------------|
| `routes.php` | Route dispatcher with 27 route cases | Parse action, enforce permissions (`academics.manage`, `timetable.view`, `timetable.manage`), load views/actions | `auth_require()`, `auth_require_permission()`, `current_action()`, `is_post()`, `redirect()`, `url()` |
| `actions/class_save.php` | Create/update class records | Validate name, generate slug, check duplicates, insert/update `classes` table, support medium/stream/shift assignment | `csrf_protect()`, `input()`, `input_int()`, `validate()`, `db_insert()`, `db_update()`, `db_fetch_one()`, `audit_log()`, `set_flash()`, `redirect()` |
| `actions/class_subjects_save.php` | Assign subjects to a class for a session | Delete existing assignments, insert new class_subjects with `is_elective` flag, transaction-wrapped | `csrf_protect()`, `input_int()`, `input_array()`, `db_begin()`, `db_delete()`, `db_insert()`, `db_commit()`, `db_rollback()`, `audit_log()` |
| `actions/class_teacher_save.php` | Assign/remove homeroom teachers | Handle delete by ID, upsert class teacher assignment, set `is_class_teacher=1`, `subject_id=NULL` | `csrf_protect()`, `input_int()`, `db_delete()`, `db_fetch_one()`, `db_update()`, `db_insert()`, `audit_log()` |
| `actions/elective_subjects_save.php` | Assign elective subjects to individual students | Validate elective class_subject IDs, delete old assignments, insert new per-student elective choices, transaction-wrapped | `csrf_protect()`, `input_int()`, `input_array()`, `db_fetch_all()`, `db_begin()`, `db_query()`, `db_insert()`, `db_commit()`, `db_rollback()`, `audit_log()` |
| `actions/medium_save.php` | Create/update medium of instruction | Validate name, insert/update `mediums` table | `csrf_protect()`, `input()`, `input_int()`, `validate()`, `db_insert()`, `db_update()`, `audit_log()`, `set_flash()`, `redirect()` |
| `actions/promote_save.php` | Bulk-promote students between sessions | Process each student: close old enrollment, create new enrollment (or graduate), record in `promotions` table, handle promoted/repeated/graduated statuses | `csrf_protect()`, `input_int()`, `input_array()`, `current_user_id()`, `db_begin()`, `db_fetch_one()`, `db_update()`, `db_insert()`, `db_commit()`, `db_rollback()`, `audit_log()` |
| `actions/section_save.php` | Create/update sections | Validate class_id + name, check duplicate name within class, insert/update `sections` table | `csrf_protect()`, `input()`, `input_int()`, `validate()`, `db_fetch_one()`, `db_insert()`, `db_update()`, `audit_log()` |
| `actions/session_save.php` | Create/update academic sessions | Validate name/dates, generate slug, handle `is_active` toggling (deactivate all others), ensure slug uniqueness | `csrf_protect()`, `input()`, `input_int()`, `validate()`, `db_update()`, `db_insert()`, `db_fetch_one()`, `audit_log()` |
| `actions/session_toggle.php` | Toggle session active/inactive | Deactivate all sessions then activate selected, or deactivate selected | `csrf_protect()`, `input_int()`, `db_fetch_one()`, `db_update()`, `db_query()`, `audit_log()` |
| `actions/shift_save.php` | Create/update shifts | Validate name, insert/update `shifts` table with start/end times | `csrf_protect()`, `input()`, `input_int()`, `validate()`, `db_insert()`, `db_update()`, `audit_log()` |
| `actions/stream_save.php` | Create/update academic streams | Validate name, insert/update `streams` table with description | `csrf_protect()`, `input()`, `input_int()`, `validate()`, `db_insert()`, `db_update()`, `audit_log()` |
| `actions/subject_save.php` | Create/update subjects | Validate name + unique code, enforce type enum (theory/practical/both), insert/update `subjects` table | `csrf_protect()`, `input()`, `input_int()`, `validate()`, `db_fetch_one()`, `db_insert()`, `db_update()`, `audit_log()` |
| `actions/subject_teacher_delete.php` | Delete subject teacher assignment | Verify record exists with `is_class_teacher=0`, delete from `class_teachers` | `csrf_protect()`, `input_int()`, `db_fetch_one()`, `db_delete()`, `audit_log()` |
| `actions/subject_teacher_save.php` | Create/update subject teacher assignments | Validate all IDs, check for duplicate assignment, insert/update `class_teachers` with `is_class_teacher=0` | `csrf_protect()`, `input_int()`, `validate()`, `db_fetch_one()`, `db_insert()`, `db_update()`, `audit_log()` |
| `actions/term_save.php` | Create/update terms within a session | Validate session_id + name + dates, generate slug, enforce end > start date, insert/update `terms` table | `csrf_protect()`, `input()`, `input_int()`, `validate()`, `db_insert()`, `db_update()`, `audit_log()` |
| `actions/timetable_save.php` | Create/delete timetable slots | Handle delete by ID, validate day/times/class/subject, enforce end > start time, insert into `timetables` | `csrf_protect()`, `input_int()`, `input()`, `validate()`, `db_fetch_one()`, `db_query()`, `db_insert()`, `audit_log()` |
| `views/sessions.php` | Academic session list + add/edit form | Fetch all sessions, handle edit mode, render table with active toggle | `db_fetch_all()`, `db_fetch_one()`, `url()`, `csrf_field()`, `old()`, `get_error()`, `e()` |
| `views/terms.php` | Term list + add/edit form | Fetch terms filtered by session, render table + form with date pickers | `db_fetch_all()`, `db_fetch_one()`, `input_int()`, `url()`, `csrf_field()`, `old()` |
| `views/classes.php` | Class list + add/edit form with medium/stream/shift dropdowns | Fetch classes with joins to mediums/streams/shifts, render table + form | `db_fetch_all()`, `db_fetch_one()`, `url()`, `csrf_field()`, `old()`, `get_error()` |
| `views/sections.php` | Section list filtered by class + add/edit form | Fetch sections by class_id, render table + form with class dropdown | `db_fetch_all()`, `db_fetch_one()`, `input_int()`, `url()`, `csrf_field()` |
| `views/subjects.php` | Subject list + add/edit form | Fetch all subjects, show class count, render form with type dropdown | `db_fetch_all()`, `db_fetch_one()`, `url()`, `csrf_field()` |
| `views/mediums.php` | Medium list + inline add/edit | Fetch all mediums, render table + inline form | `db_fetch_all()`, `db_fetch_one()`, `url()`, `csrf_field()` |
| `views/streams.php` | Stream list + inline add/edit | Fetch all streams, render table + form | `db_fetch_all()`, `db_fetch_one()`, `url()`, `csrf_field()` |
| `views/shifts.php` | Shift list with time display + form | Fetch all shifts, render table with start/end + form | `db_fetch_all()`, `db_fetch_one()`, `url()`, `csrf_field()` |
| `views/class_subjects.php` | Class-subject assignment matrix | Fetch classes, subjects, current assignments, render checkbox matrix | `db_fetch_all()`, `input_int()`, `url()`, `csrf_field()` |
| `views/elective_subjects.php` | Student-elective assignment matrix | Fetch elective subjects for class, enrolled students, current selections | `db_fetch_all()`, `input_int()`, `url()`, `csrf_field()` |
| `views/class_teachers.php` | Class teacher assignment table + form | Fetch classes, sections, teachers, current assignments | `db_fetch_all()`, `input_int()`, `url()`, `csrf_field()` |
| `views/subject_teachers.php` | Subject teacher assignment table + form | Fetch assignments with joins, teachers, subjects | `db_fetch_all()`, `url()`, `csrf_field()` |
| `views/promote.php` | Student promotion form | Fetch sessions, classes, sections, enrolled students with checkboxes | `db_fetch_all()`, `input_int()`, `url()`, `csrf_field()` |
| `views/timetable.php` | Weekly timetable grid | Fetch slots by class/section, render day×period grid | `db_fetch_all()`, `input_int()`, `url()`, `csrf_field()` |

### 3.4.2 API Module

```
modules/api/
└── routes.php
```

| File | Purpose | Responsibilities | Dependencies |
|------|---------|-----------------|-------------|
| `routes.php` | Internal JSON API endpoints | Serve AJAX requests: sections by class_id, subjects by class_id + session_id; set JSON headers, no-cache | `auth_require()`, `input_int()`, `db_fetch_all()`, `json_encode()` |

### 3.4.3 Attendance Module

```
modules/attendance/
├── routes.php
├── actions/
│   └── save.php
└── views/
    ├── report.php
    ├── student.php
    ├── take.php
    └── view.php
```

| File | Purpose | Responsibilities | Dependencies |
|------|---------|-----------------|-------------|
| `routes.php` | 5-route dispatcher | Route index/save/report/view/student, enforce `attendance.view`/`attendance.manage` | `current_action()`, `auth_require_permission()` |
| `actions/save.php` | Save daily attendance records | Validate date (not future), upsert attendance per student (present/absent/late/excused), transaction-wrapped | `csrf_protect()`, `input_int()`, `input()`, `auth_user()`, `get_active_term()`, `db_begin()`, `db_fetch_one()`, `db_update()`, `db_insert()`, `db_commit()`, `db_rollback()`, `audit_log()` |
| `views/take.php` | Take attendance UI | Fetch enrolled students by class/section/date, show existing records, render radio buttons | `db_fetch_all()`, `input_int()`, `input()`, `get_active_session()`, `url()`, `csrf_field()` |
| `views/view.php` | View daily attendance | Display read-only attendance records with summary stats | `db_fetch_all()`, `input_int()`, `input()` |
| `views/report.php` | Date-range attendance report | Fetch attendance in date range, render matrix (students × dates), A4 print layout | `db_fetch_all()`, `input_int()`, `input()` |
| `views/student.php` | Individual student attendance history | Fetch full attendance for one student, show summary (present/absent/late/excused counts) | `db_fetch_all()`, `db_fetch_one()`, `input_int()` |

### 3.4.4 Auth Module

```
modules/auth/
├── routes.php
├── actions/
│   ├── change_password.php
│   ├── forgot_password.php
│   ├── login.php
│   ├── reset_password.php
│   └── update_profile.php
└── views/
    ├── change_password.php
    ├── forgot_password.php
    ├── login.php
    ├── profile.php
    └── reset_password.php
```

| File | Purpose | Responsibilities | Dependencies |
|------|---------|-----------------|-------------|
| `routes.php` | 6-route dispatcher for auth | Login (GET/POST), logout, forgot-password, reset-password, change-password, profile | `current_action()`, `is_post()`, `auth_check()`, `auth_require()`, `csrf_protect()`, `auth_logout()`, `redirect()` |
| `actions/login.php` | Process login form | Validate credentials, call `auth_attempt()`, redirect to intended URL or dashboard | `csrf_protect()`, `validate()`, `input()`, `auth_attempt()`, `redirect()`, `set_flash()`, `set_old_input()` |
| `actions/forgot_password.php` | Process forgot password | Validate email, create reset token, log reset URL (email TODO), show generic message to prevent enumeration | `csrf_protect()`, `validate()`, `input()`, `db_fetch_one()`, `auth_create_reset_token()`, `audit_log()` |
| `actions/reset_password.php` | Process password reset | Validate token + new password, call `auth_reset_password()`, redirect to login | `csrf_protect()`, `validate()`, `input()`, `auth_reset_password()` |
| `actions/change_password.php` | Process password change | Verify current password, hash new password with bcrypt cost 12, update user record | `auth_require()`, `csrf_protect()`, `validate()`, `input()`, `auth_user()`, `db_fetch_one()`, `password_verify()`, `password_hash()`, `db_update()`, `audit_log()` |
| `actions/update_profile.php` | Process profile update | Validate name/email/phone, check email uniqueness, handle avatar upload w/ old avatar deletion, update session data | `auth_require()`, `csrf_protect()`, `validate()`, `input()`, `auth_user()`, `handle_upload()`, `delete_upload()`, `db_update()`, `db_fetch_one()`, `audit_log()` |
| `views/login.php` | Login page (standalone layout) | Render username/password form with school branding, CSRF field | `csrf_field()`, `old()`, `get_flash()`, `APP_NAME` |
| `views/forgot_password.php` | Forgot password page (standalone) | Render email input form | `csrf_field()`, `get_flash()`, `APP_NAME` |
| `views/reset_password.php` | Password reset page (standalone) | Render new password + confirm fields with token hidden | `csrf_field()`, `get_flash()` |
| `views/change_password.php` | Password change page (authenticated) | Render current + new + confirm fields | `csrf_field()`, `auth_user()`, `get_error()` |
| `views/profile.php` | Profile edit page (authenticated) | Render name, email, phone, avatar upload with preview | `csrf_field()`, `auth_user()`, `db_fetch_one()`, `old()`, `get_error()`, `upload_url()` |

### 3.4.5 Communication Module

```
modules/communication/
├── routes.php
├── actions/
│   ├── announcement_delete.php
│   ├── announcement_save.php
│   ├── message_send.php
│   ├── notification_read.php
│   └── notification_read_all.php
└── views/
    ├── announcements.php
    ├── announcement_form.php
    ├── announcement_view.php
    ├── compose.php
    ├── inbox.php
    ├── message_view.php
    ├── notifications.php
    └── sent.php
```

| File | Purpose | Responsibilities |
|------|---------|-----------------|
| `routes.php` | 11-route dispatcher | Announcements CRUD, messages (inbox/sent/compose/view/send), notifications (list/read/read-all) |
| `actions/announcement_save.php` | Create/update announcement | Validate title + content, set target_audience/status/is_pinned, insert/update `announcements` |
| `actions/announcement_delete.php` | Delete announcement | Validate ID, delete from `announcements`, audit log |
| `actions/message_send.php` | Send message | Validate receiver exists, prevent self-messaging, insert into `messages`, create notification for receiver |
| `actions/notification_read.php` | Mark notification as read | Update `read_at` timestamp on `notifications` |
| `actions/notification_read_all.php` | Mark all notifications as read | Bulk update all unread notifications for current user |
| `views/announcements.php` | Announcement listing | Role-filtered list with pinned badges, pagination |
| `views/announcement_form.php` | Create/edit announcement form | Title, content (textarea), target audience, status, publish date |
| `views/announcement_view.php` | View single announcement | Full announcement with author and edit link |
| `views/compose.php` | Compose message form | Recipient select, subject, body, reply prefill support |
| `views/inbox.php` | Received messages list | Paginated list with read/unread status |
| `views/message_view.php` | View single message | Message detail with reply/delete actions |
| `views/notifications.php` | Notification list | Paginated notifications with mark-all-read button |
| `views/sent.php` | Sent messages list | Paginated sent messages |

### 3.4.6 Dashboard Module

```
modules/dashboard/
├── routes.php
└── views/
    └── index.php
```

| File | Purpose | Responsibilities |
|------|---------|-----------------|
| `routes.php` | Single-route dispatcher | Load dashboard view for authenticated users |
| `views/index.php` | Role-based dashboard (706 lines) | Detect user role, query role-specific stats (student count, teacher count, classes, revenue, attendance summary), render stat cards + quick action links |

### 3.4.7 Exams Module

```
modules/exams/
├── routes.php
├── actions/
│   ├── assessment_delete.php
│   ├── assessment_save.php
│   ├── assignment_delete.php
│   ├── assignment_save.php
│   ├── conduct_save.php
│   ├── exam_save.php
│   ├── exam_schedule_save.php
│   ├── grade_scale_save.php
│   ├── marks_save.php
│   ├── report_card_generate.php
│   └── results_save.php
└── views/
    ├── assessment_add.php
    ├── assignments.php
    ├── assignment_form.php
    ├── assignment_view.php
    ├── enter_conduct.php
    ├── enter_results.php
    ├── exams.php
    ├── exam_schedule.php
    ├── grade_scale.php
    ├── marks.php
    ├── report_cards.php
    ├── report_card_print.php
    ├── result_analysis.php
    ├── result_cards.php
    └── roster.php
```

| File | Purpose | Responsibilities |
|------|---------|-----------------|
| `routes.php` | 19-route dispatcher + 2 AJAX endpoints | Route assignments, exams, marks, assessments, conduct, results, report cards, grade scale, roster, result analysis; inline AJAX for subject totals and subjects list |
| `actions/assessment_save.php` | Create assessment definitions | Validate class/subject/term, enforce total marks ≤ 100 cap, insert into `assessments` |
| `actions/assessment_delete.php` | Delete assessment + linked results | Delete from `student_results` then `assessments`, transaction-wrapped |
| `actions/assignment_save.php` | Create/update homework assignments | Validate class/subject/title/dates, handle file attachment upload, insert/update `assignments` |
| `actions/assignment_delete.php` | Delete assignment | Remove assignment from `assignments` |
| `actions/conduct_save.php` | Save student conduct grades | Validate grades (A-F), upsert `student_conduct` per student per term |
| `actions/exam_save.php` | Create/update exam definitions | Validate name/term, insert/update `exams` |
| `actions/exam_schedule_save.php` | Create/update exam schedules | Validate exam/class/subject/date/time, insert/update `exam_schedules` |
| `actions/grade_scale_save.php` | Save grade scale entries | Validate min/max/letter/GPA, upsert `grade_scale_entries` |
| `actions/marks_save.php` | Save exam marks | Validate marks per student, upsert `marks` |
| `actions/report_card_generate.php` | Generate report cards | Calculate totals/averages/ranks, insert into `report_cards` |
| `actions/results_save.php` | Save assessment results | Validate marks per student per assessment, upsert `student_results` |
| `views/enter_results.php` | 6-step marks entry wizard | Term → Class → Section → Subject → Assessment → Marks input |
| `views/roster.php` | Full class roster | All subjects × students matrix with totals, averages, ranks, conduct |
| `views/result_cards.php` | Printable A4 portrait report cards | Per-student report cards with QR verification code |
| `views/result_analysis.php` | Grade distribution analysis | Statistical breakdown by grade ranges and gender |

### 3.4.8 Finance Module

```
modules/finance/
├── routes.php
├── actions/
│   ├── discount_save.php
│   ├── discount_delete.php
│   ├── fee_category_save.php
│   ├── fee_category_delete.php
│   ├── fee_structure_save.php
│   ├── fee_structure_delete.php
│   ├── fm_api_fee_students.php
│   ├── fm_api_students.php
│   ├── fm_assignment_delete.php
│   ├── fm_assignment_save.php
│   ├── fm_charge_waive.php
│   ├── fm_exemption_delete.php
│   ├── fm_exemption_save.php
│   ├── fm_fee_delete.php
│   ├── fm_fee_duplicate.php
│   ├── fm_fee_save.php
│   ├── fm_fee_toggle.php
│   ├── fm_group_delete.php
│   ├── fm_group_member_add.php
│   ├── fm_group_member_remove.php
│   ├── fm_group_save.php
│   ├── fm_payment_save.php
│   ├── fm_report_export.php
│   ├── invoice_delete.php
│   ├── invoice_generate.php
│   ├── payment_callback.php
│   ├── payment_initiate.php
│   └── payment_save.php
└── views/
    ├── discounts.php
    ├── discount_form.php
    ├── fee_categories.php
    ├── fee_report.php
    ├── fee_structures.php
    ├── fee_structure_form.php
    ├── fm_assign_fees.php
    ├── fm_dashboard.php
    ├── fm_fee_form.php
    ├── fm_fee_view.php
    ├── fm_generate_invoice.php
    ├── fm_groups.php
    ├── fm_group_form.php
    ├── fm_group_members.php
    ├── fm_manage_fees.php
    ├── fm_payment.php
    ├── fm_reports.php
    ├── invoices.php
    ├── invoice_form.php
    ├── invoice_print.php
    ├── invoice_view.php
    ├── payments.php
    ├── payment_form.php
    ├── payment_receipt.php
    └── pay_online.php
```

*(36 routes — the largest module in the system)*

### 3.4.9 Settings Module

```
modules/settings/
├── routes.php
├── actions/
│   └── general_save.php
└── views/
    ├── audit_logs.php
    ├── backup.php
    └── general.php
```

| File | Purpose | Responsibilities |
|------|---------|-----------------|
| `routes.php` | 4-route dispatcher | General settings, general-save, audit-logs, backup |
| `actions/general_save.php` | Save system settings | Iterate key-value pairs, upsert `settings` table |
| `views/general.php` | Settings form | Auto-generated form from grouped settings in DB |
| `views/audit_logs.php` | Audit log viewer | Filterable table: user, action, module, date range |
| `views/backup.php` | Database backup management | Trigger backup, list existing files, download/restore |

### 3.4.10 Students Module

```
modules/students/
├── routes.php
├── actions/
│   ├── bulk_import.php
│   ├── delete.php
│   ├── export.php
│   ├── generate_credentials.php
│   ├── promote.php
│   ├── reset_student_password.php
│   ├── sample_csv.php
│   ├── save_roll_numbers.php
│   ├── store.php
│   └── update.php
└── views/
    ├── bulk_import.php
    ├── create.php
    ├── credentials.php
    ├── details.php
    ├── edit.php
    ├── id_cards.php
    ├── index.php
    ├── promote.php
    ├── reset_password.php
    ├── roll_numbers.php
    └── view.php
```

| File | Purpose | Responsibilities |
|------|---------|-----------------|
| `routes.php` | 14-route dispatcher | Index, create, edit, view, delete, promote, admission, roll-numbers, details, id-cards, credentials, reset-password, bulk-import, sample-csv, enroll, export |
| `actions/store.php` | Create new student | Validate all fields (name, DOB, gender, address), handle photo upload, insert into `students`, create enrollment, link guardians, audit log |
| `actions/update.php` | Update existing student | Validate + update `students` record, handle photo re-upload, update guardian links |
| `actions/delete.php` | Soft-delete student | Set `deleted_at` timestamp on `students` record |
| `actions/bulk_import.php` | CSV bulk import | Parse uploaded CSV, validate rows, create students + enrollments in transaction, report success/error counts |
| `actions/export.php` | Export student data | Query enrolled students with filters, output as CSV download |
| `actions/generate_credentials.php` | Generate usernames/passwords | Create user accounts for students with auto-generated credentials |
| `actions/promote.php` | Promote individual student | Close current enrollment, create new enrollment |
| `actions/reset_student_password.php` | Reset student user password | Generate new password hash, update user record |
| `actions/sample_csv.php` | Download sample CSV template | Output CSV header row as downloadable file |
| `actions/save_roll_numbers.php` | Assign roll numbers | Update `roll_number` on `enrollments` or `students` for each student in section |
| `views/create.php` | Student admission form (412 lines) | Full form: personal info, photo upload, Ethiopian address fields, dynamic guardian forms |
| `views/edit.php` | Edit student form (419 lines) | Pre-filled edit form with existing photo preview, guardian editing |
| `views/index.php` | Student listing | Paginated table with search + class/section/status filters |
| `views/view.php` | Student profile | Read-only profile: info, enrollment, guardian details |
| `views/details.php` | Detailed student records | Full table with class, section, guardian, status columns |
| `views/bulk_import.php` | CSV import UI | Upload form + sample download + import results display |
| `views/id_cards.php` | ID card generator | Filterable grid of student ID cards (photo, name, class, barcode) |
| `views/credentials.php` | Credential generation UI | Class/section filter + student list with username generation |
| `views/roll_numbers.php` | Roll number assignment | Student list with roll number input fields per section |
| `views/promote.php` | Promotion UI | Source/target session + class selection + student checkboxes |
| `views/reset_password.php` | Password reset UI | Individual + bulk reset forms |

### 3.4.11 Users Module

```
modules/users/
├── routes.php
├── actions/
│   ├── delete.php
│   ├── store.php
│   ├── toggle_status.php
│   └── update.php
└── views/
    ├── create.php
    ├── edit.php
    ├── index.php
    └── view.php
```

| File | Purpose | Responsibilities |
|------|---------|-----------------|
| `routes.php` | 6-route dispatcher | Index, create, edit, view, delete, toggle-status |
| `actions/store.php` | Create new user | Validate name/username/email/password, hash password, insert into `users`, assign roles via `user_roles`, audit log |
| `actions/update.php` | Update existing user | Validate, update user record, re-assign roles |
| `actions/delete.php` | Soft-delete user | Set `deleted_at` on `users` |
| `actions/toggle_status.php` | Toggle active/inactive | Update `status` field on `users` |
| `views/index.php` | User listing | Paginated table with role/status filters |
| `views/create.php` | Create user form | Full name, username, email, phone, password, role checkboxes |
| `views/edit.php` | Edit user form | Pre-filled form with role checkboxes |
| `views/view.php` | User detail view | User profile + last 20 audit log entries |

## 3.5 Template Files

```
templates/
├── layout.php
├── errors/
│   ├── 403.php
│   └── 404.php
└── partials/
    ├── academics_nav.php
    ├── flash.php
    ├── header.php
    ├── mobile_nav.php
    └── sidebar.php
```

| File | Purpose | Responsibilities |
|------|---------|-----------------|
| `layout.php` | Master page layout | HTML skeleton with `<head>` (Tailwind CDN, Font Awesome, CSRF meta, PWA meta), includes header, sidebar, mobile nav, flash messages, injects `$content` from view, includes footer + PWA registration script |
| `errors/403.php` | Forbidden error page | Display "Access Denied" message with link back to dashboard |
| `errors/404.php` | Not Found error page | Display "Page Not Found" message with link back to home |
| `partials/header.php` | Top navigation bar | School logo, school name, notification bell with unread count, user avatar dropdown (profile, change password, logout) |
| `partials/sidebar.php` | Left sidebar navigation | Permission-gated menu links for all modules, active state highlighting, collapsible sections |
| `partials/mobile_nav.php` | Mobile bottom navigation | Responsive mobile menu bar (dashboard, students, attendance, more) |
| `partials/flash.php` | Flash message display | Render success/error/warning/info alerts from session flash data, auto-dismiss after 5 seconds |
| `partials/academics_nav.php` | Academics sub-navigation | Tab navigation within academics module (sessions, terms, classes, sections, subjects, etc.) |

## 3.6 Public / Static Files

```
public/
├── index.php
├── uploads.php
├── manifest.webmanifest
├── service-worker.js
├── offline.html
└── assets/
    ├── css/
    │   └── app.css
    ├── js/
    │   └── app.js
    └── icons/
        ├── icon-72.svg
        └── icon.php
```

| File | Purpose | Responsibilities |
|------|---------|-----------------|
| `index.php` | Front controller / application entry point | Define `APP_ROOT`, load `.env`, load all config files, load all core libraries, set security headers, init session, generate CSRF token, dispatch router |
| `uploads.php` | Secure file proxy | Serve uploaded files from outside document root; validate path (no directory traversal), check MIME type against allowlist, set cache headers |
| `manifest.webmanifest` | PWA manifest | Define app name ("Urji Beri School SMS"), icons, start URL, display mode (standalone), theme color (#1e40af), shortcuts (Dashboard, Students, Attendance) |
| `service-worker.js` | PWA service worker | Cache app shell files on install, serve from cache with network fallback, show offline.html when network unavailable |
| `offline.html` | Offline fallback page | Simple HTML page shown when app is offline and no cached page is available |
| `assets/css/app.css` | Custom application styles | Additional CSS beyond Tailwind utilities (custom components, print styles, animations) |
| `assets/js/app.js` | Client-side JavaScript | AJAX helpers, dynamic dropdown loading, form validation, modal management, sidebar toggle, notification polling |
| `assets/icons/icon-72.svg` | SVG app icon | 72px app icon for PWA |
| `assets/icons/icon.php` | Dynamic icon generator | PHP script that generates PNG icons on-the-fly for different sizes |

## 3.7 SQL / Migration Files

```
sql/
├── schema.sql
├── seed.sql
├── 001_core_auth.sql
├── 002_academics.sql
├── 003_students.sql
├── 004_assessment.sql
├── 005_finance.sql
├── 006_payment_gateway.sql
├── 007_communication.sql
├── 008_system.sql
├── 009_seed_data.sql
├── 010_schema_fix.sql
├── 011_academics_extended.sql
├── 012_results_module.sql
├── 013_student_conduct.sql
├── 014_fix_assessment_unique.sql
├── 015_fee_management.sql
├── 016_fee_management_permissions.sql
├── 017_fee_management_seed.sql
├── 018_rbac_demo_users.sql
├── run_migration.php
├── check_seed.php
└── seed_2026_2027.php
```

| File | Purpose | Tables Created/Affected |
|------|---------|----------------------|
| `schema.sql` | Complete DDL (fresh install, 54 tables) | All tables in dependency order |
| `seed.sql` | Complete seed data (720 lines) | Roles, permissions, users, sessions, classes, subjects, grades, fees, gateways, settings, stored procedures for bulk data |
| `001_core_auth.sql` | Migration: Auth tables | `roles`, `permissions`, `role_permissions`, `users`, `user_roles`, `login_attempts` |
| `002_academics.sql` | Migration: Academic tables | `academic_sessions`, `terms`, `classes`, `sections`, `subjects`, `class_subjects`, `class_teachers`, `timetables` |
| `003_students.sql` | Migration: Student tables | `students`, `guardians`, `student_guardians`, `enrollments`, `promotions`, `student_documents` |
| `004_assessment.sql` | Migration: Assessment tables | `attendance`, `assignments`, `assignment_submissions`, `exams`, `exam_schedules`, `marks`, `grade_scales`, `grade_scale_entries`, `report_cards` |
| `005_finance.sql` | Migration: Finance tables | `fee_categories`, `fee_structures`, `invoices`, `invoice_items`, `payments`, `fee_discounts`, `student_fee_discounts` |
| `006_payment_gateway.sql` | Migration: Payment tables | `payment_gateways`, `payment_transactions`, `payment_attempts`, `payment_webhooks`, `payment_reconciliation_logs`, `invoice_payment_links` |
| `007_communication.sql` | Migration: Communication tables | `announcements`, `messages`, `notifications` |
| `008_system.sql` | Migration: System tables | `audit_logs`, `settings` |
| `009_seed_data.sql` | Seed: Default data | 7 roles, ~50 permissions, admin user, session, classes, subjects, grade scale, fee categories, payment gateways, settings |
| `010_schema_fix.sql` | Fix: Missing columns | Add `full_name`, `is_active` to `users`, `classes`, `sections`, `subjects`, `students`, `guardians` |
| `011_academics_extended.sql` | Migration: Extended academics | `mediums`, `streams`, `shifts`, `student_elective_subjects`; alter `classes` add FK columns |
| `012_results_module.sql` | Migration: Results | `assessments`, `student_results` |
| `013_student_conduct.sql` | Migration: Conduct | `student_conduct` |
| `014_fix_assessment_unique.sql` | Fix: Deduplicate assessments | Heal duplicate data, add unique key to `assessments` |
| `015_fee_management.sql` | Migration: Advanced fees | `fees`, `recurrence_configs`, `penalty_configs`, `student_groups`, `student_group_members`, `fee_assignments`, `fee_exemptions`, `student_fee_charges`, `penalty_charges`, `finance_audit_log` |
| `016_fee_management_permissions.sql` | Seed: Fee permissions | 12 `fee_management.*` permissions, role assignments |
| `017_fee_management_seed.sql` | Seed: Fee sample data | Sample fees, recurrence, penalties, groups, assignments |
| `018_rbac_demo_users.sql` | Seed: Demo users | 6 demo users with all roles, ~25 missing permissions, complete role matrices |
| `run_migration.php` | PHP migration runner | Executes SQL migration files against the database |
| `check_seed.php` | PHP seed verification | Checks all expected seed data exists |
| `seed_2026_2027.php` | PHP seed for 2026/2027 | Creates academic session for new school year |

## 3.8 Cron Job Files

```
cron/
├── fm_penalty_job.php
└── fm_recurrence_job.php
```

| File | Purpose | Schedule | Responsibilities |
|------|---------|----------|-----------------|
| `fm_penalty_job.php` | Calculate late payment penalties | Daily (recommended) | Find overdue `student_fee_charges`, apply penalty rules from `penalty_configs`, insert `penalty_charges`, update charge amounts |
| `fm_recurrence_job.php` | Generate recurring fee charges | Monthly (recommended) | Find active `recurrence_configs`, calculate next due date, generate new `student_fee_charges` for applicable students |

## 3.9 Storage & Utility Files

```
storage/
├── backups/                    ← Database backup .sql files
└── uploads/                    ← (Alternative upload location)

uploads/
└── students/
    └── 2026/                   ← Student photos organized by year/month

sampleCSV/
└── students_import_sample.csv  ← CSV template for bulk student import

logs/                           ← PHP error logs, application logs

docs/
├── CPANEL_DEPLOYMENT.md
├── DEPLOYMENT.md
├── PHASE1_ARCHITECTURE.md
└── SECURITY_CHECKLIST.md
```

---

# 4. COMPLETE VARIABLE DOCUMENTATION

## 4.1 Environment Variables (.env)

| Variable Name | Data Type | Default Value | Description | Scope | Used By |
|--------------|-----------|---------------|-------------|-------|---------|
| `APP_ENV` | string | `production` | Application environment (`development` / `production`) | Environment | `config/app.php` → `APP_ENV` constant |
| `APP_URL` | string | Auto-detected | Full application base URL (e.g., `https://school.example.com`) | Environment | `config/app.php` → `APP_URL` constant |
| `DB_HOST` | string | `localhost` | MySQL server hostname | Environment | `config/database.php` → `DB_HOST` constant |
| `DB_PORT` | string | `3306` | MySQL server port | Environment | `config/database.php` → `DB_PORT` constant |
| `DB_NAME` | string | `urjiberi_school` | MySQL database name | Environment | `config/database.php` → `DB_NAME` constant |
| `DB_USER` | string | `root` | MySQL username | Environment | `config/database.php` → `DB_USER` constant |
| `DB_PASS` | string | (empty) | MySQL password | Environment | `config/database.php` → `DB_PASS` constant |
| `TELEBIRR_APP_ID` | string | (empty) | Telebirr application ID | Environment | `config/payment.php` → `TELEBIRR_CONFIG['app_id']` |
| `TELEBIRR_APP_KEY` | string | (empty) | Telebirr application key | Environment | `config/payment.php` → `TELEBIRR_CONFIG['app_key']` |
| `TELEBIRR_SHORT_CODE` | string | (empty) | Telebirr merchant short code | Environment | `config/payment.php` → `TELEBIRR_CONFIG['short_code']` |
| `TELEBIRR_PUBLIC_KEY` | string | (empty) | Telebirr RSA public key | Environment | `config/payment.php` → `TELEBIRR_CONFIG['public_key']` |
| `TELEBIRR_API_URL` | string | `https://app.ethiomobilemoney.et:2121` | Telebirr API base URL | Environment | `config/payment.php` → `TELEBIRR_CONFIG['api_url']` |
| `CHAPA_SECRET_KEY` | string | (empty) | Chapa API secret key | Environment | `config/payment.php` → `CHAPA_CONFIG['secret_key']` |
| `CHAPA_PUBLIC_KEY` | string | (empty) | Chapa publishable key | Environment | `config/payment.php` → `CHAPA_CONFIG['public_key']` |
| `CHAPA_WEBHOOK_SECRET` | string | (empty) | Chapa webhook verification secret | Environment | `config/payment.php` → `CHAPA_CONFIG['webhook_secret']` |
| `STRIPE_SECRET_KEY` | string | (empty) | Stripe secret key (template only) | Environment | `config/payment.php` → `STRIPE_CONFIG['secret_key']` |
| `STRIPE_PUBLISHABLE_KEY` | string | (empty) | Stripe publishable key (template only) | Environment | `config/payment.php` → `STRIPE_CONFIG['publishable_key']` |
| `STRIPE_WEBHOOK_SECRET` | string | (empty) | Stripe webhook secret (template only) | Environment | `config/payment.php` → `STRIPE_CONFIG['webhook_secret']` |

## 4.2 Configuration Constants

### 4.2.1 config/app.php Constants (36)

| Constant Name | Data Type | Default Value | Description | Scope | Used By |
|--------------|-----------|---------------|-------------|-------|---------|
| `APP_NAME` | string | `'Urji Beri School SMS'` | Display name of the application | Global constant | Layout header, login page, PWA manifest, emails |
| `APP_VERSION` | string | `'1.0.0'` | Current application version | Global constant | Service worker cache key, footer display |
| `APP_ENV` | string | From `getenv('APP_ENV')` or `'production'` | Environment mode | Global constant | `APP_DEBUG` derivation, error reporting |
| `APP_DEBUG` | bool | `APP_ENV === 'development'` | Debug mode flag | Global constant | Error display, verbose error messages |
| `APP_URL` | string | Auto-detected from `$_SERVER` or `getenv('APP_URL')` | Application base URL without trailing slash | Global constant | URL generation (`url()`), payment callback URLs, redirect targets |
| `APP_TIMEZONE` | string | `'Africa/Addis_Ababa'` | Application timezone | Global constant | `date_default_timezone_set()`, all date operations |
| `ROOT_PATH` | string | Same as `APP_ROOT` | Root directory path | Global constant | File path construction |
| `CONFIG_PATH` | string | `APP_ROOT . '/config'` | Configuration directory | Global constant | Config file loading |
| `CORE_PATH` | string | `APP_ROOT . '/core'` | Core library directory | Global constant | Core file loading |
| `MODULES_PATH` | string | `APP_ROOT . '/modules'` | Modules directory | Global constant | Module routing and view loading |
| `TEMPLATES_PATH` | string | `APP_ROOT . '/templates'` | Templates directory | Global constant | Layout, error pages, partials |
| `PUBLIC_PATH` | string | `APP_ROOT . '/public'` | Public document root | Global constant | Static file path references |
| `UPLOAD_PATH` | string | `APP_ROOT . '/uploads'` | Upload storage directory | Global constant | File upload handler, upload URL generation |
| `LOG_PATH` | string | `APP_ROOT . '/logs'` | Log directory | Global constant | Error log configuration |
| `SQL_PATH` | string | `APP_ROOT . '/sql'` | SQL migration directory | Global constant | Migration runner |
| `SESSION_NAME` | string | `'urjiberi_session'` | PHP session cookie name | Global constant | `session_name()` in `auth_init_session()` |
| `SESSION_LIFETIME` | int | `7200` (2 hours) | Session lifetime in seconds | Global constant | `session.gc_maxlifetime`, `session.cookie_lifetime` |
| `SESSION_SECURE` | bool | `true` if HTTPS detected | Secure cookie flag | Global constant | `session.cookie_secure`, security headers |
| `SESSION_HTTPONLY` | bool | `true` | HttpOnly cookie flag | Global constant | `session.cookie_httponly` |
| `SESSION_SAMESITE` | string | `'Lax'` | SameSite cookie attribute | Global constant | `session.cookie_samesite` |
| `LOGIN_MAX_ATTEMPTS` | int | `5` | Maximum failed login attempts before lockout | Global constant | `auth_check_lockout()`, `auth_attempt()` |
| `LOGIN_LOCKOUT_MINUTES` | int | `15` | Account lockout duration in minutes | Global constant | `auth_check_lockout()`, `auth_attempt()` |
| `PASSWORD_MIN_LENGTH` | int | `8` | Minimum password length | Global constant | `validate()` password rule |
| `CSRF_TOKEN_NAME` | string | `'_csrf_token'` | CSRF token field/header name | Global constant | All CSRF functions |
| `UPLOAD_MAX_SIZE` | int | `5242880` (5MB) | Maximum upload size in bytes | Global constant | `handle_upload()`, `validate_file()` |
| `UPLOAD_ALLOWED_TYPES` | array | `['image/jpeg', 'image/png', ...]` | Allowed MIME types for uploads | Global constant | `handle_upload()`, `uploads.php` |
| `DEFAULT_PER_PAGE` | int | `20` | Default pagination page size | Global constant | `db_paginate()`, all paginated views |
| `ITEMS_PER_PAGE` | int | Same as `DEFAULT_PER_PAGE` | Alias for default per page | Global constant | Legacy compatibility |
| `MAX_PER_PAGE` | int | `100` | Maximum allowed page size | Global constant | `db_paginate()` |
| `DEFAULT_CURRENCY` | string | `'ETB'` | ISO currency code | Global constant | Payment processing |
| `CURRENCY_SYMBOL` | string | `'Br'` | Currency display symbol | Global constant | `format_money()` |
| `CURRENCY` | string | Same as `DEFAULT_CURRENCY` | Alias for currency code | Global constant | Payment gateway integration |
| `DATE_FORMAT_DISPLAY` | string | `'d M Y'` | Date format for UI display | Global constant | `format_date()` |
| `DATE_FORMAT_DB` | string | `'Y-m-d'` | Date format for database | Global constant | Date value storage |
| `DATETIME_FORMAT_DISPLAY` | string | `'d M Y H:i'` | DateTime format for UI | Global constant | `format_datetime()` |
| `DATETIME_FORMAT_DB` | string | `'Y-m-d H:i:s'` | DateTime format for database | Global constant | DateTime value storage |

### 4.2.2 config/database.php Constants (8)

| Constant Name | Data Type | Default Value | Description | Scope | Used By |
|--------------|-----------|---------------|-------------|-------|---------|
| `DB_HOST` | string | From `getenv('DB_HOST')` or `'localhost'` | MySQL server hostname | Global constant | `db_connect()` DSN |
| `DB_PORT` | string | From `getenv('DB_PORT')` or `'3306'` | MySQL server port | Global constant | `db_connect()` DSN |
| `DB_NAME` | string | From `getenv('DB_NAME')` or `'urjiberi_school'` | MySQL database name | Global constant | `db_connect()` DSN |
| `DB_USER` | string | From `getenv('DB_USER')` or `'root'` | MySQL username | Global constant | `db_connect()` PDO constructor |
| `DB_PASS` | string | From `getenv('DB_PASS')` or `''` | MySQL password | Global constant | `db_connect()` PDO constructor |
| `DB_CHARSET` | string | `'utf8mb4'` | MySQL charset | Global constant | `db_connect()` DSN |
| `DB_COLLATION` | string | `'utf8mb4_unicode_ci'` | MySQL collation | Global constant | `db_connect()` init command |
| `DB_OPTIONS` | array | PDO options array | PDO configuration (exception mode, fetch assoc, no emulation) | Global constant | `db_connect()` PDO constructor |

### 4.2.3 config/payment.php Constants (12)

| Constant Name | Data Type | Default Value | Description | Scope | Used By |
|--------------|-----------|---------------|-------------|-------|---------|
| `PAYMENT_ACTIVE_GATEWAYS` | array | `['telebirr']` | Enabled payment gateways | Global constant | Payment initiation logic |
| `TELEBIRR_CONFIG` | array | Config array with 9 keys | Telebirr API configuration | Global constant | `telebirr_initiate()`, `telebirr_verify()` |
| `CHAPA_CONFIG` | array | Config array with 7 keys | Chapa API configuration | Global constant | `chapa_initiate()`, `chapa_verify()` |
| `STRIPE_CONFIG` | array | Config array with 7 keys | Stripe API configuration (template) | Global constant | Not active in v1.0 |
| `PAYMENT_IDEMPOTENCY_TTL` | int | `86400` (24 hours) | Idempotency key TTL in seconds | Global constant | `gateway_create_transaction()` |
| `PAYMENT_RECONCILIATION_INTERVAL` | int | `900` (15 minutes) | Reconciliation check interval | Global constant | Reconciliation logic |
| `PAYMENT_PENDING_TIMEOUT` | int | `3600` (1 hour) | Pending transaction timeout | Global constant | Transaction cleanup |
| `TXN_STATE_PENDING` | string | `'pending'` | Transaction state: pending | Global constant | All payment processing |
| `TXN_STATE_SUCCESS` | string | `'success'` | Transaction state: successful | Global constant | All payment processing |
| `TXN_STATE_FAILED` | string | `'failed'` | Transaction state: failed | Global constant | All payment processing |
| `TXN_STATE_CANCELLED` | string | `'cancelled'` | Transaction state: cancelled | Global constant | All payment processing |
| `TXN_STATE_REFUNDED` | string | `'refunded'` | Transaction state: refunded | Global constant | All payment processing |

## 4.3 Core Library Variables & Functions

### 4.3.1 core/env.php Variables

**Function: `env_load(string $path): void`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$path` | string | Parameter | Path to `.env` file |
| `$lines` | array\|false | Local | Lines read from `.env` file |
| `$line` | string | Local (loop) | Single line from `.env` file |
| `$name` | string | Local | Environment variable name (left of `=`) |
| `$value` | string | Local | Environment variable value (right of `=`) |

**Function: `env(string $key, mixed $default = null): mixed`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$key` | string | Parameter | Environment variable name to retrieve |
| `$default` | mixed | Parameter | Default value if variable not set |
| `$value` | string\|false | Local | Value from `getenv()` |

### 4.3.2 core/db.php Variables

**Function: `db_connect(): PDO`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$pdo` | PDO\|null | Static | Singleton PDO connection instance |
| `$dsn` | string | Local | PDO Data Source Name string |

**Function: `db_query(string $sql, array $params = []): PDOStatement`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$sql` | string | Parameter | SQL query string with `?` placeholders |
| `$params` | array | Parameter | Bound parameter values |
| `$stmt` | PDOStatement | Local | Prepared statement |

**Function: `db_insert(string $table, array $data): int`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$table` | string | Parameter | Target table name |
| `$data` | array | Parameter | Associative array of column => value |
| `$columns` | string | Local | Comma-separated column names |
| `$placeholders` | string | Local | Comma-separated `?` placeholders |
| Return | int | — | Last inserted ID (`lastInsertId()`) |

**Function: `db_update(string $table, array $data, string $where, array $whereParams = []): int`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$table` | string | Parameter | Target table name |
| `$data` | array | Parameter | Associative array of column => value to update |
| `$where` | string | Parameter | WHERE clause (e.g., `'id = ?'`) |
| `$whereParams` | array | Parameter | WHERE clause parameter values |
| Return | int | — | Number of affected rows |

**Function: `db_paginate(string $sql, array $params, int $page, int $perPage): array`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$sql` | string | Parameter | Base SQL query (without LIMIT) |
| `$params` | array | Parameter | Query parameters |
| `$page` | int | Parameter | Current page number (1-based) |
| `$perPage` | int | Parameter | Items per page |
| `$total` | int | Local | Total matching row count |
| `$totalPages` | int | Local | Calculated total pages |
| `$offset` | int | Local | SQL OFFSET value |
| Return | array | — | `['data' => [...], 'total' => int, 'page' => int, 'per_page' => int, 'total_pages' => int]` |

### 4.3.3 core/auth.php Variables

**Function: `auth_attempt(string $identifier, string $password): array|string`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$identifier` | string | Parameter | Username or email |
| `$password` | string | Parameter | Plain text password |
| `$lockout` | int\|false | Local | Minutes remaining in lockout, or false |
| `$user` | array\|null | Local | User record from database with role_slugs and role_ids |
| `$lockUntil` | string | Local | Lockout expiry timestamp |
| `$mins` | int | Local | Minutes remaining for locked account |
| Return | array\|string | — | User array on success, error message string on failure |

**Session Variables Set by `auth_attempt()` on Success:**

| Variable | Type | Scope | Description | Accessed By |
|----------|------|-------|-------------|-------------|
| `$_SESSION['user_id']` | int | Session | Authenticated user's ID | `auth_user_id()`, `auth_user()`, all modules |
| `$_SESSION['username']` | string | Session | Authenticated user's username | `auth_user()` |
| `$_SESSION['user_name']` | string | Session | Authenticated user's full name | `auth_user()`, header display |
| `$_SESSION['user_email']` | string | Session | Authenticated user's email | `auth_user()` |
| `$_SESSION['user_avatar']` | string\|null | Session | Path to user's avatar image | `auth_user()`, header display |
| `$_SESSION['user_roles']` | array | Session | Array of role slugs (e.g., `['admin', 'teacher']`) | `auth_has_role()` |
| `$_SESSION['user_role_ids']` | array | Session | Array of role IDs (e.g., `[2, 3]`) | `auth_load_permissions()` |
| `$_SESSION['logged_in']` | bool | Session | Authentication flag | `auth_check()` |
| `$_SESSION['login_time']` | int | Session | Unix timestamp of login | Session security |
| `$_SESSION['_last_regeneration']` | int | Session | Unix timestamp of last session ID regeneration | `auth_init_session()` (regenerate every 30 min) |
| `$_SESSION['permissions']` | array | Session | Array of permission strings (e.g., `['students.view', 'students.create']`) or `['*']` for super admin | `auth_has_permission()` |

### 4.3.4 core/csrf.php Variables

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$_SESSION[CSRF_TOKEN_NAME]` | string | Session | Current CSRF token (64 hex chars) |
| `$token` | string | Local | Token retrieved from POST body or X-CSRF-TOKEN header |
| `$sessionToken` | string | Local | Token from session for comparison |
| `$method` | string | Local | Current HTTP request method |

### 4.3.5 core/helpers.php Variables

**Flash Message Session Variables:**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$_SESSION['_flash'][$type]` | string | Session | Flash message of type `success`, `error`, `warning`, `info` |
| `$_SESSION['_old_input']` | array | Session | Previous form input values for re-population |
| `$_SESSION['_validation_errors']` | array | Session | Field-level validation error messages |

**Function: `get_active_session(): ?array`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| Return | array\|null | — | `['id' => int, 'name' => string, 'start_date' => string, 'end_date' => string, 'is_active' => 1]` or null |

**Function: `format_money(float $amount): string`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$amount` | float | Parameter | Numeric amount |
| Return | string | — | Formatted string (e.g., `"Br 1,500.00"`) |

### 4.3.6 core/security.php Variables

**Function: `handle_upload(string $fieldName, string $subDir, ?array $allowedTypes, ?int $maxSize): string|array`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$fieldName` | string | Parameter | Form file input field name |
| `$subDir` | string | Parameter | Subdirectory under uploads (e.g., `'students/2026/02'`) |
| `$allowedTypes` | array\|null | Parameter | Override MIME types (null = use default) |
| `$maxSize` | int\|null | Parameter | Override max size (null = use default) |
| `$file` | array | Local | `$_FILES[$fieldName]` data |
| `$finfo` | resource | Local | Fileinfo handle |
| `$mime` | string | Local | Detected MIME type |
| `$ext` | string | Local | File extension |
| `$fileName` | string | Local | Generated unique filename |
| `$destDir` | string | Local | Full destination directory path |
| `$destPath` | string | Local | Full destination file path |
| Return | string\|array | — | Relative path string on success, or error string on failure |

**Function: `audit_log(string $action, ...): void`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$action` | string | Parameter | Action identifier (e.g., `'class.create'`, `'login_success'`) |
| Inserts to | — | — | `audit_logs` table with user_id, IP, user agent, action, details |

### 4.3.7 core/validation.php Variables

**Function: `validate(array $data, array $rules): array`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$data` | array | Parameter | Input data (`$_POST` or custom array) |
| `$rules` | array | Parameter | Validation rules per field (e.g., `['name' => 'required|max:100']`) |
| `$errors` | array | Local | Accumulated error messages per field |
| `$field` | string | Local (loop) | Current field name |
| `$ruleString` | string | Local (loop) | Pipe-separated rules for current field |
| `$ruleParts` | array | Local | Individual rules split by `|` |
| Return | array | — | Empty array = valid; non-empty = `['field' => 'error message']` |

**Supported Rule Keywords:**

| Rule | Format | Description |
|------|--------|-------------|
| `required` | `required` | Field must not be empty |
| `email` | `email` | Must be valid email format |
| `numeric` | `numeric` | Must be numeric |
| `integer` | `integer` | Must be integer |
| `min` | `min:N` | Minimum string length |
| `max` | `max:N` | Maximum string length |
| `between` | `between:min,max` | Length between min and max |
| `date` | `date` | Must be valid date |
| `in` | `in:val1,val2,...` | Must be one of listed values |
| `unique` | `unique:table,column` | Must not exist in database |
| `confirmed` | `confirmed` | Must match `{field}_confirmation` |
| `password` | `password` | Min length (`PASSWORD_MIN_LENGTH`), mixed case, number |
| `phone` | `phone` | Valid phone number pattern |
| `nullable` | `nullable` | Allow null/empty (skip remaining rules if empty) |

**Function: `input(string $key): string`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$key` | string | Parameter | POST/GET field name |
| Return | string | — | Sanitized (trimmed, stripped tags) value |

**Function: `input_int(string $key): int`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$key` | string | Parameter | POST/GET field name |
| Return | int | — | Integer-cast value (0 if not numeric) |

**Function: `input_array(string $key): array`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$key` | string | Parameter | POST/GET field name (expects array input) |
| Return | array | — | Array value (empty array if not set) |

### 4.3.8 core/router.php Variables

**Function: `router_dispatch(): void`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$module` | string | Local | Module name from `$_GET['module']` (default: `'dashboard'`) |
| `$action` | string | Local | Action name from `$_GET['action']` (default: `'index'`) |
| `$routeFile` | string | Local | Full path to module's routes.php |

**Function: `url(string $module, string $action = 'index', array $params = []): string`**

| Variable | Type | Scope | Description |
|----------|------|-------|-------------|
| `$module` | string | Parameter | Target module name |
| `$action` | string | Parameter | Target action name |
| `$params` | array | Parameter | Additional query parameters |
| Return | string | — | URL string (e.g., `'?module=students&action=view&id=5'`) |

## 4.4 Session Variables (Complete Reference)

| Session Key | Data Type | Set By | Description | Accessed By |
|-------------|-----------|--------|-------------|-------------|
| `$_SESSION['user_id']` | int | `auth_attempt()` | Current user's database ID | `auth_user_id()`, `current_user_id()`, all permission checks |
| `$_SESSION['username']` | string | `auth_attempt()` | Current user's login username | `auth_user()`, header display |
| `$_SESSION['user_name']` | string | `auth_attempt()` | Current user's full display name | `auth_user()`, header, sidebar |
| `$_SESSION['user_email']` | string | `auth_attempt()` | Current user's email address | `auth_user()`, profile page |
| `$_SESSION['user_avatar']` | string\|null | `auth_attempt()` | Relative path to avatar image | `auth_user()`, header avatar |
| `$_SESSION['user_roles']` | array | `auth_attempt()` | Role slugs: `['super_admin']`, `['teacher']`, etc. | `auth_has_role()`, dashboard role detection |
| `$_SESSION['user_role_ids']` | array | `auth_attempt()` | Role numeric IDs: `[1]`, `[3]`, etc. | `auth_load_permissions()` |
| `$_SESSION['logged_in']` | bool | `auth_attempt()` | Whether user is authenticated | `auth_check()` |
| `$_SESSION['login_time']` | int | `auth_attempt()` | Unix timestamp of login | Session expiry checks |
| `$_SESSION['permissions']` | array | `auth_attempt()` | Cached permission strings: `['students.view', ...]` or `['*']` | `auth_has_permission()`, sidebar menu visibility |
| `$_SESSION['_last_regeneration']` | int | `auth_init_session()` | Unix timestamp of last session ID regeneration (every 1800s) | Session fixation prevention |
| `$_SESSION['_csrf_token']` | string | `csrf_generate()` | Current CSRF token (64 hex chars) | `csrf_field()`, `csrf_meta()`, `csrf_validate()` |
| `$_SESSION['_flash']` | array | `set_flash()` | Flash messages: `['success' => 'msg', 'error' => 'msg']` | `get_flash()`, `has_flash()`, flash partial |
| `$_SESSION['_old_input']` | array | `set_old_input()` | Previous form input for re-population | `old()`, form value restoration |
| `$_SESSION['_validation_errors']` | array | `set_validation_errors()` | Per-field validation errors: `['name' => 'Required']` | `get_error()`, `get_validation_error()`, `has_error()` |
| `$_SESSION['intended_url']` | string | `auth_require()` | URL user was trying to reach before login redirect | `login.php` action (redirect after successful login) |

## 4.5 Module Action Variables (Key Variables per Action File)

### 4.5.1 Academics Module Action Variables

**actions/class_save.php:**

| Variable | Type | Default | Scope | Description |
|----------|------|---------|-------|-------------|
| `$id` | int | 0 | Local | Class ID (0 for new, >0 for update) |
| `$name` | string | — | Local | Class display name |
| `$slug` | string | — | Local | URL-safe class identifier |
| `$data` | array | — | Local | Column data for insert/update |
| `$errors` | array | — | Local | Validation error messages |
| `$dup` | array\|null | — | Local | Duplicate check result |

**actions/session_save.php:**

| Variable | Type | Default | Scope | Description |
|----------|------|---------|-------|-------------|
| `$id` | int | 0 | Local | Session ID |
| `$name` | string | — | Local | Session name (e.g., "2025/2026") |
| `$start_date` | string | — | Local | Session start date |
| `$end_date` | string | — | Local | Session end date |
| `$is_active` | int | 0\|1 | Local | Active flag |
| `$slug` | string | — | Local | Generated URL slug |
| `$data` | array | — | Local | Insert/update data |
| `$rules` | array | — | Local | Validation rules |
| `$errors` | array | — | Local | Validation errors |

**actions/promote_save.php:**

| Variable | Type | Default | Scope | Description |
|----------|------|---------|-------|-------------|
| `$fromSession` | int | — | Local | Source academic session ID |
| `$fromClass` | int | — | Local | Source class ID |
| `$fromSection` | int | — | Local | Source section ID |
| `$toSession` | int | — | Local | Destination session ID |
| `$toClass` | int | — | Local | Destination class ID |
| `$toSection` | int\|null | — | Local | Destination section ID |
| `$studentIds` | array | — | Local | Selected student IDs |
| `$statuses` | array | — | Local | Per-student promotion status map |
| `$userId` | int | — | Local | Current user ID (promoter) |
| `$now` | string | — | Local | Current datetime |
| `$promoted` | int | 0 | Local | Counter: promoted students |
| `$repeated` | int | 0 | Local | Counter: repeated students |
| `$graduated` | int | 0 | Local | Counter: graduated students |
| `$errors` | int | 0 | Local | Counter: skipped (no enrollment) |
| `$enrollment` | array\|null | — | Local (loop) | Current student's enrollment record |
| `$destClass` | int | — | Local (loop) | Computed destination class |
| `$destSection` | int\|null | — | Local (loop) | Computed destination section |
| `$destSession` | int | — | Local (loop) | Computed destination session |
| `$status` | string | `'promoted'` | Local (loop) | Current student's promotion status |

### 4.5.2 Attendance Module Action Variables

**actions/save.php:**

| Variable | Type | Default | Scope | Description |
|----------|------|---------|-------|-------------|
| `$classId` | int | — | Local | Class ID for attendance |
| `$sectionId` | int\|null | — | Local | Section ID (optional) |
| `$sessionId` | int | — | Local | Academic session ID |
| `$date` | string | — | Local | Attendance date (Y-m-d) |
| `$studentsData` | array | — | Local | `$_POST['students']` — student_id => status/remarks |
| `$activeTerm` | array\|null | — | Local | Current active term |
| `$termId` | int\|null | — | Local | Active term ID |
| `$validStatuses` | array | `['present','absent','late','excused']` | Local | Allowed status values |
| `$takenBy` | int | — | Local | Current user ID (Who took attendance) |
| `$studentId` | int | — | Local (loop) | Current student ID |
| `$status` | string | `'present'` | Local (loop) | Current student's attendance status |
| `$remarks` | string | — | Local (loop) | Optional remarks |
| `$existing` | array\|null | — | Local (loop) | Existing record for upsert |

### 4.5.3 Auth Module Action Variables

**actions/login.php:**

| Variable | Type | Default | Scope | Description |
|----------|------|---------|-------|-------------|
| `$rules` | array | — | Local | Validation rules for login form |
| `$errors` | array | — | Local | Validation errors |
| `$username` | string | — | Local | Username/email input |
| `$password` | string | — | Local | Password input |
| `$result` | array\|string | — | Local | `auth_attempt()` result |
| `$intended` | string | — | Local | URL to redirect to after login |

**actions/change_password.php:**

| Variable | Type | Default | Scope | Description |
|----------|------|---------|-------|-------------|
| `$rules` | array | — | Local | Validation rules |
| `$errors` | array | — | Local | Validation errors |
| `$user` | array | — | Local | Current authenticated user |
| `$currentPassword` | string | — | Local | Current password input |
| `$newPassword` | string | — | Local | New password input |
| `$dbUser` | array\|null | — | Local | User record with password_hash |

### 4.5.4 Finance Module Action Variables

**actions/fm_fee_save.php:**

| Variable | Type | Default | Scope | Description |
|----------|------|---------|-------|-------------|
| `$id` | int | 0 | Local | Fee ID (0 for new) |
| `$data` | array | — | Local | Fee fields: name, description, amount, type, status, session_id |
| `$errors` | array | — | Local | Validation errors |
| `$recurrenceData` | array | — | Local | Recurrence config: interval, day, start_date, end_date |
| `$penaltyData` | array | — | Local | Penalty config: type, value, grace_days, max_penalty |
| `$hasRecurrence` | bool | — | Local | Whether recurrence is enabled |
| `$hasPenalty` | bool | — | Local | Whether penalty is enabled |

**actions/fm_payment_save.php:**

| Variable | Type | Default | Scope | Description |
|----------|------|---------|-------|-------------|
| `$studentId` | int | — | Local | Student ID |
| `$chargeIds` | array | — | Local | Selected charge IDs to pay |
| `$paymentMethod` | string | — | Local | Payment method (cash/bank/online) |
| `$referenceNo` | string | — | Local | Reference/receipt number |
| `$totalAmount` | float | — | Local | Total payment amount |
| `$invoiceId` | int | — | Local | Generated/existing invoice ID |

## 4.6 Module View Variables (Key Variables per View)

### 4.6.1 Dashboard View Variables

**views/index.php (706 lines):**

| Variable | Type | Source | Description |
|----------|------|--------|-------------|
| `$user` | array | `auth_user()` | Current user info |
| `$isSuperAdmin` | bool | `auth_is_super_admin()` | Is super admin |
| `$isAdmin` | bool | `auth_has_role('admin')` | Is admin |
| `$isTeacher` | bool | `auth_has_role('teacher')` | Is teacher |
| `$isStudent` | bool | `auth_has_role('student')` | Is student |
| `$isParent` | bool | `auth_has_role('parent')` | Is parent |
| `$isAccountant` | bool | `auth_has_role('accountant')` | Is accountant |
| `$activeSession` | array\|null | `get_active_session()` | Current active session |
| `$sessionId` | int | `$activeSession['id']` | Session ID |
| `$totalStudents` | int | `db_count()` | Total enrolled students |
| `$totalTeachers` | int | `db_count()` | Total active teachers |
| `$totalClasses` | int | `db_count()` | Total active classes |
| `$totalRevenue` | float | `db_fetch_value()` | Total payment amount this session |
| `$recentActivity` | array | `db_fetch_all()` | Recent audit log entries |
| `$attendanceToday` | array | `db_fetch_all()` | Today's attendance summary |
| `$announcements` | array | `db_fetch_all()` | Recent announcements |
| `$unreadNotifications` | int | `get_unread_notification_count()` | Unread notification count |

### 4.6.2 Common View Pattern Variables

Every view file that renders within the layout uses these common variables:

| Variable | Type | Source | Description |
|----------|------|--------|-------------|
| `$pageTitle` | string | Set in `routes.php` | Page title for `<title>` and breadcrumb |
| `$content` | string | `ob_get_clean()` | Buffered HTML content for layout injection |
| `$user` | array | `auth_user()` | Current user (used in header/sidebar) |

### 4.6.3 Paginated View Pattern Variables

Views with pagination consistently use:

| Variable | Type | Source | Description |
|----------|------|--------|-------------|
| `$page` | int | `input_int('page')` or `1` | Current page number |
| `$search` | string | `input('search')` | Search query string |
| `$result` | array | `db_paginate()` | Pagination result with `data`, `total`, `page`, `per_page`, `total_pages` |

### 4.6.4 Student Views Variables

**views/create.php (412 lines):**

| Variable | Type | Source | Description |
|----------|------|--------|-------------|
| `$classes` | array | `db_fetch_all()` | All active classes for dropdown |
| `$sections` | array | `db_fetch_all()` | Sections (loaded dynamically via AJAX) |
| `$session` | array | `get_active_session()` | Active academic session |
| `$mediums` | array | `db_fetch_all()` | Available mediums |
| `$streams` | array | `db_fetch_all()` | Available streams |
| `$shifts` | array | `db_fetch_all()` | Available shifts |

**views/edit.php (419 lines):**

| Variable | Type | Source | Description |
|----------|------|--------|-------------|
| `$id` | int | `route_id()` | Student ID being edited |
| `$student` | array | `db_fetch_one()` | Student record |
| `$enrollment` | array\|null | `db_fetch_one()` | Current enrollment |
| `$studentGuardians` | array | `db_fetch_all()` | Linked guardians |
| `$classes` | array | `db_fetch_all()` | All active classes |
| `$sections` | array | `db_fetch_all()` | All sections |

---

**End of Part 2**

| Document | Contents |
|----------|----------|
| **PART 1** | Project Overview, System Architecture |
| **PART 2** (this file) | Complete File & Folder Structure, Variable Documentation |
| **PART 3** | Functional Requirements, Non-Functional Requirements, Database Design |
| **PART 4** | API Design, Development Checklist |
| **PART 5** | Development Plan, Coding Standards, Testing Strategy, Deployment Plan |
