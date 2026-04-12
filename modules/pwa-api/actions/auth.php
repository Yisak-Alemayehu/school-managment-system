<?php
/**
 * PWA API — Auth Actions
 * login / logout / me
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// ─────────────────────────────────────────────────────────────
// POST /pwa-api/login
// Body: { "username": "...", "password": "...", "role": "student|parent" }
// ─────────────────────────────────────────────────────────────
function pwa_handle_login(): never
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        pwa_error('Method not allowed.', 405);
    }

    $body     = pwa_request_json();
    $identifier = pwa_str($body, 'username');
    $password   = pwa_str($body, 'password');
    $wantedRole = pwa_str($body, 'role'); // 'student' or 'parent'

    if ($identifier === '' || $password === '') {
        pwa_error('Username and password are required.');
    }

    if (!in_array($wantedRole, ['student', 'parent'], true)) {
        pwa_error('Role must be "student" or "parent".');
    }

    // Reuse existing auth logic (brute-force protection, lockout, etc.)
    $result = auth_attempt($identifier, $password);
    if (is_string($result)) {
        pwa_error($result, 401);
    }

    $user = $result;

    // Determine PWA role and linked record
    if ($wantedRole === 'student') {
        $student = db_fetch_one(
            "SELECT s.id, s.full_name, s.photo, s.status,
                    c.name AS class_name, c.id AS class_id,
                    sec.name AS section_name, sec.id AS section_id,
                    e.roll_no
             FROM students s
             LEFT JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
             LEFT JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
             LEFT JOIN classes c ON c.id = e.class_id
             LEFT JOIN sections sec ON sec.id = e.section_id
             WHERE s.user_id = ? AND s.deleted_at IS NULL AND s.status = 'active'
             ORDER BY e.id DESC LIMIT 1",
            [$user['id']]
        );

        if (!$student) {
            pwa_error('No active student account linked to this user. Please contact the school.', 403);
        }

        $linkedId  = (int) $student['id'];
        $tokenRaw  = pwa_token_create($user['id'], 'student', $linkedId);

        pwa_json([
            'token'   => $tokenRaw,
            'role'    => 'student',
            'user'    => [
                'id'       => (int) $user['id'],
                'name'     => $user['full_name'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'avatar'   => $user['avatar'],
            ],
            'student' => [
                'id'           => $linkedId,
                'full_name'    => $student['full_name'],
                'photo'        => $student['photo'] ? (APP_URL . '/uploads/students/' . $student['photo']) : null,
                'class_name'   => $student['class_name'],
                'section_name' => $student['section_name'],
                'class_id'     => $student['class_id'] ? (int) $student['class_id'] : null,
                'section_id'   => $student['section_id'] ? (int) $student['section_id'] : null,
                'roll_no'      => $student['roll_no'],
            ],
        ]);
    }

    // Parent / Guardian
    $guardian = db_fetch_one(
        "SELECT g.id, g.full_name, g.photo, g.relation
         FROM guardians g
         WHERE g.user_id = ?
         ORDER BY g.id ASC LIMIT 1",
        [$user['id']]
    );

    if (!$guardian) {
        pwa_error('No parent/guardian account linked to this user. Please contact the school.', 403);
    }

    $linkedId = (int) $guardian['id'];
    $tokenRaw = pwa_token_create($user['id'], 'parent', $linkedId);

    // Fetch children
    $children = db_fetch_all(
        "SELECT s.id, s.full_name, s.photo, s.status,
                c.name AS class_name, sec.name AS section_name
         FROM students s
         JOIN student_guardians sg ON sg.student_id = s.id AND sg.guardian_id = ?
         LEFT JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
         LEFT JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
         LEFT JOIN classes c ON c.id = e.class_id
         LEFT JOIN sections sec ON sec.id = e.section_id
         WHERE s.deleted_at IS NULL
         ORDER BY sg.is_primary DESC, s.full_name ASC",
        [$linkedId]
    );

    pwa_json([
        'token'    => $tokenRaw,
        'role'     => 'parent',
        'user'     => [
            'id'       => (int) $user['id'],
            'name'     => $user['full_name'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'avatar'   => $user['avatar'],
        ],
        'guardian' => [
            'id'       => $linkedId,
            'name'     => $guardian['full_name'],
            'relation' => $guardian['relation'],
            'photo'    => $guardian['photo'] ? (APP_URL . '/uploads/guardians/' . $guardian['photo']) : null,
        ],
        'children' => array_map(fn($c) => [
            'id'           => (int) $c['id'],
            'full_name'    => $c['full_name'],
            'photo'        => $c['photo'] ? (APP_URL . '/uploads/students/' . $c['photo']) : null,
            'class_name'   => $c['class_name'],
            'section_name' => $c['section_name'],
            'status'       => $c['status'],
        ], $children),
    ]);
}

// ─────────────────────────────────────────────────────────────
// POST /pwa-api/logout
// Header: Authorization: Bearer {token}
// ─────────────────────────────────────────────────────────────
function pwa_handle_logout(): never
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (str_starts_with($header, 'Bearer ')) {
        $raw = trim(substr($header, 7));
        if (strlen($raw) === 64 && ctype_xdigit($raw)) {
            pwa_token_revoke($raw);
        }
    }
    pwa_json(['message' => 'Logged out successfully.']);
}

// ─────────────────────────────────────────────────────────────
// GET /pwa-api/me
// Returns current user profile
// ─────────────────────────────────────────────────────────────
function pwa_handle_me(array $apiUser): never
{
    $user = db_fetch_one(
        "SELECT id, username, full_name, email, avatar, phone FROM users WHERE id = ?",
        [$apiUser['user_id']]
    );

    $profile = ['role' => $apiUser['role']];

    if ($apiUser['role'] === 'student') {
        $student = db_fetch_one(
            "SELECT s.id, s.full_name, s.first_name, s.last_name, s.photo,
                    s.gender, s.date_of_birth, s.blood_group, s.phone, s.email,
                    s.address, s.admission_no, s.admission_date,
                    c.name AS class_name, c.id AS class_id,
                    sec.name AS section_name, sec.id AS section_id,
                    e.roll_no,
                    acs.name AS session_name
             FROM students s
             LEFT JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
             LEFT JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
             LEFT JOIN classes c ON c.id = e.class_id
             LEFT JOIN sections sec ON sec.id = e.section_id
             WHERE s.id = ?
             ORDER BY e.id DESC LIMIT 1",
            [$apiUser['linked_id']]
        );
        $profile['student'] = $student;
    } else {
        $guardian = db_fetch_one(
            "SELECT id, full_name, first_name, last_name, photo, relation, phone, email, address
             FROM guardians WHERE id = ?",
            [$apiUser['linked_id']]
        );
        $profile['guardian'] = $guardian;
    }

    pwa_json(array_merge([
        'id'       => (int) $user['id'],
        'username' => $user['username'],
        'name'     => $user['full_name'],
        'email'    => $user['email'],
        'avatar'   => $user['avatar'],
        'phone'    => $user['phone'],
    ], $profile));
}
