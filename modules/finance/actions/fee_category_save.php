<?php
/**
 * Finance — Save Fee Category
 */
verify_csrf();

$id   = input_int('id');
$name = trim($_POST['name'] ?? '');
$type = $_POST['type'] ?? 'tuition';
$desc = trim($_POST['description'] ?? '');

$errors = validate($_POST, [
    'name' => 'required|max:100',
]);

if ($errors) {
    set_flash('error', implode('<br>', $errors));
    redirect(url('finance', 'fee-categories') . ($id ? "&edit={$id}" : ''));
}

if ($id) {
    db_update('fee_categories', [
        'name'        => $name,
        'type'        => $type,
        'description' => $desc ?: null,
    ], 'id = ?', [$id]);
    audit_log('fee_category_update', 'fee_categories', $id);
    set_flash('success', 'Category updated.');
} else {
    $id = db_insert('fee_categories', [
        'name'        => $name,
        'type'        => $type,
        'description' => $desc ?: null,
    ]);
    audit_log('fee_category_create', 'fee_categories', $id);
    set_flash('success', 'Category added.');
}

redirect(url('finance', 'fee-categories'));
