# Payment System (Finance Module)

This document provides a **detailed, end-to-end explanation** of the payment system implemented in this SMS application (also called the Finance Module). It covers:

- Database schema (tables + relationships)
- Core business flows (fee assignment, payment collection, balance updates)
- All major backend code paths (which scripts run for each action)
- How UI screens map to database actions
- What data is audited and where

---

## 0. Finance Module Routes (URLs)

The Finance module is routed via `modules/finance/routes.php` using `action` values. The main available pages and actions are:

- `finance?route=students` (Student list)
- `finance?route=student-detail&id=<student_id>` (Student finance detail)
- `finance?route=assign-fee` (Assign fee action, POST)
- `finance?route=remove-fee` (Remove fee action, POST)
- `finance?route=adjust-balance` (Adjust balance action, POST)
- `finance?route=make-payment` (Make payment action, POST)
- `finance?route=groups` (Fee groups list)
- `finance?route=group-detail&id=<group_id>` (Group detail)
- `finance?route=group-save` (Create/update group, POST)
- `finance?route=group-assign-members` (Add students to group, POST)
- `finance?route=group-remove-members` (Remove students from group, POST)
- `finance?route=group-action` (Bulk group actions like assign fees, POST)
- `finance?route=collect-payment` (Collect payment page)
- `finance?route=collect-payment-save` (Collect payment submit, POST)
- `finance?route=payment-attachment&id=<transaction_id>` (Print payment receipt)
- `finance?route=payments` (All payment history)
- `finance?route=supplementary-payments` (Supplementary payments history)
- `finance?route=fee-due` (All due fees)
- `finance?route=fee-save` (Create fee, POST)
- `finance?route=fee-detail&id=<fee_id>` (Fee detail)
- `finance?route=supplementary-fees` (Supplementary fees list)
- `finance?route=supplementary-fee-save` (Create supplementary fee, POST)
- `finance?route=report-students` (Student financial report)
- `finance?route=report-penalty` (Penalty report)
- `finance?route=report-supplementary` (Supplementary report)
- `finance?route=report-generate` (Generate report, POST)
- `finance?route=export-pdf` (Export finance data to PDF)
- `finance?route=export-excel` (Export finance data to Excel)

Each route is protected by permission checks (`auth_require_permission`) and uses `is_post()` to ensure POST-only actions.

---

## 1. High-Level Concepts

### 1.1 What is a “Fee”?
A *fee* is a charge the school wants to collect from a student. Fees can be:
- **Recurrent** (e.g., monthly tuition)
- **One-time** (e.g., admission fee)

Fees are defined once in the system, then assigned to students.

### 1.2 What is a “Student Fee Assignment”?
When a fee is assigned to a student, the system creates a record representing that student’s obligation. That record tracks:
- How much is owed
- How much is paid
- Remaining balance

### 1.3 What is a “Transaction”?
All actions that change a student’s financial state are recorded as a `transaction`. This includes:
- fee assignment
- fee removal
- payment
- balance adjustment

Transactions provide a complete audit trail.

---

## 2. Database Schema (Finance Tables)

### 2.1 `fin_fees` (Master Fee Definitions)
Defines fee templates and their rules.

Key fields:
- `id` (PK)
- `description` – fee name
- `amount` – base amount
- `currency` – usually ETB
- `fee_type` – 1 = recurrent, 0 = one-time
- `effective_date` / `end_date` – validity range
- penalty fields (optional, not auto-applied)
- `is_active` – whether fee can be assigned

### 2.2 `fin_fee_classes` (Fee Assignment to Classes)
Links a fee to a class so you can assign fees to students based on their class.

### 2.3 `fin_student_fees` (Assigned Fees)
Each record is one student’s assigned fee.

Key fields:
- `student_id` – references `students.id`
- `fee_id` – references `fin_fees.id`
- `amount` – total assigned amount
- `balance` – outstanding amount remaining
- `is_active` – indicates if the assignment is still active
- `assigned_by` / `removed_by` – who performed the assignment/removal

### 2.4 `fin_transactions` (Audit Log)
Tracks every financial event and provides the “source of truth”.

Key fields:
- `student_id`, `student_fee_id`
- `type`:
  - `payment`
  - `adjustment`
  - `fee_assigned`
  - `fee_removed`
  - `penalty`
  - `refund`
- `amount` (payments stored as negative values)
- `balance_before`, `balance_after`
- `channel` / `channel_transaction_id` / `payer_phone` / `receipt_no`
- `processed_by`

### 2.5 Penalties (Unused Automations)
There is a dedicated penalty framework for future automated late fees:
- `fin_varying_penalties` (tiered penalty rates)
- `fin_penalty_log` (log when penalty was applied)

Currently, penalty application is not implemented in the code; the tables exist for future expansion.

### 2.6 Supplementary Fees (Separate path)
For one-time “extras” (e.g., field trip):
- `fin_supplementary_fees`: definition
- `fin_supplementary_transactions`: payments


---

## 3. Core Payment Flows (Code Paths)

This section explains each major operation, the files involved, and the database updates performed.

### 3.1 Assign Fee to Student — `assign_fee.php`
**Trigger**: UI button *Assign Fee* on Student Finance Detail.

**UI Fields:**
- `student_id` (hidden)
- `fee_id` (selected from active fees list)

**What it does (step-by-step):**
1. Validates the request via `csrf_protect()`.
2. Reads `student_id` and `fee_id` using `input_int()`.
3. Verifies the fee exists and is active:
   - SQL: `SELECT * FROM fin_fees WHERE id = ? AND is_active = 1`
4. Checks for an existing assignment:
   - SQL: `SELECT id FROM fin_student_fees WHERE student_id = ? AND fee_id = ? AND is_active = 1`
5. Inserts a new student fee record:
   - SQL: `INSERT INTO fin_student_fees (student_id, fee_id, amount, currency, balance, is_active, assigned_by, created_at, updated_at) VALUES (...)`
   - Sets `balance` to the fee amount.
6. Inserts a transaction record for audit:
   - SQL: `INSERT INTO fin_transactions (student_id, student_fee_id, type, amount, currency, balance_before, balance_after, description, processed_by, created_at) VALUES (...)`
   - `type = 'fee_assigned'`
   - `amount` is the positive fee value.

**Key files:**
- `modules/finance/actions/assign_fee.php`

### 3.2 Remove Fee from Student — `remove_fee.php`
**Trigger**: UI button *Remove Fee*.

**UI Fields:**
- `student_id` (hidden)
- `student_fee_id` (selected in modal)
- `reason` (optional explanation)

**What it does (step-by-step):**
1. Protects against CSRF with `csrf_protect()`.
2. Reads `student_id`, `student_fee_id`, and `reason` from POST.
3. Loads active assignment:
   - SQL: `SELECT * FROM fin_student_fees WHERE id = ? AND student_id = ? AND is_active = 1`
4. Marks the assignment inactive:
   - SQL: `UPDATE fin_student_fees SET is_active = 0, removed_at = NOW(), removed_by = ? WHERE id = ?`
5. Creates a transaction record:
   - `type = 'fee_removed'`
   - `amount = -balance` (negative so net balance is cleared)
   - `balance_before` = previous `balance`
   - `balance_after` = 0
   - `description` includes fee description + optional reason.

**Key files:**
- `modules/finance/actions/remove_fee.php`

### 3.3 Adjust Balance — `adjust_balance.php`
**Trigger:** UI button *Adjust Balance*.

**UI Fields:**
- `student_id` (hidden)
- `student_fee_id` (selected in modal)
- `amount` (positive or negative number)
- `reason` (mandatory field)

**What it does (step-by-step):**
1. Runs `csrf_protect()`.
2. Reads inputs and validates:
   - `amount` must not be 0
   - `reason` is required
3. Loads the active assignment record:
   - SQL: `SELECT * FROM fin_student_fees WHERE id = ? AND student_id = ? AND is_active = 1`
4. Calculates the new balance:
   - `newBalance = current_balance + adjustment_amount`
5. Updates `fin_student_fees.balance` to `newBalance`:
   - SQL: `UPDATE fin_student_fees SET balance = ? WHERE id = ?`
6. Creates an audit transaction:
   - `type = 'adjustment'`
   - `amount` = adjustment amount (can be negative or positive)
   - `balance_before` and `balance_after` show the change
   - `description` includes the supplied reason

**Key files:**
- `modules/finance/actions/adjust_balance.php`

### 3.4 Make Payment — `make_payment.php`
**Trigger:** Student Finance Detail → **“Make Payment”** (a simple single-payment form that appears on the student’s finance page).

**UI Fields:**
- `student_id` (hidden)
- `student_fee_id` (hidden; selects the specific assigned fee)
- `amount` (payment amount)
- `channel` (payment method, e.g., cash/bank)
- `reference` (optional reference number)

**What it does (step-by-step):**
1. Runs `csrf_protect()`.
2. Reads and validates inputs:
   - `student_id` and `student_fee_id` must exist
   - `amount` must be > 0
3. Loads `fin_student_fees` (active assignment):
   - SQL: `SELECT * FROM fin_student_fees WHERE id = ? AND student_id = ? AND is_active = 1`
4. Calculates `newBalance = current_balance - amount`.
5. Updates assignment record:
   - SQL: `UPDATE fin_student_fees SET balance = ? WHERE id = ?`
6. Creates a transaction record:
   - `type = 'payment'`
   - `amount = -amount` (payments are stored negative)
   - `balance_before` = current balance, `balance_after` = newBalance
   - Optional: `channel`, `reference`, `description`

**Key files:**
- `modules/finance/actions/make_payment.php`

**Important behavior:**
- There is no guard preventing overpayment; if `amount` exceeds balance, the system will store a negative balance (unless the UI prevents it).
- The UI does not generate a receipt PDF automatically; that is done by the separate receipt action.

### 3.5 Collect Payment (Full Payment Form) — `collect_payment_save.php`
**Trigger:** Finance → Collect Payment (multi-step payment collection page).

This is the most comprehensive payment workflow. It is designed for cashiers/accountants to record payments (including bank/TeleBirr transfers) with full metadata.

**UI Flow (What the user sees):**
1. Search and select a student (filter by name/class/balance).
2. If a student is selected, the page shows:
   - Active fees with their remaining balance
   - Recent payments made by that student
3. When a fee is chosen, the form asks for:
   - Amount (ETB)
   - Payment Channel (TeleBirr, Bank Transfer, Cash, etc.)
   - Optional Reference
   - Optional Notes
4. If the chosen channel is NOT TeleBirr, the UI also shows a **“Confirm payment received”** checkbox.
5. Submit saves the payment, updates the balance, and allows printing a receipt.

**Required POST fields (backend expects):**
- `student_id`
- `student_fee_id`
- `amount`
- `channel`
- optional: `reference`, `notes`
- channel-specific:
  - TeleBirr: `channel_transaction_id`, `payer_phone`
  - Bank: `channel_payment_type`, `channel_depositor_name`, `channel_depositor_branch`, `bank_transaction_id`
- `confirm_paid` (required for non-TeleBirr channels)

**Backend behavior (step-by-step):**
1. Runs `csrf_protect()`.
2. Validates required fields; returns errors if missing.
3. If channel != `telebirr`, verifies `confirm_paid` is checked.
4. Loads the active student fee assignment:
   - SQL: `SELECT * FROM fin_student_fees WHERE id = ? AND student_id = ? AND is_active = 1`
5. If the payment amount is more than the remaining balance, it caps the payment to the remaining balance.
6. Generates a receipt number:
   - Example: `RCP-20260313-3F1A2B`
7. Updates `fin_student_fees.balance`:
   - `UPDATE fin_student_fees SET balance = ? WHERE id = ?`
8. Inserts the transaction record into `fin_transactions`:
   - `type` = `payment`
   - `amount` = -amount (negative)
   - `balance_before` / `balance_after`
   - stores payment metadata (`channel`, `channel_transaction_id`, etc.)
   - stores `receipt_no` and `notes` if provided
9. Sets a success flash message including the receipt number.

**Key files:**
- `modules/finance/views/collect_payment.php`
- `modules/finance/actions/collect_payment_save.php`

**Receipt Printing:**
After payment is saved, the page shows a “Print Receipt” button (or link to `payment_attachment.php`) which generates a PDF and increments `fin_transactions.print_count`.

**Notes on balancing:**
- This form prevents overpayment by reducing the payment amount to the remaining balance (capping logic).
- The system treats the payment as “complete” once recorded; there is no separate reconciliation step.

### 3.6 Payment Receipt / Attachment — `payment_attachment.php`
**Trigger:** “Print Receipt” action on a payment record.

**What it does (step-by-step):**
1. Reads `id` from route parameter or query string (`route_id()` or `input_int('id')`).
2. Loads the transaction record and related information via a joined query:
   - Student name / admission number
   - Class name
   - Fee description
   - Processed by user name
   - Transaction details (amount, channel, receipt_no, etc.)
3. If the transaction is missing or not of type `payment`, it redirects with an error.
4. Determines if this is a “copy” receipt:
   - If `print_count > 0`, it marks the receipt as a copy.
5. Updates `print_count` in `fin_transactions` (`+1`).
6. Instantiates `PaymentAttachmentPDF` from `core/pdf_payment_attachment.php` and calls `generate()`.

**Receipt PDF Contents include:**
- School name and contact info
- Student details (name, class, admission no)
- Fee description
- Amount paid
- Payment channel and transaction/reference numbers
- Receipt number and print copy status

**Key files:**
- `modules/finance/actions/payment_attachment.php`
- `core/pdf_payment_attachment.php`

**Behavior notes:**
- The receipt is always generated as an A5 PDF and served directly to the browser.
- The system relies on `print_count` to show whether this is the original copy or a reprint.

---

## 4. UI Pages & What They Show

### 4.1 Finance → Students (Student Finance List)
- Lists all students with a summary balance.
- Allows filtering by name, class, gender, outstanding balance.
- Clicking a student opens the Student Finance Detail page.

**How it works (backend query):**
- The list query aggregates `fin_student_fees` to calculate each student’s total outstanding balance:
  ```sql
  SELECT s.id, s.full_name, s.admission_no, s.phone,
         c.name AS class_name, sec.name AS section_name,
         COALESCE(SUM(CASE WHEN sf.is_active = 1 THEN sf.balance ELSE 0 END), 0) AS total_balance
    FROM students s
    LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
    LEFT JOIN classes c ON e.class_id = c.id
    LEFT JOIN sections sec ON e.section_id = sec.id
    LEFT JOIN fin_student_fees sf ON s.id = sf.student_id
   WHERE s.deleted_at IS NULL
   GROUP BY s.id
   HAVING total_balance > 0  -- optional
  ```
- The UI also supports paging and search by name/code/phone.

### 4.2 Finance → Student Detail
Shows:
- Student profile and guardian contact
- Payment summary (total active fees, total outstanding balance)
- Tabs:
  - Active fees (current assigned fees)
  - Payments (only transactions of type `payment`)
  - Full transaction history

Actions available:
- Assign fee
- Remove fee
- Adjust balance
- Make payment

### 4.3 Finance → Collect Payment
Designed as an actionable step-by-step workflow.

**Step 1 – Find the student:**
- You can search by name, admission number, or phone.
- You can filter by class/section.
- You can filter by balance status:
  - `With Outstanding Balance` (shows only students with remaining balance)
  - `No Outstanding Balance`

**Step 2 – Choose active fee:**
- Once a student is selected, the page loads all **active** `fin_student_fees` with a remaining balance (> 0).
- The UI allows selecting one fee to pay against.

**Step 3 – Enter payment details:**
The form includes:
- Amount (ETB)
- Payment channel (TeleBirr, bank, cash, etc.)
- Reference and notes
- Channel-specific fields (e.g., TeleBirr transaction ID, payer phone, bank depositor name)
- For non-TeleBirr channels, a “Confirm payment received” checkbox is required.

**Step 4 – Submit and clear the balance:**
- The backend records the transaction, updates the balance, and generates a receipt number.
- After submission, the form reloads with the selected student and shows a “Print Receipt” button.

**Related backend query patterns:**
- Student lookup uses `students` joined with `enrollments` and `classes`.
- Active fees query uses:
  ```sql
  SELECT sf.id AS sf_id, sf.amount, sf.balance, sf.currency, f.description
    FROM fin_student_fees sf
    JOIN fin_fees f ON sf.fee_id = f.id
   WHERE sf.student_id = ? AND sf.is_active = 1 AND sf.balance > 0
  ```
- Recent payments query uses `fin_transactions` filtered by `type = 'payment'`.

### 4.4 Finance → Payment History
Lists all payments across all students.

### 4.5 Finance → Fee Due
Lists outstanding fees per student (from `fin_student_fees` where `balance > 0`).

### 4.6 Finance → Supplementary Fees & Payments
For separate “supplementary fee” flow (uses its own tables).

---

## 5. Data Flow Summary (End-to-End)

### A) When a fee is assigned:
1. Create `fin_student_fees`
2. Create `fin_transactions` type `fee_assigned`

### B) When a payment is made:
1. Update `fin_student_fees.balance`
2. Create `fin_transactions` type `payment`

### C) When a fee is removed:
1. Set `fin_student_fees.is_active = 0`
2. Create `fin_transactions` type `fee_removed`

### D) When balance is adjusted:
1. Update `fin_student_fees.balance`
2. Create `fin_transactions` type `adjustment`

---

## 6. Important Notes for Developers / Maintainers

### 6.1 Penalty System (Not Automated)
Although the database contains penalty configuration fields (and a log table), no code currently applies penalties automatically. If you want to implement it, you would need a cron job or scheduled task to:
1. Query overdue `fin_student_fees`
2. Calculate penalty based on `fin_fees` settings
3. Increase `balance` and record `fin_transactions` + `fin_penalty_log`

### 6.2 Currency Handling
The system stores currency in both `fin_fees` and `fin_student_fees`. Payments are assumed to use the same currency, and there is no cross-currency conversion built into the payment code.

---

## 7. Common Use Cases (Concrete Examples)
These examples walk through what happens in the database for typical tasks.

### 7.1 Assign monthly tuition to a student
1. In UI: Go to Finance → Students → select student → Assign Fee -> choose a fee (e.g., "Tuition") and submit.
2. Database changes:
   - A row is inserted into `fin_student_fees` with:
     - `amount` = fee amount
     - `balance` = fee amount
     - `is_active` = 1
   - A row is inserted into `fin_transactions` with:
     - `type` = `fee_assigned`
     - `amount` = fee amount
     - `balance_before` = 0
     - `balance_after` = fee amount

### 7.2 Record a partial payment
1. In UI: Go to student’s finance page, click Make Payment / Collect Payment.
2. Amount entered is less than `fin_student_fees.balance`.
3. Database changes:
   - `fin_student_fees.balance` is decreased by the payment amount.
   - `fin_transactions` gets a row with:
     - `type` = `payment`
     - `amount` = -payment amount
     - `balance_before` = previous balance
     - `balance_after` = updated balance

### 7.3 Record a full payment (balance becomes 0)
1. The same payment entry is used, with amount equal to the remaining balance.
2. After submit, `fin_student_fees.balance` becomes `0`.
3. The student’s record will now show no outstanding balance in the Student List and Collect Payment screens.

### 7.4 Overpaying (amount > balance)
1. The collect payment endpoint automatically caps the payment to the remaining balance.
2. The student fee `balance` will become `0`, and the transaction amount matches the capped value.

### 7.5 Printing a receipt
1. After payment, click the “Print Receipt” button.
2. The system loads the `fin_transactions` record and generates a PDF.
3. `fin_transactions.print_count` is incremented each time to mark subsequent prints as copies.

### 7.6 Removing a fee with remaining balance
1. Click Remove Fee on the student’s finance detail page.
2. The system sets the assignment `is_active = 0` and creates a transaction:
   - `type` = `fee_removed`
   - `amount` = -(remaining balance)
   - `balance_after` = 0

---

## 8. Debugging Tips (Where to Look)

### 8.1 Why does a student still show a balance?
- Check `fin_student_fees` to see if `is_active = 1` and `balance > 0`.
- Confirm there isn’t a `fin_transactions` payment missing or with the wrong sign.

### 8.2 Why isn’t a payment showing in history?
- Ensure `type = 'payment'` in `fin_transactions`.
- Confirm the transaction’s `student_id` matches the student you’re viewing.

### 8.3 Why is a receipt flagged as “copy”?
- The receipt is considered a copy if `print_count` on the transaction is > 0.

---

## 9. Where to Extend / Customize

### 9.1 Add automated penalty calculation (future enhancement)
- Add a scheduled script (CRON or task scheduler) to:
  1. Find student fees past due using `fin_student_fees.balance > 0` and `fin_fees` penalty settings.
  2. Calculate penalty using `fin_fees.penalty_type` settings.
  3. Update `fin_student_fees.balance` and insert a `fin_transactions` row with `type = 'penalty'`.
  4. Log the application in `fin_penalty_log`.

### 9.2 Add new payment channels
- Update UI (`collect_payment.php`) to include the new channel.
- Update `collect_payment_save.php` to record any additional metadata required.

---

### 6.3 Data Integrity
All finance actions use transactions (`db_begin()` / `db_commit()`), so updates to balances and audit logs are atomic.

---

## 7. Quick Reference: Where to Find Things In Code

| Feature | UI Path | Main Code File(s) |
|---|---|---|
| Assign fee | Finance → Student → Assign Fee | `assign_fee.php` |
| Remove fee | Finance → Student → Remove Fee | `remove_fee.php` |
| Adjust balance | Finance → Student → Adjust Balance | `adjust_balance.php` |
| Quick payment | Finance → Student → Make Payment | `make_payment.php` |
| Full payment workflow | Finance → Collect Payment | `collect_payment.php` + `collect_payment_save.php` |
| Print receipt | Payment history / payment detail | `payment_attachment.php` + `core/pdf_payment_attachment.php` |
| Fee setup | Finance → Fees | `fee_save.php` |

---

## 10. System Diagram (Mermaid)

Below is a diagram that shows the key UI actions, the PHP actions they trigger, and how those actions update the core finance tables.

```mermaid
flowchart LR
  subgraph UI[UI Pages]
    UI1[Student Finance Detail]
    UI2[Collect Payment]
    UI3[Payment History / Receipt]
  end

  subgraph Actions[Server Actions]
    AssignFee[Assign Fee\n(assign_fee.php)]
    RemoveFee[Remove Fee\n(remove_fee.php)]
    Adjust[Adjust Balance\n(adjust_balance.php)]
    MakePayment[Make Payment\n(make_payment.php)]
    CollectPay[Collect Payment\n(collect_payment_save.php)]
    Receipt[Print Receipt\n(payment_attachment.php)]
  end

  subgraph Tables[Database Tables]
    FeeDef[fin_fees]
    StudentFee[fin_student_fees]
    Tx[fin_transactions]
  end

  UI1 --> AssignFee
  UI1 --> RemoveFee
  UI1 --> Adjust
  UI1 --> MakePayment
  UI2 --> CollectPay
  UI3 --> Receipt

  AssignFee -->|creates| StudentFee
  AssignFee -->|logs| Tx
  RemoveFee -->|updates| StudentFee
  RemoveFee -->|logs| Tx
  Adjust -->|updates| StudentFee
  Adjust -->|logs| Tx
  MakePayment -->|updates| StudentFee
  MakePayment -->|logs| Tx
  CollectPay -->|updates| StudentFee
  CollectPay -->|logs| Tx
  Receipt -->|reads| Tx
```