# Results System (Exams, Marks, Report Cards)

This document provides a complete, end-to-end explanation of the **Results system** in this SMS application.

It covers:
- Database schema (tables + relationships)
- UI pages and routes
- Full backend code flows (what runs on each action)
- How marks are entered, how report cards are generated, and how verification works
- Common troubleshooting and extension points

---

## 0. Routes (URLs) & Permissions

The Results module is routed via `modules/exams/routes.php`.

### 0.1 Main UI pages (GET)
- `exams?route=exams` — Exam definitions list
- `exams?route=exam-schedule` — Exam schedule
- `exams?route=marks` — Enter marks
- `exams?route=enter-results` — Enter student results (assessment-based)
- `exams?route=enter-conduct` — Enter student conduct/behavior grades
- `exams?route=add-assessment` — Create assessment
- `exams?route=roster` — Generate roster for exams
- `exams?route=result-cards` — Report cards dashboard
- `exams?route=report-card-print` — Print report card (with optional public link)
- `exams?route=result-analysis` — Result analysis dashboard
- `exams?route=grade-scale` — Grade scale management

### 0.2 Actions (POST)
- `exams?route=assignment-save` — Save assignment
- `exams?route=assignment-delete` — Delete assignment
- `exams?route=exam-save` — Create/update exam
- `exams?route=exam-schedule-save` — Save exam schedule
- `exams?route=marks-save` — Save entered marks
- `exams?route=results-save` — Save student results (assessment results)
- `exams?route=conduct-save` — Save conduct grades
- `exams?route=assessment-save` — Create/update assessment
- `exams?route=assessment-delete` — Delete assessment
- `exams?route=report-card-generate` — Generate report cards

### 0.3 Public Verification (No auth required)
- `exams?route=report-card-verify` — Verify report card using QR code signature

> Permissions: Most pages require `exam.view` / `marks.view` / `report_card.view`, while modification actions require `exam.manage`, `marks.manage`, or `report_card.manage`.

---

## 1. Database Schema (Results + Reports)

### 1.1 `exams` (Exam Definitions)
Stores exam events (midterm, final, quiz, etc.).

Key fields:
- `name`, `description`
- `session_id`, `term_id`
- `type` (`midterm`, `final`, `quiz`, `test`, `practical`, `mock`)
- `start_date`, `end_date`, `status`
- `created_by`

### 1.2 `exam_schedules` (Class/Subject Exam Timetable)
Schedules exams per class and subject.

Key fields:
- `exam_id`, `class_id`, `subject_id`
- `exam_date`, `start_time`, `end_time`
- `max_marks`, `pass_marks`

### 1.3 `assessments` (Assessment Definitions)
Defines individual assessments (e.g., quiz 1, assignment) linked to a subject/class.

Key fields:
- `name`, `description`
- `class_id`, `subject_id`, `session_id`, `term_id`
- `total_marks` (default 100)
- Unique constraint: `(name, class_id, subject_id, session_id, term_id)`

### 1.4 `student_results` (Assessment Results)
Stores per-student results for assessments.

Key fields:
- `assessment_id`, `student_id`
- `class_id`, `section_id`
- `marks_obtained` (nullable = not entered)
- `is_absent`, `remarks`
- `entered_by`
- Unique constraint: `(assessment_id, student_id)`

### 1.5 `grade_scales` / `grade_scale_entries` (Grading Rules)
Define how percentages map to grades.

Key fields:
- `grade_scales`: `name`, `is_default`
- `grade_scale_entries`: `grade`, `min_percentage`, `max_percentage`, `grade_point`, `remark`

### 1.6 `report_cards` (Generated Report Cards)
Holds generated report card entries with summary metrics.

Key fields:
- `student_id`, `session_id`, `term_id`, `class_id`, `section_id`, `exam_id`
- `total_marks`, `total_max_marks`, `percentage`, `grade`, `rank`
- `attendance_days`, `absent_days`
- `teacher_remarks`, `principal_remarks`
- `status` (`draft`, `published`)
- `generated_by`, `generated_at`
- Unique key: `(student_id, session_id, term_id, exam_id)`

### 1.7 `student_conduct` (Behavior / Conduct Grades)
Stores per-student conduct grades per term.

Key fields:
- `student_id`, `class_id`, `session_id`, `term_id`
- `conduct` (A/B/C/D/F)
- `remarks`, `entered_by`
- Unique: `(student_id, class_id, session_id, term_id)`

---

## 2. Core Workflows (Detailed)

### 2.1 Exam Management

#### Define an exam
- **UI:** `exams?route=exams`
- **Action:** `exams?route=exam-save`
- **Code:** `modules/exams/actions/exam_save.php`

**Key behavior:**
- Validates required fields and saves to `exams`.
- Uses status (`upcoming`, `ongoing`, `completed`, `cancelled`).

#### Schedule exam dates
- **UI:** `exams?route=exam-schedule`
- **Action:** `exams?route=exam-schedule-save`

**Key behavior:**
- Stores one row per class/subject schedule in `exam_schedules`.
- Includes `max_marks` and `pass_marks`.

---

### 2.2 Assessment & Student Results

This system lets teachers define assessments and enter student marks for each assessment.

#### Assessments (definition)
- **UI:** `exams?route=add-assessment`
- **Action:** `exams?route=assessment-save`

**Table:** `assessments`
**Key fields:** `name`, `class_id`, `subject_id`, `session_id`, `term_id`, `total_marks`.

#### Entering results
- **UI:** `exams?route=enter-results` (Results entry page)
- **Action:** `exams?route=results-save` → `modules/exams/actions/results_save.php`

**How it works (step-by-step):**
1. The form submits `assessment_id`, `class_id`, `term_id`, `subject_id`, and `results` array.
2. The backend validates inputs and loads the assessment.
3. For each student:
   - Determines if student is marked absent (`is_absent`).
   - Caps entered marks at the assessment’s `total_marks`.
   - Upserts into `student_results`.

**Note:**
- Marks are capped between 0 and total marks.
- `is_absent` triggers `marks_obtained = NULL` (or stored as null when absent).

---

### 2.3 Mark Entry (Alternative path)
There is a similar path for entering marks using the `marks` table (older or alternate flow) via:
- `exams?route=marks` and `exams?route=marks-save`

This flow uses the `marks` table (different schema) and is typically used for subject-specific entry.

---

### 2.4 Grade Scale Management

- **UI:** `exams?route=grade-scale`
- **Action:** `exams?route=grade-scale-save`

Grade scales map percentage ranges to letter grades and grade points.

---

### 2.5 Report Card Generation

#### Generate report cards
- **UI:** `exams?route=report-cards`
- **Action:** `exams?route=report-card-generate` (`modules/exams/actions/report_card_generate.php`)

**What it does:**
1. Collects active students for a given class/session/term.
2. Collects subject list for class.
3. Aggregates marks per student/subject from `student_results`.
4. Calculates total marks, percentage, grade, rank, attendance metrics.
5. Inserts report cards into `report_cards` (deleting existing for that class/term).

**Grade calculation (quick):**
- Percentage = total_marks / total_max_marks * 100
- Grade computed using hard-coded thresholds:
  - >= 90 → A
  - >= 80 → B
  - >= 70 → C
  - >= 60 → D
  - else → F

**Ranking:**
- Students are sorted by percentage, with equal scores sharing the same rank.

#### Print report card
- **UI:** `exams?route=report-card-print&id=<id>`
- **Public QR access:** `exams?route=report-card-print&id=<id>&copy=1&sig=<hmac>`

**Behavior:**
- The printed report pulls the report card by ID.
- If `copy` and `sig` are present, it verifies HMAC:
  - Signature is `hash_hmac('sha256', id|student_id|session_id, secret)`
  - Secret is `urjiberi_report_card_secret_2026`

#### QR Verification (public)
- **Route:** `exams?route=report-card-verify&id=<id>&sig=<hmac>`
- **File:** `modules/exams/actions/report_card_verify.php`

This returns an HTML validation badge confirming authenticity.

---

### 2.6 Result Analysis & Reports

- Result analysis view: `exams?route=result-analysis`
- Generates class/subject performance charts, pass/fail rates, etc.

- Roster generation view: `exams?route=roster`
  - Builds student lists for exam administration.

---

## 3. Data Model (Results Tables) at a Glance

### `exams`
- Primary exam definitions (midterm, final, etc.)

### `exam_schedules`
- Scheduling of exams per class/subject.

### `assessments`
- Assessment definitions for mark entry.

### `student_results`
- Marks per student per assessment.

### `marks`
- Alternate marks table used by mark entry UI.

### `grade_scales` / `grade_scale_entries`
- Grade mapping definitions.

### `report_cards`
- Generated report card summaries.

### `student_conduct`
- Per-student conduct grades per term.

---

## 4. Common Use Cases (Step-by-Step)

### 4.1 Create an exam and schedule it
1. Create exam under **Exams**.
2. Go to **Exam Schedule** and add schedule rows per class/subject.

### 4.2 Define an assessment + enter results
1. Create assessment under **Add Assessment**.
2. Use **Enter Results** to input marks per student.
3. Save results (stored in `student_results`).

### 4.3 Generate report cards
1. Go to **Report Cards**.
2. Select class/term and generate.
3. System calculates totals, percentage, grade, rank, attendance.

### 4.4 Print & verify report card
1. Use **Print Report Card** to generate PDF.
2. Use QR verification for public validation.

---

## 5. Troubleshooting & Tips

### 5.1 Missing student marks
- Ensure mark entry is saved in `student_results`.
- Verify the assessment belongs to the correct class/subject/session/term.

### 5.2 Report card shows missing data
- The generator uses subject list from `class_subjects`.
- Ensure subjects are assigned to the class for that session.

### 5.3 QR verification fails
- Make sure the `sig` matches the HMAC computed using the fixed secret (`urjiberi_report_card_secret_2026`).
- Ensure the report card ID and session match the signature inputs.

---

## 6. Extension Points (What to Add Next)

### 6.1 Use grade scales for grading
The system currently uses hard-coded grade thresholds. You can modify `report_card_generate.php` to use `grade_scales` and `grade_scale_entries`.

### 6.2 Add subject-level grade breakdown in report card
Currently report cards store only summary totals. You can extend `report_cards` (or add a child table) to store per-subject marks in report cards.

### 6.3 Add pass/fail logic per subject
The generator can compare each subject’s mark against `pass_marks` from `exam_schedules`.

---

**End of Results System documentation.**
