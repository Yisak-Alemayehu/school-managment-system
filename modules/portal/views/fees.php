<?php
/**
 * Portal — Parent Fees View
 */

$guardianId  = portal_linked_id();
$activeChild = portal_active_child();

if (!$activeChild) {
    portal_head('Fees', portal_url('dashboard'));
    echo '<div class="card text-center py-12 text-gray-400"><p class="text-4xl mb-3">💰</p>';
    echo '<p class="text-sm">No child selected. Go back to the dashboard.</p></div>';
    portal_foot('fees');
    return;
}

$studentId = (int) $activeChild['id'];

// Fee records
$fees = db_fetch_all(
    "SELECT sf.id, sf.amount, sf.balance,
            (sf.amount - sf.balance) AS paid,
            f.description AS fee_name, f.fee_type, f.effective_date, f.end_date
     FROM fin_student_fees sf
     JOIN fin_fees f ON f.id = sf.fee_id
     WHERE sf.student_id = ? AND sf.is_active = 1
     ORDER BY sf.created_at DESC",
    [$studentId]
);

// Totals
$totalAmount = array_sum(array_column($fees, 'amount'));
$totalPaid   = array_sum(array_column($fees, 'paid'));
$totalDue    = array_sum(array_column($fees, 'balance'));

// Transactions (payments only)
$transactions = db_fetch_all(
    "SELECT tx.amount, tx.channel, tx.receipt_no, tx.description, tx.created_at,
            f.description AS fee_name
     FROM fin_transactions tx
     LEFT JOIN fin_student_fees sf ON sf.id = tx.student_fee_id
     LEFT JOIN fin_fees f ON f.id = sf.fee_id
     WHERE tx.student_id = ? AND tx.type = 'payment'
     ORDER BY tx.created_at DESC LIMIT 20",
    [$studentId]
);

portal_head('Fees', portal_url('dashboard'));

// Helper: status badge from balance
function fee_badge(float $balance): string {
    if ($balance <= 0) return 'badge-green';
    return 'badge-red';
}
function fee_status(float $balance, float $paid): string {
    if ($balance <= 0) return 'Paid';
    if ($paid > 0) return 'Partial';
    return 'Unpaid';
}
?>

<!-- Child info -->
<div class="flex items-center gap-3 mb-5">
  <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center text-xl">🎓</div>
  <div>
    <p class="font-semibold text-gray-900"><?= e($activeChild['full_name']) ?></p>
    <p class="text-xs text-gray-500"><?= e($activeChild['class_name'] ?? '') ?></p>
  </div>
</div>

<!-- Summary card -->
<?php if ($totalAmount > 0): ?>
<div class="card mb-5 <?= $totalDue > 0 ? 'bg-red-50 border-red-100' : 'bg-green-50 border-green-100' ?>">
  <p class="section-title <?= $totalDue > 0 ? 'text-red-700' : 'text-green-700' ?>">Fee Summary</p>
  <div class="space-y-2">
    <div class="flex justify-between text-sm">
      <span class="text-gray-600">Total Fees</span>
      <span class="font-semibold"><?= number_format($totalAmount, 2) ?> ETB</span>
    </div>
    <div class="flex justify-between text-sm">
      <span class="text-gray-600">Amount Paid</span>
      <span class="font-semibold text-green-700"><?= number_format($totalPaid, 2) ?> ETB</span>
    </div>
    <div class="flex justify-between text-sm font-bold border-t border-gray-200 pt-2">
      <span>Balance Due</span>
      <span class="text-xl <?= $totalDue > 0 ? 'text-red-600' : 'text-green-600' ?>">
        <?= number_format($totalDue, 2) ?> ETB
      </span>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Fee breakdown -->
<?php if (!empty($fees)): ?>
<div class="mb-5">
  <p class="section-title">Fee Breakdown</p>
  <div class="card p-0 overflow-hidden divide-y divide-gray-50">
    <?php foreach ($fees as $fee): ?>
    <div class="px-4 py-3 space-y-1.5">
      <?php $bal = (float) $fee['balance']; $pd = (float) $fee['paid']; ?>
      <div class="flex items-center justify-between">
        <p class="text-sm font-semibold text-gray-900"><?= e($fee['fee_name']) ?></p>
        <span class="badge <?= fee_badge($bal) ?>"><?= fee_status($bal, $pd) ?></span>
      </div>
      <div class="flex justify-between text-xs text-gray-500">
        <span>Total: <?= number_format((float) $fee['amount'], 2) ?> ETB</span>
        <span>Paid: <span class="text-green-600 font-semibold"><?= number_format($pd, 2) ?> ETB</span></span>
      </div>
      <?php if ($bal > 0): ?>
      <p class="text-xs text-red-600 font-semibold">
        Balance: <?= number_format($bal, 2) ?> ETB
      </p>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php else: ?>
<div class="card text-center py-10 text-gray-400 mb-5">
  <p class="text-4xl mb-2">💵</p>
  <p class="text-sm">No fee records found.</p>
</div>
<?php endif; ?>

<!-- Payment history -->
<?php if (!empty($transactions)): ?>
<div class="mb-5">
  <p class="section-title">Payment History</p>
  <div class="card p-0 overflow-hidden divide-y divide-gray-50">
    <?php foreach ($transactions as $tx): ?>
    <div class="flex items-center justify-between px-4 py-3">
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-gray-900"><?= e($tx['fee_name']) ?></p>
        <p class="text-xs text-gray-400">
          <?= e(date('d M Y', strtotime($tx['created_at']))) ?>
          <?php if ($tx['channel']): ?> · <?= e(ucfirst($tx['channel'])) ?><?php endif; ?>
        </p>
        <?php if ($tx['receipt_no']): ?>
        <p class="text-xs text-gray-400">Ref: <?= e($tx['receipt_no']) ?></p>
        <?php endif; ?>
      </div>
      <span class="text-sm font-bold text-green-700 flex-shrink-0">
        +<?= number_format((float) $tx['amount'], 2) ?> ETB
      </span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php portal_foot('fees'); ?>
