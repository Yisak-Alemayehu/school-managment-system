<?php
/**
 * Messaging — Conversation View
 * Shows full message thread for a conversation
 */

$userId = auth_user_id();
$convId = route_id() ?: input_int('id');

if (!$convId) {
    redirect('messaging', 'inbox');
}

// Verify user is a participant
$participant = db_fetch_one("SELECT * FROM msg_conversation_participants WHERE conversation_id = ? AND user_id = ? AND is_deleted = 0", [$convId, $userId]);
if (!$participant) {
    set_flash('error', 'Conversation not found.');
    redirect('messaging', 'inbox');
}

$conversation = db_fetch_one("SELECT * FROM msg_conversations WHERE id = ?", [$convId]);
if (!$conversation) {
    set_flash('error', 'Conversation not found.');
    redirect('messaging', 'inbox');
}

// Get participants
$participants = db_fetch_all("
    SELECT u.id, u.full_name, u.avatar, u.username
      FROM msg_conversation_participants cp
      JOIN users u ON cp.user_id = u.id
     WHERE cp.conversation_id = ? AND cp.is_deleted = 0
     ORDER BY u.full_name
", [$convId]);

// Determine display name
$displayName = $conversation['subject'] ?: 'Conversation';
if ($conversation['type'] === 'solo') {
    foreach ($participants as $p) {
        if ($p['id'] != $userId) {
            $displayName = $p['full_name'];
            break;
        }
    }
} elseif ($conversation['type'] === 'group' && $conversation['group_id']) {
    $group = db_fetch_one("SELECT name FROM msg_groups WHERE id = ?", [$conversation['group_id']]);
    $displayName = $group ? $group['name'] : 'Group';
}

// Get messages with attachments
$messages = db_fetch_all("
    SELECT m.id, m.body, m.sender_id, m.created_at,
           u.full_name AS sender_name, u.avatar AS sender_avatar
      FROM msg_messages m
      JOIN users u ON m.sender_id = u.id
     WHERE m.conversation_id = ?
     ORDER BY m.created_at ASC
", [$convId]);

// Get attachments for all messages
$messageIds = array_column($messages, 'id');
$attachments = [];
if (!empty($messageIds)) {
    $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
    $allAttachments = db_fetch_all("SELECT * FROM msg_attachments WHERE message_id IN ($placeholders)", $messageIds);
    foreach ($allAttachments as $att) {
        $attachments[$att['message_id']][] = $att;
    }
}

// Mark messages as read
db_query("
    UPDATE msg_message_status
       SET status = 'read', read_at = NOW()
     WHERE user_id = ? AND status != 'read'
       AND message_id IN (SELECT id FROM msg_messages WHERE conversation_id = ?)
", [$userId, $convId]);

// Update last_read_at
db_update('msg_conversation_participants', ['last_read_at' => date('Y-m-d H:i:s')], 'conversation_id = ? AND user_id = ?', [$convId, $userId]);

ob_start();
?>

<div class="flex flex-col h-[calc(100vh-8rem)]">
    <!-- Header -->
    <div class="bg-white dark:bg-dark-card rounded-t-xl border border-gray-200 dark:border-dark-border px-4 py-3 flex items-center gap-3">
        <a href="<?= url('messaging', 'inbox') ?>" class="text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:text-gray-300 lg:hidden">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
            <span class="text-primary-700 font-semibold"><?= strtoupper(mb_substr($displayName, 0, 1)) ?></span>
        </div>
        <div class="flex-1 min-w-0">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text truncate"><?= e($displayName) ?></h2>
            <p class="text-xs text-gray-500 dark:text-dark-muted">
                <?php if ($conversation['type'] === 'solo'): ?>
                    Direct Message
                <?php elseif ($conversation['type'] === 'group'): ?>
                    Group · <?= count($participants) ?> members
                <?php else: ?>
                    Bulk Message · <?= count($participants) ?> recipients
                <?php endif; ?>
            </p>
        </div>
        <!-- Delete conversation -->
        <form method="POST" action="<?= url('messaging', 'delete') ?>" onsubmit="return confirm('Remove this conversation from your inbox?')">
            <?= csrf_field() ?>
            <input type="hidden" name="conversation_id" value="<?= $convId ?>">
            <button type="submit" class="text-gray-400 dark:text-gray-500 hover:text-red-500" title="Delete">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </form>
    </div>

    <!-- Messages Area -->
    <div id="messages-container" class="flex-1 overflow-y-auto bg-gray-50 dark:bg-dark-bg border-x border-gray-200 dark:border-dark-border px-4 py-4 space-y-3">
        <?php if (empty($messages)): ?>
        <div class="text-center text-gray-400 dark:text-gray-500 py-8">
            <p class="text-sm">No messages yet. Start the conversation!</p>
        </div>
        <?php endif; ?>

        <?php
        $lastDate = '';
        foreach ($messages as $msg):
            $msgDate = format_date($msg['created_at'], 'M d, Y');
            $isMine = ($msg['sender_id'] == $userId);
            if ($msgDate !== $lastDate):
                $lastDate = $msgDate;
        ?>
        <div class="flex justify-center my-2">
            <span class="text-xs text-gray-400 dark:text-gray-500 bg-white dark:bg-dark-card px-3 py-1 rounded-full border"><?= $msgDate ?></span>
        </div>
        <?php endif; ?>

        <div class="flex <?= $isMine ? 'justify-end' : 'justify-start' ?>">
            <div class="max-w-[75%]">
                <?php if (!$isMine && $conversation['type'] !== 'solo'): ?>
                <p class="text-xs text-gray-500 dark:text-dark-muted mb-0.5 ml-1"><?= e($msg['sender_name']) ?></p>
                <?php endif; ?>
                <div class="rounded-2xl px-4 py-2 <?= $isMine ? 'bg-primary-600 text-white rounded-br-md' : 'bg-white dark:bg-dark-card text-gray-800 dark:text-dark-text border border-gray-200 dark:border-dark-border rounded-bl-md' ?>">
                    <p class="text-sm whitespace-pre-wrap break-words"><?= e($msg['body']) ?></p>
                    <?php if (!empty($attachments[$msg['id']])): ?>
                    <div class="mt-2 space-y-1">
                        <?php foreach ($attachments[$msg['id']] as $att):
                            $isImage = str_starts_with($att['mime_type'], 'image/');
                        ?>
                        <?php if ($isImage): ?>
                        <a href="<?= upload_url($att['file_path']) ?>" onclick="openLightbox(this.href,'<?= e(addslashes($att['file_name'])) ?>');return false;" class="block cursor-pointer">
                            <img src="<?= upload_url($att['file_path']) ?>" alt="<?= e($att['file_name']) ?>"
                                 class="max-w-[280px] max-h-[200px] rounded-lg object-cover border <?= $isMine ? 'border-primary-400' : 'border-gray-200 dark:border-dark-border' ?>" loading="lazy">
                        </a>
                        <?php else: ?>
                        <a href="<?= upload_url($att['file_path']) ?>" target="_blank"
                           class="flex items-center gap-1.5 text-xs <?= $isMine ? 'text-primary-100 hover:text-white' : 'text-primary-600 hover:text-primary-800' ?>">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <?= e($att['file_name']) ?>
                            <span class="opacity-70">(<?= round($att['file_size'] / 1024) ?>KB)</span>
                        </a>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <p class="text-xs <?= $isMine ? 'text-right' : 'text-left' ?> text-gray-400 dark:text-gray-500 mt-0.5 mx-1">
                    <?= format_datetime($msg['created_at'], 'g:i A') ?>
                </p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Reply Form -->
    <?php if ($conversation['type'] !== 'bulk' || ($GLOBALS['_is_admin'] ?? false)): ?>
    <div class="bg-white dark:bg-dark-card rounded-b-xl border border-gray-200 dark:border-dark-border border-t-0 p-3">
        <!-- File preview area -->
        <div id="reply-file-preview" class="flex flex-wrap gap-2 mb-2 empty:mb-0"></div>
        <form method="POST" action="<?= url('messaging', 'reply') ?>" enctype="multipart/form-data" class="flex items-end gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="conversation_id" value="<?= $convId ?>">

            <label class="cursor-pointer text-gray-400 dark:text-gray-500 hover:text-primary-600 flex-shrink-0 pb-2" title="Attach file">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                <input type="file" name="attachments[]" multiple class="hidden" id="reply-file-input" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx">
            </label>

            <textarea name="body" rows="1" required maxlength="5000" placeholder="Type a message…"
                      class="flex-1 px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500 resize-none"
                      onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit();}"
                      oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,120)+'px';"></textarea>

            <button type="submit" class="flex-shrink-0 p-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700" title="Send">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
// Scroll to bottom on load
(function() {
    var container = document.getElementById('messages-container');
    if (container) container.scrollTop = container.scrollHeight;

    // Auto-refresh: poll for new messages every 5 seconds
    var convId = <?= (int)$convId ?>;
    var lastMsgId = <?= !empty($messages) ? (int)end($messages)['id'] : 0 ?>;
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function pollNewMessages() {
        fetch('<?= url('messaging', 'api-messages') ?>&id=' + convId + '&after=' + lastMsgId)
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(data) {
                if (!data || !data.messages || !data.messages.length) return;
                data.messages.forEach(function(msg) {
                    if (msg.id <= lastMsgId) return;
                    lastMsgId = msg.id;
                    var bubble = document.createElement('div');
                    bubble.className = 'flex ' + (msg.is_mine ? 'justify-end' : 'justify-start');
                    var senderHtml = '';
                    <?php if ($conversation['type'] !== 'solo'): ?>
                    if (!msg.is_mine) {
                        senderHtml = '<p class="text-xs text-gray-500 dark:text-dark-muted mb-0.5 ml-1">' + escHtml(msg.sender_name) + '</p>';
                    }
                    <?php endif; ?>
                    var attHtml = '';
                    if (msg.attachments && msg.attachments.length) {
                        attHtml = '<div class="mt-2 space-y-1">';
                        msg.attachments.forEach(function(a) {
                            if (a.is_image) {
                                var borderCls = msg.is_mine ? 'border-primary-400' : 'border-gray-200 dark:border-dark-border';
                                attHtml += '<a href="' + escHtml(a.file_url) + '" onclick="openLightbox(this.href,this.querySelector(\'img\').alt);return false;" class="block cursor-pointer">'
                                    + '<img src="' + escHtml(a.file_url) + '" alt="' + escHtml(a.file_name) + '"'
                                    + ' class="max-w-[280px] max-h-[200px] rounded-lg object-cover border ' + borderCls + '" loading="lazy">'
                                    + '</a>';
                            } else {
                                var cls = msg.is_mine ? 'text-primary-100 hover:text-white' : 'text-primary-600 hover:text-primary-800';
                                attHtml += '<a href="' + escHtml(a.file_url) + '" target="_blank" class="flex items-center gap-1.5 text-xs ' + cls + '">'
                                    + '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>'
                                    + escHtml(a.file_name)
                                    + '</a>';
                            }
                        });
                        attHtml += '</div>';
                    }
                    var bgClass = msg.is_mine ? 'bg-primary-600 text-white rounded-br-md' : 'bg-white dark:bg-dark-card text-gray-800 dark:text-dark-text border border-gray-200 dark:border-dark-border rounded-bl-md';
                    var timeAlign = msg.is_mine ? 'text-right' : 'text-left';
                    bubble.innerHTML = '<div class="max-w-[75%]">' + senderHtml
                        + '<div class="rounded-2xl px-4 py-2 ' + bgClass + '">'
                        + '<p class="text-sm whitespace-pre-wrap break-words">' + escHtml(msg.body) + '</p>'
                        + attHtml + '</div>'
                        + '<p class="text-xs ' + timeAlign + ' text-gray-400 dark:text-gray-500 mt-0.5 mx-1">' + escHtml(msg.time) + '</p></div>';
                    container.appendChild(bubble);
                });
                container.scrollTop = container.scrollHeight;

                // Mark as read
                if (csrfToken) {
                    fetch('<?= url('messaging', 'api-mark-read') ?>', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'csrf_token=' + encodeURIComponent(csrfToken) + '&conversation_id=' + convId
                    });
                }
            })
            .catch(function() {});
    }

    function escHtml(str) {
        var d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    setInterval(pollNewMessages, 5000);

    // File preview for reply
    var replyFileInput = document.getElementById('reply-file-input');
    var replyPreview = document.getElementById('reply-file-preview');
    if (replyFileInput && replyPreview) {
        replyFileInput.addEventListener('change', function() {
            replyPreview.innerHTML = '';
            Array.from(this.files).forEach(function(file) {
                var item = document.createElement('div');
                item.className = 'relative';
                if (file.type.startsWith('image/')) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        item.innerHTML = '<img src="' + e.target.result + '" class="w-16 h-16 object-cover rounded-lg border border-gray-200 dark:border-dark-border">'
                            + '<p class="text-[10px] text-gray-500 dark:text-dark-muted mt-0.5 truncate max-w-[64px]">' + escHtml(file.name) + '</p>';
                    };
                    reader.readAsDataURL(file);
                } else {
                    var ext = file.name.split('.').pop().toUpperCase();
                    var size = (file.size / 1024).toFixed(0) + ' KB';
                    item.innerHTML = '<div class="w-16 h-16 rounded-lg border border-gray-200 dark:border-dark-border bg-gray-50 dark:bg-dark-bg flex flex-col items-center justify-center">'
                        + '<span class="text-xs font-bold text-gray-400 dark:text-gray-500">' + escHtml(ext) + '</span>'
                        + '<span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">' + size + '</span></div>'
                        + '<p class="text-[10px] text-gray-500 dark:text-dark-muted mt-0.5 truncate max-w-[64px]">' + escHtml(file.name) + '</p>';
                }
                replyPreview.appendChild(item);
            });
        });
    }
})();
</script>

<!-- Image Lightbox Modal -->
<div id="img-lightbox" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80" onclick="if(event.target===this)closeLightbox()">
    <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white hover:text-gray-300 z-10" title="Close">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <div class="relative max-w-[90vw] max-h-[85vh] flex flex-col items-center">
        <img id="lightbox-img" src="" alt="" class="max-w-full max-h-[78vh] object-contain rounded-lg shadow-2xl">
        <div class="mt-3 flex items-center gap-3">
            <span id="lightbox-name" class="text-white text-sm truncate max-w-[50vw]"></span>
            <a id="lightbox-download" href="" download class="inline-flex items-center gap-1.5 px-4 py-2 bg-white dark:bg-dark-card text-gray-800 dark:text-dark-text rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-dark-card2 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download
            </a>
        </div>
    </div>
</div>
<script>
function openLightbox(src, name) {
    var lb = document.getElementById('img-lightbox');
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox-img').alt = name || '';
    document.getElementById('lightbox-name').textContent = name || '';
    document.getElementById('lightbox-download').href = src;
    document.getElementById('lightbox-download').download = name || 'image';
    lb.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    var lb = document.getElementById('img-lightbox');
    lb.classList.add('hidden');
    document.getElementById('lightbox-img').src = '';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
});
</script>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
