<?php
/**
 * Users — Create View
 * Select from HR employees or create a non-employee user
 */

$roles = db_fetch_all("SELECT id, name FROM roles ORDER BY id");

// Fetch ALL active employees (show who already has account)
$employees = db_fetch_all("
    SELECT e.id, e.employee_id, e.first_name, e.father_name, e.grandfather_name,
           e.email, e.phone, e.role, e.user_id
    FROM hr_employees e
    WHERE e.status = 'active'
      AND e.deleted_at IS NULL
    ORDER BY e.first_name, e.father_name
");

// Build JSON data for JS auto-fill
$employeeJson = [];
foreach ($employees as $emp) {
    $fullName = trim($emp['first_name'] . ' ' . $emp['father_name'] . ' ' . $emp['grandfather_name']);
    $suggestedUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $emp['first_name'])) 
                       . '.' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $emp['father_name']));
    $employeeJson[] = [
        'id'          => (int)$emp['id'],
        'emp_code'    => $emp['employee_id'],
        'full_name'   => $fullName,
        'email'       => $emp['email'] ?? '',
        'phone'       => $emp['phone'] ?? '',
        'username'    => $suggestedUsername,
        'role'        => $emp['role'] ?? '',
        'has_account' => !empty($emp['user_id']),
    ];
}

ob_start();
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('users') ?>" class="p-1 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:text-dark-muted rounded">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Add New User</h1>
    </div>

    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <form method="POST" action="<?= url('users', 'create') ?>" class="space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" id="user_mode" name="user_mode" value="<?= e(old('user_mode', 'employee')) ?>">

            <!-- Mode Toggle -->
            <div class="flex rounded-lg border border-gray-200 dark:border-dark-border overflow-hidden">
                <button type="button" id="btn-mode-employee"
                        class="flex-1 px-4 py-2.5 text-sm font-medium transition text-center bg-primary-800 text-white">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    From Employee
                </button>
                <button type="button" id="btn-mode-manual"
                        class="flex-1 px-4 py-2.5 text-sm font-medium transition text-center bg-gray-50 dark:bg-dark-bg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-dark-border">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    Non-Employee User
                </button>
            </div>

            <!-- ========== EMPLOYEE MODE ========== -->
            <div id="employee-section">
                <!-- Searchable Employee Selector -->
                <div class="relative">
                    <label for="employee_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search & Select Employee <span class="text-red-500">*</span></label>
                    <input type="text" id="employee_search" autocomplete="off"
                           placeholder="Type to search by name or ID..."
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                    <input type="hidden" id="employee_id" name="employee_id" value="<?= e(old('employee_id')) ?>">
                    <?php if ($err = get_validation_error('employee_id')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>

                    <!-- Dropdown list -->
                    <div id="employee-dropdown" class="absolute z-20 w-full mt-1 bg-white dark:bg-dark-card border border-gray-200 dark:border-dark-border rounded-lg shadow-lg max-h-60 overflow-y-auto hidden">
                    </div>
                </div>

                <!-- Selected employee badge -->
                <div id="selected-employee" class="mt-3 hidden">
                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span id="selected-emp-name" class="text-sm font-medium text-green-800 dark:text-green-300"></span>
                            <span id="selected-emp-code" class="text-xs text-green-600 dark:text-green-400"></span>
                        </div>
                        <button type="button" id="clear-employee" class="text-green-600 hover:text-green-800 dark:text-green-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Employee list table -->
                <div class="mt-4">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">All Employees</div>
                    <div class="border border-gray-200 dark:border-dark-border rounded-lg overflow-hidden max-h-48 overflow-y-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 dark:bg-dark-bg sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left text-gray-600 dark:text-gray-400">Name</th>
                                    <th class="px-3 py-2 text-left text-gray-600 dark:text-gray-400">ID</th>
                                    <th class="px-3 py-2 text-left text-gray-600 dark:text-gray-400">Role</th>
                                    <th class="px-3 py-2 text-center text-gray-600 dark:text-gray-400">Status</th>
                                </tr>
                            </thead>
                            <tbody id="employee-table-body" class="divide-y divide-gray-100 dark:divide-dark-border">
                                <?php foreach ($employees as $emp):
                                    $hasAccount = !empty($emp['user_id']);
                                ?>
                                <tr class="employee-row cursor-pointer hover:bg-gray-50 dark:hover:bg-dark-bg <?= $hasAccount ? 'opacity-50' : '' ?>"
                                    data-id="<?= (int)$emp['id'] ?>" data-has-account="<?= $hasAccount ? '1' : '0' ?>">
                                    <td class="px-3 py-2 text-gray-900 dark:text-dark-text">
                                        <?= e($emp['first_name'] . ' ' . $emp['father_name'] . ' ' . $emp['grandfather_name']) ?>
                                    </td>
                                    <td class="px-3 py-2 text-gray-500 dark:text-gray-400"><?= e($emp['employee_id']) ?></td>
                                    <td class="px-3 py-2 text-gray-500 dark:text-gray-400 capitalize"><?= e($emp['role']) ?></td>
                                    <td class="px-3 py-2 text-center">
                                        <?php if ($hasAccount): ?>
                                            <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 dark:bg-dark-border text-gray-500 dark:text-gray-400">Has Account</span>
                                        <?php else: ?>
                                            <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">Available</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========== COMMON USER FIELDS ========== -->
            <div id="user-fields" class="space-y-5" style="display: none;">

                <!-- Info banner for employee mode -->
                <div id="emp-info-banner" class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 hidden">
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Employee details auto-filled. Set a password and role for their account.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" id="full_name" name="full_name" value="<?= e(old('full_name')) ?>" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <?php if ($err = get_validation_error('full_name')): ?>
                            <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username <span class="text-red-500">*</span></label>
                        <input type="text" id="username" name="username" value="<?= e(old('username')) ?>" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <?php if ($err = get_validation_error('username')): ?>
                            <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                        <?php endif; ?>
                    </div>

                    <div id="email-field">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" id="email" name="email" value="<?= e(old('email')) ?>"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <p id="email-hint" class="mt-1 text-xs text-amber-600" style="display:none;">This employee has no email on file. Please provide one.</p>
                        <?php if ($err = get_validation_error('email')): ?>
                            <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                        <input type="text" id="phone" name="phone" value="<?= e(old('phone')) ?>"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm bg-white dark:bg-dark-card dark:text-dark-text"
                               placeholder="+251...">
                        <?php if ($err = get_validation_error('phone')): ?>
                            <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password <span class="text-red-500">*</span></label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm bg-white dark:bg-dark-card dark:text-dark-text"
                               placeholder="Min 8 chars, mixed case, number, symbol">
                        <?php if ($err = get_validation_error('password')): ?>
                            <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                    </div>
                </div>

                <div>
                    <label for="role_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role <span class="text-red-500">*</span></label>
                    <select id="role_id" name="role_id" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <option value="">Select role...</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= old('role_id') == $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($err = get_validation_error('role_id')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" id="is_active" name="is_active" value="1" checked
                           class="rounded text-primary-600 focus:ring-primary-500">
                    <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">Active account</label>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" id="force_password_change" name="force_password_change" value="1" checked
                           class="rounded text-primary-600 focus:ring-primary-500">
                    <label for="force_password_change" class="text-sm text-gray-700 dark:text-gray-300">Force password change on first login</label>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="<?= url('users') ?>" class="px-4 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:bg-dark-bg">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                        Create User
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    var employees = <?= json_encode($employeeJson, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var empMap = {};
    for (var i = 0; i < employees.length; i++) {
        empMap[employees[i].id] = employees[i];
    }

    // Elements
    var modeInput     = document.getElementById('user_mode');
    var btnEmployee   = document.getElementById('btn-mode-employee');
    var btnManual     = document.getElementById('btn-mode-manual');
    var empSection    = document.getElementById('employee-section');
    var fieldsDiv     = document.getElementById('user-fields');
    var infoBanner    = document.getElementById('emp-info-banner');
    var fullNameEl    = document.getElementById('full_name');
    var usernameEl    = document.getElementById('username');
    var emailEl       = document.getElementById('email');
    var phoneEl       = document.getElementById('phone');
    var emailHint     = document.getElementById('email-hint');
    var searchInput   = document.getElementById('employee_search');
    var dropdown      = document.getElementById('employee-dropdown');
    var empIdInput    = document.getElementById('employee_id');
    var selectedBadge = document.getElementById('selected-employee');
    var selectedName  = document.getElementById('selected-emp-name');
    var selectedCode  = document.getElementById('selected-emp-code');
    var clearBtn      = document.getElementById('clear-employee');
    var tableRows     = document.querySelectorAll('.employee-row');

    var activeClass = 'bg-primary-800 text-white';
    var inactiveClass = 'bg-gray-50 dark:bg-dark-bg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-dark-border';

    // ---- MODE TOGGLE ----
    function setMode(mode) {
        modeInput.value = mode;
        if (mode === 'employee') {
            btnEmployee.className = btnEmployee.className.replace(inactiveClass, '').trim();
            if (btnEmployee.className.indexOf(activeClass) < 0) btnEmployee.className += ' ' + activeClass;
            btnManual.className = btnManual.className.replace(activeClass, '').trim();
            if (btnManual.className.indexOf(inactiveClass) < 0) btnManual.className += ' ' + inactiveClass;
            empSection.style.display = '';
            // Only show fields if employee selected
            fieldsDiv.style.display = empIdInput.value ? 'block' : 'none';
            infoBanner.classList.remove('hidden');
        } else {
            btnManual.className = btnManual.className.replace(inactiveClass, '').trim();
            if (btnManual.className.indexOf(activeClass) < 0) btnManual.className += ' ' + activeClass;
            btnEmployee.className = btnEmployee.className.replace(activeClass, '').trim();
            if (btnEmployee.className.indexOf(inactiveClass) < 0) btnEmployee.className += ' ' + inactiveClass;
            empSection.style.display = 'none';
            empIdInput.value = '';
            fieldsDiv.style.display = 'block';
            infoBanner.classList.add('hidden');
            // Make fields editable for manual mode
            setFieldsEditable(true);
            emailHint.style.display = 'none';
        }
    }

    btnEmployee.addEventListener('click', function() { setMode('employee'); });
    btnManual.addEventListener('click', function() {
        setMode('manual');
        fullNameEl.value = '';
        usernameEl.value = '';
        emailEl.value = '';
        phoneEl.value = '';
        selectedBadge.classList.add('hidden');
        searchInput.value = '';
    });

    function setFieldsEditable(editable) {
        fullNameEl.readOnly = !editable;
        phoneEl.readOnly = !editable;
        emailEl.readOnly = false;
        if (editable) {
            fullNameEl.classList.remove('bg-gray-50', 'dark:bg-dark-bg', 'cursor-not-allowed');
            fullNameEl.classList.add('bg-white', 'dark:bg-dark-card');
            phoneEl.classList.remove('bg-gray-50', 'dark:bg-dark-bg', 'cursor-not-allowed');
            phoneEl.classList.add('bg-white', 'dark:bg-dark-card');
            emailEl.classList.remove('bg-gray-50', 'dark:bg-dark-bg', 'cursor-not-allowed');
            emailEl.classList.add('bg-white', 'dark:bg-dark-card');
        } else {
            fullNameEl.classList.add('bg-gray-50', 'dark:bg-dark-bg', 'cursor-not-allowed');
            fullNameEl.classList.remove('bg-white', 'dark:bg-dark-card');
        }
    }

    // ---- SEARCH DROPDOWN ----
    function renderDropdown(filter) {
        var html = '';
        var q = (filter || '').toLowerCase();
        var count = 0;
        for (var i = 0; i < employees.length; i++) {
            var e = employees[i];
            var text = e.full_name + ' ' + e.emp_code;
            if (q && text.toLowerCase().indexOf(q) < 0) continue;
            var disabled = e.has_account;
            html += '<div class="employee-option px-3 py-2 text-sm ' +
                (disabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer hover:bg-primary-50 dark:hover:bg-primary-900/20') +
                '" data-id="' + e.id + '" data-disabled="' + (disabled ? '1' : '0') + '">' +
                '<div class="font-medium text-gray-900 dark:text-dark-text">' + escHtml(e.full_name) + '</div>' +
                '<div class="text-xs text-gray-500 dark:text-gray-400">' + escHtml(e.emp_code) + ' &middot; ' + escHtml(e.role) +
                (disabled ? ' &mdash; <span class="text-red-500">Already has account</span>' : '') +
                '</div></div>';
            count++;
        }
        if (count === 0) {
            html = '<div class="px-3 py-3 text-sm text-gray-400 text-center">No employees found</div>';
        }
        dropdown.innerHTML = html;
        dropdown.classList.remove('hidden');

        // Attach click
        var opts = dropdown.querySelectorAll('.employee-option');
        for (var j = 0; j < opts.length; j++) {
            opts[j].addEventListener('click', function() {
                if (this.getAttribute('data-disabled') === '1') return;
                selectEmployee(parseInt(this.getAttribute('data-id')));
            });
        }
    }

    function escHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str || ''));
        return d.innerHTML;
    }

    searchInput.addEventListener('focus', function() { renderDropdown(this.value); });
    searchInput.addEventListener('input', function() { renderDropdown(this.value); });
    document.addEventListener('click', function(ev) {
        if (!dropdown.contains(ev.target) && ev.target !== searchInput) {
            dropdown.classList.add('hidden');
        }
    });

    // ---- SELECT EMPLOYEE ----
    function selectEmployee(empId) {
        var emp = empMap[empId];
        if (!emp || emp.has_account) return;

        empIdInput.value = emp.id;
        searchInput.value = emp.full_name;
        dropdown.classList.add('hidden');

        // Show badge
        selectedName.textContent = emp.full_name;
        selectedCode.textContent = '(' + emp.emp_code + ')';
        selectedBadge.classList.remove('hidden');

        // Show and fill fields
        fieldsDiv.style.display = 'block';
        infoBanner.classList.remove('hidden');
        fullNameEl.value  = emp.full_name;
        fullNameEl.readOnly = true;
        fullNameEl.classList.add('bg-gray-50', 'dark:bg-dark-bg', 'cursor-not-allowed');
        fullNameEl.classList.remove('bg-white', 'dark:bg-dark-card');
        usernameEl.value  = emp.username;
        phoneEl.value     = emp.phone || '';
        phoneEl.readOnly  = true;
        phoneEl.classList.add('bg-gray-50', 'dark:bg-dark-bg', 'cursor-not-allowed');
        phoneEl.classList.remove('bg-white', 'dark:bg-dark-card');

        if (emp.email) {
            emailEl.value    = emp.email;
            emailEl.readOnly = true;
            emailEl.classList.add('bg-gray-50', 'dark:bg-dark-bg', 'cursor-not-allowed');
            emailEl.classList.remove('bg-white', 'dark:bg-dark-card');
            emailHint.style.display = 'none';
        } else {
            emailEl.value    = '';
            emailEl.readOnly = false;
            emailEl.classList.remove('bg-gray-50', 'dark:bg-dark-bg', 'cursor-not-allowed');
            emailEl.classList.add('bg-white', 'dark:bg-dark-card');
            emailHint.style.display = 'block';
        }

        // Highlight table row
        for (var k = 0; k < tableRows.length; k++) {
            tableRows[k].classList.remove('bg-primary-50', 'dark:bg-primary-900/10');
            if (parseInt(tableRows[k].getAttribute('data-id')) === empId) {
                tableRows[k].classList.add('bg-primary-50', 'dark:bg-primary-900/10');
            }
        }
    }

    // Table row click
    for (var r = 0; r < tableRows.length; r++) {
        tableRows[r].addEventListener('click', function() {
            if (this.getAttribute('data-has-account') === '1') return;
            selectEmployee(parseInt(this.getAttribute('data-id')));
        });
    }

    // Clear button
    clearBtn.addEventListener('click', function() {
        empIdInput.value = '';
        searchInput.value = '';
        selectedBadge.classList.add('hidden');
        fieldsDiv.style.display = 'none';
        fullNameEl.value = '';
        usernameEl.value = '';
        emailEl.value = '';
        phoneEl.value = '';
        for (var k = 0; k < tableRows.length; k++) {
            tableRows[k].classList.remove('bg-primary-50', 'dark:bg-primary-900/10');
        }
    });

    // ---- INIT ----
    var savedMode = modeInput.value || 'employee';
    setMode(savedMode);

    // Re-select on validation error return
    if (savedMode === 'employee' && empIdInput.value) {
        selectEmployee(parseInt(empIdInput.value));
    }
    if (savedMode === 'manual') {
        fieldsDiv.style.display = 'block';
    }
})();
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Add New User';
require APP_ROOT . '/templates/layout.php';
