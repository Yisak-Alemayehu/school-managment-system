<?php
/**
 * Communication — View Announcement
 */
$id = input_int('id');
$a  = db_fetch_one("
    SELECT a.*, u.full_name AS author_name
    FROM announcements a
    LEFT JOIN users u ON u.id = a.created_by
    WHERE a.id = ?
", [$id]);

if (!$a) {
    set_flash('error', 'Announcement not found.');
    redirect(url('communication', 'announcements'));
}

$pageTitle = $a['title'];

ob_start();
?>
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="<?= url('communication', 'announcements') ?>" class="text-sm text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:text-gray-300">&larr; All Announcements</a>
        <?php if (has_permission('manage_communication')): ?>
            <a href="<?= url('communication', 'announcement-edit') ?>&id=<?= $a['id'] ?>"
               class="px-4 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text hover:bg-gray-50 dark:bg-dark-bg">Edit</a>
        <?php endif; ?>
    </div>

    <article class="bg-white dark:bg-dark-card rounded-xl shadow-sm border p-8">
        <?php if ($a['is_pinned']): ?>
            <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full font-medium">Pinned</span>
        <?php endif; ?>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-dark-text mt-2"><?= e($a['title']) ?></h1>
        <div class="flex items-center gap-4 mt-2 text-sm text-gray-500 dark:text-dark-muted">
            <span>By <?= e($a['author_name'] ?? 'System') ?></span>
            <span><?= format_date($a['publish_date']) ?></span>
            <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800"><?= ucfirst($a['target_audience']) ?></span>
        </div>
        <hr class="my-4">
        <div class="prose max-w-none text-gray-700 dark:text-gray-300 leading-relaxed">
            <?= nl2br(e($a['content'])) ?>
        </div>
    </article>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
