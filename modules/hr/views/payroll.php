<?php
/**
 * HR — Payroll Periods View
 */

require_once APP_ROOT . '/core/ethiopian_calendar.php';

$periods = db_fetch_all(
    "SELECT pp.*, 
            (SELECT COUNT(*) FROM hr_payroll_records pr WHERE pr.payroll_period_id = pp.id) AS record_count,
            (SELECT SUM(pr.net_salary) FROM hr_payroll_records pr WHERE pr.payroll_period_id = pp.id) AS total_net
     FROM hr_payroll_periods pp
     ORDER BY pp.year_ec DESC, pp.month_ec DESC"
);

$ecMonths = ec_month_names();

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Payroll</h1>
        <button onclick="openPeriodModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Payroll Period
        </button>
    </div>

    <!-- Payroll Periods -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($periods as $p): ?>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-dark-text">
                        <?= e(ec_month_name((int)$p['month_ec'])) ?> <?= e($p['year_ec']) ?>
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        <?= e($p['start_date']) ?> — <?= e($p['end_date']) ?>
                    </p>
                </div>
                <?php
                $statusBadge = [
                    'draft'    => 'bg-gray-100 text-gray-600',
                    'generated'=> 'bg-blue-100 text-blue-700',
                    'approved' => 'bg-green-100 text-green-700',
                    'paid'     => 'bg-purple-100 text-purple-700',
                ];
                ?>
                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $statusBadge[$p['status']] ?? 'bg-gray-100 text-gray-600' ?>">
                    <?= ucfirst($p['status']) ?>
                </span>
            </div>

            <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                <div>
                    <span class="text-gray-500 dark:text-dark-muted">Employees</span>
                    <p class="font-medium text-gray-900 dark:text-dark-text"><?= (int)$p['record_count'] ?></p>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-dark-muted">Total Net</span>
                    <p class="font-medium text-gray-900 dark:text-dark-text"><?= number_format($p['total_net'] ?? 0, 2) ?> Br</p>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-dark-muted">Working Days</span>
                    <p class="font-medium text-gray-900 dark:text-dark-text"><?= (int)($p['working_days'] ?? 0) ?></p>
                </div>
            </div>

            <div class="flex gap-2 pt-3 border-t border-gray-100 dark:border-dark-border">
                <?php if ($p['status'] === 'draft'): ?>
                <form method="POST" action="<?= url('hr', 'payroll-generate') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="payroll_period_id" value="<?= $p['id'] ?>">
                    <button class="text-xs text-blue-600 hover:text-blue-800 font-medium" onclick="return confirm('Generate payroll for all active employees?')">Generate</button>
                </form>
                <?php endif; ?>

                <?php if ($p['status'] === 'generated'): ?>
                <form method="POST" action="<?= url('hr', 'payroll-approve') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button class="text-xs text-green-600 hover:text-green-800 font-medium">Approve</button>
                </form>
                <?php endif; ?>

                <?php if ($p['status'] === 'approved'): ?>
                <form method="POST" action="<?= url('hr', 'payroll-approve') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="action" value="mark_paid">
                    <button class="text-xs text-purple-600 hover:text-purple-800 font-medium">Mark Paid</button>
                </form>
                <?php endif; ?>

                <?php if ((int)$p['record_count'] > 0): ?>
                <a href="<?= url('hr', 'payroll-detail', $p['id']) ?>" class="text-xs text-primary-600 hover:text-primary-800 font-medium">View Details</a>
                <a href="<?= url('hr', 'payroll-bank-sheet', $p['id']) ?>" class="text-xs text-gray-600 hover:text-gray-800 font-medium">Bank Sheet</a>
                <a href="<?= url('hr', 'payroll-pension-sheet', $p['id']) ?>" class="text-xs text-gray-600 hover:text-gray-800 font-medium">Pension</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($periods)): ?>
        <div class="col-span-full text-center py-8 text-gray-400">No payroll periods created yet.</div>
        <?php endif; ?>
    </div>
</div>

<!-- New Period Modal -->
<div id="periodModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-dark-card rounded-xl w-full max-w-sm p-6 m-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-dark-text mb-4">New Payroll Period</h3>
        <form method="POST" action="<?= url('hr', 'payroll-period-save') ?>">
            <?= csrf_field() ?>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Ethiopian Month *</label>
                    <select name="month_ec" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <?php foreach ($ecMonths as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $num == ec_current_month() ? 'selected' : '' ?>><?= $num ?>. <?= e($name['en'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Ethiopian Year *</label>
                    <input type="number" name="year_ec" required value="<?= ec_current_year() ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" onclick="closePeriodModal()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">Create Period</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPeriodModal() { document.getElementById('periodModal').classList.remove('hidden'); }
function closePeriodModal() { document.getElementById('periodModal').classList.add('hidden'); }
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
