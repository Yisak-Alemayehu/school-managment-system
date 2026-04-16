<?php $pageTitle = 'Testimonials'; $currentPage = 'testimonials'; include __DIR__ . '/layout_top.php'; ?>

<div class="bg-white rounded-xl border border-gray-100 p-5 mb-6">
    <h3 class="text-sm font-bold text-gray-900 mb-4">Add / Edit Testimonial</h3>
    <form method="POST" action="<?= base_url('admin/testimonials/save') ?>" id="testForm">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="testId" value="">
        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">School Name</label>
                <input type="text" name="school_name" id="testSchool" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Person Name</label>
                <input type="text" name="person_name" id="testPerson" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Role / Title</label>
                <input type="text" name="role" id="testRole" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="Principal">
            </div>
        </div>
        <div class="mt-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Content</label>
            <textarea name="content" id="testContent" rows="3" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm"></textarea>
        </div>
        <div class="mt-3 flex items-center gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Rating</label>
                <select name="rating" id="testRating" class="px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white">
                    <option value="5">5 Stars</option><option value="4">4 Stars</option><option value="3">3 Stars</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sort Order</label>
                <input type="number" name="sort_order" id="testSort" value="0" class="px-3 py-2 rounded-lg border border-gray-200 text-sm w-20">
            </div>
            <label class="flex items-center gap-2 text-sm mt-5"><input type="checkbox" name="is_active" id="testActive" value="1" checked class="rounded border-gray-300 text-primary-600"> Active</label>
            <button type="submit" class="mt-5 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700">Save</button>
            <button type="button" onclick="resetTest()" class="mt-5 px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 hidden" id="cancelTest">Cancel</button>
        </div>
    </form>
</div>

<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($testimonials as $t): ?>
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <div class="flex items-center gap-1 mb-2">
            <?php for ($i = 0; $i < $t['rating']; $i++): ?>
            <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            <?php endfor; ?>
        </div>
        <p class="text-sm text-gray-700 mb-3">"<?= e($t['content']) ?>"</p>
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs font-bold text-gray-900"><?= e($t['person_name']) ?></div>
                <div class="text-[10px] text-gray-500"><?= e($t['role'] ?? '') ?> · <?= e($t['school_name']) ?></div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick='editTest(<?= json_encode($t) ?>)' class="text-primary-600 text-xs hover:underline">Edit</button>
                <button onclick="deleteItem('testimonials', <?= $t['id'] ?>)" class="text-red-600 text-xs hover:underline">Del</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
function editTest(t) {
    document.getElementById('testId').value=t.id; document.getElementById('testSchool').value=t.school_name;
    document.getElementById('testPerson').value=t.person_name; document.getElementById('testRole').value=t.role||'';
    document.getElementById('testContent').value=t.content; document.getElementById('testRating').value=t.rating;
    document.getElementById('testSort').value=t.sort_order; document.getElementById('testActive').checked=t.is_active==1;
    document.getElementById('cancelTest').classList.remove('hidden');
}
function resetTest(){document.getElementById('testForm').reset();document.getElementById('testId').value='';document.getElementById('cancelTest').classList.add('hidden');}
function deleteItem(type,id){if(!confirm('Delete?'))return;const fd=new FormData();fd.append('type',type);fd.append('id',id);fd.append('csrf_token','<?=e(Auth::generateCsrfToken())?>');fetch('<?=base_url('admin/delete')?>',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success)location.reload();});}
</script>

<?php include __DIR__ . '/layout_bottom.php'; ?>
