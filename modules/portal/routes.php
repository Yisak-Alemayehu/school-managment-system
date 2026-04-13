<?php
/**
 * Portal Module — Routes
 * Student & Parent mobile portal (PHP, session-based auth).
 * URL pattern: /portal/{action}
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once __DIR__ . '/_session.php';
require_once __DIR__ . '/_layout.php';

$action = current_action() ?: 'login';

// Public actions (no portal auth required)
if (!portal_check() && !in_array($action, ['login', 'logout', 'offline'])) {
    redirect(url('portal', 'login'));
}

// ── Route dispatcher ─────────────────────────────────────────────────────────

switch ($action) {

    // ── Root / index ─────────────────────────────────────────────────────────
    case 'index':
        redirect(portal_check() ? url('portal', 'dashboard') : url('portal', 'login'));
        break;

    // ── Offline (served by service worker cache) ──────────────────────────────
    case 'offline':
        require __DIR__ . '/views/offline.php';
        break;

    // ── Login ─────────────────────────────────────────────────────────────────
    case 'login':
        if (portal_check()) {
            redirect(url('portal', 'dashboard'));
        }
        if (is_post()) {
            $identifier = trim($_POST['username'] ?? '');
            $password   = $_POST['password'] ?? '';
            $wantedRole = $_POST['role'] ?? 'student';

            if ($identifier === '' || $password === '') {
                set_flash('error', 'Username and password are required.');
                redirect(url('portal', 'login'));
            }
            if (!in_array($wantedRole, ['student', 'parent'], true)) {
                set_flash('error', 'Please select a valid role.');
                redirect(url('portal', 'login'));
            }

            $result = auth_attempt($identifier, $password);
            if (is_string($result)) {
                set_flash('error', $result);
                $_SESSION['_old_input']['username'] = $identifier;
                $_SESSION['_old_input']['role']     = $wantedRole;
                redirect(url('portal', 'login'));
            }

            $user = $result;

            if ($wantedRole === 'student') {
                $student = db_fetch_one(
                    "SELECT s.id, s.full_name, s.photo, s.admission_no,
                            c.name AS class_name, c.id AS class_id,
                            sec.name AS section_name, sec.id AS section_id,
                            e.roll_no
                     FROM students s
                     LEFT JOIN enrollments e
                            ON e.student_id = s.id AND e.status = 'active'
                     LEFT JOIN academic_sessions acs
                            ON acs.id = e.session_id AND acs.is_active = 1
                     LEFT JOIN classes c ON c.id = e.class_id
                     LEFT JOIN sections sec ON sec.id = e.section_id
                     WHERE s.user_id = ? AND s.deleted_at IS NULL AND s.status = 'active'
                     ORDER BY e.id DESC LIMIT 1",
                    [$user['id']]
                );
                if (!$student) {
                    set_flash('error', 'No active student account linked to this user. Please contact the school.');
                    redirect(url('portal', 'login'));
                }
                portal_login_session([
                    'user_id'   => (int) $user['id'],
                    'role'      => 'student',
                    'linked_id' => (int) $student['id'],
                    'user'      => [
                        'id'       => (int) $user['id'],
                        'name'     => $user['full_name'],
                        'username' => $user['username'],
                        'email'    => $user['email'],
                        'avatar'   => $user['avatar'] ?? null,
                    ],
                    'student' => $student,
                ]);
            } else {
                $guardian = db_fetch_one(
                    "SELECT g.id, g.full_name, g.photo, g.relation
                     FROM guardians g WHERE g.user_id = ?
                     ORDER BY g.id ASC LIMIT 1",
                    [$user['id']]
                );
                if (!$guardian) {
                    set_flash('error', 'No parent/guardian account linked to this user. Please contact the school.');
                    redirect(url('portal', 'login'));
                }
                $children = db_fetch_all(
                    "SELECT s.id, s.full_name, s.photo, s.status,
                            c.name AS class_name, sec.name AS section_name
                     FROM students s
                     JOIN student_guardians sg ON sg.student_id = s.id AND sg.guardian_id = ?
                     LEFT JOIN enrollments e
                            ON e.student_id = s.id AND e.status = 'active'
                     LEFT JOIN academic_sessions acs
                            ON acs.id = e.session_id AND acs.is_active = 1
                     LEFT JOIN classes c ON c.id = e.class_id
                     LEFT JOIN sections sec ON sec.id = e.section_id
                     WHERE s.deleted_at IS NULL
                     ORDER BY sg.is_primary DESC, s.full_name ASC",
                    [(int) $guardian['id']]
                );
                portal_login_session([
                    'user_id'         => (int) $user['id'],
                    'role'            => 'parent',
                    'linked_id'       => (int) $guardian['id'],
                    'user'            => [
                        'id'       => (int) $user['id'],
                        'name'     => $user['full_name'],
                        'username' => $user['username'],
                        'email'    => $user['email'],
                        'avatar'   => $user['avatar'] ?? null,
                    ],
                    'guardian'        => [
                        'id'       => (int) $guardian['id'],
                        'name'     => $guardian['full_name'],
                        'relation' => $guardian['relation'],
                        'photo'    => $guardian['photo'],
                    ],
                    'children'        => $children,
                    'active_child_id' => !empty($children) ? (int) $children[0]['id'] : null,
                ]);
            }

            redirect(url('portal', 'dashboard'));
        }
        require __DIR__ . '/views/login.php';
        break;

    // ── Logout ────────────────────────────────────────────────────────────────
    case 'logout':
        portal_logout();
        set_flash('success', 'You have been signed out.');
        redirect(url('portal', 'login'));
        break;

    // ── Switch active child (parent) ──────────────────────────────────────────
    case 'switch-child':
        portal_require('parent');
        if (is_post()) {
            $childId = (int) ($_POST['child_id'] ?? 0);
            if ($childId) {
                portal_switch_child($childId);
            }
        }
        redirect(url('portal', 'dashboard'));
        break;

    // ── Dashboard ─────────────────────────────────────────────────────────────
    case 'dashboard':
        portal_require();
        if (portal_role() === 'student') {
            require __DIR__ . '/views/dashboard_student.php';
        } else {
            require __DIR__ . '/views/dashboard_parent.php';
        }
        break;

    // ── Academic pages (student & parent) ────────────────────────────────────
    case 'results':
        portal_require();
        require __DIR__ . '/views/results.php';
        break;

    case 'attendance':
        portal_require();
        require __DIR__ . '/views/attendance.php';
        break;

    case 'timetable':
        portal_require();
        require __DIR__ . '/views/timetable.php';
        break;

    // ── Parent pages ──────────────────────────────────────────────────────────
    case 'fees':
        portal_require('parent');
        require __DIR__ . '/views/fees.php';
        break;

    // ── Shared pages ──────────────────────────────────────────────────────────
    case 'messages':
        portal_require();
        if (is_post()) {
            // Handle message send
            $receiverId = (int) ($_POST['receiver_id'] ?? 0);
            $subject    = trim($_POST['subject'] ?? '');
            $body       = trim($_POST['body'] ?? '');

            if (!$receiverId || $body === '') {
                set_flash('error', 'Recipient and message body are required.');
            } elseif ($receiverId === portal_user_id()) {
                set_flash('error', 'You cannot send a message to yourself.');
            } elseif (strlen($body) > 5000) {
                set_flash('error', 'Message is too long (max 5000 characters).');
            } else {
                $receiver = db_fetch_one(
                    "SELECT id FROM users WHERE id = ? AND deleted_at IS NULL",
                    [$receiverId]
                );
                if ($receiver) {
                    db_insert('messages', [
                        'sender_id'   => portal_user_id(),
                        'receiver_id' => $receiverId,
                        'subject'     => $subject ?: '(No Subject)',
                        'body'        => $body,
                        'is_read'     => 0,
                    ]);
                    set_flash('success', 'Message sent successfully.');
                } else {
                    set_flash('error', 'Recipient not found.');
                }
            }
            redirect(url('portal', 'messages'));
        }
        require __DIR__ . '/views/messages.php';
        break;

    case 'notices':
        portal_require();
        require __DIR__ . '/views/notices.php';
        break;

    case 'profile':
        portal_require();
        require __DIR__ . '/views/profile.php';
        break;

    case 'change-password':
        portal_require();
        if (is_post()) {
            csrf_protect();
            $current = trim($_POST['current_password'] ?? '');
            $newPw   = trim($_POST['password'] ?? '');
            $confirm = trim($_POST['password_confirmation'] ?? '');

            if ($current === '' || $newPw === '' || $confirm === '') {
                set_flash('error', 'All fields are required.');
            } elseif (strlen($newPw) < 8) {
                set_flash('error', 'New password must be at least 8 characters.');
            } elseif ($newPw !== $confirm) {
                set_flash('error', 'Password confirmation does not match.');
            } else {
                $dbUser = db_fetch_one("SELECT password_hash FROM users WHERE id = ?", [portal_user_id()]);
                if (!$dbUser || !password_verify($current, $dbUser['password_hash'])) {
                    set_flash('error', 'Current password is incorrect.');
                } else {
                    db_query(
                        "UPDATE users SET password_hash = ?, password_changed_at = NOW() WHERE id = ?",
                        [password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]), portal_user_id()]
                    );
                    set_flash('success', 'Password updated successfully.');
                    redirect(url('portal', 'profile'));
                }
            }
            redirect(url('portal', 'change-password'));
        }
        require __DIR__ . '/views/change_password.php';
        break;

    default:
        router_not_found();
        break;
}
