<?php
/**
 * Finance — Supplementary Transaction Report
 * Selection criteria + 4-column field selection
 */

$supFees = db_fetch_all("SELECT id, description FROM fin_supplementary_fees WHERE is_active = 1 ORDER BY description");
$classes = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");

ob_start();
?>

<div class="space-y-4">
    <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Supplementary Transaction Report</h1>

    <form method="POST" action="<?= url('finance', 'report-generate') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="report_type" value="supplementary">

        <!-- Selection Criteria -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6 mb-4">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase mb-3">Selection Criteria</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Supplementary Fee</label>
                    <select name="sfee_id" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">All</option>
                        <?php foreach ($supFees as $sf): ?>
                            <option value="<?= $sf['id'] ?>"><?= e($sf['description']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Class</label>
                    <select name="class_id" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Channel</label>
                    <select name="channel" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">All Channels</option>
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="mobile">Mobile</option>
                        <option value="online">Online</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date From</label>
                    <input type="date" name="date_from" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date To</label>
                    <input type="date" name="date_to" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
        </div>

        <!-- Field Selection (4 columns) -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6 mb-4">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase mb-3">Select Fields to Include</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Column 1: Student -->
                <div>
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase mb-2">Student</h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="student_code" checked class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Student Code</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="full_name" checked class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Full Name</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="class" class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Class</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="gender" class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Gender</label>
                    </div>
                </div>
                <!-- Column 2: Fee -->
                <div>
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase mb-2">Fee</h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="fee_description" checked class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Fee Description</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="fee_amount" checked class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Fee Amount</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="currency" class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Currency</label>
                    </div>
                </div>
                <!-- Column 3: Transaction -->
                <div>
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase mb-2">Transaction</h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="tx_amount" checked class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Transaction Amount</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="tx_date" checked class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Transaction Date</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="channel" class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Payment Channel</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="receipt_no" class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Receipt No</label>
                    </div>
                </div>
                <!-- Column 4: Details -->
                <div>
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-dark-muted uppercase mb-2">Details</h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="depositor_name" class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Depositor Name</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="depositor_branch" class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Depositor Branch</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="tx_id" class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Transaction ID</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="processed_by" class="rounded border-gray-300 dark:border-dark-border text-primary-600"> Processed By</label>
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
