<?php
/**
 * Reset Parent Password Action
 * Admin-only: single (by username or phone) or bulk (all parent accounts).
 */
csrf_protect();

$mode = $_POST['mode'] ?? 'single';

if ($mode === 'single') {
    $identifier  = trim($_POST['identifier'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');

    if (!$identifier) {
        set_flash('error', 'Please provide a username or phone number.');
        redirect(url('students', 'parent-reset-password'));
    }

    // Look up by username first, then by guardian phone
    $user = db_fetch_one("SELECT id FROM users WHERE username = ? LIMIT 1", [$identifier]);

    if (!$user) {
        // Try matching guardian phone → user_id
        $guardian = db_fetch_one(
            "SELECT user_id FROM guardians WHERE phone = ? AND user_id IS NOT NULL LIMIT 1",
            [$identifier]
        );
        if ($guardian) {
            $user = ['id' => $guardian['user_id']];
        }
    }

    if (!$user) {
        set_flash('error', 'Parent account not found for that username or phone number.');
        redirect(url('students', 'parent-reset-password'));
    }

    if (!$newPassword) {
        $newPassword = substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 8);
    }

    db_update('users',
        ['password_hash' => password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12])],
        'id = ?',
        [$user['id']]
    );

    set_flash('success', "Password reset successfully. New password: $newPassword");
    redirect(url('students', 'parent-reset-password'));
}

// ── Bulk mode: reset all parent accounts ──────────────────────────────────
$passMode       = $_POST['bulk_password_mode'] ?? 'phone';
$customPassword = trim($_POST['custom_password'] ?? '');

// Fetch all guardians that have a linked user account
$parents = db_fetch_all(
    "SELECT g.phone, g.user_id
       FROM guardians g
      WHERE g.user_id IS NOT NULL"
);

if (empty($parents)) {
    set_flash('error', 'No parent accounts found.');
    redirect(url('students', 'parent-reset-password'));
}

$count = 0;
foreach ($parents as $p) {
    if ($customPassword) {
        $plain = $customPassword;
    } else {
        $plain = match ($passMode) {
            'random' => substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 8),
            default  => preg_replace('/\D/', '', $p['phone']) ?: 'Pass@1234',
        };
    }
    db_update('users',
        ['password_hash' => password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12])],
        'id = ?',
        [$p['user_id']]
    );
    $count++;
}

set_flash('success', "Passwords reset for $count parent account(s).");
redirect(url('students', 'parent-reset-password'));
