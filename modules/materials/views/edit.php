<?php
/**
 * Academic Materials — Edit View
 */

$material = db_fetch_one(
    "SELECT m.*, c.name AS class_name, s.name AS subject_name
     FROM academic_materials m
     JOIN classes c ON c.id = m.class_id
     JOIN subjects s ON s.id = m.subject_id
     WHERE m.id = ? AND m.deleted_at IS NULL",
    [$id]
);
if (!$material) {
    set_flash('error', 'Material not found.');
    redirect(url('materials'));
}

$classes  = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY numeric_name ASC, name ASC");
$subjects = db_fetch_all("SELECT id, name FROM subjects WHERE is_active = 1 ORDER BY name ASC");

ob_start();
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('materials', 'view', $id) ?>" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Edit Material</h1>
    </div>

    <?php if ($msg = get_flash('error')): ?>
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg border border-red-200 text-sm"><?= e($msg) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('materials', 'edit', $id) ?>" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Material Info -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4 pb-2 border-b border-gray-100 dark:border-dark-border">
                Material Information
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="title" name="title"
                           value="<?= e(old('title') ?: $material['title']) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-dark-bg dark:border-dark-border dark:text-dark-text">
                    <?php if ($err = get_validation_error('title')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="class_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Grade Level <span class="text-red-500">*</span>
                    </label>
                    <select id="class_id" name="class_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-dark-bg dark:border-dark-border dark:text-dark-text">
                        <option value="">Select Grade</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?= $cls['id'] ?>"
                                <?= (old('class_id') ?: $material['class_id']) == $cls['id'] ? 'selected' : '' ?>>
                                <?= e($cls['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($err = get_validation_error('class_id')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="subject_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Subject <span class="text-red-500">*</span>
                    </label>
                    <select id="subject_id" name="subject_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-dark-bg dark:border-dark-border dark:text-dark-text">
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?= $sub['id'] ?>"
                                <?= (old('subject_id') ?: $material['subject_id']) == $sub['id'] ? 'selected' : '' ?>>
                                <?= e($sub['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($err = get_validation_error('subject_id')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <div class="sm:col-span-2">
                    <label for="book_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Book Type <span class="text-red-500">*</span>
                    </label>
                    <select id="book_type" name="book_type" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-dark-bg dark:border-dark-border dark:text-dark-text">
                        <option value="">Select Type</option>
                        <?php
                        $curType = old('book_type') ?: $material['book_type'];
                        ?>
                        <option value="teachers_guide" <?= $curType === 'teachers_guide' ? 'selected' : '' ?>>Teacher's Guide</option>
                        <option value="student_book" <?= $curType === 'student_book' ? 'selected' : '' ?>>Student Book</option>
                        <option value="supplementary" <?= $curType === 'supplementary' ? 'selected' : '' ?>>Supplementary Book</option>
                    </select>
                    <?php if ($err = get_validation_error('book_type')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- File Uploads (Optional on edit) -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4 pb-2 border-b border-gray-100 dark:border-dark-border">
                Files <span class="text-xs font-normal text-gray-400">(Leave empty to keep current files)</span>
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Current + New Cover -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cover Page</label>
                    <?php if ($material['cover_image']): ?>
                    <div class="mb-3">
                        <img src="<?= upload_url($material['cover_image']) ?>" alt="Current cover"
                             class="w-32 h-auto rounded-lg border border-gray-200 shadow-sm">
                        <p class="text-xs text-gray-400 mt-1">Current cover</p>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="cover_image" accept="image/png"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm dark:bg-dark-bg dark:border-dark-border">
                    <p class="text-xs text-gray-400 mt-1">PNG only, max 5 MB</p>
                    <?php if ($err = get_validation_error('cover_image')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Current + New PDF -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Material File</label>
                    <div class="mb-3 flex items-center gap-2 p-2 bg-gray-50 rounded-lg dark:bg-dark-bg">
                        <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">Current PDF</p>
                            <p class="text-xs text-gray-400">
                                <?= $material['file_size'] ? round($material['file_size'] / 1048576, 1) . ' MB' : 'Unknown size' ?>
                            </p>
                        </div>
                    </div>
                    <input type="file" name="material_file" accept="application/pdf"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm dark:bg-dark-bg dark:border-dark-border">
                    <p class="text-xs text-gray-400 mt-1">PDF only, max 50 MB</p>
                    <?php if ($err = get_validation_error('material_file')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex gap-2">
            <button type="submit" class="px-6 py-2.5 bg-primary-800 text-white rounded-lg font-medium text-sm hover:bg-primary-900 transition-colors">
                Update Material
            </button>
            <a href="<?= url('materials', 'view', $id) ?>" class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-dark-border dark:text-gray-300">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
?>
