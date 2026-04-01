<?php
/**
 * Finance — Collect Supplementary Payment Page
 * Search student, select supplementary fee, and collect payment.
 */

// Fetch active classes for filter
$classes = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");

// Student search / filters
$search   = input('search');
$classId  = input_int('class_id');
$studentId = input_int('student_id');
$sfeeId   = input_int('sfee_id');
$page     = max(1, input_int('page') ?: 1);
$perPage  = 15;
$student  = null;
$availableFees    = [];
$recentPayments   = [];

if ($studentId) {
    $student = db_fetch_one(
        "SELECT s.*, c.name AS class_name, sec.name AS section_name
           FROM students s
           LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
           LEFT JOIN classes c ON e.class_id = c.id
           LEFT JOIN sections sec ON e.section_id = sec.id
          WHERE s.id = ? AND s.deleted_at IS NULL",
        [$studentId]
    );

    if ($student) {
        $availableFees = db_fetch_all(
            "SELECT * FROM fin_supplementary_fees WHERE is_active = 1 ORDER BY description"
        );

        $recentPayments = db_fetch_all(
            "SELECT st.*, sf.description AS fee_desc
               FROM fin_supplementary_transactions st
               LEFT JOIN fin_supplementary_fees sf ON st.supplementary_fee_id = sf.id
              WHERE st.student_id = ?
              ORDER BY st.created_at DESC
              LIMIT 10",
            [$studentId]
        );
    }
}

// Student list (always shown when no student selected)
$searchResults = [];
$totalStudents = 0;
$totalPages    = 1;
if (!$studentId) {
    $where  = ["s.deleted_at IS NULL"];
    $params = [];

    if ($search) {
        $where[]  = "(s.full_name LIKE ? OR s.admission_no LIKE ? OR s.phone LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($classId) {
        $where[]  = "e.class_id = ?";
        $params[] = $classId;
    }

    $whereClause = implode(' AND ', $where);

    $countRow = db_fetch_one(
        "SELECT COUNT(DISTINCT s.id) AS cnt
           FROM students s
           LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
          WHERE $whereClause",
        $params
    );
    $totalStudents = (int)($countRow['cnt'] ?? 0);
    $totalPages    = max(1, (int)ceil($totalStudents / $perPage));
    $page          = min($page, $totalPages);
    $offset        = ($page - 1) * $perPage;

    $searchResults = db_fetch_all(
        "SELECT s.id, s.full_name, s.admission_no, s.phone,
                c.name AS class_name, sec.name AS section_name
           FROM students s
           LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
           LEFT JOIN classes c ON e.class_id = c.id
           LEFT JOIN sections sec ON e.section_id = sec.id
          WHERE $whereClause
          GROUP BY s.id, s.full_name, s.admission_no, s.phone, c.name, sec.name
          ORDER BY s.full_name
          LIMIT $perPage OFFSET $offset",
        $params
    );
}

// Payment channels
$paymentChannels = [
    'cash'           => 'Cash',
    'bank'           => 'Bank',
    'mobile'         => 'Mobile',
    'telebirr'       => 'TeleBirr',
];

ob_start();
?>

<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Collect Supplementary Payment</h1>
        <a href="<?= url('finance', 'supplementary-payments') ?>"
           class="px-3 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 font-medium inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Payment History
        </a>
    </div>

    <!-- Step 1: Search / Filter Students -->
    <?php if (!$student): ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Step 1: Find Student</h2>
        <form method="GET" action="<?= url('finance', 'collect-supplementary-payment') ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Name, student code, or phone…"
                       class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500" autofocus>
                <select name="class_id"
                        class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $classId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Search
                </button>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($search || $classId): ?>
                <a href="<?= url('finance', 'collect-supplementary-payment') ?>" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 dark:text-dark-muted">Clear filters</a>
                <?php endif; ?>
                <span class="ml-auto text-xs text-gray-500 dark:text-dark-muted"><?= $totalStudents ?> student<?= $totalStudents !== 1 ? 's' : '' ?> found</span>
            </div>
        </form>

        <?php if (!empty($searchResults)): ?>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border responsive-table">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Class</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php foreach ($searchResults as $i => $sr): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg">
                        <td class="px-4 py-3 text-sm text-gray-400"><?= ($page - 1) * $perPage + $i + 1 ?></td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($sr['full_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted"><?= e($sr['admission_no']) ?></td>
                        <td class="px-4 py-3 text-sm">
                            <?= e($sr['class_name'] ?? '—') ?>
                            <?php if (!empty($sr['section_name'])): ?><span class="text-gray-400"> / <?= e($sr['section_name']) ?></span><?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <a href="<?= url('finance', 'collect-supplementary-payment') ?>&student_id=<?= $sr['id'] ?>"
                               class="px-3 py-1.5 bg-primary-600 text-white text-xs rounded-lg hover:bg-primary-700 font-medium">
                                Select
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="mt-4 flex items-center justify-between flex-wrap gap-2">
            <p class="text-xs text-gray-500 dark:text-dark-muted">
                Showing <?= ($page - 1) * $perPage + 1 ?>–<?= min($page * $perPage, $totalStudents) ?> of <?= $totalStudents ?> students
            </p>
            <div class="flex items-center gap-1">
                <?php
                $qBase = array_filter(['search' => $search, 'class_id' => $classId ?: '']);
                $buildPageUrl = fn(int $p) => url('finance', 'collect-supplementary-payment') . '&' . http_build_query(array_merge($qBase, ['page' => $p]));
                ?>
                <?php if ($page > 1): ?>
                <a href="<?= $buildPageUrl(1) ?>" class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-dark-border hover:bg-gray-100 dark:hover:bg-dark-bg">&laquo;</a>
                <a href="<?= $buildPageUrl($page - 1) ?>" class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-dark-border hover:bg-gray-100 dark:hover:bg-dark-bg">&lsaquo;</a>
                <?php endif; ?>
                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                <a href="<?= $buildPageUrl($p) ?>"
                   class="px-2.5 py-1 text-xs rounded border <?= $p === $page ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-300 dark:border-dark-border hover:bg-gray-100 dark:hover:bg-dark-bg' ?>">
                    <?= $p ?>
                </a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <a href="<?= $buildPageUrl($page + 1) ?>" class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-dark-border hover:bg-gray-100 dark:hover:bg-dark-bg">&rsaquo;</a>
                <a href="<?= $buildPageUrl($totalPages) ?>" class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-dark-border hover:bg-gray-100 dark:hover:bg-dark-bg">&raquo;</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php elseif ($search): ?>
        <div class="mt-4 p-4 bg-yellow-50 text-yellow-700 rounded-lg text-sm">No students found matching "<strong><?= e($search) ?></strong>".</div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Step 2: Student Selected — Show supplementary fees and payment form -->
    <?php if ($student): ?>
    <div class="flex items-center gap-2 mb-2">
        <a href="<?= url('finance', 'collect-supplementary-payment') ?>" class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-dark-muted hover:text-primary-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Search
        </a>
    </div>

    <!-- Student Info Card -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <div class="flex flex-col sm:flex-row gap-4 items-start">
            <div class="flex-shrink-0">
                <?php if (!empty($student['photo'])): ?>
                    <img src="<?= upload_url($student['photo']) ?>" alt="Photo" class="w-16 h-16 rounded-xl object-cover border">
                <?php else: ?>
                    <div class="w-16 h-16 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center text-lg font-bold border">
                        <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-2">
                <div>
                    <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Student Name</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($student['full_name']) ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Student Code</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($student['admission_no']) ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Class</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($student['class_name'] ?? '—') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Form -->
    <?php if (empty($availableFees)): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
        <p class="text-yellow-700 font-medium">No active supplementary fees available.</p>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Step 2: Collect Supplementary Payment</h2>
        <form method="POST" action="<?= url('finance', 'collect-supplementary-payment-save') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="student_id" value="<?= $student['id'] ?>">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Supplementary Fee *</label>
                        <select name="supplementary_fee_id" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                            <option value="">— Select Fee —</option>
                            <?php foreach ($availableFees as $af): ?>
                            <option value="<?= $af['id'] ?>" data-amount="<?= $af['amount'] ?>" <?= $sfeeId == $af['id'] ? 'selected' : '' ?>>
                                <?= e($af['description']) ?> — <?= format_money($af['amount']) ?> <?= e($af['currency']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount *</label>
                        <input type="number" name="amount" step="0.01" min="0.01" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                               placeholder="Enter amount" id="supAmount">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Method *</label>
                        <select name="channel" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                            <option value="">— Select Method —</option>
                            <?php foreach ($paymentChannels as $key => $label): ?>
                            <option value="<?= $key ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reference</label>
                        <input type="text" name="reference"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                               placeholder="Optional reference number">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea name="notes" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                                  placeholder="Optional notes…"></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <a href="<?= url('finance', 'collect-supplementary-payment') ?>"
                   class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 font-medium inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Record Payment
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Recent Supplementary Payments -->
    <?php if ($student && !empty($recentPayments)): ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Recent Supplementary Payments</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border responsive-table">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Fee</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Channel</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php foreach ($recentPayments as $rp): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg">
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted"><?= format_datetime($rp['created_at']) ?></td>
                        <td class="px-4 py-3 text-sm"><?= e($rp['fee_desc'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm font-semibold text-green-600"><?= format_money($rp['amount']) ?></td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300">
                                <?= ucfirst(str_replace('_', ' ', $rp['channel'] ?? '—')) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted"><?= e($rp['receipt_no'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; /* end if ($student) */ ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const feeSelect = document.querySelector('select[name="supplementary_fee_id"]');
    const amountInput = document.getElementById('supAmount');
    if (feeSelect) {
        feeSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const amount = parseFloat(opt.dataset.amount || 0);
            if (amount > 0) {
                amountInput.value = amount;
            }
        });
        // Trigger on load if pre-selected
        if (feeSelect.value) { feeSelect.dispatchEvent(new Event('change')); }
    }
});
</script>

<?php
$content = ob_get_clean();
$page_title = $pageTitle;
include TEMPLATES_PATH . '/layout.php';
