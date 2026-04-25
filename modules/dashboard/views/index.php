<?php
/**
 * Dashboard — Main View
 * Role-based dashboard with summary cards and quick actions
 * Styled as mobile-first app-like portals per role
 */
$user = auth_user();
$isSuperAdmin = auth_is_super_admin();
$isAdmin      = auth_has_role('admin') || auth_has_role('school_admin') || $isSuperAdmin;
$isTeacher    = auth_has_role('teacher');
$isStudent    = auth_has_role('student');
$isParent     = auth_has_role('parent');
$isAccountant = auth_has_role('accountant');

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

    // ── Students per class (for pie chart) ──
    $studentsPerClass = db_fetch_all(
        "SELECT c.id, c.name AS class_name, COUNT(e.student_id) AS student_count
         FROM classes c
         LEFT JOIN sections sec ON sec.class_id = c.id AND sec.is_active = 1
         LEFT JOIN enrollments e ON e.section_id = sec.id AND e.status = 'active'
         LEFT JOIN students s ON s.id = e.student_id AND s.status = 'active' AND s.deleted_at IS NULL
         WHERE c.is_active = 1
         GROUP BY c.id, c.name
         ORDER BY c.sort_order, c.name"
    );

    // ── Employee gender breakdown ──
    $superAdminStats['male_employees']   = db_fetch_value("SELECT COUNT(*) FROM hr_employees WHERE gender = 'male'   AND status = 'active' AND deleted_at IS NULL") ?: 0;
    $superAdminStats['female_employees'] = db_fetch_value("SELECT COUNT(*) FROM hr_employees WHERE gender = 'female' AND status = 'active' AND deleted_at IS NULL") ?: 0;
    $superAdminStats['total_employees']  = $superAdminStats['male_employees'] + $superAdminStats['female_employees'];

    // ── Financial Summary ──
    $superAdminStats['total_fees_assigned'] = db_fetch_value(
        "SELECT COALESCE(SUM(amount), 0) FROM fin_student_fees WHERE is_active = 1"
    ) ?: 0;
    $superAdminStats['total_collected'] = db_fetch_value(
        "SELECT COALESCE(SUM(amount), 0) FROM fin_transactions WHERE type = 'payment'"
    ) ?: 0;
    $superAdminStats['total_outstanding'] = db_fetch_value(
        "SELECT COALESCE(SUM(balance), 0) FROM fin_student_fees WHERE is_active = 1 AND balance > 0"
    ) ?: 0;
    $superAdminStats['total_penalties'] = db_fetch_value(
        "SELECT COALESCE(SUM(amount), 0) FROM fin_transactions WHERE type = 'penalty'"
    ) ?: 0;
    $superAdminStats['collection_rate'] = $superAdminStats['total_fees_assigned'] > 0
        ? round(($superAdminStats['total_collected'] / $superAdminStats['total_fees_assigned']) * 100, 1)
        : 0;
    // Recent 5 payments
    $recentPayments = db_fetch_all(
        "SELECT t.amount, t.currency, t.channel, t.created_at, s.full_name, s.admission_no
         FROM fin_transactions t
         JOIN students s ON t.student_id = s.id
         WHERE t.type = 'payment'
         ORDER BY t.created_at DESC LIMIT 5"
    );

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

<!-- Welcome Bar (Admin/Parent only - Student/Teacher have their own profile headers) -->
<?php if ($isAdmin || $isParent): ?>
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
<?php endif; ?>

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
        <div class="w-11 h-11 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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


<!-- ── Class Distribution (Donut Chart + Breakdown) ── -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Students per Class</h3>
        <?php
        $classColors = ['#3B82F6','#8B5CF6','#EC4899','#F59E0B','#10B981','#06B6D4','#EF4444','#6366F1','#14B8A6','#F97316','#84CC16','#E11D48'];
        $totalClassStudents = array_sum(array_column($studentsPerClass, 'student_count'));
        ?>
        <?php if ($totalClassStudents > 0): ?>
        <div class="flex items-center gap-6">
            <!-- SVG Donut -->
            <div class="flex-shrink-0">
                <svg width="130" height="130" viewBox="0 0 42 42" class="donut-chart">
                    <circle cx="21" cy="21" r="15.915" fill="none" stroke="#e5e7eb" stroke-width="5"/>
                    <?php
                    $cumulativePercent = 0;
                    foreach ($studentsPerClass as $i => $cls):
                        if ($cls['student_count'] <= 0) continue;
                        $pct = ($cls['student_count'] / $totalClassStudents) * 100;
                        $color = $classColors[$i % count($classColors)];
                        $dashArray = $pct . ' ' . (100 - $pct);
                        $offset = 100 - $cumulativePercent + 25;
                    ?>
                    <circle cx="21" cy="21" r="15.915" fill="none" stroke="<?= $color ?>" stroke-width="5"
                            stroke-dasharray="<?= $dashArray ?>" stroke-dashoffset="<?= $offset ?>"
                            class="transition-all duration-500"/>
                    <?php
                        $cumulativePercent += $pct;
                    endforeach;
                    ?>
                    <text x="21" y="21" text-anchor="middle" dy=".35em" class="text-[6px] font-bold fill-gray-700 dark:fill-gray-200"><?= number_format($totalClassStudents) ?></text>
                    <text x="21" y="26" text-anchor="middle" class="text-[3px] fill-gray-400">students</text>
                </svg>
            </div>
            <!-- Legend -->
            <div class="flex-1 space-y-1.5 max-h-[140px] overflow-y-auto">
                <?php foreach ($studentsPerClass as $i => $cls):
                    $color = $classColors[$i % count($classColors)];
                    $pct = $totalClassStudents > 0 ? round($cls['student_count'] / $totalClassStudents * 100, 1) : 0;
                ?>
                <div class="flex items-center gap-2 text-xs">
                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:<?= $color ?>"></span>
                    <span class="flex-1 text-gray-700 dark:text-gray-300 truncate"><?= e($cls['class_name']) ?></span>
                    <span class="text-gray-500 dark:text-dark-muted font-medium"><?= number_format($cls['student_count']) ?></span>
                    <span class="text-gray-400 dark:text-gray-500 w-10 text-right"><?= $pct ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
            <p class="text-sm text-gray-400 dark:text-gray-500">No student enrollments found.</p>
        <?php endif; ?>
    </div>

    <!-- Class Breakdown Table -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Class Details</h3>
        <div class="overflow-x-auto max-h-[180px] overflow-y-auto">
            <table class="w-full text-xs">
                <thead class="sticky top-0 bg-white dark:bg-dark-card">
                    <tr class="text-left text-gray-500 dark:text-dark-muted border-b">
                        <th class="pb-2 font-medium">Class</th>
                        <th class="pb-2 font-medium text-right">Students</th>
                        <th class="pb-2 font-medium text-right">Share</th>
                        <th class="pb-2 font-medium pl-3">Distribution</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-dark-border">
                    <?php foreach ($studentsPerClass as $i => $cls):
                        $color = $classColors[$i % count($classColors)];
                        $pct = $totalClassStudents > 0 ? round($cls['student_count'] / $totalClassStudents * 100, 1) : 0;
                    ?>
                    <tr>
                        <td class="py-1.5 text-gray-700 dark:text-gray-300 font-medium"><?= e($cls['class_name']) ?></td>
                        <td class="py-1.5 text-gray-600 dark:text-dark-muted text-right"><?= number_format($cls['student_count']) ?></td>
                        <td class="py-1.5 text-gray-500 dark:text-dark-muted text-right"><?= $pct ?>%</td>
                        <td class="py-1.5 pl-3">
                            <div class="w-full bg-gray-100 dark:bg-dark-card2 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── Gender Demographics ── -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <!-- Student Gender Breakdown -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Student Gender Ratio</h3>
        <?php
        $totalG   = $superAdminStats['male_students'] + $superAdminStats['female_students'];
        $malePct  = $totalG > 0 ? round($superAdminStats['male_students']   / $totalG * 100) : 50;
        $femPct   = $totalG > 0 ? round($superAdminStats['female_students'] / $totalG * 100) : 50;
        ?>
        <!-- Stacked Bar -->
        <div class="flex rounded-full h-5 overflow-hidden mb-3">
            <div class="bg-blue-500 h-5 flex items-center justify-center text-[10px] text-white font-bold transition-all" style="width:<?= $malePct ?>%"><?= $malePct ?>%</div>
            <div class="bg-pink-500 h-5 flex items-center justify-center text-[10px] text-white font-bold transition-all" style="width:<?= $femPct ?>%"><?= $femPct ?>%</div>
        </div>
        <div class="flex justify-between text-xs">
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                <span class="text-gray-600 dark:text-dark-muted">Male</span>
                <span class="font-semibold text-gray-800 dark:text-dark-text"><?= number_format($superAdminStats['male_students']) ?></span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                <span class="text-gray-600 dark:text-dark-muted">Female</span>
                <span class="font-semibold text-gray-800 dark:text-dark-text"><?= number_format($superAdminStats['female_students']) ?></span>
            </div>
        </div>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">Total active students: <?= number_format($totalG) ?></p>
    </div>

    <!-- Employee Gender Breakdown -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Employee Gender Ratio</h3>
        <?php
        $totalEmp     = $superAdminStats['total_employees'];
        $empMalePct   = $totalEmp > 0 ? round($superAdminStats['male_employees']   / $totalEmp * 100) : 50;
        $empFemPct    = $totalEmp > 0 ? round($superAdminStats['female_employees'] / $totalEmp * 100) : 50;
        ?>
        <!-- Stacked Bar -->
        <div class="flex rounded-full h-5 overflow-hidden mb-3">
            <div class="bg-indigo-500 h-5 flex items-center justify-center text-[10px] text-white font-bold transition-all" style="width:<?= $empMalePct ?>%"><?= $empMalePct ?>%</div>
            <div class="bg-rose-400 h-5 flex items-center justify-center text-[10px] text-white font-bold transition-all" style="width:<?= $empFemPct ?>%"><?= $empFemPct ?>%</div>
        </div>
        <div class="flex justify-between text-xs">
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                <span class="text-gray-600 dark:text-dark-muted">Male</span>
                <span class="font-semibold text-gray-800 dark:text-dark-text"><?= number_format($superAdminStats['male_employees']) ?></span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                <span class="text-gray-600 dark:text-dark-muted">Female</span>
                <span class="font-semibold text-gray-800 dark:text-dark-text"><?= number_format($superAdminStats['female_employees']) ?></span>
            </div>
        </div>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">Total active employees: <?= number_format($totalEmp) ?></p>
    </div>
</div>

<!-- ── Financial Summary ── -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 sm:p-5 flex items-center gap-4">
        <div class="w-11 h-11 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Total Collected</p>
            <p class="text-xl font-bold text-emerald-600"><?= number_format($superAdminStats['total_collected'], 2) ?></p>
            <p class="text-[10px] text-gray-400 dark:text-gray-500">ETB</p>
        </div>
    </div>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 sm:p-5 flex items-center gap-4">
        <div class="w-11 h-11 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
            </svg>
        </div>
        <div>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Outstanding</p>
            <p class="text-xl font-bold text-red-600"><?= number_format($superAdminStats['total_outstanding'], 2) ?></p>
            <p class="text-[10px] text-gray-400 dark:text-gray-500">ETB unpaid</p>
        </div>
    </div>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 sm:p-5 flex items-center gap-4">
        <div class="w-11 h-11 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
        </div>
        <div>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Penalties</p>
            <p class="text-xl font-bold text-amber-600"><?= number_format($superAdminStats['total_penalties'], 2) ?></p>
            <p class="text-[10px] text-gray-400 dark:text-gray-500">ETB charged</p>
        </div>
    </div>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 sm:p-5 flex items-center gap-4">
        <div class="w-11 h-11 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
        </div>
        <div>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Collection Rate</p>
            <p class="text-xl font-bold <?= $superAdminStats['collection_rate'] >= 75 ? 'text-green-600' : ($superAdminStats['collection_rate'] >= 50 ? 'text-yellow-600' : 'text-red-600') ?>"><?= $superAdminStats['collection_rate'] ?>%</p>
            <p class="text-[10px] text-gray-400 dark:text-gray-500">of <?= number_format($superAdminStats['total_fees_assigned'], 2) ?> ETB</p>
        </div>
    </div>
</div>

<!-- Financial Collection Progress + Recent Payments -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <!-- Collection Progress -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Fee Collection Progress</h3>
        <div class="w-full bg-gray-100 dark:bg-dark-card2 rounded-full h-4 overflow-hidden mb-2">
            <div class="h-4 rounded-full transition-all <?= $superAdminStats['collection_rate'] >= 75 ? 'bg-emerald-500' : ($superAdminStats['collection_rate'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') ?>"
                 style="width:<?= min($superAdminStats['collection_rate'], 100) ?>%"></div>
        </div>
        <div class="flex justify-between text-xs text-gray-500 dark:text-dark-muted">
            <span>Collected: <?= number_format($superAdminStats['total_collected'], 2) ?> ETB</span>
            <span>Total: <?= number_format($superAdminStats['total_fees_assigned'], 2) ?> ETB</span>
        </div>
        <a href="<?= url('finance') ?>" class="text-xs text-primary-600 hover:underline mt-3 inline-block">View Finance &rarr;</a>
    </div>

    <!-- Recent Payments -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Recent Payments</h3>
        <?php if (empty($recentPayments)): ?>
            <p class="text-xs text-gray-400 dark:text-gray-500">No payments recorded yet.</p>
        <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($recentPayments as $rp): ?>
            <div class="flex items-center justify-between text-xs">
                <div class="min-w-0 flex-1">
                    <p class="text-gray-700 dark:text-gray-300 font-medium truncate"><?= e($rp['full_name']) ?></p>
                    <p class="text-gray-400 dark:text-gray-500"><?= e($rp['admission_no']) ?> &bull; <?= time_ago($rp['created_at']) ?></p>
                </div>
                <span class="text-emerald-600 font-semibold ml-2">+<?= number_format($rp['amount'], 2) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Row: Attendance rate -->
<div class="grid grid-cols-1 gap-4 mb-6">

    <!-- Today's Attendance Rate + Class Breakdown -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5 sm:col-span-1">
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
<!-- ── Teacher Portal Dashboard ───────────────────────────── -->
<?php
// Get teacher's assigned classes
$teacherName = explode(' ', $user['name'] ?? $user['full_name'] ?? 'Teacher')[0];
$avatarUrl = !empty($user['avatar']) ? url('/uploads/' . $user['avatar']) : '';
$activeSession = get_active_session();

$assignedClasses = [];
if ($activeSession) {
    $assignedClasses = db_fetch_all(
        "SELECT DISTINCT c.id, c.name as class_name, sec.name as section_name, c.sort_order,
                (SELECT COUNT(DISTINCT e.student_id) FROM enrollments e WHERE e.section_id = sec.id AND e.status = 'active') as student_count
         FROM class_teachers ct
         JOIN sections sec ON ct.section_id = sec.id
         JOIN classes c ON sec.class_id = c.id
         WHERE ct.teacher_id = ? AND ct.session_id = ?
         ORDER BY c.sort_order, c.name",
        [$user['id'], $activeSession['id']]
    );
}

// Today's timetable
$todayDay = strtolower(date('l'));
$teacherTimetable = [];
if ($activeSession) {
    $teacherTimetable = db_fetch_all(
        "SELECT t.*, s.name as subject_name, c.name as class_name, sec.name as section_name
         FROM timetables t
         JOIN subjects s ON t.subject_id = s.id
         JOIN sections sec ON t.section_id = sec.id
         JOIN classes c ON sec.class_id = c.id
         WHERE t.teacher_id = ? AND t.day_of_week = ? AND t.session_id = ?
         ORDER BY t.start_time",
        [$user['id'], $todayDay, $activeSession['id']]
    );
}
?>

<!-- Profile Header -->
<div class="bg-gradient-to-br from-primary-600 to-primary-800 dark:from-primary-800 dark:to-primary-950 rounded-2xl p-5 mb-6 text-white relative overflow-hidden">
    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-8 translate-x-8"></div>
    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-6 -translate-x-6"></div>
    <div class="flex items-center gap-4 relative z-10">
        <?php if ($avatarUrl): ?>
            <img src="<?= e($avatarUrl) ?>" alt="" class="w-16 h-16 rounded-full border-2 border-white/30 object-cover">
        <?php else: ?>
            <div class="w-16 h-16 rounded-full border-2 border-white/30 bg-white/20 flex items-center justify-center text-2xl font-bold">
                <?= strtoupper(substr($teacherName, 0, 1)) ?>
            </div>
        <?php endif; ?>
        <div>
            <h1 class="text-xl font-bold">Hi, <?= e($teacherName) ?>!</h1>
            <p class="text-primary-100 text-sm mt-0.5"><?= count($assignedClasses) ?> class<?= count($assignedClasses) !== 1 ? 'es' : '' ?> assigned</p>
            <p class="text-primary-200 text-xs mt-0.5"><?= date('l, M j, Y') ?></p>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-2 gap-3 mb-6">
    <!-- Take Attendance -->
    <a href="<?= url('attendance') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Attendance</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Take / View</p>
        </div>
    </a>

    <!-- Enter Marks -->
    <a href="<?= url('exams', 'marks') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Enter Marks</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Grade students</p>
        </div>
    </a>

    <!-- Assignments -->
    <a href="<?= url('exams', 'assignments') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Assignments</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Manage tasks</p>
        </div>
    </a>

    <!-- Timetable -->
    <a href="<?= url('academics', 'my-timetable') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Timetable</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Schedule</p>
        </div>
    </a>

    <!-- Report Cards -->
    <a href="<?= url('exams', 'report-cards') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Report Cards</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">View / Print</p>
        </div>
    </a>

    <!-- Messages -->
    <a href="<?= url('messaging', 'inbox') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-cyan-50 dark:bg-cyan-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Messages</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Inbox</p>
        </div>
    </a>

    <!-- Students -->
    <a href="<?= url('students', 'details') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Students</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Class roster</p>
        </div>
    </a>

    <!-- Profile -->
    <a href="<?= url('auth', 'profile') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-gray-50 dark:bg-gray-800/40 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Profile</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">My info</p>
        </div>
    </a>
</div>

<!-- Today's Timetable -->
<?php if (!empty($teacherTimetable)): ?>
<div class="mb-6">
    <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-3">Today's Schedule</h2>
    <div class="space-y-2">
        <?php
        $periodColors = ['bg-blue-500', 'bg-purple-500', 'bg-amber-500', 'bg-rose-500', 'bg-cyan-500', 'bg-indigo-500', 'bg-emerald-500', 'bg-primary-500'];
        foreach ($teacherTimetable as $i => $tt):
            $color = $periodColors[$i % count($periodColors)];
        ?>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3">
            <div class="w-1.5 h-12 <?= $color ?> rounded-full flex-shrink-0"></div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 dark:text-dark-text"><?= e($tt['subject_name']) ?></p>
                <p class="text-xs text-gray-500 dark:text-dark-muted"><?= e($tt['class_name']) ?> — <?= e($tt['section_name']) ?></p>
            </div>
            <div class="text-right flex-shrink-0">
                <p class="text-xs font-medium text-gray-700 dark:text-gray-300"><?= date('g:i A', strtotime($tt['start_time'])) ?></p>
                <p class="text-xs text-gray-400 dark:text-dark-muted"><?= date('g:i A', strtotime($tt['end_time'])) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- My Classes -->
<?php if (!empty($assignedClasses)): ?>
<div class="mb-6">
    <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-3">My Classes</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        <?php foreach ($assignedClasses as $ac): ?>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-gray-800 dark:text-dark-text"><?= e($ac['class_name']) ?> — <?= e($ac['section_name']) ?></p>
                <p class="text-xs text-gray-500 dark:text-dark-muted"><?= $ac['student_count'] ?> student<?= $ac['student_count'] != 1 ? 's' : '' ?></p>
            </div>
            <div class="w-8 h-8 rounded-full bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center">
                <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php elseif ($isStudent): ?>
<!-- ── Student Portal Dashboard ───────────────────────────── -->
<?php
// Get student info
$studentInfo = rbac_get_student();
$enrollment = null;
if ($studentInfo) {
    $enrollment = db_fetch_one(
        "SELECT e.*, c.name as class_name, sec.name as section_name
         FROM enrollments e
         JOIN sections sec ON e.section_id = sec.id
         JOIN classes c ON sec.class_id = c.id
         WHERE e.student_id = ? AND e.status = 'active'
         ORDER BY e.id DESC LIMIT 1",
        [$studentInfo['id']]
    );
}
$studentName = explode(' ', $user['name'] ?? $user['full_name'] ?? 'Student')[0];
$avatarUrl = !empty($user['avatar']) ? url('/uploads/' . $user['avatar']) : '';
?>

<!-- Profile Header -->
<div class="bg-gradient-to-br from-primary-600 to-primary-800 dark:from-primary-800 dark:to-primary-950 rounded-2xl p-5 mb-6 text-white relative overflow-hidden">
    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-8 translate-x-8"></div>
    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-6 -translate-x-6"></div>
    <div class="flex items-center gap-4 relative z-10">
        <?php if ($avatarUrl): ?>
            <img src="<?= e($avatarUrl) ?>" alt="" class="w-16 h-16 rounded-full border-2 border-white/30 object-cover">
        <?php else: ?>
            <div class="w-16 h-16 rounded-full border-2 border-white/30 bg-white/20 flex items-center justify-center text-2xl font-bold">
                <?= strtoupper(substr($studentName, 0, 1)) ?>
            </div>
        <?php endif; ?>
        <div>
            <h1 class="text-xl font-bold">Hi, <?= e($studentName) ?>!</h1>
            <?php if ($enrollment): ?>
                <p class="text-primary-100 text-sm mt-0.5"><?= e($enrollment['class_name']) ?> — <?= e($enrollment['section_name']) ?></p>
            <?php endif; ?>
            <p class="text-primary-200 text-xs mt-0.5"><?= date('l, M j, Y') ?></p>
        </div>
    </div>
</div>

<!-- Quick Menu Cards -->
<div class="grid grid-cols-2 gap-3 mb-6">
    <!-- Attendance -->
    <a href="<?= url('attendance', 'student') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Attendance</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">View records</p>
        </div>
    </a>

    <!-- Timetable -->
    <a href="<?= url('academics', 'my-timetable') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Timetable</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Daily schedule</p>
        </div>
    </a>

    <!-- Exams -->
    <a href="<?= url('exams', 'exams') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Exams</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Exam schedule</p>
        </div>
    </a>

    <!-- Results -->
    <a href="<?= url('exams', 'report-cards') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Results</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Report cards</p>
        </div>
    </a>

    <!-- Assignments -->
    <a href="<?= url('exams', 'assignments') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Assignments</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Homework</p>
        </div>
    </a>

    <!-- Messages -->
    <a href="<?= url('messaging', 'inbox') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-cyan-50 dark:bg-cyan-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Messages</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Inbox</p>
        </div>
    </a>

    <!-- Subjects -->
    <a href="<?= url('academics', 'my-subjects') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Subjects</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">My subjects</p>
        </div>
    </a>

    <!-- Profile -->
    <a href="<?= url('auth', 'profile') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-gray-50 dark:bg-gray-800/40 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Profile</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">My info</p>
        </div>
    </a>
</div>

<?php elseif ($isParent): ?>
<!-- ── Parent Portal Dashboard ────────────────────────────── -->
<?php
$parentName = explode(' ', $user['name'] ?? $user['full_name'] ?? 'Parent')[0];
$avatarUrl = !empty($user['avatar']) ? url('/uploads/' . $user['avatar']) : '';
$children = db_fetch_all(
    "SELECT s.id, s.admission_no, s.full_name, s.first_name, c.name as class_name, sec.name as section_name
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

<!-- Profile Header -->
<div class="bg-gradient-to-br from-primary-600 to-primary-700 dark:from-primary-800 dark:to-primary-900 rounded-2xl p-5 mb-6 text-white relative overflow-hidden">
    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-8 translate-x-8"></div>
    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-6 -translate-x-6"></div>
    <div class="flex items-center gap-4 relative z-10">
        <?php if ($avatarUrl): ?>
            <img src="<?= e($avatarUrl) ?>" alt="" class="w-16 h-16 rounded-full border-2 border-white/30 object-cover">
        <?php else: ?>
            <div class="w-16 h-16 rounded-full border-2 border-white/30 bg-white/20 flex items-center justify-center text-2xl font-bold">
                <?= strtoupper(substr($parentName, 0, 1)) ?>
            </div>
        <?php endif; ?>
        <div>
            <h1 class="text-xl font-bold">Hi, <?= e($parentName) ?>!</h1>
            <p class="text-primary-100 text-sm mt-0.5"><?= count($children) ?> child<?= count($children) !== 1 ? 'ren' : '' ?> enrolled</p>
            <p class="text-primary-200 text-xs mt-0.5"><?= date('l, M j, Y') ?></p>
        </div>
    </div>
</div>

<!-- My Children -->
<?php if (empty($children)): ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6 text-center mb-6">
        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <p class="text-gray-500 dark:text-dark-muted">No children linked to your account.</p>
    </div>
<?php else: ?>
    <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-3">My Children</h2>
    <div class="space-y-3 mb-6">
        <?php foreach ($children as $child): ?>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center text-primary-700 dark:text-primary-400 font-bold text-sm flex-shrink-0">
                    <?= strtoupper(substr($child['first_name'] ?? $child['full_name'], 0, 1)) ?>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-800 dark:text-dark-text"><?= e($child['full_name']) ?></p>
                    <p class="text-xs text-gray-500 dark:text-dark-muted"><?= e($child['class_name'] ?? 'N/A') ?> — <?= e($child['section_name'] ?? '') ?></p>
                </div>
                <a href="<?= url('students', 'view', $child['id']) ?>" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">View Profile</a>
            </div>
            <!-- Quick links for this child -->
            <div class="grid grid-cols-4 gap-2">
                <a href="<?= url('attendance', 'student') ?>?student_id=<?= $child['id'] ?>" class="flex flex-col items-center gap-1 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-dark-card2 transition">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                    <span class="text-[10px] text-gray-600 dark:text-dark-muted">Attendance</span>
                </a>
                <a href="<?= url('exams', 'report-cards') ?>?student_id=<?= $child['id'] ?>" class="flex flex-col items-center gap-1 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-dark-card2 transition">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <span class="text-[10px] text-gray-600 dark:text-dark-muted">Results</span>
                </a>
                <a href="<?= url('academics', 'my-subjects') ?>?student_id=<?= $child['id'] ?>" class="flex flex-col items-center gap-1 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-dark-card2 transition">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    <span class="text-[10px] text-gray-600 dark:text-dark-muted">Subjects</span>
                </a>
                <a href="<?= url('exams', 'assignments') ?>" class="flex flex-col items-center gap-1 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-dark-card2 transition">
                    <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    <span class="text-[10px] text-gray-600 dark:text-dark-muted">Homework</span>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Quick Menu Cards -->
<div class="grid grid-cols-2 gap-3 mb-6">
    <!-- Messages -->
    <a href="<?= url('messaging', 'inbox') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-cyan-50 dark:bg-cyan-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Messages</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Contact school</p>
        </div>
    </a>

    <!-- Announcements -->
    <a href="<?= url('communication', 'announcements') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Announcements</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">School news</p>
        </div>
    </a>

    <!-- Finance -->
    <a href="<?= url('finance', 'students') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Fees</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">Payment status</p>
        </div>
    </a>

    <!-- Profile -->
    <a href="<?= url('auth', 'profile') ?>" class="portal-card group bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-3 hover:shadow-md transition-all active:scale-[0.98]">
        <div class="w-11 h-11 rounded-xl bg-gray-50 dark:bg-gray-800/40 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 dark:text-dark-text">Profile</p>
            <p class="text-xs text-gray-500 dark:text-dark-muted">My info</p>
        </div>
    </a>
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
