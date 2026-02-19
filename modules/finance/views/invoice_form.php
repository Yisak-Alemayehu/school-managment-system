<?php
/**
 * Finance — Invoice Generation Form
 * Generate invoice for a single student or entire class
 */
$pageTitle = 'Generate Invoice';

$sessionId = get_active_session_id();
$termId    = get_active_term_id();
$classes   = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY name");

ob_start();
?>
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Generate Invoice</h1>
        <a href="<?= url('finance', 'invoices') ?>" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border p-6">
        <form method="POST" action="<?= url('finance', 'invoice-generate') ?>">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class *</label>
                    <select name="class_id" id="invoiceClass" required
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Generate For</label>
                    <select name="scope" id="invoiceScope"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="class">Entire Class</option>
                        <option value="student">Single Student</option>
                    </select>
                </div>

                <div id="studentSelectWrap" class="hidden md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                    <select name="student_id" id="invoiceStudent"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Select student after choosing class</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                    <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Term</label>
                    <select name="term_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <?php
                        $terms = db_fetch_all("SELECT id, name FROM terms WHERE session_id = ? ORDER BY id", [$sessionId]);
                        foreach ($terms as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $t['id'] == $termId ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Fee items preview -->
            <div id="feePreview" class="mt-6 hidden">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Fee Items for Selected Class:</h3>
                <div id="feePreviewBody" class="border rounded-lg divide-y"></div>
                <div class="mt-2 text-right font-semibold text-gray-900">Total: <span id="feeTotal">Br 0.00</span></div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" placeholder="Optional notes for the invoice..."
                          class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="px-6 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">
                    Generate Invoice
                </button>
                <a href="<?= url('finance', 'invoices') ?>" class="px-6 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const scopeSel = document.getElementById('invoiceScope');
    const studentWrap = document.getElementById('studentSelectWrap');
    const classSel = document.getElementById('invoiceClass');
    const studentSel = document.getElementById('invoiceStudent');
    const feePreview = document.getElementById('feePreview');
    const feeBody = document.getElementById('feePreviewBody');
    const feeTotal = document.getElementById('feeTotal');

    scopeSel.addEventListener('change', function() {
        studentWrap.classList.toggle('hidden', this.value !== 'student');
    });

    classSel.addEventListener('change', function() {
        const classId = this.value;
        if (!classId) {
            feePreview.classList.add('hidden');
            return;
        }

        // Load fee structures for preview
        fetch('<?= url('finance', 'fee-structures') ?>&class_id=' + classId + '&ajax=1')
            .then(r => r.json())
            .then(data => {
                feeBody.innerHTML = '';
                let total = 0;
                if (data.length === 0) {
                    feeBody.innerHTML = '<div class="p-3 text-sm text-gray-500">No fee structures found for this class.</div>';
                } else {
                    data.forEach(fee => {
                        total += parseFloat(fee.amount);
                        feeBody.innerHTML += '<div class="flex justify-between p-3 text-sm"><span>' + fee.category_name + '</span><span class="font-semibold">Br ' + parseFloat(fee.amount).toLocaleString('en', {minimumFractionDigits:2}) + '</span></div>';
                    });
                }
                feeTotal.textContent = 'Br ' + total.toLocaleString('en', {minimumFractionDigits:2});
                feePreview.classList.remove('hidden');
            })
            .catch(() => {});

        // Load students for the class
        fetch('?module=students&action=list&class_id=' + classId + '&ajax=1')
            .then(r => r.json())
            .then(students => {
                studentSel.innerHTML = '<option value="">Select Student</option>';
                students.forEach(s => {
                    studentSel.innerHTML += '<option value="' + s.id + '">' + s.first_name + ' ' + s.last_name + ' (' + s.admission_no + ')</option>';
                });
            })
            .catch(() => {});
    });
});
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/templates/layout.php';
