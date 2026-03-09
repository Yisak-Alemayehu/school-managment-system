<?php
/**
 * HR — Save Employee Action
 * Creates or updates an employee record.
 */
csrf_protect();

$id = input_int('id');

require_once APP_ROOT . '/core/ethiopian_calendar.php';
require_once APP_ROOT . '/core/payroll.php';

// Convert EC dates to Gregorian
$dobEc    = trim(input('date_of_birth_ec'));
$hireEc   = trim(input('hire_date_ec'));

$dobGreg   = $dobEc   ? ec_str_to_gregorian($dobEc)   : null;
$hireGreg  = $hireEc  ? ec_str_to_gregorian($hireEc)  : null;

$data = [
    'first_name'              => trim(input('first_name')),
    'father_name'             => trim(input('father_name')),
    'grandfather_name'        => trim(input('grandfather_name')),
    'first_name_am'           => trim(input('first_name_am')) ?: null,
    'father_name_am'          => trim(input('father_name_am')) ?: null,
    'grandfather_name_am'     => trim(input('grandfather_name_am')) ?: null,
    'gender'                  => input('gender'),
    'date_of_birth_ec'        => $dobEc ?: null,
    'date_of_birth_gregorian' => $dobGreg ?: null,
    'phone'                   => trim(input('phone')),
    'email'                   => trim(input('email')) ?: null,
    'address'                 => trim(input('address')) ?: null,
    'emergency_contact_name'  => trim(input('emergency_contact_name')) ?: null,
    'emergency_contact_phone' => trim(input('emergency_contact_phone')) ?: null,
    'department_id'           => input_int('department_id') ?: null,
    'position'                => trim(input('position')),
    'qualification'           => trim(input('qualification')) ?: null,
    'role'                    => input('role'),
    'employment_type'         => input('employment_type'),
    'start_date_ec'           => $hireEc ?: null,
    'start_date_gregorian'    => $hireGreg ?: null,
    'basic_salary'            => (float) input('basic_salary'),
    'transport_allowance'     => (float) input('transport_allowance'),
    'position_allowance'      => (float) input('position_allowance'),
    'other_allowance'         => (float) input('other_allowance'),
    'other_deductions'        => (float) input('other_deductions'),
    'status'                  => input('status') ?: 'active',
    'tin_number'              => trim(input('tin_number')) ?: null,
    'pension_number'          => trim(input('pension_number')) ?: null,
    'bank_name'               => trim(input('bank_name')) ?: null,
    'bank_account'            => trim(input('bank_account')) ?: null,
    'user_id'                 => input_int('user_id') ?: null,
    'updated_by'              => auth_user_id(),
];

// ── Validation ──
$errors = validate($data, [
    'first_name'       => 'required|max:100',
    'father_name'      => 'required|max:100',
    'grandfather_name' => 'required|max:100',
    'gender'           => 'required|in:male,female',
    'phone'            => 'required',
    'position'         => 'required|max:100',
    'role'             => 'required|in:teacher,admin,accountant,librarian,support_staff',
    'employment_type'  => 'required|in:permanent,full_time,contract,part_time,temporary',
    'basic_salary'     => 'required',
]);

// Validate EC dates
if ($dobEc && !ec_validate_date($dobEc)) {
    $errors['date_of_birth_ec'] = 'Invalid Ethiopian date format. Use DD/MM/YYYY.';
}
if ($hireEc && !ec_validate_date($hireEc)) {
    $errors['start_date_ec'] = 'Invalid Ethiopian date format. Use DD/MM/YYYY.';
}

// Validate department exists
if ($data['department_id']) {
    $dept = db_fetch_one("SELECT id FROM hr_departments WHERE id = ? AND deleted_at IS NULL", [$data['department_id']]);
    if (!$dept) {
        $errors['department_id'] = 'Selected department not found.';
    }
}

// Check unique email
if ($data['email']) {
    $dupEmail = db_fetch_one(
        "SELECT id FROM hr_employees WHERE email = ? AND deleted_at IS NULL" . ($id ? " AND id != ?" : ""),
        $id ? [$data['email'], $id] : [$data['email']]
    );
    if ($dupEmail) {
        $errors['email'] = 'An employee with this email already exists.';
    }
}

// Check unique TIN
if ($data['tin_number']) {
    $dupTin = db_fetch_one(
        "SELECT id FROM hr_employees WHERE tin_number = ? AND deleted_at IS NULL" . ($id ? " AND id != ?" : ""),
        $id ? [$data['tin_number'], $id] : [$data['tin_number']]
    );
    if ($dupTin) {
        $errors['tin_number'] = 'An employee with this TIN already exists.';
    }
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

// Handle photo upload
if (!empty($_FILES['photo']['name'])) {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (in_array($_FILES['photo']['type'], $allowed, true) && $_FILES['photo']['size'] <= 2 * 1024 * 1024) {
        $ext  = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $name = 'emp_' . ($id ?: 'new') . '_' . time() . '.' . $ext;
        $dest = APP_ROOT . '/storage/uploads/hr/' . $name;
        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
            $data['photo'] = 'hr/' . $name;
        }
    }
}

if ($id) {
    try {
        db_update('hr_employees', $data, 'id = ?', [$id]);
        audit_log('hr.employee.update', "Updated employee ID: {$id}");
        set_flash('success', 'Employee updated successfully.');
    } catch (\Throwable $e) {
        error_log("Employee update failed: " . $e->getMessage());
        set_flash('error', 'Failed to update employee. Please try again.');
        set_old_input();
        redirect_back();
    }
} else {
    // Generate employee ID
    $data['employee_id'] = payroll_next_employee_id();
    $data['created_by'] = auth_user_id();

    try {
        $newId = db_insert('hr_employees', $data);
        audit_log('hr.employee.create', "Created employee: {$data['employee_id']}");
    } catch (\Throwable $e) {
        error_log("Employee insert failed: " . $e->getMessage());
        set_flash('error', 'Failed to create employee. Please try again.');
        set_old_input();
        redirect_back();
    }

    // Auto-generate Employment Contract PDF
    try {
        require_once APP_ROOT . '/core/pdf_contract.php';
        $contractDir = APP_ROOT . '/storage/uploads/contracts';
        if (!is_dir($contractDir)) {
            mkdir($contractDir, 0755, true);
        }
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', ($data['first_name'] ?? '') . '_' . ($data['father_name'] ?? ''));
        $contractPath = $contractDir . "/Contract_{$safeName}_{$data['employee_id']}.pdf";
        pdf_contract($newId, 'F', $contractPath);
        audit_log('hr.contract.generate', "Auto-generated contract for: {$data['employee_id']}");
    } catch (\Throwable $e) {
        // Contract generation failure should not block employee creation
        error_log("Contract PDF generation failed for employee {$data['employee_id']}: " . $e->getMessage());
    }

    set_flash('success', 'Employee created successfully. Contract PDF generated.');
}

redirect(url('hr', 'employees'));
