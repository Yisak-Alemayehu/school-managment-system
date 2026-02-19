<?php
/**
 * Communication — Compose Message
 */
$pageTitle = 'Compose Message';

$replyTo = input_int('reply_to');
$prefill = [];
if ($replyTo) {
    $orig = db_fetch_one("
        SELECT m.*, u.full_name AS sender_name
        FROM messages m JOIN users u ON u.id = m.sender_id
        WHERE m.id = ?
    ", [$replyTo]);
    if ($orig) {
        $prefill = [
            'receiver_id' => $orig['sender_id'],
            'subject'     => (str_starts_with($orig['subject'], 'Re: ') ? '' : 'Re: ') . $orig['subject'],
            'body'        => "\n\n--- Original message from {$orig['sender_name']} ---\n" . $orig['body'],
        ];
    }
}

// Get all active users for recipient list
$users = db_fetch_all("SELECT id, full_name, username FROM users WHERE is_active = 1 AND id != ? ORDER BY full_name", [current_user_id()]);

ob_start();
?>
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Compose Message</h1>
        <a href="<?= url('communication', 'inbox') ?>" class="text-sm text-gray-500 hover:text-gray-700">&larr; Inbox</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border p-6">
        <form method="POST" action="<?= url('communication', 'message-send') ?>">
            <?= csrf_field() ?>

            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To *</label>
                    <select name="receiver_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Select Recipient</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($prefill['receiver_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                <?= e($u['full_name']) ?> (<?= e($u['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                    <input type="text" name="subject" required value="<?= e($prefill['subject'] ?? '') ?>"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                    <textarea name="body" required rows="8"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"><?= e($prefill['body'] ?? '') ?></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="px-6 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">Send Message</button>
                    <a href="<?= url('communication', 'inbox') ?>" class="px-6 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
