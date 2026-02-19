<?php
/**
 * Communication — Save Announcement
 */
verify_csrf();

$id = input_int('id');

$errors = validate($_POST, [
    'title'   => 'required|max:255',
    'content' => 'required',
]);

if ($errors) {
    set_flash('error', implode('<br>', $errors));
    redirect($id ? url('communication', 'announcement-edit') . "&id={$id}" : url('communication', 'announcement-create'));
}

$data = [
    'title'           => trim($_POST['title']),
    'content'         => trim($_POST['content']),
    'target_audience' => $_POST['target_audience'] ?? 'all',
    'publish_date'    => !empty($_POST['publish_date']) ? date('Y-m-d H:i:s', strtotime($_POST['publish_date'])) : date('Y-m-d H:i:s'),
    'status'          => $_POST['status'] ?? 'published',
    'is_pinned'       => isset($_POST['is_pinned']) ? 1 : 0,
];

if ($id) {
    db_update('announcements', $data, 'id = ?', [$id]);
    audit_log('announcement_update', 'announcements', $id);
    set_flash('success', 'Announcement updated.');
} else {
    $data['created_by'] = current_user_id();
    $id = db_insert('announcements', $data);
    audit_log('announcement_create', 'announcements', $id);
    set_flash('success', 'Announcement published.');
}

redirect(url('communication', 'announcements'));
