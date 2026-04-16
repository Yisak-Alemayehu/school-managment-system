<?php $pageTitle = 'FAQs'; $currentPage = 'faqs'; include __DIR__ . '/layout_top.php'; ?>

<div class="bg-white rounded-xl border border-gray-100 p-5 mb-6">
    <h3 class="text-sm font-bold text-gray-900 mb-4">Add / Edit FAQ</h3>
    <form method="POST" action="<?= base_url('admin/faqs/save') ?>" id="faqForm">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="faqId" value="">
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Question</label>
                <input type="text" name="question" id="faqQ" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                    <input type="text" name="category" id="faqCat" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="General">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Order</label>
                    <input type="number" name="sort_order" id="faqSort" value="0" class="px-3 py-2 rounded-lg border border-gray-200 text-sm w-20">
                </div>
            </div>
        </div>
        <div class="mt-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Answer</label>
            <textarea name="answer" id="faqA" required rows="3" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm"></textarea>
        </div>
        <div class="mt-3 flex items-center gap-3">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" id="faqActive" value="1" checked class="rounded border-gray-300 text-primary-600"> Active</label>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700">Save</button>
            <button type="button" onclick="resetFaq()" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 hidden" id="cancelFaq">Cancel</button>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 bg-gray-50/50">
                <th class="text-left px-4 py-3 font-semibold text-gray-600 w-8">#</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Question</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Category</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php foreach ($faqs as $f): ?>
            <tr class="hover:bg-gray-50/50">
                <td class="px-4 py-3 text-gray-400"><?= $f['sort_order'] ?></td>
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-900"><?= e($f['question']) ?></div>
                    <div class="text-xs text-gray-500 mt-0.5 line-clamp-1"><?= e($f['answer']) ?></div>
                </td>
                <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 bg-gray-100 rounded-full"><?= e($f['category'] ?? 'General') ?></span></td>
                <td class="px-4 py-3"><span class="text-xs font-semibold <?= $f['is_active']?'text-green-600':'text-gray-400' ?>"><?= $f['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td class="px-4 py-3">
                    <button onclick='editFaq(<?= json_encode($f) ?>)' class="text-primary-600 text-xs font-medium hover:underline mr-2">Edit</button>
                    <button onclick="deleteItem('faqs', <?= $f['id'] ?>)" class="text-red-600 text-xs font-medium hover:underline">Delete</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function editFaq(f){
    document.getElementById('faqId').value=f.id;document.getElementById('faqQ').value=f.question;
    document.getElementById('faqA').value=f.answer;document.getElementById('faqCat').value=f.category||'';
    document.getElementById('faqSort').value=f.sort_order;document.getElementById('faqActive').checked=f.is_active==1;
    document.getElementById('cancelFaq').classList.remove('hidden');
}
function resetFaq(){document.getElementById('faqForm').reset();document.getElementById('faqId').value='';document.getElementById('cancelFaq').classList.add('hidden');}
function deleteItem(type,id){if(!confirm('Delete?'))return;const fd=new FormData();fd.append('type',type);fd.append('id',id);fd.append('csrf_token','<?=e(Auth::generateCsrfToken())?>');fetch('<?=base_url('admin/delete')?>',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success)location.reload();});}
</script>

<?php include __DIR__ . '/layout_bottom.php'; ?>
