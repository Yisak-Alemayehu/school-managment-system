<?php
/**
 * HR — Save Attendance Device
 */
csrf_protect();

$id = input_int('id');

$data = [
    'device_name'     => trim(input('device_name')),
    'device_model'    => input('device_model') ?: 'ZKTeco',
    'ip_address'      => trim(input('ip_address')) ?: null,
    'port'            => input_int('port') ?: 4370,
    'location'        => trim(input('location')) ?: null,
    'status'          => input('status') ?: 'active',
    'connection_type' => input('connection_type') ?: 'api',
];

$errors = validate($data, [
    'device_name' => 'required|max:100',
]);

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

if ($id) {
    db_update('hr_attendance_devices', $data, 'id = ?', [$id]);
    audit_log('hr.device.update', "Updated device: {$data['device_name']}");
    set_flash('success', 'Device updated.');
} else {
    $data['created_by'] = auth_user_id();
    db_insert('hr_attendance_devices', $data);
    audit_log('hr.device.create', "Created device: {$data['device_name']}");
    set_flash('success', 'Device registered.');
}

redirect(url('hr', 'devices'));
