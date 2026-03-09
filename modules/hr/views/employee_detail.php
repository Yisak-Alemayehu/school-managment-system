<?php
/**
 * HR — Employee Detail View
 */

$id = route_id();
if (!$id) { redirect(url('hr', 'employees')); exit; }

$emp = db_fetch_one(
    "SELECT e.*, d.name AS department_name
     FROM hr_employees e
     LEFT JOIN hr_departments d ON e.department_id = d.id
     WHERE e.id = ? AND e.deleted_at IS NULL",
    [$id]
);
if (!$emp) { set_flash('error', 'Employee not found.'); redirect(url('hr', 'employees')); exit; }

$documents = db_fetch_all(
    "SELECT * FROM hr_employee_documents WHERE employee_id = ? ORDER BY uploaded_at DESC",
    [$id]
);

// Phase 2: Get recurring allowances
$allowances = db_fetch_all(
    "SELECT * FROM hr_employee_allowances WHERE employee_id = ? AND status = 'active' ORDER BY allowance_type, name",
    [$id]
);

$leaveBalances = db_fetch_all(
    "SELECT lt.name, lt.days_allowed AS max_days,
            COALESCE(SUM(CASE WHEN lr.status='approved' THEN lr.days END), 0) AS used
     FROM hr_leave_types lt
     LEFT JOIN hr_leave_requests lr ON lr.leave_type_id = lt.id AND lr.employee_id = ? AND lr.status='approved'
     WHERE lt.status = 'active'
     GROUP BY lt.id
     ORDER BY lt.name",
    [$id]
);

require_once APP_ROOT . '/core/ethiopian_calendar.php';
$ecHire = !empty($emp['hire_date_ec']) ? $emp['hire_date_ec'] : '';

// Phase 4: Attendance history (last 30 records)
$attendanceHistory = db_fetch_all(
    "SELECT date_gregorian, date_ec, status, check_in, check_out, notes AS remarks
     FROM hr_attendance WHERE employee_id = ? ORDER BY date_gregorian DESC LIMIT 30",
    [$id]
);

// Phase 4: Payroll history
$payrollHistory = db_fetch_all(
    "SELECT pr.*, pp.month_ec, pp.year_ec, pp.month_name_ec, pp.status AS period_status
     FROM hr_payroll_records pr
     JOIN hr_payroll_periods pp ON pr.payroll_period_id = pp.id
     WHERE pr.employee_id = ?
     ORDER BY pp.year_ec DESC, pp.month_ec DESC
     LIMIT 12",
    [$id]
);

// Phase 4: Leave request history
$leaveHistory = db_fetch_all(
    "SELECT lr.*, lt.name AS leave_type_name
     FROM hr_leave_requests lr
     JOIN hr_leave_types lt ON lr.leave_type_id = lt.id
     WHERE lr.employee_id = ?
     ORDER BY lr.created_at DESC
     LIMIT 20",
    [$id]
);

ob_start();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-start justify-between">
        <div>
            <a href="<?= url('hr', 'employees') ?>" class="text-sm text-primary-600 hover:underline">&larr; Back to Employees</a>
            <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text mt-1">
                <?= e($emp['first_name'] . ' ' . $emp['father_name'] . ' ' . $emp['grandfather_name']) ?>
            </h1>
            <p class="text-sm text-gray-500 dark:text-dark-muted"><?= e($emp['employee_id']) ?> &bull; <?= e($emp['position']) ?></p>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('hr', 'print-contract', $emp['id']) ?>" target="_blank" class="px-3 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Contract
            </a>
            <a href="<?= url('hr', 'download-contract', $emp['id']) ?>" class="px-3 py-2 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 font-medium">Download</a>
            <a href="<?= url('hr', 'employee-form', $emp['id']) ?>" class="px-3 py-2 bg-amber-600 text-white text-sm rounded-lg hover:bg-amber-700 font-medium">Edit</a>
            <form method="POST" action="<?= url('hr', 'employee-delete') ?>" onsubmit="return confirm('Delete this employee?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                <button class="px-3 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 font-medium">Delete</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Personal Info -->
            <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4">Personal Information</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div><dt class="text-gray-500 dark:text-dark-muted">Full Name</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= e($emp['first_name'] . ' ' . $emp['father_name'] . ' ' . $emp['grandfather_name']) ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Full Name (Amharic)</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= e(($emp['first_name_am'] ?? '') . ' ' . ($emp['father_name_am'] ?? '') . ' ' . ($emp['grandfather_name_am'] ?? '')) ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Gender</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= ucfirst($emp['gender']) ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Date of Birth (EC)</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= e($emp['date_of_birth_ec'] ?? '—') ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Phone</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= e($emp['phone'] ?? '—') ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Email</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= e($emp['email'] ?? '—') ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Address</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= e($emp['address'] ?? '—') ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Emergency Contact</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= e(($emp['emergency_contact_name'] ?? '') . ' — ' . ($emp['emergency_contact_phone'] ?? '')) ?></dd></div>
                </dl>
            </div>

            <!-- Employment Info -->
            <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4">Employment Details</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div><dt class="text-gray-500 dark:text-dark-muted">Employee ID</dt><dd class="font-mono font-medium text-gray-900 dark:text-dark-text"><?= e($emp['employee_id']) ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Department</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= e($emp['department_name'] ?? '—') ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Position</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= e($emp['position']) ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Employment Type</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= ucfirst(str_replace('_', ' ', $emp['employment_type'])) ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Hire Date (EC)</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= e($ecHire ?: '—') ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Hire Date (GC)</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= e($emp['hire_date_gc'] ?? '—') ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">TIN Number</dt><dd class="font-mono font-medium text-gray-900 dark:text-dark-text"><?= e($emp['tin_number'] ?? '—') ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Pension Number</dt><dd class="font-mono font-medium text-gray-900 dark:text-dark-text"><?= e($emp['pension_number'] ?? '—') ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Qualification</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= e($emp['qualification'] ?? '—') ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Bank Name</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= e($emp['bank_name'] ?? '—') ?></dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Bank Account</dt><dd class="font-mono font-medium text-gray-900 dark:text-dark-text"><?= e($emp['bank_account'] ?? '—') ?></dd></div>
                    <div>
                        <dt class="text-gray-500 dark:text-dark-muted">Status</dt>
                        <dd>
                            <?php $sc = ['active' => 'bg-green-100 text-green-700', 'left' => 'bg-gray-100 text-gray-600', 'suspended' => 'bg-red-100 text-red-700']; ?>
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $sc[$emp['status']] ?? 'bg-gray-100 text-gray-600' ?>"><?= ucfirst($emp['status']) ?></span>
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Salary Info -->
            <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4">Salary Breakdown</h2>
                <?php
                require_once APP_ROOT . '/core/payroll.php';
                $calc = payroll_calculate(
                    (float)($emp['basic_salary'] ?? 0),
                    (float)($emp['transport_allowance'] ?? 0),
                    (float)(($emp['position_allowance'] ?? 0) + ($emp['other_allowance'] ?? 0))
                );
                ?>
                <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                    <div><dt class="text-gray-500 dark:text-dark-muted">Basic Salary</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= number_format($emp['basic_salary'], 2) ?> Br</dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Transport Allowance</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= number_format($emp['transport_allowance'], 2) ?> Br</dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Position Allowance</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= number_format($emp['position_allowance'], 2) ?> Br</dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Other Allowances</dt><dd class="font-medium text-gray-900 dark:text-dark-text"><?= number_format($emp['other_allowance'], 2) ?> Br</dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Gross Salary</dt><dd class="font-bold text-gray-900 dark:text-dark-text"><?= number_format($calc['gross_salary'], 2) ?> Br</dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Income Tax</dt><dd class="font-medium text-red-600">(<?= number_format($calc['income_tax'], 2) ?>)</dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Pension (7%)</dt><dd class="font-medium text-red-600">(<?= number_format($calc['employee_pension'], 2) ?>)</dd></div>
                    <div><dt class="text-gray-500 dark:text-dark-muted">Other Deductions</dt><dd class="font-medium text-red-600">(<?= number_format($calc['other_deductions'], 2) ?>)</dd></div>
                    <div class="sm:col-span-3 border-t pt-3 dark:border-dark-border"><dt class="text-gray-500 dark:text-dark-muted">Net Salary</dt><dd class="font-bold text-lg text-green-700 dark:text-green-400"><?= number_format($calc['net_salary'], 2) ?> Br</dd></div>
                </dl>
            </div>

            <!-- Documents -->
            <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text">Documents</h2>
                    <button onclick="document.getElementById('docModal').classList.remove('hidden')" class="text-xs px-3 py-1.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700">Upload Document</button>
                </div>
                <?php if (empty($documents)): ?>
                <p class="text-sm text-gray-400">No documents uploaded.</p>
                <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($documents as $doc): ?>
                    <div class="flex items-center justify-between bg-gray-50 dark:bg-dark-bg rounded-lg px-3 py-2">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($doc['title']) ?></p>
                            <p class="text-xs text-gray-500"><?= ucfirst(str_replace('_', ' ', $doc['document_type'])) ?> &bull; <?= date('M d, Y', strtotime($doc['created_at'])) ?></p>
                        </div>
                        <div class="flex gap-2">
                            <a href="/uploads/hr/<?= e($doc['file_path']) ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs">View</a>
                            <form method="POST" action="<?= url('hr', 'employee-document-delete') ?>" onsubmit="return confirm('Delete document?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                                <input type="hidden" name="employee_id" value="<?= $emp['id'] ?>">
                                <button class="text-red-600 hover:text-red-800 text-xs">Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recurring Allowances (Phase 2) -->
            <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text">Recurring Allowances</h2>
                    <button onclick="document.getElementById('allowanceModal').classList.remove('hidden')" class="text-xs px-3 py-1.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700">Add Allowance</button>
                </div>
                <?php if (empty($allowances)): ?>
                <p class="text-sm text-gray-400">No recurring allowances configured. Fixed allowances from employee record are used.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500 dark:text-dark-muted uppercase">
                                <th class="text-left py-2 pr-3">Type</th>
                                <th class="text-left py-2 pr-3">Name</th>
                                <th class="text-right py-2 pr-3">Amount</th>
                                <th class="text-center py-2 pr-3">Taxable</th>
                                <th class="text-center py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                            <?php $totalAllowance = 0; foreach ($allowances as $al): $totalAllowance += (float)$al['amount']; ?>
                            <tr>
                                <td class="py-2 pr-3">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-700"><?= ucfirst($al['allowance_type']) ?></span>
                                </td>
                                <td class="py-2 pr-3 font-medium text-gray-900 dark:text-dark-text"><?= e($al['name']) ?></td>
                                <td class="py-2 pr-3 text-right font-mono"><?= number_format($al['amount'], 2) ?></td>
                                <td class="py-2 pr-3 text-center"><?= $al['is_taxable'] ? 'Yes' : 'No' ?></td>
                                <td class="py-2 text-center">
                                    <form method="POST" action="<?= url('hr', 'allowance-delete') ?>" onsubmit="return confirm('Remove this allowance?');" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $al['id'] ?>">
                                        <button class="text-red-600 hover:text-red-800 text-xs">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-200 dark:border-dark-border">
                                <td colspan="2" class="py-2 pr-3 font-semibold text-gray-900 dark:text-dark-text">Total</td>
                                <td class="py-2 pr-3 text-right font-mono font-bold"><?= number_format($totalAllowance, 2) ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Photo -->
            <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5 text-center">
                <?php if (!empty($emp['photo'])): ?>
                <img src="/uploads/hr/<?= e($emp['photo']) ?>" class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-gray-100 dark:border-dark-border" alt="Photo">
                <?php else: ?>
                <div class="w-32 h-32 rounded-full mx-auto bg-gray-200 dark:bg-dark-border flex items-center justify-center">
                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <?php endif; ?>
                <p class="mt-3 font-semibold text-gray-900 dark:text-dark-text"><?= e($emp['first_name'] . ' ' . $emp['father_name']) ?></p>
                <p class="text-sm text-gray-500 dark:text-dark-muted"><?= e($emp['position']) ?></p>
            </div>

            <!-- Leave Balances -->
            <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4">Leave Balances</h2>
                <div class="space-y-3">
                    <?php foreach ($leaveBalances as $lb): $remaining = $lb['max_days'] - $lb['used']; ?>
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-600 dark:text-dark-muted"><?= e($lb['name']) ?></span>
                            <span class="font-medium text-gray-900 dark:text-dark-text"><?= $remaining ?>/<?= $lb['max_days'] ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-dark-border">
                            <?php $pct = $lb['max_days'] > 0 ? min(100, ($lb['used'] / $lb['max_days']) * 100) : 0; ?>
                            <div class="bg-primary-600 h-2 rounded-full" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($leaveBalances)): ?>
                    <p class="text-xs text-gray-400">No leave types configured.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ History Tabs (Phase 4) ═══ -->
    <div x-data="{ historyTab: 'attendance' }" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border">
        <div class="border-b border-gray-200 dark:border-dark-border px-5 pt-4">
            <nav class="flex gap-4 -mb-px">
                <button @click="historyTab = 'attendance'" :class="historyTab === 'attendance' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="pb-3 border-b-2 text-sm font-medium transition-colors">Attendance History</button>
                <button @click="historyTab = 'payroll'" :class="historyTab === 'payroll' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="pb-3 border-b-2 text-sm font-medium transition-colors">Payroll History</button>
                <button @click="historyTab = 'leave'" :class="historyTab === 'leave' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="pb-3 border-b-2 text-sm font-medium transition-colors">Leave History</button>
            </nav>
        </div>
        <div class="p-5">
            <!-- Attendance Tab -->
            <div x-show="historyTab === 'attendance'" x-transition>
                <?php if (empty($attendanceHistory)): ?>
                <p class="text-sm text-gray-400">No attendance records found.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm divide-y divide-gray-100 dark:divide-dark-border">
                        <thead>
                            <tr class="text-xs text-gray-500 uppercase">
                                <th class="text-left py-2 pr-3">Date (EC)</th>
                                <th class="text-left py-2 pr-3">Date (GC)</th>
                                <th class="text-center py-2 pr-3">Status</th>
                                <th class="text-left py-2 pr-3">Check In</th>
                                <th class="text-left py-2 pr-3">Check Out</th>
                                <th class="text-left py-2">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-dark-border">
                            <?php foreach ($attendanceHistory as $att):
                                $attColors = ['present'=>'bg-green-100 text-green-700','absent'=>'bg-red-100 text-red-700','late'=>'bg-amber-100 text-amber-700','half_day'=>'bg-blue-100 text-blue-700','leave'=>'bg-purple-100 text-purple-700'];
                            ?>
                            <tr>
                                <td class="py-2 pr-3 font-mono"><?= e($att['date_ec'] ?? '—') ?></td>
                                <td class="py-2 pr-3 font-mono text-gray-500"><?= e($att['date_gregorian']) ?></td>
                                <td class="py-2 pr-3 text-center">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $attColors[$att['status']] ?? 'bg-gray-100 text-gray-600' ?>"><?= ucfirst(str_replace('_', ' ', $att['status'])) ?></span>
                                </td>
                                <td class="py-2 pr-3 text-gray-500"><?= e($att['check_in'] ?? '—') ?></td>
                                <td class="py-2 pr-3 text-gray-500"><?= e($att['check_out'] ?? '—') ?></td>
                                <td class="py-2 text-gray-400 text-xs"><?= e($att['remarks'] ?? '') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-400 mt-2">Showing last 30 records</p>
                <?php endif; ?>
            </div>

            <!-- Payroll Tab -->
            <div x-show="historyTab === 'payroll'" x-transition>
                <?php if (empty($payrollHistory)): ?>
                <p class="text-sm text-gray-400">No payroll records found.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm divide-y divide-gray-100 dark:divide-dark-border">
                        <thead>
                            <tr class="text-xs text-gray-500 uppercase">
                                <th class="text-left py-2 pr-3">Period</th>
                                <th class="text-right py-2 pr-3">Gross</th>
                                <th class="text-right py-2 pr-3">Tax</th>
                                <th class="text-right py-2 pr-3">Pension</th>
                                <th class="text-right py-2 pr-3">Net</th>
                                <th class="text-center py-2">Payslip</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-dark-border">
                            <?php foreach ($payrollHistory as $pr): ?>
                            <tr>
                                <td class="py-2 pr-3 font-medium text-gray-900 dark:text-dark-text"><?= e(($pr['month_name_ec'] ?? $pr['month_ec']) . ' ' . $pr['year_ec']) ?></td>
                                <td class="py-2 pr-3 text-right"><?= number_format($pr['gross_salary'], 2) ?></td>
                                <td class="py-2 pr-3 text-right text-red-600"><?= number_format($pr['income_tax'], 2) ?></td>
                                <td class="py-2 pr-3 text-right text-amber-600"><?= number_format($pr['employee_pension'], 2) ?></td>
                                <td class="py-2 pr-3 text-right font-bold text-green-700"><?= number_format($pr['net_salary'], 2) ?></td>
                                <td class="py-2 text-center">
                                    <a href="<?= url('hr', 'payslip', $pr['id']) ?>" class="text-primary-600 hover:text-primary-800 text-xs font-medium">View</a>
                                    <a href="<?= url('hr', 'print-payslip', $pr['id']) ?>" target="_blank" class="text-gray-500 hover:text-gray-700 text-xs ml-1">PDF</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-400 mt-2">Showing last 12 periods</p>
                <?php endif; ?>
            </div>

            <!-- Leave Tab -->
            <div x-show="historyTab === 'leave'" x-transition>
                <?php if (empty($leaveHistory)): ?>
                <p class="text-sm text-gray-400">No leave requests found.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm divide-y divide-gray-100 dark:divide-dark-border">
                        <thead>
                            <tr class="text-xs text-gray-500 uppercase">
                                <th class="text-left py-2 pr-3">Leave Type</th>
                                <th class="text-left py-2 pr-3">From</th>
                                <th class="text-left py-2 pr-3">To</th>
                                <th class="text-center py-2 pr-3">Days</th>
                                <th class="text-center py-2 pr-3">Status</th>
                                <th class="text-left py-2">Reason</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-dark-border">
                            <?php foreach ($leaveHistory as $lh):
                                $lhColors = ['pending'=>'bg-amber-100 text-amber-700','approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700'];
                            ?>
                            <tr>
                                <td class="py-2 pr-3 font-medium text-gray-900 dark:text-dark-text"><?= e($lh['leave_type_name']) ?></td>
                                <td class="py-2 pr-3 font-mono text-gray-500"><?= e($lh['start_date'] ?? '') ?></td>
                                <td class="py-2 pr-3 font-mono text-gray-500"><?= e($lh['end_date'] ?? '') ?></td>
                                <td class="py-2 pr-3 text-center font-medium"><?= (int)$lh['days'] ?></td>
                                <td class="py-2 pr-3 text-center">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $lhColors[$lh['status']] ?? 'bg-gray-100 text-gray-600' ?>"><?= ucfirst($lh['status']) ?></span>
                                </td>
                                <td class="py-2 text-gray-400 text-xs max-w-[200px] truncate"><?= e($lh['reason'] ?? '—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-400 mt-2">Showing last 20 requests</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Document Upload Modal -->
<div id="docModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-dark-card rounded-xl w-full max-w-md p-6 m-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-dark-text mb-4">Upload Document</h3>
        <form method="POST" action="<?= url('hr', 'employee-document-save') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="employee_id" value="<?= $emp['id'] ?>">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Title *</label>
                    <input type="text" name="title" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Type</label>
                    <select name="document_type" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <option value="contract">Contract</option>
                        <option value="id_copy">ID Copy</option>
                        <option value="certificate">Certificate</option>
                        <option value="tin_certificate">TIN Certificate</option>
                        <option value="pension_id">Pension ID</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">File * (PDF, JPEG, PNG, DOC — Max 5MB)</label>
                    <input type="file" name="document" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" onclick="document.getElementById('docModal').classList.add('hidden')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">Upload</button>
            </div>
        </form>
    </div>
</div>

<!-- Allowance Modal (Phase 2) -->
<div id="allowanceModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-dark-card rounded-xl w-full max-w-md p-6 m-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-dark-text mb-4">Add Recurring Allowance</h3>
        <form method="POST" action="<?= url('hr', 'allowance-save') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="employee_id" value="<?= $emp['id'] ?>">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Type *</label>
                    <select name="allowance_type" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <option value="transport">Transport</option>
                        <option value="housing">Housing</option>
                        <option value="responsibility">Responsibility</option>
                        <option value="hardship">Hardship</option>
                        <option value="position">Position</option>
                        <option value="overtime">Overtime</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Name *</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text" placeholder="e.g. Monthly Transport">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Amount (Birr) *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Start Date</label>
                        <input type="date" name="start_date" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">End Date</label>
                        <input type="date" name="end_date" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                    </div>
                </div>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_taxable" value="1" checked class="rounded border-gray-300">
                        Taxable
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_permanent" value="1" checked class="rounded border-gray-300">
                        Permanent (auto-include)
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" onclick="document.getElementById('allowanceModal').classList.add('hidden')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">Save Allowance</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
