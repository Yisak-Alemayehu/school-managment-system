<?php
/**
 * HR — Save Leave Request
 */
csrf_protect();

$id = input_int('id');

$startEc   = trim(input('start_date_ec'));
$endEc     = trim(input('end_date_ec'));
$startGreg = trim(input('start_date_gregorian'));
$endGreg   = trim(input('end_date_gregorian'));

// Auto-convert dates
if ($startEc && !$startGreg) $startGreg = ec_str_to_gregorian($startEc);
if ($endEc && !$endGreg) $endGreg = ec_str_to_gregorian($endEc);
if ($startGreg && !$startEc) $startEc = gregorian_str_to_ec($startGreg);
if ($endGreg && !$endEc) $endEc = gregorian_str_to_ec($endGreg);

// Calculate leave days (working days)
$days = 1;
if ($startGreg && $endGreg) {
    $days = ec_working_days($startGreg, $endGreg);
    if ($days < 1) $days = 1;
}

$data = [
    'employee_id'          => input_int('employee_id'),
    'leave_type_id'        => input_int('leave_type_id'),
    'start_date_ec'        => $startEc ?: null,
    'start_date_gregorian' => $startGreg,
    'end_date_ec'          => $endEc ?: null,
    'end_date_gregorian'   => $endGreg,
    'days'                 => $days,
    'reason'               => trim(input('reason')) ?: null,
    'status'               => 'pending',
];

$errors = validate($data, [
    'employee_id'          => 'required',
    'leave_type_id'        => 'required',
    'start_date_gregorian' => 'required',
    'end_date_gregorian'   => 'required',
]);

// Validate employee
if (!$errors && $data['employee_id']) {
    $emp = db_fetch_one("SELECT id FROM hr_employees WHERE id = ? AND deleted_at IS NULL", [$data['employee_id']]);
    if (!$emp) {
        $errors['employee_id'] = 'Employee not found.';
    }
}

// Validate leave type and check balance
if (!$errors && $data['leave_type_id']) {
    $leaveType = db_fetch_one("SELECT * FROM hr_leave_types WHERE id = ? AND status = 'active'", [$data['leave_type_id']]);
    if (!$leaveType) {
        $errors['leave_type_id'] = 'Leave type not found.';
    } elseif ($leaveType['days_allowed'] > 0) {
        // Check used days this year
        $ecYear = ec_current_year();
        $usedDays = db_fetch_value(
            "SELECT COALESCE(SUM(days), 0) FROM hr_leave_requests
             WHERE employee_id = ? AND leave_type_id = ? AND status IN ('pending','approved')
             AND YEAR(start_date_gregorian) = YEAR(CURDATE())" . ($id ? " AND id != ?" : ""),
            $id ? [$data['employee_id'], $data['leave_type_id'], $id] : [$data['employee_id'], $data['leave_type_id']]
        );
        $remaining = $leaveType['days_allowed'] - $usedDays;
        if ($days > $remaining) {
            $errors['days'] = "Insufficient leave balance. Remaining: {$remaining} days.";
        }
    }
}

// Check overlapping leave
if (!$errors) {
    $overlap = db_fetch_one(
        "SELECT id FROM hr_leave_requests
         WHERE employee_id = ? AND status IN ('pending','approved')
         AND start_date_gregorian <= ? AND end_date_gregorian >= ?" . ($id ? " AND id != ?" : ""),
        $id ? [$data['employee_id'], $endGreg, $startGreg, $id]
            : [$data['employee_id'], $endGreg, $startGreg]
    );
    if ($overlap) {
        $errors['start_date_gregorian'] = 'Leave request overlaps with an existing request.';
    }
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

// Handle attachment
if (!empty($_FILES['attachment']['name'])) {
    $file = $_FILES['attachment'];
    if ($file['size'] <= 5 * 1024 * 1024) {
        $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = 'leave_' . $data['employee_id'] . '_' . time() . '.' . $ext;
        $dir  = APP_ROOT . '/storage/uploads/hr/leave';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            $data['attachment'] = 'hr/leave/' . $name;
        }
    }
}

if ($id) {
    unset($data['status']); // Don't reset status on edit
    db_update('hr_leave_requests', $data, 'id = ?', [$id]);
    audit_log('hr.leave.update', "Updated leave request ID: {$id}");
    set_flash('success', 'Leave request updated.');
} else {
    $data['created_by'] = auth_user_id();
    db_insert('hr_leave_requests', $data);
    audit_log('hr.leave.create', "Created leave request for employee ID: {$data['employee_id']}");
    set_flash('success', 'Leave request submitted.');
}

redirect(url('hr', 'leave-requests'));
