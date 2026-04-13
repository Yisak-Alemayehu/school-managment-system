<?php
/**
 * Generate Username & Password for Parent / Guardian accounts
 * Admin-only action.
 */
csrf_protect();

$ids            = array_map('intval', $_POST['ids'] ?? []);
$overwrite      = !empty($_POST['overwrite']);
$usernameFormat = $_POST['username_format'] ?? 'phone';
$passwordMode   = $_POST['password_mode']   ?? 'phone';
$customPassword = trim($_POST['custom_password'] ?? '');

if (empty($ids)) {
    set_flash('error', 'No parents selected.');
    redirect(url('students', 'parent-credentials'));
}

$generated = 0;
$skipped   = 0;
$errors    = [];

foreach ($ids as $guardianId) {
    $guardian = db_fetch_one(
        "SELECT id, first_name, last_name, full_name, phone, user_id
           FROM guardians
          WHERE id = ?
          LIMIT 1",
        [$guardianId]
    );
    if (!$guardian) continue;

    // Skip if already has credentials and overwrite not requested
    if ($guardian['user_id'] && !$overwrite) {
        $skipped++;
        continue;
    }

    // Build username
    switch ($usernameFormat) {
        case 'firstlast':
            $parts    = array_map('strtolower', explode(' ', trim($guardian['full_name'])));
            $username = implode('.', array_slice($parts, 0, 2));
            break;
        case 'firstname_id':
            $username = strtolower(explode(' ', $guardian['first_name'])[0]) . $guardian['id'];
            break;
        default: // phone
            $username = preg_replace('/\D/', '', $guardian['phone']);
    }

    // Ensure username uniqueness
    $base = $username; $suffix = 1;
    while (db_fetch_value(
        "SELECT COUNT(*) FROM users WHERE username = ? AND id != ?",
        [$username, $guardian['user_id'] ?? 0]
    )) {
        $username = $base . $suffix++;
    }

    // Build password
    if ($customPassword) {
        $plainPassword = $customPassword;
    } else {
        switch ($passwordMode) {
            case 'random':
                $plainPassword = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, 8);
                break;
            default: // phone
                $plainPassword = preg_replace('/\D/', '', $guardian['phone']) ?: 'Pass@1234';
        }
    }

    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    try {
        if ($guardian['user_id'] && $overwrite) {
            db_update('users',
                ['username' => $username, 'password_hash' => $hashedPassword],
                'id = ?',
                [$guardian['user_id']]
            );
        } else {
            $userId = db_insert('users', [
                'username'      => $username,
                'email'         => $username . '@parent.local',
                'password_hash' => $hashedPassword,
                'full_name'     => $guardian['full_name'],
                'is_active'     => 1,
            ]);
            // Link user to guardian record
            db_update('guardians', ['user_id' => $userId], 'id = ?', [$guardianId]);
            // Assign parent role
            $roleId = db_fetch_value("SELECT id FROM roles WHERE slug = 'parent' LIMIT 1");
            if ($roleId) {
                db_query("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)", [$userId, $roleId]);
            }
        }
        $generated++;
    } catch (\Exception $e) {
        $errors[] = $guardian['full_name'] . ': ' . $e->getMessage();
    }
}

$msg = "Credentials generated for $generated parent(s).";
if ($skipped)        $msg .= " $skipped skipped (already have credentials).";
if (!empty($errors)) $msg .= ' Errors: ' . implode('; ', $errors);

set_flash($errors ? 'error' : 'success', $msg);
redirect(url('students', 'parent-credentials'));
