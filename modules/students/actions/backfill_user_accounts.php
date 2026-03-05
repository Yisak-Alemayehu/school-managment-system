<?php
/**
 * One-time script: Backfill user accounts for students who don't have one.
 *
 * Run from browser as admin or via CLI:
 *   php modules/students/actions/backfill_user_accounts.php
 *
 * Each student gets:
 *   - Username: lowercase admission_no (spaces/slashes replaced with _)
 *   - Password: admission_no (plain, forced password change on first login)
 *   - Role: student
 *
 * Safe to re-run — skips students who already have user_id set.
 */

require_once __DIR__ . '/../../../config/app.php';
require_once CORE_PATH . '/env.php';
require_once CORE_PATH . '/db.php';
require_once CORE_PATH . '/auth.php';
require_once CORE_PATH . '/helpers.php';

// If run from browser, require admin
if (php_sapi_name() !== 'cli') {
    if (!function_exists('auth_user_id') || !auth_user_id()) {
        die('Please log in as admin first.');
    }
    if (!auth_is_super_admin() && !auth_has_role('admin')) {
        die('Admin access required.');
    }
    echo '<pre>';
}

$students = db_fetch_all("
    SELECT s.id, s.admission_no, s.first_name, s.last_name, s.gender,
           s.date_of_birth, s.phone, s.email, s.address, s.photo
      FROM students s
     WHERE s.user_id IS NULL AND s.status = 'active' AND s.deleted_at IS NULL
     ORDER BY s.id
");

$studentRoleId = db_fetch_value("SELECT id FROM roles WHERE slug = 'student' LIMIT 1");

if (!$studentRoleId) {
    die("ERROR: 'student' role not found in roles table.\n");
}

$created  = 0;
$skipped  = 0;
$errors   = 0;

echo "Found " . count($students) . " students without user accounts.\n\n";

foreach ($students as $s) {
    $fullName = trim($s['first_name'] . ' ' . $s['last_name']);
    $username = strtolower(str_replace([' ', '/'], '_', $s['admission_no']));
    $plain    = $s['admission_no'];
    $email    = $s['email'] ?: ($username . '@student.local');

    // Ensure unique username
    if (db_exists('users', 'username = ?', [$username])) {
        $username .= '_' . $s['id'];
    }
    if (db_exists('users', 'email = ?', [$email])) {
        $email = $username . '@student.local';
        if (db_exists('users', 'email = ?', [$email])) {
            $email = $username . '_' . $s['id'] . '@student.local';
        }
    }

    try {
        db_begin();

        $uid = db_insert('users', [
            'username'              => $username,
            'email'                 => $email,
            'password_hash'         => password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]),
            'full_name'             => $fullName,
            'first_name'            => $s['first_name'],
            'last_name'             => $s['last_name'],
            'phone'                 => $s['phone'],
            'gender'                => $s['gender'],
            'date_of_birth'         => $s['date_of_birth'],
            'address'               => $s['address'],
            'avatar'                => $s['photo'],
            'is_active'             => 1,
            'status'                => 'active',
            'force_password_change' => 1,
        ]);

        db_update('students', ['user_id' => $uid], 'id = ?', [$s['id']]);
        db_query("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)", [$uid, $studentRoleId]);

        db_commit();

        echo "  OK  Student #{$s['id']} ({$fullName}) → user '{$username}'\n";
        $created++;

    } catch (Exception $e) {
        db_rollback();
        echo "  ERR Student #{$s['id']} ({$fullName}): {$e->getMessage()}\n";
        $errors++;
    }
}

echo "\nDone. Created: {$created} | Skipped: {$skipped} | Errors: {$errors}\n";

if (php_sapi_name() !== 'cli') {
    echo '</pre>';
}
