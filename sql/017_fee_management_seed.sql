-- ============================================================
-- 017_fee_management_seed.sql
-- Sample seed data for the Fee Management module
-- Run after 015 & 016 migrations
-- ============================================================

-- ── 1. Sample Fees ──────────────────────────────────────────

INSERT INTO fees (fee_type, currency, description, amount, effective_date, end_date, status, created_by) VALUES
('one_time',  'ETB', 'Registration Fee 2026/2027',          500.00,  '2026-09-01', '2027-06-30', 'active', 1),
('recurrent', 'ETB', 'Monthly Tuition Fee 2026/2027',      2500.00,  '2026-09-01', '2027-06-30', 'active', 1),
('one_time',  'ETB', 'Laboratory Fee (Science Students)',    300.00,  '2026-09-01', '2027-06-30', 'active', 1),
('one_time',  'ETB', 'Library Fee 2026/2027',                150.00,  '2026-09-01', '2027-06-30', 'active', 1),
('recurrent', 'ETB', 'Transportation Fee (Monthly)',        1200.00,  '2026-09-01', '2027-06-30', 'active', 1),
('one_time',  'ETB', 'Uniform Fee (New Students)',           800.00,  '2026-09-01', '2027-06-30', 'draft',  1),
('one_time',  'ETB', 'Exam Fee - Final Exam 2027',          200.00,  '2027-01-01', '2027-06-30', 'draft',  1);


-- ── 2. Recurrence Configs (for recurrent fees) ─────────────

-- Monthly Tuition (fee_id = 2): every 1 month, 10 times (Sept-June)
INSERT INTO recurrence_configs (fee_id, frequency_number, frequency_unit, max_recurrences, next_due_date, current_recurrence) VALUES
(2, 1, 'months', 10, '2026-10-01', 1);

-- Transportation (fee_id = 5): every 1 month, 10 times
INSERT INTO recurrence_configs (fee_id, frequency_number, frequency_unit, max_recurrences, next_due_date, current_recurrence) VALUES
(5, 1, 'months', 10, '2026-10-01', 1);


-- ── 3. Penalty Configs ─────────────────────────────────────

-- Monthly Tuition penalty: 50 ETB fixed, one-time, 3-day grace
INSERT INTO penalty_configs (fee_id, grace_period_number, grace_period_unit, penalty_type, penalty_amount, penalty_frequency, max_penalty_amount, max_penalty_applications) VALUES
(2, 3, 'days', 'fixed', 50.00, 'one_time', 50.00, 1);

-- Registration Fee penalty: 5% recurring weekly, 7-day grace, max 500 ETB, max 4 applications
INSERT INTO penalty_configs (fee_id, grace_period_number, grace_period_unit, penalty_type, penalty_amount, penalty_frequency, penalty_recurrence_number, penalty_recurrence_unit, max_penalty_amount, max_penalty_applications) VALUES
(1, 7, 'days', 'percentage', 5.00, 'recurrent', 1, 'weeks', 500.00, 4);


-- ── 4. Student Groups ──────────────────────────────────────

INSERT INTO student_groups (name, description, status, created_by) VALUES
('Scholarship Recipients 2026',  'Students receiving full or partial scholarships', 'active', 1),
('Transportation Group A',       'Students using morning bus route A',              'active', 1),
('Science Stream Students',      'Students enrolled in natural science stream',     'active', 1);


-- ── 5. Student Group Members (sample — assumes students exist) ─

-- Add first 3 students to Scholarship group (group_id=1)
INSERT INTO student_group_members (group_id, student_id)
SELECT 1, id FROM students ORDER BY id LIMIT 3;

-- Add students 4-8 to Transportation group (group_id=2)
INSERT INTO student_group_members (group_id, student_id)
SELECT 2, id FROM students ORDER BY id LIMIT 5 OFFSET 3;

-- Add students 1-5 to Science group (group_id=3)
INSERT INTO student_group_members (group_id, student_id)
SELECT 3, id FROM students ORDER BY id LIMIT 5;


-- ── 6. Fee Assignments ─────────────────────────────────────

-- Registration Fee → all students in class 1 (assumes class id=1 has sections)
INSERT INTO fee_assignments (fee_id, assignment_type, target_id, created_by)
SELECT 1, 'grade', id, 1 FROM classes LIMIT 1;

-- Monthly Tuition → all students in class 1
INSERT INTO fee_assignments (fee_id, assignment_type, target_id, created_by)
SELECT 2, 'grade', id, 1 FROM classes LIMIT 1;

-- Library Fee → all students in class 1
INSERT INTO fee_assignments (fee_id, assignment_type, target_id, created_by)
SELECT 4, 'grade', id, 1 FROM classes LIMIT 1;

-- Transportation → Transportation Group
INSERT INTO fee_assignments (fee_id, assignment_type, target_id, created_by) VALUES
(5, 'group', 2, 1);

-- Lab Fee → Science group
INSERT INTO fee_assignments (fee_id, assignment_type, target_id, created_by) VALUES
(3, 'group', 3, 1);


-- ── 7. Sample Exemptions ───────────────────────────────────

-- Exempt first scholarship student from Registration Fee
INSERT INTO fee_exemptions (fee_id, student_id, reason, created_by)
SELECT 1, student_id, 'Full scholarship recipient - registration waived', 1
FROM student_group_members WHERE group_id = 1 LIMIT 1;

-- Exempt first scholarship student from Monthly Tuition
INSERT INTO fee_exemptions (fee_id, student_id, reason, created_by)
SELECT 2, student_id, 'Full scholarship recipient - tuition waived', 1
FROM student_group_members WHERE group_id = 1 LIMIT 1;


-- ── 8. Sample Student Fee Charges ──────────────────────────

-- Generate registration fee charges for first 5 students
INSERT INTO student_fee_charges (fee_id, student_id, amount, currency, due_date, status)
SELECT 1, s.id, 500.00, 'ETB', '2026-09-15', 
       CASE WHEN s.id % 3 = 0 THEN 'paid' WHEN s.id % 3 = 1 THEN 'pending' ELSE 'overdue' END
FROM students s ORDER BY s.id LIMIT 5;

-- Generate first month tuition charges for first 5 students
INSERT INTO student_fee_charges (fee_id, student_id, amount, currency, due_date, status)
SELECT 2, s.id, 2500.00, 'ETB', '2026-09-01',
       CASE WHEN s.id % 2 = 0 THEN 'paid' ELSE 'pending' END
FROM students s ORDER BY s.id LIMIT 5;

-- Library fee charges
INSERT INTO student_fee_charges (fee_id, student_id, amount, currency, due_date, status)
SELECT 4, s.id, 150.00, 'ETB', '2026-09-15', 'pending'
FROM students s ORDER BY s.id LIMIT 5;


-- ── 9. Sample Audit Log Entries ────────────────────────────

INSERT INTO finance_audit_log (user_id, action, entity_type, entity_id, details) VALUES
(1, 'Created fee: Registration Fee 2026/2027',       'fee', 1, '{"amount":500,"type":"one_time"}'),
(1, 'Created fee: Monthly Tuition Fee 2026/2027',    'fee', 2, '{"amount":2500,"type":"recurrent"}'),
(1, 'Activated fee: Registration Fee 2026/2027',     'fee', 1, NULL),
(1, 'Activated fee: Monthly Tuition Fee 2026/2027',  'fee', 2, NULL),
(1, 'Assigned Registration Fee to Grade',            'fee', 1, '{"assignment_type":"grade"}'),
(1, 'Assigned Monthly Tuition to Grade',             'fee', 2, '{"assignment_type":"grade"}'),
(1, 'Created group: Scholarship Recipients 2026',    'group', 1, NULL),
(1, 'Added exemption for scholarship student',       'fee', 1, '{"reason":"Full scholarship"}');
