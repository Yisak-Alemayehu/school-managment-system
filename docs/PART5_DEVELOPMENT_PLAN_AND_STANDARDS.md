# Urji Beri School Management System — Pre-Development Documentation

## PART 5: DEVELOPMENT PLAN, CODING STANDARDS, TESTING STRATEGY & DEPLOYMENT PLAN

**Document Version:** 1.0.0  
**Date:** February 27, 2026  
**Status:** Pre-Development Planning  

---

# TABLE OF CONTENTS — PART 5

10. [Development Plan](#10-development-plan)
11. [Coding Standards](#11-coding-standards)
12. [Testing Strategy](#12-testing-strategy)
13. [Deployment Plan](#13-deployment-plan)

---

# 10. DEVELOPMENT PLAN

## 10.1 Development Methodology

**Approach**: Agile-inspired iterative development with 2-week sprints  
**Team Structure**: Small team (1–3 developers)  
**Version Control**: Git with feature branches  
**Branching Strategy**:
- `main` — production-ready code
- `develop` — integration branch
- `feature/{name}` — feature development
- `bugfix/{name}` — bug fixes
- `hotfix/{name}` — production hotfixes

## 10.2 Development Phases & Timeline

### Phase 1: Foundation (Weeks 1–3)

**Sprint 1 (Week 1–2): Core Infrastructure**

| Task | Priority | Est. Hours | Dependencies |
|------|----------|-----------|-------------- |
| Set up project structure (directories, index.php) | Critical | 4 | None |
| Create `.env` and config files (app.php, database.php, payment.php) | Critical | 4 | Project structure |
| Implement `core/env.php` — environment loader | Critical | 2 | Config files |
| Implement `core/db.php` — PDO database layer | Critical | 8 | env.php |
| Write and run `sql/schema.sql` (54 tables) | Critical | 12 | Database connection |
| Write `sql/seed.sql` and migration files | Critical | 8 | Schema |
| Write `sql/run_migration.php` | High | 4 | Schema |
| Implement `core/security.php` — security headers + sanitization | Critical | 4 | None |
| Implement `core/csrf.php` — CSRF token management | Critical | 3 | Session |
| Implement `core/router.php` — front controller + routing | Critical | 8 | index.php |
| Implement `core/validation.php` — input validation library | Critical | 8 | None |
| Implement `core/response.php` — JSON response helpers | Medium | 2 | None |
| Implement `core/helpers.php` — global utility functions | High | 8 | None |

**Sprint 1 Deliverables**: Working front controller, database connection, security layer, routing

**Sprint 2 (Week 2–3): Authentication & Authorization**

| Task | Priority | Est. Hours | Dependencies |
|------|----------|-----------|-------------- |
| Implement `core/auth.php` — full auth library | Critical | 12 | db.php, security.php |
| Implement `core/rbac.php` — role & permission management | Critical | 8 | db.php, auth.php |
| Create `templates/layout.php` — master layout | Critical | 8 | router.php |
| Create `templates/partials/` (header, sidebar, flash, mobile_nav) | Critical | 12 | layout.php |
| Create `templates/errors/` (403.php, 404.php) | High | 3 | layout.php |
| Create `modules/auth/routes.php` | Critical | 4 | router.php |
| Create auth views (login, forgot, reset, change, profile) | Critical | 12 | layout.php |
| Create auth actions (login, forgot, reset, change, profile) | Critical | 12 | auth.php |
| Create `public/assets/css/style.css` | High | 8 | Tailwind CDN |
| Create `public/assets/js/app.js` | High | 12 | None |
| Seed default roles, permissions, admin user | Critical | 4 | Schema, rbac.php |

**Sprint 2 Deliverables**: Complete authentication system, layout templates, basic CSS/JS

---

### Phase 2: Core Modules (Weeks 4–7)

**Sprint 3 (Week 4–5): Academics + Students**

| Task | Priority | Est. Hours | Dependencies |
|------|----------|-----------|-------------- |
| `modules/dashboard/` — routes + index view | Critical | 8 | Auth complete |
| `modules/academics/` — CRUD views (sessions, terms, classes, sections, subjects) | Critical | 20 | Layout, DB |
| `modules/academics/` — actions (save/toggle for sessions, terms, classes, sections, subjects) | Critical | 16 | Validation |
| Academic extras (mediums, streams, shifts) | High | 8 | Classes |
| Class-subject assignment (matrix UI + save) | High | 8 | Classes, subjects |
| Class teacher + subject teacher assignment | High | 8 | Users, classes |
| `modules/students/` — CRUD views (index, create, edit, view) | Critical | 20 | Academics |
| `modules/students/` — actions (store, update, delete) | Critical | 16 | Validation, DB |
| Student photo upload and storage | High | 4 | security.php |
| Guardian management (create, link, edit) | High | 6 | Students |
| Enrollment management | High | 4 | Sessions, classes |
| `modules/api/routes.php` | High | 3 | DB |

**Sprint 3 Deliverables**: Full academics + student management, API endpoints

**Sprint 4 (Week 5–6): Students Extended + Attendance**

| Task | Priority | Est. Hours | Dependencies |
|------|----------|-----------|-------------- |
| Student bulk import (CSV) | Medium | 8 | Students |
| Student export (CSV) | Medium | 4 | Students |
| Student credential generation | Medium | 4 | Auth, students |
| Student password reset | Medium | 3 | Auth |
| Roll number assignment | Medium | 4 | Enrollments |
| Student ID card generation | Medium | 6 | Students |
| Student promotion (academics + students) | High | 8 | Enrollments |
| Elective subject assignment | Medium | 6 | Class-subjects |
| `modules/attendance/` — take views + save action | Critical | 12 | Students, classes |
| Attendance report + student attendance view | High | 8 | Attendance |

**Sprint 4 Deliverables**: Extended student features, attendance system

**Sprint 5 (Week 6–7): Exams & Assessment**

| Task | Priority | Est. Hours | Dependencies |
|------|----------|-----------|-------------- |
| Assignment CRUD (list, form, view, save, delete) | High | 12 | Classes, subjects |
| Exam CRUD (list, save) | Critical | 8 | Sessions, terms |
| Exam schedule management | High | 8 | Exams, classes |
| Marks entry (UI + save) | Critical | 12 | Exams, students |
| Grade scale management | High | 6 | DB |
| Assessment definitions + results entry | Critical | 12 | Classes, subjects |
| Student conduct entry | Medium | 6 | Students, sessions |
| Roster generation (all subjects × students) | High | 12 | Marks/results |
| Report card generation + printing | Critical | 16 | Assessments, grade scale |
| Result analysis (charts, statistics) | Medium | 8 | Results |

**Sprint 5 Deliverables**: Complete examination and assessment system

---

### Phase 3: Finance & Communication (Weeks 8–11)

**Sprint 6 (Week 8–9): Legacy Finance**

| Task | Priority | Est. Hours | Dependencies |
|------|----------|-----------|-------------- |
| Fee category CRUD | Critical | 6 | DB |
| Fee structure CRUD | Critical | 8 | Fee categories, sessions, classes |
| Invoice generation | Critical | 12 | Fee structures, students |
| Invoice list, view, print | Critical | 10 | Invoices |
| Payment recording (manual) | Critical | 8 | Invoices |
| Payment receipt printing | High | 6 | Payments |
| Fee discounts CRUD | High | 6 | Fee categories |
| Student fee discount assignment | High | 4 | Discounts, students |
| Fee report | High | 8 | Invoices, payments |

**Sprint 6 Deliverables**: Complete legacy finance system

**Sprint 7 (Week 9–10): Payment Gateway + Advanced Fee Management**

| Task | Priority | Est. Hours | Dependencies |
|------|----------|-----------|-------------- |
| `core/payment_gateway.php` — abstraction layer | Critical | 12 | DB, config |
| `core/gateways/telebirr.php` — Telebirr integration | Critical | 12 | Payment gateway |
| `core/gateways/chapa.php` — Chapa integration | Critical | 8 | Payment gateway |
| Online payment page (student/parent facing) | High | 8 | Payment gateways |
| Payment initiation + callback handling | Critical | 12 | Gateways |
| Webhook verification + reconciliation | High | 8 | Payment transactions |
| Fee Management v2: fee definitions CRUD | High | 12 | Finance base |
| Fee Management v2: student groups CRUD | High | 8 | Students |
| Fee Management v2: fee assignment (student + group) | High | 10 | Fees, groups |
| Fee Management v2: exemptions | High | 6 | Assignments |

**Sprint 7 Deliverables**: Online payments, advanced fee management core

**Sprint 8 (Week 10–11): Fee Management Advanced + Communication**

| Task | Priority | Est. Hours | Dependencies |
|------|----------|-----------|-------------- |
| Fee charges generation from assignments | High | 8 | Fee assignments |
| Fee payment against charges | High | 8 | Fee charges |
| Charge waiving | Medium | 4 | Fee charges |
| Fee reports + export | High | 8 | Fee charges |
| Fee dashboard | High | 8 | Fee reports |
| Invoice generation from charges | High | 6 | Fee charges, invoices |
| `cron/fm_recurrence_job.php` — recurring fees | High | 8 | Recurrence configs |
| `cron/fm_penalty_job.php` — penalty calculation | High | 8 | Penalty configs |
| Announcement CRUD | Critical | 10 | Layout |
| Messages (inbox, compose, send, view, sent) | High | 14 | Users |
| Notifications (list, mark read, mark all read) | High | 8 | DB |

**Sprint 8 Deliverables**: Advanced fee management, communication system

---

### Phase 4: Administration & Polish (Weeks 12–14)

**Sprint 9 (Week 12–13): Users, Settings, Polish**

| Task | Priority | Est. Hours | Dependencies |
|------|----------|-----------|-------------- |
| User management CRUD | Critical | 12 | Auth, RBAC |
| Role assignment in user forms | High | 4 | RBAC |
| User toggle status | High | 3 | Users |
| General settings management | High | 8 | DB |
| Audit log viewer | High | 6 | Audit logs |
| Database backup/restore interface | High | 8 | DB |
| Sidebar permissions filtering | High | 4 | RBAC |
| Timetable management | Medium | 12 | Academics |
| Mobile navigation polish | Medium | 4 | CSS/JS |
| PWA implementation | Medium | 6 | Service worker |

**Sprint 9 Deliverables**: Complete administration, settings, PWA

**Sprint 10 (Week 13–14): Testing & Deployment Prep**

| Task | Priority | Est. Hours | Dependencies |
|------|----------|-----------|-------------- |
| End-to-end testing all modules | Critical | 20 | All modules |
| Security audit (OWASP top 10) | Critical | 12 | All |
| Performance testing + optimization | High | 8 | All |
| Cross-browser testing | High | 6 | CSS/JS |
| Mobile responsiveness testing | High | 4 | CSS |
| Print layout testing (report cards, invoices, receipts) | High | 4 | Print views |
| Bug fixes from testing | Critical | 20 | Testing |
| Documentation finalization | High | 8 | All |
| Deployment guide creation | High | 4 | All |
| Production deployment | Critical | 8 | All |

**Sprint 10 Deliverables**: Production-ready system

---

## 10.3 Timeline Summary

| Phase | Duration | Sprints | Key Deliverables |
|-------|----------|---------|-----------------|
| Phase 1: Foundation | Weeks 1–3 | 1–2 | Infrastructure, auth, templates |
| Phase 2: Core Modules | Weeks 4–7 | 3–5 | Academics, students, attendance, exams |
| Phase 3: Finance & Communication | Weeks 8–11 | 6–8 | Finance, payments, fee mgmt, communication |
| Phase 4: Administration & Polish | Weeks 12–14 | 9–10 | Users, settings, testing, deployment |
| **Total** | **14 weeks** | **10 sprints** | **Complete system** |

## 10.4 Milestones

| Milestone | Target Date | Criteria |
|-----------|------------|----------|
| M1: Foundation Complete | Week 3 | Login works, layout renders, RBAC active |
| M2: Core Modules Complete | Week 7 | Academics, students, attendance, exams functional |
| M3: Finance Complete | Week 11 | All fee, invoice, payment, online payment features working |
| M4: Beta Release | Week 13 | All modules functional, communication complete |
| M5: Production Release | Week 14 | All bugs fixed, security audited, deployed |

## 10.5 Risk Register

| Risk | Impact | Probability | Mitigation |
|------|--------|------------|------------|
| Payment gateway API changes | High | Medium | Abstraction layer isolates integrations |
| Scope creep from additional feature requests | High | High | Strict sprint backlog management |
| Database performance with large datasets | Medium | Low | Proper indexing, query optimization |
| Browser compatibility issues | Medium | Medium | Tailwind CSS handles cross-browser |
| Security vulnerabilities discovered | High | Medium | Security-first design, OWASP checklist |
| Single developer bottleneck | High | Medium | Modular architecture enables parallel work |
| Ethiopian payment API instability | Medium | Medium | Retry mechanisms + manual payment fallback |

---

# 11. CODING STANDARDS

## 11.1 PHP Coding Standards

### Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Functions | `snake_case` | `auth_check()`, `db_fetch_all()` |
| Constants | `UPPER_SNAKE_CASE` | `APP_NAME`, `DB_HOST`, `MAX_LOGIN_ATTEMPTS` |
| Variables | `$camelCase` | `$pageTitle`, `$userId`, `$sessionId` |
| File names | `snake_case.php` | `class_subjects_save.php`, `forgot_password.php` |
| URL/action slugs | `kebab-case` | `class-subjects`, `forgot-password` |
| Database tables | `snake_case` (plural) | `students`, `class_subjects`, `audit_logs` |
| Database columns | `snake_case` | `first_name`, `created_at`, `is_active` |
| CSS classes | `kebab-case` | `nav-link`, `btn-primary`, `card-header` |

### Function Prefixes

| Prefix | Purpose | Example |
|--------|---------|---------|
| `auth_` | Authentication/authorization | `auth_check()`, `auth_require()` |
| `db_` | Database operations | `db_query()`, `db_fetch()` |
| `csrf_` | CSRF protection | `csrf_token()`, `csrf_field()` |
| `validate_` | Input validation | `validate_required()`, `validate_email()` |
| `input_` | Input retrieval | `input()`, `input_int()` |
| `json_` | JSON responses | `json_response()`, `json_success()` |
| `rbac_` | Role-based access control | `rbac_get_roles()`, `rbac_assign_user_role()` |
| `pg_` | Payment gateway | `pg_initialize_payment()`, `pg_handle_callback()` |
| `pwa_` | Progressive Web App | `pwa_meta_tags()` |
| `chapa_` | Chapa gateway | `chapa_initialize()`, `chapa_verify()` |
| `telebirr_` | Telebirr gateway | `telebirr_initialize()`, `telebirr_verify()` |
| `fm_` | Fee management | URL action prefix |

### Code Structure Rules

1. **No OOP / No Classes**: All code uses procedural functions
2. **No Frameworks / No Composer**: Pure PHP, no external dependencies
3. **No ORM**: Raw SQL via PDO prepared statements
4. **Explicit Includes**: All file includes use `require` or `require __DIR__`
5. **Single Responsibility**: One action per file (e.g., `store.php`, `update.php`, `delete.php`)
6. **Security First**: All user input sanitized via `sanitize_input()`, all output escaped via `e()`

### PHP Code Style

```php
<?php
/**
 * File description
 * Purpose: [what this file does]
 */

// 1. Permission check
auth_require();
auth_require_permission('module.action');

// 2. CSRF protection (state-changing only)
csrf_protect();

// 3. Input collection and validation
$name = input('name');
$email = input_email('email');
$errors = [];

if (!validate_required($name)) {
    $errors[] = 'Name is required';
}

if (!empty($errors)) {
    set_flash('error', implode('<br>', $errors));
    redirect(url('module', 'action'));
}

// 4. Business logic (database operations)
try {
    db_transaction(function () use ($name, $email) {
        db_insert('table', [
            'name' => $name,
            'email' => $email,
        ]);
        audit_log('create', 'module', 'entity', db_last_id());
    });
    set_flash('success', 'Record created successfully.');
} catch (Exception $e) {
    set_flash('error', 'Failed to create record.');
}

// 5. Redirect
redirect(url('module', 'index'));
```

### View Template Pattern

```php
<?php
/** @var string $pageTitle */

// Data loading
$pdo = db_connect();
$items = db_fetch_all("SELECT * FROM table WHERE is_active = 1 ORDER BY name");
$activeSession = get_active_session();

ob_start();
?>

<!-- HTML content using Tailwind CSS classes -->
<div class="max-w-7xl mx-auto py-6 px-4">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
        <?= e($pageTitle) ?>
    </h1>

    <!-- Flash messages -->
    <?php include TEMPLATES_PATH . '/partials/flash.php'; ?>

    <!-- Content -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mt-4">
        <!-- Table, forms, etc. -->
    </div>
</div>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
```

---

## 11.2 SQL Coding Standards

### Table Design Rules
1. **Primary Key**: Always `id BIGINT UNSIGNED AUTO_INCREMENT`
2. **Timestamps**: Always include `created_at DATETIME DEFAULT CURRENT_TIMESTAMP` and `updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
3. **Soft Delete**: Use `deleted_at DATETIME DEFAULT NULL` where applicable
4. **Foreign Keys**: Named `fk_{table}_{column}`, with appropriate ON DELETE action
5. **Indexes**: Named `idx_{table}_{column(s)}` or `uk_{table}_{column(s)}` for unique
6. **ENUM Types**: Use for fixed small sets of values; document all valid values
7. **Currency**: `DECIMAL(12,2)` for monetary values
8. **Collation**: `utf8mb4_unicode_ci` for all text columns
9. **Engine**: InnoDB for all tables (transaction + FK support)

### Query Standards
```sql
-- SELECT: Always specify columns, never SELECT *
SELECT s.id, s.first_name, s.last_name, e.class_id
FROM students s
JOIN enrollments e ON e.student_id = s.id
WHERE s.deleted_at IS NULL
  AND e.session_id = ?
ORDER BY s.first_name ASC
LIMIT ? OFFSET ?;

-- INSERT: Use named columns
INSERT INTO students (first_name, last_name, gender, admission_no)
VALUES (?, ?, ?, ?);

-- UPDATE: Always include WHERE clause
UPDATE students SET status = ?, updated_at = NOW() WHERE id = ?;

-- DELETE: Prefer soft delete
UPDATE students SET deleted_at = NOW() WHERE id = ?;
```

### Migration File Conventions
```sql
-- File: NNN_description.sql
-- Example: 015_fee_management.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,...';

-- Wrap in transaction where possible
START TRANSACTION;

CREATE TABLE IF NOT EXISTS table_name (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- columns...
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
```

---

## 11.3 HTML / Tailwind CSS Standards

### HTML Rules
1. **Semantic HTML5**: Use `<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<footer>`
2. **Accessibility**: Include `aria-label`, `role`, `alt` attributes
3. **Forms**: Always include `<label>` with `for` attribute; use `autocomplete` attributes
4. **Tables**: Use `<thead>`, `<tbody>`, responsive wrapper `<div class="overflow-x-auto">`
5. **Output Escaping**: All PHP variables wrapped in `<?= e($var) ?>` — NEVER use raw `<?= $var ?>`

### Tailwind CSS Patterns

```html
<!-- Card component -->
<div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Title</h2>
    <p class="text-sm text-gray-600 dark:text-gray-400">Description</p>
</div>

<!-- Button variants -->
<button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
    Primary
</button>
<button class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-medium">
    Secondary
</button>
<button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
    Danger
</button>

<!-- Form input -->
<div class="mb-4">
    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        Name <span class="text-red-500">*</span>
    </label>
    <input type="text" id="name" name="name"
           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                  bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                  focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
           value="<?= e(old('name')) ?>" required>
</div>

<!-- Status badge -->
<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>
<span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Inactive</span>
```

### Dark Mode Support
- Always include `dark:` variants for backgrounds, text, and borders
- Toggle via `class="dark"` on `<html>` element
- Stored in localStorage and session

### Print Styles
```css
@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    body { font-size: 12pt; }
    @page { size: A4 portrait; margin: 1cm; }
}
```

---

## 11.4 JavaScript Standards

### General Rules
1. **Vanilla JS ES6+**: No jQuery, no frameworks
2. **Strict Mode**: Not explicitly required (module-scope functions)
3. **Event Handling**: Use `addEventListener()`, never inline `onclick`
4. **AJAX**: Use `fetch()` API with `async/await`
5. **DOM Ready**: Wrap code in `document.addEventListener('DOMContentLoaded', ...)`
6. **CSRF**: Include token in all POST requests

### AJAX Pattern
```javascript
async function loadSections(classId) {
    try {
        const response = await fetch(`?module=api&action=sections&class_id=${classId}`);
        const data = await response.json();
        const select = document.getElementById('section_id');
        select.innerHTML = '<option value="">-- Select Section --</option>';
        data.forEach(section => {
            select.innerHTML += `<option value="${section.id}">${section.name}</option>`;
        });
    } catch (error) {
        console.error('Failed to load sections:', error);
    }
}
```

### Form Submission Pattern
```javascript
document.getElementById('myForm').addEventListener('submit', function(e) {
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
});
```

### Confirm Delete Pattern
```javascript
function confirmDelete(formId) {
    if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
        document.getElementById(formId).submit();
    }
}
```

---

## 11.5 File Organization Conventions

### Module Structure
```
modules/{module_name}/
├── routes.php              # Switch/case routing
├── actions/                # State-changing operations
│   ├── store.php           # Create new record
│   ├── update.php          # Update existing record
│   ├── delete.php          # Delete record
│   └── {specific}_save.php # Specialized saves
└── views/                  # Page rendering
    ├── index.php           # List/table view
    ├── create.php          # Create form
    ├── edit.php            # Edit form
    ├── view.php            # Detail/profile view
    └── {specific}.php      # Specialized views
```

### Action File Pattern
1. Permission check
2. CSRF protection
3. Input collection + validation
4. Database operation (in transaction if multi-table)
5. Audit log
6. Flash message
7. Redirect

### View File Pattern
1. Data loading (queries)
2. `ob_start()` to capture output buffer
3. HTML content with Tailwind classes
4. `$content = ob_get_clean()`
5. `include TEMPLATES_PATH . '/layout.php'`

---

# 12. TESTING STRATEGY

## 12.1 Testing Levels

### Level 1: Manual Functional Testing

**Approach**: Systematic manual testing of each module and feature  
**When**: After each sprint, feature completion, and before release

#### Auth Module Test Cases
| # | Test Case | Steps | Expected Result |
|---|-----------|-------|----------------|
| T-AUTH-01 | Valid login | Enter valid username + password | Redirect to dashboard, session created |
| T-AUTH-02 | Invalid password | Enter valid username, wrong password | Error message, attempt incremented |
| T-AUTH-03 | Account lockout | Fail login 5 times in 15 min | "Account locked" message |
| T-AUTH-04 | Lockout expiry | Wait 15 minutes after lockout | Login succeeds |
| T-AUTH-05 | CSRF bypass attempt | Submit login without CSRF token | 403 error or redirect |
| T-AUTH-06 | Session timeout | Wait 2 hours | Redirect to login page |
| T-AUTH-07 | Logout | Click logout | Session destroyed, redirect to login |
| T-AUTH-08 | Forgot password | Submit valid email | "Check email" message, token generated |
| T-AUTH-09 | Reset with invalid token | Submit expired/invalid token | Error message |
| T-AUTH-10 | Change password | Enter correct current, valid new | Password updated, success message |
| T-AUTH-11 | Profile update | Change name, email, avatar | Profile updated, session refreshed |
| T-AUTH-12 | Email enumeration | Submit non-existent email to forgot | Same "check email" message (no leak) |

#### RBAC Test Cases
| # | Test Case | Expected Result |
|---|-----------|----------------|
| T-RBAC-01 | Super Admin accesses all modules | Access granted |
| T-RBAC-02 | Teacher accesses settings | 403 forbidden |
| T-RBAC-03 | Student accesses other student | Access denied or no data |
| T-RBAC-04 | Parent sees only linked children | Other students not visible |
| T-RBAC-05 | Accountant accesses finance | Access granted |
| T-RBAC-06 | Removed permission | Feature inaccessible immediately |

#### Student Module Test Cases
| # | Test Case | Expected Result |
|---|-----------|----------------|
| T-STU-01 | Create student with all fields | Student created, enrollment created |
| T-STU-02 | Create with duplicate admission_no | Validation error |
| T-STU-03 | Upload photo > 5MB | Rejection with error message |
| T-STU-04 | Upload non-image file | Rejection with error message |
| T-STU-05 | Edit student guardian | Guardian updated |
| T-STU-06 | Soft delete student | deleted_at set, student hidden from list |
| T-STU-07 | CSV import with valid file | Students created, count displayed |
| T-STU-08 | CSV import with invalid rows | Per-row errors reported |
| T-STU-09 | Export filtered students | CSV downloaded with correct data |
| T-STU-10 | Generate credentials | User created, credentials displayed |

#### Finance Test Cases
| # | Test Case | Expected Result |
|---|-----------|----------------|
| T-FIN-01 | Generate invoice | Invoice with correct totals |
| T-FIN-02 | Record partial payment | Balance updated, status = partial |
| T-FIN-03 | Record full payment | Balance = 0, status = paid |
| T-FIN-04 | Initiate Telebirr payment | Redirect to Telebirr checkout |
| T-FIN-05 | Telebirr callback success | Payment recorded, invoice updated |
| T-FIN-06 | Telebirr callback failure | Transaction marked failed |
| T-FIN-07 | Duplicate payment prevention | Idempotency key rejects duplicate |
| T-FIN-08 | Fee assignment to group | All group members get charges |
| T-FIN-09 | Fee exemption | Exempted student has no charge |
| T-FIN-10 | Penalty calculation | Correct penalty applied after grace |

### Level 2: Security Testing

| # | Test | Tool/Method | Target |
|---|------|------------|--------|
| T-SEC-01 | SQL injection | Manual + sqlmap | All form inputs |
| T-SEC-02 | XSS (reflected) | Manual payload testing | All output fields |
| T-SEC-03 | XSS (stored) | Store `<script>` in DB fields | Announcement content, messages |
| T-SEC-04 | CSRF bypass | Disable CSRF token | All POST forms |
| T-SEC-05 | Session fixation | Manual session ID test | Login flow |
| T-SEC-06 | Directory traversal | Path manipulation in uploads.php | File download |
| T-SEC-07 | IDOR | Change entity IDs in URLs | Student view, invoice view |
| T-SEC-08 | Privilege escalation | Modify role_id in requests | User management |
| T-SEC-09 | File upload bypass | Rename .php as .jpg | Photo upload |
| T-SEC-10 | Brute-force | Rapid login attempts | Login endpoint |
| T-SEC-11 | Information disclosure | Check error messages | 500 errors in production |
| T-SEC-12 | HTTP header security | Security headers check | All responses |

### Level 3: Cross-Browser & Responsive Testing

| Browser | Versions | Platform |
|---------|----------|----------|
| Chrome | 90+ | Windows, macOS, Android |
| Firefox | 88+ | Windows, macOS |
| Safari | 14+ | macOS, iOS |
| Edge | 90+ | Windows |

| Device | Screen Size | Test Focus |
|--------|------------|------------|
| Desktop | 1920×1080, 1366×768 | Full layout, sidebar |
| Tablet | 768×1024 | Sidebar toggle, tables |
| Mobile | 375×667, 414×896 | Mobile nav, card layout, modals |

### Level 4: Performance Testing

| # | Test | Tool | Target |
|---|------|------|--------|
| T-PERF-01 | Page load time | Browser DevTools | < 500ms server response |
| T-PERF-02 | Database query count | PDO logging | < 10 queries per page |
| T-PERF-03 | Memory usage | `memory_get_peak_usage()` | < 32MB per request |
| T-PERF-04 | Large dataset pagination | 5,000 students | < 1s page load |
| T-PERF-05 | Concurrent users | Apache Bench (ab) | 200+ requests/sec |
| T-PERF-06 | CSV import large file | 1,000 row CSV | < 60s |

### Level 5: Print Testing

| Item | Paper Size | Orientation | Columns |
|------|-----------|-------------|---------|
| Report Card | A4 | Portrait | Single student per page |
| Roster | A4 | Landscape | All subjects × students |
| Invoice | A4 | Portrait | Invoice details + items |
| Payment Receipt | A4 | Portrait | Receipt details |
| Student ID Card | A4 | Portrait | Multiple cards per page |
| Attendance Report | A4 | Landscape | Students × dates |

---

## 12.2 Test Data Management

### Test Users
| Username | Password | Role | Purpose |
|----------|----------|------|---------|
| admin | Admin@2025 | Super Admin | Full access testing |
| abebe.teacher | Teacher@2025 | Teacher | Teacher RBAC testing |
| student1 | Student@2025 | Student | Student self-service testing |
| parent1 | Parent@2025 | Parent | Parent access testing |
| finance.user | Finance@2025 | Accountant | Finance module testing |
| admin2 | Admin@2025 | Admin | Admin-level testing |

### Required Seed Data
- 1 active academic session (2026/2027)
- 2 terms (1st Semester, 2nd Semester)
- 12 classes (Grade 1–12) with 2–3 sections each
- 15+ subjects
- 50+ students with enrollments
- 5+ guardians linked to students
- Grade scale (Ethiopian standard)
- 3 payment gateways (Telebirr, Chapa, Stripe)
- Settings records for all configurable values

---

## 12.3 Bug Tracking

### Severity Levels
| Level | Definition | Response Time |
|-------|-----------|---------------|
| Critical | System crash, data loss, security breach | Fix immediately |
| High | Feature non-functional, workaround exists | Fix within 24 hours |
| Medium | UI issue, minor feature gap | Fix within sprint |
| Low | Cosmetic, enhancement | Backlog |

### Bug Report Template
```
Title: [Module] Brief description
Severity: Critical / High / Medium / Low
Steps to Reproduce:
1. Navigate to...
2. Click on...
3. Enter...
Expected Result: ...
Actual Result: ...
Browser/OS: ...
Screenshot: (if applicable)
```

---

# 13. DEPLOYMENT PLAN

## 13.1 Server Requirements

### Minimum Server Specifications
| Component | Requirement |
|-----------|------------|
| CPU | 2 cores |
| RAM | 2 GB |
| Storage | 20 GB SSD |
| OS | Ubuntu 20.04+ / CentOS 8+ / cPanel |
| PHP | 8.1+ (8.2 recommended) |
| MySQL | 8.0+ |
| Web Server | Apache 2.4+ with mod_rewrite OR Nginx 1.18+ |
| SSL | Let's Encrypt (free) or commercial certificate |

### PHP Extensions Required
| Extension | Purpose |
|-----------|---------|
| pdo | Database abstraction |
| pdo_mysql | MySQL driver |
| openssl | Telebirr RSA encryption, token generation |
| curl | Payment gateway API calls |
| mbstring | Multi-byte string handling |
| json | JSON encode/decode |
| fileinfo | MIME type detection for uploads |

### PHP Configuration (`php.ini`)
```ini
upload_max_filesize = 10M
post_max_size = 15M
max_execution_time = 120
memory_limit = 256M
session.cookie_httponly = On
session.cookie_secure = On
session.use_strict_mode = On
display_errors = Off          # Production
log_errors = On
error_log = /path/to/logs/php_errors.log
```

---

## 13.2 Deployment Steps

### Step 1: Server Preparation
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install apache2 php8.2 php8.2-mysql php8.2-curl php8.2-mbstring php8.2-xml php8.2-fileinfo
sudo apt install mysql-server
sudo a2enmod rewrite
sudo systemctl restart apache2

# Enable SSL
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d yourdomain.com
```

### Step 2: Database Setup
```bash
# Create database and user
mysql -u root -p
CREATE DATABASE urjiberi_school CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'urjiberi_user'@'localhost' IDENTIFIED BY 'StrongPassword!2026';
GRANT ALL PRIVILEGES ON urjiberi_school.* TO 'urjiberi_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema and seed
mysql -u urjiberi_user -p urjiberi_school < sql/schema.sql
mysql -u urjiberi_user -p urjiberi_school < sql/seed.sql
```

### Step 3: Application Deployment
```bash
# Upload files to server
rsync -avz --exclude='.env' --exclude='storage/backups/*' ./ user@server:/var/www/urjiberi/

# Set permissions
chmod -R 755 /var/www/urjiberi/
chmod -R 775 /var/www/urjiberi/storage/
chmod -R 775 /var/www/urjiberi/uploads/
chmod -R 775 /var/www/urjiberi/logs/
find /var/www/urjiberi/ -name "*.php" -exec chmod 644 {} \;

# Create .env
cp .env.example .env
nano .env  # Edit with production values
chmod 600 .env
```

### Step 4: Apache Virtual Host Configuration
```apache
<VirtualHost *:443>
    ServerName school.urjiberi.edu.et
    DocumentRoot /var/www/urjiberi/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/school.urjiberi.edu.et/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/school.urjiberi.edu.et/privkey.pem

    <Directory /var/www/urjiberi/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Block access to sensitive directories
    <DirectoryMatch "^/var/www/urjiberi/(config|core|sql|storage|logs|templates|modules)">
        Require all denied
    </DirectoryMatch>

    ErrorLog ${APACHE_LOG_DIR}/urjiberi_error.log
    CustomLog ${APACHE_LOG_DIR}/urjiberi_access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName school.urjiberi.edu.et
    Redirect permanent / https://school.urjiberi.edu.et/
</VirtualHost>
```

### Step 5: `.htaccess` (public directory)
```apache
RewriteEngine On

# Force HTTPS
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Front controller
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]

# Block dotfiles
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
```

### Step 6: Cron Jobs
```bash
# Edit crontab
crontab -e

# Add recurring fee generation (daily at 1:00 AM)
0 1 * * * /usr/bin/php /var/www/urjiberi/cron/fm_recurrence_job.php >> /var/www/urjiberi/logs/cron_recurrence.log 2>&1

# Add penalty calculation (daily at 2:00 AM)
0 2 * * * /usr/bin/php /var/www/urjiberi/cron/fm_penalty_job.php >> /var/www/urjiberi/logs/cron_penalty.log 2>&1

# Database backup (daily at 3:00 AM)
0 3 * * * mysqldump -u urjiberi_user -p'Password' urjiberi_school | gzip > /var/www/urjiberi/storage/backups/backup_$(date +\%Y\%m\%d).sql.gz
```

### Step 7: Post-Deployment Verification
- [ ] Access login page via HTTPS
- [ ] Login with admin credentials
- [ ] Verify dashboard loads with correct data
- [ ] Test CSRF protection (inspect form tokens)
- [ ] Test session timeout
- [ ] Verify file uploads work (student photo)
- [ ] Verify payment gateway sandbox connectivity
- [ ] Check security headers (use securityheaders.com)
- [ ] Verify error logging to logs/ directory
- [ ] Test 403 and 404 error pages
- [ ] Verify PWA manifest + service worker registration
- [ ] Run `sql/check_seed.php` to verify seed data

---

## 13.3 cPanel Deployment (Shared Hosting)

### Directory Structure on cPanel
```
/home/username/
├── public_html/          ← Point to public/ contents
│   ├── index.php
│   ├── .htaccess
│   ├── uploads.php
│   ├── service-worker.js
│   ├── manifest.webmanifest
│   ├── offline.html
│   └── assets/
├── urjiberi_app/         ← Private application directory
│   ├── .env
│   ├── config/
│   ├── core/
│   ├── modules/
│   ├── templates/
│   ├── sql/
│   ├── logs/
│   ├── storage/
│   └── cron/
```

### Key cPanel Steps
1. Upload `urjiberi_app/` outside `public_html` for security
2. Upload `public/` contents into `public_html`
3. Update `public_html/index.php` to reference `urjiberi_app/` path
4. Create MySQL database via cPanel → MySQL Databases
5. Import `schema.sql` and `seed.sql` via phpMyAdmin
6. Configure `.env` with cPanel DB credentials
7. Set up cron jobs via cPanel → Cron Jobs
8. Enable SSL via cPanel → SSL/TLS

---

## 13.4 Environment Configuration

### Production `.env`
```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=urjiberi_school
DB_USER=urjiberi_user
DB_PASS=ProductionPassword!2026
DB_CHARSET=utf8mb4

# Payment — Telebirr (Production)
TELEBIRR_APP_ID=production_app_id
TELEBIRR_APP_KEY=production_app_key
TELEBIRR_SHORT_CODE=production_short_code
TELEBIRR_PUBLIC_KEY=/full/path/to/telebirr_public.pem

# Payment — Chapa (Production)
CHAPA_SECRET_KEY=CHASECK_PRODUCTION_KEY

# Debug
APP_DEBUG=false
APP_ENV=production
```

### Security Checklist for Production
- [ ] `APP_DEBUG=false` in `.env`
- [ ] `display_errors = Off` in `php.ini`
- [ ] `.env` file is `chmod 600`
- [ ] `config/`, `core/`, `sql/`, `storage/` directories not web-accessible
- [ ] HTTPS enforced (redirect HTTP → HTTPS)
- [ ] Admin password changed from default
- [ ] Payment gateways switched from sandbox to production
- [ ] Database user has minimum required privileges
- [ ] Error logs directed to `logs/` directory only
- [ ] Backup cron job configured and tested
- [ ] Remove demo users (`sql/018_rbac_demo_users.sql` data)
- [ ] Security headers verified (securityheaders.com — A grade)

---

## 13.5 Maintenance & Monitoring

### Routine Maintenance Tasks

| Task | Frequency | Method |
|------|-----------|--------|
| Database backup | Daily | Cron job (mysqldump) |
| Log rotation | Weekly | Truncate or rotate `logs/` files |
| Backup cleanup | Monthly | Delete backups older than 30 days |
| PHP/MySQL updates | Monthly | Package manager updates |
| SSL renewal | Every 60 days | Certbot auto-renewal |
| Security audit | Quarterly | OWASP checklist review |
| Password rotation | Quarterly | Admin password change |

### Monitoring Checklist
- [ ] Monitor disk usage (uploads, backups, logs)
- [ ] Monitor PHP error log for new errors
- [ ] Monitor database size growth
- [ ] Check payment gateway API health
- [ ] Verify cron jobs are running (check log timestamps)
- [ ] Test login flow monthly
- [ ] Review audit logs for suspicious activity

### Incident Response
1. **Detection**: Monitor error logs, user reports
2. **Assessment**: Evaluate severity and impact
3. **Containment**: Disable affected feature if critical
4. **Resolution**: Fix, test, deploy
5. **Recovery**: Restore from backup if data affected
6. **Post-Mortem**: Document root cause and prevention

---

## 13.6 Backup & Recovery

### Backup Strategy
| Type | Frequency | Retention | Storage |
|------|-----------|-----------|---------|
| Full database dump | Daily | 30 days | `storage/backups/` + off-site |
| File backup (uploads) | Weekly | 30 days | Off-site only |
| Configuration backup | On change | 5 versions | Off-site only |

### Recovery Procedures

#### Database Recovery
```bash
# Stop application (maintenance mode)
# Restore from backup
gunzip < storage/backups/backup_20260227.sql.gz | mysql -u urjiberi_user -p urjiberi_school

# Verify data integrity
php sql/check_seed.php
```

#### Application Recovery
```bash
# Restore files from backup
rsync -avz backup_source/ /var/www/urjiberi/

# Restore permissions
chmod -R 755 /var/www/urjiberi/
chmod -R 775 /var/www/urjiberi/storage/ /var/www/urjiberi/uploads/ /var/www/urjiberi/logs/
```

---

**End of Part 5**

---

# DOCUMENT INDEX — ALL PARTS

| Part | File | Sections | Contents |
|------|------|----------|----------|
| **PART 1** | `PART1_PROJECT_OVERVIEW_AND_ARCHITECTURE.md` | 1–2 | Project Overview, System Architecture |
| **PART 2** | `PART2_FILE_STRUCTURE_AND_VARIABLES.md` | 3–4 | Complete File & Folder Structure, Variable Documentation |
| **PART 3** | `PART3_REQUIREMENTS_AND_DATABASE.md` | 5–7 | Functional Requirements, Non-Functional Requirements, Database Design |
| **PART 4** | `PART4_API_AND_DEVELOPMENT_CHECKLIST.md` | 8–9 | API Design (all endpoints), Development Checklist |
| **PART 5** | `PART5_DEVELOPMENT_PLAN_AND_STANDARDS.md` | 10–13 | Development Plan, Coding Standards, Testing Strategy, Deployment Plan |

**Total Coverage**: 13 sections across 5 files documenting every aspect of the Urji Beri School Management System.
