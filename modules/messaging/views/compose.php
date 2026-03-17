<?php
/**
 * Messaging — Compose New Message
 * Solo message to any user in the system
 */

$userId = auth_user_id();
$recipientId = input_int('to');
$recipientUser = null;

if ($recipientId) {
    $recipientUser = db_fetch_one("SELECT id, full_name, username, avatar FROM users WHERE id = ? AND status = 'active' AND deleted_at IS NULL AND id != ?", [$recipientId, $userId]);
}

$errors = get_validation_errors();

ob_start();
?>

<div class="max-w-2xl mx-auto space-y-4">
    <div class="flex items-center gap-3">
        <a href="<?= url('messaging', 'inbox') ?>" class="text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">New Message</h1>
    </div>

    <form method="POST" action="<?= url('messaging', 'send') ?>" enctype="multipart/form-data"
          class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6 space-y-4">
        <?= csrf_field() ?>

        <!-- Recipient -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
            <div class="relative">
                <input type="text" id="recipient-search" autocomplete="off"
                       placeholder="Search by name or username…"
                       value="<?= $recipientUser ? e($recipientUser['full_name']) : old('recipient_name') ?>"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500 <?= !empty($errors['recipient_id']) ? 'border-red-300' : '' ?>">
                <input type="hidden" name="recipient_id" id="recipient-id" value="<?= $recipientUser ? $recipientUser['id'] : old('recipient_id') ?>">
                <div id="recipient-dropdown" class="hidden absolute z-10 w-full mt-1 bg-white dark:bg-dark-card border border-gray-200 dark:border-dark-border rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
            </div>
            <?php if (!empty($errors['recipient_id'])): ?>
            <p class="mt-1 text-xs text-red-600"><?= e($errors['recipient_id']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Subject -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject <span class="text-gray-400 dark:text-gray-500">(optional)</span></label>
            <input type="text" name="subject" value="<?= old('subject') ?>" maxlength="255"
                   class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
        </div>

        <!-- Message Body -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
            <textarea name="body" rows="6" maxlength="5000"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500 <?= !empty($errors['body']) ? 'border-red-300' : '' ?>"
                      placeholder="Type your message… (optional if sending attachment)"><?= old('body') ?></textarea>
            <?php if (!empty($errors['body'])): ?>
            <p class="mt-1 text-xs text-red-600"><?= e($errors['body']) ?></p>
            <?php endif; ?>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500"><span id="char-count">0</span>/5000</p>
        </div>

        <!-- File / Audio Attachments -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Attachments <span class="text-gray-400 dark:text-gray-500">(max 5 files, 10 MB each)</span></label>
            <div class="flex flex-wrap items-start gap-2">
                <label class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 bg-gray-100 dark:bg-dark-card2 border border-gray-200 dark:border-dark-border rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-dark-border">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/></svg>
                    Attach Files
                    <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.webm,.ogg,.mp3,.wav" id="compose-file-input" class="hidden" onchange="showFile(this)">
                </label>
                <button type="button" id="compose-record-btn" class="inline-flex items-center gap-2 px-3 py-2 bg-gray-100 dark:bg-dark-card2 border border-gray-200 dark:border-dark-border rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-dark-border">
                    <span id="compose-record-icon">&#9679;</span>
                    <span id="compose-record-label">Record Audio</span>
                </button>
            </div>
            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Allowed: Images, PDF, Word, Excel, Audio</p>
            <div id="compose-file-preview" class="mt-2 flex flex-wrap gap-2"></div>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="<?= url('messaging', 'inbox') ?>" class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 font-medium">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Send Message
            </button>
        </div>
    </form>
</div>

<script>
(function() {
    var searchInput = document.getElementById('recipient-search');
    var hiddenInput = document.getElementById('recipient-id');
    var dropdown    = document.getElementById('recipient-dropdown');
    var charCount   = document.getElementById('char-count');
    var bodyField   = document.querySelector('textarea[name="body"]');
    var debounceTimer;

    // Character count
    if (bodyField && charCount) {
        charCount.textContent = bodyField.value.length;
        bodyField.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }

    if (!searchInput || !dropdown) return;

    searchInput.addEventListener('input', function() {
        var q = this.value.trim();
        hiddenInput.value = '';
        clearTimeout(debounceTimer);
        if (q.length < 2) { dropdown.classList.add('hidden'); return; }
        debounceTimer = setTimeout(function() {
            fetch('<?= url('messaging', 'api-search-users') ?>&q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var users = Array.isArray(data) ? data : (data.users || []);
                    if (!users.length) {
                        dropdown.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500 dark:text-dark-muted">No users found</div>';
                    } else {
                        dropdown.innerHTML = users.map(function(u) {
                            return '<div class="px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-dark-card2 cursor-pointer flex items-center gap-2" data-id="' + u.id + '" data-name="' + (u.full_name || '').replace(/"/g, '&quot;') + '">'
                                + '<div class="w-7 h-7 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">'
                                + '<span class="text-primary-700 text-xs font-semibold">' + (u.full_name || '?').charAt(0).toUpperCase() + '</span></div>'
                                + '<div><div class="font-medium">' + (u.full_name || 'Unknown') + '</div>'
                                + '<div class="text-xs text-gray-400 dark:text-gray-500">' + (u.role || '') + '</div></div></div>';
                        }).join('');
                    }
                    dropdown.classList.remove('hidden');
                })
                .catch(function() {
                    dropdown.innerHTML = '<div class="px-3 py-2 text-sm text-red-500">Search failed. Try again.</div>';
                    dropdown.classList.remove('hidden');
                });
        }, 300);
    });

    dropdown.addEventListener('click', function(e) {
        var item = e.target.closest('[data-id]');
        if (!item) return;
        searchInput.value = item.dataset.name;
        hiddenInput.value = item.dataset.id;
        dropdown.classList.add('hidden');
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // File & Audio preview + recording
    var fileInput = document.getElementById('compose-file-input');
    var previewBox = document.getElementById('compose-file-preview');
    var recordBtn  = document.getElementById('compose-record-btn');
    var recordIcon = document.getElementById('compose-record-icon');
    var recordLabel= document.getElementById('compose-record-label');

    function updateFilePreview() {
        if (!fileInput || !previewBox) return;
        previewBox.innerHTML = '';
        Array.from(fileInput.files).forEach(function(file) {
            var item = document.createElement('div');
            item.className = 'relative group';
            if (file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    item.innerHTML = '<img src="' + e.target.result + '" class="w-20 h-20 object-cover rounded-lg border border-gray-200 dark:border-dark-border">'
                        + '<p class="text-[10px] text-gray-500 dark:text-dark-muted mt-0.5 truncate max-w-[80px]">' + escHtml(file.name) + '</p>';
                };
                reader.readAsDataURL(file);
            } else if (file.type.startsWith('audio/')) {
                var url = URL.createObjectURL(file);
                item.innerHTML = '<audio controls class="w-40 rounded-lg" src="' + url + '"></audio>'
                    + '<p class="text-[10px] text-gray-500 dark:text-dark-muted mt-1 truncate max-w-[120px]">' + escHtml(file.name) + '</p>';
            } else {
                var ext = file.name.split('.').pop().toUpperCase();
                var size = (file.size / 1024).toFixed(0) + ' KB';
                item.innerHTML = '<div class="w-20 h-20 rounded-lg border border-gray-200 dark:border-dark-border bg-gray-50 dark:bg-dark-bg flex flex-col items-center justify-center">'
                    + '<span class="text-xs font-bold text-gray-400 dark:text-gray-500">' + escHtml(ext) + '</span>'
                    + '<span class="text-[10px] text-gray-400 dark:text-dark-muted mt-1">' + size + '</span></div>'
                    + '<p class="text-[10px] text-gray-500 dark:text-dark-muted mt-0.5 truncate max-w-[80px]">' + escHtml(file.name) + '</p>';
            }
            previewBox.appendChild(item);
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', updateFilePreview);
    }

    // Audio recording
    if (recordBtn) {
        var mediaRecorder = null;
        var audioChunks = [];
        recordBtn.addEventListener('click', function() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                return;
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Audio recording is not supported on this browser.');
                return;
            }

            recordBtn.classList.add('bg-red-100', 'text-red-700');
            recordIcon.textContent = '■';
            recordLabel.textContent = 'Stop Recording';

            navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];
                mediaRecorder.ondataavailable = function(e) {
                    if (e.data && e.data.size) audioChunks.push(e.data);
                };
                mediaRecorder.onstop = function() {
                    stream.getTracks().forEach(t => t.stop());
                    var blob = new Blob(audioChunks, { type: 'audio/webm' });
                    var file = new File([blob], 'recording_' + Date.now() + '.webm', { type: blob.type });
                    addFileToInput(file);
                    updateFilePreview();

                    recordBtn.classList.remove('bg-red-100', 'text-red-700');
                    recordIcon.textContent = '●';
                    recordLabel.textContent = 'Record Audio';
                };
                mediaRecorder.start();
            }).catch(function() {
                alert('Unable to access microphone. Please allow access.');
                recordBtn.classList.remove('bg-red-100', 'text-red-700');
                recordIcon.textContent = '●';
                recordLabel.textContent = 'Record Audio';
            });
        });

        function addFileToInput(file) {
            if (!fileInput) return;
            var dt = new DataTransfer();
            Array.from(fileInput.files).forEach(f => dt.items.add(f));
            dt.items.add(file);
            fileInput.files = dt.files;
        }
    }

    function escHtml(t) {
        var d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }
})();
</script>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
