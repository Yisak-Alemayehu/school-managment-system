<?php $pageTitle = 'Dashboard'; $currentPage = 'dashboard'; include __DIR__ . '/layout_top.php'; ?>

<!-- Stats Grid -->
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
    <?php
    $statCards = [
        ['label' => 'Total Schools', 'value' => $stats['total_schools'], 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'color' => 'primary'],
        ['label' => 'Active Schools', 'value' => $stats['active_schools'], 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'green'],
        ['label' => 'Pending Demos', 'value' => $stats['pending_demos'], 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z', 'color' => 'blue'],
        ['label' => 'Pending Payments', 'value' => $stats['pending_payments'], 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'amber'],
        ['label' => 'Revenue', 'value' => format_etb($stats['total_revenue']), 'icon' => 'M9 8h6m-5 0a3 3 0 110 6H9l3 3m-3-6h6m6 1a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'emerald'],
        ['label' => 'New Submissions', 'value' => $stats['new_submissions'], 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'color' => 'violet'],
    ];
    foreach ($statCards as $card):
    ?>
    <div class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-9 h-9 bg-<?= $card['color'] ?>-50 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-<?= $card['color'] ?>-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $card['icon'] ?>"/></svg>
            </div>
        </div>
        <div class="text-xl font-bold text-gray-900"><?= is_numeric($card['value']) ? number_format($card['value']) : $card['value'] ?></div>
        <div class="text-xs text-gray-500 mt-0.5"><?= $card['label'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pipeline Overview -->
<div class="bg-white rounded-xl border border-gray-100 p-5 mb-6">
    <h3 class="text-sm font-bold text-gray-900 mb-4">Pipeline Overview</h3>
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2">
        <?php
        $pipelineCounts = [];
        foreach ($pipelineData as $p) $pipelineCounts[$p['pipeline_stage']] = $p['count'];
        $allStages = ['requested','demo_scheduled','demo_completed','interested','agreement_sent','payment_pending','active','churned'];
        foreach ($allStages as $stage):
            $info = pipeline_stage_info($stage);
            $count = $pipelineCounts[$stage] ?? 0;
        ?>
        <a href="<?= base_url('admin/schools?stage=' . $stage) ?>" class="text-center p-3 rounded-lg bg-<?= $info['color'] ?>-50 hover:bg-<?= $info['color'] ?>-100 transition-colors">
            <div class="text-lg font-bold text-<?= $info['color'] ?>-700"><?= $count ?></div>
            <div class="text-[10px] font-medium text-<?= $info['color'] ?>-600 mt-0.5"><?= $info['label'] ?></div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <!-- Recent Schools -->
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-gray-900">Recent Schools</h3>
            <a href="<?= base_url('admin/schools') ?>" class="text-xs text-primary-600 hover:text-primary-700 font-medium">View all &rarr;</a>
        </div>
        <div class="space-y-3">
            <?php foreach (array_slice($recentSchools, 0, 5) as $school):
                $stageInfo = pipeline_stage_info($school['pipeline_stage']);
            ?>
            <a href="<?= base_url('admin/schools/' . $school['id']) ?>" class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 text-xs font-bold">
                        <?= strtoupper(substr($school['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-900"><?= e($school['name']) ?></div>
                        <div class="text-xs text-gray-500"><?= e($school['user_email']) ?></div>
                    </div>
                </div>
                <span class="text-[10px] font-semibold px-2 py-1 rounded-full bg-<?= $stageInfo['color'] ?>-50 text-<?= $stageInfo['color'] ?>-700"><?= $stageInfo['label'] ?></span>
            </a>
            <?php endforeach; ?>
            <?php if (empty($recentSchools)): ?>
            <p class="text-sm text-gray-500 text-center py-4">No schools registered yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h3 class="text-sm font-bold text-gray-900 mb-4">Recent Activity</h3>
        <div class="space-y-3">
            <?php foreach (array_slice($recentActivity, 0, 8) as $act): ?>
            <div class="flex items-start gap-3 text-sm">
                <div class="w-2 h-2 rounded-full bg-primary-400 mt-1.5 flex-shrink-0"></div>
                <div>
                    <span class="font-medium text-gray-900"><?= e($act['user_name'] ?? 'System') ?></span>
                    <span class="text-gray-500"> <?= e($act['action']) ?></span>
                    <span class="block text-xs text-gray-400 mt-0.5"><?= time_ago($act['created_at']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($recentActivity)): ?>
            <p class="text-sm text-gray-500 text-center py-4">No activity yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout_bottom.php'; ?>
