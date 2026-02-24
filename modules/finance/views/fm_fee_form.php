<?php
/**
 * Fee Management — Create / Edit Fee Form
 * Single-page form with conditional recurrence + penalty sections
 */
$id   = route_id() ?: input_int('id');
$edit = null;
$recurrence = null;
$penalty    = null;

if ($id) {
    $edit = db_fetch_one("SELECT * FROM fees WHERE id = ? AND deleted_at IS NULL", [$id]);
    if (!$edit) {
        set_flash('error', 'Fee not found.');
        redirect('finance', 'fm-manage-fees');
    }
    $recurrence = db_fetch_one("SELECT * FROM recurrence_configs WHERE fee_id = ?", [$id]);
    $penalty    = db_fetch_one("SELECT * FROM penalty_configs WHERE fee_id = ?", [$id]);
}

$pageTitle = $edit ? 'Edit Fee' : 'Create New Fee';

ob_start();
?>
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900"><?= $pageTitle ?></h1>
        <a href="<?= url('finance', 'fm-manage-fees') ?>" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Fees</a>
    </div>

    <form method="POST" action="<?= url('finance', 'fm-fee-save') ?>" id="feeForm" class="space-y-6">
        <?= csrf_field() ?>
        <?php if ($edit): ?>
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
        <?php endif; ?>

        <!-- Basic Information -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fee Type *</label>
                    <select name="fee_type" id="feeType" required
                            onchange="toggleRecurrenceSection()"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="one_time" <?= old('fee_type', $edit['fee_type'] ?? 'one_time') === 'one_time' ? 'selected' : '' ?>>One-Time</option>
                        <option value="recurrent" <?= old('fee_type', $edit['fee_type'] ?? '') === 'recurrent' ? 'selected' : '' ?>>Recurrent</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                    <select name="currency" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="ETB" <?= old('currency', $edit['currency'] ?? 'ETB') === 'ETB' ? 'selected' : '' ?>>ETB (Birr)</option>
                        <option value="USD" <?= old('currency', $edit['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                    <textarea name="description" required maxlength="500" rows="3" id="descField"
                              oninput="updateCharCount()"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                              placeholder="E.g., Monthly tuition fee for 2026 academic year"><?= old('description', $edit['description'] ?? '') ?></textarea>
                    <p class="text-xs text-gray-400 mt-1"><span id="charCount">0</span>/500 characters</p>
                    <?php if ($err = get_validation_error('description')): ?>
                        <p class="text-xs text-red-600 mt-1"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm"><?= CURRENCY_SYMBOL ?></span>
                        <input type="number" name="amount" required step="0.01" min="0.01"
                               value="<?= old('amount', $edit['amount'] ?? '') ?>"
                               class="w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                               placeholder="0.00">
                    </div>
                    <?php if ($err = get_validation_error('amount')): ?>
                        <p class="text-xs text-red-600 mt-1"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Validity Period -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Validity Period</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Effective Date *</label>
                    <input type="date" name="effective_date" required
                           value="<?= old('effective_date', $edit['effective_date'] ?? '') ?>"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <?php if ($err = get_validation_error('effective_date')): ?>
                        <p class="text-xs text-red-600 mt-1"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date *</label>
                    <input type="date" name="end_date" required
                           value="<?= old('end_date', $edit['end_date'] ?? '') ?>"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <?php if ($err = get_validation_error('end_date')): ?>
                        <p class="text-xs text-red-600 mt-1"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <p class="text-xs text-amber-600 mt-3" id="dateWarning" style="display:none;">
                ⚠ End date must be after the effective date.
            </p>
        </div>

        <!-- Recurrence Section (only for recurrent fees) -->
        <div id="recurrenceSection" class="bg-white rounded-xl shadow-sm border p-6" style="display:none;">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Recurrence Settings</h2>
            <p class="text-sm text-gray-500 mb-4">Configure how often this fee recurs.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frequency Number</label>
                    <input type="number" name="frequency_number" min="1" value="<?= old('frequency_number', $recurrence['frequency_number'] ?? 1) ?>"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frequency Unit</label>
                    <select name="frequency_unit" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="days" <?= old('frequency_unit', $recurrence['frequency_unit'] ?? '') === 'days' ? 'selected' : '' ?>>Days</option>
                        <option value="weeks" <?= old('frequency_unit', $recurrence['frequency_unit'] ?? '') === 'weeks' ? 'selected' : '' ?>>Weeks</option>
                        <option value="months" <?= old('frequency_unit', $recurrence['frequency_unit'] ?? 'months') === 'months' ? 'selected' : '' ?>>Months</option>
                        <option value="years" <?= old('frequency_unit', $recurrence['frequency_unit'] ?? '') === 'years' ? 'selected' : '' ?>>Years</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Recurrences</label>
                    <input type="number" name="max_recurrences" min="0" 
                           value="<?= old('max_recurrences', $recurrence['max_recurrences'] ?? 0) ?>"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                           placeholder="0 = unlimited">
                    <p class="text-xs text-gray-400 mt-1">0 = unlimited recurrences</p>
                </div>
            </div>

            <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                <p class="text-xs text-blue-700">
                    <strong>Note:</strong> The first charge is generated immediately upon fee activation. 
                    Subsequent charges will be generated automatically based on the frequency schedule.
                </p>
            </div>
        </div>

        <!-- Penalty Section (toggle) -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Late Payment Penalty</h2>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="penalty_enabled" value="1" id="penaltyToggle"
                           onchange="togglePenaltySection()"
                           <?= $penalty ? 'checked' : '' ?>
                           class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-primary-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                    <span class="ml-2 text-sm text-gray-500">Enable</span>
                </label>
            </div>

            <div id="penaltySection" style="display:<?= $penalty ? 'block' : 'none' ?>;">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Grace Period</label>
                        <div class="flex gap-2">
                            <input type="number" name="grace_period_number" min="0" 
                                   value="<?= old('grace_period_number', $penalty['grace_period_number'] ?? 0) ?>"
                                   class="w-20 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <select name="grace_period_unit" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="days" <?= old('grace_period_unit', $penalty['grace_period_unit'] ?? 'days') === 'days' ? 'selected' : '' ?>>Days</option>
                                <option value="weeks" <?= old('grace_period_unit', $penalty['grace_period_unit'] ?? '') === 'weeks' ? 'selected' : '' ?>>Weeks</option>
                                <option value="months" <?= old('grace_period_unit', $penalty['grace_period_unit'] ?? '') === 'months' ? 'selected' : '' ?>>Months</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Penalty Type</label>
                        <select name="penalty_type" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="fixed" <?= old('penalty_type', $penalty['penalty_type'] ?? 'fixed') === 'fixed' ? 'selected' : '' ?>>Fixed Amount</option>
                            <option value="percentage" <?= old('penalty_type', $penalty['penalty_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage of Fee</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Penalty Amount / %</label>
                        <input type="number" name="penalty_amount" step="0.01" min="0"
                               value="<?= old('penalty_amount', $penalty['penalty_amount'] ?? '') ?>"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                               placeholder="E.g., 50 or 5%">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Penalty Frequency</label>
                        <select name="penalty_frequency" id="penaltyFreq" onchange="togglePenaltyRecurrence()"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="one_time" <?= old('penalty_frequency', $penalty['penalty_frequency'] ?? 'one_time') === 'one_time' ? 'selected' : '' ?>>One-Time</option>
                            <option value="recurrent" <?= old('penalty_frequency', $penalty['penalty_frequency'] ?? '') === 'recurrent' ? 'selected' : '' ?>>Recurrent</option>
                        </select>
                    </div>

                    <div id="penaltyRecurrenceUnit" style="display:<?= ($penalty['penalty_frequency'] ?? '') === 'recurrent' ? 'block' : 'none' ?>;">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Recurrence Every</label>
                        <div class="flex gap-2">
                            <input type="number" name="penalty_recurrence_number" min="1" 
                                   value="<?= old('penalty_recurrence_number', $penalty['penalty_recurrence_number'] ?? 1) ?>"
                                   class="w-20 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <select name="penalty_recurrence_unit" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="days" <?= old('penalty_recurrence_unit', $penalty['penalty_recurrence_unit'] ?? 'days') === 'days' ? 'selected' : '' ?>>Days</option>
                                <option value="weeks" <?= old('penalty_recurrence_unit', $penalty['penalty_recurrence_unit'] ?? '') === 'weeks' ? 'selected' : '' ?>>Weeks</option>
                                <option value="months" <?= old('penalty_recurrence_unit', $penalty['penalty_recurrence_unit'] ?? '') === 'months' ? 'selected' : '' ?>>Months</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Penalty Amount *</label>
                        <input type="number" name="max_penalty_amount" step="0.01" min="0"
                               value="<?= old('max_penalty_amount', $penalty['max_penalty_amount'] ?? '') ?>"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                               placeholder="Total cap across all penalties">
                        <?php if ($err = get_validation_error('max_penalty_amount')): ?>
                            <p class="text-xs text-red-600 mt-1"><?= e($err) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Applications</label>
                        <input type="number" name="max_penalty_applications" min="0"
                               value="<?= old('max_penalty_applications', $penalty['max_penalty_applications'] ?? 0) ?>"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                               placeholder="0 = unlimited">
                        <p class="text-xs text-gray-400 mt-1">0 = unlimited</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Penalty End Date</label>
                        <input type="date" name="penalty_end_date"
                               value="<?= old('penalty_end_date', $penalty['penalty_end_date'] ?? '') ?>"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <p class="text-xs text-gray-400 mt-1">Optional. Penalties stop after this date.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Preview -->
        <div class="bg-gradient-to-r from-primary-50 to-blue-50 rounded-xl border border-primary-200 p-6" id="preview">
            <h3 class="text-sm font-semibold text-primary-800 mb-2">Fee Summary Preview</h3>
            <div id="previewContent" class="text-sm text-gray-700 space-y-1">
                <p>Fill in the form to see a preview.</p>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex flex-col sm:flex-row gap-3 justify-end">
            <a href="<?= url('finance', 'fm-manage-fees') ?>" class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 text-center">
                Cancel
            </a>
            <button type="button" onclick="resetForm()" class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                Reset
            </button>
            <button type="submit" name="save_action" value="draft"
                    class="px-6 py-2.5 bg-gray-600 text-white rounded-lg text-sm font-medium hover:bg-gray-700">
                Save as Draft
            </button>
            <button type="submit" name="save_action" value="activate"
                    class="px-6 py-2.5 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
                <?= $edit ? 'Update Fee' : 'Create & Activate' ?>
            </button>
        </div>
    </form>
</div>

<script>
function toggleRecurrenceSection() {
    var sec = document.getElementById('recurrenceSection');
    sec.style.display = document.getElementById('feeType').value === 'recurrent' ? 'block' : 'none';
    updatePreview();
}

function togglePenaltySection() {
    document.getElementById('penaltySection').style.display = 
        document.getElementById('penaltyToggle').checked ? 'block' : 'none';
    updatePreview();
}

function togglePenaltyRecurrence() {
    document.getElementById('penaltyRecurrenceUnit').style.display = 
        document.getElementById('penaltyFreq').value === 'recurrent' ? 'block' : 'none';
}

function updateCharCount() {
    var len = document.getElementById('descField').value.length;
    document.getElementById('charCount').textContent = len;
}

function updatePreview() {
    var form = document.getElementById('feeForm');
    var desc = form.querySelector('[name=description]').value || 'No description';
    var amount = parseFloat(form.querySelector('[name=amount]').value) || 0;
    var type = form.querySelector('[name=fee_type]').value;
    var effDate = form.querySelector('[name=effective_date]').value;
    var endDate = form.querySelector('[name=end_date]').value;

    var html = '<p><strong>' + desc + '</strong></p>';
    html += '<p>Amount: <?= CURRENCY_SYMBOL ?> ' + amount.toLocaleString(undefined, {minimumFractionDigits:2}) + ' (' + type.replace('_',' ') + ')</p>';
    if (effDate && endDate) html += '<p>Period: ' + effDate + ' to ' + endDate + '</p>';
    if (type === 'recurrent') {
        var fn = form.querySelector('[name=frequency_number]').value || 1;
        var fu = form.querySelector('[name=frequency_unit]').value || 'months';
        html += '<p>Recurs: every ' + fn + ' ' + fu + '</p>';
    }
    if (document.getElementById('penaltyToggle').checked) html += '<p>⚠ Penalty enabled</p>';

    document.getElementById('previewContent').innerHTML = html;
}

function resetForm() {
    document.getElementById('feeForm').reset();
    toggleRecurrenceSection();
    togglePenaltySection();
    updatePreview();
}

// Init
document.addEventListener('DOMContentLoaded', function() {
    toggleRecurrenceSection();
    updateCharCount();
    updatePreview();

    // Live preview on input change
    document.getElementById('feeForm').addEventListener('input', updatePreview);
    document.getElementById('feeForm').addEventListener('change', updatePreview);

    // Date validation
    var effInput = document.querySelector('[name=effective_date]');
    var endInput = document.querySelector('[name=end_date]');
    function checkDates() {
        var show = effInput.value && endInput.value && endInput.value <= effInput.value;
        document.getElementById('dateWarning').style.display = show ? 'block' : 'none';
    }
    effInput.addEventListener('change', checkDates);
    endInput.addEventListener('change', checkDates);
});
</script>
<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
