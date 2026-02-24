<?php
/**
 * Fee Management — Save Student Group (Create / Update)
 */

if (!is_post()) { redirect('finance', 'fm-groups'); }
verify_csrf();

$id     = input_int('id');
$isEdit = (bool) $id;

$errors = validate($_POST, [
    'name' => 'required|max:150',
]);

$name   = trim($_POST['name']);
$desc   = trim($_POST['description'] ?? '');
$status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

// Unique name check
$nameExists = db_fetch_one(
    "SELECT id FROM student_groups WHERE name = ? AND id != ?",
    [$name, $id ?: 0]
);
if ($nameExists) {
    $errors['name'] = 'Group name must be unique.';
}

if (!empty($errors)) {
    set_validation_errors($errors);
    set_old_input();
    set_flash('error', 'Please fix the errors below.');
    if ($isEdit) {
        redirect('finance', 'fm-group-form', $id);
    } else {
        redirect('finance', 'fm-group-form');
    }
}

try {
    $data = [
        'name'        => $name,
        'description' => $desc ?: null,
        'status'      => $status,
    ];

    if ($isEdit) {
        $existing = db_fetch_one("SELECT * FROM student_groups WHERE id = ?", [$id]);
        if (!$existing) {
            set_flash('error', 'Group not found.');
            redirect('finance', 'fm-groups');
        }
        db_update('student_groups', $data, 'id = ?', [$id]);
        $groupId = $id;
    } else {
        $data['created_by'] = auth_user_id();
        $groupId = db_insert('student_groups', $data);
    }

    db_insert('finance_audit_log', [
        'user_id'     => auth_user_id(),
        'action'      => $isEdit ? 'group_updated' : 'group_created',
        'entity_type' => 'student_group',
        'entity_id'   => $groupId,
        'details'     => json_encode(['name' => $name]),
        'ip_address'  => get_client_ip(),
    ]);

    set_flash('success', $isEdit ? 'Group updated.' : 'Group created.');
    redirect('finance', 'fm-group-members', $groupId);

} catch (Throwable $e) {
    error_log('Group save error: ' . $e->getMessage());
    set_flash('error', 'Failed to save group.');
    set_old_input();
    redirect('finance', 'fm-group-form');
}
