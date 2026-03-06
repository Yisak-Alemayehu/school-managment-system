<?php
/**
 * Finance — Fees Due List + Add New Fee (complex form)
 */

$search = input('search');
$page   = max(1, input_int('page') ?: 1);
$perPage = 25;

$where  = ["1=1"];
$params = [];
if ($search) {
    $where[]  = "f.description LIKE ?";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);
$offset      = ($page - 1) * $perPage;

$total = (int) db_fetch_value("SELECT COUNT(*) FROM fin_fees f WHERE $whereClause", $params);
$fees  = db_fetch_all(
    "SELECT f.*,
            (SELECT GROUP_CONCAT(c.name SEPARATOR ', ')
               FROM fin_fee_classes fc JOIN classes c ON fc.class_id = c.id
              WHERE fc.fee_id = f.id) AS class_names,
            (SELECT COUNT(*) FROM fin_student_fees sf WHERE sf.fee_id = f.id AND sf.is_active = 1) AS student_count,
            u.full_name AS created_by_name
       FROM fin_fees f
       LEFT JOIN users u ON f.created_by = u.id
      WHERE $whereClause
      ORDER BY f.created_at DESC
      LIMIT $perPage OFFSET $offset",
    $params
);

$lastPage   = max(1, (int) ceil($total / $perPage));
$pagination = [
    'total' => $total, 'per_page' => $perPage, 'current_page' => $page,
    'last_page' => $lastPage, 'from' => $total > 0 ? $offset + 1 : 0,
    'to' => min($offset + $perPage, $total),
];

$classes = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");

$exportQs = http_build_query(array_filter(['type' => 'fees', 'search' => $search]));

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Fee / Tuition Management</h1>
        <div class="flex gap-2">
            <a href="<?= url('finance', 'export-pdf') ?>&<?= $exportQs ?>"
               class="px-3 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 font-medium">PDF</a>
            <a href="<?= url('finance', 'export-excel') ?>&<?= $exportQs ?>"
               class="px-3 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 font-medium">Excel</a>
            <button onclick="document.getElementById('addFeeSection').classList.toggle('hidden')"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">
                + Add New Fee
            </button>
        </div>
    </div>

    <!-- Search -->
    <form method="GET" action="<?= url('finance', 'fee-due') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
        <div class="flex gap-3">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search fees…"
                   class="flex-1 px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">Search</button>
            <a href="<?= url('finance', 'fee-due') ?>" class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 font-medium">Clear</a>
        </div>
    </form>

    <!-- ═══════════════ ADD NEW FEE FORM ═══════════════ -->
    <div id="addFeeSection" class="hidden bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Add New Fee</h2>
        <form method="POST" action="<?= url('finance', 'fee-save') ?>" id="feeForm">
            <?= csrf_field() ?>

            <!-- Section 1: Basic Information -->
            <div class="border-b border-gray-200 dark:border-dark-border pb-4 mb-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase mb-3">Basic Fee Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fee Type *</label>
                        <select name="fee_type" id="feeType" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                            <option value="1">Recurrent</option>
                            <option value="0">One-Time</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Currency *</label>
                        <select name="currency" id="feeCurrency" onchange="toggleForeignAmount()"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                            <option value="ETB">ETB (Birr)</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>

                    <div id="foreignAmountWrap" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Exchange Rate Amount (ETB)</label>
                        <input type="number" name="foreign_amount" step="0.01" min="0"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                               placeholder="ETB equivalent">
                    </div>

                    <div class="md:col-span-2 lg:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description *</label>
                        <input type="text" name="description" required maxlength="255"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                               placeholder="e.g. Monthly Tuition Fee">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount *</label>
                        <input type="number" name="amount" step="0.01" min="0" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                               placeholder="0.00">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign to Classes</label>
                        <select name="class_ids[]" multiple
                                class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                                style="min-height: 80px;">
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 dark:text-dark-muted mt-1">Hold Ctrl/Cmd to multi-select</p>
                    </div>
                </div>
            </div>

            <!-- Section 2: Fee Validity Period -->
            <div class="border-b border-gray-200 dark:border-dark-border pb-4 mb-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase mb-3">Fee Validity Period</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Effective Date *</label>
                        <input type="date" name="effective_date" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date *</label>
                        <input type="date" name="end_date" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>
            </div>

            <!-- Section 3: Recurrent Fee Settings (shows when fee_type = 1) -->
            <div id="recurrentSection" class="border-b border-gray-200 dark:border-dark-border pb-4 mb-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase mb-3">Recurrent Fee Settings</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Apply Every</label>
                        <input type="number" name="apply_every" min="1" max="31" value="1"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Frequency</label>
                        <select name="frequency"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                            <option value="months">Month(s)</option>
                            <option value="weeks">Week(s)</option>
                            <option value="days">Day(s)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 4: Penalty Settings -->
            <div class="border-b border-gray-200 dark:border-dark-border pb-4 mb-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase mb-3">Penalty Settings</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="has_penalty" id="hasPenalty" value="1" checked
                                   onchange="togglePenaltySection()" class="rounded border-gray-300 dark:border-dark-border text-primary-600 focus:ring-primary-500">
                            Has Penalty
                        </label>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_credit_hour" value="1"
                                   class="rounded border-gray-300 dark:border-dark-border text-primary-600 focus:ring-primary-500">
                            Is Credit Hour Based
                        </label>
                    </div>
                </div>
            </div>

            <!-- Section 5: Arrears / Penalty Detail (shows when has_penalty) -->
            <div id="penaltyDetail" class="pb-4 mb-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase mb-3">Arrears / Penalty Detail</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unpaid After</label>
                        <div class="flex gap-2">
                            <input type="number" name="penalty_unpaid_after" min="1" value="1"
                                   class="flex-1 px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                            <select name="penalty_unpaid_unit"
                                    class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                                <option value="months">Month(s)</option>
                                <option value="weeks">Week(s)</option>
                                <option value="days">Day(s)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Penalty Type</label>
                        <select name="penalty_type" id="penaltyType" onchange="toggleVaryingPenalty()"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                            <option value="fixed_amount">Fixed Amount</option>
                            <option value="fixed_percentage">Fixed Percentage</option>
                            <option value="varying_amount">Varying Amount</option>
                            <option value="varying_percentage">Varying Percentage</option>
                        </select>
                    </div>

                    <div id="fixedPenaltyValue">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Penalty Value</label>
                        <input type="number" name="penalty_value" step="0.01" min="0"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                               placeholder="Amount or %">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Penalty Frequency</label>
                        <select name="penalty_frequency" id="penaltyFreq" onchange="togglePenaltyReapply()"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                            <option value="one_time">One-Time</option>
                            <option value="recurrent">Recurrent</option>
                        </select>
                    </div>

                    <div id="penaltyReapplyWrap" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reapply Every</label>
                        <div class="flex gap-2">
                            <input type="number" name="penalty_reapply_every" min="1"
                                   class="flex-1 px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                            <select name="penalty_reapply_unit"
                                    class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                                <option value="months">Month(s)</option>
                                <option value="weeks">Week(s)</option>
                                <option value="days">Day(s)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Penalty Expiry Date</label>
                        <input type="date" name="penalty_expiry_date"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Penalty Amount</label>
                        <input type="number" name="max_penalty_amount" step="0.01" min="0" value="1000"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Penalty Count</label>
                        <input type="number" name="max_penalty_count" min="0" value="0"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                               placeholder="0 = unlimited">
                    </div>
                </div>

                <!-- Varying Penalty Values (dynamic list) -->
                <div id="varyingPenaltyWrap" class="hidden mt-4">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Varying Penalty Values</h4>
                    <div id="varyingPenaltyList" class="space-y-2">
                        <div class="flex gap-2 items-center varying-row">
                            <span class="text-sm text-gray-500 dark:text-dark-muted w-8">1.</span>
                            <input type="number" name="varying_values[]" step="0.01" min="0"
                                   class="flex-1 px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                                   placeholder="Value">
                            <button type="button" onclick="removeVaryingRow(this)" class="text-red-500 hover:text-red-700 text-sm">Remove</button>
                        </div>
                    </div>
                    <button type="button" onclick="addVaryingRow()"
                            class="mt-2 px-3 py-1 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200">
                        + Add Value
                    </button>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">Add Fee</button>
                <button type="reset" onclick="document.getElementById('addFeeSection').classList.add('hidden')"
                        class="px-6 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 font-medium">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Fee List Table -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border responsive-table">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Classes</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Students</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Period</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Penalty</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php if (empty($fees)): ?>
                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No fees defined.</td></tr>
                    <?php else: ?>
                    <?php foreach ($fees as $f): ?>
                    <tr class="hover:bg-gray-50 dark:bg-dark-bg transition-colors">
                        <td class="px-4 py-3 text-sm font-medium" data-label="Description">
                            <a href="<?= url('finance', 'fee-detail', $f['id']) ?>" class="text-primary-600 hover:underline"><?= e($f['description']) ?></a>
                        </td>
                        <td class="px-4 py-3 text-sm" data-label="Amount"><?= format_money($f['amount']) ?> <?= e($f['currency']) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Type">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $f['fee_type'] ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' ?>">
                                <?= $f['fee_type'] ? 'Recurrent' : 'One-Time' ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Classes"><?= e($f['class_names'] ?? 'All') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Students"><?= (int) $f['student_count'] ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Period">
                            <?= format_date($f['effective_date']) ?> — <?= format_date($f['end_date']) ?>
                        </td>
                        <td class="px-4 py-3 text-sm" data-label="Penalty">
                            <?php if ($f['has_penalty']): ?>
                                <span class="text-orange-600 font-medium"><?= ucfirst(str_replace('_', ' ', $f['penalty_type'] ?? 'Yes')) ?></span>
                            <?php else: ?>
                                <span class="text-gray-400 dark:text-gray-500">None</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm" data-label="Status">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $f['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= $f['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm" data-label="Actions">
                            <a href="<?= url('finance', 'fee-detail', $f['id']) ?>" class="text-primary-600 hover:text-primary-800 text-sm font-medium">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= pagination_html($pagination, url('finance/fee-due')) ?>
    </div>
</div>

<script>
function toggleForeignAmount() {
    const cur = document.getElementById('feeCurrency').value;
    document.getElementById('foreignAmountWrap').classList.toggle('hidden', cur === 'ETB');
}

document.getElementById('feeType').addEventListener('change', function() {
    document.getElementById('recurrentSection').classList.toggle('hidden', this.value === '0');
});

function togglePenaltySection() {
    const show = document.getElementById('hasPenalty').checked;
    document.getElementById('penaltyDetail').classList.toggle('hidden', !show);
}

function toggleVaryingPenalty() {
    const type = document.getElementById('penaltyType').value;
    const isVarying = type === 'varying_amount' || type === 'varying_percentage';
    document.getElementById('varyingPenaltyWrap').classList.toggle('hidden', !isVarying);
    document.getElementById('fixedPenaltyValue').classList.toggle('hidden', isVarying);
}

function togglePenaltyReapply() {
    const freq = document.getElementById('penaltyFreq').value;
    document.getElementById('penaltyReapplyWrap').classList.toggle('hidden', freq !== 'recurrent');
}

function addVaryingRow() {
    const list = document.getElementById('varyingPenaltyList');
    const count = list.querySelectorAll('.varying-row').length + 1;
    const row = document.createElement('div');
    row.className = 'flex gap-2 items-center varying-row';
    row.innerHTML = '<span class="text-sm text-gray-500 dark:text-dark-muted w-8">' + count + '.</span>' +
        '<input type="number" name="varying_values[]" step="0.01" min="0" class="flex-1 px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500" placeholder="Value">' +
        '<button type="button" onclick="removeVaryingRow(this)" class="text-red-500 hover:text-red-700 text-sm">Remove</button>';
    list.appendChild(row);
}

function removeVaryingRow(btn) {
    const list = document.getElementById('varyingPenaltyList');
    if (list.querySelectorAll('.varying-row').length > 1) {
        btn.closest('.varying-row').remove();
        // Re-number
        list.querySelectorAll('.varying-row').forEach(function(row, i) {
            row.querySelector('span').textContent = (i + 1) + '.';
        });
    }
}
</script>

<?php
$content = ob_get_clean();
$page_title = $pageTitle;
include TEMPLATES_PATH . '/layout.php';
