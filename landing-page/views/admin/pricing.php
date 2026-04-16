<?php $pageTitle = 'Pricing Packages'; $currentPage = 'pricing'; include __DIR__ . '/layout_top.php'; ?>

<div class="bg-white rounded-xl border border-gray-100 p-5 mb-6">
    <h3 class="text-sm font-bold text-gray-900 mb-4">Add / Edit Package</h3>
    <form method="POST" action="<?= base_url('admin/pricing/save') ?>" id="pricingForm">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="pkgId" value="">
        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                <input type="text" name="name" id="pkgName" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">School Size Range</label>
                <input type="text" name="school_size" id="pkgSize" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="200 - 500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Student Range</label>
                <input type="text" name="student_range" id="pkgRange" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="200-500">
            </div>
        </div>
        <div class="grid sm:grid-cols-4 gap-4 mt-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Setup Fee Min</label>
                <input type="number" name="setup_fee_min" id="pkgSetupMin" step="0.01" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Setup Fee Max</label>
                <input type="number" name="setup_fee_max" id="pkgSetupMax" step="0.01" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Monthly Min</label>
                <input type="number" name="monthly_fee_min" id="pkgMonthMin" step="0.01" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Monthly Max</label>
                <input type="number" name="monthly_fee_max" id="pkgMonthMax" step="0.01" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
        </div>
        <div class="mt-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Features (one per line)</label>
            <textarea name="features_list" id="pkgFeatures" rows="4" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="Student Management&#10;Attendance Tracking&#10;..."></textarea>
        </div>
        <div class="mt-3 flex items-center gap-4">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_popular" id="pkgPopular" value="1" class="rounded border-gray-300 text-primary-600"> Popular</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" id="pkgActive" value="1" checked class="rounded border-gray-300 text-primary-600"> Active</label>
            <input type="number" name="sort_order" id="pkgSort" value="0" class="px-3 py-2 rounded-lg border border-gray-200 text-sm w-20" placeholder="Order">
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700">Save</button>
            <button type="button" onclick="resetPkg()" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 hidden" id="cancelPkg">Cancel</button>
        </div>
    </form>
</div>

<div class="grid sm:grid-cols-3 gap-4">
    <?php foreach ($packages as $p): ?>
    <div class="bg-white rounded-xl border <?= $p['is_popular'] ? 'border-primary-300 ring-2 ring-primary-100' : 'border-gray-100' ?> p-5 relative">
        <?php if ($p['is_popular']): ?><span class="absolute -top-2.5 left-1/2 -translate-x-1/2 bg-primary-600 text-white text-[10px] font-bold px-3 py-0.5 rounded-full">Popular</span><?php endif; ?>
        <h4 class="text-lg font-bold text-gray-900"><?= e($p['name']) ?></h4>
        <p class="text-xs text-gray-500 mt-1"><?= e($p['school_size'] ?? $p['student_range']) ?></p>
        <div class="my-3">
            <span class="text-sm font-bold text-gray-900"><?= format_etb($p['setup_fee_min']) ?> - <?= format_etb($p['setup_fee_max']) ?></span>
            <span class="text-xs text-gray-500 block">Setup Fee</span>
        </div>
        <?php if ($p['monthly_fee_min']): ?>
        <div class="text-xs text-gray-500 mb-3"><?= format_etb($p['monthly_fee_min']) ?> - <?= format_etb($p['monthly_fee_max']) ?>/mo</div>
        <?php endif; ?>
        <?php
        $featuresList = json_decode($p['features_list'] ?? '[]', true);
        if ($featuresList): ?>
        <ul class="text-xs text-gray-600 space-y-1 mb-4">
            <?php foreach (array_slice($featuresList, 0, 5) as $feat): ?>
            <li class="flex items-center gap-1"><svg class="w-3 h-3 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><?= e($feat) ?></li>
            <?php endforeach; ?>
            <?php if (count($featuresList) > 5): ?><li class="text-gray-400">+<?= count($featuresList) - 5 ?> more</li><?php endif; ?>
        </ul>
        <?php endif; ?>
        <div class="flex items-center gap-2 mt-3">
            <button onclick='editPkg(<?= json_encode($p) ?>)' class="text-primary-600 text-xs font-medium hover:underline">Edit</button>
            <button onclick="deleteItem('pricing_packages', <?= $p['id'] ?>)" class="text-red-600 text-xs font-medium hover:underline">Delete</button>
            <span class="text-xs ml-auto <?= $p['is_active']?'text-green-600':'text-gray-400' ?>"><?= $p['is_active'] ? 'Active' : 'Inactive' ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
function editPkg(p) {
    document.getElementById('pkgId').value = p.id;
    document.getElementById('pkgName').value = p.name;
    document.getElementById('pkgSize').value = p.school_size || '';
    document.getElementById('pkgRange').value = p.student_range || '';
    document.getElementById('pkgSetupMin').value = p.setup_fee_min;
    document.getElementById('pkgSetupMax').value = p.setup_fee_max;
    document.getElementById('pkgMonthMin').value = p.monthly_fee_min;
    document.getElementById('pkgMonthMax').value = p.monthly_fee_max;
    document.getElementById('pkgSort').value = p.sort_order;
    document.getElementById('pkgPopular').checked = p.is_popular == 1;
    document.getElementById('pkgActive').checked = p.is_active == 1;
    var features = JSON.parse(p.features_list || '[]');
    document.getElementById('pkgFeatures').value = features.join('\n');
    document.getElementById('cancelPkg').classList.remove('hidden');
}
function resetPkg() { document.getElementById('pricingForm').reset(); document.getElementById('pkgId').value=''; document.getElementById('cancelPkg').classList.add('hidden'); }
function deleteItem(type, id) {
    if (!confirm('Delete this item?')) return;
    const fd = new FormData(); fd.append('type', type); fd.append('id', id); fd.append('csrf_token', '<?= e(Auth::generateCsrfToken()) ?>');
    fetch('<?= base_url('admin/delete') ?>', { method:'POST', body:fd }).then(r=>r.json()).then(d=>{if(d.success) location.reload();});
}
</script>

<?php include __DIR__ . '/layout_bottom.php'; ?>
