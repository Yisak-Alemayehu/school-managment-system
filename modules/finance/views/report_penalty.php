<?php
/**
 * Finance — Penalty Report
 * Selection criteria + field selection
 */

$classes = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");
$fees    = db_fetch_all("SELECT id, description FROM fin_fees WHERE is_active = 1 AND has_penalty = 1 ORDER BY description");

ob_start();
?>

<div class="space-y-4">
    <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Penalty Report</h1>

    <form method="POST" action="<?= url('finance', 'apply-penalties') ?>" class="inline" onsubmit="return confirm('Apply penalties to all overdue student fees now?')">
        <?= csrf_field() ?>
        <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm hover:bg-orange-700 font-medium">Apply Penalties Now</button>
    </form>

    <form method="POST" action="<?= url('finance', 'report-generate') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="report_type" value="penalty">

        <!-- Selection Criteria -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6 mb-4">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase mb-3">Selection Criteria</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Class</label>
                    <select name="class_id" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fee</label>
                    <select name="fee_id" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        <option value="">All Fees with Penalty</option>
                        <?php foreach ($fees as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= e($f['description']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date From</label>
                    <input type="date" name="date_from" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date To</label>
                    <input type="date" name="date_to" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
        </div>

        <!-- Field Selection -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6 mb-4">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase mb-3">Select Fields to Include</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase mb-2">Student Info</h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="student_code" checked class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Student Code</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="full_name" checked class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Full Name</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="class" checked class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Class</label>
                    </div>
                </div>
                <div>
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase mb-2">Fee Info</h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="fee_description" checked class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Fee Description</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="fee_amount" checked class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Fee Amount</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="balance" checked class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Outstanding Balance</label>
                    </div>
                </div>
                <div>
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase mb-2">Penalty Info</h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="penalty_count" checked class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Penalty Count</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="penalty_total" checked class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Total Penalty Amount</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="last_penalty_date" class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Last Penalty Date</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="penalty_type" class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Penalty Type</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Output -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6 mb-4">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase mb-3">Output Format</h2>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="output" value="screen" checked class="text-primary-600 focus:ring-primary-500"> View on Screen</label>
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="output" value="pdf" class="text-primary-600 focus:ring-primary-500"> Download PDF</label>
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="output" value="excel" class="text-primary-600 focus:ring-primary-500"> Download Excel</label>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">Generate Report</button>
            <button type="reset" class="px-6 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 font-medium">Reset</button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
$page_title = $pageTitle;
include TEMPLATES_PATH . '/layout.php';
