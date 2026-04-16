<?php $pageTitle = 'Agreement'; $currentPage = 'agreement'; include __DIR__ . '/layout_top.php'; ?>

<?php if (!empty($agreement)): ?>
<div class="bg-white rounded-xl border border-gray-100 p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-bold text-gray-900"><?= e($agreement['title']) ?></h3>
            <p class="text-xs text-gray-500 mt-1">Sent: <?= format_date($agreement['sent_at']) ?></p>
        </div>
        <span class="text-sm font-semibold px-3 py-1 rounded-full bg-<?= $agreement['status']==='accepted'?'green':($agreement['status']==='rejected'?'red':'yellow') ?>-50 text-<?= $agreement['status']==='accepted'?'green':($agreement['status']==='rejected'?'red':'yellow') ?>-700"><?= ucfirst($agreement['status']) ?></span>
    </div>

    <?php if ($agreement['setup_fee'] || $agreement['monthly_fee']): ?>
    <div class="grid sm:grid-cols-2 gap-4 mb-6">
        <?php if ($agreement['setup_fee']): ?>
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="text-xs text-gray-500">Setup Fee</div>
            <div class="text-lg font-bold text-gray-900"><?= format_etb($agreement['setup_fee']) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($agreement['monthly_fee']): ?>
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="text-xs text-gray-500">Monthly Fee</div>
            <div class="text-lg font-bold text-gray-900"><?= format_etb($agreement['monthly_fee']) ?></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="prose prose-sm max-w-none text-gray-700 border rounded-lg p-5 bg-gray-50/50 mb-6">
        <?= nl2br(e($agreement['content'])) ?>
    </div>

    <?php if ($agreement['status'] === 'sent'): ?>
    <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
        <form method="POST" action="<?= base_url('customer/agreement/respond') ?>" class="flex items-center gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="agreement_id" value="<?= $agreement['id'] ?>">
            <button type="submit" name="response" value="accept" class="px-6 py-2.5 bg-green-600 text-white text-sm font-semibold rounded-xl hover:bg-green-700 transition-colors">
                Accept Agreement
            </button>
            <button type="submit" name="response" value="reject" class="px-6 py-2.5 bg-white text-red-600 border border-red-200 text-sm font-semibold rounded-xl hover:bg-red-50 transition-colors" onclick="return confirm('Are you sure you want to reject this agreement?')">
                Reject
            </button>
        </form>
    </div>
    <?php elseif ($agreement['status'] === 'accepted'): ?>
    <div class="bg-green-50 rounded-lg p-4">
        <p class="text-sm text-green-800 font-medium">You accepted this agreement on <?= format_date($agreement['responded_at']) ?>.</p>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="bg-white rounded-xl border border-gray-100 p-12 text-center">
    <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <h3 class="text-sm font-bold text-gray-900 mb-1">No Agreement Yet</h3>
    <p class="text-sm text-gray-500">Your agreement will appear here once our team prepares it.</p>
</div>
<?php endif; ?>

<?php include __DIR__ . '/layout_bottom.php'; ?>
