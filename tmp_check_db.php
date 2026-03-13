<?php
// Required for core loader guards
define('APP_ROOT', __DIR__);

require __DIR__ . '/core/env.php';
env_load(__DIR__ . '/.env');
require __DIR__ . '/config/app.php';
require __DIR__ . '/config/database.php';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $tables = ['users', 'enrollments', 'attendance', 'academic_sessions', 'classes', 'sections'];
    foreach ($tables as $t) {
        $exists = $pdo->query("SHOW TABLES LIKE '$t'")->fetchColumn();
        echo "$t: " . ($exists ? 'exists' : 'missing') . "\n";
    }

    $checks = [
        'users' => ['is_active', 'force_password_change', 'role_id'],
        'enrollments' => ['session_id', 'academic_session_id'],
        'attendance' => ['level'],
        'announcements' => ['publish_date'],
        'students' => ['full_name'],
    ];
    foreach ($checks as $tbl => $cols) {
        foreach ($cols as $col) {
            $res = $pdo->query("SHOW COLUMNS FROM $tbl LIKE '$col'")->fetchColumn();
            echo "$tbl.$col: " . ($res ? 'exists' : 'missing') . "\n";
        }
    }

} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
