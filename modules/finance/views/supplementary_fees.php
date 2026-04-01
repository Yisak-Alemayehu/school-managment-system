<?php
/**
 * Finance — Supplementary Fees List + Add New Supplementary Fee
 */

$search = input('search');
$page   = max(1, input_int('page') ?: 1);
$perPage = 25;

$where  = ["1=1"];
$params = [];
if ($search) {
    $where[]  = "sf.description LIKE ?";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);
$offset      = ($page - 1) * $perPage;

$total = (int) db_fetch_value("SELECT COUNT(*) FROM fin_supplementary_fees sf WHERE $whereClause", $params);
$sfees = db_fetch_all(
    "SELECT sf.*,
            (SELECT COUNT(*) FROM fin_supplementary_transactions st WHERE st.supplementary_fee_id = sf.id) AS tx_count,
            (SELECT COALESCE(SUM(st.amount), 0) FROM fin_supplementary_transactions st WHERE st.supplementary_fee_id = sf.id) AS total_collected,
            u.full_name AS created_by_name
       FROM fin_supplementary_fees sf
       LEFT JOIN users u ON sf.created_by = u.id
      WHERE $whereClause
      ORDER BY sf.created_at DESC
      LIMIT $perPage OFFSET $offset",
    $params
);

$lastPage   = max(1, (int) ceil($total / $perPage));
$pagination = [
    'total' => $total, 'per_page' => $perPage, 'current_page' => $page,
    'last_page' => $lastPage, 'from' => $total > 0 ? $offset + 1 : 0,
    'to' => min($offset + $perPage, $total),
];

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Supplementary Fees</h1>
        <button onclick="document.getElementById('addSupFeeModal').classList.remove('hidden')"
                class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">
            + Add Supplementary Fee
        </button>
    </div>

    <!-- Search -->
    <form method="GET" action="<?= url('finance', 'supplementary-fees') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-4">
        <div class="flex gap-3">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search supplementary fees…"
                   class="flex-1 px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">Search</button>
            <a href="<?= url('finance', 'supplementary-fees') ?>" class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 font-medium">Clear</a>
        </div>
    </form>

    <!-- Table -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border responsive-table">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Currency</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Transactions</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Total Collected</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Created</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php if (empty($sfees)): ?>
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No supplementary fees defined.</td></tr>
                    <?php else: ?>
                    <?php foreach ($sfees as $sf): ?>
                    <tr class="hover:bg-gray-50 dark:bg-dark-bg transition-colors">
                        <td class="px-4 py-3 text-sm font-medium" data-label="Description"><?= e($sf['description']) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Amount"><?= format_money($sf['amount']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Currency"><?= e($sf['currency']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Transactions"><?= (int) $sf['tx_count'] ?></td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-dark-text" data-label="Collected"><?= format_money($sf['total_collected']) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Status">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $sf['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= $sf['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Created"><?= format_date($sf['created_at']) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Actions">
                            <div class="flex items-center gap-1 flex-wrap">
                                <a href="<?= url('finance', 'collect-supplementary-payment') ?>&sfee_id=<?= $sf['id'] ?>"
                                   class="px-2 py-1 bg-green-50 text-green-700 text-xs rounded-lg hover:bg-green-100 font-medium border border-green-200">
                                    Collect
                                </a>
                                <button type="button" onclick="openEditSupFee(<?= e(json_encode($sf)) ?>)"
                                        class="px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded-lg hover:bg-blue-100 font-medium border border-blue-200">
                                    Edit
                                </button>
                                <form method="POST" action="<?= url('finance', 'supplementary-fee-toggle') ?>" class="inline" onsubmit="return confirm('Toggle active status?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="supplementary_fee_id" value="<?= $sf['id'] ?>">
                                    <button type="submit"
                                            class="px-2 py-1 text-xs rounded-lg font-medium border <?= $sf['is_active'] ? 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100' : 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100' ?>">
                                        <?= $sf['is_active'] ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= pagination_html($pagination, url('finance/supplementary-fees')) ?>
    </div>
</div>

<!-- Add Supplementary Fee Modal -->
<div id="addSupFeeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white dark:bg-dark-card rounded-xl w-full max-w-md p-6 shadow-xl">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Add Supplementary Fee</h2>
        <form method="POST" action="<?= url('finance', 'supplementary-fee-save') ?>">
            <?= csrf_field() ?>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description *</label>
                    <input type="text" name="description" required maxlength="255"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                           placeholder="e.g. Lab Fee, Uniform Fee">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount *</label>
                    <input type="number" name="amount" step="0.01" min="0" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                           placeholder="0.00">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Currency</label>
                    <select name="currency" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        <option value="ETB">ETB (Birr)</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
                <div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="rounded border-gray-300 dark:border-dark-border text-primary-600 focus:ring-primary-500">
                        Active
                    </label>
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">Add</button>
                <button type="button" onclick="document.getElementById('addSupFeeModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 font-medium">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Supplementary Fee Modal -->
<div id="editSupFeeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white dark:bg-dark-card rounded-xl w-full max-w-md p-6 shadow-xl">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Edit Supplementary Fee</h2>
        <form method="POST" action="<?= url('finance', 'supplementary-fee-update') ?>" id="editSupFeeForm">
            <?= csrf_field() ?>
            <input type="hidden" name="supplementary_fee_id" id="editSupFeeId">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description *</label>
                    <input type="text" name="description" id="editSupFeeDesc" required maxlength="255"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount *</label>
                    <input type="number" name="amount" id="editSupFeeAmount" step="0.01" min="0" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Currency</label>
                    <select name="currency" id="editSupFeeCurrency" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        <option value="ETB">ETB (Birr)</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
                <div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" id="editSupFeeActive"
                               class="rounded border-gray-300 dark:border-dark-border text-primary-600 focus:ring-primary-500">
                        Active
                    </label>
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">Update</button>
                <button type="button" onclick="document.getElementById('editSupFeeModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 font-medium">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditSupFee(sf) {
    document.getElementById('editSupFeeId').value = sf.id;
    document.getElementById('editSupFeeDesc').value = sf.description;
    document.getElementById('editSupFeeAmount').value = sf.amount;
    document.getElementById('editSupFeeCurrency').value = sf.currency;
    document.getElementById('editSupFeeActive').checked = !!parseInt(sf.is_active);
    document.getElementById('editSupFeeModal').classList.remove('hidden');
}
</script>

<?php
$content = ob_get_clean();
$page_title = $pageTitle;
include TEMPLATES_PATH . '/layout.php';
