<?php
/**
 * Academics — My Subjects (Student/Parent-facing view)
 * Shows subjects assigned to the student's class
 */

$studentInfo = null;
$enrollment = null;

if (auth_has_role('student')) {
    $studentInfo = rbac_get_student();
} elseif (auth_has_role('parent')) {
    $childId = input_int('student_id');
    $children = rbac_get_children();
    if ($childId && rbac_parent_has_child($childId)) {
        $studentInfo = db_fetch_one("SELECT s.*, e.class_id, e.section_id, c.name as class_name, sec.name as section_name
            FROM students s
            LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
            LEFT JOIN classes c ON e.class_id = c.id
            LEFT JOIN sections sec ON e.section_id = sec.id
            WHERE s.id = ? AND s.status = 'active'", [$childId]);
    } elseif (!empty($children)) {
        $studentInfo = db_fetch_one("SELECT s.*, e.class_id, e.section_id, c.name as class_name, sec.name as section_name
            FROM students s
            LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
            LEFT JOIN classes c ON e.class_id = c.id
            LEFT JOIN sections sec ON e.section_id = sec.id
            WHERE s.id = ? AND s.status = 'active'", [$children[0]['id']]);
    }
}

$subjects = [];
if ($studentInfo && !empty($studentInfo['class_id'])) {
    $activeSession = get_active_session();
    $sessionId = $activeSession['id'] ?? 0;
    $subjects = db_fetch_all("
        SELECT s.name, s.code, s.type, 
               CONCAT(u.first_name, ' ', u.last_name) AS teacher_name
        FROM class_subjects cs
        JOIN subjects s ON cs.subject_id = s.id
        LEFT JOIN class_teachers ct ON ct.class_id = cs.class_id AND ct.subject_id = cs.subject_id AND ct.session_id = cs.session_id
        LEFT JOIN users u ON u.id = ct.teacher_id
        WHERE cs.class_id = ? AND cs.session_id = ?
        ORDER BY s.name
    ", [$studentInfo['class_id'], $sessionId]);
}

ob_start();
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('dashboard') ?>" class="p-2 hover:bg-gray-100 dark:hover:bg-dark-card2 rounded-lg">
            <svg class="w-5 h-5 text-gray-500 dark:text-dark-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">My Subjects</h1>
            <?php if ($studentInfo): ?>
                <p class="text-sm text-gray-500 dark:text-dark-muted"><?= e($studentInfo['class_name'] ?? '') ?> — <?= e($studentInfo['section_name'] ?? '') ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($subjects)): ?>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-8 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            <p class="text-gray-500 dark:text-dark-muted">No subjects found for your class.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php
            $subjectColors = ['bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400', 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400', 'bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400', 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400', 'bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400', 'bg-cyan-50 dark:bg-cyan-900/20 text-cyan-600 dark:text-cyan-400', 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400', 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400'];
            foreach ($subjects as $i => $sub):
                $color = $subjectColors[$i % count($subjectColors)];
            ?>
            <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-100 dark:border-dark-border p-4 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 <?= $color ?> font-bold text-sm">
                    <?= strtoupper(substr($sub['code'] ?? $sub['name'], 0, 2)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 dark:text-dark-text"><?= e($sub['name']) ?></p>
                    <div class="flex items-center gap-3 mt-0.5">
                        <?php if (!empty($sub['code'])): ?>
                            <span class="text-xs text-gray-500 dark:text-dark-muted"><?= e($sub['code']) ?></span>
                        <?php endif; ?>
                        <span class="text-xs px-2 py-0.5 rounded-full <?= ($sub['type'] ?? '') === 'elective' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400' : 'bg-primary-100 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' ?>">
                            <?= ucfirst($sub['type'] ?? 'core') ?>
                        </span>
                    </div>
                </div>
                <?php if (!empty($sub['teacher_name'])): ?>
                    <div class="text-right flex-shrink-0">
                        <p class="text-xs text-gray-500 dark:text-dark-muted">Teacher</p>
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300"><?= e($sub['teacher_name']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-4 text-center"><?= count($subjects) ?> subject<?= count($subjects) !== 1 ? 's' : '' ?> total</p>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
