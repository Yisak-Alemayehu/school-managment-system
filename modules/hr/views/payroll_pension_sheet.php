<?php
/**
 * HR — Pension Report Sheet (Print-ready, Ethiopian Pension format)
 */

require_once APP_ROOT . '/core/ethiopian_calendar.php';

$id = route_id();
if (!$id) { redirect(url('hr', 'payroll')); exit; }

$period = db_fetch_one("SELECT * FROM hr_payroll_periods WHERE id = ?", [$id]);
if (!$period) { set_flash('error', 'Payroll period not found.'); redirect(url('hr', 'payroll')); exit; }

$records = db_fetch_all(
    "SELECT pr.basic_salary, pr.employee_pension, pr.employer_pension,
            CONCAT(e.first_name, ' ', e.father_name, ' ', e.grandfather_name) AS full_name,
            e.employee_id AS emp_code, e.pension_number, e.tin_number
     FROM hr_payroll_records pr
     JOIN hr_employees e ON pr.employee_id = e.id
     WHERE pr.payroll_period_id = ?
     ORDER BY e.first_name",
    [$id]
);

$ecMonths = ec_month_names();
$monthName = $ecMonths[(int)$period['month_ec']] ?? $period['month_ec'];

$totalBasic    = array_sum(array_column($records, 'basic_salary'));
$totalEmpPen   = array_sum(array_column($records, 'employee_pension'));
$totalErPen    = array_sum(array_column($records, 'employer_pension'));
$totalPension  = $totalEmpPen + $totalErPen;

ob_start();
?>

<div class="max-w-4xl mx-auto space-y-4">
    <div class="flex justify-between items-center print:hidden">
        <a href="<?= url('hr', 'payroll-detail', $id) ?>" class="text-sm text-primary-600 hover:underline">&larr; Back to Payroll Detail</a>
        <div class="flex items-center gap-2">
            <a href="<?= url('hr', 'print-pension', $id) ?>" target="_blank" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">PDF</a>
            <a href="<?= url('hr', 'download-pension', $id) ?>" class="px-4 py-2 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 font-medium">Download</a>
            <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900 font-medium">Print</button>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6 print:border-0 print:shadow-none print:p-0">
        <div class="text-center border-b-2 border-gray-900 pb-3 mb-4">
            <h2 class="text-lg font-bold text-gray-900 uppercase">Urji Beri School</h2>
            <p class="text-sm text-gray-600">Private Organization Employees' Pension Contribution Report</p>
            <p class="text-sm font-semibold text-gray-700 mt-1"><?= e($monthName) ?> <?= e($period['year_ec']) ?> E.C.</p>
            <p class="text-xs text-gray-500 mt-0.5">Period: <?= e($period['start_date_gc']) ?> — <?= e($period['end_date_gc']) ?></p>
        </div>

        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase text-xs">#</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase text-xs">Emp. ID</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase text-xs">Full Name</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase text-xs">TIN</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase text-xs">Pension No.</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-600 uppercase text-xs">Basic Salary</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-600 uppercase text-xs">Employee (7%)</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-600 uppercase text-xs">Employer (11%)</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-600 uppercase text-xs">Total (18%)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php $n = 0; foreach ($records as $r): $n++; ?>
                <tr>
                    <td class="px-3 py-1.5 text-gray-500"><?= $n ?></td>
                    <td class="px-3 py-1.5 font-mono text-gray-600"><?= e($r['emp_code']) ?></td>
                    <td class="px-3 py-1.5 font-medium text-gray-900"><?= e($r['full_name']) ?></td>
                    <td class="px-3 py-1.5 font-mono text-gray-600"><?= e($r['tin_number'] ?? '—') ?></td>
                    <td class="px-3 py-1.5 font-mono text-gray-600"><?= e($r['pension_number'] ?? '—') ?></td>
                    <td class="px-3 py-1.5 text-right"><?= number_format($r['basic_salary'], 2) ?></td>
                    <td class="px-3 py-1.5 text-right"><?= number_format($r['employee_pension'], 2) ?></td>
                    <td class="px-3 py-1.5 text-right"><?= number_format($r['employer_pension'], 2) ?></td>
                    <td class="px-3 py-1.5 text-right font-medium"><?= number_format($r['employee_pension'] + $r['employer_pension'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="5" class="px-3 py-2 text-right uppercase text-gray-600">Totals</td>
                    <td class="px-3 py-2 text-right"><?= number_format($totalBasic, 2) ?></td>
                    <td class="px-3 py-2 text-right"><?= number_format($totalEmpPen, 2) ?></td>
                    <td class="px-3 py-2 text-right"><?= number_format($totalErPen, 2) ?></td>
                    <td class="px-3 py-2 text-right text-green-700"><?= number_format($totalPension, 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="grid grid-cols-3 gap-8 mt-8 text-xs text-gray-500 text-center">
            <div class="border-t border-gray-400 pt-2">Prepared By</div>
            <div class="border-t border-gray-400 pt-2">Finance Head</div>
            <div class="border-t border-gray-400 pt-2">Director</div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
