<?php
/**
 * Academics — Toggle Term Active Status
 * Unlike sessions, multiple terms can be active simultaneously.
 */
csrf_protect();

$termId = input_int('term_id');
if (!$termId) { redirect(url('academics', 'terms')); }

$term = db_fetch_one("SELECT * FROM terms WHERE id = ?", [$termId]);
if (!$term) { redirect(url('academics', 'terms')); }

$newStatus = $term['is_active'] ? 0 : 1;
db_update('terms', ['is_active' => $newStatus], 'id = ?', [$termId]);

$action = $newStatus ? 'activated' : 'deactivated';
audit_log("term.{$action}", "Term {$action}: {$term['name']}");
set_flash('success', "Term \"{$term['name']}\" {$action}.");

redirect(url('academics', 'terms') . '&session_id=' . $term['session_id']);
