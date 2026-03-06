<?php
/**
 * Mobile Bottom Navigation Partial
 * Role-specific bottom navigation with app-like feel
 */
if (!auth_check()) return;

$user = auth_user();
$isSuperAdmin = auth_is_super_admin();
$isAdmin = $isSuperAdmin || auth_has_role('admin') || auth_has_role('school_admin');
$isTeacher = auth_has_role('teacher');
$isStudent = auth_has_role('student');
$isParent = auth_has_role('parent');

// Role-specific nav items
if ($isStudent) {
    $mobileNav = [
        ['icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Home', 'url' => '/dashboard', 'module' => 'dashboard'],
        ['icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'label' => 'Schedule', 'url' => '/academics/my-timetable', 'module' => 'academics'],
        ['icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', 'label' => 'Messages', 'url' => '/messaging/inbox', 'module' => 'messaging'],
        ['icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'label' => 'Profile', 'url' => '/auth/profile', 'module' => 'auth'],
    ];
} elseif ($isTeacher) {
    $mobileNav = [
        ['icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Home', 'url' => '/dashboard', 'module' => 'dashboard'],
        ['icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'label' => 'Schedule', 'url' => '/academics/my-timetable', 'module' => 'academics'],
        ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'label' => 'Attend.', 'url' => '/attendance', 'module' => 'attendance'],
        ['icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'label' => 'Profile', 'url' => '/auth/profile', 'module' => 'auth'],
    ];
} elseif ($isParent) {
    $mobileNav = [
        ['icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Home', 'url' => '/dashboard', 'module' => 'dashboard'],
        ['icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'label' => 'Children', 'url' => '/students/details', 'module' => 'students'],
        ['icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', 'label' => 'Messages', 'url' => '/messaging/inbox', 'module' => 'messaging'],
        ['icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'label' => 'Profile', 'url' => '/auth/profile', 'module' => 'auth'],
    ];
} else {
    // Admin / Super Admin / Default
    $mobileNav = [
        ['icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Home', 'url' => '/dashboard', 'module' => 'dashboard'],
        ['icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'label' => 'Students', 'url' => '/students', 'module' => 'students'],
        ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'label' => 'Attend.', 'url' => '/attendance', 'module' => 'attendance'],
        ['icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', 'label' => 'Messages', 'url' => '/messaging/inbox', 'module' => 'messaging'],
        ['icon' => 'M4 6h16M4 12h16M4 18h7', 'label' => 'More', 'url' => '#', 'module' => '__more', 'onclick' => 'toggleSidebar()'],
    ];
}
?>

<nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-dark-card border-t border-gray-200 dark:border-dark-border z-20 no-print safe-area-bottom shadow-[0_-2px_10px_rgba(0,0,0,0.05)]">
    <div class="flex items-center justify-around h-16 max-w-lg mx-auto">
        <?php foreach ($mobileNav as $item):
            $active = route_is($item['module']);
            $onclick = isset($item['onclick']) ? 'onclick="' . $item['onclick'] . '"' : '';
        ?>
        <a href="<?= $item['url'] === '#' ? '#' : url($item['url']) ?>" <?= $onclick ?> class="relative flex flex-col items-center justify-center gap-1 w-full h-full group">
            <div class="<?= $active ? 'bg-primary-50 dark:bg-primary-900/20' : '' ?> rounded-2xl px-4 py-1 transition-all">
                <svg class="w-5 h-5 <?= $active ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-dark-muted group-hover:text-gray-600 dark:group-hover:text-gray-300' ?> transition-colors" fill="none" stroke="currentColor" stroke-width="<?= $active ? '2.5' : '2' ?>" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/></svg>
            </div>
            <span class="text-[10px] font-medium <?= $active ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-dark-muted' ?>"><?= $item['label'] ?></span>
            <?php if ($item['module'] === 'messaging'):
                $mobileUnread = get_unread_message_count();
                if ($mobileUnread > 0): ?>
            <span id="mobile-msg-badge" class="absolute top-1 right-1/4 bg-red-500 text-white text-[8px] font-bold w-4 h-4 flex items-center justify-center rounded-full shadow-sm"><?= $mobileUnread > 9 ? '9+' : $mobileUnread ?></span>
            <?php endif; endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="safe-area-spacer"></div>
</nav>
<!-- Spacer for bottom nav on mobile -->
<div class="lg:hidden h-16"></div>
