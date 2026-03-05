<?php
/**
 * Dashboard — Main View
 * Role-based dashboard with summary cards and quick actions
 */
$user = auth_user();
$isSuperAdmin = auth_is_super_admin();
$isAdmin      = auth_has_role('admin') || $isSuperAdmin;
$isTeacher    = auth_has_role('teacher');
$isStudent    = auth_has_role('student');
$isParent     = auth_has_role('parent');

// ── Gather Stats ─────────────────────────────────────────
$stats = [];

if ($isAdmin) {
    $stats['total_students']  = db_fetch_value("SELECT COUNT(*) FROM students WHERE status = 'active' AND deleted_at IS NULL") ?: 0;
    $stats['total_teachers']  = db_fetch_value("SELECT COUNT(*) FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.slug = 'teacher' AND u.is_active = 1 AND u.deleted_at IS NULL") ?: 0;
    $stats['total_classes']   = db_fetch_value("SELECT COUNT(*) FROM classes WHERE is_active = 1") ?: 0;
    $stats['total_sections']  = db_fetch_value("SELECT COUNT(*) FROM sections WHERE is_active = 1") ?: 0;

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

// ── Super Admin Extended Stats ────────────────────────────
$superAdminStats   = [];
$recentStudents    = [];
$attendanceByClass = [];

if ($isSuperAdmin) {
    // People
    $superAdminStats['total_users']    = db_fetch_value("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND is_active = 1") ?: 0;
    $superAdminStats['total_parents']  = db_fetch_value(
        "SELECT COUNT(DISTINCT g.id) FROM guardians g JOIN student_guardians sg ON g.id = sg.guardian_id"
    ) ?: 0;
    $superAdminStats['total_subjects'] = db_fetch_value("SELECT COUNT(*) FROM subjects WHERE is_active = 1") ?: 0;
    $superAdminStats['total_sections'] = db_fetch_value("SELECT COUNT(*) FROM sections WHERE is_active = 1") ?: 0;

    // Gender breakdown
    $superAdminStats['male_students']   = db_fetch_value("SELECT COUNT(*) FROM students WHERE gender = 'male'   AND status = 'active' AND deleted_at IS NULL") ?: 0;
    $superAdminStats['female_students'] = db_fetch_value("SELECT COUNT(*) FROM students WHERE gender = 'female' AND status = 'active' AND deleted_at IS NULL") ?: 0;

    // New admissions this month
    $monthStart = date('Y-m-01');
    $superAdminStats['new_admissions_month'] = db_fetch_value(
        "SELECT COUNT(*) FROM students WHERE status = 'active' AND deleted_at IS NULL AND created_at >= ?",
        [$monthStart]
    ) ?: 0;

    // Attendance rate today (among enrolled students)
    $totalEnrolled = db_fetch_value("SELECT COUNT(DISTINCT student_id) FROM attendance WHERE date = ?", [$today]) ?: 0;
    $superAdminStats['attendance_rate'] = $totalEnrolled > 0
        ? round(($stats['attendance_today'] / $totalEnrolled) * 100, 1)
        : 0;
    $superAdminStats['total_marked_today'] = $totalEnrolled;

    // Exams & results
    $activeSession = get_active_session();
    if ($activeSession) {
        $superAdminStats['total_exams'] = db_fetch_value(
            "SELECT COUNT(*) FROM exams WHERE session_id = ?", [$activeSession['id']]
        ) ?: 0;
    }

    // Recent 6 students
    $recentStudents = db_fetch_all(
        "SELECT s.id, s.full_name, s.admission_no, s.gender, s.created_at,
                c.name AS class_name, sec.name AS section_name
         FROM students s
         LEFT JOIN enrollments e  ON s.id = e.student_id AND e.status = 'active'
         LEFT JOIN sections sec   ON e.section_id = sec.id
         LEFT JOIN classes c      ON sec.class_id  = c.id
         WHERE s.status = 'active' AND s.deleted_at IS NULL
         ORDER BY s.created_at DESC LIMIT 6"
    );

    // Top 5 classes by attendance today
    $attendanceByClass = db_fetch_all(
        "SELECT c.name AS class_name,
                COUNT(CASE WHEN a.status='present' THEN 1 END) AS present,
                COUNT(CASE WHEN a.status='absent'  THEN 1 END) AS absent,
                COUNT(*) AS total
         FROM attendance a
         JOIN students s  ON a.student_id = s.id
         JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
         JOIN sections sec  ON e.section_id = sec.id
         JOIN classes c     ON sec.class_id  = c.id
         WHERE a.date = ?
         GROUP BY c.id, c.name
         ORDER BY total DESC LIMIT 5",
        [$today]
    );
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
    <h1 class="text-2xl font-bold text-gray-900 dark:text-dark-text">Welcome, <?= e(explode(' ', $user['name'] ?? $user['full_name'] ?? '')[0]) ?>!</h1>
    <p class="text-sm text-gray-500 dark:text-dark-muted mt-1">
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
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 sm:p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-dark-muted">Total Students</p>
                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-dark-text mt-1"><?= number_format($stats['total_students']) ?></p>
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
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 sm:p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-dark-muted">Teachers</p>
                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-dark-text mt-1"><?= number_format($stats['total_teachers']) ?></p>
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
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 sm:p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-dark-muted">Classes</p>
                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-dark-text mt-1"><?= number_format($stats['total_classes']) ?></p>
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
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 sm:p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-dark-muted">Present Today</p>
                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-dark-text mt-1"><?= number_format($stats['attendance_today']) ?></p>
            </div>
            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-dark-muted mt-2"><?= number_format($stats['absent_today']) ?> absent</p>
    </div>
</div>



<?php if ($isSuperAdmin): ?>
<!-- ══════════════════════════════════════════════════════
     SUPER ADMIN EXTENDED STATISTICS
     ══════════════════════════════════════════════════════ -->

<!-- Row: Academic & People KPIs -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
    <!-- Total Users -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 sm:p-5 flex items-center gap-4">
        <div class="w-11 h-11 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <div>
            <p class="text-xs text-gray-500 dark:text-dark-muted">System Users</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-dark-text"><?= number_format($superAdminStats['total_users']) ?></p>
            <a href="<?= url('users') ?>" class="text-xs text-primary-600 hover:underline">Manage &rarr;</a>
        </div>
    </div>
    <!-- Parents -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 sm:p-5 flex items-center gap-4">
        <div class="w-11 h-11 bg-pink-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
        </div>
        <div>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Parents / Guardians</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-dark-text"><?= number_format($superAdminStats['total_parents']) ?></p>
        </div>
    </div>
    <!-- Subjects -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 sm:p-5 flex items-center gap-4">
        <div class="w-11 h-11 bg-teal-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </div>
        <div>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Active Subjects</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-dark-text"><?= number_format($superAdminStats['total_subjects']) ?></p>
            <a href="<?= url('academics', 'subjects') ?>" class="text-xs text-primary-600 hover:underline">View &rarr;</a>
        </div>
    </div>
    <!-- New Admissions This Month -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 sm:p-5 flex items-center gap-4">
        <div class="w-11 h-11 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
        </div>
        <div>
            <p class="text-xs text-gray-500 dark:text-dark-muted">New Admissions</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-dark-text"><?= number_format($superAdminStats['new_admissions_month']) ?></p>
            <p class="text-xs text-gray-400 dark:text-gray-500">This month</p>
        </div>
    </div>
</div>

<!-- Row: Exams -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
    <!-- Total Exams -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 sm:p-5 flex items-center gap-4">
        <div class="w-11 h-11 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <div>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Exams (Session)</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-dark-text"><?= number_format($superAdminStats['total_exams'] ?? 0) ?></p>
            <a href="<?= url('exams') ?>" class="text-xs text-primary-600 hover:underline">View &rarr;</a>
        </div>
    </div>
</div>

<!-- Row: Gender breakdown + Attendance rate -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <!-- Gender Breakdown -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Student Gender Breakdown</h3>
        <?php
        $totalG   = $superAdminStats['male_students'] + $superAdminStats['female_students'];
        $malePct  = $totalG > 0 ? round($superAdminStats['male_students']   / $totalG * 100) : 50;
        $femPct   = $totalG > 0 ? round($superAdminStats['female_students'] / $totalG * 100) : 50;
        ?>
        <div class="flex items-center gap-3 mb-2">
            <span class="text-xs w-14 text-gray-500 dark:text-dark-muted text-right">Male</span>
            <div class="flex-1 bg-gray-100 dark:bg-dark-card2 rounded-full h-4 overflow-hidden">
                <div class="bg-blue-500 h-4 rounded-full" style="width:<?= $malePct ?>%"></div>
            </div>
            <span class="text-xs w-16 text-gray-700 dark:text-gray-300 font-medium"><?= number_format($superAdminStats['male_students']) ?> (<?= $malePct ?>%)</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs w-14 text-gray-500 dark:text-dark-muted text-right">Female</span>
            <div class="flex-1 bg-gray-100 dark:bg-dark-card2 rounded-full h-4 overflow-hidden">
                <div class="bg-pink-500 h-4 rounded-full" style="width:<?= $femPct ?>%"></div>
            </div>
            <span class="text-xs w-16 text-gray-700 dark:text-gray-300 font-medium"><?= number_format($superAdminStats['female_students']) ?> (<?= $femPct ?>%)</span>
        </div>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">Total active students: <?= number_format($totalG) ?></p>
    </div>

    <!-- Today's Attendance Rate + Class Breakdown -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Today's Attendance Rate</h3>
            <span class="text-2xl font-bold <?= $superAdminStats['attendance_rate'] >= 75 ? 'text-green-600' : ($superAdminStats['attendance_rate'] >= 50 ? 'text-yellow-600' : 'text-red-600') ?>">
                <?= $superAdminStats['attendance_rate'] ?>%
            </span>
        </div>
        <!-- Overall bar -->
        <div class="w-full bg-gray-100 dark:bg-dark-card2 rounded-full h-3 mb-4 overflow-hidden">
            <div class="h-3 rounded-full <?= $superAdminStats['attendance_rate'] >= 75 ? 'bg-green-500' : ($superAdminStats['attendance_rate'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') ?>"
                 style="width:<?= $superAdminStats['attendance_rate'] ?>%"></div>
        </div>
        <?php if (!empty($attendanceByClass)): ?>
        <div class="space-y-1">
            <?php foreach ($attendanceByClass as $ac):
                $pct = $ac['total'] > 0 ? round($ac['present'] / $ac['total'] * 100) : 0;
            ?>
            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-dark-muted">
                <span class="w-20 truncate font-medium"><?= e($ac['class_name']) ?></span>
                <div class="flex-1 bg-gray-100 dark:bg-dark-card2 rounded-full h-2 overflow-hidden">
                    <div class="bg-blue-400 h-2 rounded-full" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="w-16 text-right text-gray-500 dark:text-dark-muted"><?= $ac['present'] ?>/<?= $ac['total'] ?> (<?= $pct ?>%)</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p class="text-xs text-gray-400 dark:text-gray-500">No attendance recorded today.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Row: Recent Students -->
<div class="grid grid-cols-1 gap-6 mb-6">
    <!-- Recent Admissions -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-dark-text">Recent Admissions</h3>
            <a href="<?= url('students') ?>" class="text-xs text-primary-600 hover:underline">View all &rarr;</a>
        </div>
        <?php if (empty($recentStudents)): ?>
            <p class="text-sm text-gray-400 dark:text-gray-500">No students found.</p>
        <?php else: ?>
        <div class="overflow-x-auto -mx-1">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-dark-muted border-b">
                        <th class="pb-2 pl-1 font-medium">Name</th>
                        <th class="pb-2 font-medium">Adm. No</th>
                        <th class="pb-2 font-medium">Class</th>
                        <th class="pb-2 font-medium">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($recentStudents as $rs): ?>
                    <tr class="hover:bg-gray-50 dark:bg-dark-bg transition">
                        <td class="py-2 pl-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-white text-xs font-bold flex-shrink-0 <?= ($rs['gender'] ?? '') === 'female' ? 'bg-pink-400' : 'bg-blue-400' ?>">
                                    <?= strtoupper(substr($rs['full_name'], 0, 1)) ?>
                                </span>
                                <a href="<?= url('students', 'view', $rs['id']) ?>" class="text-gray-800 dark:text-dark-text hover:text-primary-600 font-medium truncate max-w-[100px]"><?= e($rs['full_name']) ?></a>
                            </div>
                        </td>
                        <td class="py-2 text-gray-500 dark:text-dark-muted"><?= e($rs['admission_no']) ?></td>
                        <td class="py-2 text-gray-600 dark:text-dark-muted"><?= e(($rs['class_name'] ?? '-') . ' ' . ($rs['section_name'] ?? '')) ?></td>
                        <td class="py-2 text-gray-400 dark:text-gray-500"><?= date('d M', strtotime($rs['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>
<?php endif; /* end $isSuperAdmin */ ?>

<!-- Quick Actions -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <a href="<?= url('students', 'create') ?>" class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border hover:border-primary-300 hover:bg-primary-50 transition text-center">
        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
        </svg>
        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">New Student</span>
    </a>
    <a href="<?= url('attendance', 'take') ?>" class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border hover:border-primary-300 hover:bg-primary-50 transition text-center">
        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Attendance</span>
    </a>
    <a href="<?= url('communication', 'create') ?>" class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border hover:border-primary-300 hover:bg-primary-50 transition text-center">
        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
        </svg>
        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Announce</span>
    </a>
</div>

<?php elseif ($isTeacher): ?>
<!-- ── Teacher Dashboard ──────────────────────────────────── -->
<div class="grid grid-cols-2 gap-4 mb-6">
    <a href="<?= url('attendance', 'take') ?>" class="flex flex-col items-center gap-3 p-6 bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border hover:border-primary-300 hover:bg-primary-50 transition">
        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Take Attendance</span>
    </a>
    <a href="<?= url('exams', 'marks') ?>" class="flex flex-col items-center gap-3 p-6 bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border hover:border-primary-300 hover:bg-primary-50 transition">
        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Enter Marks</span>
    </a>
    <a href="<?= url('exams', 'assignments') ?>" class="flex flex-col items-center gap-3 p-6 bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border hover:border-primary-300 hover:bg-primary-50 transition">
        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Assignments</span>
    </a>
    <a href="<?= url('communication', 'messages') ?>" class="flex flex-col items-center gap-3 p-6 bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border hover:border-primary-300 hover:bg-primary-50 transition">
        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
        </svg>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Messages</span>
    </a>
</div>

<?php elseif ($isStudent): ?>
<!-- ── Student Dashboard ──────────────────────────────────── -->
<div class="grid grid-cols-2 gap-4 mb-6">
    <a href="<?= url('exams', 'my-results') ?>" class="flex flex-col items-center gap-3 p-6 bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border hover:border-primary-300 hover:bg-primary-50 transition">
        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">My Results</span>
    </a>
    <a href="<?= url('attendance', 'my-attendance') ?>" class="flex flex-col items-center gap-3 p-6 bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border hover:border-primary-300 hover:bg-primary-50 transition">
        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Attendance</span>
    </a>
    <a href="<?= url('academics', 'timetable') ?>" class="flex flex-col items-center gap-3 p-6 bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border hover:border-primary-300 hover:bg-primary-50 transition">
        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Timetable</span>
    </a>
</div>

<?php elseif ($isParent): ?>
<!-- ── Parent Dashboard ───────────────────────────────────── -->
<div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-dark-text mb-4">My Children</h3>
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
        <p class="text-sm text-gray-500 dark:text-dark-muted">No children linked to your account.</p>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($children as $child): ?>
                <a href="<?= url('students', 'view', $child['id']) ?>" class="flex items-center justify-between p-3 rounded-lg border hover:bg-gray-50 dark:bg-dark-bg transition">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-dark-text"><?= e($child['full_name']) ?></p>
                        <p class="text-xs text-gray-500 dark:text-dark-muted"><?= e($child['admission_no']) ?> &bull; <?= e($child['class_name'] ?? 'N/A') ?> <?= e($child['section_name'] ?? '') ?></p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-dark-text">Recent Announcements</h3>
            <a href="<?= url('communication', 'announcements') ?>" class="text-xs text-primary-600 hover:underline">View all</a>
        </div>
        <?php if (empty($announcements)): ?>
            <p class="text-sm text-gray-400 dark:text-gray-500">No announcements.</p>
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
                                <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($ann['title']) ?></p>
                                <p class="text-xs text-gray-500 dark:text-dark-muted mt-0.5"><?= e(truncate(strip_tags($ann['content']), 80)) ?></p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1"><?= time_ago($ann['created_at']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Notifications -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-dark-text">Notifications</h3>
        </div>
        <?php if (empty($notifications)): ?>
            <p class="text-sm text-gray-400 dark:text-gray-500">No notifications.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($notifications as $notif): ?>
                    <div class="pb-3 border-b last:border-0 last:pb-0 <?= $notif['is_read'] ? '' : 'bg-blue-50 -mx-2 px-2 rounded' ?>">
                        <p class="text-sm <?= $notif['is_read'] ? 'text-gray-700 dark:text-gray-300' : 'text-gray-900 dark:text-dark-text font-medium' ?>"><?= e($notif['title']) ?></p>
                        <p class="text-xs text-gray-500 dark:text-dark-muted mt-0.5"><?= e(truncate($notif['message'], 80)) ?></p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1"><?= time_ago($notif['created_at']) ?></p>
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
