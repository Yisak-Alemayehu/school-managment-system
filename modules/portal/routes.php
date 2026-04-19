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
        $userId = portal_user_id();

        // ── AJAX endpoints ──
        // Unread count
        if (isset($_GET['_check_unread'])) {
            header('Content-Type: application/json');
            echo json_encode(['count' => (int) db_fetch_value(
                "SELECT COUNT(*) FROM msg_message_status WHERE user_id = ? AND status != 'read'",
                [$userId]
            )]);
            exit;
        }

        // AJAX: fetch messages for a conversation
        if (isset($_GET['_fetch_thread'])) {
            header('Content-Type: application/json');
            $cid = (int) $_GET['_fetch_thread'];
            $afterId = (int) ($_GET['after'] ?? 0);
            $participant = db_fetch_one(
                "SELECT 1 FROM msg_conversation_participants WHERE conversation_id = ? AND user_id = ? AND is_deleted = 0",
                [$cid, $userId]
            );
            if (!$participant) { echo json_encode(['error' => 'denied']); exit; }

            $where = $afterId ? "AND m.id > ?" : "AND 1=1";
            $params = $afterId ? [$cid, $afterId] : [$cid];
            $msgs = db_fetch_all("
                SELECT m.id, m.body, m.sender_id, m.created_at, u.full_name AS sender_name
                FROM msg_messages m JOIN users u ON u.id = m.sender_id
                WHERE m.conversation_id = ? $where ORDER BY m.created_at ASC
            ", $params);

            // Attachments
            $msgIds = array_column($msgs, 'id');
            $attachments = [];
            if ($msgIds) {
                $ph = implode(',', array_fill(0, count($msgIds), '?'));
                $atts = db_fetch_all("SELECT * FROM msg_attachments WHERE message_id IN ($ph)", $msgIds);
                foreach ($atts as $a) {
                    $a['url'] = upload_url($a['file_path']);
                    $a['is_image'] = str_starts_with($a['mime_type'], 'image/');
                    $attachments[$a['message_id']][] = $a;
                }
            }

            foreach ($msgs as &$m) {
                $m['attachments'] = $attachments[$m['id']] ?? [];
                $m['is_mine'] = (int)$m['sender_id'] === $userId;
                $m['time'] = date('d M, g:i A', strtotime($m['created_at']));
            }
            unset($m);

            // Mark as read
            if ($msgIds) {
                $ph = implode(',', array_fill(0, count($msgIds), '?'));
                db_query("UPDATE msg_message_status SET status='read', read_at=NOW() WHERE user_id=? AND status!='read' AND message_id IN ($ph)", array_merge([$userId], $msgIds));
                db_query("UPDATE msg_conversation_participants SET last_read_at=NOW() WHERE conversation_id=? AND user_id=?", [$cid, $userId]);
            }

            echo json_encode(['messages' => $msgs]);
            exit;
        }

        // AJAX: fetch conversation list
        if (isset($_GET['_fetch_conversations'])) {
            header('Content-Type: application/json');
            $conversations = db_fetch_all("
                SELECT c.id, c.type, c.subject,
                    (SELECT m2.body FROM msg_messages m2 WHERE m2.conversation_id = c.id ORDER BY m2.created_at DESC LIMIT 1) AS last_body,
                    (SELECT m3.created_at FROM msg_messages m3 WHERE m3.conversation_id = c.id ORDER BY m3.created_at DESC LIMIT 1) AS last_msg_at,
                    (SELECT m4.sender_id FROM msg_messages m4 WHERE m4.conversation_id = c.id ORDER BY m4.created_at DESC LIMIT 1) AS last_sender_id,
                    (SELECT COUNT(*) FROM msg_message_status ms JOIN msg_messages mm ON mm.id = ms.message_id
                        WHERE mm.conversation_id = c.id AND ms.user_id = ? AND ms.status != 'read') AS unread_count,
                    ou.id AS other_user_id, ou.full_name AS other_name
                FROM msg_conversations c
                JOIN msg_conversation_participants cp ON cp.conversation_id = c.id AND cp.user_id = ? AND cp.is_deleted = 0
                LEFT JOIN msg_conversation_participants ocp ON ocp.conversation_id = c.id AND ocp.user_id != ?
                LEFT JOIN users ou ON ou.id = ocp.user_id
                WHERE EXISTS (SELECT 1 FROM msg_messages m WHERE m.conversation_id = c.id)
                ORDER BY last_msg_at DESC LIMIT 30
            ", [$userId, $userId, $userId]);

            foreach ($conversations as &$c) {
                $c['is_mine'] = (int)($c['last_sender_id'] ?? 0) === $userId;
                $c['time'] = $c['last_msg_at'] ? date('d M', strtotime($c['last_msg_at'])) : '';
                $c['initials'] = mb_substr($c['other_name'] ?? '?', 0, 1);
            }
            unset($c);
            echo json_encode(['conversations' => $conversations]);
            exit;
        }

        // AJAX: fetch staff list for compose
        if (isset($_GET['_fetch_staff'])) {
            header('Content-Type: application/json');
            $staff = db_fetch_all(
                "SELECT DISTINCT u.id, u.full_name, r.name AS role_name
                 FROM users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id
                 WHERE r.slug IN ('teacher','admin','staff','super-admin') AND u.deleted_at IS NULL AND u.id != ?
                 ORDER BY u.full_name ASC",
                [$userId]
            );
            echo json_encode(['staff' => $staff]);
            exit;
        }

        // AJAX: send message
        if (is_post() && isset($_GET['_ajax_send'])) {
            header('Content-Type: application/json');
            $receiverId = (int) ($_POST['receiver_id'] ?? 0);
            $subject    = trim($_POST['subject'] ?? '');
            $body       = trim($_POST['body'] ?? '');
            $convIdPost = (int) ($_POST['conversation_id'] ?? 0);

            if (!$receiverId || ($body === '' && empty($_FILES['attachments']['name'][0]))) {
                echo json_encode(['error' => 'Recipient and message are required.']);
                exit;
            }
            if ($receiverId === $userId) {
                echo json_encode(['error' => 'Cannot message yourself.']);
                exit;
            }
            if ($body && mb_strlen($body) > 5000) {
                echo json_encode(['error' => 'Message too long (max 5000).']);
                exit;
            }

            $receiver = db_fetch_one("SELECT id FROM users WHERE id = ? AND deleted_at IS NULL", [$receiverId]);
            if (!$receiver) {
                echo json_encode(['error' => 'Recipient not found.']);
                exit;
            }

            db_begin();
            try {
                if ($convIdPost) {
                    $isP = db_fetch_one("SELECT 1 FROM msg_conversation_participants WHERE conversation_id=? AND user_id=?", [$convIdPost, $userId]);
                    if (!$isP) $convIdPost = 0;
                }
                if (!$convIdPost) {
                    $existingConvId = db_fetch_value("
                        SELECT c.id FROM msg_conversations c
                        JOIN msg_conversation_participants cp1 ON cp1.conversation_id = c.id AND cp1.user_id = ?
                        JOIN msg_conversation_participants cp2 ON cp2.conversation_id = c.id AND cp2.user_id = ?
                        WHERE c.type = 'solo' LIMIT 1
                    ", [$userId, $receiverId]);

                    if ($existingConvId) {
                        $convIdPost = (int) $existingConvId;
                        db_query("UPDATE msg_conversation_participants SET is_deleted=0 WHERE conversation_id=? AND user_id=?", [$convIdPost, $userId]);
                        db_query("UPDATE msg_conversation_participants SET is_deleted=0 WHERE conversation_id=? AND user_id=?", [$convIdPost, $receiverId]);
                    } else {
                        $convIdPost = db_insert('msg_conversations', [
                            'type' => 'solo', 'subject' => $subject ?: null, 'created_by' => $userId,
                        ]);
                        db_insert('msg_conversation_participants', ['conversation_id' => $convIdPost, 'user_id' => $userId]);
                        db_insert('msg_conversation_participants', ['conversation_id' => $convIdPost, 'user_id' => $receiverId]);
                    }
                }

                $messageId = db_insert('msg_messages', [
                    'conversation_id' => $convIdPost,
                    'sender_id'       => $userId,
                    'body'            => $body,
                ]);

                db_insert('msg_message_status', [
                    'message_id' => $messageId,
                    'user_id'    => $receiverId,
                    'status'     => 'sent',
                ]);

                // Handle attachments
                $uploadedAtts = [];
                if (!empty($_FILES['attachments']['name'][0])) {
                    $maxFiles = 5;
                    $maxFileSize = 10 * 1024 * 1024;
                    $allowedTypes = [
                        'image/jpeg','image/png','image/gif','image/webp',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ];
                    $fileCount = min(count($_FILES['attachments']['name']), $maxFiles);
                    for ($i = 0; $i < $fileCount; $i++) {
                        $tmpName = $_FILES['attachments']['tmp_name'][$i] ?? null;
                        $originalName = $_FILES['attachments']['name'][$i] ?? '';
                        $size = $_FILES['attachments']['size'][$i] ?? 0;
                        if (!$tmpName || $size <= 0 || $size > $maxFileSize) continue;

                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_file($finfo, $tmpName);
                        finfo_close($finfo);
                        if (!in_array($mime, $allowedTypes)) continue;

                        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                        $safeExts = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx'];
                        if (!in_array($ext, $safeExts)) $ext = 'bin';

                        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
                        $subDir = 'messaging/' . date('Y/m');
                        $targetDir = UPLOAD_PATH . '/' . $subDir;
                        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

                        $targetPath = $targetDir . '/' . $filename;
                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $finalSize = $size;
                            if (in_array($mime, ['image/jpeg','image/png','image/webp']) && function_exists('compress_image')) {
                                $compressed = compress_image($targetPath, 1200, 1200, 75);
                                if ($compressed !== false) $finalSize = $compressed;
                            }
                            $attId = db_insert('msg_attachments', [
                                'message_id' => $messageId,
                                'file_name'  => $originalName,
                                'file_path'  => $subDir . '/' . $filename,
                                'file_size'  => $finalSize,
                                'mime_type'  => $mime,
                            ]);
                            $uploadedAtts[] = [
                                'id' => $attId,
                                'file_name' => $originalName,
                                'file_path' => $subDir . '/' . $filename,
                                'file_size' => $finalSize,
                                'mime_type' => $mime,
                                'url' => upload_url($subDir . '/' . $filename),
                                'is_image' => str_starts_with($mime, 'image/'),
                            ];
                        }
                    }
                }

                db_update('msg_conversations', ['updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$convIdPost]);
                db_commit();

                // Return the created message
                $user = db_fetch_one("SELECT full_name FROM users WHERE id=?", [$userId]);
                echo json_encode([
                    'success' => true,
                    'conversation_id' => $convIdPost,
                    'message' => [
                        'id' => $messageId,
                        'body' => $body,
                        'sender_id' => $userId,
                        'sender_name' => $user['full_name'] ?? '',
                        'created_at' => date('Y-m-d H:i:s'),
                        'time' => date('d M, g:i A'),
                        'is_mine' => true,
                        'attachments' => $uploadedAtts,
                    ],
                ]);
            } catch (\Throwable $e) {
                db_rollback();
                echo json_encode(['error' => 'Failed to send message.']);
            }
            exit;
        }

        // Normal page load
        if (is_post()) {
            redirect(url('portal', 'messages'));
        }
        require __DIR__ . '/views/messages.php';
        break;

    case 'materials':
        portal_require();
        require __DIR__ . '/views/materials.php';
        break;

    case 'materials-subject':
        portal_require();
        require __DIR__ . '/views/materials_subject.php';
        break;

    case 'materials-viewer':
        portal_require();
        require __DIR__ . '/views/materials_viewer.php';
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
