<?php $pageTitle = 'Demos'; $currentPage = 'demos'; include __DIR__ . '/layout_top.php'; ?>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <!-- Create Demo Slot -->
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h3 class="text-sm font-bold text-gray-900 mb-4">Create Demo Slot</h3>
        <form method="POST" action="<?= base_url('admin/demos/create-slot') ?>">
            <?= csrf_field() ?>
            <div class="space-y-3">
                <input type="date" name="date" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" min="<?= date('Y-m-d') ?>">
                <div class="grid grid-cols-2 gap-2">
                    <input type="time" name="time_start" required class="px-3 py-2 rounded-lg border border-gray-200 text-sm" value="09:00">
                    <input type="time" name="time_end" required class="px-3 py-2 rounded-lg border border-gray-200 text-sm" value="10:00">
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">Add Slot</button>
            </div>
        </form>
    </div>

    <!-- Available Slots -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 p-5">
        <h3 class="text-sm font-bold text-gray-900 mb-4">Available Slots</h3>
        <div class="grid sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto">
            <?php foreach ($slots as $slot): ?>
            <div class="flex items-center justify-between p-2 rounded-lg bg-<?= $slot['is_available'] ? 'green' : 'gray' ?>-50 text-sm">
                <span><?= format_date($slot['date']) ?> <?= e($slot['time_start']) ?>-<?= e($slot['time_end']) ?></span>
                <span class="text-xs font-semibold <?= $slot['is_available'] ? 'text-green-700' : 'text-gray-500' ?>"><?= $slot['is_available'] ? 'Open' : 'Booked' ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (empty($slots)): ?>
            <p class="text-sm text-gray-500 col-span-2">No slots created.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Demo Requests -->
<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="text-sm font-bold text-gray-900">Demo Requests</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">School</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Scheduled</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Notes</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($demos as $demo): ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-4 py-3 font-medium"><?= e($demo['school_name']) ?></td>
                    <td class="px-4 py-3"><?= $demo['scheduled_at'] ? format_datetime($demo['scheduled_at']) : '<span class="text-gray-400">Not scheduled</span>' ?></td>
                    <td class="px-4 py-3">
                        <select onchange="updateDemoStatus(<?= $demo['id'] ?>, this.value)" class="text-xs font-semibold px-2 py-1 rounded-full bg-<?= $demo['status']==='completed'?'green':($demo['status']==='scheduled'?'blue':'yellow') ?>-50 text-<?= $demo['status']==='completed'?'green':($demo['status']==='scheduled'?'blue':'yellow') ?>-700 border-0 cursor-pointer">
                            <?php foreach (['pending','scheduled','completed','cancelled','no_show'] as $s): ?>
                            <option value="<?= $s ?>" <?= $demo['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="px-4 py-3 text-gray-500 max-w-xs truncate"><?= e($demo['notes'] ?? '-') ?></td>
                    <td class="px-4 py-3">
                        <a href="<?= base_url('admin/schools/' . $demo['school_id']) ?>" class="text-primary-600 hover:text-primary-700 text-xs font-medium">View School &rarr;</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($demos)): ?>
                <tr><td colspan="5" class="text-center py-8 text-gray-500">No demo requests yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function updateDemoStatus(id, status) {
    const fd = new FormData(); fd.append('demo_id', id); fd.append('status', status); fd.append('csrf_token', '<?= e(Auth::generateCsrfToken()) ?>');
    fetch('<?= base_url('admin/demos/update-status') ?>', { method:'POST', body:fd }).then(r=>r.json()).then(d=>{if(d.success) location.reload();});
}
</script>

<?php include __DIR__ . '/layout_bottom.php'; ?>
