<?php
/**
 * Exams — Save Assignment Action
 */
csrf_protect();

$id   = input_int('id');
$data = [
    'title'       => input('title'),
    'class_id'    => input_int('class_id'),
    'subject_id'  => input_int('subject_id'),
    'session_id'  => input_int('session_id'),
    'term_id'     => input_int('term_id'),
    'description' => input('description'),
    'due_date'    => input('due_date'),
    'total_marks' => input_int('total_marks'),
    'status'      => in_array(input('status'), ['draft', 'published']) ? input('status') : 'draft',
];

$errors = validate($data, [
    'title'       => 'required|max:200',
    'class_id'    => 'required|integer',
    'subject_id'  => 'required|integer',
    'due_date'    => 'required|date',
    'total_marks' => 'required|integer',
]);

if ($errors) {
    set_validation_errors($errors);
    set_old_input();
    redirect_back();
}

// Handle file upload
if (!empty($_FILES['file']['name'])) {
    $upload = handle_upload('file', 'assignments');
    if ($upload['success']) {
        $data['file_path'] = $upload['path'];
        // Delete old file if editing
        if ($id) {
            $old = db_fetch_value("SELECT file_path FROM assignments WHERE id = ?", [$id]);
            if ($old) delete_upload($old);
        }
    } else {
        set_flash('error', $upload['error']);
        set_old_input();
        redirect_back();
    }
}

if ($id) {
    db_update('assignments', $data, 'id = ?', [$id]);
    audit_log('assignment.update', "Updated assignment: {$data['title']}");
    set_flash('success', 'Assignment updated.');
} else {
    $data['created_by'] = auth_user()['id'];
    db_insert('assignments', $data);
    audit_log('assignment.create', "Created assignment: {$data['title']}");
    set_flash('success', 'Assignment created.');
}

redirect(url('exams', 'assignments'));
