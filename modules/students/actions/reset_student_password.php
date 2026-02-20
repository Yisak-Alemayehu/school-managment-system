<?php
/**
 * Reset Student Password Action
 */
csrf_protect();

$mode = $_POST['mode'] ?? 'single';

if ($mode === 'single') {
    $identifier  = trim($_POST['identifier'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');

    if (!$identifier) {
        set_flash('error', 'Please provide an admission number or username.');
        redirect(url('students', 'reset-password'));
    }

    // Find user by admission_no or username
    $user = db_fetch_one(
        "SELECT u.id FROM users u
           JOIN students s ON s.user_id = u.id
          WHERE s.admission_no = ? AND s.deleted_at IS NULL
          LIMIT 1",
        [$identifier]
    );
    if (!$user) {
        $user = db_fetch_one("SELECT id FROM users WHERE username = ? LIMIT 1", [$identifier]);
    }

    if (!$user) {
        set_flash('error', 'Student not found with that admission number or username.');
        redirect(url('students', 'reset-password'));
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
    redirect(url('students', 'reset-password'));
}

// Bulk mode
$classId    = (int)($_POST['class_id']    ?? 0);
$sectionId  = (int)($_POST['section_id']  ?? 0);
$passMode   = $_POST['bulk_password_mode'] ?? 'adm_no';

if (!$classId) {
    set_flash('error', 'Please select a class.');
    redirect(url('students', 'reset-password'));
}

$where  = ["e.status = 'active'", "s.deleted_at IS NULL", "s.user_id IS NOT NULL"];
$params = [];

if ($sectionId) {
    $where[]  = "e.section_id = ?";
    $params[] = $sectionId;
} else {
    $where[]  = "c.id = ?";
    $params[] = $classId;
}

$students = db_fetch_all(
    "SELECT s.user_id, s.admission_no, s.date_of_birth
       FROM students s
       JOIN enrollments e ON e.student_id = s.id
       JOIN sections sec ON sec.id = e.section_id
       JOIN classes c ON c.id = sec.class_id
      WHERE " . implode(' AND ', $where),
    $params
);

$count = 0;
foreach ($students as $st) {
    $plain = match ($passMode) {
        'dob'    => $st['date_of_birth'] ? date('dmY', strtotime($st['date_of_birth'])) : $st['admission_no'],
        'random' => substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 8),
        default  => $st['admission_no'],
    };
    db_update('users',
        ['password_hash' => password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12])],
        'id = ?',
        [$st['user_id']]
    );
    $count++;
}

set_flash('success', "Passwords reset for $count student(s).");
redirect(url('students', 'reset-password'));
