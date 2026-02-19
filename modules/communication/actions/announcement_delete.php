<?php
/**
 * Communication — Delete Announcement
 */
verify_csrf_get();

$id = input_int('id');
if (!$id) {
    set_flash('error', 'Invalid announcement.');
    redirect(url('communication', 'announcements'));
}

db_delete('announcements', 'id = ?', [$id]);
audit_log('announcement_delete', 'announcements', $id);
set_flash('success', 'Announcement deleted.');
redirect(url('communication', 'announcements'));
