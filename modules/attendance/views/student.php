<?php
/**
 * Attendance — Individual Student Attendance View
 * Auto-detects student_id for logged-in student users
 */

$studentId = input_int('student_id');

// Auto-detect for student role
if (!$studentId && auth_has_role('student')) {
    $studentId = rbac_student_id();
}

// Parent: if no student_id, redirect to dashboard
if (!$studentId && auth_has_role('parent')) {
    $children = rbac_get_children();
    if (!empty($children)) {
        $studentId = (int) $children[0]['id'];
    }
}

if (!$studentId) {
    set_flash('error', 'No student specified.');
    redirect(url('attendance', 'report'));
}

// Validate parent can only view their own children's attendance
if (auth_has_role('parent') && !rbac_parent_has_child($studentId)) {
    set_flash('error', 'Access denied.');
    redirect(url('dashboard'));
}

// Students can only view their own attendance
if (auth_has_role('student') && $studentId !== rbac_student_id()) {
    set_flash('error', 'Access denied.');
    redirect(url('dashboard'));
}

$student = db_fetch_one("SELECT * FROM students WHERE id = ?", [$studentId]);
if (!$student) {
    set_flash('error', 'Student not found.');
    redirect(url('attendance', 'report'));
}

$activeSession = get_active_session();
$sessionId     = $activeSession['id'] ?? 0;

// Summary
$summary = db_fetch_one("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'present') AS present,
        SUM(status = 'absent') AS absent,
        SUM(status = 'late') AS late,
        SUM(status = 'excused') AS excused
    FROM attendance
    WHERE student_id = ? AND session_id = ?
", [$studentId, $sessionId]);

$pct = ($summary['total'] ?? 0) > 0
    ? round((($summary['present'] + $summary['late']) / $summary['total']) * 100, 1)
    : 0;

// Recent records
$records = db_fetch_all("
    SELECT a.date, a.status, a.remarks, c.name AS class_name
    FROM attendance a
    JOIN classes c ON c.id = a.class_id
    WHERE a.student_id = ? AND a.session_id = ?
    ORDER BY a.date DESC
    LIMIT 60
", [$studentId, $sessionId]);

ob_start();
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('attendance', 'report') ?>" class="p-2 hover:bg-gray-100 dark:hover:bg-dark-card2 rounded-lg">
            <svg class="w-5 h-5 text-gray-500 dark:text-dark-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h1>
            <p class="text-sm text-gray-500 dark:text-dark-muted"><?= e($student['admission_no']) ?> — Attendance Record</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4 text-center">
            <div class="text-2xl font-bold text-gray-900 dark:text-dark-text"><?= $summary['total'] ?? 0 ?></div>
            <div class="text-xs text-gray-500 dark:text-dark-muted mt-1">Total Days</div>
        </div>
        <div class="bg-green-50 rounded-xl border border-green-200 p-4 text-center">
            <div class="text-2xl font-bold text-green-700"><?= $summary['present'] ?? 0 ?></div>
            <div class="text-xs text-green-600 mt-1">Present</div>
        </div>
        <div class="bg-red-50 rounded-xl border border-red-200 p-4 text-center">
            <div class="text-2xl font-bold text-red-700"><?= $summary['absent'] ?? 0 ?></div>
            <div class="text-xs text-red-600 mt-1">Absent</div>
        </div>
        <div class="bg-yellow-50 rounded-xl border border-yellow-200 p-4 text-center">
            <div class="text-2xl font-bold text-yellow-700"><?= $summary['late'] ?? 0 ?></div>
            <div class="text-xs text-yellow-600 mt-1">Late</div>
        </div>
        <div class="bg-blue-50 rounded-xl border border-blue-200 p-4 text-center">
            <div class="text-2xl font-bold <?= $pct >= 75 ? 'text-blue-700' : 'text-red-700' ?>"><?= $pct ?>%</div>
            <div class="text-xs text-blue-600 mt-1">Rate</div>
        </div>
    </div>

    <!-- Records -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <div class="overflow-x-auto"><table class="w-full">
            <thead class="bg-gray-50 dark:bg-dark-bg border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Class</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Remarks</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                <?php foreach ($records as $r): ?>
                    <?php
                    $badge = match($r['status']) {
                        'present' => 'bg-green-100 text-green-800',
                        'absent'  => 'bg-red-100 text-red-800',
                        'late'    => 'bg-yellow-100 text-yellow-800',
                        'excused' => 'bg-blue-100 text-blue-800',
                        default   => 'bg-gray-100 dark:bg-dark-card2 text-gray-800 dark:text-dark-text',
                    };
                    ?>
                    <tr class="hover:bg-gray-50 dark:bg-dark-bg">
                        <td class="px-4 py-2.5 text-sm text-gray-900 dark:text-dark-text"><?= format_date($r['date']) ?></td>
                        <td class="px-4 py-2.5 text-sm text-gray-600 dark:text-dark-muted"><?= e($r['class_name']) ?></td>
                        <td class="px-4 py-2.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $badge ?>">
                                <?= ucfirst($r['status']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-sm text-gray-500 dark:text-dark-muted"><?= e($r['remarks']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
