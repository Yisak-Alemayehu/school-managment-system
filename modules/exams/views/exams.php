<?php
/**
 * Exams — Exams List View
 */

$activeSession = get_active_session();
$sessionId = $activeSession['id'] ?? 0;

$exams = db_fetch_all("
    SELECT e.*, s.name AS session_name, t.name AS term_name,
           (SELECT COUNT(*) FROM exam_schedules es WHERE es.exam_id = e.id) AS schedule_count
    FROM exams e
    LEFT JOIN academic_sessions s ON s.id = e.session_id
    LEFT JOIN terms t ON t.id = e.term_id
    WHERE e.session_id = ?
    ORDER BY e.start_date DESC
", [$sessionId]);

$terms = db_fetch_all("SELECT id, name FROM terms WHERE session_id = ? ORDER BY start_date", [$sessionId]);

$editId  = input_int('edit');
$editing = $editId ? db_fetch_one("SELECT * FROM exams WHERE id = ?", [$editId]) : null;

ob_start();
?>

<div class="max-w-5xl mx-auto">
    <h1 class="text-xl font-bold text-gray-900 mb-6">Exams</h1>

    <!-- Form -->
    <?php if (auth_has_permission('exam.manage')): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4"><?= $editing ? 'Edit Exam' : 'Create Exam' ?></h2>
        <form method="POST" action="<?= url('exams', 'exam-save') ?>" class="grid grid-cols-1 sm:grid-cols-5 gap-4">
            <?= csrf_field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>
            <input type="hidden" name="session_id" value="<?= $sessionId ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Exam Name</label>
                <input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required placeholder="e.g. Mid-Term Exam"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Term</label>
                <select name="term_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select</option>
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= ($editing['term_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="<?= e($editing['start_date'] ?? '') ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="<?= e($editing['end_date'] ?? '') ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                    <?= $editing ? 'Update' : 'Create' ?>
                </button>
                <?php if ($editing): ?>
                    <a href="<?= url('exams', 'exams') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- List -->
    <div class="space-y-3">
        <?php if (empty($exams)): ?>
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-500">No exams created yet.</div>
        <?php endif; ?>
        <?php foreach ($exams as $ex): ?>
            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-sm transition">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900"><?= e($ex['name']) ?></h3>
                        <div class="text-xs text-gray-500 mt-1">
                            <?= e($ex['term_name'] ?? 'N/A') ?> &middot; <?= format_date($ex['start_date']) ?> — <?= format_date($ex['end_date']) ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            <?= $ex['status'] === 'completed' ? 'bg-green-100 text-green-800' : ($ex['status'] === 'ongoing' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') ?>">
                            <?= ucfirst($ex['status']) ?>
                        </span>
                        <span class="text-xs text-gray-400"><?= $ex['schedule_count'] ?> schedules</span>
                        <a href="<?= url('exams', 'exam-schedule') ?>&exam_id=<?= $ex['id'] ?>" class="px-3 py-1 border border-gray-300 rounded-lg text-xs hover:bg-gray-50">Schedule</a>
                        <?php if (auth_has_permission('exam.manage')): ?>
                            <a href="<?= url('exams', 'exams') ?>&edit=<?= $ex['id'] ?>" class="p-1 text-gray-400 hover:text-yellow-600 rounded">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
