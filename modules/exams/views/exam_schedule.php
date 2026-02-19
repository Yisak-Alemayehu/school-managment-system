<?php
/**
 * Exams — Exam Schedule View
 */

$examId = input_int('exam_id');
$exam = db_fetch_one("SELECT * FROM exams WHERE id = ?", [$examId]);
if (!$exam) {
    set_flash('error', 'Exam not found.');
    redirect(url('exams', 'exams'));
}

$classes  = db_fetch_all("SELECT id, name FROM classes ORDER BY sort_order ASC");
$subjects = db_fetch_all("SELECT id, name, code FROM subjects ORDER BY name ASC");

$schedules = db_fetch_all("
    SELECT es.*, c.name AS class_name, sub.name AS subject_name, sub.code AS subject_code
    FROM exam_schedules es
    JOIN classes c ON c.id = es.class_id
    JOIN subjects sub ON sub.id = es.subject_id
    WHERE es.exam_id = ?
    ORDER BY es.exam_date ASC, c.level ASC
", [$examId]);

$editId  = input_int('edit');
$editing = $editId ? db_fetch_one("SELECT * FROM exam_schedules WHERE id = ?", [$editId]) : null;

ob_start();
?>

<div class="max-w-5xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('exams', 'exams') ?>" class="p-2 hover:bg-gray-100 rounded-lg">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900">Schedule: <?= e($exam['name']) ?></h1>
    </div>

    <?php if (auth_has_permission('exam.manage')): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4"><?= $editing ? 'Edit Schedule' : 'Add Schedule Entry' ?></h2>
        <form method="POST" action="<?= url('exams', 'exam-schedule-save') ?>" class="grid grid-cols-1 sm:grid-cols-6 gap-4">
            <?= csrf_field() ?>
            <input type="hidden" name="exam_id" value="<?= $examId ?>">
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select name="class_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Select</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($editing['class_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                <select name="subject_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Select</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($editing['subject_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="exam_date" value="<?= e($editing['exam_date'] ?? '') ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start</label>
                <input type="time" name="start_time" value="<?= e($editing['start_time'] ?? '') ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Marks</label>
                <input type="number" name="full_marks" value="<?= e($editing['full_marks'] ?? 100) ?>" min="1" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                    <?= $editing ? 'Update' : 'Add' ?>
                </button>
                <?php if ($editing): ?>
                    <a href="<?= url('exams', 'exam-schedule') ?>&exam_id=<?= $examId ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Full Marks</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pass</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($schedules)): ?>
                    <tr><td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">No schedule entries yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($schedules as $s): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900"><?= format_date($s['exam_date']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= substr($s['start_time'], 0, 5) ?> — <?= substr($s['end_time'], 0, 5) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= e($s['class_name']) ?></td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= e($s['subject_name']) ?> (<?= e($s['subject_code']) ?>)</td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= $s['full_marks'] ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= $s['pass_marks'] ?></td>
                        <td class="px-4 py-3 text-right">
                            <?php if (auth_has_permission('exam.manage')): ?>
                                <a href="<?= url('exams', 'exam-schedule') ?>&exam_id=<?= $examId ?>&edit=<?= $s['id'] ?>" class="p-1 text-gray-400 hover:text-yellow-600">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                            <?php endif; ?>
                            <?php if (auth_has_permission('marks.manage')): ?>
                                <a href="<?= url('exams', 'marks') ?>&schedule_id=<?= $s['id'] ?>" class="ml-1 px-2 py-0.5 bg-primary-100 text-primary-700 rounded text-xs font-medium hover:bg-primary-200">Enter Marks</a>
                            <?php endif; ?>
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
