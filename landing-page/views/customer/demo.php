<?php $pageTitle = 'Book a Demo'; $currentPage = 'demo'; include __DIR__ . '/layout_top.php'; ?>

<?php if (!empty($existingDemo)): ?>
<!-- Existing Demo -->
<div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
        </div>
        <div>
            <h3 class="text-sm font-bold text-gray-900">Your Demo</h3>
            <p class="text-xs text-gray-500">Status: <span class="font-semibold text-<?= $existingDemo['status']==='completed'?'green':($existingDemo['status']==='scheduled'?'blue':'yellow') ?>-700"><?= ucfirst($existingDemo['status']) ?></span></p>
        </div>
    </div>
    <?php if ($existingDemo['scheduled_at']): ?>
    <div class="bg-blue-50 rounded-lg p-4">
        <p class="text-sm text-blue-800"><strong>Scheduled for:</strong> <?= format_datetime($existingDemo['scheduled_at']) ?></p>
        <?php if ($existingDemo['notes']): ?>
        <p class="text-sm text-blue-700 mt-2"><?= e($existingDemo['notes']) ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($existingDemo['status'] === 'completed'): ?>
    <div class="mt-4 bg-green-50 rounded-lg p-4">
        <p class="text-sm text-green-800">Your demo has been completed. Our team will follow up with you about the next steps.</p>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<!-- Book Demo -->
<div class="grid lg:grid-cols-2 gap-6">
    <!-- Info -->
    <div class="bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl p-6 text-white">
        <h3 class="text-lg font-bold mb-2">See Eduelevate in Action</h3>
        <p class="text-sm text-primary-100 mb-4">Book a personalized demo to see how our School Management System can transform your institution.</p>
        <ul class="space-y-3 text-sm">
            <li class="flex items-center gap-2"><svg class="w-4 h-4 text-primary-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> 30-minute live walkthrough</li>
            <li class="flex items-center gap-2"><svg class="w-4 h-4 text-primary-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Tailored to your school size</li>
            <li class="flex items-center gap-2"><svg class="w-4 h-4 text-primary-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Q&A with our team</li>
            <li class="flex items-center gap-2"><svg class="w-4 h-4 text-primary-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> No commitment required</li>
        </ul>
    </div>

    <!-- Booking Form -->
    <div class="bg-white rounded-xl border border-gray-100 p-6">
        <h3 class="text-sm font-bold text-gray-900 mb-4">Select a Time Slot</h3>
        <?php if (!empty($availableSlots)): ?>
        <form method="POST" action="<?= base_url('customer/demo/book') ?>">
            <?= csrf_field() ?>
            <div class="space-y-2 max-h-60 overflow-y-auto mb-4">
                <?php foreach ($availableSlots as $slot): ?>
                <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-primary-300 hover:bg-primary-50/30 cursor-pointer transition-all">
                    <input type="radio" name="slot_id" value="<?= $slot['id'] ?>" required class="text-primary-600">
                    <div>
                        <div class="text-sm font-medium"><?= format_date($slot['date']) ?></div>
                        <div class="text-xs text-gray-500"><?= e($slot['time_start']) ?> - <?= e($slot['time_end']) ?></div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Notes (optional)</label>
                <textarea name="notes" rows="2" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20" placeholder="Any specific questions or topics you'd like covered?"></textarea>
            </div>
            <button type="submit" class="w-full py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-xl hover:bg-primary-700 transition-colors">Book Demo</button>
        </form>
        <?php else: ?>
        <div class="text-center py-8">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <p class="text-sm text-gray-500">No available slots at the moment.</p>
            <p class="text-xs text-gray-400 mt-1">Our team will contact you to schedule a demo.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/layout_bottom.php'; ?>
