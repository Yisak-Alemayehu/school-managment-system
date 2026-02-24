<?php
/**
 * Fee Management — Record Payment
 * Two modes:
 *   1. No student_id → search / list students with outstanding charges
 *   2. student_id given → show outstanding charges with checkboxes + payment form
 */

$studentId = input_int('student_id');
$sessionId = get_active_session_id();

// ── Mode 2: Student selected — show charges + payment form ──
if ($studentId) {
    // Fetch student with current enrollment
    $student = db_fetch_one("
        SELECT s.id, s.admission_no, s.first_name, s.last_name,
               u.email, u.phone,
               c.id AS class_id, c.name AS class_name, sec.name AS section_name,
               e.session_id
        FROM students s
        LEFT JOIN users u ON u.id = s.user_id
        JOIN enrollments e ON e.student_id = s.id AND e.session_id = ? AND e.status = 'active'
        JOIN classes c ON c.id = e.class_id
        LEFT JOIN sections sec ON sec.id = e.section_id
        WHERE s.id = ?
    ", [$sessionId, $studentId]);

    if (!$student) {
        set_flash('error', 'Student not found or not enrolled in the current session.');
        redirect('finance', 'fm-payment');
    }

    // Get outstanding charges (pending / overdue) with fee description
    $charges = db_fetch_all("
        SELECT sfc.id, sfc.fee_id, sfc.amount, sfc.paid_amount, sfc.due_date, sfc.status,
               sfc.occurrence_number, sfc.currency,
               f.description AS fee_description, f.fee_type
        FROM student_fee_charges sfc
        JOIN fees f ON f.id = sfc.fee_id
        WHERE sfc.student_id = ? AND sfc.status IN ('pending', 'overdue')
        ORDER BY sfc.due_date ASC, sfc.id ASC
    ", [$studentId]);

    // Get outstanding penalty charges
    $penalties = db_fetch_all("
        SELECT pc.id, pc.charge_id, pc.penalty_amount, pc.status, pc.applied_at,
               f.description AS fee_description
        FROM penalty_charges pc
        JOIN student_fee_charges sfc ON sfc.id = pc.charge_id
        JOIN fees f ON f.id = sfc.fee_id
        WHERE sfc.student_id = ? AND pc.status = 'pending'
        ORDER BY pc.applied_at ASC
    ", [$studentId]);

    // Calculate totals
    $totalOutstanding = 0;
    foreach ($charges as $ch) {
        $totalOutstanding += ($ch['amount'] - $ch['paid_amount']);
    }
    $totalPenalties = 0;
    foreach ($penalties as $p) {
        $totalPenalties += $p['penalty_amount'];
    }
    $grandTotal = $totalOutstanding + $totalPenalties;

    // Previous payments for this student (recent 5)
    $recentPayments = db_fetch_all("
        SELECT p.receipt_no, p.amount, p.method, p.payment_date, p.status
        FROM payments p
        WHERE p.student_id = ? AND p.status = 'completed'
        ORDER BY p.payment_date DESC
        LIMIT 5
    ", [$studentId]);

    $pageTitle = 'Record Payment — ' . $student['first_name'] . ' ' . $student['last_name'];

    ob_start();
    ?>
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <a href="<?= url('finance', 'fm-payment') ?>" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">Record Payment</h1>
                </div>
                <p class="text-sm text-gray-500 mt-1">Select charges and enter payment details</p>
            </div>
        </div>

        <!-- Student Info Card -->
        <div class="bg-primary-50 border border-primary-200 rounded-xl p-5">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-bold text-primary-900"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h2>
                    <div class="flex flex-wrap gap-x-6 gap-y-1 mt-2 text-sm text-primary-700">
                        <span><strong>Admission:</strong> <?= e($student['admission_no']) ?></span>
                        <span><strong>Class:</strong> <?= e($student['class_name']) ?><?= $student['section_name'] ? ' - ' . e($student['section_name']) : '' ?></span>
                        <?php if ($student['phone']): ?>
                            <span><strong>Phone:</strong> <?= e($student['phone']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs text-primary-600 uppercase tracking-wide font-medium">Total Outstanding</p>
                    <p class="text-2xl font-bold text-red-600 mt-1"><?= format_currency($grandTotal) ?></p>
                </div>
            </div>
        </div>

        <?php if (empty($charges) && empty($penalties)): ?>
            <!-- No outstanding charges -->
            <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
                <svg class="mx-auto w-16 h-16 text-green-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-700">No Outstanding Charges</h3>
                <p class="text-sm text-gray-500 mt-1">This student has no pending or overdue fee charges.</p>
                <a href="<?= url('finance', 'fm-payment') ?>" class="mt-4 inline-block text-primary-600 text-sm hover:underline">&larr; Back to student search</a>
            </div>
        <?php else: ?>

        <form method="POST" action="<?= url('finance', 'fm-payment-save') ?>" id="paymentForm">
            <?= csrf_field() ?>
            <input type="hidden" name="student_id" value="<?= $studentId ?>">

            <!-- Outstanding Fee Charges -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Outstanding Fee Charges</h2>
                    <label class="flex items-center gap-2 text-sm text-primary-600 cursor-pointer">
                        <input type="checkbox" id="selectAllCharges" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" checked>
                        Select All
                    </label>
                </div>
                <?php if (!empty($charges)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="w-10 px-4 py-2"></th>
                                <th class="text-left px-4 py-2 font-medium text-gray-600">Fee Description</th>
                                <th class="text-center px-4 py-2 font-medium text-gray-600">Due Date</th>
                                <th class="text-center px-4 py-2 font-medium text-gray-600">Status</th>
                                <th class="text-right px-4 py-2 font-medium text-gray-600">Amount</th>
                                <th class="text-right px-4 py-2 font-medium text-gray-600">Paid</th>
                                <th class="text-right px-4 py-2 font-medium text-gray-600">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($charges as $ch): ?>
                            <?php $balance = $ch['amount'] - $ch['paid_amount']; ?>
                            <tr class="hover:bg-gray-50 charge-row">
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="charge_ids[]" value="<?= $ch['id'] ?>"
                                           data-balance="<?= $balance ?>"
                                           class="charge-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500" checked>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900"><?= e($ch['fee_description']) ?></div>
                                    <?php if ($ch['occurrence_number'] > 1): ?>
                                        <span class="text-xs text-gray-400">Occurrence #<?= $ch['occurrence_number'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center text-gray-600"><?= format_date($ch['due_date']) ?></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $ch['status'] === 'overdue' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                        <?= ucfirst($ch['status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono"><?= format_currency($ch['amount']) ?></td>
                                <td class="px-4 py-3 text-right font-mono text-green-600"><?= format_currency($ch['paid_amount']) ?></td>
                                <td class="px-4 py-3 text-right font-mono font-semibold text-red-600"><?= format_currency($balance) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <?php if (!empty($penalties)): ?>
                <!-- Penalty charges section -->
                <div class="px-6 py-3 border-t bg-red-50">
                    <h3 class="text-sm font-semibold text-red-700 mb-2">Penalty Charges</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($penalties as $p): ?>
                            <tr class="hover:bg-red-50/50 charge-row">
                                <td class="w-10 px-4 py-3">
                                    <input type="checkbox" name="penalty_ids[]" value="<?= $p['id'] ?>"
                                           data-balance="<?= $p['penalty_amount'] ?>"
                                           class="charge-checkbox rounded border-gray-300 text-red-600 focus:ring-red-500" checked>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-red-700">Penalty — <?= e($p['fee_description']) ?></div>
                                    <span class="text-xs text-gray-400">Applied <?= format_date($p['applied_at']) ?></span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-semibold text-red-600" colspan="5">
                                    <?= format_currency($p['penalty_amount']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Selected Total -->
                <div class="px-6 py-4 border-t bg-gray-50 flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-600">Selected Total:</span>
                    <span id="selectedTotal" class="text-lg font-bold text-primary-800"><?= format_currency($grandTotal) ?></span>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="bg-white rounded-xl shadow-sm border p-6 mt-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Details</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount (ETB) <span class="text-red-500">*</span></label>
                        <input type="number" name="amount" id="paymentAmount" required step="0.01" min="0.01"
                               value="<?= number_format($grandTotal, 2, '.', '') ?>"
                               class="w-full rounded-lg border-gray-300 shadow-sm text-lg font-semibold focus:border-primary-500 focus:ring-primary-500">
                        <p class="text-xs text-gray-500 mt-1">
                            Enter exact amount received. Overpayment will be recorded as advance credit.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_date" required value="<?= date('Y-m-d') ?>"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                        <select name="method" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="cash">💵 Cash</option>
                            <option value="bank_transfer">🏦 Bank Transfer</option>
                            <option value="cheque">📝 Cheque</option>
                            <option value="gateway">📱 Online Gateway (Telebirr/Chapa)</option>
                            <option value="other">📋 Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reference / Transaction #</label>
                        <input type="text" name="reference" placeholder="Bank ref, cheque number, transaction ID..."
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" rows="2" placeholder="Any additional notes about this payment..."
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                    </div>
                </div>

                <!-- Payment Summary -->
                <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-green-700 font-medium">Selected Charges:</span>
                        <span id="summarySelected" class="font-semibold"><?= format_currency($grandTotal) ?></span>
                    </div>
                    <div class="flex items-center justify-between text-sm mt-1">
                        <span class="text-green-700 font-medium">Payment Amount:</span>
                        <span id="summaryPayment" class="font-semibold"><?= format_currency($grandTotal) ?></span>
                    </div>
                    <div class="flex items-center justify-between text-sm mt-1 pt-2 border-t border-green-300">
                        <span class="text-green-700 font-medium">Remaining Balance:</span>
                        <span id="summaryBalance" class="font-bold text-green-800"><?= format_currency(0) ?></span>
                    </div>
                    <div id="overpayNotice" class="hidden mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-700">
                        <strong>Note:</strong> Overpayment of <span id="overpayAmount"></span> will be recorded as advance credit.
                    </div>
                </div>

                <!-- Submit -->
                <div class="mt-6 flex items-center gap-3">
                    <button type="submit" id="submitBtn"
                            class="px-8 py-3 bg-green-700 text-white rounded-lg text-sm font-semibold hover:bg-green-800 transition shadow-sm">
                        <svg class="w-4 h-4 inline mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Process Payment &amp; Generate Invoice
                    </button>
                    <a href="<?= url('finance', 'fm-payment') ?>" class="px-6 py-3 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</a>
                </div>
            </div>
        </form>

        <!-- Recent Payments -->
        <?php if (!empty($recentPayments)): ?>
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h2 class="text-sm font-semibold text-gray-700">Recent Payments</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-4 py-2 text-xs font-medium text-gray-500">Receipt</th>
                        <th class="text-right px-4 py-2 text-xs font-medium text-gray-500">Amount</th>
                        <th class="text-center px-4 py-2 text-xs font-medium text-gray-500">Method</th>
                        <th class="text-center px-4 py-2 text-xs font-medium text-gray-500">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($recentPayments as $rp): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-xs"><?= e($rp['receipt_no']) ?></td>
                        <td class="px-4 py-2 text-right font-semibold text-green-600"><?= format_currency($rp['amount']) ?></td>
                        <td class="px-4 py-2 text-center capitalize"><?= e(str_replace('_', ' ', $rp['method'])) ?></td>
                        <td class="px-4 py-2 text-center text-gray-500"><?= format_date($rp['payment_date']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php endif; // end else (has charges) ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.charge-checkbox');
        const selectAll  = document.getElementById('selectAllCharges');
        const amountInput = document.getElementById('paymentAmount');
        const selectedTotalEl = document.getElementById('selectedTotal');
        const summarySelected = document.getElementById('summarySelected');
        const summaryPayment  = document.getElementById('summaryPayment');
        const summaryBalance  = document.getElementById('summaryBalance');
        const overpayNotice   = document.getElementById('overpayNotice');
        const overpayAmount   = document.getElementById('overpayAmount');

        function formatMoney(v) {
            return 'Br ' + Number(v).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function recalc() {
            let total = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) total += parseFloat(cb.dataset.balance) || 0;
            });
            selectedTotalEl.textContent = formatMoney(total);
            summarySelected.textContent = formatMoney(total);

            // Auto-set amount to selected total if user hasn't manually changed it
            if (!amountInput.dataset.userModified) {
                amountInput.value = total.toFixed(2);
            }

            updateSummary();
        }

        function updateSummary() {
            let selectedTotal = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) selectedTotal += parseFloat(cb.dataset.balance) || 0;
            });

            const payAmount = parseFloat(amountInput.value) || 0;
            summaryPayment.textContent = formatMoney(payAmount);

            const remaining = selectedTotal - payAmount;
            summaryBalance.textContent = formatMoney(Math.max(0, remaining));

            if (payAmount > selectedTotal && selectedTotal > 0) {
                const overpay = payAmount - selectedTotal;
                overpayNotice.classList.remove('hidden');
                overpayAmount.textContent = formatMoney(overpay);
                summaryBalance.textContent = formatMoney(0);
                summaryBalance.parentElement.querySelector('span:first-child').textContent = 'Advance Credit:';
                summaryBalance.textContent = '+' + formatMoney(overpay);
                summaryBalance.className = 'font-bold text-blue-700';
            } else {
                overpayNotice.classList.add('hidden');
                summaryBalance.parentElement.querySelector('span:first-child').textContent = 'Remaining Balance:';
                summaryBalance.className = 'font-bold ' + (remaining > 0 ? 'text-red-600' : 'text-green-800');
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => { cb.checked = this.checked; });
                amountInput.dataset.userModified = '';
                recalc();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                amountInput.dataset.userModified = '';
                recalc();
                // Update select all state
                if (selectAll) {
                    selectAll.checked = [...checkboxes].every(c => c.checked);
                }
            });
        });

        amountInput.addEventListener('input', function() {
            this.dataset.userModified = '1';
            updateSummary();
        });

        // Confirm before submit
        document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
            const amt = parseFloat(amountInput.value) || 0;
            const checked = document.querySelectorAll('.charge-checkbox:checked').length;
            if (checked === 0) {
                e.preventDefault();
                alert('Please select at least one charge to pay.');
                return;
            }
            if (amt <= 0) {
                e.preventDefault();
                alert('Please enter a valid payment amount.');
                return;
            }
            if (!confirm('Process payment of ' + formatMoney(amt) + '?\n\nThis will generate an invoice and update charge statuses.')) {
                e.preventDefault();
            }
        });
    });
    </script>
    <?php
    $content = ob_get_clean();
    require ROOT_PATH . '/templates/layout.php';
    return; // stop further execution
}


// ══════════════════════════════════════════════════════════════════════
// ── Mode 1: No student selected — search students with outstanding charges ──
// ══════════════════════════════════════════════════════════════════════

$search = trim($_GET['q'] ?? '');
$classFilter = input_int('class_id');
$page = max(1, input_int('page') ?: 1);
$perPage = 20;

// Get classes for filter dropdown
$classes = db_fetch_all("SELECT id, name FROM classes ORDER BY numeric_name, name");

// Build query for students with outstanding charges
$where = ["e.session_id = ?", "e.status = 'active'"];
$params = [$sessionId];

if ($search !== '') {
    $where[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_no LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($classFilter) {
    $where[] = "e.class_id = ?";
    $params[] = $classFilter;
}

$whereSQL = implode(' AND ', $where);

$total = db_fetch_value("
    SELECT COUNT(DISTINCT s.id)
    FROM students s
    JOIN enrollments e ON e.student_id = s.id
    WHERE {$whereSQL}
", $params);

$offset = ($page - 1) * $perPage;
$students = db_fetch_all("
    SELECT s.id, s.admission_no, s.first_name, s.last_name,
           c.name AS class_name, sec.name AS section_name,
           COALESCE(ch.outstanding_count, 0) AS outstanding_count,
           COALESCE(ch.outstanding_amount, 0) AS outstanding_amount,
           COALESCE(ch.overdue_count, 0) AS overdue_count
    FROM students s
    JOIN enrollments e ON e.student_id = s.id
    JOIN classes c ON c.id = e.class_id
    LEFT JOIN sections sec ON sec.id = e.section_id
    LEFT JOIN (
        SELECT student_id,
               SUM(CASE WHEN status IN ('pending','overdue') THEN 1 ELSE 0 END) AS outstanding_count,
               SUM(CASE WHEN status IN ('pending','overdue') THEN (amount - paid_amount) ELSE 0 END) AS outstanding_amount,
               SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) AS overdue_count
        FROM student_fee_charges
        GROUP BY student_id
    ) ch ON ch.student_id = s.id
    WHERE {$whereSQL}
    ORDER BY ch.outstanding_amount DESC, s.first_name, s.last_name
    LIMIT {$perPage} OFFSET {$offset}
", $params);

$totalPages = max(1, ceil($total / $perPage));

$pageTitle = 'Record Payment';

ob_start();
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Record Payment</h1>
            <p class="text-sm text-gray-500 mt-1">Search and select a student to process a fee payment</p>
        </div>
    </div>

    <!-- Search / Filter -->
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <form method="GET" action="<?= url('finance', 'fm-payment') ?>" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by name, admission number, or phone..."
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500"
                       autofocus>
            </div>
            <select name="class_id" class="rounded-lg border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">All Classes</option>
                <?php foreach ($classes as $cl): ?>
                    <option value="<?= $cl['id'] ?>" <?= $classFilter == $cl['id'] ? 'selected' : '' ?>><?= e($cl['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="px-5 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
                <svg class="w-4 h-4 inline mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Search
            </button>
            <?php if ($search || $classFilter): ?>
                <a href="<?= url('finance', 'fm-payment') ?>" class="px-4 py-2 text-gray-500 text-sm hover:text-gray-700">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Student List -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <?php if (empty($students)): ?>
            <div class="p-12 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="text-gray-500 text-sm">No students found. Try a different search.</p>
            </div>
        <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Student</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Class</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-600">Outstanding</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600">Amount Due</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($students as $st): ?>
                    <tr class="hover:bg-gray-50 <?= $st['overdue_count'] > 0 ? 'bg-red-50/30' : '' ?>">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900"><?= e($st['first_name'] . ' ' . $st['last_name']) ?></div>
                            <div class="text-xs text-gray-500"><?= e($st['admission_no']) ?></div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <?= e($st['class_name']) ?><?= $st['section_name'] ? ' - ' . e($st['section_name']) : '' ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($st['outstanding_count'] > 0): ?>
                                <span class="inline-flex items-center gap-1">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                        <?= $st['outstanding_count'] ?> charge<?= $st['outstanding_count'] != 1 ? 's' : '' ?>
                                    </span>
                                    <?php if ($st['overdue_count'] > 0): ?>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                            <?= $st['overdue_count'] ?> overdue
                                        </span>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="text-xs text-green-600">All clear</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <?php if ($st['outstanding_amount'] > 0): ?>
                                <span class="font-semibold text-red-600"><?= format_currency($st['outstanding_amount']) ?></span>
                            <?php else: ?>
                                <span class="text-green-600 text-xs">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <?php if ($st['outstanding_count'] > 0): ?>
                                <a href="<?= url('finance', 'fm-payment') ?>&student_id=<?= $st['id'] ?>"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-700 text-white rounded-lg text-xs font-medium hover:bg-green-800 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    Pay
                                </a>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">No dues</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center">
        <?php
        $baseUrl = url('finance', 'fm-payment') . ($search ? '&q=' . urlencode($search) : '') . ($classFilter ? '&class_id=' . $classFilter : '');
        echo render_pagination($page, $totalPages, $baseUrl);
        ?>
    </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
