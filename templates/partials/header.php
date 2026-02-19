<?php
/**
 * Top Header Partial
 */
$user = auth_user();
$unreadNotifs = get_unread_notification_count();
?>
<header class="sticky top-0 z-20 bg-white border-b border-gray-200 no-print">
    <div class="flex items-center justify-between h-14 px-4 md:px-6">
        <!-- Left: hamburger + page title -->
        <div class="flex items-center gap-3">
            <button onclick="toggleSidebar()" class="lg:hidden p-1.5 rounded-lg text-gray-500 hover:bg-gray-100" aria-label="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h2 class="text-lg font-semibold text-gray-800 hidden sm:block"><?= e($page_title ?? 'Dashboard') ?></h2>
        </div>

        <!-- Right: notifications + profile -->
        <div class="flex items-center gap-2 md:gap-3">
            <!-- Notifications -->
            <a href="<?= url('/communication/notifications') ?>" class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100" title="Notifications">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <?php if ($unreadNotifs > 0): ?>
                <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold w-4.5 h-4.5 flex items-center justify-center rounded-full"><?= $unreadNotifs > 9 ? '9+' : $unreadNotifs ?></span>
                <?php endif; ?>
            </a>

            <!-- Profile dropdown -->
            <div class="relative" id="profile-dropdown">
                <button onclick="toggleProfileMenu()" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center flex-shrink-0">
                        <?php if ($user['avatar']): ?>
                            <img src="<?= url('/uploads/' . e($user['avatar'])) ?>" alt="" class="w-8 h-8 rounded-full object-cover">
                        <?php else: ?>
                            <span class="text-white text-sm font-semibold"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="hidden md:block text-left">
                        <p class="text-sm font-medium text-gray-700 leading-tight"><?= e($user['name']) ?></p>
                        <p class="text-xs text-gray-500 leading-tight"><?= e(ucfirst($user['roles'][0] ?? 'User')) ?></p>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <!-- Dropdown menu -->
                <div id="profile-menu" class="hidden absolute right-0 mt-1 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                    <div class="px-4 py-2 border-b border-gray-100">
                        <p class="text-sm font-medium text-gray-800"><?= e($user['name']) ?></p>
                        <p class="text-xs text-gray-500"><?= e($user['email']) ?></p>
                    </div>
                    <a href="<?= url('/users/profile') ?>" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        My Profile
                    </a>
                    <a href="<?= url('/users/change-password') ?>" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        Change Password
                    </a>
                    <?php if (auth_is_super_admin()): ?>
                    <a href="<?= url('/settings') ?>" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Settings
                    </a>
                    <?php endif; ?>
                    <div class="border-t border-gray-100 mt-1"></div>
                    <form method="POST" action="<?= url('/auth/logout') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
