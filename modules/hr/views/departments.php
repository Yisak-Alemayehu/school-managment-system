<?php
/**
 * HR — Departments View
 */

$departments = db_fetch_all(
    "SELECT d.*, 
            (SELECT COUNT(*) FROM hr_employees e WHERE e.department_id = d.id AND e.deleted_at IS NULL AND e.status = 'active') AS employee_count,
            CONCAT(hd.first_name, ' ', hd.father_name) AS head_name
     FROM hr_departments d
     LEFT JOIN hr_employees hd ON d.head_of_department_id = hd.id
     WHERE d.deleted_at IS NULL
     ORDER BY d.name"
);

$employees = db_fetch_all("SELECT id, first_name, father_name FROM hr_employees WHERE deleted_at IS NULL AND status = 'active' ORDER BY first_name");

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Departments</h1>
        <button onclick="openDeptModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Department
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($departments as $dept): ?>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-dark-text"><?= e($dept['name']) ?></h3>
                    <p class="text-xs font-mono text-gray-400"><?= e($dept['code']) ?></p>
                </div>
                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $dept['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                    <?= ucfirst($dept['status']) ?>
                </span>
            </div>
            <?php if (!empty($dept['description'])): ?>
            <p class="text-sm text-gray-500 dark:text-dark-muted mb-3"><?= e($dept['description']) ?></p>
            <?php endif; ?>
            <div class="flex items-center justify-between text-sm">
                <div class="text-gray-500 dark:text-dark-muted">
                    <span class="font-medium text-gray-900 dark:text-dark-text"><?= (int)$dept['employee_count'] ?></span> employees
                </div>
                <?php if ($dept['head_name']): ?>
                <div class="text-xs text-gray-500">Head: <?= e($dept['head_name']) ?></div>
                <?php endif; ?>
            </div>
            <div class="flex gap-2 mt-3 pt-3 border-t border-gray-100 dark:border-dark-border">
                <button onclick="editDept(<?= htmlspecialchars(json_encode($dept), ENT_QUOTES) ?>)" class="text-xs text-amber-600 hover:text-amber-800 font-medium">Edit</button>
                <form method="POST" action="<?= url('hr', 'department-delete') ?>" onsubmit="return confirm('Delete this department?');" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $dept['id'] ?>">
                    <button class="text-xs text-red-600 hover:text-red-800 font-medium">Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($departments)): ?>
        <div class="col-span-full text-center py-8 text-gray-400">No departments found.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Department Modal -->
<div id="deptModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-dark-card rounded-xl w-full max-w-md p-6 m-4">
        <h3 id="deptModalTitle" class="text-lg font-semibold text-gray-900 dark:text-dark-text mb-4">Add Department</h3>
        <form method="POST" action="<?= url('hr', 'department-save') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="dept_id" value="">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Name *</label>
                    <input type="text" name="name" id="dept_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Code *</label>
                    <input type="text" name="code" id="dept_code" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text" placeholder="e.g. ADMIN">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Description</label>
                    <textarea name="description" id="dept_desc" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Head of Department</label>
                    <select name="head_employee_id" id="dept_head" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <option value="">None</option>
                        <?php foreach ($employees as $e_item): ?>
                        <option value="<?= $e_item['id'] ?>"><?= e($e_item['first_name'] . ' ' . $e_item['father_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Status</label>
                    <select name="status" id="dept_status" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" onclick="closeDeptModal()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDeptModal() {
    document.getElementById('deptModalTitle').textContent = 'Add Department';
    document.getElementById('dept_id').value = '';
    document.getElementById('dept_name').value = '';
    document.getElementById('dept_code').value = '';
    document.getElementById('dept_desc').value = '';
    document.getElementById('dept_head').value = '';
    document.getElementById('dept_status').value = 'active';
    document.getElementById('deptModal').classList.remove('hidden');
}
function editDept(d) {
    document.getElementById('deptModalTitle').textContent = 'Edit Department';
    document.getElementById('dept_id').value = d.id;
    document.getElementById('dept_name').value = d.name;
    document.getElementById('dept_code').value = d.code;
    document.getElementById('dept_desc').value = d.description || '';
    document.getElementById('dept_head').value = d.head_employee_id || '';
    document.getElementById('dept_status').value = d.status;
    document.getElementById('deptModal').classList.remove('hidden');
}
function closeDeptModal() { document.getElementById('deptModal').classList.add('hidden'); }
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
