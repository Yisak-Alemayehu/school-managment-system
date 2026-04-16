<?php $pageTitle = 'Features'; $currentPage = 'features'; include __DIR__ . '/layout_top.php'; ?>

<!-- Add Feature -->
<div class="bg-white rounded-xl border border-gray-100 p-5 mb-6">
    <h3 class="text-sm font-bold text-gray-900 mb-4">Add / Edit Feature</h3>
    <form method="POST" action="<?= base_url('admin/features/save') ?>" id="featureForm">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="featureId" value="">
        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                <input type="text" name="title" id="featureTitle" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Icon (SVG path)</label>
                <input type="text" name="icon" id="featureIcon" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="M12 ...">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sort Order</label>
                <input type="number" name="sort_order" id="featureSort" value="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
        </div>
        <div class="mt-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
            <textarea name="description" id="featureDesc" rows="2" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm"></textarea>
        </div>
        <div class="mt-3 flex items-center gap-3">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" id="featureActive" value="1" checked class="rounded border-gray-300 text-primary-600"> Active</label>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700">Save Feature</button>
            <button type="button" onclick="resetForm()" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 hidden" id="cancelBtn">Cancel</button>
        </div>
    </form>
</div>

<!-- Features List -->
<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 bg-gray-50/50">
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Icon</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Title</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Description</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Order</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php foreach ($features as $f): ?>
            <tr class="hover:bg-gray-50/50">
                <td class="px-4 py-3">
                    <div class="w-8 h-8 bg-primary-50 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?= e($f['icon'] ?? 'M5 13l4 4L19 7') ?>"/></svg>
                    </div>
                </td>
                <td class="px-4 py-3 font-medium"><?= e($f['title']) ?></td>
                <td class="px-4 py-3 text-gray-500 max-w-xs truncate"><?= e($f['description']) ?></td>
                <td class="px-4 py-3"><?= $f['sort_order'] ?></td>
                <td class="px-4 py-3"><span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-<?= $f['is_active']?'green':'gray' ?>-50 text-<?= $f['is_active']?'green':'gray' ?>-700"><?= $f['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td class="px-4 py-3">
                    <button onclick='editFeature(<?= json_encode($f) ?>)' class="text-primary-600 text-xs font-medium hover:underline mr-2">Edit</button>
                    <button onclick="deleteItem('features', <?= $f['id'] ?>)" class="text-red-600 text-xs font-medium hover:underline">Delete</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function editFeature(f) {
    document.getElementById('featureId').value = f.id;
    document.getElementById('featureTitle').value = f.title;
    document.getElementById('featureIcon').value = f.icon || '';
    document.getElementById('featureDesc').value = f.description || '';
    document.getElementById('featureSort').value = f.sort_order;
    document.getElementById('featureActive').checked = f.is_active == 1;
    document.getElementById('cancelBtn').classList.remove('hidden');
}
function resetForm() {
    document.getElementById('featureForm').reset();
    document.getElementById('featureId').value = '';
    document.getElementById('cancelBtn').classList.add('hidden');
}
function deleteItem(type, id) {
    if (!confirm('Delete this item?')) return;
    const fd = new FormData(); fd.append('type', type); fd.append('id', id); fd.append('csrf_token', '<?= e(Auth::generateCsrfToken()) ?>');
    fetch('<?= base_url('admin/delete') ?>', { method:'POST', body:fd }).then(r=>r.json()).then(d=>{if(d.success) location.reload();});
}
</script>

<?php include __DIR__ . '/layout_bottom.php'; ?>
