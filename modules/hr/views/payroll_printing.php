<?php
/**
 * HR — Payroll Printing Hub View
 * Central page for selecting payroll period and generating/previewing PDF forms.
 */

require_once APP_ROOT . '/core/ethiopian_calendar.php';

$periods = db_fetch_all(
    "SELECT pp.*, 
            (SELECT COUNT(*) FROM hr_payroll_records WHERE payroll_period_id = pp.id) AS emp_count,
            (SELECT SUM(net_salary) FROM hr_payroll_records WHERE payroll_period_id = pp.id) AS total_net
     FROM hr_payroll_periods pp
     WHERE pp.status IN ('generated','approved','paid')
     ORDER BY pp.year_ec DESC, pp.month_ec DESC"
);

$ecMonths = ec_month_names();
$selectedId = input_int('period_id');

ob_start();
partial('pdf_preview_modal');
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <a href="<?= url('hr', 'payroll') ?>" class="text-sm text-primary-600 hover:underline">&larr; Back to Payroll</a>
            <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text mt-1">Payroll Printing Hub</h1>
        </div>
    </div>

    <?php if (empty($periods)): ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-8 text-center">
        <p class="text-gray-400">No payroll periods with generated data found. Generate payroll first.</p>
        <a href="<?= url('hr', 'payroll') ?>" class="mt-3 inline-block px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">Go to Payroll</a>
    </div>
    <?php else: ?>

    <!-- Period Selector -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5" x-data="{ selectedPeriod: <?= $selectedId ?: (int)$periods[0]['id'] ?> }">
        <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-2">Select Payroll Period</label>
        <select x-model="selectedPeriod" class="w-full sm:w-auto px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
            <?php foreach ($periods as $p):
                $label = ($ecMonths[(int)$p['month_ec']] ?? $p['month_ec']) . ' ' . $p['year_ec'] . ' — '
                       . $p['emp_count'] . ' employees, Net: ' . number_format($p['total_net'] ?? 0, 0) . ' Br'
                       . ' (' . ucfirst($p['status']) . ')';
            ?>
            <option value="<?= $p['id'] ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Print Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-5">
            <!-- Income Tax Declaration -->
            <div class="border border-gray-200 dark:border-dark-border rounded-xl p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-dark-text">Income Tax Declaration</h3>
                        <p class="text-xs text-gray-500 dark:text-dark-muted">Schedule A — Government Form</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="$dispatch('open-pdf', {url: '/hr/print-tax/' + selectedPeriod, title: 'Income Tax Declaration'})" class="px-3 py-1.5 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 font-medium">Preview</button>
                    <a :href="'/hr/download-tax/' + selectedPeriod" class="px-3 py-1.5 bg-gray-600 text-white text-xs rounded-lg hover:bg-gray-700 font-medium">Download</a>
                </div>
            </div>

            <!-- Pension Contribution -->
            <div class="border border-gray-200 dark:border-dark-border rounded-xl p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-dark-text">Pension Contribution</h3>
                        <p class="text-xs text-gray-500 dark:text-dark-muted">Private Org Pension Form</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="$dispatch('open-pdf', {url: '/hr/print-pension/' + selectedPeriod, title: 'Pension Contribution'})" class="px-3 py-1.5 bg-teal-600 text-white text-xs rounded-lg hover:bg-teal-700 font-medium">Preview</button>
                    <a :href="'/hr/download-pension/' + selectedPeriod" class="px-3 py-1.5 bg-gray-600 text-white text-xs rounded-lg hover:bg-gray-700 font-medium">Download</a>
                </div>
            </div>

            <!-- Bank Transfer Sheet -->
            <div class="border border-gray-200 dark:border-dark-border rounded-xl p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-dark-text">Bank Transfer Sheet</h3>
                        <p class="text-xs text-gray-500 dark:text-dark-muted">Salary transfer list for bank</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="$dispatch('open-pdf', {url: '/hr/print-bank/' + selectedPeriod, title: 'Bank Transfer Sheet'})" class="px-3 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 font-medium">Preview</button>
                    <a :href="'/hr/download-bank/' + selectedPeriod" class="px-3 py-1.5 bg-gray-600 text-white text-xs rounded-lg hover:bg-gray-700 font-medium">Download</a>
                </div>
            </div>

            <!-- Payroll Detail -->
            <div class="border border-gray-200 dark:border-dark-border rounded-xl p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-dark-text">Full Payroll Report</h3>
                        <p class="text-xs text-gray-500 dark:text-dark-muted">Detailed payroll table</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a :href="'<?= url('hr', 'payroll-detail') ?>/' + selectedPeriod" class="px-3 py-1.5 bg-purple-600 text-white text-xs rounded-lg hover:bg-purple-700 font-medium">View Detail</a>
                </div>
            </div>

            <!-- Individual Payslips -->
            <div class="border border-gray-200 dark:border-dark-border rounded-xl p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-dark-text">Individual Payslips</h3>
                        <p class="text-xs text-gray-500 dark:text-dark-muted">View per-employee payslips</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a :href="'<?= url('hr', 'payroll-detail') ?>/' + selectedPeriod" class="px-3 py-1.5 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 font-medium">Go to Detail</a>
                </div>
            </div>

            <!-- Payroll Bank Sheet View -->
            <div class="border border-gray-200 dark:border-dark-border rounded-xl p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-dark-text">Pension Summary View</h3>
                        <p class="text-xs text-gray-500 dark:text-dark-muted">On-screen pension sheet</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a :href="'<?= url('hr', 'payroll-pension-sheet') ?>/' + selectedPeriod" class="px-3 py-1.5 bg-amber-600 text-white text-xs rounded-lg hover:bg-amber-700 font-medium">View</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
