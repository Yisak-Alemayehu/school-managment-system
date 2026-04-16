<?php $pageTitle = 'Contact Submissions'; $currentPage = 'submissions'; include __DIR__ . '/layout_top.php'; ?>

<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Name</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Contact</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">School</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Message</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Date</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($submissions as $sub): ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-4 py-3 font-medium"><?= e($sub['name']) ?></td>
                    <td class="px-4 py-3">
                        <div class="text-gray-900"><?= e($sub['email']) ?></div>
                        <div class="text-xs text-gray-500"><?= e($sub['phone'] ?? '') ?></div>
                    </td>
                    <td class="px-4 py-3"><?= e($sub['school_name'] ?? '-') ?></td>
                    <td class="px-4 py-3 max-w-xs">
                        <div class="text-gray-700 truncate"><?= e($sub['message']) ?></div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-<?= $sub['status']==='contacted'?'green':($sub['status']==='read'?'blue':'yellow') ?>-50 text-<?= $sub['status']==='contacted'?'green':($sub['status']==='read'?'blue':'yellow') ?>-700"><?= ucfirst($sub['status']) ?></span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500"><?= time_ago($sub['created_at']) ?></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1">
                            <?php foreach (['new'=>'New','read'=>'Read','contacted'=>'Contacted'] as $sv => $sl): ?>
                            <button onclick="updateStatus(<?= $sub['id'] ?>, '<?= $sv ?>')" class="text-[10px] px-2 py-1 rounded <?= $sub['status'] === $sv ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>"><?= $sl ?></button>
                            <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($submissions)): ?>
                <tr><td colspan="7" class="text-center py-8 text-gray-500">No submissions yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function updateStatus(id, status) {
    const fd = new FormData(); fd.append('submission_id', id); fd.append('status', status); fd.append('csrf_token', '<?= e(Auth::generateCsrfToken()) ?>');
    fetch('<?= base_url('admin/submissions/update') ?>', { method:'POST', body:fd }).then(r=>r.json()).then(d=>{if(d.success) location.reload();});
}
</script>

<?php include __DIR__ . '/layout_bottom.php'; ?>
