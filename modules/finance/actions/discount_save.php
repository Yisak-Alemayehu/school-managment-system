<?php
/**
 * Finance — Save Discount
 */
verify_csrf();

$id = input_int('id');

$errors = validate($_POST, [
    'student_id'    => 'required|numeric',
    'discount_type' => 'required',
    'amount'        => 'required|numeric',
]);

if ($errors) {
    set_flash('error', implode('<br>', $errors));
    redirect($id ? url('finance', 'discount-edit') . "&id={$id}" : url('finance', 'discount-create'));
}

$data = [
    'student_id'    => (int)$_POST['student_id'],
    'session_id'    => get_active_session_id(),
    'discount_type' => $_POST['discount_type'],
    'amount'        => (float)$_POST['amount'],
    'reason'        => trim($_POST['reason'] ?? '') ?: null,
    'is_active'     => isset($_POST['is_active']) ? 1 : 0,
];

if ($id) {
    db_update('fee_discounts', $data, 'id = ?', [$id]);
    audit_log('discount_update', 'fee_discounts', $id);
    set_flash('success', 'Discount updated.');
} else {
    $id = db_insert('fee_discounts', $data);
    audit_log('discount_create', 'fee_discounts', $id);
    set_flash('success', 'Discount added.');
}

redirect(url('finance', 'discounts'));
