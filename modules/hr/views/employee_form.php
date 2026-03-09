<?php
/**
 * HR — Employee Add/Edit Form View
 */

require_once APP_ROOT . '/core/ethiopian_calendar.php';

$id  = route_id();
$emp = null;

if ($id) {
    $emp = db_fetch_one("SELECT * FROM hr_employees WHERE id = ? AND deleted_at IS NULL", [$id]);
    if (!$emp) { set_flash('error', 'Employee not found.'); redirect(url('hr', 'employees')); exit; }
}

$departments = db_fetch_all("SELECT id, name FROM hr_departments WHERE deleted_at IS NULL AND status = 'active' ORDER BY name");
$isEdit = !empty($emp);
$errors = get_validation_errors();

ob_start();
partial('ethiopian_datepicker');
?>

<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <a href="<?= url('hr', 'employees') ?>" class="text-sm text-primary-600 hover:underline">&larr; Back to Employees</a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text mt-1"><?= $isEdit ? 'Edit Employee' : 'Add New Employee' ?></h1>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="bg-red-50 dark:bg-red-900/30 border border-red-400 rounded-lg p-4">
        <h3 class="text-sm font-semibold text-red-800 dark:text-red-300 mb-2">Please fix the following errors:</h3>
        <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300 space-y-1">
            <?php foreach ($errors as $field => $msg): ?>
            <li><?= e($msg) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('hr', 'employee-save') ?>" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $emp['id'] ?>"><?php endif; ?>

        <!-- Personal Information -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4">Personal Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">First Name (English) *</label>
                    <input type="text" name="first_name" value="<?= e(old('first_name', $emp['first_name'] ?? '')) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Father Name (English) *</label>
                    <input type="text" name="father_name" value="<?= e(old('father_name', $emp['father_name'] ?? '')) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Grandfather Name (English) *</label>
                    <input type="text" name="grandfather_name" value="<?= e(old('grandfather_name', $emp['grandfather_name'] ?? '')) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">First Name (አማርኛ)</label>
                    <input type="text" name="first_name_am" value="<?= e(old('first_name_am', $emp['first_name_am'] ?? '')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Father Name (አማርኛ)</label>
                    <input type="text" name="father_name_am" value="<?= e(old('father_name_am', $emp['father_name_am'] ?? '')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Grandfather Name (አማርኛ)</label>
                    <input type="text" name="grandfather_name_am" value="<?= e(old('grandfather_name_am', $emp['grandfather_name_am'] ?? '')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Gender *</label>
                    <select name="gender" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <option value="">Select</option>
                        <option value="male" <?= old('gender', $emp['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= old('gender', $emp['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Date of Birth (EC)</label>
                    <div x-data="ecDatePicker({value: '<?= e(old('date_of_birth_ec', $emp['date_of_birth_ec'] ?? '')) ?>', name: 'date_of_birth_ec', required: false})">
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
                            <input type="number" x-model="year" @change="updateValue()" placeholder="Year" class="w-20 px-2 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text" min="1970" max="2020">
                        </div>
                        <input type="hidden" :name="fieldName" :value="formatted" :required="isRequired">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Phone *</label>
                    <input type="text" name="phone" value="<?= e(old('phone', $emp['phone'] ?? '')) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Email</label>
                    <input type="email" name="email" value="<?= e(old('email', $emp['email'] ?? '')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Address</label>
                    <input type="text" name="address" value="<?= e(old('address', $emp['address'] ?? '')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" value="<?= e(old('emergency_contact_name', $emp['emergency_contact_name'] ?? '')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Emergency Contact Phone</label>
                    <input type="text" name="emergency_contact_phone" value="<?= e(old('emergency_contact_phone', $emp['emergency_contact_phone'] ?? '')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Photo</label>
                    <input type="file" name="photo" accept=".jpg,.jpeg,.png"
                           class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                    <?php if ($isEdit && !empty($emp['photo'])): ?>
                    <p class="text-xs text-gray-400 mt-1">Current: <?= e(basename($emp['photo'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Employment Details -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4">Employment Details</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Department *</label>
                    <select name="department_id" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= old('department_id', $emp['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Position *</label>
                    <input type="text" name="position" value="<?= e(old('position', $emp['position'] ?? '')) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Role *</label>
                    <select name="role" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <option value="">Select Role</option>
                        <?php foreach (['teacher' => 'Teacher', 'admin' => 'Admin', 'accountant' => 'Accountant', 'librarian' => 'Librarian', 'support_staff' => 'Support Staff'] as $rv => $rl): ?>
                        <option value="<?= $rv ?>" <?= old('role', $emp['role'] ?? '') === $rv ? 'selected' : '' ?>><?= $rl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Employment Type *</label>
                    <select name="employment_type" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <option value="">Select</option>
                        <?php foreach (['permanent' => 'Permanent', 'full_time' => 'Full Time', 'contract' => 'Contract', 'part_time' => 'Part Time', 'temporary' => 'Temporary'] as $tv => $tl): ?>
                        <option value="<?= $tv ?>" <?= old('employment_type', $emp['employment_type'] ?? '') === $tv ? 'selected' : '' ?>><?= $tl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Hire Date (EC)</label>
                    <div x-data="ecDatePicker({value: '<?= e(old('hire_date_ec', $emp['start_date_ec'] ?? '')) ?>', name: 'hire_date_ec', required: false})">
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
                            <input type="number" x-model="year" @change="updateValue()" placeholder="Year" class="w-20 px-2 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text" min="2000" max="2030">
                        </div>
                        <input type="hidden" :name="fieldName" :value="formatted" :required="isRequired">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Qualification</label>
                    <select name="qualification" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <option value="">Select</option>
                        <?php foreach (['PhD', 'Masters', 'Bachelors', 'Diploma', 'Certificate', 'Grade 12', 'Grade 10', 'Below Grade 10'] as $q): ?>
                        <option value="<?= $q ?>" <?= old('qualification', $emp['qualification'] ?? '') === $q ? 'selected' : '' ?>><?= $q ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">TIN Number</label>
                    <input type="text" name="tin_number" value="<?= e(old('tin_number', $emp['tin_number'] ?? '')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Pension Number</label>
                    <input type="text" name="pension_number" value="<?= e(old('pension_number', $emp['pension_number'] ?? '')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <option value="active" <?= old('status', $emp['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= old('status', $emp['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        <option value="left" <?= old('status', $emp['status'] ?? '') === 'left' ? 'selected' : '' ?>>Left</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Salary & Banking -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4">Salary &amp; Banking</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Basic Salary (Br) *</label>
                    <input type="number" name="basic_salary" step="0.01" min="0" value="<?= e(old('basic_salary', $emp['basic_salary'] ?? '')) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Transport Allowance (Br)</label>
                    <input type="number" name="transport_allowance" step="0.01" min="0" value="<?= e(old('transport_allowance', $emp['transport_allowance'] ?? '0')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Position Allowance (Br)</label>
                    <input type="number" name="position_allowance" step="0.01" min="0" value="<?= e(old('position_allowance', $emp['position_allowance'] ?? '0')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Other Allowance (Br)</label>
                    <input type="number" name="other_allowance" step="0.01" min="0" value="<?= e(old('other_allowance', $emp['other_allowance'] ?? '0')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Other Deductions (Br)</label>
                    <input type="number" name="other_deductions" step="0.01" min="0" value="<?= e(old('other_deductions', $emp['other_deductions'] ?? '0')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Bank Name</label>
                    <select name="bank_name" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <option value="">Select</option>
                        <?php foreach (['Commercial Bank of Ethiopia', 'Dashen Bank', 'Awash Bank', 'Bank of Abyssinia', 'Wegagen Bank', 'United Bank', 'Nib International Bank', 'Cooperative Bank of Oromia', 'Berhan Bank', 'Abay Bank', 'Oromia Bank', 'Other'] as $b): ?>
                        <option value="<?= $b ?>" <?= old('bank_name', $emp['bank_name'] ?? '') === $b ? 'selected' : '' ?>><?= $b ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Bank Account Number</label>
                    <input type="text" name="bank_account" value="<?= e(old('bank_account', $emp['bank_account'] ?? '')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end gap-3">
            <a href="<?= url('hr', 'employees') ?>" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:text-dark-muted font-medium">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
                <?= $isEdit ? 'Update Employee' : 'Save Employee' ?>
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
