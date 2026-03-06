<?php
/**
 * Exams — Grade Scale View
 */

$scales = db_fetch_all("SELECT * FROM grade_scales ORDER BY is_default DESC, name ASC");

$editScaleId = input_int('scale_id') ?: ($scales[0]['id'] ?? 0);

$entries = [];
if ($editScaleId) {
    $entries = db_fetch_all("SELECT * FROM grade_scale_entries WHERE grade_scale_id = ? ORDER BY min_mark DESC", [$editScaleId]);
}

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text mb-6">Grade Scale</h1>

    <!-- Scale selector -->
    <div class="flex items-center gap-3 mb-4">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Select Scale:</label>
        <select onchange="window.location='<?= url('exams', 'grade-scale') ?>&scale_id='+this.value" class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
            <?php foreach ($scales as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $editScaleId == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?> <?= $s['is_default'] ? '(Default)' : '' ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if ($editScaleId): ?>
    <form method="POST" action="<?= url('exams', 'grade-scale-save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="grade_scale_id" value="<?= $editScaleId ?>">

        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
            <div class="overflow-x-auto"><table class="w-full">
                <thead class="bg-gray-50 dark:bg-dark-bg border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Grade</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Min %</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Max %</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Grade Point</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-dark-muted uppercase">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php foreach ($entries as $i => $e): ?>
                        <tr class="hover:bg-gray-50 dark:bg-dark-bg">
                            <td class="px-4 py-2">
                                <input type="hidden" name="entries[<?= $i ?>][id]" value="<?= $e['id'] ?>">
                                <input type="text" name="entries[<?= $i ?>][grade]" value="<?= e($e['grade']) ?>" required
                                       class="w-20 px-2 py-1.5 border border-gray-300 dark:border-dark-border rounded text-sm text-center font-bold">
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" name="entries[<?= $i ?>][min_mark]" value="<?= $e['min_mark'] ?>" min="0" max="100" step="0.1" required
                                       class="w-20 px-2 py-1.5 border border-gray-300 dark:border-dark-border rounded text-sm text-center">
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" name="entries[<?= $i ?>][max_mark]" value="<?= $e['max_mark'] ?>" min="0" max="100" step="0.1" required
                                       class="w-20 px-2 py-1.5 border border-gray-300 dark:border-dark-border rounded text-sm text-center">
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" name="entries[<?= $i ?>][grade_point]" value="<?= $e['grade_point'] ?>" min="0" max="4" step="0.1" required
                                       class="w-20 px-2 py-1.5 border border-gray-300 dark:border-dark-border rounded text-sm text-center">
                            </td>
                            <td class="px-4 py-2">
                                <input type="text" name="entries[<?= $i ?>][description]" value="<?= e($e['description'] ?? '') ?>"
                                       class="w-full px-2 py-1.5 border border-gray-300 dark:border-dark-border rounded text-sm" placeholder="e.g. Excellent">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <!-- New row -->
                    <?php $n = count($entries); ?>
                    <tr class="bg-green-50">
                        <td class="px-4 py-2">
                            <input type="text" name="entries[<?= $n ?>][grade]" placeholder="New"
                                   class="w-20 px-2 py-1.5 border border-gray-300 dark:border-dark-border rounded text-sm text-center">
                        </td>
                        <td class="px-4 py-2">
                            <input type="number" name="entries[<?= $n ?>][min_mark]" min="0" max="100" step="0.1"
                                   class="w-20 px-2 py-1.5 border border-gray-300 dark:border-dark-border rounded text-sm text-center" placeholder="0">
                        </td>
                        <td class="px-4 py-2">
                            <input type="number" name="entries[<?= $n ?>][max_mark]" min="0" max="100" step="0.1"
                                   class="w-20 px-2 py-1.5 border border-gray-300 dark:border-dark-border rounded text-sm text-center" placeholder="100">
                        </td>
                        <td class="px-4 py-2">
                            <input type="number" name="entries[<?= $n ?>][grade_point]" min="0" max="4" step="0.1"
                                   class="w-20 px-2 py-1.5 border border-gray-300 dark:border-dark-border rounded text-sm text-center" placeholder="0">
                        </td>
                        <td class="px-4 py-2">
                            <input type="text" name="entries[<?= $n ?>][description]"
                                   class="w-full px-2 py-1.5 border border-gray-300 dark:border-dark-border rounded text-sm" placeholder="Description">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div></div>

        <div class="mt-4 flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                Save Grade Scale
            </button>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
