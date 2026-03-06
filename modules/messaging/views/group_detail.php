<?php
/**
 * Messaging — Group Detail / Chat (Student Only)
 */

$userId  = auth_user_id();
$groupId = route_id() ?: input_int('id');

if (!$groupId) {
    redirect('messaging', 'groups');
}

// Verify membership
$membership = db_fetch_one("SELECT * FROM msg_group_members WHERE group_id = ? AND user_id = ?", [$groupId, $userId]);
if (!$membership) {
    set_flash('error', 'You are not a member of this group.');
    redirect('messaging', 'groups');
}

$group = db_fetch_one("
    SELECT g.*, u.full_name AS creator_name, c.name AS class_name, sec.name AS section_name
      FROM msg_groups g
      JOIN users u ON g.created_by = u.id
      LEFT JOIN classes c ON g.class_id = c.id
      LEFT JOIN sections sec ON g.section_id = sec.id
     WHERE g.id = ? AND g.is_active = 1
", [$groupId]);

if (!$group) {
    set_flash('error', 'Group not found.');
    redirect('messaging', 'groups');
}

$isGroupAdmin = (bool) $membership['is_admin'];

// Get or create conversation for this group
$convId = db_fetch_value("SELECT id FROM msg_conversations WHERE type = 'group' AND group_id = ?", [$groupId]);
if (!$convId) {
    $convId = db_insert('msg_conversations', [
        'type'       => 'group',
        'subject'    => $group['name'],
        'created_by' => $group['created_by'],
        'group_id'   => $groupId,
    ]);
    // Add all group members as conversation participants
    $allMembers = db_fetch_all("SELECT user_id FROM msg_group_members WHERE group_id = ?", [$groupId]);
    foreach ($allMembers as $m) {
        db_insert('msg_conversation_participants', ['conversation_id' => $convId, 'user_id' => $m['user_id']]);
    }
}

// Get members
$members = db_fetch_all("
    SELECT gm.user_id, gm.is_admin, u.full_name, u.avatar
      FROM msg_group_members gm
      JOIN users u ON gm.user_id = u.id
     WHERE gm.group_id = ?
     ORDER BY gm.is_admin DESC, u.full_name
", [$groupId]);

// Get messages
$messages = db_fetch_all("
    SELECT m.id, m.body, m.sender_id, m.created_at,
           u.full_name AS sender_name
      FROM msg_messages m
      JOIN users u ON m.sender_id = u.id
     WHERE m.conversation_id = ?
     ORDER BY m.created_at ASC
", [$convId]);

// Get attachments
$messageIds = array_column($messages, 'id');
$attachments = [];
if (!empty($messageIds)) {
    $ph = implode(',', array_fill(0, count($messageIds), '?'));
    $allAtt = db_fetch_all("SELECT * FROM msg_attachments WHERE message_id IN ($ph)", $messageIds);
    foreach ($allAtt as $att) {
        $attachments[$att['message_id']][] = $att;
    }
}

// Mark messages as read
db_query("
    UPDATE msg_message_status SET status = 'read', read_at = NOW()
     WHERE user_id = ? AND status != 'read'
       AND message_id IN (SELECT id FROM msg_messages WHERE conversation_id = ?)
", [$userId, $convId]);

// Available classmates to add (not already members)
$availableClassmates = [];
if ($isGroupAdmin) {
    $enrollment = db_fetch_one("
        SELECT e.class_id, e.section_id
          FROM students s JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
         WHERE s.user_id = ? LIMIT 1
    ", [$userId]);

    if ($enrollment) {
        $existingIds = array_column($members, 'user_id');
        $existingPh = implode(',', array_fill(0, count($existingIds), '?'));

        $cmParams = array_merge($existingIds, [$enrollment['class_id']]);
        $sectionFilter = '';
        if ($enrollment['section_id']) {
            $sectionFilter = "AND e.section_id = ?";
            $cmParams[] = $enrollment['section_id'];
        }

        $availableClassmates = db_fetch_all("
            SELECT u.id, u.full_name
              FROM students s
              JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
              JOIN users u ON s.user_id = u.id
             WHERE u.id NOT IN ($existingPh)
               AND e.class_id = ? $sectionFilter
               AND u.status = 'active' AND u.deleted_at IS NULL
             ORDER BY u.full_name
        ", $cmParams);
    }
}

ob_start();
?>

<div class="flex flex-col h-[calc(100vh-8rem)] lg:flex-row gap-4">
    <!-- Chat Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Header -->
        <div class="bg-white dark:bg-dark-card rounded-t-xl border border-gray-200 dark:border-dark-border px-4 py-3 flex items-center gap-3">
            <a href="<?= url('messaging', 'groups') ?>" class="text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:text-gray-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text truncate"><?= e($group['name']) ?></h2>
                <p class="text-xs text-gray-500 dark:text-dark-muted"><?= count($members) ?> members</p>
            </div>
            <button type="button" onclick="document.getElementById('members-panel').classList.toggle('hidden')"
                    class="lg:hidden text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:text-dark-muted p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
        </div>

        <!-- Messages -->
        <div id="group-messages" class="flex-1 overflow-y-auto bg-gray-50 dark:bg-dark-bg border-x border-gray-200 dark:border-dark-border px-4 py-4 space-y-3">
            <?php if (empty($messages)): ?>
            <div class="text-center text-gray-400 dark:text-gray-500 py-8">
                <p class="text-sm">No messages yet. Say hello to your group!</p>
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
                    <?php if (!$isMine): ?>
                    <p class="text-xs text-gray-500 dark:text-dark-muted mb-0.5 ml-1"><?= e($msg['sender_name']) ?></p>
                    <?php endif; ?>
                    <div class="rounded-2xl px-4 py-2 <?= $isMine ? 'bg-green-600 text-white rounded-br-md' : 'bg-white dark:bg-dark-card text-gray-800 dark:text-dark-text border border-gray-200 dark:border-dark-border rounded-bl-md' ?>">
                        <p class="text-sm whitespace-pre-wrap break-words"><?= e($msg['body']) ?></p>
                        <?php if (!empty($attachments[$msg['id']])): ?>
                        <div class="mt-2 space-y-1">
                            <?php foreach ($attachments[$msg['id']] as $att):
                                $isImage = str_starts_with($att['mime_type'], 'image/');
                            ?>
                            <?php if ($isImage): ?>
                            <a href="<?= upload_url($att['file_path']) ?>" onclick="openLightbox(this.href,'<?= e(addslashes($att['file_name'])) ?>');return false;" class="block cursor-pointer">
                                <img src="<?= upload_url($att['file_path']) ?>" alt="<?= e($att['file_name']) ?>"
                                     class="max-w-[280px] max-h-[200px] rounded-lg object-cover border <?= $isMine ? 'border-green-400' : 'border-gray-200 dark:border-dark-border' ?>" loading="lazy">
                            </a>
                            <?php else: ?>
                            <a href="<?= upload_url($att['file_path']) ?>" target="_blank"
                               class="flex items-center gap-1.5 text-xs <?= $isMine ? 'text-green-100 hover:text-white' : 'text-green-600 hover:text-green-800' ?>">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <?= e($att['file_name']) ?>
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
        <div class="bg-white dark:bg-dark-card rounded-b-xl border border-gray-200 dark:border-dark-border border-t-0 p-3">
            <!-- File preview area -->
            <div id="group-reply-preview" class="flex flex-wrap gap-2 mb-2 empty:mb-0"></div>
            <form method="POST" action="<?= url('messaging', 'group-send') ?>" enctype="multipart/form-data" class="flex items-end gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="group_id" value="<?= $groupId ?>">
                <input type="hidden" name="conversation_id" value="<?= $convId ?>">

                <label class="cursor-pointer text-gray-400 dark:text-gray-500 hover:text-green-600 flex-shrink-0 pb-2" title="Attach file">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    <input type="file" name="attachments[]" multiple class="hidden" id="group-reply-file-input" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx">
                </label>

                <textarea name="body" rows="1" required maxlength="5000" placeholder="Type a message…"
                          class="flex-1 px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-green-500 resize-none"
                          onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit();}"
                          oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,120)+'px';"></textarea>

                <button type="submit" class="flex-shrink-0 p-2 bg-green-600 text-white rounded-lg hover:bg-green-700" title="Send">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Members Panel -->
    <div id="members-panel" class="hidden lg:block w-full lg:w-72 flex-shrink-0">
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
            <div class="px-4 py-3 border-b bg-gray-50 dark:bg-dark-bg">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-dark-text">Members (<?= count($members) ?>)</h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-dark-border max-h-64 overflow-y-auto">
                <?php foreach ($members as $m): ?>
                <div class="flex items-center gap-2 px-4 py-2">
                    <div class="w-7 h-7 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                        <span class="text-green-700 text-xs font-semibold"><?= strtoupper(mb_substr($m['full_name'], 0, 1)) ?></span>
                    </div>
                    <span class="text-sm text-gray-800 dark:text-dark-text flex-1 truncate"><?= e($m['full_name']) ?></span>
                    <?php if ($m['is_admin']): ?>
                    <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded">Admin</span>
                    <?php endif; ?>
                    <?php if ($isGroupAdmin && !$m['is_admin'] && $m['user_id'] != $userId): ?>
                    <form method="POST" action="<?= url('messaging', 'group-remove-member') ?>" onsubmit="return confirm('Remove this member?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="group_id" value="<?= $groupId ?>">
                        <input type="hidden" name="user_id" value="<?= $m['user_id'] ?>">
                        <button type="submit" class="text-red-400 hover:text-red-600" title="Remove">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($isGroupAdmin && !empty($availableClassmates) && count($members) < $group['max_members']): ?>
            <div class="px-4 py-3 border-t bg-gray-50 dark:bg-dark-bg">
                <form method="POST" action="<?= url('messaging', 'group-add-member') ?>" class="flex gap-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <select name="user_id" required class="flex-1 px-2 py-1.5 border border-gray-300 dark:border-dark-border dark:bg-dark-card dark:text-dark-text rounded-lg text-xs focus:ring-2 focus:ring-green-500">
                        <option value="">Add member…</option>
                        <?php foreach ($availableClassmates as $cm): ?>
                        <option value="<?= $cm['id'] ?>"><?= e($cm['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs hover:bg-green-700 font-medium">Add</button>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($isGroupAdmin): ?>
            <div class="px-4 py-3 border-t space-y-2">
                <form method="POST" action="<?= url('messaging', 'group-edit') ?>" class="space-y-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <input type="text" name="name" value="<?= e($group['name']) ?>" required maxlength="100"
                           class="w-full px-2 py-1.5 border border-gray-300 dark:border-dark-border rounded-lg text-xs focus:ring-2 focus:ring-green-500"
                           placeholder="Group name">
                    <button type="submit" class="w-full px-3 py-1.5 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-xs hover:bg-gray-200 font-medium">Rename Group</button>
                </form>
                <form method="POST" action="<?= url('messaging', 'group-delete') ?>" onsubmit="return confirm('Delete this group permanently?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <button type="submit" class="w-full px-3 py-1.5 bg-red-50 text-red-600 rounded-lg text-xs hover:bg-red-100 font-medium">Delete Group</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    var container = document.getElementById('group-messages');
    if (container) container.scrollTop = container.scrollHeight;

    // Auto-refresh: poll for new messages every 5 seconds
    var convId = <?= (int)$convId ?>;
    var lastMsgId = <?= !empty($messages) ? (int)end($messages)['id'] : 0 ?>;
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function escHtml(str) {
        var d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

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
                    if (!msg.is_mine) {
                        senderHtml = '<p class="text-xs text-gray-500 dark:text-dark-muted mb-0.5 ml-1">' + escHtml(msg.sender_name) + '</p>';
                    }
                    var attHtml = '';
                    if (msg.attachments && msg.attachments.length) {
                        attHtml = '<div class="mt-2 space-y-1">';
                        msg.attachments.forEach(function(a) {
                            if (a.is_image) {
                                var borderCls = msg.is_mine ? 'border-green-400' : 'border-gray-200 dark:border-dark-border';
                                attHtml += '<a href="' + escHtml(a.file_url) + '" onclick="openLightbox(this.href,this.querySelector(\'img\').alt);return false;" class="block cursor-pointer">'
                                    + '<img src="' + escHtml(a.file_url) + '" alt="' + escHtml(a.file_name) + '"'
                                    + ' class="max-w-[280px] max-h-[200px] rounded-lg object-cover border ' + borderCls + '" loading="lazy">'
                                    + '</a>';
                            } else {
                                var cls = msg.is_mine ? 'text-green-100 hover:text-white' : 'text-green-600 hover:text-green-800';
                                attHtml += '<a href="' + escHtml(a.file_url) + '" target="_blank" class="flex items-center gap-1.5 text-xs ' + cls + '">'
                                    + '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>'
                                    + escHtml(a.file_name)
                                    + '</a>';
                            }
                        });
                        attHtml += '</div>';
                    }
                    var bgClass = msg.is_mine ? 'bg-green-600 text-white rounded-br-md' : 'bg-white dark:bg-dark-card text-gray-800 dark:text-dark-text border border-gray-200 dark:border-dark-border rounded-bl-md';
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

    setInterval(pollNewMessages, 5000);

    // File preview for group reply
    var grpFileInput = document.getElementById('group-reply-file-input');
    var grpPreview = document.getElementById('group-reply-preview');
    if (grpFileInput && grpPreview) {
        grpFileInput.addEventListener('change', function() {
            grpPreview.innerHTML = '';
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
                grpPreview.appendChild(item);
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
