<?php
/**
 * Communication — Announcement Create/Edit Form
 */
$id   = input_int('id');
$edit = null;
if ($id) {
    $edit = db_fetch_one("SELECT * FROM announcements WHERE id = ?", [$id]);
    if (!$edit) {
        set_flash('error', 'Announcement not found.');
        redirect(url('communication', 'announcements'));
    }
}

$pageTitle = $edit ? 'Edit Announcement' : 'New Announcement';

ob_start();
?>
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900"><?= $pageTitle ?></h1>
        <a href="<?= url('communication', 'announcements') ?>" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border p-6">
        <form method="POST" action="<?= url('communication', 'announcement-save') ?>">
            <?= csrf_field() ?>
            <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <?php endif; ?>

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                    <input type="text" name="title" required value="<?= e($edit['title'] ?? '') ?>"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Content *</label>
                    <textarea name="content" required rows="8"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"><?= e($edit['content'] ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Target Audience</label>
                        <select name="target_audience" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="all" <?= ($edit['target_audience'] ?? 'all') === 'all' ? 'selected' : '' ?>>Everyone</option>
                            <option value="teacher" <?= ($edit['target_audience'] ?? '') === 'teacher' ? 'selected' : '' ?>>Teachers</option>
                            <option value="student" <?= ($edit['target_audience'] ?? '') === 'student' ? 'selected' : '' ?>>Students</option>
                            <option value="parent" <?= ($edit['target_audience'] ?? '') === 'parent' ? 'selected' : '' ?>>Parents</option>
                            <option value="admin" <?= ($edit['target_audience'] ?? '') === 'admin' ? 'selected' : '' ?>>Admins</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Publish Date</label>
                        <input type="datetime-local" name="publish_date"
                               value="<?= $edit ? date('Y-m-d\TH:i', strtotime($edit['publish_date'])) : date('Y-m-d\TH:i') ?>"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="published" <?= ($edit['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= ($edit['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_pinned" value="1"
                               <?= ($edit['is_pinned'] ?? 0) ? 'checked' : '' ?>
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm font-medium text-gray-700">Pin to top</span>
                    </label>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="px-6 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
                        <?= $edit ? 'Update' : 'Publish' ?> Announcement
                    </button>
                    <a href="<?= url('communication', 'announcements') ?>" class="px-6 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
