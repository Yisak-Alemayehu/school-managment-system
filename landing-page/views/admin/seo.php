<?php $pageTitle = 'SEO Settings'; $currentPage = 'seo'; include __DIR__ . '/layout_top.php'; ?>

<div class="space-y-4">
    <?php foreach ($seoSettings as $seo): ?>
    <form method="POST" action="<?= base_url('admin/seo/save') ?>" class="bg-white rounded-xl border border-gray-100 p-5">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $seo['id'] ?>">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-gray-900">Page: <?= e(ucfirst($seo['page_slug'])) ?></h3>
            <span class="text-xs text-gray-400 font-mono">/<?= e($seo['page_slug']) ?></span>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Meta Title</label>
                <input type="text" name="meta_title" value="<?= e($seo['meta_title'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" maxlength="70">
                <span class="text-[10px] text-gray-400">Max 70 characters</span>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Keywords</label>
                <input type="text" name="keywords" value="<?= e($seo['keywords'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="keyword1, keyword2">
            </div>
        </div>
        <div class="mt-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Meta Description</label>
            <textarea name="meta_description" rows="2" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" maxlength="160"><?= e($seo['meta_description'] ?? '') ?></textarea>
            <span class="text-[10px] text-gray-400">Max 160 characters</span>
        </div>
        <div class="grid sm:grid-cols-2 gap-4 mt-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">OG Title</label>
                <input type="text" name="og_title" value="<?= e($seo['og_title'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">OG Image URL</label>
                <input type="text" name="og_image" value="<?= e($seo['og_image'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
        </div>
        <div class="mt-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">OG Description</label>
            <textarea name="og_description" rows="2" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm"><?= e($seo['og_description'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="mt-4 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">Save SEO Settings</button>
    </form>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
