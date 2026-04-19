<?php
/**
 * Portal — Enhanced Messages View (student & parent)
 * Features: Threaded conversations, real-time polling, reply inline, notifications
 */

$userId  = portal_user_id();
$role    = portal_role();
$page    = max(1, (int) ($_GET['page'] ?? 1));
$limit   = 20;
$offset  = ($page - 1) * $limit;
$compose = isset($_GET['compose']);
$viewId  = isset($_GET['view']) ? (int) $_GET['view'] : null;
$replyTo = isset($_GET['reply']) ? (int) $_GET['reply'] : null;

// Group messages into threads by (sender,receiver pair + subject)
$messages = db_fetch_all(
    "SELECT m.id, m.subject, m.body, m.is_read, m.created_at,
            m.sender_id, m.receiver_id,
            s.full_name AS sender_name, s.avatar AS sender_avatar,
            r.full_name AS receiver_name, r.avatar AS receiver_avatar
     FROM messages m
     LEFT JOIN users s ON s.id = m.sender_id
     LEFT JOIN users r ON r.id = m.receiver_id
     WHERE m.receiver_id = ? OR m.sender_id = ?
     ORDER BY m.created_at DESC
     LIMIT ? OFFSET ?",
    [$userId, $userId, $limit, $offset]
);

// Mark received unread messages as read when viewing
if ($viewId) {
    db_query("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?", [$viewId, $userId]);
}

$unreadCount = (int) db_fetch_value(
    "SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0",
    [$userId]
);

$totalMessages = (int) db_fetch_value(
    "SELECT COUNT(*) FROM messages WHERE receiver_id = ? OR sender_id = ?",
    [$userId, $userId]
);
$totalPages = (int) ceil($totalMessages / $limit);

// Staff list for compose
$staffList = [];
if ($compose || $replyTo) {
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

// Get conversation thread for viewed message
$thread = [];
$viewedMsg = null;
if ($viewId) {
    $viewedMsg = db_fetch_one(
        "SELECT m.*, s.full_name AS sender_name, s.avatar AS sender_avatar,
                r.full_name AS receiver_name, r.avatar AS receiver_avatar
         FROM messages m
         LEFT JOIN users s ON s.id = m.sender_id
         LEFT JOIN users r ON r.id = m.receiver_id
         WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?)",
        [$viewId, $userId, $userId]
    );

    if ($viewedMsg) {
        $otherId = (int)$viewedMsg['sender_id'] === $userId
            ? (int)$viewedMsg['receiver_id']
            : (int)$viewedMsg['sender_id'];

        $thread = db_fetch_all(
            "SELECT m.*, s.full_name AS sender_name, s.avatar AS sender_avatar
             FROM messages m
             LEFT JOIN users s ON s.id = m.sender_id
             WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
             ORDER BY m.created_at ASC",
            [$userId, $otherId, $otherId, $userId]
        );

        // Mark all in thread as read
        db_query(
            "UPDATE messages SET is_read = 1 WHERE receiver_id = ?
             AND sender_id = ? AND is_read = 0",
            [$userId, $otherId]
        );
    }
}

$activeNav = 'messages';
portal_head('Messages', $viewId ? portal_url('messages') : portal_url('dashboard'));
?>

<?php if ($viewedMsg): ?>
<!-- ═══ Thread View ═══ -->
<div class="mb-4">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="font-bold text-gray-900 text-lg">
        <?= e($viewedMsg['subject'] ?: '(No Subject)') ?>
      </h2>
      <p class="text-xs text-gray-500">
        Conversation with
        <span class="font-semibold">
          <?= e((int)$viewedMsg['sender_id'] === $userId ? $viewedMsg['receiver_name'] : $viewedMsg['sender_name']) ?>
        </span>
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
      <p class="text-xs font-semibold <?= $isMine ? 'text-primary-200' : 'text-primary-600' ?> mb-1">
        <?= e($msg['sender_name']) ?>
      </p>
      <?php endif; ?>
      <p class="text-sm whitespace-pre-wrap"><?= e($msg['body']) ?></p>
      <p class="text-[10px] mt-1 <?= $isMine ? 'text-primary-200' : 'text-gray-400' ?> text-right">
        <?= e(date('d M, g:i A', strtotime($msg['created_at']))) ?>
        <?php if ($isMine && $msg['is_read']): ?>
          ✓✓
        <?php elseif ($isMine): ?>
          ✓
        <?php endif; ?>
      </p>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Reply form -->
<div class="sticky bottom-16 bg-gray-50 pt-2 pb-2">
  <form method="POST" action="<?= portal_url('messages') ?>" class="flex gap-2">
    <?= csrf_field() ?>
    <input type="hidden" name="receiver_id" value="<?= (int)$viewedMsg['sender_id'] === $userId ? (int)$viewedMsg['receiver_id'] : (int)$viewedMsg['sender_id'] ?>">
    <input type="hidden" name="subject" value="<?= e($viewedMsg['subject'] ?: '(No Subject)') ?>">
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

<!-- Messages list -->
<?php if (empty($messages) && !$compose): ?>
<div class="card text-center py-12 text-gray-400">
  <div class="text-5xl mb-3">💬</div>
  <p class="text-sm font-medium">No messages yet</p>
  <p class="text-xs mt-1">Start a conversation with your teacher or admin</p>
  <a href="<?= portal_url('messages', ['compose' => '1']) ?>" class="btn-primary mt-4 !text-sm !px-5">
    Send First Message
  </a>
</div>
<?php elseif (!$compose): ?>

<?php
  // Group messages into conversations (by other party)
  $conversations = [];
  foreach ($messages as $msg) {
      $isSent = (int)$msg['sender_id'] === $userId;
      $otherId = $isSent ? (int)$msg['receiver_id'] : (int)$msg['sender_id'];
      if (!isset($conversations[$otherId])) {
          $conversations[$otherId] = [
              'other_id' => $otherId,
              'other_name' => $isSent ? $msg['receiver_name'] : $msg['sender_name'],
              'other_avatar' => $isSent ? $msg['receiver_avatar'] : $msg['sender_avatar'],
              'latest_msg' => $msg,
              'is_sent' => $isSent,
              'unread' => 0,
              'message_id' => (int)$msg['id'],
          ];
      }
      if (!$isSent && !$msg['is_read']) {
          $conversations[$otherId]['unread']++;
      }
  }
?>

<div class="space-y-2 mb-5">
  <?php foreach ($conversations as $conv):
    $msg = $conv['latest_msg'];
    $initials = mb_substr($conv['other_name'] ?? '?', 0, 1);
  ?>
  <a href="<?= portal_url('messages', ['view' => $conv['message_id']]) ?>"
     class="card flex items-center gap-3 transition-all hover:shadow-md hover:border-primary-200
            <?= $conv['unread'] > 0 ? 'border-primary-300 bg-primary-50/50' : 'bg-white' ?>">
    <!-- Avatar -->
    <div class="flex-shrink-0 w-11 h-11 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold text-lg">
      <?= e($initials) ?>
    </div>
    <!-- Content -->
    <div class="flex-1 min-w-0">
      <div class="flex items-center justify-between gap-2">
        <span class="text-sm font-bold text-gray-900 truncate"><?= e($conv['other_name'] ?? 'Unknown') ?></span>
        <span class="text-[10px] text-gray-400 flex-shrink-0">
          <?= e(date('d M', strtotime($msg['created_at']))) ?>
        </span>
      </div>
      <?php if ($msg['subject'] && $msg['subject'] !== '(No Subject)'): ?>
      <p class="text-xs font-semibold text-gray-700 truncate"><?= e($msg['subject']) ?></p>
      <?php endif; ?>
      <p class="text-xs text-gray-500 truncate">
        <?= $conv['is_sent'] ? 'You: ' : '' ?><?= e(mb_substr($msg['body'], 0, 60)) ?>
      </p>
    </div>
    <!-- Unread badge -->
    <?php if ($conv['unread'] > 0): ?>
    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-600 text-white flex items-center justify-center text-xs font-bold">
      <?= $conv['unread'] ?>
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
    const badge = document.querySelector('[data-unread-badge]');

    setInterval(async () => {
        try {
            const resp = await fetch('<?= portal_url('messages', ['_check_unread' => '1']) ?>');
            if (!resp.ok) return;
            const text = await resp.text();
            // Simple unread check - the page will show updated count on reload
            if (parseInt(text) > lastCount) {
                // New messages - show notification
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
