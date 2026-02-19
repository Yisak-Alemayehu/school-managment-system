<?php
/**
 * Academics — Toggle Session Active Status
 */
csrf_protect();

$id = input_int('id');
if (!$id) { redirect(url('academics', 'sessions')); }

$session = db_fetch_one("SELECT * FROM academic_sessions WHERE id = ?", [$id]);
if (!$session) { redirect(url('academics', 'sessions')); }

if ($session['is_active']) {
    // Deactivate
    db_update('academic_sessions', ['is_active' => 0], 'id = ?', [$id]);
    audit_log('session.deactivate', "Deactivated session: {$session['name']}");
    set_flash('success', 'Session deactivated.');
} else {
    // Deactivate all others, activate this one
    db_query("UPDATE academic_sessions SET is_active = 0");
    db_update('academic_sessions', ['is_active' => 1], 'id = ?', [$id]);
    audit_log('session.activate', "Activated session: {$session['name']}");
    set_flash('success', 'Session activated.');
}

redirect(url('academics', 'sessions'));
