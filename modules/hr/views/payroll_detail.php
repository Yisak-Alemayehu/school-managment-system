<?php
/**
 * HR — Payroll Period Detail View
 */

require_once APP_ROOT . '/core/ethiopian_calendar.php';

$id = route_id();
if (!$id) { redirect(url('hr', 'payroll')); exit; }

$period = db_fetch_one("SELECT * FROM hr_payroll_periods WHERE id = ?", [$id]);
if (!$period) { set_flash('error', 'Payroll period not found.'); redirect(url('hr', 'payroll')); exit; }

$records = db_fetch_all(
    "SELECT pr.*, 
            CONCAT(e.first_name, ' ', e.father_name) AS employee_name,
            e.employee_id AS emp_code, e.bank_name, e.bank_account,
            d.name AS department_name
     FROM hr_payroll_records pr
     JOIN hr_employees e ON pr.employee_id = e.id
     LEFT JOIN hr_departments d ON e.department_id = d.id
     ORDER BY d.name, e.first_name",
    []
);

// Filter by period
$records = db_fetch_all(
    "SELECT pr.*, 
            CONCAT(e.first_name, ' ', e.father_name) AS employee_name,
            e.employee_id AS emp_code, e.bank_name, e.bank_account,
            d.name AS department_name
     FROM hr_payroll_records pr
     JOIN hr_employees e ON pr.employee_id = e.id
     LEFT JOIN hr_departments d ON e.department_id = d.id
     WHERE pr.payroll_period_id = ?
     ORDER BY d.name, e.first_name",
    [$id]
);

$ecMonths = ec_month_names();
$monthName = $ecMonths[(int)$period['month_ec']]['en'] ?? $period['month_ec'];

// Totals
$totals = ['basic_salary' => 0, 'transport_allowance' => 0, 'other_allowance' => 0,
           'overtime' => 0, 'gross_salary' => 0, 'income_tax' => 0, 'employee_pension' => 0, 'employer_pension' => 0,
           'other_deductions' => 0, 'net_salary' => 0];
foreach ($records as $r) {
    foreach ($totals as $k => &$v) { $v += (float)$r[$k]; }
}
unset($v);

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <a href="<?= url('hr', 'payroll') ?>" class="text-sm text-primary-600 hover:underline">&larr; Back to Payroll</a>
            <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text mt-1">
                Payroll: <?= e($monthName) ?> <?= e($period['year_ec']) ?>
            </h1>
            <p class="text-sm text-gray-500 dark:text-dark-muted"><?= e($period['start_date']) ?> — <?= e($period['end_date']) ?> &bull; <?= ucfirst($period['status']) ?></p>
        </div>
        <div class="flex items-center gap-2 print:hidden">
            <!-- PDF Downloads Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    PDF Reports
                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 bg-white dark:bg-dark-card rounded-xl shadow-lg border border-gray-200 dark:border-dark-border z-50 py-1">
                    <a href="<?= url('hr', 'print-tax', $id) ?>" target="_blank" class="block px-4 py-2 text-sm text-gray-700 dark:text-dark-text hover:bg-gray-100 dark:hover:bg-dark-bg">
                        <span class="font-medium">Income Tax Declaration</span>
                        <span class="block text-xs text-gray-400">Schedule A — Government Form</span>
                    </a>
                    <a href="<?= url('hr', 'print-pension', $id) ?>" target="_blank" class="block px-4 py-2 text-sm text-gray-700 dark:text-dark-text hover:bg-gray-100 dark:hover:bg-dark-bg">
                        <span class="font-medium">Pension Contribution</span>
                        <span class="block text-xs text-gray-400">Private Org Pension Form</span>
                    </a>
                    <a href="<?= url('hr', 'print-bank', $id) ?>" target="_blank" class="block px-4 py-2 text-sm text-gray-700 dark:text-dark-text hover:bg-gray-100 dark:hover:bg-dark-bg">
                        <span class="font-medium">Bank Transfer Sheet</span>
                        <span class="block text-xs text-gray-400">Salary transfer list</span>
                    </a>
                    <div class="border-t border-gray-100 dark:border-dark-border my-1"></div>
                    <a href="<?= url('hr', 'download-tax', $id) ?>" class="block px-4 py-2 text-sm text-gray-500 dark:text-dark-muted hover:bg-gray-100 dark:hover:bg-dark-bg">
                        Download Tax PDF
                    </a>
                    <a href="<?= url('hr', 'download-pension', $id) ?>" class="block px-4 py-2 text-sm text-gray-500 dark:text-dark-muted hover:bg-gray-100 dark:hover:bg-dark-bg">
                        Download Pension PDF
                    </a>
                    <a href="<?= url('hr', 'download-bank', $id) ?>" class="block px-4 py-2 text-sm text-gray-500 dark:text-dark-muted hover:bg-gray-100 dark:hover:bg-dark-bg">
                        Download Bank Sheet PDF
                    </a>
                </div>
            </div>
            <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900 font-medium">Print</button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 print:hidden">
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 text-center">
            <p class="text-xs text-gray-500 dark:text-dark-muted">Employees</p>
            <p class="text-lg font-bold text-gray-900 dark:text-dark-text"><?= count($records) ?></p>
        </div>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 text-center">
            <p class="text-xs text-gray-500 dark:text-dark-muted">Total Gross</p>
            <p class="text-lg font-bold text-gray-900 dark:text-dark-text"><?= number_format($totals['gross_salary'], 2) ?></p>
        </div>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 text-center">
            <p class="text-xs text-gray-500 dark:text-dark-muted">Total Tax</p>
            <p class="text-lg font-bold text-red-600"><?= number_format($totals['income_tax'], 2) ?></p>
        </div>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 text-center">
            <p class="text-xs text-gray-500 dark:text-dark-muted">Total Pension</p>
            <p class="text-lg font-bold text-amber-600"><?= number_format($totals['employee_pension'] + $totals['employer_pension'], 2) ?></p>
        </div>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 text-center">
            <p class="text-xs text-gray-500 dark:text-dark-muted">Total Net</p>
            <p class="text-lg font-bold text-green-700"><?= number_format($totals['net_salary'], 2) ?></p>
        </div>
    </div>

    <!-- Payroll Table -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border text-xs">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-dark-muted uppercase">#</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-dark-muted uppercase">Employee</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-dark-muted uppercase">Dept</th>
                        <th class="px-3 py-2 text-right font-semibold text-gray-600 dark:text-dark-muted uppercase">Basic</th>
                        <th class="px-3 py-2 text-right font-semibold text-gray-600 dark:text-dark-muted uppercase">Transp</th>
                        <th class="px-3 py-2 text-right font-semibold text-gray-600 dark:text-dark-muted uppercase">Other Allow</th>
                        <th class="px-3 py-2 text-right font-semibold text-gray-600 dark:text-dark-muted uppercase">OT</th>
                        <th class="px-3 py-2 text-right font-semibold text-gray-600 dark:text-dark-muted uppercase">Gross</th>
                        <th class="px-3 py-2 text-right font-semibold text-red-600 uppercase">Tax</th>
                        <th class="px-3 py-2 text-right font-semibold text-amber-600 uppercase">Pension(7%)</th>
                        <th class="px-3 py-2 text-right font-semibold text-gray-600 dark:text-dark-muted uppercase">Other Ded.</th>
                        <th class="px-3 py-2 text-right font-semibold text-green-600 uppercase">Net</th>
                        <th class="px-3 py-2 text-center font-semibold text-gray-600 dark:text-dark-muted uppercase print:hidden">Payslip</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php $n = 0; foreach ($records as $r): $n++; ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg">
                        <td class="px-3 py-2 text-gray-500"><?= $n ?></td>
                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-dark-text whitespace-nowrap"><?= e($r['employee_name']) ?></td>
                        <td class="px-3 py-2 text-gray-500"><?= e($r['department_name'] ?? '—') ?></td>
                        <td class="px-3 py-2 text-right"><?= number_format($r['basic_salary'], 2) ?></td>
                        <td class="px-3 py-2 text-right"><?= number_format($r['transport_allowance'], 2) ?></td>
                        <td class="px-3 py-2 text-right"><?= number_format($r['other_allowance'], 2) ?></td>
                        <td class="px-3 py-2 text-right"><?= number_format($r['overtime'] ?? 0, 2) ?></td>
                        <td class="px-3 py-2 text-right font-medium"><?= number_format($r['gross_salary'], 2) ?></td>
                        <td class="px-3 py-2 text-right text-red-600"><?= number_format($r['income_tax'], 2) ?></td>
                        <td class="px-3 py-2 text-right text-amber-600"><?= number_format($r['employee_pension'], 2) ?></td>
                        <td class="px-3 py-2 text-right"><?= number_format($r['other_deductions'], 2) ?></td>
                        <td class="px-3 py-2 text-right font-bold text-green-700"><?= number_format($r['net_salary'], 2) ?></td>
                        <td class="px-3 py-2 text-center print:hidden">
                            <a href="<?= url('hr', 'payslip', $r['id']) ?>" class="text-primary-600 hover:text-primary-800 font-medium">View</a>
                            <a href="<?= url('hr', 'print-payslip', $r['id']) ?>" target="_blank" class="text-gray-500 hover:text-gray-700 font-medium ml-1" title="PDF">PDF</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-dark-bg font-semibold">
                    <tr>
                        <td colspan="3" class="px-3 py-2 text-right uppercase text-gray-600">Totals</td>
                        <td class="px-3 py-2 text-right"><?= number_format($totals['basic_salary'], 2) ?></td>
                        <td class="px-3 py-2 text-right"><?= number_format($totals['transport_allowance'], 2) ?></td>
                        <td class="px-3 py-2 text-right"><?= number_format($totals['other_allowance'], 2) ?></td>
                        <td class="px-3 py-2 text-right"><?= number_format($totals['overtime'], 2) ?></td>
                        <td class="px-3 py-2 text-right"><?= number_format($totals['gross_salary'], 2) ?></td>
                        <td class="px-3 py-2 text-right text-red-600"><?= number_format($totals['income_tax'], 2) ?></td>
                        <td class="px-3 py-2 text-right text-amber-600"><?= number_format($totals['employee_pension'], 2) ?></td>
                        <td class="px-3 py-2 text-right"><?= number_format($totals['other_deductions'], 2) ?></td>
                        <td class="px-3 py-2 text-right text-green-700"><?= number_format($totals['net_salary'], 2) ?></td>
                        <td class="print:hidden"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
