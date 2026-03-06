<?php
/**
 * Messaging — Bulk Message (Admin Only)
 * Send message to multiple students or teachers at once
 */

$userId   = auth_user_id();
$errors   = get_validation_errors();
$classes  = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");

// Get teachers for multi-select
$teachers = db_fetch_all("
    SELECT u.id, u.full_name
      FROM users u
      JOIN user_roles ur ON u.id = ur.user_id
      JOIN roles r ON ur.role_id = r.id AND r.slug = 'teacher'
     WHERE u.status = 'active' AND u.deleted_at IS NULL
     ORDER BY u.full_name
");

ob_start();
?>

<div class="max-w-3xl mx-auto space-y-4">
    <div class="flex items-center gap-3">
        <a href="<?= url('messaging', 'inbox') ?>" class="text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Bulk Message</h1>
    </div>

    <form method="POST" action="<?= url('messaging', 'bulk-send') ?>" enctype="multipart/form-data"
          class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6 space-y-5">
        <?= csrf_field() ?>

        <!-- Target Type -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Send To</label>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="target_type" value="students" checked onchange="toggleBulkTarget(this.value)"
                           class="text-primary-600 focus:ring-primary-500">
                    <span class="text-sm">Students</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="target_type" value="teachers" onchange="toggleBulkTarget(this.value)"
                           class="text-primary-600 focus:ring-primary-500">
                    <span class="text-sm">Teachers</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="target_type" value="all" onchange="toggleBulkTarget(this.value)"
                           class="text-primary-600 focus:ring-primary-500">
                    <span class="text-sm">All Users</span>
                </label>
            </div>
        </div>

        <!-- Student Filters -->
        <div id="student-filters" class="space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Class</label>
                    <select name="class_id" id="bulk-class" onchange="ajaxLoadSections(this.value, 'bulk-section', 0, 'All Sections')"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        <option value="">All Classes (everyone)</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Section</label>
                    <select name="section_id" id="bulk-section" disabled
                            class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        <option value="">All Sections</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Teacher Multi-select -->
        <div id="teacher-filters" class="hidden">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Teachers</label>
            <div class="border border-gray-300 dark:border-dark-border rounded-lg max-h-48 overflow-y-auto p-2 space-y-1">
                <label class="flex items-center gap-2 px-2 py-1 hover:bg-gray-50 dark:bg-dark-bg rounded cursor-pointer border-b pb-2 mb-1">
                    <input type="checkbox" id="select-all-teachers" onchange="toggleAllTeachers(this.checked)"
                           class="text-primary-600 rounded focus:ring-primary-500">
                    <span class="text-sm font-medium">Select All</span>
                </label>
                <?php foreach ($teachers as $t): ?>
                <label class="flex items-center gap-2 px-2 py-1 hover:bg-gray-50 dark:bg-dark-bg rounded cursor-pointer">
                    <input type="checkbox" name="teacher_ids[]" value="<?= $t['id'] ?>" class="teacher-cb text-primary-600 rounded focus:ring-primary-500">
                    <span class="text-sm"><?= e($t['full_name']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Subject -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label>
            <input type="text" name="subject" value="<?= old('subject') ?>" required maxlength="255"
                   class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500 <?= !empty($errors['subject']) ? 'border-red-300' : '' ?>"
                   placeholder="Message subject…">
            <?php if (!empty($errors['subject'])): ?>
            <p class="mt-1 text-xs text-red-600"><?= e($errors['subject']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Message Body -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
            <textarea name="body" rows="6" required maxlength="5000"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500 <?= !empty($errors['body']) ? 'border-red-300' : '' ?>"
                      placeholder="Type your broadcast message…"><?= old('body') ?></textarea>
            <?php if (!empty($errors['body'])): ?>
            <p class="mt-1 text-xs text-red-600"><?= e($errors['body']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Attachments -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Attachments <span class="text-gray-400 dark:text-gray-500">(max 5 files, 10 MB each)</span></label>
            <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx" id="bulk-file-input"
                   class="w-full text-sm text-gray-500 dark:text-dark-muted file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
            <div id="bulk-file-preview" class="mt-2 flex flex-wrap gap-2"></div>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="<?= url('messaging', 'inbox') ?>" class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 font-medium">Cancel</a>
            <button type="submit" onclick="return confirm('Send this message to all selected recipients?')"
                    class="px-6 py-2 bg-purple-600 text-white rounded-lg text-sm hover:bg-purple-700 font-medium inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                Send Bulk Message
            </button>
        </div>
    </form>
</div>

<script>
function toggleBulkTarget(val) {
    document.getElementById('student-filters').classList.toggle('hidden', val !== 'students');
    document.getElementById('teacher-filters').classList.toggle('hidden', val !== 'teachers');
}
function toggleAllTeachers(checked) {
    document.querySelectorAll('.teacher-cb').forEach(function(cb) { cb.checked = checked; });
}

// File preview for bulk form
(function() {
    var fileInput = document.getElementById('bulk-file-input');
    var previewBox = document.getElementById('bulk-file-preview');
    if (!fileInput || !previewBox) return;

    function escHtml(t) {
        var d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }

    fileInput.addEventListener('change', function() {
        previewBox.innerHTML = '';
        Array.from(this.files).forEach(function(file) {
            var item = document.createElement('div');
            item.className = 'relative';
            if (file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    item.innerHTML = '<img src="' + e.target.result + '" class="w-20 h-20 object-cover rounded-lg border border-gray-200 dark:border-dark-border">'
                        + '<p class="text-[10px] text-gray-500 dark:text-dark-muted mt-0.5 truncate max-w-[80px]">' + escHtml(file.name) + '</p>';
                };
                reader.readAsDataURL(file);
            } else {
                var ext = file.name.split('.').pop().toUpperCase();
                var size = (file.size / 1024).toFixed(0) + ' KB';
                item.innerHTML = '<div class="w-20 h-20 rounded-lg border border-gray-200 dark:border-dark-border bg-gray-50 dark:bg-dark-bg flex flex-col items-center justify-center">'
                    + '<span class="text-xs font-bold text-gray-400 dark:text-gray-500">' + escHtml(ext) + '</span>'
                    + '<span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">' + size + '</span></div>'
                    + '<p class="text-[10px] text-gray-500 dark:text-dark-muted mt-0.5 truncate max-w-[80px]">' + escHtml(file.name) + '</p>';
            }
            previewBox.appendChild(item);
        });
    });
})();
</script>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
