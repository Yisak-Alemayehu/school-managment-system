<?php
define('APP_ROOT', __DIR__);
require __DIR__ . '/core/env.php';
env_load(__DIR__ . '/.env');
require __DIR__ . '/config/app.php';
require __DIR__ . '/config/database.php';
require __DIR__ . '/core/db.php';
db_connect();

echo "=== All terms ===\n";
$terms = db_fetch_all("SELECT t.id, t.name, t.is_active, t.session_id, acs.name AS sess_name, acs.is_active AS sess_active FROM terms t JOIN academic_sessions acs ON acs.id = t.session_id ORDER BY t.session_id, t.start_date");
foreach ($terms as $t) {
    echo "  id={$t['id']} name={$t['name']} term_active={$t['is_active']} session={$t['session_id']} sess={$t['sess_name']} sess_active={$t['sess_active']}\n";
}

echo "\n=== Active enrollments (first 5 students) ===\n";
$enr = db_fetch_all("SELECT e.student_id, e.session_id, e.class_id, e.status FROM enrollments e WHERE e.status = 'active' LIMIT 5");
foreach ($enr as $e) {
    echo "  student={$e['student_id']} session={$e['session_id']} class={$e['class_id']}\n";
}

if (!empty($enr)) {
    $sid = $enr[0]['student_id'];
    echo "\n=== Terms for student {$sid} (via enrollment subquery) ===\n";
    $st = db_fetch_all("SELECT t.id, t.name, t.is_active, t.session_id FROM terms t WHERE t.session_id IN (SELECT DISTINCT e2.session_id FROM enrollments e2 WHERE e2.student_id = ? AND e2.status = 'active') ORDER BY t.session_id DESC, t.start_date ASC", [$sid]);
    foreach ($st as $t) {
        echo "  id={$t['id']} name={$t['name']} active={$t['is_active']} session={$t['session_id']}\n";
    }
}
