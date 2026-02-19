<?php
/**
 * Flash Messages Partial
 */
$flashMessages = get_all_flash();
if (empty($flashMessages)) return;

$types = [
    'success' => ['bg' => 'bg-green-50 border-green-400 text-green-800', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
    'error'   => ['bg' => 'bg-red-50 border-red-400 text-red-800', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
    'warning' => ['bg' => 'bg-yellow-50 border-yellow-400 text-yellow-800', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>'],
    'info'    => ['bg' => 'bg-blue-50 border-blue-400 text-blue-800', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
];
?>

<div class="px-4 md:px-6 pt-4 space-y-2">
    <?php foreach ($flashMessages as $type => $message): 
        $style = $types[$type] ?? $types['info'];
    ?>
    <div class="flex items-start gap-3 px-4 py-3 border-l-4 rounded-r-lg <?= $style['bg'] ?> flash-message" role="alert">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $style['icon'] ?></svg>
        <p class="text-sm flex-1"><?= e($message) ?></p>
        <button onclick="this.parentElement.remove()" class="flex-shrink-0 opacity-50 hover:opacity-100" aria-label="Dismiss">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <?php endforeach; ?>
</div>
