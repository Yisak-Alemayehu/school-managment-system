<?php
/**
 * Finance — Collect Payment Page
 * Search student, select fee, choose payment method, and collect payment.
 * TeleBirr payments are auto-marked as paid. Other methods require manual confirmation.
 */

// Fetch active classes for filter
$classes = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");

// Student search / filters
$search        = input('search');
$classId       = input_int('class_id');
$sectionId     = input_int('section_id');
$balanceFilter = input('balance_filter'); // 'with_balance' | 'no_balance' | ''
$studentId     = input_int('student_id');
$page          = max(1, input_int('page') ?: 1);
$perPage       = 15;
$student       = null;
$activeFees    = [];
$recentPayments = [];

// Sections for the selected class
$sections = [];
if ($classId) {
    $sections = db_fetch_all("SELECT id, name FROM sections WHERE class_id = ? AND is_active = 1 ORDER BY name", [$classId]);
}

if ($studentId) {
    $student = db_fetch_one(
        "SELECT s.*, c.name AS class_name, sec.name AS section_name, e.roll_no
           FROM students s
           LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
           LEFT JOIN classes c ON e.class_id = c.id
           LEFT JOIN sections sec ON e.section_id = sec.id
          WHERE s.id = ? AND s.deleted_at IS NULL",
        [$studentId]
    );

    if ($student) {
        $activeFees = db_fetch_all(
            "SELECT sf.id AS sf_id, sf.amount, sf.balance, sf.currency, f.description
               FROM fin_student_fees sf
               JOIN fin_fees f ON sf.fee_id = f.id
              WHERE sf.student_id = ? AND sf.is_active = 1 AND sf.balance > 0
              ORDER BY f.description",
            [$studentId]
        );

        $recentPayments = db_fetch_all(
            "SELECT t.*, f.description AS fee_desc
               FROM fin_transactions t
               LEFT JOIN fin_student_fees sf ON t.student_fee_id = sf.id
               LEFT JOIN fin_fees f ON sf.fee_id = f.id
              WHERE t.student_id = ? AND t.type = 'payment'
              ORDER BY t.created_at DESC
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
    if ($sectionId) {
        $where[]  = "e.section_id = ?";
        $params[] = $sectionId;
    }

    $whereClause = implode(' AND ', $where);

    // Having clause for balance filter
    $having = '';
    if ($balanceFilter === 'with_balance') {
        $having = 'HAVING total_balance > 0';
    } elseif ($balanceFilter === 'no_balance') {
        $having = 'HAVING total_balance = 0';
    }

    // Count total
    $countRow = db_fetch_one(
        "SELECT COUNT(*) AS cnt FROM (
            SELECT s.id,
                   COALESCE(SUM(CASE WHEN sf.is_active = 1 THEN sf.balance ELSE 0 END), 0) AS total_balance
              FROM students s
              LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
              LEFT JOIN fin_student_fees sf ON s.id = sf.student_id
             WHERE $whereClause
             GROUP BY s.id
             $having
         ) sub",
        $params
    );
    $totalStudents = (int)($countRow['cnt'] ?? 0);
    $totalPages    = max(1, (int)ceil($totalStudents / $perPage));
    $page          = min($page, $totalPages);
    $offset        = ($page - 1) * $perPage;

    $searchResults = db_fetch_all(
        "SELECT s.id, s.full_name, s.admission_no, s.phone,
                c.name AS class_name, sec.name AS section_name,
                COALESCE(SUM(CASE WHEN sf.is_active = 1 THEN sf.balance ELSE 0 END), 0) AS total_balance
           FROM students s
           LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
           LEFT JOIN classes c ON e.class_id = c.id
           LEFT JOIN sections sec ON e.section_id = sec.id
           LEFT JOIN fin_student_fees sf ON s.id = sf.student_id
          WHERE $whereClause
          GROUP BY s.id, s.full_name, s.admission_no, s.phone, c.name, sec.name
          $having
          ORDER BY s.full_name
          LIMIT $perPage OFFSET $offset",
        $params
    );
}

// Payment channels
$paymentChannels = [
    'telebirr'       => 'TeleBirr',
    'cbe_birr'       => 'CBE Birr',
    'bank_transfer'  => 'Bank Transfer',
    'bank_deposit'   => 'Bank Deposit',
    'cash'           => 'Cash',
    'check'          => 'Check',
    'mobile_banking' => 'Mobile Banking',
    'other'          => 'Other',
];

ob_start();
?>

<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Collect Payment</h1>
        <a href="<?= url('finance', 'payments') ?>"
           class="px-3 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 font-medium inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Payment History
        </a>
    </div>

    <!-- Step 1: Search / Filter Students -->
    <?php if (!$student): ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Step 1: Find Student</h2>
        <form method="GET" action="<?= url('finance', 'collect-payment') ?>" id="studentFilterForm">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Name, student code, or phone…"
                       class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500" autofocus>
                <select name="class_id" id="classFilterSelect"
                        class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                        onchange="this.form.section_id.value=''; this.form.submit();">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $classId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="section_id"
                        class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    <option value="">All Sections</option>
                    <?php foreach ($sections as $sec): ?>
                    <option value="<?= $sec['id'] ?>" <?= $sectionId == $sec['id'] ? 'selected' : '' ?>><?= e($sec['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="balance_filter"
                        class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    <option value="">All Students</option>
                    <option value="with_balance" <?= $balanceFilter === 'with_balance' ? 'selected' : '' ?>>With Outstanding Balance</option>
                    <option value="no_balance" <?= $balanceFilter === 'no_balance' ? 'selected' : '' ?>>No Outstanding Balance</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Search
                </button>
                <?php if ($search || $classId || $sectionId || $balanceFilter): ?>
                <a href="<?= url('finance', 'collect-payment') ?>" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 dark:text-dark-muted">Clear filters</a>
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Class / Section</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Balance</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php foreach ($searchResults as $i => $sr): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg">
                        <td class="px-4 py-3 text-sm text-gray-400"><?= ($page - 1) * $perPage + $i + 1 ?></td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-dark-text" data-label="Student"><?= e($sr['full_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted" data-label="Code"><?= e($sr['admission_no']) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Class">
                            <?= e($sr['class_name'] ?? '—') ?>
                            <?php if (!empty($sr['section_name'])): ?><span class="text-gray-400"> / <?= e($sr['section_name']) ?></span><?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold <?= $sr['total_balance'] > 0 ? 'text-red-600' : 'text-green-600' ?>" data-label="Balance">
                            <?= format_money($sr['total_balance']) ?>
                        </td>
                        <td class="px-4 py-3" data-label="Action">
                            <a href="<?= url('finance', 'collect-payment') ?>&student_id=<?= $sr['id'] ?>"
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
                $qBase = array_filter(['search' => $search, 'class_id' => $classId ?: '', 'section_id' => $sectionId ?: '', 'balance_filter' => $balanceFilter]);
                $buildPageUrl = fn(int $p) => url('finance', 'collect-payment') . '&' . http_build_query(array_merge($qBase, ['page' => $p]));
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
        <?php elseif ($totalStudents === 0): ?>
        <div class="mt-4 p-4 bg-gray-50 dark:bg-dark-bg text-gray-500 rounded-lg text-sm">No students found for the selected filters.</div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Step 2: Student Selected — Show fees and payment form -->
    <?php if ($student): ?>
    <div class="flex items-center gap-2 mb-2">
        <a href="<?= url('finance', 'collect-payment') ?>" class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-dark-muted hover:text-primary-600">
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

    <!-- Outstanding Fees -->
    <?php if (empty($activeFees)): ?>
    <div class="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
        <svg class="w-12 h-12 mx-auto text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-green-700 font-medium">This student has no outstanding fees.</p>
    </div>
    <?php else: ?>

    <!-- Payment Form -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Step 2: Collect Payment</h2>

        <form method="POST" action="<?= url('finance', 'collect-payment-save') ?>" id="collectPaymentForm">
            <?= csrf_field() ?>
            <input type="hidden" name="student_id" value="<?= $student['id'] ?>">

            <!-- Multi-fee table -->
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border">
                    <thead class="bg-gray-50 dark:bg-dark-bg">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase w-10">
                                <input type="checkbox" id="checkAllFees" class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Fee Description</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Balance</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Amount to Pay</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                        <?php foreach ($activeFees as $af): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg fee-row">
                            <td class="px-4 py-3">
                                <input type="checkbox" name="fees[<?= $af['sf_id'] ?>][selected]" value="1"
                                       class="fee-checkbox w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                       data-sf-id="<?= $af['sf_id'] ?>">
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-dark-text"><?= e($af['description']) ?></td>
                            <td class="px-4 py-3 text-sm font-semibold text-red-600"><?= format_money($af['balance']) ?></td>
                            <td class="px-4 py-3">
                                <input type="number" name="fees[<?= $af['sf_id'] ?>][amount]" step="0.01" min="0.01"
                                       value="<?= number_format((float)$af['balance'], 2, '.', '') ?>"
                                       data-balance="<?= $af['balance'] ?>"
                                       class="fee-amount w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                                       data-sf-id="<?= $af['sf_id'] ?>" disabled>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-dark-bg">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-dark-text text-right">Total:</td>
                            <td class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-dark-text" id="batchTotal">0.00 ETB</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left: Channel and reference -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Method *</label>
                        <select name="channel" id="paymentChannel" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                            <option value="">— Select Method —</option>
                            <?php foreach ($paymentChannels as $key => $label): ?>
                            <option value="<?= $key ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reference</label>
                        <input type="text" name="reference"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                               placeholder="Optional reference">
                    </div>
                </div>

                <!-- Right: Additional fields based on payment method -->
                <div class="space-y-4">
                    <!-- TeleBirr-specific -->
                    <div id="teleBirrFields" class="hidden space-y-4">
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-blue-700 font-medium">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                TeleBirr payments are automatically marked as paid.
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">TeleBirr Transaction ID *</label>
                            <input type="text" name="channel_transaction_id" id="teleBirrTxId"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                                   placeholder="e.g. TB2026030812345">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payer Phone Number</label>
                            <input type="text" name="payer_phone"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                                   placeholder="e.g. 09XXXXXXXX">
                        </div>
                    </div>

                    <!-- Bank fields (transfer / deposit) -->
                    <div id="bankFields" class="hidden space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Type</label>
                            <select name="channel_payment_type"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                                <option value="">— Select —</option>
                                <option value="deposit">Deposit</option>
                                <option value="transfer">Transfer</option>
                                <option value="online">Online Banking</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Depositor / Sender Name</label>
                            <input type="text" name="channel_depositor_name"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                                   placeholder="Name of depositor">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bank / Branch</label>
                            <input type="text" name="channel_depositor_branch"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                                   placeholder="e.g. CBE - Bole Branch">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Transaction / Reference Number</label>
                            <input type="text" name="bank_transaction_id"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                                   placeholder="Transaction reference">
                        </div>
                    </div>

                    <!-- Common notes field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea name="notes" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"
                                  placeholder="Optional notes…"></textarea>
                    </div>
                </div>
            </div>

            <!-- Manual confirmation for non-TeleBirr -->
            <div id="manualConfirm" class="hidden mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="confirm_paid" id="confirmPaidCheck" value="1"
                           class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="text-sm font-medium text-yellow-800">I confirm that this payment has been received and verified.</span>
                </label>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-3 mt-6">
                <a href="<?= url('finance', 'collect-payment') ?>"
                   class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200">
                    Cancel
                </a>
                <button type="submit" id="submitPaymentBtn" disabled
                        class="px-6 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 font-medium inline-flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Record Payment
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Recent Payments for this Student -->
    <?php if ($student && !empty($recentPayments)): ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Recent Payments</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border responsive-table">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Fee</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Channel</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Receipt</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Attachment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php foreach ($recentPayments as $rp): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg">
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted" data-label="Date"><?= format_datetime($rp['created_at']) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Fee"><?= e($rp['fee_desc'] ?? $rp['description'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm font-semibold text-green-600" data-label="Amount"><?= format_money(abs($rp['amount'])) ?></td>
                        <td class="px-4 py-3 text-sm" data-label="Channel">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= ($rp['channel'] ?? '') === 'telebirr' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 dark:bg-dark-card2 text-gray-700 dark:text-gray-300' ?>">
                                <?= ucfirst(str_replace('_', ' ', $rp['channel'] ?? '—')) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted" data-label="Receipt"><?= e($rp['receipt_no'] ?? '—') ?></td>
                        <td class="px-4 py-3" data-label="Attachment">
                            <a href="<?= url('finance', 'payment-attachment', $rp['id']) ?>" target="_blank"
                               class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 text-indigo-700 text-xs rounded-lg hover:bg-indigo-100 font-medium border border-indigo-200">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print Attachment
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; /* end if ($student) */ ?>
</div>

<!-- JavaScript for dynamic form behavior -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var channelSelect = document.getElementById('paymentChannel');
    var teleBirrFields = document.getElementById('teleBirrFields');
    var bankFields = document.getElementById('bankFields');
    var manualConfirm = document.getElementById('manualConfirm');
    var confirmCheck = document.getElementById('confirmPaidCheck');
    var submitBtn = document.getElementById('submitPaymentBtn');
    var teleBirrTxId = document.getElementById('teleBirrTxId');
    var checkAllFees = document.getElementById('checkAllFees');
    var batchTotalEl = document.getElementById('batchTotal');
    var checkboxes = document.querySelectorAll('.fee-checkbox');
    var amountInputs = document.querySelectorAll('.fee-amount');

    if (!channelSelect || !checkboxes.length) return;

    // Check/uncheck all
    if (checkAllFees) {
        checkAllFees.addEventListener('change', function() {
            checkboxes.forEach(function(cb) {
                cb.checked = checkAllFees.checked;
                var sfId = cb.dataset.sfId;
                var amtInput = document.querySelector('.fee-amount[data-sf-id="' + sfId + '"]');
                if (amtInput) amtInput.disabled = !cb.checked;
            });
            updateTotal();
            validateForm();
        });
    }

    // Individual checkbox toggle
    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', function() {
            var sfId = cb.dataset.sfId;
            var amtInput = document.querySelector('.fee-amount[data-sf-id="' + sfId + '"]');
            if (amtInput) amtInput.disabled = !cb.checked;
            updateTotal();
            validateForm();
        });
    });

    // Amount input change
    amountInputs.forEach(function(inp) {
        inp.addEventListener('input', function() {
            updateTotal();
            validateForm();
        });
    });

    // Update running total
    function updateTotal() {
        var total = 0;
        checkboxes.forEach(function(cb) {
            if (cb.checked) {
                var sfId = cb.dataset.sfId;
                var amtInput = document.querySelector('.fee-amount[data-sf-id="' + sfId + '"]');
                if (amtInput) total += parseFloat(amtInput.value) || 0;
            }
        });
        if (batchTotalEl) batchTotalEl.textContent = total.toFixed(2) + ' ETB';
    }

    // Show/hide fields based on payment method
    channelSelect.addEventListener('change', function() {
        var channel = this.value;
        teleBirrFields.classList.add('hidden');
        bankFields.classList.add('hidden');
        manualConfirm.classList.add('hidden');
        if (teleBirrTxId) teleBirrTxId.removeAttribute('required');

        if (channel === 'telebirr') {
            teleBirrFields.classList.remove('hidden');
            if (teleBirrTxId) teleBirrTxId.setAttribute('required', 'required');
            if (confirmCheck) confirmCheck.checked = true;
        } else if (channel === 'bank_transfer' || channel === 'bank_deposit') {
            bankFields.classList.remove('hidden');
            manualConfirm.classList.remove('hidden');
            if (confirmCheck) confirmCheck.checked = false;
        } else if (channel) {
            manualConfirm.classList.remove('hidden');
            if (confirmCheck) confirmCheck.checked = false;
        }
        validateForm();
    });

    // Validate form for submit button
    function validateForm() {
        var anyChecked = false;
        checkboxes.forEach(function(cb) {
            if (cb.checked) {
                var sfId = cb.dataset.sfId;
                var amtInput = document.querySelector('.fee-amount[data-sf-id="' + sfId + '"]');
                if (amtInput && parseFloat(amtInput.value) > 0) anyChecked = true;
            }
        });
        var hasChannel = channelSelect.value !== '';
        var isTeleBirr = channelSelect.value === 'telebirr';
        var isConfirmed = isTeleBirr || (confirmCheck && confirmCheck.checked);
        submitBtn.disabled = !(anyChecked && hasChannel && isConfirmed);
    }

    if (confirmCheck) confirmCheck.addEventListener('change', validateForm);

    // Overpayment warning on submit
    document.getElementById('collectPaymentForm').addEventListener('submit', function(e) {
        var totalOverpay = 0;
        var overpayCount = 0;
        checkboxes.forEach(function(cb) {
            if (cb.checked) {
                var sfId = cb.dataset.sfId;
                var amtInput = document.querySelector('.fee-amount[data-sf-id="' + sfId + '"]');
                if (amtInput) {
                    var amt = parseFloat(amtInput.value) || 0;
                    var bal = parseFloat(amtInput.dataset.balance) || 0;
                    if (amt > bal) {
                        totalOverpay += (amt - bal);
                        overpayCount++;
                    }
                }
            }
        });

        if (totalOverpay > 0) {
            if (!confirm('Total overpayment of ' + totalOverpay.toFixed(2) + ' ETB across ' + overpayCount + ' fee(s) will be credited to the student\'s wallet. Continue?')) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>

<?php
$content = ob_get_clean();
$page_title = $pageTitle;
include TEMPLATES_PATH . '/layout.php';
