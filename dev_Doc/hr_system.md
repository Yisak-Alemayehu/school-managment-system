# HR & Payroll System (HR Module)

This document provides a **complete, in-depth guide** to the HR & Payroll module in this SMS system.

It covers:
- database schema (tables & key relationships)
- all UI pages/routes & permissions
- detailed backend flow for each major feature
- payroll calculation logic (tax + pension)
- common use cases and troubleshooting tips

---

## 0. Routes (URLs) & Permissions

The HR module is routed via `modules/hr/routes.php`.

### 0.1 Main UI pages (GET)
- `hr?route=index` — HR Dashboard
- `hr?route=employees` — Employees list
- `hr?route=employee-detail&id=<id>` — Employee profile
- `hr?route=employee-form&id=<id?>` — Add/Edit employee
- `hr?route=departments` — Departments
- `hr?route=attendance` — Staff attendance
- `hr?route=attendance-report` — Attendance report
- `hr?route=leave-types` — Leave types
- `hr?route=holidays` — Holidays
- `hr?route=leave-requests` — Leave requests list
- `hr?route=leave-request-form` — Submit leave request
- `hr?route=leave-balances` — Leave balances per staff
- `hr?route=payroll` — Payroll periods list
- `hr?route=payroll-detail&id=<id>` — Payroll period detail
- `hr?route=payslip&id=<id>` — Payslip view
- `hr?route=payroll-bank-sheet&id=<id>` — Bank transfer sheet
- `hr?route=payroll-pension-sheet&id=<id>` — Pension report sheet
- `hr?route=reports` — HR reports menu
- `hr?route=reports-dashboard` — Reports dashboard
- `hr?route=payroll-printing` — Payroll printing hub
- `hr?route=devices` — Biometric devices

### 0.2 Actions (POST)
- `hr?route=employee-save` — create/update employee
- `hr?route=employee-delete` — delete employee
- `hr?route=employee-document-save` — upload employee document
- `hr?route=employee-document-delete` — delete employee document
- `hr?route=allowance-save` — save employee allowance
- `hr?route=allowance-delete` — delete allowance
- `hr?route=department-save` — save department
- `hr?route=department-delete` — delete department
- `hr?route=attendance-mark` — record attendance
- `hr?route=attendance-mark-absent` — bulk mark absent
- `hr?route=attendance-process-biometric` — process biometric data
- `hr?route=leave-type-save` — save leave type
- `hr?route=holiday-save` — save holiday
- `hr?route=leave-request-save` — submit/update leave request
- `hr?route=leave-approve` — approve/reject leave request
- `hr?route=payroll-period-save` — create/update payroll period
- `hr?route=payroll-generate` — generate payroll records
- `hr?route=payroll-approve` — approve payroll
- `hr?route=device-save` — register/edit biometric device
- `hr?route=device-sync` — sync biometric device data

### 0.3 PDF / Print actions
- `hr?route=print-tax&id=<id>` — generate income tax PDF
- `hr?route=print-pension&id=<id>` — generate pension report PDF
- `hr?route=print-bank&id=<id>` — bank transfer sheet PDF
- `hr?route=print-contract&id=<id>` — employment contract PDF
- `hr?route=print-payslip&id=<id>` — payslip PDF
- `hr?route=download-tax&id=<id>` — download PDF
- `hr?route=download-pension&id=<id>`
- `hr?route=download-bank&id=<id>`
- `hr?route=download-contract&id=<id>`
- `hr?route=download-payslip&id=<id>`

---

## 1. Database Schema (HR Tables)

### 1.1 Departments — `hr_departments`
Organizational units.

Key fields:
- `name`, `code`, `description`
- `head_of_department_id` (FK to `hr_employees`)
- `status` (`active`/`inactive`)

### 1.2 Employees — `hr_employees`
Core employee profile.

Key fields:
- `employee_id` (EMP-YYYY-XXXX format)
- `first_name`, `father_name`, `grandfather_name` (plus Amharic fields)
- `gender`, `date_of_birth_ec`, `date_of_birth_gregorian`
- `phone`, `email`, `address`
- `department_id`, `position`, `role`
- `employment_type` (permanent/contract/etc.)
- `start_date_ec`, `start_date_gregorian`, `end_date_*`
- salary fields: `basic_salary`, `transport_allowance`, `position_allowance`, `other_allowance`, `other_deductions`
- `status` (active/left/suspended)
- tax/pension fields: `tin_number`, `pension_number`
- bank details: `bank_name`, `bank_account`
- `biometric_id`, `fingerprint_registered`
- `user_id` (link to `users` table for login)

### 1.3 Employee Documents — `hr_employee_documents`
Store employee documents (contracts, IDs, certificates). Files uploaded to `storage/uploads/hr/`.

### 1.4 Leave Types — `hr_leave_types`
Types of leave (annual, sick, maternity).

Key fields:
- `name`, `code`, `days_allowed`, `status`

### 1.5 Holidays — `hr_holidays`
Public/school holidays (Gregorian + Ethiopian calendar).

### 1.6 Biometric Devices — `hr_attendance_devices`
Registered biometric devices used for attendance.

### 1.7 Employee Biometric Mapping — `hr_employee_biometric`
Maps employees to device user IDs and stores fingerprint hash.

### 1.8 HR Attendance — `hr_attendance`
Daily attendance records for staff.

Key fields:
- `employee_id`, `date_gregorian`, `date_ec`
- `check_in`, `check_out`
- `status` (`present`, `absent`, `late`, `half_day`, `leave`, `holiday`)
- `source` (`manual`, `biometric`, `mobile`)
- `device_id`, `sync_timestamp`, `notes`, `marked_by`

Unique constraint: (`employee_id`, `date_gregorian`)

### 1.9 HR Attendance Logs — `hr_attendance_logs`
Raw biometric device logs (pre-processed).

### 1.10 Leave Requests — `hr_leave_requests`
Employee leave applications.

Key fields:
- `employee_id`, `leave_type_id`
- start/end dates (Gregorian + Ethiopian calendar)
- `days`, `reason`, `attachment`
- `status` (`pending`, `approved`, `rejected`, `cancelled`)
- `approved_by`, `approval_date`, `rejection_reason`

### 1.11 Payroll Periods — `hr_payroll_periods`
Represents a payroll batch/month (Ethiopian + Gregorian).

Key fields:
- `month_ec`, `year_ec`, `month_gregorian`, `year_gregorian`
- `start_date`, `end_date` (gregorian)
- `status` (`draft`, `generated`, `approved`, `paid`)
- `generated_by`, `approved_by`, `generated_at`, `approved_at`

### 1.12 Payroll Records — `hr_payroll_records`
Individual payroll entries per employee per period.

Key fields include:
- `working_days`, `days_worked`
- `basic_salary`, `prorated_salary`, `gross_salary`, `net_salary`
- `income_tax`, `employee_pension`, `employer_pension` (pension calcs are fixed: 7%/11%)
- allowances/deductions
- `payment_status` (`pending`, `paid`, `cancelled`)
- `payment_method` (`bank_transfer`, `cash`)

### 1.13 Employee Allowances — `hr_employee_allowances`
Recurring allowances per employee.

Key fields:
- `allowance_type` (transport, housing, responsibility, etc.)
- `is_taxable`, `is_permanent`, `start_date`, `end_date`

---

## 2. Core HR Workflows

### 2.1 Employee Management

**Create / Edit employee**
- UI: `hr?route=employee-form`
- Action: `hr?route=employee-save` → `modules/hr/actions/employee_save.php`

**Key behavior:**
- Converts Ethiopian calendar dates to Gregorian using `core/ethiopian_calendar.php`.
- Validates unique email & TIN.
- Generates `employee_id` (format `EMP-YYYY-XXXX`) via `payroll_next_employee_id()`.
- Automatically generates a **contract PDF** using `core/pdf_contract.php` when a new employee is created.

**Delete employee**
- Action: `hr?route=employee-delete`.
- Soft delete via `deleted_at`.

**Employee documents**
- Upload documents: `hr?route=employee-document-save`
- Delete documents: `hr?route=employee-document-delete`

---

### 2.2 Departments

- View: `hr?route=departments`
- Create/update: `hr?route=department-save`
- Delete: `hr?route=department-delete`

Departments have `head_of_department_id` linking to an employee.

---

### 2.3 Attendance (Staff)

#### Manual attendance
- View: `hr?route=attendance`
- Save: `hr?route=attendance-mark` (action: `modules/hr/actions/attendance_mark.php`)

#### Biometric sync
- Sync device logs: `hr?route=attendance-process-biometric` (action: `biometric_process.php`)
- Mark absent: `hr?route=attendance-mark-absent` (action: `attendance_mark_absent.php`)

#### Reporting and status
- Attendance report: `hr?route=attendance-report`

---

### 2.4 Leave Management

#### Leave types and holidays
- Leave types: `hr?route=leave-types` (save action: `leave_type_save.php`)
- Holidays: `hr?route=holidays` (save action: `holiday_save.php`)

#### Leave requests
- Submit: `hr?route=leave-request-form`
- Save: `hr?route=leave-request-save`
- Approve/reject: `hr?route=leave-approve`

#### Leave balances
- Viewing: `hr?route=leave-balances`

---

### 2.5 Payroll

#### Payroll periods
- View: `hr?route=payroll`
- Save: `hr?route=payroll-period-save`
- Generate payroll: `hr?route=payroll-generate` (action: `payroll_generate.php`)
- Approve payroll: `hr?route=payroll-approve` (action: `payroll_approve.php`)

#### Payroll records
- View payroll details: `hr?route=payroll-detail&id=<id>`
- Pay slip: `hr?route=payslip&id=<id>`
- Bank transfer sheet: `hr?route=payroll-bank-sheet&id=<id>`
- Pension report: `hr?route=payroll-pension-sheet&id=<id>`

#### Payroll printing (bulk)
- `hr?route=payroll-printing`

---

### 2.6 Reports

- HR reports dashboard: `hr?route=reports-dashboard`
- Reports list: `hr?route=reports`

---

### 2.7 Devices & Biometric Integration

- Device list: `hr?route=devices`
- Add/edit device: `hr?route=device-save`
- Sync device logs: `hr?route=device-sync`

---

## 3. Payroll Calculation Logic

All payroll math is implemented in `core/payroll.php`.

### 3.1 Income Tax (Ethiopian formula)
- Uses fixed brackets:
  - 0–2000: 0%
  - 2001–4000: 15% - 300
  - 4001–7000: 20% - 500.5
  - 7001–10000: 25% - 850.5
  - 10001–14000: 30% - 1350
  - Above 14000: 35% - 2050

### 3.2 Pension
- Employee pension: 7% of basic salary
- Employer pension: 11% of basic salary
- Total pension: 18% of basic salary

### 3.3 Prorating salary
- Uses `payroll_prorate_salary()` based on working days and days worked.

### 3.4 Full payroll calculation
- Gross = prorated salary + allowances + overtime
- Taxable income = gross
- Income tax = calculated via formula
- Total deductions = tax + employee pension + other deductions
- Net salary = gross - total deductions

### 3.5 Generating payroll records (action)
- `modules/hr/actions/payroll_generate.php` uses the above functions and stores into `hr_payroll_records`.
- It considers:
  - active employees within period
  - pro-rating for join/leave dates
  - recurring allowances from `hr_employee_allowances`

---

## 4. Common Use Cases

### 4.1 Add a new employee
1. Go to **HR → Employees**.
2. Click **Add Employee**, fill details, upload photo.
3. Save (system generates employee ID and contract PDF).

### 4.2 Record attendance manually
1. Go to **HR → Attendance**.
2. Select employee(s) and date.
3. Mark in/out and status.
4. Save.

### 4.3 Approve a leave request
1. Go to **HR → Leave Requests**.
2. Review request.
3. Approve/reject (action: `leave_approve.php`).

### 4.4 Run payroll
1. Create payroll period under **Payroll**.
2. Click **Generate Payroll** (creates records).
3. Review payroll details.
4. Approve payroll (sets status and allows printing).

---

## 5. Troubleshooting & Notes

### 5.1 Payroll records not generated
- Ensure payroll period is in `draft` status.
- Confirm employees are active and within date range.

### 5.2 Attendance not saving
- Check for unique constraint on `hr_attendance` (employee + date).
- Verify date is not in the future.

### 5.3 Leave balance not updating
- Leave balances are derived from `hr_leave_requests` and `hr_leave_types`.

---

## 6. Where to Extend / Customize

### 6.1 Add overtime calculation rules
- Extend `payroll_calculate()` to include overtime rates.

### 6.2 Add leave balance automation
- Add cron job to calculate remaining leave days based on requests and leave type allowance.

### 6.3 Biometric sync enhancement
- Improve `biometric_process.php` to map raw device logs into `hr_attendance` with smarter in/out pairing.

---

**End of HR & Payroll System documentation.**
