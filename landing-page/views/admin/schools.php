<?php $pageTitle = 'Schools'; $currentPage = 'schools'; include __DIR__ . '/layout_top.php'; ?>

<div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <form method="GET" action="<?= base_url('admin/schools') ?>" class="flex items-center gap-2 flex-wrap">
        <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search schools..." class="px-4 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 w-64">
        <select name="stage" class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20">
            <option value="all">All Stages</option>
            <?php foreach (['requested','demo_scheduled','demo_completed','interested','agreement_sent','payment_pending','active','churned'] as $s): ?>
            <option value="<?= $s ?>" <?= ($_GET['stage'] ?? '') === $s ? 'selected' : '' ?>><?= pipeline_stage_info($s)['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 transition-colors">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">School</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Contact</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Package</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Students</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Stage</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Joined</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($schools as $school):
                    $stageInfo = pipeline_stage_info($school['pipeline_stage']);
                ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 text-xs font-bold"><?= strtoupper(substr($school['name'], 0, 1)) ?></div>
                            <div>
                                <div class="font-medium text-gray-900"><?= e($school['name']) ?></div>
                                <div class="text-xs text-gray-500"><?= e($school['school_type'] ?? '') ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-gray-900"><?= e($school['user_name']) ?></div>
                        <div class="text-xs text-gray-500"><?= e($school['user_email']) ?></div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-semibold px-2 py-1 rounded-full bg-primary-50 text-primary-700"><?= e(ucfirst($school['package'] ?? 'N/A')) ?></span>
                    </td>
                    <td class="px-4 py-3 text-gray-600"><?= number_format($school['student_count']) ?></td>
                    <td class="px-4 py-3">
                        <select onchange="updateStage(<?= $school['id'] ?>, this.value)" class="text-xs font-semibold px-2 py-1 rounded-full bg-<?= $stageInfo['color'] ?>-50 text-<?= $stageInfo['color'] ?>-700 border-0 cursor-pointer focus:outline-none">
                            <?php foreach (['requested','demo_scheduled','demo_completed','interested','agreement_sent','payment_pending','active','churned'] as $s): ?>
                            <option value="<?= $s ?>" <?= $school['pipeline_stage'] === $s ? 'selected' : '' ?>><?= pipeline_stage_info($s)['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs"><?= format_date($school['created_at']) ?></td>
                    <td class="px-4 py-3">
                        <a href="<?= base_url('admin/schools/' . $school['id']) ?>" class="text-primary-600 hover:text-primary-700 font-medium text-xs">View &rarr;</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($schools)): ?>
                <tr><td colspan="7" class="text-center py-8 text-gray-500">No schools found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function updateStage(schoolId, stage) {
    const formData = new FormData();
    formData.append('school_id', schoolId);
    formData.append('stage', stage);
    formData.append('csrf_token', '<?= e(Auth::generateCsrfToken()) ?>');
    fetch('<?= base_url('admin/schools/update-stage') ?>', { method: 'POST', body: formData })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); });
}
</script>

<?php include __DIR__ . '/layout_bottom.php'; ?>
