<?php
/**
 * Generate Username & Password for Students
 */
csrf_protect();

$mode     = $_POST['mode'] ?? 'class';
$ids      = array_map('intval', $_POST['ids'] ?? []);
$overwrite = !empty($_POST['overwrite']);
$usernameFormat = $_POST['username_format'] ?? 'adm_no';
$passwordMode   = $_POST['password_mode']   ?? 'adm_no';

// Single student manual override
$manualUsername = trim($_POST['manual_username'] ?? '');
$manualPassword = trim($_POST['manual_password'] ?? '');

if (empty($ids)) {
    set_flash('error', 'No students selected.');
    redirect(url('students', 'credentials'));
}

$generated = 0;
$skipped   = 0;
$errors    = [];

foreach ($ids as $studentId) {
    $student = db_fetch_one(
        "SELECT s.id, s.full_name, s.admission_no, s.date_of_birth, s.user_id,
                e.roll_no
           FROM students s
           LEFT JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
          WHERE s.id = ? AND s.deleted_at IS NULL
          LIMIT 1",
        [$studentId]
    );
    if (!$student) continue;

    // Check if user already has credentials
    if ($student['user_id'] && !$overwrite && $mode !== 'single') {
        $skipped++;
        continue;
    }

    // Build username
    if ($mode === 'single' && $manualUsername) {
        $username = $manualUsername;
    } else {
        switch ($usernameFormat) {
            case 'firstlast':
                $parts = explode(' ', strtolower($student['full_name']));
                $username = implode('.', array_slice($parts, 0, 2));
                break;
            case 'firstname_roll':
                $firstName = strtolower(explode(' ', $student['full_name'])[0]);
                $username  = $firstName . ($student['roll_no'] ?? $studentId);
                break;
            default: // adm_no
                $username = strtolower(str_replace([' ', '/'], ['_', '_'], $student['admission_no']));
        }
    }

    // Ensure username is unique
    $base = $username; $suffix = 1;
    while (db_fetch_value("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?", [$username, $student['user_id'] ?? 0])) {
        $username = $base . $suffix++;
    }

    // Build password
    if ($mode === 'single' && $manualPassword) {
        $plainPassword = $manualPassword;
    } else {
        switch ($passwordMode) {
            case 'dob':
                $dob = $student['date_of_birth'] ? date('dmY', strtotime($student['date_of_birth'])) : 'Pass@1234';
                $plainPassword = $dob;
                break;
            case 'random':
                $plainPassword = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, 8);
                break;
            default: // adm_no
                $plainPassword = $student['admission_no'];
        }
    }

    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    try {
        if ($student['user_id'] && $overwrite) {
            // Update existing user
            db_update('users',
                ['username' => $username, 'password_hash' => $hashedPassword],
                'id = ?',
                [$student['user_id']]
            );
        } else {
            // Create new user account
            $userId = db_insert('users', [
                'username'      => $username,
                'email'         => $username . '@student.local',
                'password_hash' => $hashedPassword,
                'full_name'     => $student['full_name'],
                'is_active'     => 1,
            ]);
            // Link to student
            db_update('students', ['user_id' => $userId], 'id = ?', [$studentId]);
            // Assign student role
            $roleId = db_fetch_value("SELECT id FROM roles WHERE slug = 'student' LIMIT 1");
            if ($roleId) {
                db_query("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)", [$userId, $roleId]);
            }
        }
        $generated++;
    } catch (\Exception $e) {
        $errors[] = $student['full_name'] . ': ' . $e->getMessage();
    }
}

$msg = "Credentials generated for $generated student(s).";
if ($skipped)        $msg .= " $skipped skipped (already have credentials).";
if (!empty($errors)) $msg .= ' Errors: ' . implode('; ', $errors);

set_flash($errors ? 'error' : 'success', $msg);
redirect(url('students', 'credentials'));
