<?php
/**
 * Academics — Save Academic Session (Fixed)
 * Generates slug from name (required NOT NULL UNIQUE column).
 */
csrf_protect();
auth_require_permission('academics_manage');

$id = input_int('id');

$rules = [
    'name'       => 'required|max:100',
    'start_date' => 'required',
    'end_date'   => 'required',
];

$errors = validate($_POST, $rules);

if ($errors) {
    set_validation_errors($errors);
    set_old_input($_POST);
    redirect_back();
}

$name       = input('name');
$start_date = input('start_date');
$end_date   = input('end_date');
$is_active  = input('is_active') ? 1 : 0;

// Generate slug
$slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-'));

// If activating, deactivate all others
if ($is_active) {
    db_update('academic_sessions', ['is_active' => 0], '1 = 1', []);
}

$data = [
    'name'       => $name,
    'slug'       => $slug,
    'start_date' => $start_date,
    'end_date'   => $end_date,
    'is_active'  => $is_active,
];

try {
    if ($id) {
        // Ensure slug uniqueness for update
        $dup = db_fetch_one("SELECT id FROM academic_sessions WHERE slug = ? AND id != ?", [$slug, $id]);
        if ($dup) {
            $slug .= '-' . $id;
            $data['slug'] = $slug;
        }
        db_update('academic_sessions', $data, 'id = ?', [$id]);
        set_flash('success', 'Academic session updated.');
        audit_log('session_update', "Updated session: {$name}");
    } else {
        // Ensure slug uniqueness for insert
        $dup = db_fetch_one("SELECT id FROM academic_sessions WHERE slug = ?", [$slug]);
        if ($dup) {
            $slug .= '-' . time();
            $data['slug'] = $slug;
        }
        db_insert('academic_sessions', $data);
        set_flash('success', 'Academic session created.');
        audit_log('session_create', "Created session: {$name}");
    }
} catch (Throwable $ex) {
    set_flash('error', 'Failed to save session: ' . $ex->getMessage());
}

redirect('academics', 'sessions');
