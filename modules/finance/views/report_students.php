<?php
/**
 * Finance — Student Info Report
 * Selection criteria + field selection checkboxes
 */

$classes  = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");
$fees     = db_fetch_all("SELECT id, description FROM fin_fees WHERE is_active = 1 ORDER BY description");

ob_start();
?>

<div class="space-y-4">
    <h1 class="text-xl font-bold text-gray-900">Student Info Report</h1>

    <form method="POST" action="<?= url('finance', 'report-generate') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="report_type" value="students">

        <!-- Selection Criteria -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase mb-3">Selection Criteria</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                    <select name="class_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                    <select name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">All</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fee</label>
                    <select name="fee_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">All Fees</option>
                        <?php foreach ($fees as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= e($f['description']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fee Status</label>
                    <select name="fee_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">All</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" name="date_from" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" name="date_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
        </div>

        <!-- Field Selection -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase mb-3">Select Fields to Include</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <!-- Column 1: Student Info -->
                <div>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Student Information</h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="student_code" checked class="rounded border-gray-300 text-primary-600"> Student Code</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="full_name" checked class="rounded border-gray-300 text-primary-600"> Full Name</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="gender" class="rounded border-gray-300 text-primary-600"> Gender</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="dob" class="rounded border-gray-300 text-primary-600"> Date of Birth</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="email" class="rounded border-gray-300 text-primary-600"> Email</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="phone" class="rounded border-gray-300 text-primary-600"> Phone</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="nationality" class="rounded border-gray-300 text-primary-600"> Nationality</label>
                    </div>
                </div>
                <!-- Column 2: Academic -->
                <div>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Academic Information</h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="class" checked class="rounded border-gray-300 text-primary-600"> Class</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="section" class="rounded border-gray-300 text-primary-600"> Section</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="enrollment_date" class="rounded border-gray-300 text-primary-600"> Enrollment Date</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="status" class="rounded border-gray-300 text-primary-600"> Student Status</label>
                    </div>
                </div>
                <!-- Column 3: Finance -->
                <div>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Finance Information</h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="total_fees" checked class="rounded border-gray-300 text-primary-600"> Total Fees</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="total_paid" checked class="rounded border-gray-300 text-primary-600"> Total Paid</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="balance" checked class="rounded border-gray-300 text-primary-600"> Balance</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="active_fees_count" class="rounded border-gray-300 text-primary-600"> Active Fees Count</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="last_payment_date" class="rounded border-gray-300 text-primary-600"> Last Payment Date</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="fields[]" value="total_penalty" class="rounded border-gray-300 text-primary-600"> Total Penalty</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Output -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase mb-3">Output Format</h2>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="output" value="screen" checked class="text-primary-600 focus:ring-primary-500"> View on Screen</label>
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="output" value="pdf" class="text-primary-600 focus:ring-primary-500"> Download PDF</label>
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="output" value="excel" class="text-primary-600 focus:ring-primary-500"> Download Excel</label>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">Generate Report</button>
            <button type="reset" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 font-medium">Reset</button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
$page_title = $pageTitle;
include TEMPLATES_PATH . '/layout.php';
