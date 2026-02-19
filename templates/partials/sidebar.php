<?php
/**
 * Sidebar Navigation Partial
 */
$user = auth_user();
$roles = $user['roles'];
$isAdmin = auth_is_super_admin() || auth_has_role('admin');
$isTeacher = auth_has_role('teacher');
$isAccountant = auth_has_role('accountant');
$isStudent = auth_has_role('student');
$isParent = auth_has_role('parent');

$navItems = [];

// Dashboard — everyone
$navItems[] = ['icon' => 'home', 'label' => 'Dashboard', 'url' => '/dashboard', 'module' => 'dashboard'];

// Academics — admin/teacher
if ($isAdmin || $isTeacher) {
    $navItems[] = ['icon' => 'academic-cap', 'label' => 'Academics', 'url' => '/academics', 'module' => 'academics'];
}

// Students — admin/teacher/parent
if ($isAdmin || $isTeacher || $isParent) {
    $navItems[] = ['icon' => 'users', 'label' => 'Students', 'url' => '/students', 'module' => 'students'];
}

// Attendance — admin/teacher/student/parent
if ($isAdmin || $isTeacher || $isStudent || $isParent) {
    $navItems[] = ['icon' => 'clipboard-check', 'label' => 'Attendance', 'url' => '/attendance', 'module' => 'attendance'];
}

// Exams & Marks — admin/teacher/student/parent
if ($isAdmin || $isTeacher || $isStudent || $isParent) {
    $navItems[] = ['icon' => 'document-text', 'label' => 'Exams', 'url' => '/exams', 'module' => 'exams'];
}

// Finance — admin/accountant/student/parent
if ($isAdmin || $isAccountant || $isStudent || $isParent) {
    $navItems[] = ['icon' => 'currency-dollar', 'label' => 'Finance', 'url' => '/finance', 'module' => 'finance'];
}

// Communication — everyone
$navItems[] = ['icon' => 'chat-alt', 'label' => 'Messages', 'url' => '/communication', 'module' => 'communication'];

// Users — admin only
if ($isAdmin) {
    $navItems[] = ['icon' => 'user-group', 'label' => 'Users', 'url' => '/users', 'module' => 'users'];
}

// Reports — admin/accountant
if ($isAdmin || $isAccountant) {
    $navItems[] = ['icon' => 'chart-bar', 'label' => 'Reports', 'url' => '/reports', 'module' => 'reports'];
}

// Settings — admin
if ($isAdmin) {
    $navItems[] = ['icon' => 'cog', 'label' => 'Settings', 'url' => '/settings', 'module' => 'settings'];
}
?>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-sidebar-bg sidebar-transition transform -translate-x-full lg:translate-x-0 overflow-y-auto no-print">
    <!-- Logo / School Name -->
    <div class="flex items-center gap-3 px-4 h-16 border-b border-white/10">
        <div class="w-9 h-9 rounded-lg bg-primary-600 flex items-center justify-center flex-shrink-0">
            <span class="text-white font-bold text-lg">U</span>
        </div>
        <div class="min-w-0">
            <h1 class="text-white font-semibold text-sm truncate"><?= e(get_school_name()) ?></h1>
            <p class="text-sidebar-text text-xs truncate">School ERP</p>
        </div>
        <button onclick="toggleSidebar()" class="lg:hidden ml-auto text-sidebar-text hover:text-white p-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="mt-2 px-3 pb-4 space-y-1">
        <?php foreach ($navItems as $item):
            $active = route_is($item['module']);
            $activeClass = $active ? 'bg-sidebar-active text-white' : 'text-sidebar-text hover:bg-sidebar-hover hover:text-white';
        ?>
        <a href="<?= url($item['url']) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= $activeClass ?>">
            <?= sidebar_icon($item['icon']) ?>
            <span><?= e($item['label']) ?></span>
            <?php if ($item['module'] === 'communication'): ?>
                <?php $unread = get_unread_notification_count(); if ($unread > 0): ?>
                <span class="ml-auto bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full"><?= $unread ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- Session info -->
    <?php $activeSession = get_active_session(); ?>
    <?php if ($activeSession): ?>
    <div class="mx-3 mb-4 p-3 rounded-lg bg-white/5 border border-white/10">
        <p class="text-xs text-sidebar-text">Active Session</p>
        <p class="text-sm text-white font-medium"><?= e($activeSession['name']) ?></p>
        <?php $activeTerm = get_active_term(); if ($activeTerm): ?>
        <p class="text-xs text-primary-400 mt-0.5"><?= e($activeTerm['name']) ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</aside>

<?php
function sidebar_icon(string $name): string {
    $icons = [
        'home'            => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
        'academic-cap'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>',
        'users'           => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
        'clipboard-check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
        'document-text'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'currency-dollar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'chat-alt'        => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',
        'user-group'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
        'chart-bar'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
        'cog'             => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
    ];

    $path = $icons[$name] ?? $icons['home'];
    return '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">' . $path . '</svg>';
}
?>
