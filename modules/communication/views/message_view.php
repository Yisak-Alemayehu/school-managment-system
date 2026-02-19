<?php
/**
 * Communication — View Message
 */
$id     = input_int('id');
$userId = current_user_id();

$message = db_fetch_one("
    SELECT m.*, 
           sender.full_name AS sender_name,
           receiver.full_name AS receiver_name
    FROM messages m
    JOIN users sender ON sender.id = m.sender_id
    JOIN users receiver ON receiver.id = m.receiver_id
    WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?)
", [$id, $userId, $userId]);

if (!$message) {
    set_flash('error', 'Message not found.');
    redirect(url('communication', 'inbox'));
}

// Mark as read if I'm the receiver
if ($message['receiver_id'] == $userId && !$message['is_read']) {
    db_update('messages', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
}

$pageTitle = $message['subject'];
$isSender  = ($message['sender_id'] == $userId);

ob_start();
?>
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="<?= url('communication', $isSender ? 'sent' : 'inbox') ?>" class="text-sm text-gray-500 hover:text-gray-700">
            &larr; <?= $isSender ? 'Sent' : 'Inbox' ?>
        </a>
        <?php if (!$isSender): ?>
            <a href="<?= url('communication', 'message-compose') ?>&reply_to=<?= $id ?>"
               class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">Reply</a>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl shadow-sm border p-6">
        <h1 class="text-xl font-bold text-gray-900"><?= e($message['subject']) ?></h1>

        <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
            <div>
                <span class="text-gray-400">From:</span>
                <span class="font-medium text-gray-700"><?= e($message['sender_name']) ?></span>
            </div>
            <div>
                <span class="text-gray-400">To:</span>
                <span class="font-medium text-gray-700"><?= e($message['receiver_name']) ?></span>
            </div>
            <div><?= format_datetime($message['created_at']) ?></div>
        </div>

        <hr class="my-4">

        <div class="prose max-w-none text-gray-700 leading-relaxed whitespace-pre-wrap">
<?= e($message['body']) ?>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
