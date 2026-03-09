# Urji Beri School Management System — User Guide

**Version:** 1.0.0  
**Last Updated:** March 2026  
**Platform:** Web-based (PWA-enabled)  

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Getting Started](#2-getting-started)
3. [Dashboard](#3-dashboard)
4. [Academics Module](#4-academics-module)
5. [Students Module](#5-students-module)
6. [Attendance Module](#6-attendance-module)
7. [Exams & Results Module](#7-exams--results-module)
8. [Finance Module](#8-finance-module)
9. [HR Management Module](#9-hr-management-module)
10. [Messaging Module](#10-messaging-module)
11. [Communication Module](#11-communication-module)
12. [Users Module](#12-users-module)
13. [Settings Module](#13-settings-module)
14. [Role-Based Access Summary](#14-role-based-access-summary)
15. [Tips & Best Practices](#15-tips--best-practices)
16. [FAQ & Troubleshooting](#16-faq--troubleshooting)

---

## 1. Introduction

### 1.1 What is Urji Beri SMS?

Urji Beri School Management System (SMS) is a comprehensive web-based platform designed for Ethiopian K-12 schools. It digitizes and streamlines every aspect of school administration including:

- Student admission and lifecycle management
- Academic setup (sessions, terms, classes, subjects)
- Daily attendance tracking
- Exam management and report card generation
- Fee collection and financial reporting
- Human resources management (employees, payroll, leave)
- Internal messaging and announcements
- Role-based access control for secure operations

### 1.2 Key Features

| Feature | Description |
|---------|-------------|
| **Ethiopian Calendar** | Full support for EC dates alongside Gregorian |
| **Ethiopian Grading** | Standard A+ to F grade scale (4.0 GPA) |
| **Bilingual Interface** | English and Amharic (አማርኛ) |
| **Dark/Light Mode** | Theme toggle for visual comfort |
| **PWA Support** | Installable as a mobile/desktop app with offline capabilities |
| **PDF Generation** | Report cards, payslips, tax sheets, bank sheets, contracts |
| **QR Codes** | QR-verified report cards |
| **Multiple Payment Gateways** | Telebirr, Chapa integration |
| **Currency** | Ethiopian Birr (ETB / Br) |

### 1.3 System Roles

The system supports **7 user roles**, each with specific permissions:

| Role | Description |
|------|-------------|
| **Super Admin** | Unrestricted access to all modules and settings |
| **School Admin** | Full academic and staff management (cannot manage roles) |
| **Teacher** | Manage assigned classes — attendance, marks, assignments |
| **Student** | View own grades, attendance, fees, and messaging |
| **Parent** | View child's information, fees, grades |
| **Accountant** | Finance and payment management |
| **Registrar** | Student records management |

### 1.4 Supported Browsers

- Google Chrome (recommended)
- Mozilla Firefox
- Microsoft Edge
- Safari (iOS)
- Any modern browser with JavaScript enabled

---

## 2. Getting Started

### 2.1 Accessing the System

Open your web browser and navigate to the school's URL (e.g., `https://yourschool.com`). You will see the login page.

### 2.2 Logging In

1. Enter your **Username** or **Email** in the first field
2. Enter your **Password** in the second field
3. Click the **Sign In** button

> **Default Credentials (first-time setup):**
> - Admin: `admin` / `Admin@123`
> - Teachers: `teacher1` through `teacher12` / `Teacher@123`

> ⚠️ **Important:** Change default passwords immediately after first login.

### 2.3 Forgot Password

1. On the login page, click **"Forgot Password?"**
2. Enter your registered **email address**
3. Follow the instructions to reset your password

### 2.4 Changing Your Password

1. Click your **profile name** in the top-right corner
2. Select **"Change Password"**
3. Enter your **Current Password**
4. Enter your **New Password** (minimum 8 characters)
5. **Confirm** the new password
6. Click **Save**

### 2.5 Updating Your Profile

1. Click your **profile name** → **"My Profile"**
2. You can update:
   - **Full Name**
   - **Email**
   - **Phone** (format: +251...)
   - **Profile Photo** (JPG, PNG, or WebP; max 2MB)
3. Click **Save Changes**

> Your **Username** is read-only and cannot be changed from the profile page.

### 2.6 Switching Language

Click the **language toggle** button (🌐) in the top-right header to switch between:
- **English** (EN)
- **Amharic** (አማ)

### 2.7 Switching Theme (Dark/Light Mode)

Click the **theme toggle** button (🌙/☀️) in the header to switch between dark and light modes. Your preference is saved automatically.

### 2.8 Installing as a Mobile App (PWA)

1. Open the system URL in Chrome (mobile or desktop)
2. Your browser will show an **"Install"** or **"Add to Home Screen"** prompt
3. Accept to install the app for quick access
4. The app works offline for basic navigation

### 2.9 Logging Out

Click your **profile name** → **"Logout"**. Your session is securely terminated.

> **Security Note:** Sessions automatically expire after **2 hours** of inactivity. After **5 failed login attempts**, your account is locked for **15 minutes**.

---

## 3. Dashboard

After logging in, you land on the **Dashboard** — a role-specific overview of key metrics and actions.

### 3.1 Super Admin / School Admin Dashboard

| Section | Information Displayed |
|---------|----------------------|
| **Statistics Cards** | Total users, students (male/female split), parents, employees, subjects, sections |
| **Admissions** | New admissions this month |
| **Attendance** | Today's attendance rate |
| **Exams** | Total exams and results entered |
| **Financial Summary** | Total fees assigned, collected, outstanding, penalties, collection rate % |
| **Recent Payments** | Last 5 payments (amount, channel, student, date) |
| **Student Distribution** | Students per class (chart) |
| **Employee Stats** | Gender breakdown |
| **Recent Activity** | Last 6 admitted students, top 5 classes by attendance, recent announcements |

### 3.2 Teacher Dashboard

- Total assigned classes and sections
- Today's attendance/absence counts for assigned classes
- Upcoming exams
- Recent announcements

### 3.3 Student Dashboard

- Own attendance performance
- Recent exam results
- Fee/payment status
- Announcements

### 3.4 Parent Dashboard

- Children's attendance and results
- Fee due information
- Announcements

### 3.5 Accountant Dashboard

- Financial summary
- Pending payments
- Fee collection rates
- Outstanding balances

---

## 4. Academics Module

**Access:** Super Admin, School Admin (full) | Teacher, Student, Parent (limited view)

The Academics module manages all academic structure and configuration.

### 4.1 Academic Sessions

**Navigation:** Sidebar → Academics → Sessions

Academic sessions represent the school year (e.g., 2025/2026).

**To create a session:**
1. Click **"Add Session"**
2. Enter Session **Name** (e.g., "2025/2026")
3. Set **Start Date** and **End Date**
4. Click **Save**

**To activate a session:**
- Click the **Toggle** button next to the session name
- Only **one session** can be active at a time
- The active session is used across all modules

### 4.2 Terms / Semesters

**Navigation:** Sidebar → Academics → Terms

Terms divide the academic year into periods (e.g., Term 1, Term 2, Term 3, Term 4).

**To create a term:**
1. Click **"Add Term"**
2. Enter Term **Name** (e.g., "Term 1")
3. Select the parent **Session**
4. Set **Start Date** and **End Date**
5. Click **Save**

**To set a term as active:**
- Click the **Activate** button — the active term is used for attendance, exams, etc.

### 4.3 Classes (Grade Levels)

**Navigation:** Sidebar → Academics → Classes

Classes represent grade levels (Grade 1 through Grade 12).

**To create a class:**
1. Click **"Add Class"**
2. Enter **Class Name** (e.g., "Grade 5")
3. Set **Numeric Order** for sorting
4. Optionally assign a **Stream** (Natural Science / Social Science)
5. Click **Save**

### 4.4 Sections

**Navigation:** Sidebar → Academics → Sections

Sections divide classes (e.g., Grade 5-A, Grade 5-B).

**To create a section:**
1. Click **"Add Section"**
2. Select the parent **Class**
3. Enter **Section Name** (e.g., "A")
4. Set **Capacity** (e.g., 40 students)
5. Click **Save**

### 4.5 Subjects

**Navigation:** Sidebar → Academics → Subjects

**To create a subject:**
1. Click **"Add Subject"**
2. Enter **Subject Name** (e.g., "Mathematics")
3. Enter **Subject Code** (e.g., "MATH")
4. Select **Subject Type** (Theory / Practical / Both)
5. Click **Save**

### 4.6 Mediums

**Navigation:** Sidebar → Academics → Mediums

Mediums are the language of instruction (e.g., English, Afan Oromo, Amharic).

**To add a medium:**
1. Click **"Add Medium"**
2. Enter the **Medium Name**
3. Click **Save**

### 4.7 Streams

**Navigation:** Sidebar → Academics → Streams

Streams are academic tracks used in upper grades (e.g., Natural Science, Social Science).

**To add a stream:**
1. Click **"Add Stream"**
2. Enter the **Stream Name**
3. Click **Save**

### 4.8 Shifts

**Navigation:** Sidebar → Academics → Shifts

Shifts define when classes operate (e.g., Morning, Afternoon, Full Day).

**To add a shift:**
1. Click **"Add Shift"**
2. Enter the **Shift Name**
3. Set **Start Time** and **End Time**
4. Click **Save**

### 4.9 Class-Subject Mapping

**Navigation:** Sidebar → Academics → Class Subjects

This feature links subjects to specific classes and defines how many subjects each grade studies.

**To assign subjects to a class:**
1. Select a **Class** from the dropdown
2. Check the subjects you want to assign to that class
3. Click **Save**

### 4.10 Elective Subjects

**Navigation:** Sidebar → Academics → Elective Subjects

Configure optional subjects that students can choose.

**To manage electives:**
1. Select a **Class**
2. Mark specific subjects as **Elective**
3. Click **Save**

### 4.11 Class Teachers

**Navigation:** Sidebar → Academics → Class Teachers

Assign a teacher as the homeroom/class teacher for each section.

**To assign a class teacher:**
1. Select a **Class** and **Section**
2. Select a **Teacher** from the dropdown
3. Click **Save**

### 4.12 Subject Teachers

**Navigation:** Sidebar → Academics → Subject Teachers

Assign teachers to teach specific subjects in specific classes.

**To assign a subject teacher:**
1. Select the **Class**
2. Select the **Subject**
3. Select the **Teacher**
4. Click **Save**

**To remove a teacher assignment:**
- Click the **Delete** (🗑️) icon next to the assignment

> **Note:** Teachers can only access attendance, marks, and student data for classes/subjects they are assigned to.

### 4.13 Timetable

**Navigation:** Sidebar → Academics → Timetable

Create and manage a weekly class timetable.

**To set up a timetable:**
1. Select a **Class** and **Section**
2. For each day and period, select a **Subject** and **Teacher**
3. Click **Save**

**Student/Parent View:**
- Students and parents can view their timetable at **Academics → My Timetable**

### 4.14 Student Promotion

**Navigation:** Sidebar → Academics → Promote Students

Promote students from one class to the next at the end of the academic year.

**To promote students:**
1. Select the **Source Class** (current)
2. Select the **Destination Class** (next year)
3. Select students to promote (check boxes)
4. Click **Promote**

> ⚠️ Ensure a new academic session exists before promotion.

### 4.15 My Subjects (Student/Parent View)

**Navigation:** Sidebar → Academics → My Subjects

Students and parents can view the list of enrolled subjects for the current session.

### 4.16 My Timetable (Student/Parent View)

**Navigation:** Sidebar → Academics → My Timetable

View the weekly class schedule with subjects, teachers, and time periods.

---

## 5. Students Module

**Access:** Super Admin, School Admin, Registrar (full) | Teacher, Parent (view only)

### 5.1 Student List

**Navigation:** Sidebar → Students → Student List

The student list displays all enrolled students with powerful filtering:

**Filters:**
- **Search:** by name, admission number, or phone
- **Class:** dropdown to filter by grade level
- **Section:** dynamic dropdown based on selected class
- **Status:** Active, Graduated, Transferred, Withdrawn

**Table Columns:**
- Student photo/avatar, Name
- Admission Number
- Class & Section
- Gender
- Status
- Actions (View, Edit, Delete)

**Top Action Buttons:**
- **New Admission** — register a new student
- **Export** — download student list

### 5.2 Student Admission (New Student)

**Navigation:** Sidebar → Students → New Admission (or click "New Admission" button)

**Step 1 — Personal Information:**
| Field | Required | Notes |
|-------|----------|-------|
| First Name | ✅ | |
| Last Name | ✅ | |
| Gender | ✅ | Male / Female |
| Date of Birth | ✅ | Date picker |
| Blood Group | ❌ | A+, A-, B+, B-, AB+, AB-, O+, O- |
| Religion | ❌ | |
| Phone | ✅ | +251... format |
| Email | ❌ | |
| Medical Conditions | ❌ | Text field |

**Step 2 — Photo Upload:**
- Supported formats: JPEG, PNG
- Maximum size: 2MB
- This photo appears on the student ID card

**Step 3 — Address Information:**
| Field | Required |
|-------|----------|
| Country | ✅ (default: Ethiopia) |
| Region | ✅ |
| City | ✅ |
| Sub-city | ✅ |

**Step 4 — Guardian Information:**
- Add at least one guardian
- Guardian name, relationship, contact info

**Step 5 — Enrollment:**
- Select **Class** and **Section**
- System generates an **Admission Number** automatically

Click **Submit** to complete the admission.

### 5.3 View Student Profile

Click the **View** (👁️) icon on any student row.

The profile page shows:
- Personal information and photo
- Enrollment history
- Guardian details
- Attendance summary
- Exam results
- Fee/payment history

### 5.4 Edit Student

Click the **Edit** (✏️) icon on any student row to update any student information.

### 5.5 Delete Student

Click the **Delete** (🗑️) icon. A confirmation dialog appears before the student record is removed.

> ⚠️ Deletion is a soft delete — records are marked inactive, not permanently removed.

### 5.6 Bulk Import (CSV)

**Navigation:** Sidebar → Students → Add Bulk Data

Import multiple students at once from a CSV file.

**Steps:**
1. Click **"Download Sample CSV"** to get the template
2. Fill in the CSV with student data (follow the column headers exactly)
3. Click **"Choose File"** and select your filled CSV
4. Click **"Import"**
5. Review any errors and correct the CSV if needed

**Sample CSV columns:** First Name, Last Name, Gender, Date of Birth, Phone, Email, Guardian Name, Guardian Phone, Class, Section, etc.

### 5.7 Assign Roll Numbers

**Navigation:** Sidebar → Students → Assign Roll Numbers

1. Select a **Class** and **Section**
2. Students appear in a list
3. Roll numbers are auto-assigned in alphabetical order — or you can manually edit
4. Click **Save**

### 5.8 Generate Student ID Cards

**Navigation:** Sidebar → Students → ID Cards

1. Select a **Class** and optionally a **Section**
2. Select students (or "Select All")
3. Click **Generate**
4. Printable ID cards are generated (A4 format, multiple per page)
5. Use the browser's **Print** function (Ctrl+P) to print

### 5.9 Generate Login Credentials

**Navigation:** Sidebar → Students → Generate Credentials

Generate username and password for students so they can log in to the system.

1. Select a **Class** and **Section**
2. Click **Generate**
3. Credentials are created (username based on admission number)
4. Download or print the credentials list for distribution

### 5.10 Reset Student Password

**Navigation:** Sidebar → Students → Reset Password

1. Search for a student by name or admission number
2. Click **Reset**
3. A new temporary password is generated for the student

### 5.11 Export Students

Click the **Export** button on the Student List page to download all student data in a spreadsheet format.

### 5.12 Student Promotion

See [Section 4.14 — Student Promotion](#414-student-promotion).

---

## 6. Attendance Module

**Access:** Super Admin, School Admin (full management) | Teacher (assigned classes only) | Student, Parent (view only)

### 6.1 Take Attendance

**Navigation:** Sidebar → Attendance → Take Attendance

**Steps:**
1. Select a **Class** from the dropdown
2. Select a **Section** (optional)
3. Select the **Date** (defaults to today; cannot select future dates)
4. Click **Load**

**Marking Attendance:**

The student list appears with these status options per student:

| Status | Icon | Color | Meaning |
|--------|------|-------|---------|
| **P** (Present) | ✓ | Green | Student is present |
| **A** (Absent) | ✗ | Red | Student is absent |
| **L** (Late) | ⏰ | Yellow | Student arrived late |
| **E** (Excused) | 📋 | Blue | Absence is excused |

**Quick Actions:**
- **All Present** — marks every student as Present
- **All Absent** — marks every student as Absent

**Optional:** Add **Remarks** for individual students in the text field (e.g., "Sick leave note provided").

Click **Save Attendance** when done.

> **Note for Teachers:** You can only mark attendance for classes and sections assigned to you.

### 6.2 View Attendance

**Navigation:** Sidebar → Attendance → View Attendance

View attendance records for a specific class, section, and date.

1. Select **Class**, **Section**, and **Date**
2. Click **View**
3. See the attendance summary (total present, absent, late, excused)

### 6.3 Attendance Report

**Navigation:** Sidebar → Attendance → Attendance Report

Generate attendance reports with these filters:
- **Class** and **Section**
- **Date Range** (from — to)
- **Status** filter (show only absences, etc.)

The report shows:
- Daily attendance totals
- Student-wise attendance percentages
- Trends over the selected period

### 6.4 Student Attendance (Individual History)

**Navigation:** Sidebar → Attendance → Student Attendance (or via student profile)

View an individual student's full attendance history:
- Total days present, absent, late, excused
- Attendance percentage
- Daily breakdown by date

**For Students/Parents:**
- Access via **Attendance → My Attendance** or **Attendance Performance**
- See your own (or child's) attendance summary and history

---

## 7. Exams & Results Module

**Access:** Super Admin, School Admin (full) | Teacher (assigned classes) | Student, Parent (view own)

### 7.1 Assignments

**Navigation:** Sidebar → Results → Assignments

Teachers can create and manage assignments for their classes.

**To create an assignment:**
1. Click **"Create Assignment"**
2. Fill in:
   - **Title** (e.g., "Chapter 5 Homework")
   - **Class** and **Section**
   - **Subject**
   - **Due Date**
   - **Description** and **Instructions**
3. Click **Save**

**To view/delete:**
- Click **View** to see assignment details
- Click **Delete** to remove an assignment

### 7.2 Exams

**Navigation:** Sidebar → Results → Exams

**To create an exam:**
1. Enter **Exam Name** (e.g., "Mid-Term Exam — Term 3")
2. Set **Start Date** and **End Date**
3. Click **Save**

**Exam Schedule:**
1. Select an existing **Exam**
2. Assign dates and times for each subject
3. Click **Save Schedule**

### 7.3 Assessments

**Navigation:** Sidebar → Results → Add Assessment

Assessments are graded evaluations (quizzes, midterms, finals) linked to terms.

**To create an assessment:**
1. Select the **Term**
2. Select the **Class**
3. Select the **Subject**
4. Enter **Assessment Name** (e.g., "Midterm Exam")
5. Set **Maximum Marks** (up to 100)
6. Click **Save**

### 7.4 Enter Marks / Results

**Navigation:** Sidebar → Results → Enter Results

**Steps:**
1. Select an **Assessment** (or Exam)
2. Select the **Class** and **Subject**
3. Click **Load**
4. Enter marks for each student in the marks field (0–100)
5. Mark students as **Absent** if applicable
6. Click **Save Results**

> **Teachers** can only enter marks for their assigned classes and subjects.

### 7.5 Enter Conduct Grades

**Navigation:** Sidebar → Results → Enter Conduct

1. Select the **Class** and **Section**
2. Select the **Term**
3. For each student, enter conduct marks or grades
4. Click **Save**

### 7.6 Grade Scale

**Navigation:** Sidebar → Results → Grade Scale

View or configure the grading scale:

| Grade | Min Mark | Max Mark | Grade Point | Description |
|-------|----------|----------|-------------|-------------|
| A+ | 95 | 100 | 4.00 | Excellent |
| A | 85 | 94 | 4.00 | Very Good |
| B+ | 75 | 84 | 3.50 | Good |
| B | 65 | 74 | 3.00 | Satisfactory |
| C+ | 55 | 64 | 2.50 | Fair |
| C | 45 | 54 | 2.00 | Adequate |
| D | 35 | 44 | 1.00 | Below Average |
| F | 0 | 34 | 0.00 | Fail |

**To modify the grade scale:**
1. Edit the min/max marks, grade point, or description
2. Click **Save**

### 7.7 Generate Roster

**Navigation:** Sidebar → Results → Generate Roster

The roster is a summary of all student results for a class.

1. Select the **Exam** or **Term**
2. Select the **Class** and **Section**
3. Click **View**

The roster displays:
- All students with their marks per subject
- Total marks, average, grade, rank
- Pass/Fail status

### 7.8 Report Cards

**Navigation:** Sidebar → Results → Report Cards

**To generate report cards:**
1. Select the **Exam** or **Term**
2. Select the **Class** and optionally **Section**
3. Click **View** to see the results table
4. Click **"Generate All"** to batch-generate report cards
5. Or click **View** on individual students to see their report card

**Report Card Features:**
- **A4 printable format** — designed for standard printing
- **QR Code** — each report card contains a QR code for verification
- School name, logo, and details
- Student information (name, class, section, roll number)
- Subject-wise marks, grades, and grade points
- Total, average, rank, and overall grade
- Conduct grades
- Teacher and principal signature areas

**To print a report card:**
1. Click **View** or **Print** on a student's report card
2. Use your browser's print function (**Ctrl+P** / **⌘+P**)
3. Select paper size: **A4**

### 7.9 Result Analysis

**Navigation:** Sidebar → Results → Result Analysis

View statistical analysis of exam results:
- **Class-wise performance** — average scores per class
- **Subject-wise analysis** — highest, lowest, average marks per subject
- **Pass/Fail rates**
- **Grade distribution** — how many students got each grade
- **Trend analysis** — performance across terms

**For Students/Parents:**
- Access via **Results → Result Analysis** to view your own (or child's) performance trends

---

## 8. Finance Module

**Access:** Super Admin, School Admin (full) | Accountant (full) | Student, Parent (view own)

### 8.1 Student Finance List

**Navigation:** Sidebar → Finance → Student List

Browse students for fee management:

**Filters:**
- Search by name, student code, email, or phone
- Filter by Class and Section
- Filter by Gender

**Table shows:**
- Student Code (clickable — opens fee detail)
- Student Name with avatar
- Class
- Gender
- Phone & Email

Click a **Student Code** to open their financial detail page.

### 8.2 Student Finance Detail

View all financial information for a specific student:
- **Assigned Fees** — list of all fees applied to this student
- **Payment History** — all payments made
- **Outstanding Balance** — remaining amount due
- **Discounts** — any applied discounts

**Actions available:**
- **Assign Fee** — add a new fee to the student
- **Remove Fee** — remove an assigned fee
- **Adjust Balance** — make manual adjustments
- **Make Payment** — record a payment

### 8.3 Finance Groups

**Navigation:** Sidebar → Finance → Groups

Groups allow you to manage fees in bulk for categories of students.

**To create a group:**
1. Click **"Add Group"**
2. Enter **Group Name** (e.g., "Grade 5 — Full Fee Payers")
3. Click **Save**

**To manage group members:**
1. Click a group to view its details
2. Click **"Assign Members"** to add students
3. Select students and click **Add**
4. To remove members, click **Remove** next to their name

**Group Actions:**
- Assign fees to all group members at once
- Remove fees from all members
- Useful for bulk operations

### 8.4 Collect Payment

**Navigation:** Sidebar → Finance → Collect Payment

**Steps:**
1. Search and select a **Student**
2. Select the **Fee** to pay against
3. Enter the **Amount** (ETB)
4. Select **Payment Method**:
   - Cash
   - Bank Transfer
   - Telebirr
   - Chapa
   - Other
5. Optionally add a **Reference Number** and **Remarks**
6. Click **Save Payment**

**Payment Attachment:**
- Upload a receipt or proof of payment (image or PDF)

### 8.5 Payment History

**Navigation:** Sidebar → Finance → Payment History

View all school payment records with:
- Student name and code
- Amount paid (ETB)
- Payment method/channel
- Date of payment
- Reference numbers

### 8.6 Fees Due

**Navigation:** Sidebar → Finance → Fee Due

View all outstanding/unpaid fees across all students:
- Student name
- Fee description
- Amount due
- Due date

### 8.7 Supplementary Fees

**Navigation:** Sidebar → Finance → Supplementary Fees

Manage additional or one-time fees (e.g., field trip fees, special materials).

**To add a supplementary fee:**
1. Click **"Add Supplementary Fee"**
2. Enter fee details (name, amount, due date)
3. Assign to specific students or groups
4. Click **Save**

### 8.8 Finance Reports

**Navigation:** Sidebar → Finance → Reports

#### Student Info Report
- Generate per-student or per-class financial summaries

#### Penalty Report
- View late payment penalties

#### Supplementary Report
- Track supplementary fee collection

**Export Options:**
- **Export PDF** — formatted financial report
- **Export Excel** — spreadsheet with detailed data

---

## 9. HR Management Module

**Access:** Super Admin, School Admin (full) | Accountant (payroll view only)

### 9.1 HR Dashboard

**Navigation:** Sidebar → HR → Dashboard

Overview of:
- Total employees (active/inactive)
- Department breakdown
- Attendance summary
- Leave statistics
- Payroll status

### 9.2 Employees

**Navigation:** Sidebar → HR → Employees

**Employee List:**
- Search by name, employee ID, department
- Filter by department, status, gender
- View employee cards with photo, name, department, position

**To add an employee:**
1. Click **"Add Employee"**
2. Fill in the employee form:
   - **Personal:** Name, gender, date of birth, phone, email, photo
   - **Employment:** Employee ID, department, position, employment type, join date
   - **Salary:** Base salary, allowances
   - **Bank:** Bank name, account number
   - **TIN:** Tax Identification Number
   - **Pension:** Pension number
3. Click **Save**

**Employee Detail Page:**
- Personal information
- Employment details
- Salary and allowances
- Document management (upload/download contracts, certifications)
- Attendance history
- Leave history

**Managing Allowances:**
1. On the employee detail page, click **"Add Allowance"**
2. Enter allowance type and amount
3. Click **Save**

**Managing Documents:**
1. Click **"Add Document"**
2. Upload the document file
3. Enter document name/type
4. Click **Save**

### 9.3 Departments

**Navigation:** Sidebar → HR → Departments

**To add a department:**
1. Click **"Add Department"**
2. Enter **Department Name**
3. Optionally assign a **Head of Department**
4. Click **Save**

### 9.4 Employee Attendance

**Navigation:** Sidebar → HR → Attendance

**Mark Attendance:**
1. Select the **Date**
2. Mark each employee as Present, Absent, Late, or On Leave
3. Click **Save**

**Biometric Integration:**
- If biometric devices are configured, attendance can be automatically synced
- Click **"Process Biometric"** to import data from devices

**Mark All Absent:**
- Use to mark all unmarked employees as absent for a specific date

**Attendance Report:**
- Select a **Month** and **Year**
- View monthly attendance grid for all employees
- See total present days, absent days, late count

### 9.5 Leave Management

**Navigation:** Sidebar → HR → Leave

#### Leave Types
Set up leave categories:
1. Click **"Add Leave Type"**
2. Enter **Name** (e.g., "Annual Leave"), **Days Allowed** per year, **Description**
3. Click **Save**

#### Holidays
Define school holidays:
1. Click **"Add Holiday"**
2. Enter **Name**, **Date**, **Description**
3. Click **Save**

#### Submit Leave Request (Employee/Teacher)
1. Click **"Request Leave"**
2. Select **Leave Type**
3. Set **From Date** and **To Date**
4. Enter **Reason**
5. Click **Submit**

#### Approve/Reject Leave (Admin)
1. Go to **Leave Requests**
2. Review pending requests
3. Click **Approve** or **Reject**
4. Add an optional **Comment**

#### Leave Balances
View each employee's remaining leave days per leave type.

### 9.6 Payroll

**Navigation:** Sidebar → HR → Payroll

#### Payroll Periods
1. Click **"Add Payroll Period"**
2. Enter **Period Name** (e.g., "March 2026")
3. Set **Start Date** and **End Date**
4. Click **Save**

#### Generate Payroll
1. Select a **Payroll Period**
2. Click **"Generate Payroll"**
3. The system calculates for each employee:
   - Basic salary
   - Allowances
   - Deductions (income tax, pension contribution)
   - Net pay
4. Review the generated payroll

#### Approve Payroll
1. After reviewing, click **"Approve"**
2. Approved payroll cannot be edited

#### Payroll Detail
Click on a payroll period to see:
- Each employee's salary breakdown
- Tax and pension deductions
- Allowances
- Net pay

#### Payslip
1. Go to **Payroll Detail**
2. Click **"Payslip"** for individual employees
3. View/print/download the payslip (PDF format)

### 9.7 PDF Reports & Printing

**Navigation:** Sidebar → HR → Printing

The HR module generates several PDF reports:

| Report | Description |
|--------|-------------|
| **Tax Sheet** | Ethiopian income tax report for all employees |
| **Pension Sheet** | Pension contribution report |
| **Bank Transfer Sheet** | Spreadsheet for bank salary transfers |
| **Employment Contract** | Printable employee contract |
| **Payslip** | Individual salary slip |

For each report:
1. Select the **Payroll Period**
2. Click **Print** (opens in browser) or **Download** (saves PDF)

### 9.8 Biometric Devices

**Navigation:** Sidebar → HR → Devices

Manage biometric attendance devices:

**To add a device:**
1. Click **"Add Device"**
2. Enter device **Name**, **IP Address**, **Port**
3. Click **Save**

**Sync Device:**
- Click **"Sync"** to pull attendance records from the device

**Device Status:**
- View connection status (online/offline)

### 9.9 HR Reports Dashboard

**Navigation:** Sidebar → HR → Reports

Overview reports including:
- Employee headcount by department
- Attendance trends
- Leave utilization
- Payroll summaries

---

## 10. Messaging Module

**Access:** All roles (with role-specific features)

### 10.1 Inbox

**Navigation:** Sidebar → Messaging → Inbox

View all received messages. Each message shows:
- Sender name and role
- Subject/preview
- Date and time
- Read/unread status

Click a message to view the full conversation thread.

### 10.2 Compose Message

**Navigation:** Sidebar → Messaging → Compose

**Steps:**
1. Click **"Compose"**
2. In the **To** field, type a name to search for users
3. Select the recipient from the dropdown
4. Enter a **Subject** (optional)
5. Type your **Message** in the text area
6. Optionally attach a file (click 📎 icon)
7. Click **Send**

### 10.3 Reply to a Message

1. Open a conversation from the Inbox
2. Type your reply in the text field at the bottom
3. Click **Send**

### 10.4 Sent Messages

**Navigation:** Sidebar → Messaging → Sent

View all messages you have sent, with recipient and timestamp.

### 10.5 Bulk Messaging (Admin Only)

**Navigation:** Sidebar → Messaging → Bulk Message

Send a message to multiple users at once.

**Steps:**
1. Click **"Bulk Message"**
2. Select **Target Audience**:
   - All Users
   - By Role (e.g., all teachers, all parents)
   - By Class (e.g., all Grade 5 parents)
3. Enter the **Subject** and **Message**
4. Click **Send**

**Bulk History:**
- View previously sent bulk messages at **Messaging → Bulk History**

### 10.6 Group Messaging (Students Only)

Students can create and manage chat groups with classmates.

**To create a group:**
1. Go to **Messaging → My Groups**
2. Click **"Create Group"**
3. Enter a **Group Name**
4. Add members by searching for classmates
5. Click **Create**

**Group features:**
- Send messages to the group
- Add/remove members
- Edit group name
- Delete group (creator only)

### 10.7 File Attachments

When composing or replying to messages:
1. Click the **attachment icon** (📎)
2. Select a file from your device
3. Supported formats: images, PDF, documents
4. Maximum file size: 5MB
5. The file is uploaded and attached to the message

### 10.8 Delete Conversations

1. Open a conversation
2. Click **"Delete"**
3. Confirm the deletion
4. The conversation is removed from your view (not from the recipient's)

---

## 11. Communication Module

**Access:** Super Admin, School Admin (full) | All roles (view announcements)

### 11.1 Announcements

**Navigation:** Sidebar → Communication → Announcements

#### View Announcements
All users see announcements relevant to their role. Each shows:
- Title
- Content preview
- Published date
- Target audience

#### Create Announcement (Admin)
1. Click **"Create Announcement"**
2. Enter a **Title**
3. Write the **Content** (supports formatting)
4. Select **Target Audience**:
   - All Users
   - Specific Roles (e.g., Teachers, Parents)
   - Specific Classes
5. Set **Priority** (Normal / Important / Urgent)
6. Click **Publish**

#### Edit/Delete Announcement
- Click **Edit** to modify a published announcement
- Click **Delete** to remove it (with confirmation)

### 11.2 Notifications

**Navigation:** Click the 🔔 bell icon in the header, or Sidebar → Communication → Notifications

Notifications alert you about:
- New messages
- Announcements
- Fee due reminders
- Attendance alerts
- Result postings

**Actions:**
- Click a notification to view its details
- Click **"Mark as Read"** on individual notifications
- Click **"Mark All as Read"** to clear all notifications

---

## 12. Users Module

**Access:** Super Admin, School Admin only

**Navigation:** Sidebar → Users

### 12.1 User List

The user list shows all system accounts:
- Name
- Username
- Role
- Status (Active / Inactive)
- Last Login date

**Filters:** Search by name, username, or filter by role.

### 12.2 Add New User

**Navigation:** Click **"Add User"** button

**Required fields:**
| Field | Description |
|-------|-------------|
| Full Name | User's display name |
| Username | Unique login ID |
| Email | Must be unique |
| Password | Minimum 8 characters |
| Confirm Password | Must match password |
| Role | Select from: Super Admin, School Admin, Teacher, Student, Parent, Accountant, Registrar |
| Phone | Optional (+251... format) |

Click **Save** to create the user.

### 12.3 Edit User

Click **Edit** (✏️) on any user to update their information. You can change:
- Name, email, phone
- Role (admin only)
- Password (reset)

### 12.4 View User Details

Click **View** (👁️) to see full user information:
- Account details
- Role and permissions
- Login history
- Created date

### 12.5 Toggle User Status

Click the **Toggle Status** button to activate or deactivate a user account:
- **Active** — user can log in
- **Inactive** — user is blocked from logging in

### 12.6 Delete User

Click **Delete** (🗑️) to remove a user account. Confirmation is required.

> ⚠️ You cannot delete your own account or the last Super Admin account.

---

## 13. Settings Module

**Access:** Super Admin, School Admin

### 13.1 General Settings

**Navigation:** Sidebar → Settings → General Settings

Configure system-wide settings organized by category:

#### General Settings
| Setting | Description |
|---------|-------------|
| School Name | Displayed in header and reports |
| School Address | Used in report cards and ID cards |
| School Phone | Contact number |
| School Email | Official email |
| School Logo | Upload school logo (used in reports, ID cards, header) |
| Timezone | Default: Africa/Addis_Ababa |
| Currency | Default: ETB |
| Academic Year | Current active session |
| Date Format | Display format for dates |

#### Email Settings (if configured)
| Setting | Description |
|---------|-------------|
| SMTP Host | Email server |
| SMTP Port | Server port |
| SMTP Username | Email login |
| SMTP Password | Email password |
| From Address | Sender email |
| From Name | Sender display name |

**To update settings:**
1. Edit the values in the form fields
2. Click **"Save Settings"**

### 13.2 Audit Logs

**Navigation:** Sidebar → Settings → Audit Logs

Track all important system activities:
- Who performed the action
- What action was taken
- When it happened
- IP address

Useful for security monitoring and accountability.

### 13.3 Database Backup

**Navigation:** Sidebar → Settings → Backup

**Access:** Super Admin only

Create a backup of the entire database:
1. Click **"Create Backup"**
2. The system generates a SQL dump file
3. Download the backup file for safekeeping

> 💡 **Best Practice:** Create regular backups, especially before major operations like student promotion or end-of-term processing.

---

## 14. Role-Based Access Summary

### Quick Reference: Who Can Do What

| Feature | Super Admin | School Admin | Teacher | Student | Parent | Accountant | Registrar |
|---------|:-----------:|:------------:|:-------:|:-------:|:------:|:----------:|:---------:|
| **Dashboard** | ✅ Full | ✅ Full | ✅ Basic | ✅ Basic | ✅ Basic | ✅ Finance | ✅ Basic |
| **Academic Setup** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **View Timetable** | ✅ | ✅ | ✅ Own | ✅ Own | ✅ Child | ❌ | ❌ |
| **Student Admission** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **View Students** | ✅ All | ✅ All | ✅ Own Classes | ✅ Self | ✅ Children | ❌ | ✅ All |
| **Take Attendance** | ✅ | ✅ | ✅ Own Classes | ❌ | ❌ | ❌ | ❌ |
| **View Attendance** | ✅ All | ✅ All | ✅ Own Classes | ✅ Self | ✅ Children | ❌ | ❌ |
| **Create Exams** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Enter Marks** | ✅ | ✅ | ✅ Own Subjects | ❌ | ❌ | ❌ | ❌ |
| **View Report Cards** | ✅ All | ✅ All | ✅ Own Classes | ✅ Self | ✅ Children | ❌ | ❌ |
| **Finance — Collect Payment** | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| **Finance — Reports** | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| **HR — Employees** | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ View | ❌ |
| **HR — Payroll** | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ View | ❌ |
| **HR — Leave** | ✅ Manage | ✅ Manage | ✅ Request | ❌ | ❌ | ❌ | ❌ |
| **Messaging** | ✅ + Bulk | ✅ + Bulk | ✅ Solo | ✅ + Groups | ✅ Solo | ✅ Solo | ✅ Solo |
| **Announcements** | ✅ Create | ✅ Create | ✅ View | ✅ View | ✅ View | ✅ View | ✅ View |
| **User Management** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Settings** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Audit Logs** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Database Backup** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 15. Tips & Best Practices

### For Administrators

1. **Start of Year Setup Checklist:**
   - Create a new Academic Session
   - Set up Terms (Term 1–4) with correct dates
   - Activate the new session and first term
   - Verify class and section structure
   - Assign class teachers and subject teachers
   - Set up fee structures
   - Create the timetable

2. **Security Best Practices:**
   - Change all default passwords immediately
   - Create individual accounts for each user (no shared accounts)
   - Review audit logs regularly
   - Create database backups before major operations
   - Deactivate accounts of users who leave the school

3. **Student Management:**
   - Use CSV bulk import for large batches of new students
   - Generate login credentials after admission
   - Assign roll numbers at the start of each year
   - Use Student Promotion at year end (not manual re-enrollment)

4. **Financial Management:**
   - Create Finance Groups for efficient fee assignment
   - Assign fees to groups rather than individual students
   - Record every payment with a reference number
   - Generate financial reports monthly
   - Review outstanding balances regularly

### For Teachers

1. **Daily Workflow:**
   - Take attendance at the beginning of each class
   - Use "All Present" button, then mark individual absences (faster)
   - Add remarks for late/excused students

2. **Exam & Results:**
   - Create assessments before entering marks
   - Double-check marks before saving (especially decimals)
   - Use Result Analysis to identify struggling students

3. **Communication:**
   - Use messaging for individual student/parent communication
   - Check inbox regularly for important notices

### For Students

1. **Check regularly:**
   - Attendance performance
   - Exam results and report cards
   - Announcements for school updates

2. **Messaging:**
   - Create study groups with classmates
   - Message teachers respectfully for academic help

### For Parents

1. **Monitor your child's:**
   - Attendance (daily presence)
   - Exam results (term-wise grades)
   - Fee payment status

---

## 16. FAQ & Troubleshooting

### General Questions

**Q: How do I reset my password?**  
A: Click "Forgot Password" on the login page, or ask an administrator to reset it for you.

**Q: Why is my account locked?**  
A: After 5 failed login attempts, accounts are locked for 15 minutes. Wait and try again, or contact an administrator.

**Q: How do I switch between English and Amharic?**  
A: Click the language toggle (🌐) in the top-right corner of any page.

**Q: Can I use the system on my phone?**  
A: Yes! The system is fully responsive. You can also install it as a PWA app from your browser for quicker access.

**Q: My session expired. Why?**  
A: Sessions expire after 2 hours of inactivity for security. Simply log in again.

### Attendance

**Q: Can I edit attendance after saving?**  
A: Admins can edit past attendance. Teachers may have restrictions on editing historical records.

**Q: Can I take attendance for a past date?**  
A: Yes, select the desired date from the date picker. Future dates are not allowed.

### Exams & Results

**Q: Why can't I enter marks for a class?**  
A: Ensure you are assigned as the subject teacher for that class. Contact an admin if you believe this is an error.

**Q: How are grades calculated?**  
A: Grades are automatically calculated based on the Grade Scale configured in Results → Grade Scale.

**Q: Can I change marks after submitting?**  
A: Yes, you can re-enter and save marks as long as report cards haven't been finalized.

### Finance

**Q: How do I pay school fees online?**  
A: The system supports Telebirr and Chapa. Use the payment link provided by the school.

**Q: Can I get a receipt for my payment?**  
A: Yes, payment records include reference numbers and can be downloaded as PDF.

### HR / Payroll

**Q: How is income tax calculated?**  
A: Based on Ethiopian income tax brackets applied to taxable income (basic salary + taxable allowances).

**Q: What about pension?**  
A: Pension is calculated as per Ethiopian pension contribution rules (both employee and employer portions).

### Technical Issues

**Q: The page is loading slowly.**  
A: Try clearing your browser cache, ensure stable internet, or use a different browser.

**Q: I see a "403 Forbidden" error.**  
A: You don't have permission to access that page. Contact your administrator.

**Q: I see a "404 Not Found" error.**  
A: The page doesn't exist. Check the URL or navigate using the sidebar menu.

**Q: Dark mode looks strange.**  
A: Toggle the theme switch to reset. If issues persist, clear browser cookies.

---

## System Information

| Detail | Value |
|--------|-------|
| **System Name** | Urji Beri School Management System |
| **Version** | 1.0.0 |
| **Currency** | Ethiopian Birr (ETB) |
| **Timezone** | Africa/Addis_Ababa |
| **Calendar Support** | Gregorian + Ethiopian Calendar |
| **Languages** | English, Amharic |
| **Max Upload Size** | 5 MB |
| **Session Timeout** | 2 hours |
| **Login Lockout** | 5 attempts / 15 minutes |
| **Minimum Password** | 8 characters |

---

*© 2026 Urji Beri School Management System. All rights reserved.*
