<?php
/**
 * Academics — Subjects View (Fixed)
 * Type uses theory/practical/both to match DB ENUM.
 */

$subjects = db_fetch_all("
    SELECT s.*,
           (SELECT COUNT(*) FROM class_subjects cs WHERE cs.subject_id = s.id) AS class_count
    FROM subjects s
    ORDER BY s.name ASC
");

$editId  = input_int('edit');
$editing = $editId ? db_fetch_one("SELECT * FROM subjects WHERE id = ?", [$editId]) : null;

ob_start();
?>

<div class="max-w-5xl mx-auto">

    <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text mb-6">Subjects</h1>

    <!-- Form -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4"><?= $editing ? 'Edit Subject' : 'Add New Subject' ?></h2>
        <form method="POST" action="<?= url('academics', 'subject-save') ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <?= csrf_field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required placeholder="e.g. Mathematics"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Code <span class="text-red-500">*</span></label>
                <input type="text" name="code" value="<?= e($editing['code'] ?? '') ?>" required placeholder="e.g. MATH"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="theory" <?= ($editing['type'] ?? '') === 'theory' ? 'selected' : '' ?>>Theory</option>
                    <option value="practical" <?= ($editing['type'] ?? '') === 'practical' ? 'selected' : '' ?>>Practical</option>
                    <option value="both" <?= ($editing['type'] ?? '') === 'both' ? 'selected' : '' ?>>Both</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                <input type="text" name="description" value="<?= e($editing['description'] ?? '') ?>" placeholder="Optional"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                    <?= $editing ? 'Update' : 'Add' ?>
                </button>
                <?php if ($editing): ?>
                    <a href="<?= url('academics', 'subjects') ?>" class="px-4 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm hover:bg-gray-50 dark:bg-dark-bg">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <div class="overflow-x-auto"><table class="w-full">
            <thead class="bg-gray-50 dark:bg-dark-bg border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Subject</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Classes</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                <?php if (empty($subjects)): ?>
                    <tr><td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-dark-muted">No subjects found. Add one above.</td></tr>
                <?php endif; ?>
                <?php foreach ($subjects as $s): ?>
                    <tr class="hover:bg-gray-50 dark:bg-dark-bg">
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($s['name']) ?></div>
                            <?php if ($s['description']): ?>
                                <div class="text-xs text-gray-500 dark:text-dark-muted"><?= e($s['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted font-mono"><?= e($s['code']) ?></td>
                        <td class="px-4 py-3">
                            <?php
                            $typeBadge = match($s['type']) {
                                'theory'    => 'bg-blue-100 text-blue-800',
                                'practical' => 'bg-purple-100 text-purple-800',
                                'both'      => 'bg-teal-100 text-teal-800',
                                default     => 'bg-gray-100 dark:bg-dark-card2 text-gray-600 dark:text-dark-muted',
                            };
                            ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $typeBadge ?>">
                                <?= ucfirst($s['type']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted"><?= $s['class_count'] ?></td>
                        <td class="px-4 py-3">
                            <?php if ($s['is_active']): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-dark-card2 text-gray-600 dark:text-dark-muted">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?= url('academics', 'subjects') ?>&edit=<?= $s['id'] ?>" class="p-2 text-gray-400 dark:text-gray-500 hover:text-yellow-600 rounded inline-block" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
