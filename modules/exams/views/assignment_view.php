<?php
/**
 * Exams — Assignment Detail View
 */

$id = input_int('id');
$assignment = db_fetch_one("
    SELECT a.*, c.name AS class_name, sub.name AS subject_name, u.full_name AS teacher_name
    FROM assignments a
    JOIN classes c ON c.id = a.class_id
    JOIN subjects sub ON sub.id = a.subject_id
    JOIN users u ON u.id = a.created_by
    WHERE a.id = ?
", [$id]);

if (!$assignment) {
    set_flash('error', 'Assignment not found.');
    redirect(url('exams', 'assignments'));
}

$submissions = db_fetch_all("
    SELECT asub.*, s.first_name, s.last_name, s.admission_no
    FROM assignment_submissions asub
    JOIN students s ON s.id = asub.student_id
    WHERE asub.assignment_id = ?
    ORDER BY asub.submitted_at DESC
", [$id]);

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('exams', 'assignments') ?>" class="p-2 hover:bg-gray-100 rounded-lg">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="flex-1">
            <h1 class="text-xl font-bold text-gray-900"><?= e($assignment['title']) ?></h1>
            <p class="text-sm text-gray-500"><?= e($assignment['class_name']) ?> &middot; <?= e($assignment['subject_name']) ?></p>
        </div>
        <?php if (auth_has_permission('assignment.manage')): ?>
            <a href="<?= url('exams', 'assignment-create') ?>&id=<?= $id ?>" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Edit</a>
            <form method="POST" action="<?= url('exams', 'assignment-delete') ?>" class="inline" onsubmit="return confirm('Delete this assignment?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <button class="px-3 py-1.5 border border-red-300 text-red-600 rounded-lg text-sm hover:bg-red-50">Delete</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Details -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
            <div>
                <span class="text-xs text-gray-500">Due Date</span>
                <p class="text-sm font-medium text-gray-900"><?= format_date($assignment['due_date']) ?></p>
            </div>
            <div>
                <span class="text-xs text-gray-500">Total Marks</span>
                <p class="text-sm font-medium text-gray-900"><?= $assignment['total_marks'] ?></p>
            </div>
            <div>
                <span class="text-xs text-gray-500">Teacher</span>
                <p class="text-sm font-medium text-gray-900"><?= e($assignment['teacher_name']) ?></p>
            </div>
            <div>
                <span class="text-xs text-gray-500">Status</span>
                <p class="text-sm">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $assignment['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' ?>">
                        <?= ucfirst($assignment['status']) ?>
                    </span>
                </p>
            </div>
        </div>
        <?php if ($assignment['description']): ?>
            <div class="border-t pt-4">
                <span class="text-xs text-gray-500">Description</span>
                <p class="text-sm text-gray-700 mt-1 whitespace-pre-line"><?= e($assignment['description']) ?></p>
            </div>
        <?php endif; ?>
        <?php if ($assignment['file_path']): ?>
            <div class="border-t pt-4 mt-4">
                <a href="/uploads/<?= e($assignment['file_path']) ?>" target="_blank" class="inline-flex items-center gap-2 text-sm text-primary-600 hover:text-primary-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download Attachment
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Submissions -->
    <h2 class="text-lg font-semibold text-gray-900 mb-3">Submissions (<?= count($submissions) ?>)</h2>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($submissions)): ?>
                    <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No submissions yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($submissions as $sub): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= e($sub['first_name'] . ' ' . $sub['last_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= format_datetime($sub['submitted_at']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= $sub['marks'] !== null ? $sub['marks'] . '/' . $assignment['total_marks'] : '<span class="text-gray-400">—</span>' ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                <?= $sub['status'] === 'graded' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                <?= ucfirst($sub['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
