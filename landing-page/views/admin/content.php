<?php $pageTitle = 'Content Management'; $currentPage = 'content'; include __DIR__ . '/layout_top.php'; ?>

<div class="space-y-4">
    <?php foreach ($contentSections as $section): ?>
    <form method="POST" action="<?= base_url('admin/content/update') ?>" class="bg-white rounded-xl border border-gray-100 p-5" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $section['id'] ?>">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-gray-900"><?= e(ucwords(str_replace('_', ' ', $section['section_key']))) ?></h3>
            <span class="text-xs text-gray-400 font-mono"><?= e($section['section_key']) ?></span>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                <input type="text" name="title" value="<?= e($section['title']) ?>" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Subtitle</label>
                <input type="text" name="subtitle" value="<?= e($section['subtitle'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20">
            </div>
        </div>
        <div class="mt-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Content</label>
            <textarea name="content" rows="4" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20"><?= e($section['content'] ?? '') ?></textarea>
        </div>
        <div class="mt-3 grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Image</label>
                <input type="file" name="image" accept="image/*" class="w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                <?php if ($section['image_path']): ?><span class="text-xs text-gray-500">Current: <?= e($section['image_path']) ?></span><?php endif; ?>
            </div>
            <div class="flex items-end gap-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" <?= $section['is_active'] ? 'checked' : '' ?> class="rounded border-gray-300 text-primary-600">
                    Active
                </label>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">Save</button>
            </div>
        </div>
    </form>
    <?php endforeach; ?>
    <?php if (empty($contentSections)): ?>
    <div class="bg-white rounded-xl border border-gray-100 p-8 text-center text-gray-500">No content sections found.</div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
