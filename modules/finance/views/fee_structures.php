<?php
/**
 * Finance — Fee Structures List
 */
$pageTitle = 'Fee Structures';

$classFilter    = input_int('class_id');
$categoryFilter = input_int('category_id');
$sessionId      = get_active_session_id();

$where  = ['fs.session_id = ?'];
$params = [$sessionId];

if ($classFilter) {
    $where[]  = 'fs.class_id = ?';
    $params[] = $classFilter;
}
if ($categoryFilter) {
    $where[]  = 'fs.fee_category_id = ?';
    $params[] = $categoryFilter;
}

$whereStr = implode(' AND ', $where);

$structures = db_fetch_all("
    SELECT fs.*, c.name AS class_name, fc.name AS category_name, fc.type AS category_type
    FROM fee_structures fs
    JOIN classes c ON c.id = fs.class_id
    JOIN fee_categories fc ON fc.id = fs.fee_category_id
    WHERE {$whereStr}
    ORDER BY c.name, fc.name
", $params);

$classes    = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY name");
$categories = db_fetch_all("SELECT id, name FROM fee_categories ORDER BY name");

ob_start();
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Fee Structures</h1>
        <a href="<?= url('finance', 'fee-structure-create') ?>"
           class="inline-flex items-center gap-2 px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
            + Add Fee Structure
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <input type="hidden" name="module" value="finance">
            <input type="hidden" name="action" value="fee-structures">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select name="class_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $classFilter == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-900">Filter</button>
                <a href="<?= url('finance', 'fee-structures') ?>" class="ml-2 text-sm text-gray-500 hover:text-gray-700">Reset</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Frequency</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($structures)): ?>
                        <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No fee structures found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($structures as $fs): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm font-medium text-gray-900"><?= e($fs['class_name']) ?></td>
                                <td class="px-6 py-3 text-sm text-gray-700"><?= e($fs['category_name']) ?></td>
                                <td class="px-6 py-3 text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><?= ucfirst($fs['category_type']) ?></span>
                                </td>
                                <td class="px-6 py-3 text-sm text-right font-semibold"><?= format_currency($fs['amount']) ?></td>
                                <td class="px-6 py-3 text-sm text-gray-600"><?= ucfirst($fs['frequency'] ?? 'term') ?></td>
                                <td class="px-6 py-3 text-sm text-gray-600"><?= $fs['due_date'] ? format_date($fs['due_date']) : '—' ?></td>
                                <td class="px-6 py-3 text-right text-sm space-x-2">
                                    <a href="<?= url('finance', 'fee-structure-edit') ?>&id=<?= $fs['id'] ?>"
                                       class="text-primary-700 hover:text-primary-900 font-medium">Edit</a>
                                    <a href="<?= url('finance', 'fee-structure-delete') ?>&id=<?= $fs['id'] ?>"
                                       class="text-red-600 hover:text-red-800 font-medium"
                                       onclick="return confirm('Delete this fee structure?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
