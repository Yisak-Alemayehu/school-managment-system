<?php $pageTitle = 'Dashboard'; $currentPage = 'dashboard'; include __DIR__ . '/layout_top.php'; ?>

<!-- Onboarding Progress -->
<div class="bg-white rounded-xl border border-gray-100 p-5 mb-6">
    <h3 class="text-sm font-bold text-gray-900 mb-4">Your Onboarding Progress</h3>
    <?php
    $stageInfo = pipeline_stage_info($school['pipeline_stage']);
    $stages = ['requested','demo_scheduled','demo_completed','interested','agreement_sent','payment_pending','active'];
    $currentStep = $stageInfo['step'];
    $totalSteps = count($stages);
    $progress = ($currentStep / $totalSteps) * 100;
    ?>
    <div class="mb-4">
        <div class="flex items-center justify-between text-sm mb-2">
            <span class="font-semibold text-<?= $stageInfo['color'] ?>-700"><?= $stageInfo['label'] ?></span>
            <span class="text-gray-500">Step <?= $currentStep ?> of <?= $totalSteps ?></span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-3">
            <div class="bg-gradient-to-r from-primary-500 to-primary-600 h-3 rounded-full transition-all duration-500" style="width:<?= $progress ?>%"></div>
        </div>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2 mt-4">
        <?php foreach ($stages as $i => $s):
            $info = pipeline_stage_info($s);
            $isComplete = $info['step'] <= $currentStep;
            $isCurrent = $school['pipeline_stage'] === $s;
        ?>
        <div class="text-center p-2 rounded-lg <?= $isCurrent ? 'bg-primary-50 ring-2 ring-primary-200' : ($isComplete ? 'bg-green-50' : 'bg-gray-50') ?> transition-all">
            <div class="w-6 h-6 mx-auto rounded-full flex items-center justify-center mb-1 <?= $isComplete ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500' ?>">
                <?php if ($isComplete): ?>
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <?php else: ?>
                <span class="text-[10px] font-bold"><?= $info['step'] ?></span>
                <?php endif; ?>
            </div>
            <p class="text-[10px] font-medium <?= $isCurrent ? 'text-primary-700' : ($isComplete ? 'text-green-700' : 'text-gray-400') ?>"><?= $info['label'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <a href="<?= base_url('customer/demo') ?>" class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-all group">
        <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-blue-100 transition-colors">
            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
        </div>
        <h4 class="text-sm font-semibold text-gray-900">Book a Demo</h4>
        <p class="text-xs text-gray-500 mt-1">See the system in action</p>
    </a>
    <a href="<?= base_url('customer/agreement') ?>" class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-all group">
        <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-emerald-100 transition-colors">
            <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <h4 class="text-sm font-semibold text-gray-900">View Agreement</h4>
        <p class="text-xs text-gray-500 mt-1">Review your service terms</p>
    </a>
    <a href="<?= base_url('customer/payments') ?>" class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-all group">
        <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-amber-100 transition-colors">
            <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h4 class="text-sm font-semibold text-gray-900">Payments</h4>
        <p class="text-xs text-gray-500 mt-1">View & submit payments</p>
    </a>
    <a href="<?= base_url('customer/profile') ?>" class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-all group">
        <div class="w-10 h-10 bg-violet-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-violet-100 transition-colors">
            <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <h4 class="text-sm font-semibold text-gray-900">School Profile</h4>
        <p class="text-xs text-gray-500 mt-1">Manage your information</p>
    </a>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <!-- School Info -->
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h3 class="text-sm font-bold text-gray-900 mb-4">School Information</h3>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">School Name</span><span class="font-medium"><?= e($school['name']) ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">Package</span><span class="font-medium"><?= e(ucfirst($school['package'] ?? 'N/A')) ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">Students</span><span class="font-medium"><?= number_format($school['student_count']) ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">Status</span><span class="font-semibold text-<?= $stageInfo['color'] ?>-700"><?= $stageInfo['label'] ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">Registered</span><span class="font-medium"><?= format_date($school['created_at']) ?></span></div>
        </div>
    </div>

    <!-- Recent Notifications -->
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-gray-900">Recent Notifications</h3>
            <a href="<?= base_url('customer/notifications') ?>" class="text-xs text-primary-600 hover:text-primary-700 font-medium">View all &rarr;</a>
        </div>
        <div class="space-y-3">
            <?php foreach (array_slice($notifications, 0, 5) as $notif): ?>
            <div class="flex items-start gap-3 p-2 rounded-lg <?= $notif['is_read'] ? '' : 'bg-primary-50/50' ?>">
                <div class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0 <?= $notif['is_read'] ? 'bg-gray-300' : 'bg-primary-500' ?>"></div>
                <div>
                    <div class="text-sm font-medium text-gray-900"><?= e($notif['title']) ?></div>
                    <div class="text-xs text-gray-500 mt-0.5"><?= time_ago($notif['created_at']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($notifications)): ?>
            <p class="text-sm text-gray-500 text-center py-4">No notifications yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
