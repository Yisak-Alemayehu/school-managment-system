<?php $pageTitle = 'Payments'; $currentPage = 'payments'; include __DIR__ . '/layout_top.php'; ?>

<?php if (!empty($payments)): ?>
<!-- Payment Summary -->
<div class="grid sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="text-xs text-gray-500">Total Due</div>
        <div class="text-lg font-bold text-gray-900"><?= format_etb($totalDue ?? 0) ?></div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="text-xs text-gray-500">Paid</div>
        <div class="text-lg font-bold text-green-700"><?= format_etb($totalPaid ?? 0) ?></div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="text-xs text-gray-500">Remaining</div>
        <div class="text-lg font-bold text-amber-700"><?= format_etb(($totalDue ?? 0) - ($totalPaid ?? 0)) ?></div>
    </div>
</div>

<!-- Payments List -->
<div class="space-y-4">
    <?php foreach ($payments as $pay): ?>
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <div class="text-sm font-bold text-gray-900"><?= format_etb($pay['amount']) ?></div>
                <div class="text-xs text-gray-500 mt-0.5"><?= ucfirst(str_replace('_', ' ', $pay['payment_type'])) ?> · Due: <?= format_date($pay['due_date']) ?></div>
            </div>
            <span class="text-xs font-semibold px-3 py-1 rounded-full bg-<?= $pay['status']==='verified'?'green':($pay['status']==='paid'?'blue':($pay['status']==='overdue'?'red':'yellow')) ?>-50 text-<?= $pay['status']==='verified'?'green':($pay['status']==='paid'?'blue':($pay['status']==='overdue'?'red':'yellow')) ?>-700"><?= ucfirst($pay['status']) ?></span>
        </div>

        <?php if ($pay['status'] === 'pending' || $pay['status'] === 'overdue'): ?>
        <!-- Upload Receipt -->
        <form method="POST" action="<?= base_url('customer/payments/submit') ?>" enctype="multipart/form-data" class="mt-4 pt-4 border-t border-gray-100">
            <?= csrf_field() ?>
            <input type="hidden" name="payment_id" value="<?= $pay['id'] ?>">
            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Upload Payment Receipt</label>
                    <input type="file" name="receipt" required accept="image/*,.pdf" class="w-full text-sm text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                </div>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors whitespace-nowrap">Submit Payment</button>
            </div>
        </form>
        <?php elseif ($pay['status'] === 'paid'): ?>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-blue-600 font-medium">Receipt submitted. Awaiting verification...</p>
        </div>
        <?php elseif ($pay['status'] === 'verified'): ?>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-green-600 font-medium">Payment verified on <?= format_date($pay['verified_at'] ?? $pay['paid_at']) ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="bg-white rounded-xl border border-gray-100 p-12 text-center">
    <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <h3 class="text-sm font-bold text-gray-900 mb-1">No Payments Yet</h3>
    <p class="text-sm text-gray-500">Payment records will appear here once created by our team.</p>
</div>
<?php endif; ?>

<?php include __DIR__ . '/layout_bottom.php'; ?>
