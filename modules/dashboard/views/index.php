<?php
/**
 * Dashboard — Main View
 * Role-based dashboard with summary cards and quick actions
 */
$user = auth_user();
$isSuperAdmin = auth_is_super_admin();
$isAdmin      = auth_has_role('Admin') || $isSuperAdmin;
$isTeacher    = auth_has_role('Teacher');
$isStudent    = auth_has_role('Student');
$isParent     = auth_has_role('Parent');
$isAccountant = auth_has_role('Accountant');

// ── Gather Stats ─────────────────────────────────────────
$stats = [];

if ($isAdmin) {
    $stats['total_students']  = db_fetch_value("SELECT COUNT(*) FROM students WHERE status = 'active' AND deleted_at IS NULL") ?: 0;
    $stats['total_teachers']  = db_fetch_value("SELECT COUNT(*) FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.slug = 'teacher' AND u.is_active = 1 AND u.deleted_at IS NULL") ?: 0;
    $stats['total_classes']   = db_fetch_value("SELECT COUNT(*) FROM classes WHERE is_active = 1") ?: 0;
    $stats['total_sections']  = db_fetch_value("SELECT COUNT(*) FROM sections WHERE is_active = 1") ?: 0;

    // Finance stats
    $activeSession = get_active_session();
    if ($activeSession) {
        $stats['total_invoiced'] = db_fetch_value(
            "SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE session_id = ?",
            [$activeSession['id']]
        ) ?: 0;
        $stats['total_collected'] = db_fetch_value(
            "SELECT COALESCE(SUM(p.amount), 0) FROM payments p JOIN invoices i ON p.invoice_id = i.id WHERE p.status = 'completed' AND i.session_id = ?",
            [$activeSession['id']]
        ) ?: 0;
        $stats['total_pending'] = $stats['total_invoiced'] - $stats['total_collected'];
    }

    // Today's attendance
    $today = date('Y-m-d');
    $stats['attendance_today'] = db_fetch_value(
        "SELECT COUNT(DISTINCT student_id) FROM attendance WHERE date = ? AND status = 'present'",
        [$today]
    ) ?: 0;
    $stats['absent_today'] = db_fetch_value(
        "SELECT COUNT(DISTINCT student_id) FROM attendance WHERE date = ? AND status = 'absent'",
        [$today]
    ) ?: 0;
}

// Recent announcements
$announcements = db_fetch_all(
    "SELECT title, content, type, created_at FROM announcements 
     WHERE status = 'published' AND (published_at IS NULL OR published_at <= NOW()) 
     AND (expires_at IS NULL OR expires_at >= NOW())
     ORDER BY is_pinned DESC, created_at DESC LIMIT 5"
);

// Recent notifications for current user
$notifications = db_fetch_all(
    "SELECT title, message, type, created_at, is_read FROM notifications 
     WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
    [$user['id']]
);

ob_start();
?>

<!-- Welcome Bar -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Welcome, <?= e(explode(' ', $user['name'] ?? $user['full_name'] ?? '')[0]) ?>!</h1>
    <p class="text-sm text-gray-500 mt-1">
        <?= date('l, F j, Y') ?>
        <?php $session = get_active_session(); if ($session): ?>
            &bull; <?= e($session['name']) ?>
            <?php $term = get_active_term(); if ($term): ?> &bull; <?= e($term['name']) ?><?php endif; ?>
        <?php endif; ?>
    </p>
</div>

<?php if ($isAdmin): ?>
<!-- ── Admin Dashboard ────────────────────────────────────── -->

<!-- Summary Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Students -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs sm:text-sm text-gray-500">Total Students</p>
                <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1"><?= number_format($stats['total_students']) ?></p>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                </svg>
            </div>
        </div>
        <a href="<?= url('students') ?>" class="text-xs text-primary-600 hover:underline mt-2 inline-block">View all &rarr;</a>
    </div>

    <!-- Teachers -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs sm:text-sm text-gray-500">Teachers</p>
                <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1"><?= number_format($stats['total_teachers']) ?></p>
            </div>
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
        </div>
        <a href="<?= url('users') ?>" class="text-xs text-primary-600 hover:underline mt-2 inline-block">Manage &rarr;</a>
    </div>

    <!-- Classes -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs sm:text-sm text-gray-500">Classes</p>
                <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1"><?= number_format($stats['total_classes']) ?></p>
            </div>
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
        </div>
        <a href="<?= url('academics', 'classes') ?>" class="text-xs text-primary-600 hover:underline mt-2 inline-block">View all &rarr;</a>
    </div>

    <!-- Attendance Today -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs sm:text-sm text-gray-500">Present Today</p>
                <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1"><?= number_format($stats['attendance_today']) ?></p>
            </div>
            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-2"><?= number_format($stats['absent_today']) ?> absent</p>
    </div>
</div>

<!-- Finance Row -->
<?php if (isset($stats['total_invoiced'])): ?>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
        <p class="text-xs sm:text-sm text-gray-500">Total Invoiced</p>
        <p class="text-lg font-bold text-gray-900 mt-1"><?= format_money($stats['total_invoiced']) ?></p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
        <p class="text-xs sm:text-sm text-gray-500">Total Collected</p>
        <p class="text-lg font-bold text-green-600 mt-1"><?= format_money($stats['total_collected']) ?></p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
        <p class="text-xs sm:text-sm text-gray-500">Outstanding</p>
        <p class="text-lg font-bold text-red-600 mt-1"><?= format_money($stats['total_pending']) ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <a href="<?= url('students', 'create') ?>" class="flex flex-col items-center gap-2 p-4 bg-white rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition text-center">
        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
        </svg>
        <span class="text-xs font-medium text-gray-700">New Student</span>
    </a>
    <a href="<?= url('attendance', 'take') ?>" class="flex flex-col items-center gap-2 p-4 bg-white rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition text-center">
        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <span class="text-xs font-medium text-gray-700">Attendance</span>
    </a>
    <a href="<?= url('finance', 'create-invoice') ?>" class="flex flex-col items-center gap-2 p-4 bg-white rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition text-center">
        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
        </svg>
        <span class="text-xs font-medium text-gray-700">New Invoice</span>
    </a>
    <a href="<?= url('communication', 'create') ?>" class="flex flex-col items-center gap-2 p-4 bg-white rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition text-center">
        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
        </svg>
        <span class="text-xs font-medium text-gray-700">Announce</span>
    </a>
</div>

<?php elseif ($isTeacher): ?>
<!-- ── Teacher Dashboard ──────────────────────────────────── -->
<div class="grid grid-cols-2 gap-4 mb-6">
    <a href="<?= url('attendance', 'take') ?>" class="flex flex-col items-center gap-3 p-6 bg-white rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition">
        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        <span class="text-sm font-medium text-gray-700">Take Attendance</span>
    </a>
    <a href="<?= url('exams', 'marks') ?>" class="flex flex-col items-center gap-3 p-6 bg-white rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition">
        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span class="text-sm font-medium text-gray-700">Enter Marks</span>
    </a>
    <a href="<?= url('exams', 'assignments') ?>" class="flex flex-col items-center gap-3 p-6 bg-white rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition">
        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span class="text-sm font-medium text-gray-700">Assignments</span>
    </a>
    <a href="<?= url('communication', 'messages') ?>" class="flex flex-col items-center gap-3 p-6 bg-white rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition">
        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
        </svg>
        <span class="text-sm font-medium text-gray-700">Messages</span>
    </a>
</div>

<?php elseif ($isStudent): ?>
<!-- ── Student Dashboard ──────────────────────────────────── -->
<div class="grid grid-cols-2 gap-4 mb-6">
    <a href="<?= url('exams', 'my-results') ?>" class="flex flex-col items-center gap-3 p-6 bg-white rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition">
        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span class="text-sm font-medium text-gray-700">My Results</span>
    </a>
    <a href="<?= url('finance', 'my-fees') ?>" class="flex flex-col items-center gap-3 p-6 bg-white rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition">
        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <span class="text-sm font-medium text-gray-700">My Fees</span>
    </a>
    <a href="<?= url('attendance', 'my-attendance') ?>" class="flex flex-col items-center gap-3 p-6 bg-white rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition">
        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span class="text-sm font-medium text-gray-700">Attendance</span>
    </a>
    <a href="<?= url('academics', 'timetable') ?>" class="flex flex-col items-center gap-3 p-6 bg-white rounded-xl border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition">
        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="text-sm font-medium text-gray-700">Timetable</span>
    </a>
</div>

<?php elseif ($isParent): ?>
<!-- ── Parent Dashboard ───────────────────────────────────── -->
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">My Children</h3>
    <?php
    $children = db_fetch_all(
        "SELECT s.id, s.admission_no, s.full_name, c.name as class_name, sec.name as section_name
         FROM students s
         JOIN student_guardians sg ON s.id = sg.student_id
         JOIN guardians g ON sg.guardian_id = g.id
         LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
         LEFT JOIN sections sec ON e.section_id = sec.id
         LEFT JOIN classes c ON sec.class_id = c.id
         WHERE g.user_id = ? AND s.status = 'active' AND s.deleted_at IS NULL",
        [$user['id']]
    );
    ?>
    <?php if (empty($children)): ?>
        <p class="text-sm text-gray-500">No children linked to your account.</p>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($children as $child): ?>
                <a href="<?= url('students', 'view', $child['id']) ?>" class="flex items-center justify-between p-3 rounded-lg border hover:bg-gray-50 transition">
                    <div>
                        <p class="font-medium text-gray-900"><?= e($child['full_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= e($child['admission_no']) ?> &bull; <?= e($child['class_name'] ?? 'N/A') ?> <?= e($child['section_name'] ?? '') ?></p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── Announcements ──────────────────────────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">Recent Announcements</h3>
            <a href="<?= url('communication', 'announcements') ?>" class="text-xs text-primary-600 hover:underline">View all</a>
        </div>
        <?php if (empty($announcements)): ?>
            <p class="text-sm text-gray-400">No announcements.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($announcements as $ann): ?>
                    <div class="pb-3 border-b last:border-0 last:pb-0">
                        <div class="flex items-start gap-2">
                            <?php if (($ann['type'] ?? '') === 'emergency'): ?>
                                <span class="mt-0.5 inline-block w-2 h-2 bg-red-500 rounded-full flex-shrink-0"></span>
                            <?php elseif (($ann['type'] ?? '') === 'event'): ?>
                                <span class="mt-0.5 inline-block w-2 h-2 bg-yellow-500 rounded-full flex-shrink-0"></span>
                            <?php else: ?>
                                <span class="mt-0.5 inline-block w-2 h-2 bg-blue-500 rounded-full flex-shrink-0"></span>
                            <?php endif; ?>
                            <div>
                                <p class="text-sm font-medium text-gray-900"><?= e($ann['title']) ?></p>
                                <p class="text-xs text-gray-500 mt-0.5"><?= e(truncate(strip_tags($ann['content']), 80)) ?></p>
                                <p class="text-xs text-gray-400 mt-1"><?= time_ago($ann['created_at']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Notifications -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
        </div>
        <?php if (empty($notifications)): ?>
            <p class="text-sm text-gray-400">No notifications.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($notifications as $notif): ?>
                    <div class="pb-3 border-b last:border-0 last:pb-0 <?= $notif['is_read'] ? '' : 'bg-blue-50 -mx-2 px-2 rounded' ?>">
                        <p class="text-sm <?= $notif['is_read'] ? 'text-gray-700' : 'text-gray-900 font-medium' ?>"><?= e($notif['title']) ?></p>
                        <p class="text-xs text-gray-500 mt-0.5"><?= e(truncate($notif['message'], 80)) ?></p>
                        <p class="text-xs text-gray-400 mt-1"><?= time_ago($notif['created_at']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Dashboard';
require APP_ROOT . '/templates/layout.php';
