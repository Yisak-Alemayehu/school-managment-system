<?php
/**
 * HR — Leave Request Form View
 * Allows HR to submit leave requests on behalf of employees.
 */

require_once APP_ROOT . '/core/ethiopian_calendar.php';

$leaveTypes = db_fetch_all("SELECT * FROM hr_leave_types WHERE status = 'active' ORDER BY name");
$employees  = db_fetch_all(
    "SELECT id, employee_id, CONCAT(first_name, ' ', father_name) AS name 
     FROM hr_employees WHERE deleted_at IS NULL AND status='active' ORDER BY first_name"
);

// Pre-select employee if passed
$selectedEmployee = input_int('employee_id');

$errors = get_validation_errors();

ob_start();
partial('ethiopian_datepicker');
?>

<div class="space-y-4">
    <div>
        <a href="<?= url('hr', 'leave-requests') ?>" class="text-sm text-primary-600 hover:underline">&larr; Back to Leave Requests</a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text mt-1">Submit Leave Request</h1>
    </div>

    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6 max-w-2xl">
        <form method="POST" action="<?= url('hr', 'leave-request-save') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="space-y-4">
                <!-- Employee -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Employee *</label>
                    <select name="employee_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>" <?= ($selectedEmployee == $emp['id'] || old('employee_id') == $emp['id']) ? 'selected' : '' ?>>
                            <?= e($emp['employee_id'] . ' — ' . $emp['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['employee_id'])): ?><p class="text-xs text-red-600 mt-1"><?= e($errors['employee_id']) ?></p><?php endif; ?>
                </div>

                <!-- Leave Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Leave Type *</label>
                    <select name="leave_type_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <option value="">Select Leave Type</option>
                        <?php foreach ($leaveTypes as $lt): ?>
                        <option value="<?= $lt['id'] ?>" <?= old('leave_type_id') == $lt['id'] ? 'selected' : '' ?> data-max="<?= $lt['max_days'] ?>" data-doc="<?= $lt['requires_document'] ?>">
                            <?= e($lt['name']) ?> (<?= $lt['max_days'] ?> days/year)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['leave_type_id'])): ?><p class="text-xs text-red-600 mt-1"><?= e($errors['leave_type_id']) ?></p><?php endif; ?>
                </div>

                <!-- Date Range -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Start Date (EC) *</label>
                        <div x-data="ecDatePicker({value: '<?= e(old('start_date') ?: '') ?>', name: 'start_date', required: true})">
                            <div class="flex gap-1">
                                <select x-model="day" @change="updateValue()" class="w-16 px-2 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                                    <option value="">Day</option>
                                    <?php for ($d = 1; $d <= 30; $d++): ?><option value="<?= $d ?>"><?= $d ?></option><?php endfor; ?>
                                </select>
                                <select x-model="month" @change="updateValue()" class="flex-1 px-2 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                                    <option value="">Month</option>
                                    <option value="1">Meskerem</option>
                                    <option value="2">Tikimt</option>
                                    <option value="3">Hidar</option>
                                    <option value="4">Tahsas</option>
                                    <option value="5">Tir</option>
                                    <option value="6">Yekatit</option>
                                    <option value="7">Megabit</option>
                                    <option value="8">Miazia</option>
                                    <option value="9">Ginbot</option>
                                    <option value="10">Sene</option>
                                    <option value="11">Hamle</option>
                                    <option value="12">Nehase</option>
                                    <option value="13">Pagume</option>
                                </select>
                                <input type="number" x-model="year" @change="updateValue()" placeholder="Year" class="w-20 px-2 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text" min="2010" max="2030">
                            </div>
                            <input type="hidden" :name="fieldName" :value="formatted" :required="isRequired">
                        </div>
                        <?php if (!empty($errors['start_date'])): ?><p class="text-xs text-red-600 mt-1"><?= e($errors['start_date']) ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">End Date (EC) *</label>
                        <div x-data="ecDatePicker({value: '<?= e(old('end_date') ?: '') ?>', name: 'end_date', required: true})">
                            <div class="flex gap-1">
                                <select x-model="day" @change="updateValue()" class="w-16 px-2 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                                    <option value="">Day</option>
                                    <?php for ($d = 1; $d <= 30; $d++): ?><option value="<?= $d ?>"><?= $d ?></option><?php endfor; ?>
                                </select>
                                <select x-model="month" @change="updateValue()" class="flex-1 px-2 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                                    <option value="">Month</option>
                                    <option value="1">Meskerem</option>
                                    <option value="2">Tikimt</option>
                                    <option value="3">Hidar</option>
                                    <option value="4">Tahsas</option>
                                    <option value="5">Tir</option>
                                    <option value="6">Yekatit</option>
                                    <option value="7">Megabit</option>
                                    <option value="8">Miazia</option>
                                    <option value="9">Ginbot</option>
                                    <option value="10">Sene</option>
                                    <option value="11">Hamle</option>
                                    <option value="12">Nehase</option>
                                    <option value="13">Pagume</option>
                                </select>
                                <input type="number" x-model="year" @change="updateValue()" placeholder="Year" class="w-20 px-2 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text" min="2010" max="2030">
                            </div>
                            <input type="hidden" :name="fieldName" :value="formatted" :required="isRequired">
                        </div>
                        <?php if (!empty($errors['end_date'])): ?><p class="text-xs text-red-600 mt-1"><?= e($errors['end_date']) ?></p><?php endif; ?>
                    </div>
                </div>

                <!-- Days (auto-calculated could be done via JS, but left as input for flexibility) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Number of Days *</label>
                    <input type="number" name="days" min="0.5" step="0.5" value="<?= e(old('days') ?: '1') ?>" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                    <?php if (!empty($errors['days'])): ?><p class="text-xs text-red-600 mt-1"><?= e($errors['days']) ?></p><?php endif; ?>
                </div>

                <!-- Reason -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Reason</label>
                    <textarea name="reason" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text" placeholder="Reason for leave request..."><?= e(old('reason') ?: '') ?></textarea>
                </div>

                <!-- Attachment -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Supporting Document (optional)</label>
                    <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                           class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                    <p class="text-xs text-gray-400 mt-1">PDF, JPEG, PNG, DOC — Max 5MB</p>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <a href="<?= url('hr', 'leave-requests') ?>" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
