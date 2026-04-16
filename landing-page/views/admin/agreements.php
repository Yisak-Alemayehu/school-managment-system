<?php $pageTitle = 'Agreements'; $currentPage = 'agreements'; include __DIR__ . '/layout_top.php'; ?>

<!-- Send Agreement Form -->
<div class="bg-white rounded-xl border border-gray-100 p-5 mb-6">
    <h3 class="text-sm font-bold text-gray-900 mb-4">Send Agreement</h3>
    <form method="POST" action="<?= base_url('admin/agreements/send') ?>">
        <?= csrf_field() ?>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">School</label>
                <select name="school_id" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <option value="">Select School</option>
                    <?php foreach ($schoolsList as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                <input type="text" name="title" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20" value="Service Agreement">
            </div>
        </div>
        <div class="mt-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Agreement Content</label>
            <textarea name="content" required rows="6" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20" placeholder="Enter the agreement terms and conditions..."></textarea>
        </div>
        <div class="mt-3 grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Setup Fee (ETB)</label>
                <input type="number" name="setup_fee" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20" step="0.01">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Monthly Fee (ETB)</label>
                <input type="number" name="monthly_fee" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20" step="0.01">
            </div>
        </div>
        <button type="submit" class="mt-4 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">Send Agreement</button>
    </form>
</div>

<!-- Agreements List -->
<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">School</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Title</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Fees</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Sent</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Responded</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($agreements as $agr): ?>
                <tr class="hover:bg-gray-50/50">
                    <td class="px-4 py-3 font-medium"><?= e($agr['school_name']) ?></td>
                    <td class="px-4 py-3"><?= e($agr['title']) ?></td>
                    <td class="px-4 py-3 text-xs">
                        <?php if ($agr['setup_fee']): ?>Setup: <?= format_etb($agr['setup_fee']) ?><br><?php endif; ?>
                        <?php if ($agr['monthly_fee']): ?>Monthly: <?= format_etb($agr['monthly_fee']) ?><?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-<?= $agr['status']==='accepted'?'green':($agr['status']==='rejected'?'red':'yellow') ?>-50 text-<?= $agr['status']==='accepted'?'green':($agr['status']==='rejected'?'red':'yellow') ?>-700"><?= ucfirst($agr['status']) ?></span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500"><?= format_date($agr['sent_at']) ?></td>
                    <td class="px-4 py-3 text-xs text-gray-500"><?= $agr['responded_at'] ? format_date($agr['responded_at']) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($agreements)): ?>
                <tr><td colspan="6" class="text-center py-8 text-gray-500">No agreements yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
