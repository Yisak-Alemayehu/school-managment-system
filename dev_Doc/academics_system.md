# Academics System (Academic Management Module)

This document provides a full, end-to-end explanation of the **Academics module** in this SMS system.

It covers:
- Database schema (tables, keys, relationships)
- Core workflows and how they map to UI pages
- Detailed code paths (which PHP files run for each action)
- Reference of routes and views
- Common use cases (example flows for sessions, classes, enrollment, promotion, timetables)

---

## 0. Module Routes (URLs)

The Academics module is routed via `modules/academics/routes.php`, driven by the `action` parameter.

### 0.1 Main page routes
- `academics?route=sessions` – Academic sessions list
- `academics?route=terms` – Terms/semesters list
- `academics?route=mediums` – Mediums list
- `academics?route=streams` – Streams list
- `academics?route=shifts` – Shifts list
- `academics?route=classes` – Classes list
- `academics?route=sections` – Sections list
- `academics?route=subjects` – Subjects list
- `academics?route=class-subjects` – Assign subjects to classes
- `academics?route=elective-subjects` – Assign elective subjects to students
- `academics?route=class-teachers` – Class teacher assignment
- `academics?route=subject-teachers` – Subject teacher assignment
- `academics?route=promote` – Promote students
- `academics?route=timetable` – Timetable management
- `academics?route=my-subjects` – Student “My Subjects” view
- `academics?route=my-timetable` – Student/teacher timetable view

### 0.2 Action routes (POST)
- `academics?route=session-save` — create/update session
- `academics?route=session-toggle` — toggle session active state
- `academics?route=term-save` — create/update term
- `academics?route=term-toggle` — toggle term active state
- `academics?route=medium-save` — create/update medium
- `academics?route=stream-save` — create/update stream
- `academics?route=shift-save` — create/update shift
- `academics?route=class-save` — create/update class
- `academics?route=section-save` — create/update section
- `academics?route=subject-save` — create/update subject
- `academics?route=class-subjects-save` — assign subjects to class
- `academics?route=elective-subjects-save` — assign elective subjects to students
- `academics?route=class-teacher-save` — assign class teacher
- `academics?route=subject-teacher-save` — assign subject teacher
- `academics?route=subject-teacher-delete` — delete subject teacher assignment
- `academics?route=promote-save` — promote students
- `academics?route=timetable-save` — add/update/delete timetable slot

> **Permissions:** Most actions require `academics.manage`; timetable read-only view uses `timetable.view`.

---

## 1. Key Academic Tables (Schema Overview)

The core academic entities are stored in the database. Below are the primary tables and their relationships.

### 1.1 Academic Sessions — `academic_sessions`
Tracks school years or academic sessions.

Key fields:
- `name`, `slug` (unique)
- `start_date`, `end_date`
- `is_active` (only one session should be active at a time)

### 1.2 Terms / Semesters — `terms`
Terms belong to a session.

Key fields:
- `session_id` (FK to `academic_sessions`)
- `name`, `slug`
- `start_date`, `end_date`

### 1.3 Mediums — `mediums`
Language/medium categories (e.g., English, Amharic).

Key fields:
- `name`

### 1.4 Streams — `streams`
Academic streams/tracks (e.g., Science, Commerce).

Key fields:
- `name`, `description`

### 1.5 Shifts — `shifts`
School shifts (morning/afternoon).

Key fields:
- `name`, `start_time`, `end_time`

### 1.6 Classes — `classes`
Defines grade levels.

Key fields:
- `name`, `slug`, `numeric_name` (ordinal ranking)
- `medium_id`, `stream_id`, `shift_id`
- `is_active`, `status`

### 1.7 Sections — `sections`
Each class can have multiple sections.

Key fields:
- `class_id` (FK to `classes`)
- `name`, `capacity`, `status`

### 1.8 Subjects — `subjects`
List of subjects.

Key fields:
- `name`, `code`
- `type` (theory/practical/both)

### 1.9 Class-Subject Assignment — `class_subjects`
Defines which subjects are taught in each class (per session), including elective flags.

Key fields:
- `class_id`, `subject_id`, `session_id`
- `is_elective`

### 1.10 Class Teachers — `class_teachers`
Used for both homeroom (class teacher) and subject teacher assignments.

Key fields:
- `class_id`, `section_id`, `subject_id`, `teacher_id`, `session_id`
- `is_class_teacher` (1 = homeroom teacher, 0 = subject teacher)

### 1.11 Students & Enrollment (Core)
- `students` — full student profiles.
- `enrollments` — links students to a specific session/class/section.

Key fields in `enrollments`:
- `student_id`, `session_id`, `class_id`, `section_id`
- `status` (active/promoted/transferred/dropped/etc.)

### 1.12 Promotions — `promotions`
Records promotions/repeats/graduations.

### 1.13 Elective Subjects — `student_elective_subjects`
Stores which elective subjects a student selected for a session.

### 1.14 Timetables — `timetables`
Defines the school timetable slots.

Key fields:
- `session_id`, `term_id`, `class_id`, `section_id`, `subject_id`, `teacher_id`
- `day_of_week`, `start_time`, `end_time`, `room`

---

## 2. Core Academic Workflows

This section explains the major academic management flows and the code that implements them.

### 2.1 Manage Academic Sessions
**UI:** Academics → Sessions

**Steps:**
1. Create or update a session.
2. Optionally activate a session (sets all others inactive).

**Key files:**
- View: `modules/academics/views/sessions.php`
- Action: `modules/academics/actions/session_save.php`
- Toggle Active: `modules/academics/actions/session_toggle.php`

**Important behavior:**
- When a session is activated, the code sets `is_active = 0` on all other sessions.
- The system expects only one active session at a time.

### 2.2 Manage Terms/Semesters
**UI:** Academics → Terms / Semesters

**Steps:**
1. Create/update a term for a given session.
2. Optionally mark active (toggle).

**Key files:**
- View: `modules/academics/views/terms.php`
- Action: `modules/academics/actions/term_save.php`
- Toggle: `modules/academics/actions/term_toggle.php`

**Important behavior:**
- Terms are tied to a session (via `session_id`).
- Validation checks ensure `end_date` is after `start_date`.

### 2.3 Manage Mediums / Streams / Shifts
**UI:** Academics → Mediums / Streams / Shifts

**Key files:**
- Mediums: `views/mediums.php`, `actions/medium_save.php`
- Streams: `views/streams.php`, `actions/stream_save.php`
- Shifts: `views/shifts.php`, `actions/shift_save.php`

These are simple reference lists; each save action validates input and inserts or updates the relevant table.

### 2.4 Manage Classes and Sections

#### Classes (Academic Levels)
**UI:** Academics → Classes

**Key fields captured:**
- Class name, slug, numeric order
- Associated medium, stream, shift

**Key files:**
- `views/classes.php`
- `actions/class_save.php`

##### How class slugs are generated
- The code generates a slug from the class name (lowercase, hyphenated).
- It checks for duplicates and appends an identifier if needed.

#### Sections (Subgroups within class)
**UI:** Academics → Sections

**Key files:**
- `views/sections.php`
- `actions/section_save.php`

Validation ensures section names are unique within a class.

### 2.5 Manage Subjects
**UI:** Academics → Subjects

**Key files:**
- `views/subjects.php`
- `actions/subject_save.php`

Subjects have a code, name, and type (theory/practical/both). The system enforces unique codes.

### 2.6 Assign Subjects to Classes
**UI:** Academics → Class-Subject Assignment

This defines what subjects are taught for each class and session.

**Key files:**
- `views/class_subjects.php`
- `actions/class_subjects_save.php`

**Behavior:**
- When saving, the code deletes all existing assignments for the class+session and re-inserts the selection.
- Elective subjects are flagged via `is_elective`.

### 2.7 Elective Subjects (Student Selections)
**UI:** Academics → Assign Elective Subjects

Students can pick from elective subjects assigned to a class.

**Key files:**
- `views/elective_subjects.php`
- `actions/elective_subjects_save.php`

**Behavior:**
- For each selected student, old elective selections (for the session) are deleted and replaced.
- Only elective subjects (from `class_subjects` where `is_elective = 1`) can be chosen.

### 2.8 Teacher Assignments (Class & Subject Teachers)

#### Class Teachers (Homeroom)
**UI:** Academics → Class Teachers

**Key files:**
- `views/class_teachers.php`
- `actions/class_teacher_save.php`

**Key behavior:**
- Stored in `class_teachers` with `is_class_teacher = 1`.
- Only one class teacher per class+section+session is allowed.

#### Subject Teachers
**UI:** Academics → Subject Teachers

**Key files:**
- `views/subject_teachers.php`
- `actions/subject_teacher_save.php`
- `actions/subject_teacher_delete.php`

**Key behavior:**
- Stored in `class_teachers` with `is_class_teacher = 0`.
- One teacher per class+section+subject+session.

### 2.9 Student Promotion (Class Advancement)
**UI:** Academics → Promote Students

**Key files:**
- `views/promote.php`
- `actions/promote_save.php`

**Process:**
1. Select students from a class/session.
2. Choose destination class/session (or mark as graduated).
3. For each student, the system:
   - Updates the current enrollment status (promoted/repeated/graduated)
   - Inserts a new enrollment for promoted/repeated students
   - Creates a `promotions` record for historic tracking

**Key tables involved:**
- `enrollments` (mark active/inactive)
- `promotions` (audit record)

### 2.10 Timetable Management
**UI:** Academics → Timetable

**Key files:**
- `views/timetable.php`
- `actions/timetable_save.php`

**What it stores:**
- Class/section/subject/teacher per day-of-week and time slot
- Room and session/term

**Key tables:**
- `timetables`

### 2.11 Student/Teacher Personal Views
These are read-only views that show assignments and timetables.

- `academics?route=my-subjects` — shows subjects assigned to the logged-in student (based on enrollment/class)
- `academics?route=my-timetable` — shows timetable for the logged-in student or teacher

**Key files:**
- `views/my_subjects.php`
- `views/my_timetable.php`

### 2.12 Exams & Results Management (Full Academic Assessment Workflow)

The Exams module manages:
- Exam definitions (midterms, finals, quizzes)
- Exam scheduling (per class/subject)
- Marks entry per student/subject
- Grade scale definition
- Report card generation and printing (with QR verification)
- Result analysis dashboards

**Key routes:**
- `exams?route=exams` — list exams
- `exams?route=exam-save` — create/update exam
- `exams?route=exam-schedule` — schedule exams
- `exams?route=exam-schedule-save` — save schedule
- `exams?route=marks` — enter marks
- `exams?route=enter-results` — enter results (subject breakdown)
- `exams?route=enter-conduct` — enter conduct/behavior grades
- `exams?route=result-cards` — view/generate report cards
- `exams?route=report-card-generate` — generate report card records
- `exams?route=report-card-print` — print report card (with optional QR public access)
- `exams?route=report-card-verify` — verify report card via QR (public)
- `exams?route=result-analysis` — student result analysis dashboard
- `exams?route=grade-scale` — define grade scale (A, B, etc.)
- `exams?route=roster` — generate exam roster

**Key files:**
- Views: `modules/exams/views/*.php`
- Actions: `modules/exams/actions/*.php`
- Routes: `modules/exams/routes.php`

#### Key tables involved
- `exams` — exam definitions
- `exam_schedules` — class/subject schedule for exams
- `marks` — student marks per exam/subject
- `grade_scales` / `grade_scale_entries` — grade mapping definitions
- `report_cards` — generated report card records

### 2.12.1 Exam definition & scheduling
**Tables:** `exams`, `exam_schedules`

**Exam creation flow:**
- The UI is `modules/exams/views/exams.php`.
- Submission hits `modules/exams/actions/exam_save.php`.
- The action validates required fields and inserts/updates the `exams` table.

**Exam schedule flow:**
- UI: `modules/exams/views/exam_schedule.php`.
- Save action: `modules/exams/actions/exam_schedule_save.php`.
- Scheduling stores per-class/subject exam timing in `exam_schedules`.

**Key fields (exams):**
- `name`, `description`
- `session_id`, `term_id`
- `type` (midterm/final/quiz/test/practical/mock)
- `start_date`, `end_date`, `status`

**Key fields (exam_schedules):**
- `exam_id`, `class_id`, `subject_id`
- `exam_date`, `start_time`, `end_time`
- `max_marks`, `pass_marks`

### 2.12.2 Marks entry & results
**Tables:** `marks` (primary data), `assignments`, `assignment_submissions` (related but distinct)

**Marks entry:**
- UI: `modules/exams/views/marks.php` (mark entry grid by class/subject)
- Submission: `modules/exams/actions/marks_save.php`

**Key marks fields:**
- `exam_id`, `exam_schedule_id`
- `student_id`, `class_id`, `subject_id`
- `marks_obtained`, `max_marks`
- `is_absent`, `remarks`
- `entered_by` (user)

**Behavior and validation:**
- The system enforces a unique mark per student/exam/subject via `UNIQUE KEY uk_marks (exam_id, student_id, subject_id)`.
- Marks entry supports absent marking.

### 2.12.3 Grade scales & grade boundaries
**Tables:** `grade_scales`, `grade_scale_entries`

The system uses grade scale definitions to map a percentage to a grade (A, B, etc.).

**Grade scale management:**
- UI: `modules/exams/views/grade_scale.php`
- Action: `modules/exams/actions/grade_scale_save.php`

**Key fields:**
- `grade_scales`: `name`, `is_default`
- `grade_scale_entries`: `grade`, `min_percentage`, `max_percentage`, `grade_point`, `remark`

### 2.12.4 Report cards (generation + printing + verification)
**Tables:** `report_cards`

**Flow:**
1. Generate report cards from exam results (or total results) via:
   - UI: `modules/exams/views/report_cards.php`
   - Action: `modules/exams/actions/report_card_generate.php`
2. Print report cards via:
   - UI: `modules/exams/views/report_card_print.php`
   - Route: `exams?route=report-card-print&id=<id>`

**Report card content:**
- Student data + class/session info
- Exam results (subject breakdown)
- Total marks, percentage, grade, rank, attendance
- Optional: teacher/principal remarks

**Security / QR link:**
- The print route supports a public copy via signed link (`?id=<>&sig=<hmac>`).
- Verification is handled by `modules/exams/actions/report_card_verify.php`.
- The signature uses an HMAC with a fixed secret: `urjiberi_report_card_secret_2026`.

### 2.12.5 Result analysis & roster generation
- Result analysis UI: `modules/exams/views/result_analysis.php`.
- Roster generation (student list for exams): `modules/exams/views/roster.php`.
- The system provides a chart of class/subject performance and pass rates.

---

### 2.13 Assignments & Attendance (Student Work + Daily Tracking)

The system also supports student assignments and attendance tracking, both of which are part of academic operations.

#### Assignments
**Tables:** `assignments`, `assignment_submissions`

**Purpose:** Teachers can create assignments, students submit responses, and teachers can grade them.

**Key UI pages:**
- `exams?route=assignments` — list assignments
- `exams?route=assignment-create` — create new assignment
- `exams?route=assignment-view&id=<id>` — view assignment details + submissions

**Key actions:**
- `exams?route=assignment-save` — create/update assignment (uploads optional file)
- `exams?route=assignment-delete` — delete assignment (cleans submissions and file)

**Important behavior:**
- Assignments are filtered by the current active session and term.
- Teachers see only assignments they created (unless superadmin).
- Each assignment can have a file attachment stored via `handle_upload()`.

#### Attendance
**Tables:** `attendance`

**Purpose:** Track student presence day-by-day.

**Key UI pages:**
- `attendance?route=index` — take attendance
- `attendance?route=report` — attendance report
- `attendance?route=view` — view attendance entries
- `attendance?route=student` — view single student attendance

**Key actions:**
- `attendance?route=save` — record attendance (upsert per student/date)

**How it works:**
- Each attendance entry is unique per student/class/date (enforced by unique key).
- Supported statuses: `present`, `absent`, `late`, `excused`.
- Data is restricted by date (no future dates allowed).

---

## 3. Data Model: Relationships at a Glance

The following Mermaid diagram shows key tables and their relationships.

```mermaid
flowchart LR
  Students[students]
  Enroll[enrollments]
  Sessions[academic_sessions]
  Terms[terms]
  Classes[classes]
  Sections[sections]
  Subjects[subjects]
  ClassSubjects[class_subjects]
  Teachers[users (teachers)]
  ClassTeachers[class_teachers]
  Promotions[promotions]
  Timetable[timetables]
  Electives[student_elective_subjects]
  Exams[exams]
  ExamSched[exam_schedules]
  Marks[marks]
  GradeScale[grade_scales]
  GradeEntries[grade_scale_entries]
  ReportCards[report_cards]

  Students --> Enroll
  Enroll --> Sessions
  Enroll --> Classes
  Enroll --> Sections
  Classes --> Sections
  Classes --> ClassSubjects
  ClassSubjects --> Subjects
  ClassSubjects --> Sessions
  Students --> Electives
  Electives --> ClassSubjects
  Classes --> ClassTeachers
  ClassTeachers --> Teachers
  ClassTeachers --> Subjects
  Timetable --> Classes
  Timetable --> Sections
  Timetable --> Subjects
  Timetable --> Teachers
  Promotions --> Students
  Promotions --> Sessions
  Promotions --> Classes
  Promotions --> Sections

  Exams --> Sessions
  Exams --> Terms
  ExamSched --> Exams
  ExamSched --> Classes
  ExamSched --> Subjects
  Marks --> Exams
  Marks --> Students
  Marks --> Subjects
  ReportCards --> Students
  ReportCards --> Sessions
  ReportCards --> Terms
  ReportCards --> Classes
  ReportCards --> Subjects
  ReportCards --> GradeScale
  GradeScale --> GradeEntries
```
---

## 4. Common Use Cases (Step-by-step)

### 4.1 Create & Activate a New Academic Session
1. Navigate to **Academics → Sessions**.
2. Click **Add Session**, fill in name, start/end dates.
3. Save.
4. (Optional) Activate it: the code will set all other sessions to inactive.

### 4.2 Add Terms for a Session
1. Go to **Academics → Terms**.
2. Select session, click **Add Term**.
3. Provide start/end dates and save.

### 4.3 Configure Classes + Sections + Subjects
1. Create class (e.g., Grade 10) under **Classes**.
2. Create sections (e.g., A, B) under **Sections**.
3. Create subjects under **Subjects**.
4. Assign subjects to the class under **Class-Subject Assignment**.

### 4.4 Assign Elective Subjects to Students
1. Populate class subjects as elective via class-subjects page.
2. Go to **Assign Elective Subjects**.
3. Select students and choose electives; save.

### 4.5 Assign Teachers
- **Class Teachers:** assign homeroom teacher via **Class Teachers**.
- **Subject Teachers:** assign subject teachers per class/section via **Subject Teachers**.

### 4.6 Promote Students (End of Year)
1. Go to **Promote Students**.
2. Select source session/class and destination session/class.
3. Pick students and choose whether each is promoted, repeated, or graduated.
4. Save.

### 4.7 Build a Timetable
1. Go to **Timetable**.
2. Choose class/section and session.
3. Add slots (day/time/subject/teacher/room).

---

## 5. Debugging Tips

### 5.1 Student not appearing in class list
- Verify `enrollments` has a row for the student with `status = 'active'`.
- Confirm `enrollments.session_id` matches the active session.

### 5.2 Subject not listed for class
- Check `class_subjects` for that class + session.
- Verify the subject is active in `subjects`.

### 5.3 Teacher not assigned on timetable
- Confirm `class_teachers` has an entry for the subject and session.
- Check the timetable slot has the correct `teacher_id` and `subject_id`.

---

## 6. Where to Extend / Customize

### 6.1 Add new session/term validation rules
Update `actions/session_save.php` and `actions/term_save.php`.

### 6.2 Add more complex timetable validation
Right now, timetable saving doesn’t check for conflicts. You can add logic in `timetable_save.php` to reject overlapping slots for the same teacher or class.

### 6.3 Improve elective selection workflow
The elective assignment currently deletes and re-inserts selections. You can improve it to be incremental and to support validation of allowed electives.

---

**End of Academics System documentation.**
