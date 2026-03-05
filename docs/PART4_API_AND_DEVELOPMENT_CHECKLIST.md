# Urji Beri School Management System — Pre-Development Documentation

## PART 4: API DESIGN & DEVELOPMENT CHECKLIST

**Document Version:** 1.0.0  
**Date:** February 27, 2026  
**Status:** Pre-Development Planning  

---

# TABLE OF CONTENTS — PART 4

8. [API Design](#8-api-design)
   - 8.1 URL Structure
   - 8.2 Authentication Module Endpoints
   - 8.3 Dashboard Module Endpoints
   - 8.4 Academics Module Endpoints
   - 8.5 Students Module Endpoints
   - 8.6 Attendance Module Endpoints
   - 8.7 Exams Module Endpoints
   - 8.8 Finance Module Endpoints
   - 8.9 Communication Module Endpoints
   - 8.10 Users Module Endpoints
   - 8.11 Settings Module Endpoints
   - 8.12 API Module (AJAX) Endpoints
9. [Development Checklist](#9-development-checklist)

---

# 8. API DESIGN

## 8.1 URL Structure

All endpoints route through the front controller at `public/index.php`.

### Base URL Pattern
```
https://domain.com/?module={module}&action={action}&id={id}
```

### URL Helper Function
```php
url(string $module, string $action = 'index', array $params = []): string
```

### Standard Parameters
| Parameter | Source | Description |
|-----------|--------|-------------|
| `module` | `$_GET['module']` | Module name (e.g., `students`, `finance`) |
| `action` | `$_GET['action']` | Action name (e.g., `create`, `edit`) |
| `id` | `$_GET['id']` | Entity ID (for view/edit/delete) |
| `page` | `$_GET['page']` | Pagination page number |
| `per_page` | `$_GET['per_page']` | Items per page |

### HTTP Methods
| Method | Usage |
|--------|-------|
| `GET` | Page rendering, data retrieval |
| `POST` | Form submissions, state-changing actions |

### Response Types
| Content-Type | Usage |
|-------------|-------|
| `text/html` | All page routes (default) |
| `application/json` | API module, AJAX endpoints |

### Security Headers (All Responses)
```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Strict-Transport-Security: max-age=31536000; includeSubDomains (HTTPS only)
```

---

## 8.2 Authentication Module Endpoints (6 routes)

| # | Method | URL | Permission | Handler | Description |
|---|--------|-----|-----------|---------|-------------|
| 1 | GET | `?module=auth&action=login` | Public | `auth/views/login.php` | Login page (redirects if logged in) |
| 2 | POST | `?module=auth&action=login` | Public | `auth/actions/login.php` | Process login |
| 3 | GET/POST | `?module=auth&action=logout` | Authenticated | Inline (routes.php) | Logout + redirect |
| 4 | GET | `?module=auth&action=forgot-password` | Public | `auth/views/forgot_password.php` | Forgot password form |
| 5 | POST | `?module=auth&action=forgot-password` | Public | `auth/actions/forgot_password.php` | Process password reset request |
| 6 | GET | `?module=auth&action=reset-password&token=X` | Public | `auth/views/reset_password.php` | Reset password form |
| 7 | POST | `?module=auth&action=reset-password` | Public | `auth/actions/reset_password.php` | Process password reset |
| 8 | GET | `?module=auth&action=change-password` | Authenticated | `auth/views/change_password.php` | Change password form |
| 9 | POST | `?module=auth&action=change-password` | Authenticated | `auth/actions/change_password.php` | Process password change |
| 10 | GET | `?module=auth&action=profile` | Authenticated | `auth/views/profile.php` | View/edit profile form |
| 11 | POST | `?module=auth&action=profile` | Authenticated | `auth/actions/update_profile.php` | Update profile |

### POST /auth/login — Request Body
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `username_or_email` | string | Yes | Non-empty, max 255 |
| `password` | string | Yes | Non-empty |
| `csrf_token` | string | Yes | Valid CSRF token |

### POST /auth/login — Response
- **Success**: `302 Redirect` → `?module=dashboard` (or stored intended URL)
- **Failure**: `302 Redirect` → `?module=auth&action=login` with flash error
- **Locked**: `302 Redirect` → `?module=auth&action=login` with lockout message

---

## 8.3 Dashboard Module Endpoints (1 route)

| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 1 | GET | `?module=dashboard` | Authenticated | `dashboard/views/index.php` |

---

## 8.4 Academics Module Endpoints (27 routes)

### Academic Sessions
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 1 | GET | `?module=academics&action=sessions` | `academics.manage` | `views/sessions.php` |
| 2 | POST | `?module=academics&action=session-save` | `academics.manage` | `actions/session_save.php` |
| 3 | POST | `?module=academics&action=session-toggle` | `academics.manage` | `actions/session_toggle.php` |

### Terms
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 4 | GET | `?module=academics&action=terms` | `academics.manage` | `views/terms.php` |
| 5 | POST | `?module=academics&action=term-save` | `academics.manage` | `actions/term_save.php` |

### Mediums
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 6 | GET | `?module=academics&action=mediums` | `academics.manage` | `views/mediums.php` |
| 7 | POST | `?module=academics&action=medium-save` | `academics.manage` | `actions/medium_save.php` |

### Streams
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 8 | GET | `?module=academics&action=streams` | `academics.manage` | `views/streams.php` |
| 9 | POST | `?module=academics&action=stream-save` | `academics.manage` | `actions/stream_save.php` |

### Shifts
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 10 | GET | `?module=academics&action=shifts` | `academics.manage` | `views/shifts.php` |
| 11 | POST | `?module=academics&action=shift-save` | `academics.manage` | `actions/shift_save.php` |

### Classes
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 12 | GET | `?module=academics&action=classes` | `academics.manage` | `views/classes.php` |
| 13 | POST | `?module=academics&action=class-save` | `academics.manage` | `actions/class_save.php` |

### Sections
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 14 | GET | `?module=academics&action=sections` | `academics.manage` | `views/sections.php` |
| 15 | POST | `?module=academics&action=section-save` | `academics.manage` | `actions/section_save.php` |

### Subjects
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 16 | GET | `?module=academics&action=subjects` | `academics.manage` | `views/subjects.php` |
| 17 | POST | `?module=academics&action=subject-save` | `academics.manage` | `actions/subject_save.php` |

### Class-Subject Assignment
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 18 | GET | `?module=academics&action=class-subjects` | `academics.manage` | `views/class_subjects.php` |
| 19 | POST | `?module=academics&action=class-subjects-save` | `academics.manage` | `actions/class_subjects_save.php` |

### Elective Subjects
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 20 | GET | `?module=academics&action=elective-subjects` | `academics.manage` | `views/elective_subjects.php` |
| 21 | POST | `?module=academics&action=elective-subjects-save` | `academics.manage` | `actions/elective_subjects_save.php` |

### Class/Subject Teachers
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 22 | GET | `?module=academics&action=class-teachers` | `academics.manage` | `views/class_teachers.php` |
| 23 | POST | `?module=academics&action=class-teacher-save` | `academics.manage` | `actions/class_teacher_save.php` |
| 24 | GET | `?module=academics&action=subject-teachers` | `academics.manage` | `views/subject_teachers.php` |
| 25 | POST | `?module=academics&action=subject-teacher-save` | `academics.manage` | `actions/subject_teacher_save.php` |
| 26 | POST | `?module=academics&action=subject-teacher-delete` | `academics.manage` | `actions/subject_teacher_delete.php` |

### Promotion & Timetable
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 27 | GET | `?module=academics&action=promote` | `academics.manage` | `views/promote.php` |
| 28 | POST | `?module=academics&action=promote-save` | `academics.manage` | `actions/promote_save.php` |
| 29 | GET | `?module=academics&action=timetable` | `timetable.view` | `views/timetable.php` |
| 30 | POST | `?module=academics&action=timetable-save` | `timetable.manage` | `actions/timetable_save.php` |

---

## 8.5 Students Module Endpoints (14 routes)

| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 1 | GET | `?module=students&action=index` | `students.view` | `views/index.php` |
| 2 | GET | `?module=students&action=create` | `students.create` | `views/create.php` |
| 3 | POST | `?module=students&action=create` | `students.create` | `actions/store.php` |
| 4 | GET | `?module=students&action=edit&id=X` | `students.edit` | `views/edit.php` |
| 5 | POST | `?module=students&action=edit&id=X` | `students.edit` | `actions/update.php` |
| 6 | GET | `?module=students&action=view&id=X` | `students.view` | `views/view.php` |
| 7 | POST | `?module=students&action=delete` | `students.delete` | `actions/delete.php` |
| 8 | GET | `?module=students&action=promote` | `students.promote` | `views/promote.php` |
| 9 | POST | `?module=students&action=promote` | `students.promote` | `actions/promote.php` |
| 10 | GET | `?module=students&action=roll-numbers` | `students.edit` | `views/roll_numbers.php` |
| 11 | POST | `?module=students&action=roll-numbers` | `students.edit` | `actions/save_roll_numbers.php` |
| 12 | GET | `?module=students&action=details` | `students.view` | `views/details.php` |
| 13 | GET | `?module=students&action=id-cards` | `students.view` | `views/id_cards.php` |
| 14 | GET | `?module=students&action=credentials` | `students.edit` | `views/credentials.php` |
| 15 | POST | `?module=students&action=credentials` | `students.edit` | `actions/generate_credentials.php` |
| 16 | GET | `?module=students&action=reset-password` | `students.edit` | `views/reset_password.php` |
| 17 | POST | `?module=students&action=reset-password` | `students.edit` | `actions/reset_student_password.php` |
| 18 | GET | `?module=students&action=bulk-import` | `students.create` | `views/bulk_import.php` |
| 19 | POST | `?module=students&action=bulk-import` | `students.create` | `actions/bulk_import.php` |
| 20 | GET | `?module=students&action=sample-csv` | `students.create` | `actions/sample_csv.php` |
| 21 | POST | `?module=students&action=enroll` | `students.create` | `actions/enroll.php` |
| 22 | GET | `?module=students&action=export` | `students.view` | `actions/export.php` |

### POST /students/create — Request Body (multipart/form-data)
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `first_name` | string | Yes | 2-100 chars |
| `last_name` | string | Yes | 2-100 chars |
| `gender` | enum | Yes | male/female/other |
| `date_of_birth` | date | Yes | YYYY-MM-DD, not future |
| `admission_no` | string | No | Unique, auto-generated if empty |
| `admission_date` | date | Yes | YYYY-MM-DD |
| `session_id` | int | Yes | Valid session |
| `class_id` | int | Yes | Valid class |
| `section_id` | int | No | Valid section within class |
| `photo` | file | No | JPEG/PNG/GIF/WebP, ≤5MB |
| `blood_group` | enum | No | A+/A-/B+/B-/AB+/AB-/O+/O- |
| `religion` | string | No | Max 50 chars |
| `nationality` | string | No | Default: "Ethiopian" |
| `phone` | string | No | Max 20 chars |
| `email` | string | No | Valid email |
| `address` | string | No | Text |
| `city` / `region` | string | No | Max 100 chars |
| `guardian[first_name]` | string | Yes | |
| `guardian[last_name]` | string | Yes | |
| `guardian[relation]` | enum | Yes | |
| `guardian[phone]` | string | Yes | |

---

## 8.6 Attendance Module Endpoints (5 routes)

| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 1 | GET | `?module=attendance&action=index` | `attendance.view` | `views/take.php` |
| 2 | POST | `?module=attendance&action=save` | `attendance.manage` | `actions/save.php` |
| 3 | GET | `?module=attendance&action=report` | `attendance.view` | `views/report.php` |
| 4 | GET | `?module=attendance&action=view` | `attendance.view` | `views/view.php` |
| 5 | GET | `?module=attendance&action=student` | `attendance.view` | `views/student.php` |

### POST /attendance/save — Request Body
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `class_id` | int | Yes | Class ID |
| `section_id` | int | No | Section ID |
| `date` | date | Yes | Attendance date (not future) |
| `attendance[{student_id}]` | enum | Yes | present/absent/late/excused per student |
| `remarks[{student_id}]` | string | No | Optional remarks per student |
| `csrf_token` | string | Yes | CSRF token |

---

## 8.7 Exams Module Endpoints (19 routes + 2 AJAX)

### Assignments
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 1 | GET | `?module=exams&action=assignments` | `assignment.view` | `views/assignments.php` |
| 2 | GET | `?module=exams&action=assignment-create` | `assignment.manage` | `views/assignment_form.php` |
| 3 | POST | `?module=exams&action=assignment-save` | `assignment.manage` | `actions/assignment_save.php` |
| 4 | GET | `?module=exams&action=assignment-view` | `assignment.view` | `views/assignment_view.php` |
| 5 | POST | `?module=exams&action=assignment-delete` | `assignment.manage` | `actions/assignment_delete.php` |

### Exams & Marks
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 6 | GET | `?module=exams&action=exams` | `exam.view` | `views/exams.php` |
| 7 | POST | `?module=exams&action=exam-save` | `exam.manage` | `actions/exam_save.php` |
| 8 | GET | `?module=exams&action=exam-schedule` | `exam.manage` | `views/exam_schedule.php` |
| 9 | POST | `?module=exams&action=exam-schedule-save` | `exam.manage` | `actions/exam_schedule_save.php` |
| 10 | GET | `?module=exams&action=marks` | `marks.view` | `views/marks.php` |
| 11 | POST | `?module=exams&action=marks-save` | `marks.manage` | `actions/marks_save.php` |

### Assessments & Results
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 12 | GET | `?module=exams&action=add-assessment` | `exam.manage` | `views/assessment_add.php` |
| 13 | POST | `?module=exams&action=assessment-save` | `exam.manage` | `actions/assessment_save.php` |
| 14 | POST | `?module=exams&action=assessment-delete` | `exam.manage` | `actions/assessment_delete.php` |
| 15 | GET | `?module=exams&action=enter-conduct` | `marks.view` | `views/enter_conduct.php` |
| 16 | POST | `?module=exams&action=conduct-save` | `marks.manage` | `actions/conduct_save.php` |
| 17 | GET | `?module=exams&action=enter-results` | `marks.view` | `views/enter_results.php` |
| 18 | POST | `?module=exams&action=results-save` | `marks.manage` | `actions/results_save.php` |

### Reports & Grade Scale
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 19 | GET | `?module=exams&action=roster` | `report_card.view` | `views/roster.php` |
| 20 | GET | `?module=exams&action=result-cards` | `report_card.view` | `views/result_cards.php` |
| 21 | GET | `?module=exams&action=result-analysis` | `exam.view` | `views/result_analysis.php` |
| 22 | GET | `?module=exams&action=grade-scale` | `exam.manage` | `views/grade_scale.php` |
| 23 | POST | `?module=exams&action=grade-scale-save` | `exam.manage` | `actions/grade_scale_save.php` |
| 24 | GET | `?module=exams&action=report-cards` | `report_card.view` | `views/report_cards.php` |
| 25 | POST | `?module=exams&action=report-card-generate` | `report_card.manage` | `actions/report_card_generate.php` |
| 26 | GET | `?module=exams&action=report-card-print` | `report_card.view` | `views/report_card_print.php` |

### AJAX Endpoints
| # | Method | URL | Permission | Returns |
|---|--------|-----|-----------|---------|
| 27 | GET | `?module=exams&action=ajax-subject-total&class_id=X&subject_id=Y&term_id=Z` | `exam.view` | `{"total": N, "remaining": 100-N}` |
| 28 | GET | `?module=exams&action=ajax-subjects&class_id=X` | `exam.view` | `[{"id": N, "name": "..."}]` |

---

## 8.8 Finance Module Endpoints (36 routes)

### New Fee Management System (18 routes)
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 1 | GET | `?module=finance&action=fm-dashboard` | `fee_management.view_dashboard` | `views/fm_dashboard.php` |
| 2 | GET | `?module=finance&action=fm-create-fee` | `fee_management.create_fee` | `views/fm_fee_form.php` |
| 3 | GET | `?module=finance&action=fm-edit-fee` | `fee_management.create_fee` | `views/fm_fee_form.php` |
| 4 | POST | `?module=finance&action=fm-fee-save` | `fee_management.create_fee` | `actions/fm_fee_save.php` |
| 5 | GET | `?module=finance&action=fm-manage-fees` | `fee_management.view_dashboard` | `views/fm_manage_fees.php` |
| 6 | POST | `?module=finance&action=fm-fee-toggle` | `fee_management.activate_fee` | `actions/fm_fee_toggle.php` |
| 7 | POST | `?module=finance&action=fm-fee-delete` | `fee_management.delete_fee` | `actions/fm_fee_delete.php` |
| 8 | POST | `?module=finance&action=fm-fee-duplicate` | `fee_management.create_fee` | `actions/fm_fee_duplicate.php` |
| 9 | GET | `?module=finance&action=fm-fee-view` | `fee_management.view_dashboard` | `views/fm_fee_view.php` |
| 10 | GET | `?module=finance&action=fm-assign-fees` | `fee_management.assign_fee` | `views/fm_assign_fees.php` |
| 11 | POST | `?module=finance&action=fm-assignment-save` | `fee_management.assign_fee` | `actions/fm_assignment_save.php` |
| 12 | POST | `?module=finance&action=fm-assignment-delete` | `fee_management.assign_fee` | `actions/fm_assignment_delete.php` |
| 13 | POST | `?module=finance&action=fm-exemption-save` | `fee_management.manage_exemptions` | `actions/fm_exemption_save.php` |
| 14 | POST | `?module=finance&action=fm-exemption-delete` | `fee_management.manage_exemptions` | `actions/fm_exemption_delete.php` |
| 15 | GET | `?module=finance&action=fm-groups` | `fee_management.manage_groups` | `views/fm_groups.php` |
| 16 | GET | `?module=finance&action=fm-group-form` | `fee_management.manage_groups` | `views/fm_group_form.php` |
| 17 | POST | `?module=finance&action=fm-group-save` | `fee_management.manage_groups` | `actions/fm_group_save.php` |
| 18 | POST | `?module=finance&action=fm-group-delete` | `fee_management.manage_groups` | `actions/fm_group_delete.php` |
| 19 | GET | `?module=finance&action=fm-group-members` | `fee_management.manage_groups` | `views/fm_group_members.php` |
| 20 | POST | `?module=finance&action=fm-group-member-add` | `fee_management.manage_groups` | `actions/fm_group_member_add.php` |
| 21 | POST | `?module=finance&action=fm-group-member-remove` | `fee_management.manage_groups` | `actions/fm_group_member_remove.php` |
| 22 | GET | `?module=finance&action=fm-reports` | `fee_management.view_reports` | `views/fm_reports.php` |
| 23 | GET | `?module=finance&action=fm-report-export` | `fee_management.export_reports` | `actions/fm_report_export.php` |
| 24 | GET | `?module=finance&action=fm-api-students` | `fee_management.assign_fee` | `actions/fm_api_students.php` |
| 25 | GET | `?module=finance&action=fm-api-fee-students` | `fee_management.assign_fee` | `actions/fm_api_fee_students.php` |
| 26 | POST | `?module=finance&action=fm-charge-waive` | `fee_management.manage_charges` | `actions/fm_charge_waive.php` |
| 27 | GET | `?module=finance&action=fm-payment` | `fee_management.view_dashboard` | `views/fm_payment.php` |
| 28 | POST | `?module=finance&action=fm-payment-save` | `fee_management.manage_charges` | `actions/fm_payment_save.php` |
| 29 | GET | `?module=finance&action=fm-generate-invoice` | `fee_management.view_dashboard` | `views/fm_generate_invoice.php` |

### Legacy Finance (18 routes)
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 30 | GET | `?module=finance&action=fee-categories` | `finance.view` | `views/fee_categories.php` |
| 31 | POST | `?module=finance&action=fee-category-save` | `finance.create` | `actions/fee_category_save.php` |
| 32 | POST | `?module=finance&action=fee-category-delete` | `finance.delete` | `actions/fee_category_delete.php` |
| 33 | GET | `?module=finance&action=fee-structures` | `finance.view` | `views/fee_structures.php` |
| 34 | GET | `?module=finance&action=fee-structure-create` | `finance.create` | `views/fee_structure_form.php` |
| 35 | POST | `?module=finance&action=fee-structure-save` | `finance.create` | `actions/fee_structure_save.php` |
| 36 | POST | `?module=finance&action=fee-structure-delete` | `finance.delete` | `actions/fee_structure_delete.php` |
| 37 | GET | `?module=finance&action=invoices` | `finance.view` | `views/invoices.php` |
| 38 | GET | `?module=finance&action=invoice-create` | `finance.create` | `views/invoice_form.php` |
| 39 | POST | `?module=finance&action=invoice-generate` | `finance.create` | `actions/invoice_generate.php` |
| 40 | GET | `?module=finance&action=invoice-view` | `finance.view` | `views/invoice_view.php` |
| 41 | GET | `?module=finance&action=invoice-print` | `finance.view` | `views/invoice_print.php` |
| 42 | POST | `?module=finance&action=invoice-delete` | `finance.delete` | `actions/invoice_delete.php` |
| 43 | GET | `?module=finance&action=payments` | `finance.view` | `views/payments.php` |
| 44 | GET | `?module=finance&action=payment-record` | `finance.payment` | `views/payment_form.php` |
| 45 | POST | `?module=finance&action=payment-save` | `finance.payment` | `actions/payment_save.php` |
| 46 | GET | `?module=finance&action=payment-receipt` | `finance.view` | `views/payment_receipt.php` |
| 47 | GET | `?module=finance&action=discounts` | `finance.view` | `views/discounts.php` |
| 48 | GET | `?module=finance&action=discount-create` | `finance.create` | `views/discount_form.php` |
| 49 | POST | `?module=finance&action=discount-save` | `finance.create` | `actions/discount_save.php` |
| 50 | POST | `?module=finance&action=discount-delete` | `finance.delete` | `actions/discount_delete.php` |
| 51 | GET | `?module=finance&action=pay-online` | Authenticated | `views/pay_online.php` |
| 52 | POST | `?module=finance&action=payment-initiate` | Authenticated | `actions/payment_initiate.php` |
| 53 | GET/POST | `?module=finance&action=payment-callback` | Public (webhook) | `actions/payment_callback.php` |
| 54 | GET | `?module=finance&action=fee-report` | `finance.view` | `views/fee_report.php` |

---

## 8.9 Communication Module Endpoints (11 routes)

### Announcements
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 1 | GET | `?module=communication&action=announcements` | Authenticated | `views/announcements.php` |
| 2 | GET | `?module=communication&action=announcement-create` | `communication.create` | `views/announcement_form.php` |
| 3 | GET | `?module=communication&action=announcement-edit` | `communication.create` | `views/announcement_form.php` |
| 4 | POST | `?module=communication&action=announcement-save` | `communication.create` | `actions/announcement_save.php` |
| 5 | GET | `?module=communication&action=announcement-view` | Authenticated | `views/announcement_view.php` |
| 6 | POST | `?module=communication&action=announcement-delete` | `communication.delete` | `actions/announcement_delete.php` |

### Messages
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 7 | GET | `?module=communication&action=messages` | Authenticated | `views/inbox.php` |
| 8 | GET | `?module=communication&action=message-compose` | Authenticated | `views/compose.php` |
| 9 | POST | `?module=communication&action=message-send` | Authenticated | `actions/message_send.php` |
| 10 | GET | `?module=communication&action=message-view` | Authenticated | `views/message_view.php` |
| 11 | GET | `?module=communication&action=sent` | Authenticated | `views/sent.php` |

### Notifications
| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 12 | GET | `?module=communication&action=notifications` | Authenticated | `views/notifications.php` |
| 13 | POST | `?module=communication&action=notification-read` | Authenticated | `actions/notification_read.php` |
| 14 | POST | `?module=communication&action=notifications-read-all` | Authenticated | `actions/notification_read_all.php` |

---

## 8.10 Users Module Endpoints (6 routes)

| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 1 | GET | `?module=users&action=index` | `users.view` | `views/index.php` |
| 2 | GET | `?module=users&action=create` | `users.create` | `views/create.php` |
| 3 | POST | `?module=users&action=create` | `users.create` | `actions/store.php` |
| 4 | GET | `?module=users&action=edit&id=X` | `users.edit` | `views/edit.php` |
| 5 | POST | `?module=users&action=edit&id=X` | `users.edit` | `actions/update.php` |
| 6 | GET | `?module=users&action=view&id=X` | `users.view` | `views/view.php` |
| 7 | POST | `?module=users&action=delete` | `users.delete` | `actions/delete.php` |
| 8 | POST | `?module=users&action=toggle-status` | `users.edit` | `actions/toggle_status.php` |

---

## 8.11 Settings Module Endpoints (4 routes)

| # | Method | URL | Permission | Handler |
|---|--------|-----|-----------|---------|
| 1 | GET | `?module=settings&action=general` | `settings.view` | `views/general.php` |
| 2 | POST | `?module=settings&action=general-save` | `settings.update` | `actions/general_save.php` |
| 3 | GET | `?module=settings&action=audit-logs` | `audit_logs.view` | `views/audit_logs.php` |
| 4 | GET | `?module=settings&action=backup` | Super Admin only | `views/backup.php` |

---

## 8.12 API Module (AJAX) Endpoints (2 routes)

| # | Method | URL | Permission | Response |
|---|--------|-----|-----------|----------|
| 1 | GET | `?module=api&action=sections&class_id=X` | Authenticated | `[{"id": 1, "name": "A"}, ...]` |
| 2 | GET | `?module=api&action=subjects&class_id=X&session_id=Y` | Authenticated | `[{"id": 1, "name": "Math", "code": "MATH"}, ...]` |

**Headers** (all API responses):
```
Content-Type: application/json; charset=utf-8
Cache-Control: no-store
```

---

# 9. DEVELOPMENT CHECKLIST

## 9.1 Infrastructure & Configuration

### Configuration Files
- [ ] `config/app.php` — Application constants (APP_NAME, APP_URL, paths, pagination, session, upload, security, feature flags)
- [ ] `config/database.php` — Database connection constants (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_CHARSET)
- [ ] `config/payment.php` — Payment gateway constants (Telebirr, Chapa, Stripe credentials, callback URLs)
- [ ] `.env` — Environment variables (18 variables: database, payment, debug)
- [ ] `.htaccess` — Apache rewrite rules (public/ directory)

### Core Libraries
- [ ] `core/env.php` — Environment variable loader (`env_load()`, `env()`)
- [ ] `core/db.php` — PDO database abstraction (`db_connect()`, `db_query()`, `db_fetch()`, `db_fetch_all()`, `db_fetch_value()`, `db_insert()`, `db_update()`, `db_delete()`, `db_transaction()`, `db_last_id()`, `db_count()`, `db_table_exists()`, `get_active_session()`, `get_active_term()`)
- [ ] `core/auth.php` — Authentication & authorization (`auth_attempt()`, `auth_check()`, `auth_user()`, `auth_user_id()`, `auth_require()`, `auth_require_permission()`, `auth_has_permission()`, `auth_has_role()`, `auth_is_super_admin()`, `auth_logout()`, `auth_load_permissions()`)
- [ ] `core/csrf.php` — CSRF protection (`csrf_token()`, `csrf_field()`, `csrf_protect()`)
- [ ] `core/router.php` — Request routing (`router_dispatch()`, `current_module()`, `current_action()`, `route_id()`, `url()`, `redirect()`, `is_post()`, `is_ajax()`)
- [ ] `core/validation.php` — Input validation & sanitization (19 functions: `validate_input()`, `input()`, `input_int()`, `input_email()`, `input_date()`, `input_array()`, `old()`, `validate_required()`, `validate_email()`, `validate_min_length()`, `validate_max_length()`, `validate_unique()`, `validate_exists()`, `validate_in()`, `validate_date()`, `validate_file()`, `validate_numeric()`, `validate_integer()`, `validate_regex()`)
- [ ] `core/response.php` — HTTP responses (`json_response()`, `json_success()`, `json_error()`)
- [ ] `core/security.php` — Security utilities (`security_headers()`, `sanitize_input()`, `generate_token()`, `rate_limit_check()`, `validate_file_upload()`, `safe_file_name()`, `get_client_ip()`)
- [ ] `core/helpers.php` — Global helpers (28 functions: `e()`, `asset()`, `format_date()`, `format_datetime()`, `format_currency()`, `time_ago()`, `str_slug()`, `set_flash()`, `get_flash()`, `has_flash()`, `paginate()`, `audit_log()`, `class_active()`, `truncate()`, `array_get()`, `is_current_module()`, `dd()`, `get_initials()`, `format_bytes()`, `get_ethiopian_months()`, `calculate_age()`, `ordinal_suffix()`, `mask_email()`, `generate_password()`, `validate_phone_et()`, `clean_phone_et()`, `is_valid_date_range()`, `school_year_label()`)
- [ ] `core/rbac.php` — Role-Based Access Control (`rbac_get_roles()`, `rbac_get_role()`, `rbac_create_role()`, `rbac_update_role()`, `rbac_delete_role()`, `rbac_get_permissions()`, `rbac_get_role_permissions()`, `rbac_sync_role_permissions()`, `rbac_assign_user_role()`, `rbac_remove_user_role()`, `rbac_get_user_roles()`, `rbac_user_has_permission()`)
- [ ] `core/payment_gateway.php` — Payment abstraction (`pg_get_active_gateways()`, `pg_get_gateway()`, `pg_initialize_payment()`, `pg_handle_callback()`, `pg_check_status()`, `pg_verify_webhook()`, `pg_create_transaction()`, `pg_update_transaction()`, `pg_generate_idempotency_key()`, `pg_log_attempt()`, `pg_link_invoice()`, `pg_reconcile()`)
- [ ] `core/pwa.php` — PWA helpers (`pwa_meta_tags()`, `pwa_manifest_data()`, `pwa_get_install_prompt()`)
- [ ] `core/gateways/chapa.php` — Chapa gateway (`chapa_initialize()`, `chapa_verify()`)
- [ ] `core/gateways/telebirr.php` — Telebirr gateway (`telebirr_initialize()`, `telebirr_decrypt_callback()`, `telebirr_verify()`)

### Entry Points
- [ ] `public/index.php` — Front controller (bootstrap, session, routing)
- [ ] `public/uploads.php` — File upload proxy (secure serving)
- [ ] `public/service-worker.js` — PWA service worker (caching, offline)
- [ ] `public/manifest.webmanifest` — PWA manifest
- [ ] `public/offline.html` — Offline fallback page

---

## 9.2 Database

### Schema Files
- [ ] `sql/schema.sql` — Master schema (54 tables, 1167 lines)
- [ ] `sql/seed.sql` — Seed data (roles, permissions, role_permissions, default admin, grade scale, payment gateways, settings)
- [ ] `sql/run_migration.php` — Migration runner script
- [ ] `sql/check_seed.php` — Seed data verification script
- [ ] `sql/seed_2026_2027.php` — Academic year 2026/2027 seed data

### Migration Files
- [ ] `sql/001_core_auth.sql` — roles, permissions, users, login_attempts, role_permissions, user_roles
- [ ] `sql/002_academics.sql` — academic_sessions, terms, classes, sections, subjects, grade_scales, grade_scale_entries, class_subjects, class_teachers, timetables
- [ ] `sql/003_students.sql` — students, guardians, student_guardians, enrollments, promotions, student_documents, student_elective_subjects
- [ ] `sql/004_assessment.sql` — attendance, assignments, assignment_submissions, exams, exam_schedules, marks, report_cards
- [ ] `sql/005_finance.sql` — fee_categories, fee_structures, invoices, invoice_items, payments, fee_discounts, student_fee_discounts
- [ ] `sql/006_payment_gateway.sql` — payment_gateways, payment_transactions, payment_attempts, payment_webhooks, payment_reconciliation_logs, invoice_payment_links
- [ ] `sql/007_communication.sql` — announcements, messages, notifications
- [ ] `sql/008_system.sql` — audit_logs, settings
- [ ] `sql/009_seed_data.sql` — Default roles, permissions, admin user, grade scale, gateways
- [ ] `sql/010_schema_fix.sql` — ALTER TABLE: add full_name to students/guardians, is_active to users
- [ ] `sql/011_academics_extended.sql` — mediums, streams, shifts tables + ALTER classes
- [ ] `sql/012_results_module.sql` — assessments, student_results tables
- [ ] `sql/013_student_conduct.sql` — student_conduct table
- [ ] `sql/014_fix_assessment_unique.sql` — Deduplicate assessments + add UNIQUE index
- [ ] `sql/015_fee_management.sql` — fees, recurrence_configs, penalty_configs, student_groups, student_group_members, fee_assignments, fee_exemptions, student_fee_charges, penalty_charges, finance_audit_log
- [ ] `sql/016_fee_management_permissions.sql` — 12 fee_management.* permissions + role assignments
- [ ] `sql/017_fee_management_seed.sql` — Sample fee data
- [ ] `sql/018_rbac_demo_users.sql` — 6 demo user accounts

---

## 9.3 Templates & Layouts

- [ ] `templates/layout.php` — Master HTML layout (head, sidebar, header, main content, footer, scripts)
- [ ] `templates/partials/header.php` — Top navigation bar (user menu, notifications, search)
- [ ] `templates/partials/sidebar.php` — Left sidebar navigation (role-based menu items)
- [ ] `templates/partials/mobile_nav.php` — Mobile bottom navigation bar
- [ ] `templates/partials/flash.php` — Flash message component (success/error/warning/info)
- [ ] `templates/partials/academics_nav.php` — Academics sub-navigation tabs
- [ ] `templates/errors/403.php` — Access denied page
- [ ] `templates/errors/404.php` — Page not found page

---

## 9.4 Public Assets

### CSS
- [ ] `public/assets/css/style.css` — Custom stylesheet (Tailwind overrides, component styles)

### JavaScript
- [ ] `public/assets/js/app.js` — Core application JavaScript (AJAX, modals, form handling, dark mode, mobile nav)

### Images & Icons
- [ ] `public/assets/icons/icon-192x192.png` — PWA icon 192px
- [ ] `public/assets/icons/icon-512x512.png` — PWA icon 512px
- [ ] `public/img/logo.png` — School logo
- [ ] `public/img/default-avatar.png` — Default user avatar

---

## 9.5 Module: Auth (6 routes)

### Views
- [ ] `modules/auth/views/login.php` — Login form (username/email + password)
- [ ] `modules/auth/views/forgot_password.php` — Forgot password form
- [ ] `modules/auth/views/reset_password.php` — Reset password form (with token)
- [ ] `modules/auth/views/change_password.php` — Change password form (authenticated)
- [ ] `modules/auth/views/profile.php` — User profile view/edit form

### Actions
- [ ] `modules/auth/actions/login.php` — Process login (validate, check lockout, verify password, create session)
- [ ] `modules/auth/actions/forgot_password.php` — Generate reset token, log reset URL
- [ ] `modules/auth/actions/reset_password.php` — Validate token, update password
- [ ] `modules/auth/actions/change_password.php` — Verify current password, update to new
- [ ] `modules/auth/actions/update_profile.php` — Update user profile fields + avatar upload

### Routes
- [ ] `modules/auth/routes.php` — Auth routing (login, logout, forgot-password, reset-password, change-password, profile)

---

## 9.6 Module: Dashboard (1 route)

### Views
- [ ] `modules/dashboard/views/index.php` — Role-based dashboard (stats cards, charts, recent activity)

### Routes
- [ ] `modules/dashboard/routes.php` — Dashboard routing

---

## 9.7 Module: Academics (27 routes)

### Views (14 views)
- [ ] `modules/academics/views/sessions.php` — Academic sessions CRUD table + modal form
- [ ] `modules/academics/views/terms.php` — Terms CRUD table + modal form
- [ ] `modules/academics/views/mediums.php` — Mediums CRUD table + modal form
- [ ] `modules/academics/views/streams.php` — Streams CRUD table + modal form
- [ ] `modules/academics/views/shifts.php` — Shifts CRUD table + modal form
- [ ] `modules/academics/views/classes.php` — Classes CRUD table + modal form
- [ ] `modules/academics/views/sections.php` — Sections CRUD table + modal form
- [ ] `modules/academics/views/subjects.php` — Subjects CRUD table + modal form
- [ ] `modules/academics/views/class_subjects.php` — Class-subject mapping matrix (checkboxes)
- [ ] `modules/academics/views/elective_subjects.php` — Student elective assignment form
- [ ] `modules/academics/views/class_teachers.php` — Class teacher assignment form
- [ ] `modules/academics/views/subject_teachers.php` — Subject teacher assignment table
- [ ] `modules/academics/views/promote.php` — Student promotion form (source → target)
- [ ] `modules/academics/views/timetable.php` — Weekly timetable grid

### Actions (13 actions)
- [ ] `modules/academics/actions/session_save.php` — Create/update academic session
- [ ] `modules/academics/actions/session_toggle.php` — Activate/deactivate session
- [ ] `modules/academics/actions/term_save.php` — Create/update term
- [ ] `modules/academics/actions/medium_save.php` — Create/update medium
- [ ] `modules/academics/actions/stream_save.php` — Create/update stream
- [ ] `modules/academics/actions/shift_save.php` — Create/update shift
- [ ] `modules/academics/actions/class_save.php` — Create/update class
- [ ] `modules/academics/actions/section_save.php` — Create/update section
- [ ] `modules/academics/actions/subject_save.php` — Create/update subject
- [ ] `modules/academics/actions/class_subjects_save.php` — Save class-subject assignments (transaction)
- [ ] `modules/academics/actions/elective_subjects_save.php` — Save student elective selections
- [ ] `modules/academics/actions/class_teacher_save.php` — Assign homeroom teacher
- [ ] `modules/academics/actions/subject_teacher_save.php` — Assign subject teacher
- [ ] `modules/academics/actions/subject_teacher_delete.php` — Remove subject teacher assignment
- [ ] `modules/academics/actions/promote_save.php` — Process student promotions (transaction)
- [ ] `modules/academics/actions/timetable_save.php` — Save timetable entries

### Routes
- [ ] `modules/academics/routes.php` — Academics routing (27 case statements)

---

## 9.8 Module: Students (14 routes)

### Views (10 views)
- [ ] `modules/students/views/index.php` — Student list (paginated, filterable, searchable)
- [ ] `modules/students/views/create.php` — Student admission form (personal info, guardian, enrollment)
- [ ] `modules/students/views/edit.php` — Student edit form
- [ ] `modules/students/views/view.php` — Student profile detail view
- [ ] `modules/students/views/promote.php` — Student promotion interface
- [ ] `modules/students/views/roll_numbers.php` — Roll number assignment form
- [ ] `modules/students/views/details.php` — Extended student details view
- [ ] `modules/students/views/id_cards.php` — ID card generation/print
- [ ] `modules/students/views/credentials.php` — Generate login credentials
- [ ] `modules/students/views/reset_password.php` — Reset student password
- [ ] `modules/students/views/bulk_import.php` — CSV bulk import form

### Actions (9 actions)
- [ ] `modules/students/actions/store.php` — Create student (personal, guardian, enrollment, photo)
- [ ] `modules/students/actions/update.php` — Update student record
- [ ] `modules/students/actions/delete.php` — Soft-delete student
- [ ] `modules/students/actions/promote.php` — Process student promotion
- [ ] `modules/students/actions/save_roll_numbers.php` — Bulk save roll numbers
- [ ] `modules/students/actions/generate_credentials.php` — Create user account for student
- [ ] `modules/students/actions/reset_student_password.php` — Reset student's password
- [ ] `modules/students/actions/bulk_import.php` — Process CSV import
- [ ] `modules/students/actions/sample_csv.php` — Download sample CSV template
- [ ] `modules/students/actions/enroll.php` — Create enrollment record
- [ ] `modules/students/actions/export.php` — Export students to CSV

### Routes
- [ ] `modules/students/routes.php` — Students routing (14 case statements)

---

## 9.9 Module: Attendance (5 routes)

### Views (4 views)
- [ ] `modules/attendance/views/take.php` — Take attendance form (class/section/date grid)
- [ ] `modules/attendance/views/report.php` — Attendance report (date range, matrix view)
- [ ] `modules/attendance/views/view.php` — View daily attendance (read-only)
- [ ] `modules/attendance/views/student.php` — Individual student attendance history

### Actions (1 action)
- [ ] `modules/attendance/actions/save.php` — Save attendance records (upsert, transaction)

### Routes
- [ ] `modules/attendance/routes.php` — Attendance routing (5 case statements)

---

## 9.10 Module: Exams (19 routes + 2 AJAX)

### Views (14 views)
- [ ] `modules/exams/views/assignments.php` — Assignment list
- [ ] `modules/exams/views/assignment_form.php` — Create/edit assignment
- [ ] `modules/exams/views/assignment_view.php` — Assignment detail + submissions
- [ ] `modules/exams/views/exams.php` — Exam list
- [ ] `modules/exams/views/exam_schedule.php` — Exam schedule management
- [ ] `modules/exams/views/marks.php` — Marks entry grid
- [ ] `modules/exams/views/assessment_add.php` — Add assessment definition
- [ ] `modules/exams/views/enter_conduct.php` — Enter student conduct grades
- [ ] `modules/exams/views/enter_results.php` — Enter assessment results (6-step wizard)
- [ ] `modules/exams/views/roster.php` — Full class roster (all subjects × students)
- [ ] `modules/exams/views/result_cards.php` — Result cards view
- [ ] `modules/exams/views/result_analysis.php` — Statistical analysis (charts, distributions)
- [ ] `modules/exams/views/grade_scale.php` — Grade scale management
- [ ] `modules/exams/views/report_cards.php` — Report card list
- [ ] `modules/exams/views/report_card_print.php` — Printable A4 report card

### Actions (8 actions)
- [ ] `modules/exams/actions/assignment_save.php` — Create/update assignment
- [ ] `modules/exams/actions/assignment_delete.php` — Delete assignment
- [ ] `modules/exams/actions/exam_save.php` — Create/update exam
- [ ] `modules/exams/actions/exam_schedule_save.php` — Save exam schedule
- [ ] `modules/exams/actions/marks_save.php` — Save exam marks (upsert)
- [ ] `modules/exams/actions/assessment_save.php` — Save assessment definition
- [ ] `modules/exams/actions/assessment_delete.php` — Delete assessment
- [ ] `modules/exams/actions/conduct_save.php` — Save student conduct grades
- [ ] `modules/exams/actions/results_save.php` — Save assessment results (upsert)
- [ ] `modules/exams/actions/grade_scale_save.php` — Save grade scale entries
- [ ] `modules/exams/actions/report_card_generate.php` — Generate report cards (calculate ranks, averages)

### Routes
- [ ] `modules/exams/routes.php` — Exams routing (21 case statements)

---

## 9.11 Module: Finance (36 routes)

### Views — New Fee Management (10 views)
- [ ] `modules/finance/views/fm_dashboard.php` — Fee management dashboard (stats, summary)
- [ ] `modules/finance/views/fm_fee_form.php` — Create/edit fee definition
- [ ] `modules/finance/views/fm_manage_fees.php` — Fee list with actions (toggle, duplicate, delete)
- [ ] `modules/finance/views/fm_fee_view.php` — Fee detail view (assignments, charges)
- [ ] `modules/finance/views/fm_assign_fees.php` — Assign fees to students/groups
- [ ] `modules/finance/views/fm_groups.php` — Student group list
- [ ] `modules/finance/views/fm_group_form.php` — Create/edit student group
- [ ] `modules/finance/views/fm_group_members.php` — Manage group member list
- [ ] `modules/finance/views/fm_reports.php` — Fee reports dashboard
- [ ] `modules/finance/views/fm_payment.php` — Record payment against fee charges
- [ ] `modules/finance/views/fm_generate_invoice.php` — Generate professional invoice from charges

### Actions — New Fee Management (13 actions)
- [ ] `modules/finance/actions/fm_fee_save.php` — Create/update fee definition
- [ ] `modules/finance/actions/fm_fee_toggle.php` — Activate/deactivate fee
- [ ] `modules/finance/actions/fm_fee_delete.php` — Delete fee definition
- [ ] `modules/finance/actions/fm_fee_duplicate.php` — Duplicate fee definition
- [ ] `modules/finance/actions/fm_assignment_save.php` — Save fee assignment (student or group)
- [ ] `modules/finance/actions/fm_assignment_delete.php` — Delete fee assignment
- [ ] `modules/finance/actions/fm_exemption_save.php` — Save fee exemption
- [ ] `modules/finance/actions/fm_exemption_delete.php` — Delete fee exemption
- [ ] `modules/finance/actions/fm_group_save.php` — Create/update student group
- [ ] `modules/finance/actions/fm_group_delete.php` — Delete student group
- [ ] `modules/finance/actions/fm_group_member_add.php` — Add member to group
- [ ] `modules/finance/actions/fm_group_member_remove.php` — Remove member from group
- [ ] `modules/finance/actions/fm_report_export.php` — Export fee report CSV
- [ ] `modules/finance/actions/fm_api_students.php` — AJAX: search students for assignment
- [ ] `modules/finance/actions/fm_api_fee_students.php` — AJAX: fee-student lookup
- [ ] `modules/finance/actions/fm_charge_waive.php` — Waive student fee charge
- [ ] `modules/finance/actions/fm_payment_save.php` — Record payment against charges

### Views — Legacy Finance (9 views)
- [ ] `modules/finance/views/fee_categories.php` — Fee category CRUD table
- [ ] `modules/finance/views/fee_structures.php` — Fee structure list
- [ ] `modules/finance/views/fee_structure_form.php` — Create/edit fee structure
- [ ] `modules/finance/views/invoices.php` — Invoice list (filterable, paginated)
- [ ] `modules/finance/views/invoice_form.php` — Generate invoice form
- [ ] `modules/finance/views/invoice_view.php` — Invoice detail view
- [ ] `modules/finance/views/invoice_print.php` — Printable A4 invoice
- [ ] `modules/finance/views/payments.php` — Payment list
- [ ] `modules/finance/views/payment_form.php` — Record manual payment form
- [ ] `modules/finance/views/payment_receipt.php` — Printable A4 payment receipt
- [ ] `modules/finance/views/discounts.php` — Discount list
- [ ] `modules/finance/views/discount_form.php` — Create/edit discount
- [ ] `modules/finance/views/pay_online.php` — Online payment page (student/parent)
- [ ] `modules/finance/views/fee_report.php` — Financial reports dashboard

### Actions — Legacy Finance (8 actions)
- [ ] `modules/finance/actions/fee_category_save.php` — Create/update fee category
- [ ] `modules/finance/actions/fee_category_delete.php` — Delete fee category
- [ ] `modules/finance/actions/fee_structure_save.php` — Create/update fee structure
- [ ] `modules/finance/actions/fee_structure_delete.php` — Delete fee structure
- [ ] `modules/finance/actions/invoice_generate.php` — Generate invoice from fee structure
- [ ] `modules/finance/actions/invoice_delete.php` — Delete/cancel invoice
- [ ] `modules/finance/actions/payment_save.php` — Record manual payment
- [ ] `modules/finance/actions/discount_save.php` — Create/update discount
- [ ] `modules/finance/actions/discount_delete.php` — Delete discount
- [ ] `modules/finance/actions/payment_initiate.php` — Initialize online payment (Telebirr/Chapa)
- [ ] `modules/finance/actions/payment_callback.php` — Handle payment gateway callback/webhook

### Routes
- [ ] `modules/finance/routes.php` — Finance routing (36 case statements)

### Cron Jobs
- [ ] `cron/fm_recurrence_job.php` — Generate recurring fee charges
- [ ] `cron/fm_penalty_job.php` — Calculate and apply late payment penalties

---

## 9.12 Module: Communication (11 routes)

### Views (8 views)
- [ ] `modules/communication/views/announcements.php` — Announcement list
- [ ] `modules/communication/views/announcement_form.php` — Create/edit announcement
- [ ] `modules/communication/views/announcement_view.php` — Announcement detail
- [ ] `modules/communication/views/inbox.php` — Message inbox
- [ ] `modules/communication/views/compose.php` — Compose new message
- [ ] `modules/communication/views/message_view.php` — View message with thread
- [ ] `modules/communication/views/sent.php` — Sent messages
- [ ] `modules/communication/views/notifications.php` — Notification list

### Actions (5 actions)
- [ ] `modules/communication/actions/announcement_save.php` — Create/update announcement
- [ ] `modules/communication/actions/announcement_delete.php` — Delete announcement
- [ ] `modules/communication/actions/message_send.php` — Send message + create notification
- [ ] `modules/communication/actions/notification_read.php` — Mark single notification as read
- [ ] `modules/communication/actions/notification_read_all.php` — Mark all notifications as read

### Routes
- [ ] `modules/communication/routes.php` — Communication routing (11 case statements)

---

## 9.13 Module: Users (6 routes)

### Views (4 views)
- [ ] `modules/users/views/index.php` — User list (filterable, paginated)
- [ ] `modules/users/views/create.php` — Create user form (with role checkboxes)
- [ ] `modules/users/views/edit.php` — Edit user form
- [ ] `modules/users/views/view.php` — User detail + audit log

### Actions (4 actions)
- [ ] `modules/users/actions/store.php` — Create user + assign roles
- [ ] `modules/users/actions/update.php` — Update user + reassign roles
- [ ] `modules/users/actions/delete.php` — Soft-delete user
- [ ] `modules/users/actions/toggle_status.php` — Toggle active/inactive status

### Routes
- [ ] `modules/users/routes.php` — Users routing (6 case statements)

---

## 9.14 Module: Settings (4 routes)

### Views (3 views)
- [ ] `modules/settings/views/general.php` — General settings form (auto-generated from DB)
- [ ] `modules/settings/views/audit_logs.php` — Audit log browser (filterable, paginated)
- [ ] `modules/settings/views/backup.php` — Database backup/restore interface

### Actions (1 action)
- [ ] `modules/settings/actions/general_save.php` — Save settings values

### Routes
- [ ] `modules/settings/routes.php` — Settings routing (4 case statements)

---

## 9.15 Module: API (2 routes)

### Routes
- [ ] `modules/api/routes.php` — JSON API routing (sections lookup, subjects lookup)

---

## 9.16 Documentation Files

- [ ] `README.md` — Project readme (setup instructions)
- [ ] `docs/PHASE1_ARCHITECTURE.md` — Architecture overview
- [ ] `docs/DEPLOYMENT.md` — Server deployment guide
- [ ] `docs/CPANEL_DEPLOYMENT.md` — cPanel-specific deployment
- [ ] `docs/SECURITY_CHECKLIST.md` — Security review checklist
- [ ] `docs/PART1_PROJECT_OVERVIEW_AND_ARCHITECTURE.md` — Pre-dev documentation Part 1
- [ ] `docs/PART2_FILE_STRUCTURE_AND_VARIABLES.md` — Pre-dev documentation Part 2
- [ ] `docs/PART3_REQUIREMENTS_AND_DATABASE.md` — Pre-dev documentation Part 3
- [ ] `docs/PART4_API_AND_DEVELOPMENT_CHECKLIST.md` — Pre-dev documentation Part 4
- [ ] `docs/PART5_DEVELOPMENT_PLAN_AND_STANDARDS.md` — Pre-dev documentation Part 5

---

## 9.17 Sample Data & Uploads

- [ ] `sampleCSV/students_import_sample.csv` — Student CSV import template
- [ ] `storage/backups/` — Backup storage directory
- [ ] `storage/uploads/` — File upload storage directory
- [ ] `uploads/students/` — Student photo storage (organized by year)

---

## 9.18 Summary Statistics

| Category | Count |
|----------|-------|
| Configuration files | 3 (+ .env) |
| Core library files | 14 |
| SQL schema/migration files | 20 |
| Template files | 8 |
| Public entry points | 5 |
| Public asset files | 4+ |
| **Module: Auth** | 5 views, 5 actions, 1 routes |
| **Module: Dashboard** | 1 view, 0 actions, 1 routes |
| **Module: Academics** | 14 views, 16 actions, 1 routes |
| **Module: Students** | 11 views, 11 actions, 1 routes |
| **Module: Attendance** | 4 views, 1 action, 1 routes |
| **Module: Exams** | 15 views, 11 actions, 1 routes |
| **Module: Finance** | 24 views, 28 actions, 1 routes |
| **Module: Communication** | 8 views, 5 actions, 1 routes |
| **Module: Users** | 4 views, 4 actions, 1 routes |
| **Module: Settings** | 3 views, 1 action, 1 routes |
| **Module: API** | 0 views, 0 actions, 1 routes |
| **Total Views** | ~89 |
| **Total Actions** | ~82 |
| **Total Routes Files** | 11 |
| **Total Route Cases** | ~155 |
| **Database Tables** | 54 |
| **Documentation Files** | 10 |
| **Cron Jobs** | 2 |

---

**End of Part 4**

| Document | Contents |
|----------|----------|
| **PART 1** | Project Overview, System Architecture |
| **PART 2** | Complete File & Folder Structure, Variable Documentation |
| **PART 3** | Functional Requirements, Non-Functional Requirements, Database Design |
| **PART 4** (this file) | API Design, Development Checklist |
| **PART 5** | Development Plan, Coding Standards, Testing Strategy, Deployment Plan |
