<?php $pageTitle = 'Payments'; $currentPage = 'payments'; include __DIR__ . '/layout_top.php'; ?>

<!-- Create Payment -->
<div class="bg-white rounded-xl border border-gray-100 p-5 mb-6">
    <h3 class="text-sm font-bold text-gray-900 mb-4">Create Payment</h3>
    <form method="POST" action="<?= base_url('admin/payments/create') ?>">
        <?= csrf_field() ?>
        <div class="grid sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">School</label>
                <select name="school_id" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white">
                    <option value="">Select School</option>
                    <?php foreach ($schoolsList as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Amount (ETB)</label>
                <input type="number" name="amount" required step="0.01" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                <select name="payment_type" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white">
                    <option value="setup">Setup Fee</option>
                    <option value="monthly">Monthly</option>
                    <option value="installment_1">Installment 1</option>
                    <option value="installment_2">Installment 2</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Due Date</label>
                <input type="date" name="due_date" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
        </div>
        <button type="submit" class="mt-4 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">Create Payment</button>
    </form>
</div>

<!-- Revenue Summary -->
<div class="grid sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="text-xs text-gray-500">Total Revenue</div>
        <div class="text-lg font-bold text-green-700"><?= format_etb($totalRevenue ?? 0) ?></div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="text-xs text-gray-500">Pending</div>
        <div class="text-lg font-bold text-yellow-700"><?= format_etb($pendingRevenue ?? 0) ?></div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="text-xs text-gray-500">Awaiting Verification</div>
        <div class="text-lg font-bold text-blue-700"><?= $awaitingCount ?? 0 ?> payments</div>
    </div>
</div>

<!-- Payments Table -->
<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">School</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Amount</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Type</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Due Date</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Receipt</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($payments as $pay): ?>
                <tr class="hover:bg-gray-50/50">
                    <td class="px-4 py-3 font-medium"><?= e($pay['school_name']) ?></td>
                    <td class="px-4 py-3"><?= format_etb($pay['amount']) ?></td>
                    <td class="px-4 py-3 text-xs"><?= ucfirst(str_replace('_', ' ', $pay['payment_type'])) ?></td>
                    <td class="px-4 py-3 text-xs text-gray-500"><?= format_date($pay['due_date']) ?></td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-<?= $pay['status']==='verified'?'green':($pay['status']==='paid'?'blue':($pay['status']==='overdue'?'red':'yellow')) ?>-50 text-<?= $pay['status']==='verified'?'green':($pay['status']==='paid'?'blue':($pay['status']==='overdue'?'red':'yellow')) ?>-700"><?= ucfirst($pay['status']) ?></span>
                    </td>
                    <td class="px-4 py-3">
                        <?php if ($pay['receipt_path']): ?>
                        <a href="<?= base_url('uploads/' . $pay['receipt_path']) ?>" target="_blank" class="text-primary-600 text-xs hover:underline">View</a>
                        <?php else: ?>
                        <span class="text-xs text-gray-400">None</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <?php if ($pay['status'] === 'paid'): ?>
                        <button onclick="verifyPayment(<?= $pay['id'] ?>, 'verify')" class="text-xs bg-green-600 text-white px-2 py-1 rounded-lg hover:bg-green-700 mr-1">Verify</button>
                        <button onclick="verifyPayment(<?= $pay['id'] ?>, 'reject')" class="text-xs bg-red-600 text-white px-2 py-1 rounded-lg hover:bg-red-700">Reject</button>
                        <?php else: ?>
                        <span class="text-xs text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?>
                <tr><td colspan="7" class="text-center py-8 text-gray-500">No payments yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function verifyPayment(id, action) {
    if (!confirm(action === 'verify' ? 'Verify this payment?' : 'Reject this payment?')) return;
    const fd = new FormData(); fd.append('payment_id', id); fd.append('action', action); fd.append('csrf_token', '<?= e(Auth::generateCsrfToken()) ?>');
    fetch('<?= base_url('admin/payments/verify') ?>', { method:'POST', body:fd }).then(r=>r.json()).then(d=>{if(d.success) location.reload();});
}
</script>

<?php include __DIR__ . '/layout_bottom.php'; ?>
