# Attendance System (Attendance Module)

This document provides a **detailed, end-to-end description** of the Attendance system in this SMS application.

It includes:
- Database schema (tables and key relationships)
- UI pages and routes
- Full backend action details (what code runs and how data is stored)
- How attendance is recorded and queried
- Common use cases and troubleshooting tips

---

## 0. Overview

The Attendance module lets teachers/administrators record daily student attendance and generate reports.

### Key capabilities
- Record attendance per class/section on a given date
- Support statuses: **present**, **absent**, **late**, **excused**
- Prevent future-date attendance
- Allow bulk marking (All Present / All Absent)
- Report attendance per student, per class, per date range

---

## 1. Routes (URLs) & Permissions

Attendance is routed via `modules/attendance/routes.php`.

### Main pages (GET)
- `attendance?route=index` — Take Attendance (main entry page)
- `attendance?route=report` — Attendance Report view
- `attendance?route=view` — View attendance records for a date/class
- `attendance?route=student` — View attendance for a specific student

### Actions (POST)
- `attendance?route=save` — Save attendance (insert/update)

### Permissions
- Viewing any attendance page requires `attendance.view`.
- Saving attendance requires `attendance.manage`.

---

## 2. Database Schema

### 2.1 Core Table: `attendance`
This table stores one record per student per date per class.

Key columns:
- `id` (PK)
- `student_id` — FK to `students.id`
- `class_id` — FK to `classes.id`
- `section_id` — FK to `sections.id` (nullable)
- `session_id` — FK to `academic_sessions.id`
- `term_id` — FK to `terms.id` (nullable)
- `date` — date of attendance
- `status` — `present | absent | late | excused`
- `remarks` — optional text
- `marked_by` — FK to `users.id` (who recorded it)
- `created_at`, `updated_at`

**Unique constraint:**
- Attendance is unique per (`student_id`, `class_id`, `date`, `subject_id`, `period`) in schema, but the UI uses `student_id/class_id/date`.

---

## 3. Attendance UI & Workflow

### 3.1 Take Attendance Page (`attendance?route=index`)

**View:** `modules/attendance/views/take.php`

**Main features:**
1. Select **Class** (required)
2. Optional **Section** filter
3. Select **Date** (defaults to today; cannot exceed today)
4. Load students enrolled in that class/session
5. Display each student with:
   - Name + admission no
   - Status radio buttons (P/A/L/E)
   - Optional remarks field
6. Save button to commit attendance for all students

#### How students are loaded
- Uses `enrollments` to fetch students with:
  - `class_id = selected class`
  - `session_id = active session`
  - `status = 'active'`

SQL used:
```sql
SELECT s.id, s.first_name, s.last_name, s.admission_no, s.photo, e.section_id
FROM students s
JOIN enrollments e ON e.student_id = s.id
WHERE e.class_id = ? AND e.session_id = ? AND e.status = 'active'
ORDER BY s.first_name, s.last_name
```

#### Existing attendance lookup
After loading students, the UI pre-fills any existing attendance for the selected date:
```sql
SELECT student_id, status, remarks
FROM attendance
WHERE date = ? AND student_id IN (...) 
```

#### Bulk status actions
The UI provides buttons: **All Present** / **All Absent** which set all radios accordingly.

---

## 4. Saving Attendance (Action)

### 4.1 Action: `attendance?route=save`
**Code:** `modules/attendance/actions/save.php`

This action is called when the attendance form is submitted.

### 4.2 Input Fields
- `class_id` (required)
- `section_id` (optional)
- `session_id` (hidden; uses active session)
- `date` (required; cannot be future)
- `students` — array keyed by student ID, each entry contains:
  - `status` (present/absent/late/excused)
  - `remarks` (text)

### 4.3 Server-side Logic (step-by-step)
1. **CSRF protection** via `csrf_protect()`.
2. Validate required fields (class, date, student list).
3. Reject if `date > today` (no future attendance).
4. Determine `term_id` from `get_active_term()`.
5. Start DB transaction (`db_begin()`).

For each student entry:
- Determine status (default to `present` if invalid).
- `upsert` attendance record:
  - If record exists for (student, class, date): update `status`, `remarks`, `marked_by`.
  - Else insert a new record.

6. Commit transaction.
7. Write audit log `attendance.save` with count.
8. Redirect back to the attendance page.

### 4.4 Notes on Unique Key & Upsert
The `attendance` table has a unique key for `(student_id, class_id, date, subject_id, period)`, but the UI only records class/date, so the system upserts on `(student_id, class_id, date)`.

---

## 5. Attendance Reporting & Viewing

### 5.1 Attendance Report (`attendance?route=report`)
**View:** `modules/attendance/views/report.php`

This page typically lets you filter by class, section, date range, and generates a report summarizing attendance percentages.

### 5.2 Attendance View (`attendance?route=view`)
**View:** `modules/attendance/views/view.php`

This view shows attendance records for a specific date/class and allows inspection but not editing.

### 5.3 Student Attendance (`attendance?route=student`)
**View:** `modules/attendance/views/student.php`

Shows attendance history for a specific student.

---

## 6. Common Use Cases (Examples)

### 6.1 Taking attendance for a class
1. Go to **Attendance → Take Attendance**.
2. Choose class, optional section, and date.
3. Click **Load**.
4. Mark each student as `P/A/L/E` and optionally add remarks.
5. Click **Save Attendance**.

### 6.2 Editing attendance for a past date
1. Select the past date in the date picker.
2. The form pre-fills statuses based on existing `attendance` records.
3. Modify and save (the system updates records via upsert).

### 6.3 Viewing a student’s attendance history
1. Go to **Attendance → Student**.
2. Filter by student or search.
3. The view shows a list of dates and their statuses.

---

## 7. Debugging & Troubleshooting

### 7.1 Attendance not saving
- Check that the form posts to `attendance?route=save`.
- Verify `csrf_protect()` passed (no missing CSRF token).
- Make sure `class_id`, `date`, and student list are present.

### 7.2 Entries duplicated for same student/date
- Confirm the `attendance` table’s unique constraint is working.
- The code updates existing results (upsert), so duplicates should not occur.

### 7.3 Unable to take attendance for a future date
- This is intentional: the code rejects `date > today` with an error.

---

## 8. Where to Customize / Extend

### 8.1 Add period-based attendance
- Modify the schema to include `period` in the UI and upsert logic.
- Update unique key and `save.php` to include `period` in the row key.

### 8.2 Add attendance export (CSV/PDF)
- Add a new route and view for exporting filtered attendance.
- Reuse the same query logic used in report view.

### 8.3 Add attendance threshold alerts
- Implement a background job that checks for students with < X% attendance and stores alerts or generates reports.

---

**End of Attendance System documentation.**
