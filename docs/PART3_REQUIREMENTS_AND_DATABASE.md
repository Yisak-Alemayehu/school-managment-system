# Urji Beri School Management System — Pre-Development Documentation

## PART 3: FUNCTIONAL REQUIREMENTS, NON-FUNCTIONAL REQUIREMENTS & DATABASE DESIGN

**Document Version:** 1.0.0  
**Date:** February 27, 2026  
**Status:** Pre-Development Planning  

---

# TABLE OF CONTENTS — PART 3

5. [Functional Requirements](#5-functional-requirements)
   - 5.1 Authentication & Authorization Module
   - 5.2 Dashboard Module
   - 5.3 Academics Module
   - 5.4 Students Module
   - 5.5 Attendance Module
   - 5.6 Exams & Assessment Module
   - 5.7 Finance Module
   - 5.8 Communication Module
   - 5.9 Users Module
   - 5.10 Settings Module
   - 5.11 API Module
   - 5.12 User Roles & Permissions Matrix
6. [Non-Functional Requirements](#6-non-functional-requirements)
7. [Database Design](#7-database-design)

---

# 5. FUNCTIONAL REQUIREMENTS

## 5.1 Authentication & Authorization Module

### FR-AUTH-01: User Login
- **Description**: Users must authenticate using username/email and password
- **Acceptance Criteria**:
  - System accepts username OR email as the identifier
  - Passwords are verified using bcrypt (cost factor 12)
  - On success: create session, store user data, load permissions, redirect to dashboard or intended URL
  - On failure: increment attempt counter, show generic error message
  - After 5 failed attempts within 15 minutes: lock account for 15 minutes
  - Session ID is regenerated on successful login (session fixation prevention)
  - CSRF token is validated on login POST

### FR-AUTH-02: Session Management
- **Description**: Maintain authenticated state via PHP sessions
- **Acceptance Criteria**:
  - Session name: `urjiberi_session`
  - Session lifetime: 2 hours (7200 seconds)
  - Cookies: Secure (HTTPS), HttpOnly, SameSite=Lax
  - Session ID is regenerated every 30 minutes
  - Session stores: user_id, username, user_name, user_email, user_avatar, user_roles, user_role_ids, logged_in, login_time, permissions

### FR-AUTH-03: Account Lockout
- **Description**: Brute-force protection via progressive lockout
- **Acceptance Criteria**:
  - Track failed attempts per username/email in `login_attempts` table
  - Lock after 5 failures in 15-minute window
  - Display "Account locked" message with remaining minutes
  - Automatically unlock after lockout duration expires

### FR-AUTH-04: Logout
- **Description**: Terminate user session
- **Acceptance Criteria**:
  - Destroy PHP session
  - Clear session cookie
  - Redirect to login page

### FR-AUTH-05: Password Reset (Forgot Password)
- **Description**: Self-service password recovery
- **Acceptance Criteria**:
  - User submits email address
  - System generates unique reset token (64 hex chars) with 1-hour expiry
  - Reset URL is logged (email delivery is a TODO)
  - Generic success message shown regardless of email existence (prevents enumeration)
  - Token is validated on reset page
  - New password must meet minimum requirements (8+ chars)
  - Token is invalidated after use

### FR-AUTH-06: Change Password
- **Description**: Authenticated password change
- **Acceptance Criteria**:
  - Requires current password verification
  - New password hashed with bcrypt cost 12
  - `updated_at` timestamp is refreshed
  - Audit log entry created

### FR-AUTH-07: Profile Management
- **Description**: Users can update their own profile
- **Acceptance Criteria**:
  - Editable fields: full name, email, phone, avatar
  - Email uniqueness is validated (excluding current user)
  - Avatar upload with MIME validation (JPEG, PNG, GIF, WebP)
  - Old avatar file is deleted when replaced
  - Session data is refreshed after profile update
  - Audit log entry created

### FR-AUTH-08: Permission-Based Access Control
- **Description**: All routes are protected by permission checks
- **Acceptance Criteria**:
  - Super Admin has wildcard `*` permission (access to everything)
  - Permissions follow `module.action` naming (e.g., `students.view`, `students.create`)
  - `auth_require()` redirects unauthenticated users to login page (preserves intended URL)
  - `auth_require_permission()` renders 403 page for unauthorized access
  - Sidebar menu items are only shown for users with relevant permissions

---

## 5.2 Dashboard Module

### FR-DASH-01: Role-Based Dashboard
- **Description**: Display role-specific dashboard with relevant statistics and quick actions
- **Acceptance Criteria**:
  - **Super Admin / Admin**: Total students, total teachers, total classes, total revenue (this session), recent audit activity, today's attendance summary, recent announcements
  - **Teacher**: Assigned classes count, upcoming assignments, today's schedule, attendance pending indicator
  - **Student**: Enrolled class/section, upcoming exams, pending assignments, attendance percentage
  - **Parent**: Children overview (each child's class, attendance, recent grades), announcements
  - **Accountant**: Total revenue, pending invoices, overdue count, recent payments
  - All cards link to relevant module pages

---

## 5.3 Academics Module

### FR-ACAD-01: Academic Session Management
- **Description**: CRUD for academic sessions (school years)
- **Acceptance Criteria**:
  - Fields: name (e.g., "2025/2026"), slug (auto-generated), start_date, end_date, is_active
  - Only ONE session can be active at a time (toggling activates one, deactivates all others)
  - Slug uniqueness enforced
  - Permission: `academics.manage`

### FR-ACAD-02: Term/Semester Management
- **Description**: CRUD for terms within a session
- **Acceptance Criteria**:
  - Fields: session_id, name, slug, start_date, end_date, is_active, sort_order
  - end_date must be after start_date
  - Slug unique within a session
  - Permission: `academics.manage`

### FR-ACAD-03: Class Management
- **Description**: CRUD for classes (grade levels)
- **Acceptance Criteria**:
  - Fields: name, slug, numeric_name, description, medium_id, stream_id, shift_id, sort_order, is_active
  - Slug uniqueness enforced
  - Supports optional linkage to mediums, streams, shifts
  - Permission: `academics.manage`

### FR-ACAD-04: Section Management
- **Description**: CRUD for sections within a class
- **Acceptance Criteria**:
  - Fields: class_id, name (e.g., "A", "B"), capacity, is_active
  - Section name unique within a class
  - Permission: `academics.manage`

### FR-ACAD-05: Subject Management
- **Description**: CRUD for subjects
- **Acceptance Criteria**:
  - Fields: name, code (unique), description, type (theory/practical/both), is_active
  - Subject code uniqueness enforced
  - Permission: `academics.manage`

### FR-ACAD-06: Medium / Stream / Shift Management
- **Description**: CRUD for instruction mediums (e.g., English, Amharic), academic streams (e.g., Natural Science, Social Science), and shifts (e.g., Morning, Afternoon)
- **Acceptance Criteria**:
  - Each has: name, is_active, sort_order
  - Shifts additionally have start_time, end_time
  - Permission: `academics.manage`

### FR-ACAD-07: Class-Subject Assignment
- **Description**: Assign subjects to classes for a given session
- **Acceptance Criteria**:
  - Matrix UI: checkboxes for each subject per class
  - Supports `is_elective` flag per assignment
  - Replaces all existing assignments for the class/session combination (delete + re-insert)
  - Transaction-wrapped for atomicity
  - Permission: `academics.manage`

### FR-ACAD-08: Class Teacher Assignment
- **Description**: Assign homeroom teacher to class/section
- **Acceptance Criteria**:
  - One homeroom teacher per class/section/session combination
  - Sets `is_class_teacher=1`, `subject_id=NULL` in `class_teachers`
  - Supports delete and reassignment
  - Permission: `academics.manage`

### FR-ACAD-09: Subject Teacher Assignment
- **Description**: Assign teachers to teach specific subjects in specific classes
- **Acceptance Criteria**:
  - Maps teacher → class → section → subject → session
  - Sets `is_class_teacher=0`
  - Duplicate check (same teacher/class/section/subject/session)
  - Permission: `academics.manage`

### FR-ACAD-10: Student Elective Subject Selection
- **Description**: Assign elective subjects to individual students
- **Acceptance Criteria**:
  - Only elective class_subjects (where `is_elective=1`) are shown
  - Multiple elective selections per student
  - Replaces existing selections (delete + re-insert)
  - Permission: `academics.manage`

### FR-ACAD-11: Student Promotion
- **Description**: Bulk promote students between sessions/classes
- **Acceptance Criteria**:
  - Source: session, class, section → target: session, class, section
  - Per-student status: promoted, repeated, graduated
  - Process: close old enrollment (update status), create new enrollment
  - Record in `promotions` table with `promoted_by` and `promoted_at`
  - Transaction-wrapped
  - Permission: `academics.manage`

### FR-ACAD-12: Timetable Management
- **Description**: Create and manage weekly class timetables
- **Acceptance Criteria**:
  - Fields: session, term, class, section, subject, teacher, day_of_week, start_time, end_time, room
  - End time must be after start time
  - Conflicts are tracked (same class/section at same day/time)
  - Grid view: 7 days × N periods
  - Permission: `timetable.manage` (view: `timetable.view`)

---

## 5.4 Students Module

### FR-STU-01: Student Admission (Create)
- **Description**: Register new students with full personal information
- **Acceptance Criteria**:
  - Fields: first_name, last_name, gender, date_of_birth, blood_group, religion, nationality, mother_tongue, phone, email, address, city, region, photo, previous_school, admission_no (unique), admission_date
  - Auto-generate admission_no if not provided
  - Photo upload with MIME validation + organized storage (`students/YYYY/MM/`)
  - Simultaneously create enrollment record (session, class, section)
  - Create/link guardian records (1+ guardians per student)
  - Permission: `students.create`

### FR-STU-02: Student Edit/Update
- **Description**: Update existing student information
- **Acceptance Criteria**:
  - All admission fields are editable
  - Photo can be replaced (old photo deleted)
  - Guardian links can be modified
  - Audit log entry created
  - Permission: `students.edit`

### FR-STU-03: Student Listing & Search
- **Description**: Paginated, filterable list of students
- **Acceptance Criteria**:
  - Filters: class, section, status (active/inactive/graduated/transferred/expelled), search by name/admission_no
  - Default: 20 per page, max: 100 per page
  - Shows: photo, name, class, section, roll number, status, admission date
  - Teacher role: only sees students in assigned classes
  - Student role: only sees own record
  - Parent role: only sees linked children
  - Permission: `students.view`

### FR-STU-04: Student Profile View
- **Description**: Detailed view of a single student's information
- **Acceptance Criteria**:
  - Personal info, enrollment history, guardian(s), attendance summary, fee status
  - Permission: `students.view` (RBAC-filtered)

### FR-STU-05: Student Soft Delete
- **Description**: Mark student as deleted without permanent removal
- **Acceptance Criteria**:
  - Sets `deleted_at` timestamp on `students` table
  - Deleted students are excluded from all queries (WHERE deleted_at IS NULL)
  - Permission: `students.delete`

### FR-STU-06: CSV Bulk Import
- **Description**: Import multiple students from a CSV file
- **Acceptance Criteria**:
  - Sample CSV template available for download
  - Parse and validate each row
  - Create student + enrollment records in a transaction
  - Report success count and per-row error details
  - Permission: `students.create`

### FR-STU-07: Student Export
- **Description**: Export student data as CSV
- **Acceptance Criteria**:
  - Supports class/section/status filters
  - Downloads CSV file with headers
  - Permission: `students.view`

### FR-STU-08: Student Credential Generation
- **Description**: Create user accounts for students with auto-generated credentials
- **Acceptance Criteria**:
  - Generate username (e.g., student's admission_no)
  - Generate random password
  - Create user record + assign "student" role
  - Link user_id to student record
  - Display/download generated credentials
  - Permission: `students.create`

### FR-STU-09: Roll Number Assignment
- **Description**: Assign sequential roll numbers to students within a section
- **Acceptance Criteria**:
  - Inline editing of roll_number per student
  - Bulk save for entire section at once
  - Permission: `students.edit`

### FR-STU-10: Student ID Card Generation
- **Description**: Generate printable student ID cards
- **Acceptance Criteria**:
  - Filter by class/section
  - Card shows: photo, name, class, section, admission_no, school branding
  - Print-optimized A4 layout (multiple cards per page)
  - Permission: `students.view`

### FR-STU-11: Student Password Reset
- **Description**: Admin/teacher can reset a student's login password
- **Acceptance Criteria**:
  - Generate new random password
  - Hash and update user record
  - Display new password to admin
  - Permission: `students.edit`

---

## 5.5 Attendance Module

### FR-ATT-01: Take Daily Attendance
- **Description**: Mark daily attendance for a class/section
- **Acceptance Criteria**:
  - Filter by: class, section, date
  - Date must not be in the future
  - Status per student: present, absent, late, excused
  - Optional remarks per student
  - Records who marked attendance (`marked_by`)
  - Associates with active term
  - Upsert: update existing record if re-marking
  - Transaction-wrapped
  - Permission: `attendance.manage`

### FR-ATT-02: View Daily Attendance
- **Description**: Read-only view of attendance for a specific date/class
- **Acceptance Criteria**:
  - Summary statistics: total present, absent, late, excused
  - Color-coded status indicators
  - Permission: `attendance.view`

### FR-ATT-03: Attendance Report
- **Description**: Generate attendance report for a date range
- **Acceptance Criteria**:
  - Filter: class, section, date range
  - Matrix view: students (rows) × dates (columns)
  - Print-optimized A4 landscape layout
  - Summary percentages per student
  - Permission: `attendance.view`

### FR-ATT-04: Individual Student Attendance
- **Description**: View attendance history for a single student
- **Acceptance Criteria**:
  - Full attendance history with date and status
  - Summary counters: total present, absent, late, excused
  - Percentage calculation
  - Permission: `attendance.view` (RBAC-filtered)

---

## 5.6 Exams & Assessment Module

### FR-EXAM-01: Exam Management
- **Description**: CRUD for exam definitions
- **Acceptance Criteria**:
  - Fields: name, description, session_id, term_id, type (midterm/final/quiz/test/practical/mock), start_date, end_date, status (upcoming/ongoing/completed/cancelled)
  - Permission: `exams.manage`

### FR-EXAM-02: Exam Schedule Management
- **Description**: Schedule exams per class/subject
- **Acceptance Criteria**:
  - Fields: exam_id, class_id, subject_id, exam_date, start_time, end_time, room, max_marks, pass_marks
  - Unique per exam+class+subject combination
  - Permission: `exams.manage`

### FR-EXAM-03: Marks Entry
- **Description**: Enter exam marks per student
- **Acceptance Criteria**:
  - Select exam → class → subject
  - Enter marks_obtained per student (0 to max_marks)
  - Support is_absent flag
  - Optional remarks
  - Upsert: update existing marks if re-entering
  - Permission: `exams.manage`

### FR-EXAM-04: Grade Scale Management
- **Description**: Define grading criteria
- **Acceptance Criteria**:
  - Grade scale with entries: grade letter, min_percentage, max_percentage, grade_point, remark
  - Default Ethiopian scale: A+ (95-100), A (85-94.99), B+ (75-84.99), B (65-74.99), C+ (55-64.99), C (45-54.99), D (35-44.99), F (0-34.99)
  - Permission: `exams.manage`

### FR-EXAM-05: Assessment Definitions (Results Module)
- **Description**: Define assessment records per class/subject/term
- **Acceptance Criteria**:
  - Fields: name, class_id, subject_id, session_id, term_id, total_marks (always 100)
  - Index on (class_id, subject_id, session_id, term_id) for unique lookup
  - Permission: `exams.manage`

### FR-EXAM-06: Assessment Results Entry
- **Description**: Enter student marks against assessments
- **Acceptance Criteria**:
  - 6-step wizard: Term → Class → Section → Subject → Assessment → Marks
  - Enter marks_obtained per student (0 to 100)
  - Support is_absent flag
  - Upsert via assessment_id + student_id unique key
  - Permission: `exams.manage`

### FR-EXAM-07: Student Conduct Entry
- **Description**: Record behavioral conduct grades per student
- **Acceptance Criteria**:
  - Grades: A (Excellent), B (Very Good), C (Good), D (Satisfactory), F (Needs Improvement)
  - Per student, per class, per session, per term
  - Optional remarks
  - Independent of academic marks
  - Permission: `exams.manage`

### FR-EXAM-08: Report Card Generation
- **Description**: Generate comprehensive report cards
- **Acceptance Criteria**:
  - Calculate: total marks, total_max_marks, percentage, grade, rank
  - Include: all subject marks, conduct grade, attendance summary
  - Insert into `report_cards` table
  - Support teacher and principal remarks
  - Permission: `exams.manage`

### FR-EXAM-09: Report Card Printing
- **Description**: Print A4 portrait report cards
- **Acceptance Criteria**:
  - One card per student per page
  - School header with logo and branding
  - Subject-wise marks table with totals and averages
  - Rank display
  - Conduct grade
  - QR verification code

### FR-EXAM-10: Class Roster
- **Description**: Full class roster view
- **Acceptance Criteria**:
  - All subjects × all students matrix
  - Per-student: total marks per subject, grand total, average, rank, conduct
  - Printable format
  - Permission: `exams.view`

### FR-EXAM-11: Result Analysis
- **Description**: Statistical analysis of exam results
- **Acceptance Criteria**:
  - Grade distribution (count per grade range)
  - Pass/fail ratios
  - Gender-based breakdown
  - Subject-wise analysis
  - Permission: `exams.view`

### FR-EXAM-12: Assignment Management
- **Description**: CRUD for homework/class assignments
- **Acceptance Criteria**:
  - Fields: title, description, class, section, subject, session, term, teacher, max_score, due_date, attachment, status (draft/published/closed)
  - Teacher RBAC: only manage own assignments
  - Permission: `exams.manage`

---

## 5.7 Finance Module

### FR-FIN-01: Fee Category Management
- **Description**: CRUD for fee categories
- **Acceptance Criteria**:
  - Fields: name, code (unique), description, type (tuition/registration/transport/lab/library/exam/uniform/other), status
  - Permission: `finance.manage`

### FR-FIN-02: Fee Structure Management
- **Description**: Define fee amounts per session/class/category
- **Acceptance Criteria**:
  - Fields: session_id, class_id, fee_category_id, term_id (optional), amount, due_date, is_mandatory, description
  - Unique per session+class+category+term combination
  - Permission: `finance.manage`

### FR-FIN-03: Fee Discount/Scholarship Management
- **Description**: Define and assign discounts/scholarships
- **Acceptance Criteria**:
  - Define: name, type (percentage/fixed), value, description, status
  - Assign: link discount to student for a session + optional fee category
  - Permission: `finance.manage`

### FR-FIN-04: Invoice Generation
- **Description**: Generate student invoices
- **Acceptance Criteria**:
  - Auto-generate invoice_no (unique)
  - Create invoice with: student_id, session_id, term_id, class_id
  - Add invoice_items from fee_structures
  - Calculate: subtotal, discount_amount, fine_amount, total_amount, balance
  - Support: draft → issued → partial → paid → overdue → cancelled → refunded status flow
  - Permission: `finance.manage`

### FR-FIN-05: Payment Recording
- **Description**: Record manual/cash/bank payments against invoices
- **Acceptance Criteria**:
  - Auto-generate receipt_no (unique)
  - Fields: invoice_id, student_id, amount, method (cash/bank_transfer/cheque/gateway/other), reference, notes, payment_date
  - Update invoice: paid_amount, balance, status
  - Permission: `finance.manage`

### FR-FIN-06: Online Payment (Telebirr/Chapa)
- **Description**: Accept payments via mobile money (Telebirr) and Chapa
- **Acceptance Criteria**:
  - Create payment_transaction with idempotency key
  - **Telebirr**: RSA-encrypt parameters, POST to Telebirr API, redirect to checkout URL
  - **Chapa**: POST to Chapa API v1/transaction/initialize, redirect to checkout URL
  - Handle callback/webhook verification
  - Update invoice and payment records on success
  - Transaction state management: pending → success/failed/cancelled
  - Permission: Students/parents can initiate own payments

### FR-FIN-07: Payment Receipt Printing
- **Description**: Print payment receipts
- **Acceptance Criteria**:
  - A4 portrait layout with school branding
  - Shows: receipt_no, student name, class, payment details, amount, date
  - Permission: `finance.view`

### FR-FIN-08: Advanced Fee Management (v2)
- **Description**: Enhanced fee system with groups, assignments, recurrence, penalties
- **Acceptance Criteria**:
  - **Fee Definitions**: Create fees with type (one_time/recurrent), effective/end dates, status lifecycle (draft → active → inactive → archived)
  - **Student Groups**: Create named groups, add/remove students, assign fees to groups
  - **Fee Assignments**: Assign fees to individual students or student groups
  - **Fee Exemptions**: Exempt specific students from fees with reasons
  - **Student Fee Charges**: Generate per-student charge records from assignments (amount_charged, amount_paid, due_date, status)
  - **Recurrence**: Auto-generate recurring fee charges based on frequency (daily/weekly/monthly/yearly)
  - **Penalties**: Auto-calculate late payment penalties (fixed or percentage) with grace periods and caps
  - Permission: `fee_management.*` (12 sub-permissions)

### FR-FIN-09: Financial Reports
- **Description**: Generate finance reports
- **Acceptance Criteria**:
  - Revenue by class, by fee category, by payment method
  - Outstanding balances report
  - Date-filtered collection report
  - Export to CSV
  - Permission: `finance.reports`

---

## 5.8 Communication Module

### FR-COM-01: Announcement Management
- **Description**: CRUD for school announcements
- **Acceptance Criteria**:
  - Fields: title, content, type (general/academic/event/emergency/other), target_roles, target_classes, attachment, is_pinned, status (draft/published/archived), published_at, expires_at
  - Announcements can be targeted to specific roles or classes
  - Pinned announcements appear at the top
  - Permission: `announcements.manage` (view: `announcements.view`)

### FR-COM-02: Internal Messaging
- **Description**: User-to-user private messaging
- **Acceptance Criteria**:
  - Compose: select recipient (any active user), subject, body
  - Inbox with read/unread status
  - Sent messages view
  - Reply support (parent_id linking)
  - Self-messaging prevention
  - Notification created for receiver on new message
  - Permission: `messages.send` (view: `messages.view`)

### FR-COM-03: Notifications
- **Description**: In-app notification system
- **Acceptance Criteria**:
  - System-generated on: new message, new announcement, payment status change, assignment posted
  - Fields: type, title, message, link, data (JSON), is_read, read_at
  - Mark individual notification as read
  - Mark all notifications as read (bulk action)
  - Unread count displayed in header bell icon
  - Permission: All authenticated users

---

## 5.9 Users Module

### FR-USR-01: User Management
- **Description**: CRUD for system users (staff accounts)
- **Acceptance Criteria**:
  - Create: full_name, username (unique), email (unique), phone, password (hashed), role assignment (checkbox multi-select)
  - Edit: all fields except password; role reassignment
  - View: user profile + last 20 audit log entries
  - Delete: soft-delete via `deleted_at`
  - Toggle: active/inactive status
  - Permission: `users.manage`

### FR-USR-02: Role Assignment
- **Description**: Assign one or more roles to a user
- **Acceptance Criteria**:
  - Available roles: Super Admin, Admin, Teacher, Student, Parent, Accountant, Librarian
  - Multi-role support (a user can have Teacher + Admin)
  - Stored in `user_roles` join table
  - Role delete clears all assignments (CASCADE)

---

## 5.10 Settings Module

### FR-SET-01: General Settings
- **Description**: System-wide configuration management
- **Acceptance Criteria**:
  - Settings stored in `settings` table as key-value pairs
  - Grouped by `setting_group` (general, school, email, etc.)
  - Supports types: string, integer, boolean, json, text, textarea, number
  - Auto-generated form from database records
  - Permission: `settings.manage`

### FR-SET-02: Audit Log Viewer
- **Description**: Browse system audit trail
- **Acceptance Criteria**:
  - Filterable by: user, action, module, date range
  - Shows: timestamp, user, action, module, entity, IP address
  - Paginated (max 100 per page)
  - Permission: `settings.manage`

### FR-SET-03: Database Backup
- **Description**: Create and manage database backups
- **Acceptance Criteria**:
  - Trigger manual backup (mysqldump)
  - List existing backup files with size and date
  - Download backup files
  - Restore from backup file
  - Storage path: `storage/backups/`
  - Permission: `settings.manage` (super_admin only recommended)

---

## 5.11 API Module

### FR-API-01: Internal JSON Endpoints
- **Description**: AJAX data endpoints for dynamic UI components
- **Acceptance Criteria**:
  - `GET ?module=api&action=sections&class_id={id}` → JSON array of sections for a class
  - `GET ?module=api&action=subjects&class_id={id}&session_id={id}` → JSON array of subjects assigned to a class in a session
  - JSON Content-Type header
  - No-cache headers
  - Authentication required

---

## 5.12 User Roles & Permissions Matrix

### Roles

| # | Role | Slug | is_system | Description |
|---|------|------|-----------|-------------|
| 1 | Super Admin | `super_admin` | Yes | Full system access (wildcard `*` permission) |
| 2 | Admin | `admin` | Yes | School administration (all modules except system settings) |
| 3 | Teacher | `teacher` | Yes | Teaching, attendance, exams for assigned classes |
| 4 | Student | `student` | Yes | View own records, attendance, grades, pay fees |
| 5 | Parent | `parent` | Yes | View linked children's records |
| 6 | Accountant | `accountant` | Yes | Finance module access |
| 7 | Librarian | `librarian` | Yes | Library management (future module) |

### Permissions (~75 permissions)

| Module | Permission | Description | Roles |
|--------|-----------|-------------|-------|
| **dashboard** | `dashboard.view` | View dashboard | All |
| **academics** | `academics.manage` | Manage classes, sessions, subjects, etc. | Super Admin, Admin |
| **students** | `students.view` | View student list/profiles | Super Admin, Admin, Teacher, Student*, Parent* |
| **students** | `students.create` | Create/admit students | Super Admin, Admin |
| **students** | `students.edit` | Edit student records | Super Admin, Admin |
| **students** | `students.delete` | Delete students | Super Admin, Admin |
| **attendance** | `attendance.view` | View attendance records | Super Admin, Admin, Teacher, Student*, Parent* |
| **attendance** | `attendance.manage` | Take/edit attendance | Super Admin, Admin, Teacher |
| **exams** | `exams.view` | View exams, marks, results | Super Admin, Admin, Teacher, Student*, Parent* |
| **exams** | `exams.manage` | Manage exams, enter marks | Super Admin, Admin, Teacher |
| **finance** | `finance.view` | View invoices, payments | Super Admin, Admin, Accountant, Student*, Parent* |
| **finance** | `finance.manage` | Manage fees, invoices, payments | Super Admin, Admin, Accountant |
| **finance** | `finance.reports` | View financial reports | Super Admin, Admin, Accountant |
| **announcements** | `announcements.view` | View announcements | All |
| **announcements** | `announcements.manage` | Create/edit announcements | Super Admin, Admin |
| **messages** | `messages.view` | View inbox/sent | All |
| **messages** | `messages.send` | Send messages | All |
| **users** | `users.view` | View user list | Super Admin, Admin |
| **users** | `users.manage` | Manage users/roles | Super Admin, Admin |
| **settings** | `settings.manage` | Manage system settings | Super Admin |
| **timetable** | `timetable.view` | View timetables | Super Admin, Admin, Teacher |
| **timetable** | `timetable.manage` | Manage timetables | Super Admin, Admin |
| **fee_management** | `fee_management.view_fees` | View fee definitions | Super Admin, Admin, Accountant |
| **fee_management** | `fee_management.manage_fees` | Create/edit fees | Super Admin, Admin, Accountant |
| **fee_management** | `fee_management.delete_fees` | Delete fees | Super Admin, Admin |
| **fee_management** | `fee_management.view_groups` | View student groups | Super Admin, Admin, Accountant |
| **fee_management** | `fee_management.manage_groups` | Manage student groups | Super Admin, Admin, Accountant |
| **fee_management** | `fee_management.assign_fees` | Assign fees to students/groups | Super Admin, Admin, Accountant |
| **fee_management** | `fee_management.manage_exemptions` | Manage fee exemptions | Super Admin, Admin |
| **fee_management** | `fee_management.record_payments` | Record manual payments | Super Admin, Admin, Accountant |
| **fee_management** | `fee_management.view_reports` | View fee reports | Super Admin, Admin, Accountant |
| **fee_management** | `fee_management.manage_penalties` | Manage penalty rules | Super Admin, Admin |
| **fee_management** | `fee_management.waive_charges` | Waive student charges | Super Admin, Admin |

\* *Starred roles have RBAC-filtered access (only own data or linked children data)*

---

# 6. NON-FUNCTIONAL REQUIREMENTS

## 6.1 Performance Requirements

| ID | Requirement | Target |
|----|------------|--------|
| NFR-PERF-01 | Page load time (server response) | < 500ms for standard pages |
| NFR-PERF-02 | API response time (JSON endpoints) | < 200ms |
| NFR-PERF-03 | Maximum concurrent users | 200+ (configurable via server tuning) |
| NFR-PERF-04 | Database query time (single query) | < 100ms average |
| NFR-PERF-05 | File upload processing | < 3 seconds for 5MB file |
| NFR-PERF-06 | CSV import throughput | 100+ students per minute |
| NFR-PERF-07 | Report generation (class roster) | < 5 seconds for 100 students |
| NFR-PERF-08 | Pagination limits | Default: 20, Max: 100 per page |

## 6.2 Security Requirements

| ID | Requirement | Implementation |
|----|------------|----------------|
| NFR-SEC-01 | Password hashing | bcrypt with cost factor 12 |
| NFR-SEC-02 | CSRF protection | Per-session token, validated on all state-changing requests |
| NFR-SEC-03 | XSS prevention | All output escaped via `e()` function (htmlspecialchars) |
| NFR-SEC-04 | SQL injection prevention | Prepared statements (PDO) for all queries |
| NFR-SEC-05 | Session security | Secure + HttpOnly + SameSite cookies; ID regeneration every 30 min |
| NFR-SEC-06 | Brute-force protection | 5 attempts / 15-minute lockout |
| NFR-SEC-07 | File upload security | MIME validation, max 5MB, restricted types, unique filenames |
| NFR-SEC-08 | Directory traversal prevention | Upload proxy (`uploads.php`) validates paths |
| NFR-SEC-09 | HTTP security headers | X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, HSTS (if HTTPS) |
| NFR-SEC-10 | Audit trail | All state-changing operations logged with user, IP, timestamp |
| NFR-SEC-11 | Input sanitization | All input trimmed and stripped of HTML tags |
| NFR-SEC-12 | Rate limiting | Configurable per-IP rate limits on sensitive endpoints |

## 6.3 Reliability Requirements

| ID | Requirement | Target |
|----|------------|--------|
| NFR-REL-01 | System availability | 99.5% uptime (excluding scheduled maintenance) |
| NFR-REL-02 | Data integrity | Transaction-wrapped operations for multi-table writes |
| NFR-REL-03 | Error handling | Graceful error pages (403, 404); debug-only stack traces |
| NFR-REL-04 | Database backup | Manual backup capability; recommended daily automated |
| NFR-REL-05 | Data recovery | Point-in-time recovery from SQL backup files |
| NFR-REL-06 | Idempotency | Payment transactions use idempotency keys (24h TTL) |

## 6.4 Usability Requirements

| ID | Requirement | Target |
|----|------------|--------|
| NFR-USE-01 | Responsive design | Mobile-first, supports 320px to 4K screens |
| NFR-USE-02 | Offline support | PWA with service worker, offline fallback page |
| NFR-USE-03 | Accessibility | Semantic HTML, ARIA labels, keyboard navigation |
| NFR-USE-04 | Print optimization | A4 print layouts for reports, ID cards, receipts |
| NFR-USE-05 | Flash messages | Success/error/warning/info feedback on every action |
| NFR-USE-06 | Form persistence | Old input values preserved on validation errors |
| NFR-USE-07 | Search | Text search on student names, admission numbers, user names |

## 6.5 Compatibility Requirements

| ID | Requirement | Target |
|----|------------|--------|
| NFR-COM-01 | PHP version | 8.1+ (8.2 recommended) |
| NFR-COM-02 | MySQL version | 8.0+ (utf8mb4, InnoDB) |
| NFR-COM-03 | Browser support | Chrome 90+, Firefox 88+, Safari 14+, Edge 90+ |
| NFR-COM-04 | Server | Apache 2.4+ or Nginx 1.18+ |
| NFR-COM-05 | Hosting | Shared hosting (cPanel) compatible, no shell access required |
| NFR-COM-06 | PHP extensions | PDO, OpenSSL, cURL, mbstring, json, fileinfo |
| NFR-COM-07 | SSL/TLS | HTTPS required for production (Let's Encrypt recommended) |

## 6.6 Scalability Requirements

| ID | Requirement | Notes |
|----|------------|-------|
| NFR-SCA-01 | Student capacity | 5,000+ students per school |
| NFR-SCA-02 | User capacity | 500+ concurrent authenticated sessions |
| NFR-SCA-03 | Database size | Designed for 100K+ audit log entries, 50K+ attendance records |
| NFR-SCA-04 | File storage | Organized by year/month subdirectories |

## 6.7 Maintainability Requirements

| ID | Requirement | Implementation |
|----|------------|----------------|
| NFR-MNT-01 | Code style | PSR-12 inspired naming; consistent patterns across all modules |
| NFR-MNT-02 | Modular architecture | Independent modules with isolated routes/actions/views |
| NFR-MNT-03 | Database migrations | Numbered SQL files executed sequentially |
| NFR-MNT-04 | Environment configuration | `.env` file for secrets; no credentials in code |
| NFR-MNT-05 | Error logging | PHP errors to `logs/` directory |

---

# 7. DATABASE DESIGN

## 7.1 Database Overview

| Property | Value |
|----------|-------|
| **RDBMS** | MySQL 8.0+ |
| **Database Name** | `urjiberi_school` |
| **Character Set** | `utf8mb4` |
| **Collation** | `utf8mb4_unicode_ci` |
| **Storage Engine** | InnoDB (all tables) |
| **Total Tables** | 54 |
| **SQL Mode** | `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION` |

## 7.2 Entity-Relationship Summary

### Core Relationships

```
roles ──< role_permissions >── permissions
users ──< user_roles >── roles
users ──< login_attempts
users ──< audit_logs

academic_sessions ──< terms
academic_sessions ──< enrollments
academic_sessions ──< class_subjects
academic_sessions ──< class_teachers

classes ──< sections
classes ──< class_subjects
classes ──< class_teachers
classes ──< enrollments
classes ──< timetables

subjects ──< class_subjects
subjects ──< class_teachers
subjects ──< timetables

students >── users (optional)
students ──< student_guardians >── guardians
students ──< enrollments
students ──< promotions
students ──< student_documents
students ──< attendance
students ──< marks
students ──< student_results
students ──< student_conduct
students ──< assignments (via submissions)
students ──< invoices
students ──< payments

exams ──< exam_schedules
exams ──< marks

assessments ──< student_results

invoices ──< invoice_items
invoices ──< payments
invoices ──< invoice_payment_links

payment_gateways ──< payment_transactions
payment_transactions ──< payment_attempts
payment_transactions ──< payment_webhooks

fees ──< recurrence_configs (1:1)
fees ──< penalty_configs (1:1)
fees ──< fee_assignments
student_groups ──< student_group_members
student_fee_charges ──< penalty_charges
```

## 7.3 Complete Table Definitions

### 7.3.1 Authentication & Authorization Tables

#### Table: `roles`

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK, AI | — | Role ID |
| name | VARCHAR(50) | NO | | — | Display name (e.g., "Super Admin") |
| slug | VARCHAR(50) | NO | UQ | — | URL-safe identifier (e.g., "super_admin") |
| description | VARCHAR(255) | YES | | NULL | Role description |
| is_system | TINYINT(1) | NO | | 0 | System role (cannot be deleted) |
| created_at | DATETIME | NO | | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | | CURRENT_TIMESTAMP ON UPDATE | |

**Indexes**: `uk_roles_slug` (UNIQUE on slug)

---

#### Table: `permissions`

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK, AI | — | Permission ID |
| module | VARCHAR(50) | NO | IDX | — | Module name (e.g., "students") |
| action | VARCHAR(50) | NO | | — | Action name (e.g., "view") |
| description | VARCHAR(255) | YES | | NULL | Permission description |
| created_at | DATETIME | NO | | CURRENT_TIMESTAMP | |

**Indexes**: `uk_permissions_module_action` (UNIQUE on module, action), `idx_permissions_module`

---

#### Table: `role_permissions`

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| role_id | BIGINT UNSIGNED | NO | PK, FK | — | → roles.id (CASCADE) |
| permission_id | BIGINT UNSIGNED | NO | PK, FK | — | → permissions.id (CASCADE) |
| created_at | DATETIME | NO | | CURRENT_TIMESTAMP | |

**Composite Primary Key**: (role_id, permission_id)

---

#### Table: `users`

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK, AI | — | User ID |
| username | VARCHAR(100) | NO | UQ | — | Login username |
| email | VARCHAR(255) | NO | UQ | — | Email address |
| password_hash | VARCHAR(255) | NO | | — | bcrypt hash |
| full_name | VARCHAR(200) | NO | | — | Display name |
| first_name | VARCHAR(100) | YES | | NULL | First name |
| last_name | VARCHAR(100) | YES | | NULL | Last name |
| phone | VARCHAR(20) | YES | | NULL | Phone number |
| avatar | VARCHAR(255) | YES | | NULL | Avatar file path |
| gender | ENUM('male','female','other') | YES | | NULL | Gender |
| date_of_birth | DATE | YES | | NULL | Date of birth |
| address | TEXT | YES | | NULL | Address |
| is_active | TINYINT(1) | NO | | 1 | Active flag |
| force_password_change | TINYINT(1) | NO | | 0 | Require password change on next login |
| status | ENUM('active','inactive','suspended') | NO | IDX | 'active' | Account status |
| email_verified_at | DATETIME | YES | | NULL | Email verification timestamp |
| last_login_at | DATETIME | YES | | NULL | Last successful login |
| last_login_ip | VARCHAR(45) | YES | | NULL | Last login IP address |
| password_reset_token | VARCHAR(255) | YES | | NULL | Reset token (64 hex chars) |
| password_reset_expires | DATETIME | YES | | NULL | Token expiry |
| login_attempts | INT UNSIGNED | NO | | 0 | Failed login counter |
| locked_until | DATETIME | YES | | NULL | Account lockout expiry |
| created_at | DATETIME | NO | | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | | CURRENT_TIMESTAMP ON UPDATE | |
| deleted_at | DATETIME | YES | IDX | NULL | Soft delete timestamp |

**Indexes**: `uk_users_username`, `uk_users_email`, `idx_users_status`, `idx_users_deleted`

---

#### Table: `user_roles`

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| user_id | BIGINT UNSIGNED | NO | PK, FK | — | → users.id (CASCADE) |
| role_id | BIGINT UNSIGNED | NO | PK, FK | — | → roles.id (CASCADE) |
| assigned_at | DATETIME | NO | | CURRENT_TIMESTAMP | |

---

#### Table: `login_attempts`

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK, AI | — | |
| username_or_email | VARCHAR(255) | NO | IDX | — | Attempted identifier |
| ip_address | VARCHAR(45) | NO | IDX | — | Client IP |
| user_agent | VARCHAR(500) | YES | | NULL | Browser user agent |
| success | TINYINT(1) | NO | | 0 | Attempt result |
| attempted_at | DATETIME | NO | | CURRENT_TIMESTAMP | |

**Indexes**: `idx_login_attempts_identifier` (username_or_email, attempted_at), `idx_login_attempts_ip` (ip_address, attempted_at)

---

### 7.3.2 Academics Tables

#### Table: `academic_sessions`

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK, AI | — | |
| name | VARCHAR(100) | NO | | — | Session name (e.g., "2025/2026") |
| slug | VARCHAR(100) | NO | UQ | — | URL-safe identifier |
| start_date | DATE | NO | | — | Session start |
| end_date | DATE | NO | | — | Session end |
| is_active | TINYINT(1) | NO | IDX | 0 | Active session flag (only 1 active) |
| created_at | DATETIME | NO | | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | | CURRENT_TIMESTAMP ON UPDATE | |

---

#### Table: `terms`

| Column | Type | Null | Key | Default | FK |
|--------|------|------|-----|---------|-----|
| id | BIGINT UNSIGNED | NO | PK, AI | — | |
| session_id | BIGINT UNSIGNED | NO | FK | — | → academic_sessions.id (CASCADE) |
| name | VARCHAR(100) | NO | | — | |
| slug | VARCHAR(100) | NO | | — | |
| start_date | DATE | NO | | — | |
| end_date | DATE | NO | | — | |
| is_active | TINYINT(1) | NO | IDX | 0 | |
| sort_order | INT UNSIGNED | NO | | 0 | |
| created_at / updated_at | DATETIME | NO | | CURRENT_TIMESTAMP | |

**Indexes**: `uk_terms_session_slug` (UNIQUE on session_id, slug)

---

#### Table: `classes`

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK, AI | — | |
| name | VARCHAR(100) | NO | | — | Class name (e.g., "Grade 9") |
| slug | VARCHAR(100) | NO | UQ | — | URL slug |
| numeric_name | INT UNSIGNED | YES | | NULL | Numeric grade level |
| description | VARCHAR(255) | YES | | NULL | |
| medium_id | BIGINT UNSIGNED | YES | | NULL | Soft ref → mediums.id |
| stream_id | BIGINT UNSIGNED | YES | | NULL | Soft ref → streams.id |
| shift_id | BIGINT UNSIGNED | YES | | NULL | Soft ref → shifts.id |
| sort_order | INT UNSIGNED | NO | | 0 | Display order |
| is_active | TINYINT(1) | NO | | 1 | |
| status | ENUM('active','inactive') | NO | | 'active' | |
| created_at / updated_at | DATETIME | NO | | CURRENT_TIMESTAMP | |

---

#### Table: `sections`

| Column | Type | Null | Key | Default | FK |
|--------|------|------|-----|---------|-----|
| id | BIGINT UNSIGNED | NO | PK, AI | — | |
| class_id | BIGINT UNSIGNED | NO | FK | — | → classes.id (CASCADE) |
| name | VARCHAR(50) | NO | | — | Section letter/name |
| capacity | INT UNSIGNED | YES | | NULL | Max students |
| is_active / status | TINYINT(1) / ENUM | NO | | 1 / 'active' | |
| created_at / updated_at | DATETIME | NO | | CURRENT_TIMESTAMP | |

**Indexes**: `uk_sections_class_name` (UNIQUE on class_id, name)

---

#### Table: `subjects`

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK, AI | — | |
| name | VARCHAR(100) | NO | | — | Subject name |
| code | VARCHAR(20) | NO | UQ | — | Unique code (e.g., "MATH") |
| description | TEXT | YES | | NULL | |
| type | ENUM('theory','practical','both') | NO | | 'theory' | |
| is_active / status | TINYINT(1) / ENUM | NO | | 1 / 'active' | |
| created_at / updated_at | DATETIME | NO | | CURRENT_TIMESTAMP | |

---

#### Table: `mediums`

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AI | |
| name | VARCHAR(100) | NO | — | Medium name (e.g., "English") |
| is_active | TINYINT(1) | NO | 1 | |
| sort_order | INT UNSIGNED | NO | 0 | |
| created_at / updated_at | DATETIME | NO | CURRENT_TIMESTAMP | |

---

#### Table: `streams`

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AI | |
| name | VARCHAR(100) | NO | — | Stream name (e.g., "Natural Science") |
| description | VARCHAR(255) | YES | NULL | |
| is_active | TINYINT(1) | NO | 1 | |
| sort_order | INT UNSIGNED | NO | 0 | |
| created_at / updated_at | DATETIME | NO | CURRENT_TIMESTAMP | |

---

#### Table: `shifts`

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AI | |
| name | VARCHAR(100) | NO | — | Shift name (e.g., "Morning") |
| start_time | TIME | YES | NULL | |
| end_time | TIME | YES | NULL | |
| is_active | TINYINT(1) | NO | 1 | |
| sort_order | INT UNSIGNED | NO | 0 | |
| created_at / updated_at | DATETIME | NO | CURRENT_TIMESTAMP | |

---

#### Table: `class_subjects`

| Column | Type | Null | Key | FK |
|--------|------|------|-----|-----|
| id | BIGINT UNSIGNED | NO | PK, AI | |
| class_id | BIGINT UNSIGNED | NO | FK | → classes.id (CASCADE) |
| subject_id | BIGINT UNSIGNED | NO | FK | → subjects.id (CASCADE) |
| session_id | BIGINT UNSIGNED | NO | FK, IDX | → academic_sessions.id (CASCADE) |
| is_elective | TINYINT(1) | NO | | Default: 0 |
| created_at | DATETIME | NO | | CURRENT_TIMESTAMP |

**Indexes**: `uk_class_subjects` (UNIQUE on class_id, subject_id, session_id)

---

#### Table: `class_teachers`

| Column | Type | Null | Key | FK |
|--------|------|------|-----|-----|
| id | BIGINT UNSIGNED | NO | PK, AI | |
| class_id | BIGINT UNSIGNED | NO | FK | → classes.id (CASCADE) |
| section_id | BIGINT UNSIGNED | YES | FK | → sections.id (SET NULL) |
| subject_id | BIGINT UNSIGNED | YES | FK | → subjects.id (CASCADE) |
| teacher_id | BIGINT UNSIGNED | NO | FK, IDX | → users.id (CASCADE) |
| session_id | BIGINT UNSIGNED | NO | FK, IDX | → academic_sessions.id (CASCADE) |
| is_class_teacher | TINYINT(1) | NO | | Default: 0 |
| created_at / updated_at | DATETIME | NO | | |

**Indexes**: `uk_class_teachers` (UNIQUE on class_id, section_id, subject_id, teacher_id, session_id)

---

#### Table: `timetables`

| Column | Type | Null | Key | FK |
|--------|------|------|-----|-----|
| id | BIGINT UNSIGNED | NO | PK, AI | |
| session_id | BIGINT UNSIGNED | NO | FK | → academic_sessions.id (CASCADE) |
| term_id | BIGINT UNSIGNED | YES | FK | → terms.id (SET NULL) |
| class_id | BIGINT UNSIGNED | NO | FK | → classes.id (CASCADE) |
| section_id | BIGINT UNSIGNED | YES | FK | → sections.id (SET NULL) |
| subject_id | BIGINT UNSIGNED | NO | FK | → subjects.id (CASCADE) |
| teacher_id | BIGINT UNSIGNED | YES | FK | → users.id (SET NULL) |
| day_of_week | ENUM('monday',...,'sunday') | NO | IDX | |
| start_time | TIME | NO | | |
| end_time | TIME | NO | | |
| room | VARCHAR(50) | YES | | NULL |
| created_at / updated_at | DATETIME | NO | | |

**Indexes**: `idx_tt_class_day` (class_id, section_id, day_of_week), `idx_tt_teacher_day` (teacher_id, day_of_week)

---

### 7.3.3 Student Tables

#### Table: `students`

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | BIGINT UNSIGNED | NO | PK, AI | — | |
| user_id | BIGINT UNSIGNED | YES | FK, IDX | NULL | → users.id (SET NULL) — linked user account |
| admission_no | VARCHAR(50) | NO | UQ | — | Unique admission number |
| roll_no | VARCHAR(50) | YES | | NULL | Legacy roll number |
| first_name | VARCHAR(100) | NO | | — | |
| last_name | VARCHAR(100) | NO | | — | |
| full_name | VARCHAR(200) | — | IDX | GENERATED (CONCAT) | Computed: first_name + last_name |
| gender | ENUM('male','female','other') | NO | | — | |
| date_of_birth | DATE | NO | | — | |
| blood_group | ENUM('A+','A-',...,'O-') | YES | | NULL | |
| religion | VARCHAR(50) | YES | | NULL | |
| nationality | VARCHAR(50) | YES | | 'Ethiopian' | |
| mother_tongue | VARCHAR(50) | YES | | NULL | |
| phone | VARCHAR(20) | YES | | NULL | |
| email | VARCHAR(255) | YES | | NULL | |
| address | TEXT | YES | | NULL | |
| city | VARCHAR(100) | YES | | NULL | |
| region | VARCHAR(100) | YES | | NULL | |
| photo | VARCHAR(255) | YES | | NULL | Photo file path |
| previous_school | VARCHAR(255) | YES | | NULL | |
| admission_date | DATE | NO | | — | |
| status | ENUM('active','inactive','graduated','transferred','expelled') | NO | IDX | 'active' | |
| medical_notes | TEXT | YES | | NULL | |
| notes | TEXT | YES | | NULL | |
| created_at / updated_at | DATETIME | NO | | CURRENT_TIMESTAMP | |
| deleted_at | DATETIME | YES | IDX | NULL | Soft delete |

---

#### Table: `guardians`

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| id | BIGINT UNSIGNED | NO | AI | |
| user_id | BIGINT UNSIGNED | YES | NULL | → users.id (SET NULL) |
| first_name / last_name | VARCHAR(100) | NO | — | |
| full_name | VARCHAR(200) | — | GENERATED | Computed column |
| relation | ENUM('father','mother','guardian','uncle','aunt','sibling','other') | NO | — | |
| phone | VARCHAR(20) | NO | — | Primary phone |
| alt_phone | VARCHAR(20) | YES | NULL | Alternative phone |
| email | VARCHAR(255) | YES | NULL | |
| occupation | VARCHAR(100) | YES | NULL | |
| address / city / region | TEXT / VARCHAR | YES | NULL | |
| photo | VARCHAR(255) | YES | NULL | |
| is_emergency_contact | TINYINT(1) | NO | 0 | |

---

#### Table: `student_guardians` (M2M)

| Column | Type | Key | FK |
|--------|------|-----|-----|
| student_id | BIGINT UNSIGNED | PK | → students.id (CASCADE) |
| guardian_id | BIGINT UNSIGNED | PK | → guardians.id (CASCADE) |
| is_primary | TINYINT(1) | | Default: 0 |
| created_at | DATETIME | | CURRENT_TIMESTAMP |

---

#### Table: `enrollments`

| Column | Type | Null | Key | FK | Description |
|--------|------|------|-----|-----|-------------|
| id | BIGINT UNSIGNED | NO | PK, AI | | |
| student_id | BIGINT UNSIGNED | NO | FK | → students.id (CASCADE) | |
| session_id | BIGINT UNSIGNED | NO | FK | → academic_sessions.id (CASCADE) | |
| class_id | BIGINT UNSIGNED | NO | FK | → classes.id (CASCADE) | |
| section_id | BIGINT UNSIGNED | YES | FK | → sections.id (SET NULL) | |
| roll_no | VARCHAR(50) | YES | | | Roll number within section |
| status | ENUM('active','promoted','transferred','dropped','repeated') | NO | | Default: 'active' | |
| enrolled_at | DATE | NO | | | Enrollment date |

**Indexes**: `uk_enrollments` (UNIQUE on student_id, session_id, class_id), `idx_enrollments_session_class` (session_id, class_id, section_id)

---

#### Table: `promotions`

| Column | Type | Key | FK | Description |
|--------|------|-----|-----|-------------|
| id | BIGINT UNSIGNED | PK, AI | | |
| student_id | BIGINT UNSIGNED | FK, IDX | → students.id (CASCADE) | |
| from_session_id | BIGINT UNSIGNED | FK, IDX | → academic_sessions.id (CASCADE) | |
| from_class_id | BIGINT UNSIGNED | FK | → classes.id (CASCADE) | |
| from_section_id | BIGINT UNSIGNED | FK | (nullable) | |
| to_session_id | BIGINT UNSIGNED | FK | → academic_sessions.id (CASCADE) | |
| to_class_id | BIGINT UNSIGNED | FK | → classes.id (CASCADE) | |
| to_section_id | BIGINT UNSIGNED | FK | (nullable) | |
| status | ENUM('promoted','repeated','transferred','graduated') | | | |
| remarks | TEXT | | | Optional |
| promoted_by | BIGINT UNSIGNED | FK | → users.id (SET NULL) | |
| promoted_at | DATETIME | | | CURRENT_TIMESTAMP |

---

#### Table: `student_documents`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | PK |
| student_id | BIGINT UNSIGNED | → students.id (CASCADE) |
| title | VARCHAR(255) | Document title |
| type | VARCHAR(50) | Document type identifier |
| file_path | VARCHAR(500) | File path |
| file_size | INT UNSIGNED | Size in bytes |
| mime_type | VARCHAR(100) | MIME type |
| uploaded_by | BIGINT UNSIGNED | → users.id (SET NULL) |
| created_at | DATETIME | |

---

#### Table: `student_elective_subjects`

| Column | Type | Key | FK |
|--------|------|-----|-----|
| id | BIGINT UNSIGNED | PK, AI | |
| student_id | BIGINT UNSIGNED | FK | → students.id (CASCADE) |
| class_subject_id | BIGINT UNSIGNED | FK | → class_subjects.id (CASCADE) |
| session_id | BIGINT UNSIGNED | FK | → academic_sessions.id (CASCADE) |
| created_at | DATETIME | | |

**Indexes**: `uk_student_elective` (UNIQUE on student_id, class_subject_id)

---

### 7.3.4 Assessment & Examination Tables

#### Table: `attendance`
- **Unique Key**: `uk_attendance_daily` (student_id, class_id, date, subject_id, period)
- **FK References**: students, classes, sections, academic_sessions, terms, subjects, users
- **Status ENUM**: 'present','absent','late','excused','half_day'

#### Table: `assignments`
- **FK References**: classes, sections, subjects, academic_sessions, terms, users (teacher_id)
- **Status ENUM**: 'draft','published','closed'
- **Key fields**: title, description, max_score, due_date, attachment

#### Table: `assignment_submissions`
- **Unique Key**: `uk_submission` (assignment_id, student_id)
- **Status ENUM**: 'submitted','graded','returned','late'
- **Key fields**: content, attachment, score, feedback, graded_by, graded_at

#### Table: `exams`
- **Type ENUM**: 'midterm','final','quiz','test','practical','mock'
- **Status ENUM**: 'upcoming','ongoing','completed','cancelled'
- **FK References**: academic_sessions, terms, users (created_by)

#### Table: `exam_schedules`
- **Unique Key**: `uk_exam_schedule` (exam_id, class_id, subject_id)
- **Key fields**: exam_date, start_time, end_time, room, max_marks, pass_marks

#### Table: `marks`
- **Unique Key**: `uk_marks` (exam_id, student_id, subject_id)
- **Key fields**: marks_obtained, max_marks, is_absent, remarks

#### Table: `grade_scales` and `grade_scale_entries`
- Grade scales with named entries: grade letter, min/max percentage, grade_point, remark
- FK: `grade_scale_entries.grade_scale_id` → `grade_scales.id` (CASCADE)

#### Table: `report_cards`
- **Unique Key**: `uk_report_cards` (student_id, session_id, term_id, exam_id)
- **Key fields**: total_marks, total_max_marks, percentage, grade, rank, attendance_days, absent_days, teacher_remarks, principal_remarks
- **Status ENUM**: 'draft','published'

#### Table: `assessments`
- **Key fields**: name, class_id, subject_id, session_id, term_id, total_marks (always 100)
- **Index**: `idx_assess_class_subject` (class_id, subject_id, session_id, term_id)

#### Table: `student_results`
- **Unique Key**: `uk_student_result` (assessment_id, student_id)
- **Key fields**: marks_obtained (NULL = not yet entered), is_absent, remarks

#### Table: `student_conduct`
- **Unique Key**: `uk_conduct` (student_id, class_id, session_id, term_id)
- **Conduct ENUM**: 'A' (Excellent), 'B' (Very Good), 'C' (Good), 'D' (Satisfactory), 'F' (Needs Improvement)

---

### 7.3.5 Finance Tables (Legacy)

#### Table: `fee_categories`
- **Unique Key**: `uk_fee_categories_code` (code)
- **Type ENUM**: 'tuition','registration','transport','lab','library','exam','uniform','other'

#### Table: `fee_structures`
- **Unique Key**: `uk_fee_structures` (session_id, class_id, fee_category_id, term_id)
- **Key fields**: amount (DECIMAL 12,2), due_date, is_mandatory

#### Table: `fee_discounts`
- **Type ENUM**: 'percentage','fixed'
- **Key fields**: name, value (DECIMAL 12,2)

#### Table: `student_fee_discounts`
- Links students to fee discounts per session, optionally per fee_category

#### Table: `invoices`
- **Unique Key**: `uk_invoices_no` (invoice_no)
- **Status ENUM**: 'draft','issued','partial','paid','overdue','cancelled','refunded'
- **Key fields**: subtotal, discount_amount, fine_amount, total_amount, paid_amount, balance

#### Table: `invoice_items`
- Line items within an invoice: description, amount, quantity, total
- FK: fee_category_id, fee_structure_id (both optional)

#### Table: `payments`
- **Unique Key**: `uk_payments_receipt` (receipt_no)
- **Method ENUM**: 'cash','bank_transfer','cheque','gateway','other'
- **Status ENUM**: 'completed','pending','failed','reversed'

---

### 7.3.6 Payment Gateway Tables

#### Table: `payment_gateways`
- **Unique Key**: `uk_gw_slug` (slug)
- **Environment ENUM**: 'sandbox','production'
- **Key fields**: name, slug, display_name, is_active, config_json

#### Table: `payment_transactions`
- **Unique Keys**: `uk_txn_ref` (transaction_ref), `uk_txn_idempotency` (idempotency_key)
- **Status ENUM**: 'pending','success','failed','cancelled','refunded','expired'
- **Key fields**: amount (DECIMAL 12,2), currency, gateway_reference, checkout_url, metadata (JSON)

#### Table: `payment_attempts`
- **Status ENUM**: 'initiated','redirected','success','failed','timeout','error'
- Tracks each attempt against a transaction

#### Table: `payment_webhooks`
- **Processing Status ENUM**: 'received','processed','failed','duplicate','invalid'
- Logs incoming webhook payloads with signature validation

#### Table: `payment_reconciliation_logs`
- **Action ENUM**: 'status_check','mark_success','mark_failed','mark_expired','manual_override'
- Audit trail for payment status changes

#### Table: `invoice_payment_links`
- Links invoices to payment_transactions with amount and is_partial flag

---

### 7.3.7 Communication Tables

#### Table: `announcements`
- **Type ENUM**: 'general','academic','event','emergency','other'
- **Status ENUM**: 'draft','published','archived'
- **Key fields**: title, content, target_roles, target_classes, is_pinned, attachment

#### Table: `messages`
- FK: sender_id → users, receiver_id → users, parent_id → messages (self-referencing for threads)
- **Key fields**: subject, body, is_read, read_at, attachment

#### Table: `notifications`
- FK: user_id → users
- **Key fields**: type, title, message, link, data (JSON), is_read, read_at

---

### 7.3.8 System Tables

#### Table: `audit_logs`
- FK: user_id → users (SET NULL)
- **Key fields**: action, module, entity_type, entity_id, old_values (JSON), new_values (JSON), ip_address, user_agent, description

#### Table: `settings`
- **Unique Key**: `uk_settings_key` (setting_group, setting_key)
- **Type ENUM**: 'string','integer','boolean','json','text','textarea','number'
- **Key fields**: setting_group, setting_key, setting_value, is_public

---

### 7.3.9 Advanced Fee Management Tables (Migration 015)

#### Table: `fees`
- **Fee Type ENUM**: 'one_time','recurrent'
- **Status ENUM**: 'draft','active','inactive','archived'
- **Key fields**: amount (DECIMAL 12,2), effective_date, end_date
- **Constraints**: CHECK (end_date > effective_date), CHECK (amount > 0)

#### Table: `recurrence_configs` (1:1 with fees)
- **Unique Key**: `uk_recurrence_fee` (fee_id)
- **Frequency Unit ENUM**: 'days','weeks','months','years'
- **Key fields**: frequency_number, max_recurrences (0 = unlimited), next_due_date

#### Table: `penalty_configs` (1:1 with fees)
- **Unique Key**: `uk_penalty_fee` (fee_id)
- **Penalty Type ENUM**: 'fixed','percentage'
- **Penalty Frequency ENUM**: 'one_time','recurrent'
- **Key fields**: grace_period_number/unit, penalty_amount, max_penalty_amount, max_penalty_applications

#### Table: `student_groups`
- **Unique Key**: `uk_student_groups_name` (name)
- Named groups for fee assignment (e.g., "Scholarship Recipients", "Grade 9 Students")

#### Table: `student_group_members` (M2M)
- **Unique Key**: `uk_group_student` (group_id, student_id)
- Soft delete support (deleted_at)

#### Table: `fee_assignments`
- Assigns fees to individual students or student groups
- **Key fields**: fee_id, student_id (nullable), group_id (nullable), effective_date, end_date, status

#### Table: `fee_exemptions`
- Exempts specific students from specific fees
- **Key fields**: fee_id, student_id, reason, exempted_by

#### Table: `student_fee_charges`
- Individual charge records generated from fee_assignments
- **Status ENUM**: 'pending','partial','paid','overdue','waived','cancelled'
- **Key fields**: amount_charged, amount_paid, due_date, paid_date

#### Table: `penalty_charges`
- Penalty records applied to overdue student_fee_charges
- **Key fields**: fee_charge_id, penalty_config_id, amount, application_number

#### Table: `finance_audit_log`
- Finance-specific audit trail with changed_by, entity_type, entity_id, action, old_data/new_data (JSON)

---

## 7.4 Seed Data Summary

### Default Roles (7)
| ID | Name | Slug |
|----|------|------|
| 1 | Super Admin | super_admin |
| 2 | Admin | admin |
| 3 | Teacher | teacher |
| 4 | Student | student |
| 5 | Parent | parent |
| 6 | Accountant | accountant |
| 7 | Librarian | librarian |

### Default Admin User
| Field | Value |
|-------|-------|
| Username | admin |
| Email | admin@urjiberi.edu.et |
| Password | `Admin@2025` (bcrypt hashed) |
| Role | Super Admin |

### Demo Users (6)
| Username | Role | Password |
|----------|------|----------|
| admin | Super Admin | Admin@2025 |
| abebe.teacher | Teacher | Teacher@2025 |
| student1 | Student | Student@2025 |
| parent1 | Parent | Parent@2025 |
| finance.user | Accountant | Finance@2025 |
| admin2 | Admin | Admin@2025 |

### Default Grade Scale (Ethiopian Standard)
| Grade | Min % | Max % | GPA | Remark |
|-------|-------|-------|-----|--------|
| A+ | 95 | 100 | 4.0 | Outstanding |
| A | 85 | 94.99 | 4.0 | Excellent |
| B+ | 75 | 84.99 | 3.5 | Very Good |
| B | 65 | 74.99 | 3.0 | Good |
| C+ | 55 | 64.99 | 2.5 | Satisfactory |
| C | 45 | 54.99 | 2.0 | Acceptable |
| D | 35 | 44.99 | 1.0 | Below Average |
| F | 0 | 34.99 | 0.0 | Fail |

### Default Payment Gateways (3)
| Name | Slug | Status |
|------|------|--------|
| Telebirr | telebirr | Active (sandbox) |
| Chapa | chapa | Active (sandbox) |
| Stripe | stripe | Inactive |

---

**End of Part 3**

| Document | Contents |
|----------|----------|
| **PART 1** | Project Overview, System Architecture |
| **PART 2** | Complete File & Folder Structure, Variable Documentation |
| **PART 3** (this file) | Functional Requirements, Non-Functional Requirements, Database Design |
| **PART 4** | API Design, Development Checklist |
| **PART 5** | Development Plan, Coding Standards, Testing Strategy, Deployment Plan |
