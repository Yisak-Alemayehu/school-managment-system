<?php
/**
 * Portal — Messages View (student & parent)
 * Uses msg_* tables (msg_conversations, msg_conversation_participants, msg_messages, msg_message_status)
 */

$userId  = portal_user_id();
$role    = portal_role();
$page    = max(1, (int) ($_GET['page'] ?? 1));
$limit   = 20;
$offset  = ($page - 1) * $limit;
$compose = isset($_GET['compose']);
$convId  = isset($_GET['view']) ? (int) $_GET['view'] : null;

// Unread count
$unreadCount = (int) db_fetch_value(
    "SELECT COUNT(DISTINCT ms.message_id)
       FROM msg_message_status ms
       JOIN msg_messages m ON m.id = ms.message_id
       JOIN msg_conversation_participants cp ON cp.conversation_id = m.conversation_id AND cp.user_id = ms.user_id AND cp.is_deleted = 0
      WHERE ms.user_id = ? AND ms.status != 'read'",
    [$userId]
);

// Staff list for compose
$staffList = [];
if ($compose) {
    $staffList = db_fetch_all(
        "SELECT DISTINCT u.id, u.full_name, r.name AS role_name
         FROM users u
         JOIN user_roles ur ON ur.user_id = u.id
         JOIN roles r ON r.id = ur.role_id
         WHERE r.slug IN ('teacher','admin','staff','super-admin')
           AND u.deleted_at IS NULL AND u.id != ?
         ORDER BY u.full_name ASC",
        [$userId]
    );
}

// ── Conversation thread view ──
$conversation = null;
$thread = [];
$otherUser = null;

if ($convId) {
    // Verify user is a participant
    $participant = db_fetch_one(
        "SELECT 1 FROM msg_conversation_participants WHERE conversation_id = ? AND user_id = ? AND is_deleted = 0",
        [$convId, $userId]
    );

    if ($participant) {
        $conversation = db_fetch_one("SELECT * FROM msg_conversations WHERE id = ?", [$convId]);
    }

    if ($conversation) {
        // Get the other participant for display
        $otherUser = db_fetch_one("
            SELECT u.id, u.full_name, u.avatar
              FROM msg_conversation_participants cp
              JOIN users u ON u.id = cp.user_id
             WHERE cp.conversation_id = ? AND cp.user_id != ?
             LIMIT 1
        ", [$convId, $userId]);

        // Get messages
        $thread = db_fetch_all("
            SELECT m.id, m.body, m.sender_id, m.created_at,
                   u.full_name AS sender_name
              FROM msg_messages m
              JOIN users u ON u.id = m.sender_id
             WHERE m.conversation_id = ?
             ORDER BY m.created_at ASC
        ", [$convId]);

        // Mark all messages as read
        db_query("
            UPDATE msg_message_status
               SET status = 'read', read_at = NOW()
             WHERE user_id = ? AND status != 'read'
               AND message_id IN (SELECT id FROM msg_messages WHERE conversation_id = ?)
        ", [$userId, $convId]);

        db_query("
            UPDATE msg_conversation_participants
               SET last_read_at = NOW()
             WHERE conversation_id = ? AND user_id = ?
        ", [$convId, $userId]);
    }
}

// ── Conversation list (inbox) ──
$conversations = [];
$totalPages = 1;
if (!$convId && !$compose) {
    // Get conversations where user is a participant, ordered by latest message
    $conversations = db_fetch_all("
        SELECT c.id, c.type, c.subject,
               (SELECT m2.body FROM msg_messages m2 WHERE m2.conversation_id = c.id ORDER BY m2.created_at DESC LIMIT 1) AS last_body,
               (SELECT m3.created_at FROM msg_messages m3 WHERE m3.conversation_id = c.id ORDER BY m3.created_at DESC LIMIT 1) AS last_msg_at,
               (SELECT m4.sender_id FROM msg_messages m4 WHERE m4.conversation_id = c.id ORDER BY m4.created_at DESC LIMIT 1) AS last_sender_id,
               (SELECT COUNT(*) FROM msg_message_status ms
                  JOIN msg_messages mm ON mm.id = ms.message_id
                 WHERE mm.conversation_id = c.id AND ms.user_id = ? AND ms.status != 'read') AS unread_count,
               ou.id AS other_user_id, ou.full_name AS other_name, ou.avatar AS other_avatar
          FROM msg_conversations c
          JOIN msg_conversation_participants cp ON cp.conversation_id = c.id AND cp.user_id = ? AND cp.is_deleted = 0
          LEFT JOIN msg_conversation_participants ocp ON ocp.conversation_id = c.id AND ocp.user_id != ?
          LEFT JOIN users ou ON ou.id = ocp.user_id
         WHERE EXISTS (SELECT 1 FROM msg_messages m WHERE m.conversation_id = c.id)
         ORDER BY last_msg_at DESC
         LIMIT ? OFFSET ?
    ", [$userId, $userId, $userId, $limit, $offset]);

    $totalConvs = (int) db_fetch_value("
        SELECT COUNT(*)
          FROM msg_conversations c
          JOIN msg_conversation_participants cp ON cp.conversation_id = c.id AND cp.user_id = ? AND cp.is_deleted = 0
         WHERE EXISTS (SELECT 1 FROM msg_messages m WHERE m.conversation_id = c.id)
    ", [$userId]);
    $totalPages = max(1, (int) ceil($totalConvs / $limit));
}

$activeNav = 'messages';
portal_head('Messages', $convId ? portal_url('messages') : portal_url('dashboard'));
?>

<?php if ($conversation && $otherUser): ?>
<!-- ═══ Thread View ═══ -->
<div class="mb-4">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="font-bold text-gray-900 text-lg">
        <?= e($conversation['subject'] ?: $otherUser['full_name']) ?>
      </h2>
      <p class="text-xs text-gray-500">
        Conversation with <span class="font-semibold"><?= e($otherUser['full_name']) ?></span>
      </p>
    </div>
  </div>
</div>

<div class="space-y-3 mb-5" id="thread-messages">
  <?php foreach ($thread as $msg):
    $isMine = (int)$msg['sender_id'] === $userId;
  ?>
  <div class="flex <?= $isMine ? 'justify-end' : 'justify-start' ?> animate-fade-in">
    <div class="max-w-[85%] <?= $isMine
        ? 'bg-primary-600 text-white rounded-2xl rounded-br-md'
        : 'bg-white border border-gray-200 text-gray-900 rounded-2xl rounded-bl-md' ?> px-4 py-3 shadow-sm">
      <?php if (!$isMine): ?>
      <p class="text-xs font-semibold text-primary-600 mb-1">
        <?= e($msg['sender_name']) ?>
      </p>
      <?php endif; ?>
      <p class="text-sm whitespace-pre-wrap"><?= e($msg['body']) ?></p>
      <p class="text-[10px] mt-1 <?= $isMine ? 'text-primary-200' : 'text-gray-400' ?> text-right">
        <?= e(date('d M, g:i A', strtotime($msg['created_at']))) ?>
      </p>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Reply form -->
<div class="sticky bottom-16 bg-gray-50 pt-2 pb-2">
  <form method="POST" action="<?= portal_url('messages') ?>" class="flex gap-2">
    <?= csrf_field() ?>
    <input type="hidden" name="conversation_id" value="<?= (int)$convId ?>">
    <input type="hidden" name="receiver_id" value="<?= (int)$otherUser['id'] ?>">
    <input type="hidden" name="subject" value="<?= e($conversation['subject'] ?? '') ?>">
    <input type="text" name="body" class="form-input flex-1 !py-3 !rounded-full !pl-5"
           placeholder="Type a reply..." required maxlength="5000" autocomplete="off">
    <button type="submit" class="btn-primary !rounded-full !px-4 !py-3 flex-shrink-0">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
      </svg>
    </button>
  </form>
</div>

<?php else: ?>
<!-- ═══ Inbox View ═══ -->

<!-- Header actions -->
<div class="flex items-center justify-between mb-4">
  <div class="flex items-center gap-2">
    <h2 class="font-bold text-gray-900 text-lg">Messages</h2>
    <?php if ($unreadCount > 0): ?>
    <span class="badge badge-red animate-pulse"><?= $unreadCount ?> new</span>
    <?php endif; ?>
  </div>
  <a href="<?= portal_url('messages', ['compose' => '1']) ?>"
     class="btn-primary !px-4 !py-2.5 !text-sm !rounded-full shadow-md shadow-primary-200">
    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    Compose
  </a>
</div>

<!-- Compose form -->
<?php if ($compose): ?>
<div class="card mb-5 border-primary-200 bg-gradient-to-br from-primary-50 to-white animate-fade-in">
  <p class="font-semibold text-primary-800 mb-3 flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
    </svg>
    New Message
  </p>
  <form method="POST" action="<?= portal_url('messages') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">To</label>
      <select name="receiver_id" class="form-input" required>
        <option value="">— Select recipient —</option>
        <?php foreach ($staffList as $staff): ?>
        <option value="<?= (int)$staff['id'] ?>"><?= e($staff['full_name']) ?> (<?= e($staff['role_name']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Subject</label>
      <input type="text" name="subject" class="form-input"
             placeholder="What's this about?" maxlength="255">
    </div>
    <div class="mb-4">
      <label class="form-label">Message</label>
      <textarea name="body" rows="4" class="form-input resize-none"
                placeholder="Type your message here..." required maxlength="5000"></textarea>
    </div>
    <div class="flex gap-2">
      <button type="submit" class="btn-primary flex-1">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
        </svg>
        Send
      </button>
      <a href="<?= portal_url('messages') ?>" class="btn-secondary px-4">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Conversations list -->
<?php if (empty($conversations) && !$compose): ?>
<div class="card text-center py-12 text-gray-400">
  <div class="text-5xl mb-3">💬</div>
  <p class="text-sm font-medium">No messages yet</p>
  <p class="text-xs mt-1">Start a conversation with your teacher or admin</p>
  <a href="<?= portal_url('messages', ['compose' => '1']) ?>" class="btn-primary mt-4 !text-sm !px-5">
    Send First Message
  </a>
</div>
<?php elseif (!$compose): ?>

<div class="space-y-2 mb-5">
  <?php foreach ($conversations as $conv):
    $initials = mb_substr($conv['other_name'] ?? '?', 0, 1);
    $isSentByMe = (int)($conv['last_sender_id'] ?? 0) === $userId;
  ?>
  <a href="<?= portal_url('messages', ['view' => (int)$conv['id']]) ?>"
     class="card flex items-center gap-3 transition-all hover:shadow-md hover:border-primary-200
            <?= $conv['unread_count'] > 0 ? 'border-primary-300 bg-primary-50/50' : 'bg-white' ?>">
    <!-- Avatar -->
    <div class="flex-shrink-0 w-11 h-11 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold text-lg">
      <?= e($initials) ?>
    </div>
    <!-- Content -->
    <div class="flex-1 min-w-0">
      <div class="flex items-center justify-between gap-2">
        <span class="text-sm font-bold text-gray-900 truncate"><?= e($conv['other_name'] ?? 'Unknown') ?></span>
        <span class="text-[10px] text-gray-400 flex-shrink-0">
          <?= $conv['last_msg_at'] ? e(date('d M', strtotime($conv['last_msg_at']))) : '' ?>
        </span>
      </div>
      <?php if ($conv['subject']): ?>
      <p class="text-xs font-semibold text-gray-700 truncate"><?= e($conv['subject']) ?></p>
      <?php endif; ?>
      <p class="text-xs text-gray-500 truncate">
        <?= $isSentByMe ? 'You: ' : '' ?><?= e(mb_substr($conv['last_body'] ?? '', 0, 60)) ?>
      </p>
    </div>
    <!-- Unread badge -->
    <?php if ($conv['unread_count'] > 0): ?>
    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-600 text-white flex items-center justify-center text-xs font-bold">
      <?= (int)$conv['unread_count'] ?>
    </div>
    <?php else: ?>
    <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="flex items-center justify-between mb-4">
  <?php if ($page > 1): ?>
  <a href="<?= portal_url('messages', ['page' => $page - 1]) ?>" class="btn-secondary !px-4 !py-2 !text-sm !rounded-full">
    ← Newer
  </a>
  <?php else: ?>
  <span></span>
  <?php endif; ?>
  <span class="text-xs text-gray-500"><?= $page ?> / <?= $totalPages ?></span>
  <?php if ($page < $totalPages): ?>
  <a href="<?= portal_url('messages', ['page' => $page + 1]) ?>" class="btn-secondary !px-4 !py-2 !text-sm !rounded-full">
    Older →
  </a>
  <?php else: ?>
  <span></span>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

<style>
@keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
.animate-fade-in { animation: fadeIn 0.25s ease-out; }
</style>

<!-- Real-time polling for new messages -->
<script>
(function() {
    let lastCount = <?= $unreadCount ?>;
    setInterval(async () => {
        try {
            const resp = await fetch('<?= portal_url('messages', ['_check_unread' => '1']) ?>');
            if (!resp.ok) return;
            const text = await resp.text();
            if (parseInt(text) > lastCount) {
                const msgNav = document.querySelector('a[href*="messages"]');
                if (msgNav) {
                    const dot = msgNav.querySelector('.msg-dot');
                    if (!dot) {
                        const d = document.createElement('span');
                        d.className = 'msg-dot absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full';
                        msgNav.style.position = 'relative';
                        msgNav.appendChild(d);
                    }
                }
                lastCount = parseInt(text);
            }
        } catch(e) {}
    }, 15000);
})();
</script>

<?php portal_foot($activeNav); ?>
