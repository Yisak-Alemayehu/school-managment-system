<?php
/**
 * HR — Individual Payslip View (Print-ready)
 */

require_once APP_ROOT . '/core/ethiopian_calendar.php';
require_once APP_ROOT . '/core/payroll.php';

$id = route_id();
if (!$id) { redirect(url('hr', 'payroll')); exit; }

$record = db_fetch_one(
    "SELECT pr.*, 
            e.first_name, e.father_name, e.grandfather_name,
            e.first_name_am, e.father_name_am, e.grandfather_name_am,
            e.employee_id AS emp_code, e.position, e.bank_name, e.bank_account, e.tin_number, e.pension_number,
            d.name AS department_name,
            pp.month_ec, pp.year_ec, pp.start_date_gc, pp.end_date_gc, pp.working_days
     FROM hr_payroll_records pr
     JOIN hr_employees e ON pr.employee_id = e.id
     JOIN hr_payroll_periods pp ON pr.payroll_period_id = pp.id
     LEFT JOIN hr_departments d ON e.department_id = d.id
     WHERE pr.id = ?",
    [$id]
);

if (!$record) { set_flash('error', 'Payslip not found.'); redirect(url('hr', 'payroll')); exit; }

$ecMonths = ec_month_names();
$monthName = $ecMonths[(int)$record['month_ec']] ?? $record['month_ec'];
$amountInWords = payroll_amount_in_words($record['net_salary']);

ob_start();
?>

<div class="max-w-2xl mx-auto space-y-4">
    <div class="flex justify-between items-center print:hidden">
        <a href="<?= url('hr', 'payroll-detail', $record['payroll_period_id']) ?>" class="text-sm text-primary-600 hover:underline">&larr; Back to Payroll Detail</a>
        <div class="flex items-center gap-2">
            <a href="<?= url('hr', 'print-payslip', $id) ?>" target="_blank" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                PDF
            </a>
            <a href="<?= url('hr', 'download-payslip', $id) ?>" class="px-4 py-2 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 font-medium">Download</a>
            <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900 font-medium">Print Payslip</button>
        </div>
    </div>

    <!-- Payslip -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 print:border-0 print:shadow-none print:p-0">
        <!-- School Header -->
        <div class="text-center border-b-2 border-gray-900 pb-3 mb-4">
            <h2 class="text-lg font-bold text-gray-900 uppercase">Urji Beri School</h2>
            <p class="text-sm text-gray-600">Monthly Salary Payslip</p>
            <p class="text-sm font-semibold text-gray-700 mt-1"><?= e($monthName) ?> <?= e($record['year_ec']) ?> E.C.</p>
        </div>

        <!-- Employee Info -->
        <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm mb-4">
            <div class="flex justify-between"><span class="text-gray-500">Employee ID:</span><span class="font-medium"><?= e($record['emp_code']) ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">Department:</span><span class="font-medium"><?= e($record['department_name'] ?? '—') ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">Name:</span><span class="font-medium"><?= e($record['first_name'] . ' ' . $record['father_name'] . ' ' . $record['grandfather_name']) ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">Position:</span><span class="font-medium"><?= e($record['position']) ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">TIN:</span><span class="font-mono font-medium"><?= e($record['tin_number'] ?? '—') ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">Bank:</span><span class="font-medium"><?= e(($record['bank_name'] ?? '') . ' — ' . ($record['bank_account'] ?? '')) ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">Pay Period:</span><span class="font-medium"><?= e($record['start_date_gc']) ?> — <?= e($record['end_date_gc']) ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">Working Days:</span><span class="font-medium"><?= (int)$record['working_days'] ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">Days Worked:</span><span class="font-medium"><?= (int)($record['days_worked'] ?? $record['working_days']) ?></span></div>
        </div>

        <!-- Earnings & Deductions -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <!-- Earnings -->
            <div>
                <h4 class="text-xs font-semibold text-gray-600 uppercase border-b border-gray-300 pb-1 mb-2">Earnings</h4>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between"><span>Basic Salary</span><span class="font-medium"><?= number_format($record['basic_salary'], 2) ?></span></div>
                    <div class="flex justify-between"><span>Transport Allowance</span><span class="font-medium"><?= number_format($record['transport_allowance'], 2) ?></span></div>
                    <div class="flex justify-between"><span>Position Allowance</span><span class="font-medium"><?= number_format($record['position_allowance'], 2) ?></span></div>
                    <div class="flex justify-between"><span>Other Allowance</span><span class="font-medium"><?= number_format($record['other_allowance'], 2) ?></span></div>
                    <?php if (($record['overtime'] ?? 0) > 0): ?>
                    <div class="flex justify-between"><span>Overtime</span><span class="font-medium"><?= number_format($record['overtime'], 2) ?></span></div>
                    <?php endif; ?>
                    <div class="flex justify-between border-t border-gray-300 pt-1 mt-1 font-semibold">
                        <span>Gross Salary</span><span><?= number_format($record['gross_salary'], 2) ?></span>
                    </div>
                </div>
            </div>

            <!-- Deductions -->
            <div>
                <h4 class="text-xs font-semibold text-gray-600 uppercase border-b border-gray-300 pb-1 mb-2">Deductions</h4>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between"><span>Income Tax</span><span class="font-medium text-red-600"><?= number_format($record['income_tax'], 2) ?></span></div>
                    <div class="flex justify-between"><span>Pension (Employee 7%)</span><span class="font-medium text-red-600"><?= number_format($record['employee_pension'], 2) ?></span></div>
                    <div class="flex justify-between"><span>Other Deductions</span><span class="font-medium text-red-600"><?= number_format($record['other_deductions'], 2) ?></span></div>
                    <div class="flex justify-between border-t border-gray-300 pt-1 mt-1 font-semibold text-red-600">
                        <span>Total Deductions</span><span><?= number_format($record['income_tax'] + $record['employee_pension'] + $record['other_deductions'], 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Net Pay -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <p class="text-sm text-green-700 font-medium">Net Pay</p>
            <p class="text-2xl font-bold text-green-800"><?= number_format($record['net_salary'], 2) ?> Br</p>
            <p class="text-xs text-green-600 mt-1"><?= e($amountInWords) ?></p>
        </div>

        <!-- Employer Contribution (info only) -->
        <div class="mt-4 text-xs text-gray-400 border-t border-gray-200 pt-3">
            <p>Employer Pension Contribution (11%): <?= number_format($record['employer_pension'], 2) ?> Br</p>
        </div>

        <!-- Signature Lines -->
        <div class="grid grid-cols-3 gap-8 mt-8 text-xs text-gray-500 text-center">
            <div class="border-t border-gray-400 pt-2">Prepared By</div>
            <div class="border-t border-gray-400 pt-2">Approved By</div>
            <div class="border-t border-gray-400 pt-2">Employee Signature</div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
