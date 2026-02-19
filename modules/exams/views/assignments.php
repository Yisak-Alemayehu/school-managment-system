<?php
/**
 * Exams — Assignments List View
 */

$activeSession = get_active_session();
$sessionId = $activeSession['id'] ?? 0;
$activeTerm = get_active_term();
$termId = $activeTerm['id'] ?? 0;

$filterClass = input_int('class_id');

$where  = "WHERE a.session_id = ? AND a.term_id = ?";
$params = [$sessionId, $termId];
if ($filterClass) {
    $where .= " AND a.class_id = ?";
    $params[] = $filterClass;
}

// For teachers, only show their assignments
if (auth_has_role('teacher') && !auth_is_super_admin()) {
    $where .= " AND a.created_by = ?";
    $params[] = auth_user()['id'];
}

$assignments = db_fetch_all("
    SELECT a.*, c.name AS class_name, sub.name AS subject_name, u.full_name AS teacher_name,
           (SELECT COUNT(*) FROM assignment_submissions asub WHERE asub.assignment_id = a.id) AS submission_count
    FROM assignments a
    JOIN classes c ON c.id = a.class_id
    JOIN subjects sub ON sub.id = a.subject_id
    JOIN users u ON u.id = a.created_by
    {$where}
    ORDER BY a.due_date DESC
", $params);

$classes = db_fetch_all("SELECT id, name FROM classes ORDER BY sort_order ASC");

ob_start();
?>

<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-gray-900">Assignments</h1>
        <?php if (auth_has_permission('assignment.manage')): ?>
            <a href="<?= url('exams', 'assignment-create') ?>" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                + New Assignment
            </a>
        <?php endif; ?>
    </div>

    <div class="flex items-center gap-3 mb-4">
        <select onchange="window.location='<?= url('exams', 'assignments') ?>&class_id='+this.value" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
            <option value="0">All Classes</option>
            <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $filterClass == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="space-y-3">
        <?php if (empty($assignments)): ?>
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-500">No assignments found.</div>
        <?php endif; ?>
        <?php foreach ($assignments as $a): ?>
            <?php $overdue = $a['due_date'] < date('Y-m-d') && $a['status'] === 'published'; ?>
            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-sm transition">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <a href="<?= url('exams', 'assignment-view') ?>&id=<?= $a['id'] ?>" class="text-sm font-semibold text-gray-900 hover:text-primary-600">
                            <?= e($a['title']) ?>
                        </a>
                        <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-gray-500">
                            <span><?= e($a['class_name']) ?></span>
                            <span>&middot;</span>
                            <span><?= e($a['subject_name']) ?></span>
                            <span>&middot;</span>
                            <span>By <?= e($a['teacher_name']) ?></span>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-xs text-gray-500">Due: <?= format_date($a['due_date']) ?></div>
                        <div class="flex items-center gap-1 mt-1">
                            <?php if ($a['status'] === 'draft'): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Draft</span>
                            <?php elseif ($overdue): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Overdue</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                            <?php endif; ?>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"><?= $a['submission_count'] ?> submissions</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
