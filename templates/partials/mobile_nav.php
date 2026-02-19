<?php
/**
 * Mobile Bottom Navigation Partial
 */
if (!auth_check()) return;

$user = auth_user();
$isAdmin = auth_is_super_admin() || auth_has_role('admin');

$mobileNav = [
    ['icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Home', 'url' => '/dashboard', 'module' => 'dashboard'],
    ['icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'label' => 'Students', 'url' => '/students', 'module' => 'students'],
    ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'label' => 'Attend.', 'url' => '/attendance', 'module' => 'attendance'],
    ['icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 'label' => 'Messages', 'url' => '/communication', 'module' => 'communication'],
    ['icon' => 'M4 6h16M4 12h16M4 18h7', 'label' => 'More', 'url' => '#', 'module' => '__more', 'onclick' => 'toggleSidebar()'],
];
?>

<nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-20 no-print safe-area-bottom">
    <div class="flex items-center justify-around h-14">
        <?php foreach ($mobileNav as $item):
            $active = route_is($item['module']);
            $activeClass = $active ? 'text-primary-600' : 'text-gray-500';
            $onclick = isset($item['onclick']) ? 'onclick="' . $item['onclick'] . '"' : '';
        ?>
        <a href="<?= $item['url'] === '#' ? '#' : url($item['url']) ?>" <?= $onclick ?> class="flex flex-col items-center justify-center gap-0.5 w-full h-full <?= $activeClass ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>"/></svg>
            <span class="text-[10px] font-medium"><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="safe-area-spacer"></div>
</nav>
<!-- Spacer for bottom nav on mobile -->
<div class="lg:hidden h-14"></div>
