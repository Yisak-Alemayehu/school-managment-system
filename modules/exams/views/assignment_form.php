<?php
/**
 * Exams — Assignment Create/Edit Form
 */

$id = input_int('id');
$assignment = $id ? db_fetch_one("SELECT * FROM assignments WHERE id = ?", [$id]) : null;

$classes  = db_fetch_all("SELECT id, name FROM classes ORDER BY sort_order ASC");
$subjects = db_fetch_all("SELECT id, name FROM subjects ORDER BY name ASC");
$activeSession = get_active_session();
$activeTerm    = get_active_term();

ob_start();
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('exams', 'assignments') ?>" class="p-2 hover:bg-gray-100 rounded-lg">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900"><?= $assignment ? 'Edit Assignment' : 'New Assignment' ?></h1>
    </div>

    <form method="POST" action="<?= url('exams', 'assignment-save') ?>" enctype="multipart/form-data" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <?= csrf_field() ?>
        <?php if ($assignment): ?><input type="hidden" name="id" value="<?= $assignment['id'] ?>"><?php endif; ?>
        <input type="hidden" name="session_id" value="<?= $activeSession['id'] ?? 0 ?>">
        <input type="hidden" name="term_id" value="<?= $activeTerm['id'] ?? 0 ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input type="text" name="title" value="<?= e($assignment['title'] ?? old('title')) ?>" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class *</label>
                <select name="class_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($assignment['class_id'] ?? old('class_id')) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                <select name="subject_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($assignment['subject_id'] ?? old('subject_id')) == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500"><?= e($assignment['description'] ?? old('description')) ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date *</label>
                <input type="date" name="due_date" value="<?= e($assignment['due_date'] ?? old('due_date')) ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Total Marks *</label>
                <input type="number" name="total_marks" value="<?= e($assignment['total_marks'] ?? old('total_marks') ?? 100) ?>" min="1" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="draft" <?= ($assignment['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= ($assignment['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Attachment (optional)</label>
            <input type="file" name="file" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
            <?php if (!empty($assignment['file_path'])): ?>
                <p class="text-xs text-gray-500 mt-1">Current file: <?= e(basename($assignment['file_path'])) ?></p>
            <?php endif; ?>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                <?= $assignment ? 'Update' : 'Create' ?> Assignment
            </button>
            <a href="<?= url('exams', 'assignments') ?>" class="px-6 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 flex items-center">Cancel</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
