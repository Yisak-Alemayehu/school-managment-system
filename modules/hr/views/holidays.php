<?php
/**
 * HR — Holidays View
 */

require_once APP_ROOT . '/core/ethiopian_calendar.php';

$year = input_int('year') ?: (int)date('Y');
$holidays = db_fetch_all("SELECT * FROM hr_holidays WHERE year = ? ORDER BY date_gregorian", [$year]);

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Public Holidays</h1>
        <button onclick="openHolModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Holiday
        </button>
    </div>

    <!-- Year Filter -->
    <form method="GET" action="<?= url('hr', 'holidays') ?>" class="flex items-center gap-3">
        <label class="text-sm font-medium text-gray-700 dark:text-dark-muted">Year:</label>
        <select name="year" onchange="this.form.submit()" class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
            <?php for ($y = $year - 2; $y <= $year + 2; $y++): ?>
            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>

    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border">
            <thead class="bg-gray-50 dark:bg-dark-bg">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Name (አማርኛ)</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Date (EC)</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Date (GC)</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Day</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                <?php if (empty($holidays)): ?>
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No holidays for <?= $year ?>.</td></tr>
                <?php else: $n = 0; foreach ($holidays as $h): $n++; ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg">
                    <td class="px-4 py-3 text-sm text-gray-500"><?= $n ?></td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($h['name']) ?></td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted"><?= e($h['name_am'] ?? '—') ?></td>
                    <td class="px-4 py-3 text-sm font-mono text-gray-600 dark:text-dark-muted"><?= e($h['date_ec'] ?? '—') ?></td>
                    <td class="px-4 py-3 text-sm font-mono text-gray-600 dark:text-dark-muted"><?= e($h['date_gregorian']) ?></td>
                    <td class="px-4 py-3 text-sm text-gray-500"><?= date('l', strtotime($h['date_gregorian'])) ?></td>
                    <td class="px-4 py-3 text-sm flex gap-2">
                        <button onclick='editHol(<?= json_encode($h) ?>)' class="text-amber-600 hover:text-amber-800 text-xs font-medium">Edit</button>
                        <form method="POST" action="<?= url('hr', 'holiday-save') ?>" onsubmit="return confirm('Delete this holiday?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $h['id'] ?>">
                            <input type="hidden" name="_delete" value="1">
                            <!-- Simple delete via save handler or dedicated route -->
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-500 dark:text-dark-muted">Total: <?= count($holidays) ?> holiday(s) for <?= $year ?></p>
</div>

<!-- Holiday Modal -->
<div id="holModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-dark-card rounded-xl w-full max-w-md p-6 m-4">
        <h3 id="holTitle" class="text-lg font-semibold text-gray-900 dark:text-dark-text mb-4">Add Holiday</h3>
        <form method="POST" action="<?= url('hr', 'holiday-save') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="hol_id" value="">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Name (English) *</label>
                    <input type="text" name="name" id="hol_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Name (አማርኛ)</label>
                    <input type="text" name="name_am" id="hol_name_am" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Date (EC — DD/MM/YYYY)</label>
                    <input type="text" name="date_ec" id="hol_ec" placeholder="DD/MM/YYYY" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                    <p class="text-xs text-gray-400 mt-1">Provide either EC or GC date — the other will be auto-calculated.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Date (GC)</label>
                    <input type="date" name="date_gregorian" id="hol_gc" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Year</label>
                    <input type="number" name="year" id="hol_year" value="<?= $year ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-dark-muted">
                    <input type="checkbox" name="is_recurring" id="hol_recur" value="1" class="rounded"> Recurring every year
                </label>
            </div>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" onclick="closeHolModal()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openHolModal() {
    document.getElementById('holTitle').textContent = 'Add Holiday';
    document.getElementById('hol_id').value = '';
    document.getElementById('hol_name').value = '';
    document.getElementById('hol_name_am').value = '';
    document.getElementById('hol_ec').value = '';
    document.getElementById('hol_gc').value = '';
    document.getElementById('hol_year').value = '<?= $year ?>';
    document.getElementById('hol_recur').checked = false;
    document.getElementById('holModal').classList.remove('hidden');
}
function editHol(h) {
    document.getElementById('holTitle').textContent = 'Edit Holiday';
    document.getElementById('hol_id').value = h.id;
    document.getElementById('hol_name').value = h.name;
    document.getElementById('hol_name_am').value = h.name_am || '';
    document.getElementById('hol_ec').value = h.date_ec || '';
    document.getElementById('hol_gc').value = h.date_gregorian || '';
    document.getElementById('hol_year').value = h.year;
    document.getElementById('hol_recur').checked = !!parseInt(h.is_recurring);
    document.getElementById('holModal').classList.remove('hidden');
}
function closeHolModal() { document.getElementById('holModal').classList.add('hidden'); }
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
