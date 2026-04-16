<?php $pageTitle = e($school['name']); $currentPage = 'schools'; include __DIR__ . '/layout_top.php'; $stageInfo = pipeline_stage_info($school['pipeline_stage']); ?>

<div class="mb-4">
    <a href="<?= base_url('admin/schools') ?>" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Schools
    </a>
</div>

<!-- School Header -->
<div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
    <div class="flex flex-col sm:flex-row items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-primary-100 flex items-center justify-center text-primary-700 text-xl font-bold"><?= strtoupper(substr($school['name'], 0, 1)) ?></div>
            <div>
                <h2 class="text-xl font-bold text-gray-900"><?= e($school['name']) ?></h2>
                <p class="text-sm text-gray-500"><?= e($school['user_email']) ?> · <?= e($school['phone'] ?? '') ?></p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-<?= $stageInfo['color'] ?>-50 text-<?= $stageInfo['color'] ?>-700"><?= $stageInfo['label'] ?></span>
                    <span class="text-xs text-gray-500"><?= e(ucfirst($school['package'] ?? '')) ?> Package</span>
                    <span class="text-xs text-gray-500"><?= number_format($school['student_count']) ?> students</span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <select onchange="updateStage(<?= $school['id'] ?>, this.value)" class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none">
                <?php foreach (['requested','demo_scheduled','demo_completed','interested','agreement_sent','payment_pending','active','churned'] as $s): ?>
                <option value="<?= $s ?>" <?= $school['pipeline_stage'] === $s ? 'selected' : '' ?>><?= pipeline_stage_info($s)['label'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<!-- Pipeline Progress -->
<div class="bg-white rounded-xl border border-gray-100 p-5 mb-6">
    <h3 class="text-sm font-bold text-gray-900 mb-4">Pipeline Progress</h3>
    <div class="flex items-center gap-1">
        <?php
        $stages = ['requested','demo_scheduled','demo_completed','interested','agreement_sent','payment_pending','active'];
        $currentStep = pipeline_stage_info($school['pipeline_stage'])['step'];
        foreach ($stages as $i => $s):
            $info = pipeline_stage_info($s);
            $isComplete = $info['step'] <= $currentStep && $school['pipeline_stage'] !== 'churned';
            $isCurrent = $school['pipeline_stage'] === $s;
        ?>
        <div class="flex-1 text-center">
            <div class="h-2 rounded-full <?= $isComplete ? 'bg-primary-500' : 'bg-gray-100' ?> <?= $isCurrent ? 'ring-2 ring-primary-300' : '' ?>"></div>
            <p class="text-[10px] mt-1 <?= $isCurrent ? 'font-bold text-primary-700' : 'text-gray-400' ?>"><?= $info['label'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <!-- Demos -->
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h3 class="text-sm font-bold text-gray-900 mb-4">Demo History</h3>
        <?php if (empty($demos)): ?>
        <p class="text-sm text-gray-500">No demos scheduled.</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($demos as $demo): ?>
            <div class="p-3 rounded-lg bg-gray-50 flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium"><?= format_datetime($demo['scheduled_at']) ?></div>
                    <div class="text-xs text-gray-500">Status: <?= e(ucfirst($demo['status'])) ?></div>
                </div>
                <select onchange="updateDemoStatus(<?= $demo['id'] ?>, this.value)" class="text-xs px-2 py-1 rounded border border-gray-200 bg-white">
                    <?php foreach (['pending','scheduled','completed','cancelled','no_show'] as $ds): ?>
                    <option value="<?= $ds ?>" <?= $demo['status'] === $ds ? 'selected' : '' ?>><?= ucfirst($ds) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Agreements -->
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h3 class="text-sm font-bold text-gray-900 mb-4">Agreements</h3>
        <?php if (empty($agreements)): ?>
        <p class="text-sm text-gray-500">No agreements sent.</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($agreements as $agr): ?>
            <div class="p-3 rounded-lg bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium"><?= e($agr['title']) ?></div>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-<?= $agr['status'] === 'accepted' ? 'green' : ($agr['status'] === 'rejected' ? 'red' : 'yellow') ?>-50 text-<?= $agr['status'] === 'accepted' ? 'green' : ($agr['status'] === 'rejected' ? 'red' : 'yellow') ?>-700"><?= ucfirst($agr['status']) ?></span>
                </div>
                <div class="text-xs text-gray-500 mt-1">Sent: <?= format_date($agr['sent_at']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Payments -->
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h3 class="text-sm font-bold text-gray-900 mb-4">Payments</h3>
        <?php if (empty($payments)): ?>
        <p class="text-sm text-gray-500">No payments created.</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($payments as $pay): ?>
            <div class="p-3 rounded-lg bg-gray-50 flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium"><?= format_etb($pay['amount']) ?></div>
                    <div class="text-xs text-gray-500"><?= e(ucfirst($pay['payment_type'])) ?> · Due: <?= format_date($pay['due_date']) ?></div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-<?= $pay['status'] === 'verified' ? 'green' : ($pay['status'] === 'paid' ? 'blue' : 'yellow') ?>-50 text-<?= $pay['status'] === 'verified' ? 'green' : ($pay['status'] === 'paid' ? 'blue' : 'yellow') ?>-700"><?= ucfirst($pay['status']) ?></span>
                    <?php if ($pay['status'] === 'paid'): ?>
                    <button onclick="verifyPayment(<?= $pay['id'] ?>, 'verify')" class="text-xs bg-green-600 text-white px-2 py-1 rounded-lg hover:bg-green-700">Verify</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Send Notification -->
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h3 class="text-sm font-bold text-gray-900 mb-4">Send Notification</h3>
        <form method="POST" action="<?= base_url('admin/send-notification') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= $school['user_id'] ?>">
            <div class="space-y-3">
                <input type="text" name="title" placeholder="Title" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                <textarea name="message" placeholder="Message" required rows="3" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">Send</button>
            </div>
        </form>
    </div>
</div>

<script>
const csrf = '<?= e(Auth::generateCsrfToken()) ?>';
function updateStage(id, stage) {
    const fd = new FormData(); fd.append('school_id', id); fd.append('stage', stage); fd.append('csrf_token', csrf);
    fetch('<?= base_url('admin/schools/update-stage') ?>', { method:'POST', body:fd }).then(r=>r.json()).then(d=>{if(d.success) location.reload();});
}
function updateDemoStatus(id, status) {
    const fd = new FormData(); fd.append('demo_id', id); fd.append('status', status); fd.append('csrf_token', csrf);
    fetch('<?= base_url('admin/demos/update-status') ?>', { method:'POST', body:fd }).then(r=>r.json()).then(d=>{if(d.success) location.reload();});
}
function verifyPayment(id, action) {
    const fd = new FormData(); fd.append('payment_id', id); fd.append('action', action); fd.append('csrf_token', csrf);
    fetch('<?= base_url('admin/payments/verify') ?>', { method:'POST', body:fd }).then(r=>r.json()).then(d=>{if(d.success) location.reload();});
}
</script>

<?php include __DIR__ . '/layout_bottom.php'; ?>
