<?php
/**
 * Finance — Save Fee Structure
 */
verify_csrf();

$id = input_int('id');

$errors = validate($_POST, [
    'class_id'        => 'required|numeric',
    'fee_category_id' => 'required|numeric',
    'amount'          => 'required|numeric',
]);

if ($errors) {
    set_flash('error', implode('<br>', $errors));
    redirect($id ? url('finance', 'fee-structure-edit') . "&id={$id}" : url('finance', 'fee-structure-create'));
}

$data = [
    'class_id'        => (int)$_POST['class_id'],
    'fee_category_id' => (int)$_POST['fee_category_id'],
    'session_id'      => get_active_session_id(),
    'amount'          => (float)$_POST['amount'],
    'frequency'       => $_POST['frequency'] ?? 'term',
    'due_date'        => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
    'late_fee'        => (float)($_POST['late_fee'] ?? 0),
    'description'     => trim($_POST['description'] ?? '') ?: null,
];

// Check duplicate (same class + category + session) when creating
if (!$id) {
    $dup = db_fetch_one(
        "SELECT id FROM fee_structures WHERE class_id = ? AND fee_category_id = ? AND session_id = ?",
        [$data['class_id'], $data['fee_category_id'], $data['session_id']]
    );
    if ($dup) {
        set_flash('error', 'A fee structure for this class and category already exists this session.');
        redirect(url('finance', 'fee-structure-create'));
    }
}

if ($id) {
    db_update('fee_structures', $data, 'id = ?', [$id]);
    audit_log('fee_structure_update', 'fee_structures', $id);
    set_flash('success', 'Fee structure updated.');
} else {
    $id = db_insert('fee_structures', $data);
    audit_log('fee_structure_create', 'fee_structures', $id);
    set_flash('success', 'Fee structure created.');
}

redirect(url('finance', 'fee-structures'));
