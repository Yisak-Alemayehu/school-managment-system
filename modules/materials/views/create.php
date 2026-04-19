<?php
/**
 * Academic Materials — Create View (Upload Form)
 */

$classes  = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY numeric_name ASC, name ASC");
$subjects = db_fetch_all("SELECT id, name FROM subjects WHERE is_active = 1 ORDER BY name ASC");
$session  = get_active_session();

ob_start();
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('materials') ?>" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Upload Material</h1>
    </div>

    <?php if ($msg = get_flash('error')): ?>
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg border border-red-200 text-sm"><?= e($msg) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('materials', 'create') ?>" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Material Info -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4 pb-2 border-b border-gray-100 dark:border-dark-border">
                Material Information
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Title -->
                <div class="sm:col-span-2">
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="title" name="title" value="<?= e(old('title')) ?>" required
                           placeholder="e.g., Mathematics Grade 5"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-dark-bg dark:border-dark-border dark:text-dark-text">
                    <?php if ($err = get_validation_error('title')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Grade / Class -->
                <div>
                    <label for="class_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Grade Level <span class="text-red-500">*</span>
                    </label>
                    <select id="class_id" name="class_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-dark-bg dark:border-dark-border dark:text-dark-text">
                        <option value="">Select Grade</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?= $cls['id'] ?>" <?= old('class_id') == $cls['id'] ? 'selected' : '' ?>>
                                <?= e($cls['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($err = get_validation_error('class_id')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Subject -->
                <div>
                    <label for="subject_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Subject <span class="text-red-500">*</span>
                    </label>
                    <select id="subject_id" name="subject_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-dark-bg dark:border-dark-border dark:text-dark-text">
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?= $sub['id'] ?>" <?= old('subject_id') == $sub['id'] ? 'selected' : '' ?>>
                                <?= e($sub['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($err = get_validation_error('subject_id')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Book Type -->
                <div class="sm:col-span-2">
                    <label for="book_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Book Type <span class="text-red-500">*</span>
                    </label>
                    <select id="book_type" name="book_type" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-dark-bg dark:border-dark-border dark:text-dark-text">
                        <option value="">Select Type</option>
                        <option value="teachers_guide" <?= old('book_type') === 'teachers_guide' ? 'selected' : '' ?>>Teacher's Guide</option>
                        <option value="student_book" <?= old('book_type') === 'student_book' ? 'selected' : '' ?>>Student Book</option>
                        <option value="supplementary" <?= old('book_type') === 'supplementary' ? 'selected' : '' ?>>Supplementary Book</option>
                    </select>
                    <?php if ($err = get_validation_error('book_type')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- File Uploads -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4 pb-2 border-b border-gray-100 dark:border-dark-border">
                Files
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Cover Image -->
                <div>
                    <label for="cover_image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Cover Page <span class="text-red-500">*</span>
                    </label>
                    <div id="coverDropZone"
                         class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center cursor-pointer hover:border-primary-400 hover:bg-primary-50/50 transition-colors"
                         onclick="document.getElementById('cover_image').click()">
                        <div id="coverPreview" class="hidden mb-3">
                            <img id="coverImg" src="" alt="Preview" class="mx-auto max-h-40 rounded-lg shadow-sm">
                        </div>
                        <div id="coverPlaceholder">
                            <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-sm text-gray-500">Click to upload cover image</p>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">PNG only, max 5 MB</p>
                    </div>
                    <input type="file" id="cover_image" name="cover_image" accept="image/png" required
                           class="hidden" onchange="previewCover(this)">
                    <?php if ($err = get_validation_error('cover_image')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Material PDF -->
                <div>
                    <label for="material_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Material File <span class="text-red-500">*</span>
                    </label>
                    <div id="pdfDropZone"
                         class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center cursor-pointer hover:border-primary-400 hover:bg-primary-50/50 transition-colors"
                         onclick="document.getElementById('material_file').click()">
                        <div id="pdfInfo" class="hidden mb-2">
                            <svg class="w-10 h-10 mx-auto text-red-500 mb-1" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 2l5 5h-5V4zm-2.5 9.5a1.5 1.5 0 01-1.5 1.5H8v2H6.5v-6H9a1.5 1.5 0 011.5 1.5v1zm4-1a1.5 1.5 0 011.5 1.5v2a1.5 1.5 0 01-1.5 1.5H13v-6h1.5a1.5 1.5 0 011 .5zM18 13.5h2v1h-1.5v1H20v1h-2v-3z"/>
                            </svg>
                            <p id="pdfFileName" class="text-sm font-medium text-gray-700 truncate"></p>
                            <p id="pdfFileSize" class="text-xs text-gray-400"></p>
                        </div>
                        <div id="pdfPlaceholder">
                            <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-sm text-gray-500">Click to upload PDF file</p>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">PDF only, max 50 MB</p>
                    </div>
                    <input type="file" id="material_file" name="material_file" accept="application/pdf" required
                           class="hidden" onchange="previewPdf(this)">
                    <?php if ($err = get_validation_error('material_file')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex gap-2">
            <button type="submit" class="px-6 py-2.5 bg-primary-800 text-white rounded-lg font-medium text-sm hover:bg-primary-900 transition-colors">
                Upload Material
            </button>
            <a href="<?= url('materials') ?>" class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-dark-border dark:text-gray-300">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function previewCover(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    if (file.type !== 'image/png') {
        alert('Only PNG images are allowed for cover page.');
        input.value = '';
        return;
    }
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('coverImg').src = e.target.result;
        document.getElementById('coverPreview').classList.remove('hidden');
        document.getElementById('coverPlaceholder').classList.add('hidden');
    };
    reader.readAsDataURL(file);
}

function previewPdf(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    if (file.type !== 'application/pdf') {
        alert('Only PDF files are allowed.');
        input.value = '';
        return;
    }
    document.getElementById('pdfFileName').textContent = file.name;
    document.getElementById('pdfFileSize').textContent = (file.size / 1048576).toFixed(1) + ' MB';
    document.getElementById('pdfInfo').classList.remove('hidden');
    document.getElementById('pdfPlaceholder').classList.add('hidden');
}
</script>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
?>
