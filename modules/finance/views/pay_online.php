<?php
/**
 * Finance — Online Payment Page (for Students/Parents)
 */
$pageTitle = 'Pay Online';

$user = current_user();
$studentId = null;

// If student role, get their ID; if parent, get linked students
if ($user['role_slug'] === 'student') {
    $student = db_fetch_one("SELECT id FROM students WHERE user_id = ?", [$user['id']]);
    $studentId = $student['id'] ?? null;
} elseif ($user['role_slug'] === 'parent') {
    // Parents can see invoices of their children
    $children = db_fetch_all("
        SELECT s.id, s.first_name, s.last_name, s.admission_no
        FROM students s
        JOIN guardians g ON g.student_id = s.id
        WHERE g.user_id = ?
    ", [$user['id']]);
}

// Get unpaid invoices
if ($studentId) {
    $invoices = db_fetch_all("
        SELECT i.*, c.name AS class_name
        FROM invoices i
        JOIN classes c ON c.id = i.class_id
        WHERE i.student_id = ? AND i.status IN ('unpaid', 'partial')
        ORDER BY i.due_date ASC
    ", [$studentId]);
} elseif (!empty($children)) {
    $childIds = array_column($children, 'id');
    $placeholders = implode(',', array_fill(0, count($childIds), '?'));
    $invoices = db_fetch_all("
        SELECT i.*, c.name AS class_name, s.first_name, s.last_name
        FROM invoices i
        JOIN classes c ON c.id = i.class_id
        JOIN students s ON s.id = i.student_id
        WHERE i.student_id IN ({$placeholders}) AND i.status IN ('unpaid', 'partial')
        ORDER BY i.due_date ASC
    ", $childIds);
} else {
    $invoices = [];
}

// Available payment gateways
$gateways = db_fetch_all("SELECT * FROM payment_gateways WHERE is_active = 1 ORDER BY name");

ob_start();
?>
<div class="max-w-3xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">Pay Online</h1>

    <?php if (empty($invoices)): ?>
        <div class="bg-white rounded-xl shadow-sm border p-8 text-center">
            <div class="text-4xl mb-2">✅</div>
            <p class="text-gray-600">No outstanding invoices. You're all paid up!</p>
        </div>
    <?php else: ?>
        <?php if (empty($gateways)): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-sm text-yellow-800">
                Online payment is currently unavailable. Please pay at the school office.
            </div>
        <?php endif; ?>

        <div class="space-y-4">
            <?php foreach ($invoices as $inv): ?>
                <?php $balance = $inv['total_amount'] - $inv['paid_amount']; ?>
                <div class="bg-white rounded-xl shadow-sm border p-5">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <div class="font-mono text-sm text-primary-700 font-medium"><?= e($inv['invoice_no']) ?></div>
                            <?php if (isset($inv['first_name'])): ?>
                                <div class="font-semibold text-gray-900"><?= e($inv['first_name'] . ' ' . $inv['last_name']) ?></div>
                            <?php endif; ?>
                            <div class="text-sm text-gray-500"><?= e($inv['class_name']) ?> | Due: <?= $inv['due_date'] ? format_date($inv['due_date']) : 'N/A' ?></div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-red-600"><?= format_currency($balance) ?></div>
                            <div class="text-xs text-gray-500">Balance Due</div>
                        </div>
                    </div>

                    <?php if (!empty($gateways)): ?>
                        <form method="POST" action="<?= url('finance', 'payment-initiate') ?>" class="mt-4 border-t pt-4">
                            <?= csrf_field() ?>
                            <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
                            <div class="flex flex-col sm:flex-row gap-3 items-end">
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 mb-1">Amount</label>
                                    <input type="number" name="amount" step="0.01" min="1" max="<?= $balance ?>"
                                           value="<?= number_format($balance, 2, '.', '') ?>"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 mb-1">Pay via</label>
                                    <select name="gateway" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                        <?php foreach ($gateways as $gw): ?>
                                            <option value="<?= e($gw['slug']) ?>"><?= e($gw['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="px-6 py-2 bg-green-700 text-white rounded-lg text-sm font-medium hover:bg-green-800">
                                    Pay Now
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
