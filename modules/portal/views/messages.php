<?php
/**
 * Portal — Messages View (shared: student & parent)
 */

$userId  = portal_user_id();
$role    = portal_role();
$page    = max(1, (int) ($_GET['page'] ?? 1));
$limit   = 20;
$offset  = ($page - 1) * $limit;
$compose = isset($_GET['compose']);

// Fetch messages (sent + received)
$messages = db_fetch_all(
    "SELECT m.id, m.subject, m.body, m.is_read, m.created_at,
            m.sender_id, m.receiver_id,
            s.full_name AS sender_name,
            r.full_name AS receiver_name
     FROM messages m
     LEFT JOIN users s ON s.id = m.sender_id
     LEFT JOIN users r ON r.id = m.receiver_id
     WHERE m.receiver_id = ? OR m.sender_id = ?
     ORDER BY m.created_at DESC
     LIMIT ? OFFSET ?",
    [$userId, $userId, $limit, $offset]
);

// Mark received unread messages as read
$unreadIds = array_map(
    fn($m) => (int) $m['id'],
    array_filter($messages, fn($m) => (int)$m['receiver_id'] === $userId && !$m['is_read'])
);
if (!empty($unreadIds)) {
    $ph = implode(',', array_fill(0, count($unreadIds), '?'));
    db_query("UPDATE messages SET is_read = 1 WHERE id IN ($ph)", $unreadIds);
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

// Staff list for compose (teachers, admin)
$staffList = [];
if ($compose) {
    $staffList = db_fetch_all(
        "SELECT DISTINCT u.id, u.full_name
         FROM users u
         JOIN user_roles ur ON ur.user_id = u.id
         JOIN roles r ON r.id = ur.role_id
         WHERE r.slug IN ('teacher','admin','staff','super-admin')
           AND u.deleted_at IS NULL AND u.id != ?
         ORDER BY u.full_name ASC",
        [$userId]
    );
}

$activeNav = 'messages';
portal_head('Messages', portal_url('dashboard'));
?>

<!-- Header actions -->
<div class="flex items-center justify-between mb-4">
  <div class="flex items-center gap-2">
    <h2 class="font-bold text-gray-900">Inbox</h2>
    <?php if ($unreadCount > 0): ?>
    <span class="badge badge-red"><?= $unreadCount ?> new</span>
    <?php endif; ?>
  </div>
  <a href="<?= portal_url('messages', ['compose' => '1']) ?>"
     class="btn-primary px-3 py-2 text-sm">
    ✉️ Compose
  </a>
</div>

<!-- Compose form -->
<?php if ($compose): ?>
<div class="card mb-5 border-primary-200 bg-primary-50">
  <p class="font-semibold text-primary-800 mb-3">New Message</p>
  <form method="POST" action="<?= portal_url('messages') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">To</label>
      <select name="receiver_id" class="form-input" required>
        <option value="">— Select recipient —</option>
        <?php foreach ($staffList as $staff): ?>
        <option value="<?= (int)$staff['id'] ?>"><?= e($staff['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Subject</label>
      <input type="text" name="subject" class="form-input"
             placeholder="(Optional subject)">
    </div>
    <div class="mb-4">
      <label class="form-label">Message</label>
      <textarea name="body" rows="4" class="form-input resize-none"
                placeholder="Type your message..." required maxlength="5000"></textarea>
    </div>
    <div class="flex gap-2">
      <button type="submit" class="btn-primary flex-1">Send Message</button>
      <a href="<?= portal_url('messages') ?>" class="btn-secondary px-4">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Messages list -->
<?php if (empty($messages)): ?>
<div class="card text-center py-12 text-gray-400">
  <p class="text-4xl mb-3">💬</p>
  <p class="text-sm">No messages yet.</p>
</div>
<?php else: ?>
<div class="space-y-2 mb-5">
  <?php foreach ($messages as $msg):
    $isSent    = (int) $msg['sender_id'] === $userId;
    $isUnread  = !$isSent && !$msg['is_read'];
    $otherName = $isSent ? $msg['receiver_name'] : $msg['sender_name'];
  ?>
  <div class="card <?= $isUnread ? 'border-primary-300 bg-primary-50' : 'bg-white' ?> space-y-1">
    <div class="flex items-start justify-between gap-2">
      <div class="flex items-center gap-2">
        <?php if ($isUnread): ?>
        <span class="w-2 h-2 rounded-full bg-primary-600 flex-shrink-0 mt-1"></span>
        <?php endif; ?>
        <div>
          <span class="text-xs font-semibold text-gray-500">
            <?= $isSent ? '→ To: ' : '← From: ' ?>
          </span>
          <span class="text-sm font-bold text-gray-900"><?= e($otherName ?? 'Unknown') ?></span>
        </div>
      </div>
      <span class="text-xs text-gray-400 flex-shrink-0">
        <?= e(date('d M, g:i A', strtotime($msg['created_at']))) ?>
      </span>
    </div>
    <?php if ($msg['subject'] && $msg['subject'] !== '(No Subject)'): ?>
    <p class="text-sm font-semibold text-gray-800"><?= e($msg['subject']) ?></p>
    <?php endif; ?>
    <p class="text-sm text-gray-600 line-clamp-2"><?= e($msg['body']) ?></p>
    <?php if (strlen($msg['body']) > 120): ?>
    <details class="text-sm text-gray-600">
      <summary class="text-primary-600 cursor-pointer text-xs font-semibold">Read more</summary>
      <p class="mt-1"><?= e($msg['body']) ?></p>
    </details>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="flex items-center justify-between mb-4">
  <?php if ($page > 1): ?>
  <a href="<?= portal_url('messages', ['page' => $page - 1]) ?>" class="btn-secondary px-4 py-2 text-sm">
    ← Previous
  </a>
  <?php else: ?>
  <span></span>
  <?php endif; ?>

  <span class="text-sm text-gray-500">Page <?= $page ?> of <?= $totalPages ?></span>

  <?php if ($page < $totalPages): ?>
  <a href="<?= portal_url('messages', ['page' => $page + 1]) ?>" class="btn-secondary px-4 py-2 text-sm">
    Next →
  </a>
  <?php else: ?>
  <span></span>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php portal_foot($activeNav); ?>
