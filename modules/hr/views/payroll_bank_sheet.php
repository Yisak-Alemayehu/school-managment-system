<?php
/**
 * HR — Payroll Bank Sheet (Print-ready)
 */

require_once APP_ROOT . '/core/ethiopian_calendar.php';

$id = route_id();
if (!$id) { redirect(url('hr', 'payroll')); exit; }

$period = db_fetch_one("SELECT * FROM hr_payroll_periods WHERE id = ?", [$id]);
if (!$period) { set_flash('error', 'Payroll period not found.'); redirect(url('hr', 'payroll')); exit; }

$records = db_fetch_all(
    "SELECT pr.net_salary,
            CONCAT(e.first_name, ' ', e.father_name, ' ', e.grandfather_name) AS full_name,
            e.employee_id AS emp_code, e.bank_name, e.bank_account
     FROM hr_payroll_records pr
     JOIN hr_employees e ON pr.employee_id = e.id
     WHERE pr.payroll_period_id = ?
     ORDER BY e.bank_name, e.first_name",
    [$id]
);

$ecMonths = ec_month_names();
$monthName = $ecMonths[(int)$period['month_ec']] ?? $period['month_ec'];
$totalNet = array_sum(array_column($records, 'net_salary'));

ob_start();
?>

<div class="max-w-4xl mx-auto space-y-4">
    <div class="flex justify-between items-center print:hidden">
        <a href="<?= url('hr', 'payroll-detail', $id) ?>" class="text-sm text-primary-600 hover:underline">&larr; Back to Payroll Detail</a>
        <div class="flex items-center gap-2">
            <a href="<?= url('hr', 'print-bank', $id) ?>" target="_blank" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">PDF</a>
            <a href="<?= url('hr', 'download-bank', $id) ?>" class="px-4 py-2 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 font-medium">Download</a>
            <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900 font-medium">Print</button>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6 print:border-0 print:shadow-none print:p-0">
        <div class="text-center border-b-2 border-gray-900 pb-3 mb-4">
            <h2 class="text-lg font-bold text-gray-900 uppercase">Urji Beri School</h2>
            <p class="text-sm text-gray-600">Bank Transfer Sheet</p>
            <p class="text-sm font-semibold text-gray-700 mt-1"><?= e($monthName) ?> <?= e($period['year_ec']) ?> E.C.</p>
        </div>

        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase text-xs">#</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase text-xs">Emp. ID</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase text-xs">Full Name</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase text-xs">Bank</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase text-xs">Account No.</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-600 uppercase text-xs">Net Amount (Br)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php $n = 0; foreach ($records as $r): $n++; ?>
                <tr>
                    <td class="px-3 py-1.5 text-gray-500"><?= $n ?></td>
                    <td class="px-3 py-1.5 font-mono text-gray-600"><?= e($r['emp_code']) ?></td>
                    <td class="px-3 py-1.5 font-medium text-gray-900"><?= e($r['full_name']) ?></td>
                    <td class="px-3 py-1.5 text-gray-600"><?= e($r['bank_name'] ?? '—') ?></td>
                    <td class="px-3 py-1.5 font-mono text-gray-600"><?= e($r['bank_account'] ?? '—') ?></td>
                    <td class="px-3 py-1.5 text-right font-medium"><?= number_format($r['net_salary'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="5" class="px-3 py-2 text-right uppercase text-gray-600">Total</td>
                    <td class="px-3 py-2 text-right text-green-700"><?= number_format($totalNet, 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="grid grid-cols-3 gap-8 mt-8 text-xs text-gray-500 text-center">
            <div class="border-t border-gray-400 pt-2">Prepared By</div>
            <div class="border-t border-gray-400 pt-2">Approved By</div>
            <div class="border-t border-gray-400 pt-2">Director</div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
