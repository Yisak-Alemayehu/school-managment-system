<?php
/**
 * Top Header Partial
 */
$user = auth_user();
$unreadNotifs = get_unread_notification_count();
$unreadMsgs = get_unread_message_count();
$currentLang = get_lang();
$languages = get_languages();
?>
<header class="sticky top-0 z-20 bg-white dark:bg-dark-card border-b border-gray-200 dark:border-dark-border no-print transition-colors glass-header">
    <div class="flex items-center justify-between h-14 px-4 md:px-6">
        <!-- Left: hamburger + page title -->
        <div class="flex items-center gap-3">
            <button onclick="toggleSidebar()" class="lg:hidden p-1.5 rounded-lg text-gray-500 dark:text-dark-muted hover:bg-gray-100 dark:hover:bg-dark-card2" aria-label="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-dark-text hidden sm:block"><?= e($page_title ?? __('dashboard')) ?></h2>
        </div>

        <!-- Right: language + theme + notifications + profile -->
        <div class="flex items-center gap-1 md:gap-2">
            <!-- Language Switcher -->
            <div class="relative" id="lang-dropdown">
                <button onclick="toggleLangMenu()" class="flex items-center gap-1 px-2 py-1.5 rounded-lg text-xs font-medium text-gray-600 dark:text-dark-muted hover:bg-gray-100 dark:hover:bg-dark-card2 transition-colors" title="<?= __('language') ?>">
                    <span><?= $currentLang === 'am' ? '🇪🇹' : '🇬🇧' ?></span>
                    <span class="hidden sm:inline"><?= $currentLang === 'am' ? 'አማ' : 'EN' ?></span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="lang-menu" class="hidden absolute right-0 mt-1 w-36 bg-white dark:bg-dark-card rounded-lg shadow-lg border border-gray-200 dark:border-dark-border py-1 z-50">
                    <?php foreach ($languages as $code => $lang): ?>
                    <a href="?lang=<?= $code ?>" class="flex items-center gap-2 px-3 py-2 text-sm <?= $code === $currentLang ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 font-medium' : 'text-gray-700 dark:text-dark-text hover:bg-gray-50 dark:hover:bg-dark-card2' ?>">
                        <span><?= $lang['flag'] ?></span>
                        <span><?= $lang['native'] ?></span>
                        <?php if ($code === $currentLang): ?>
                        <svg class="w-4 h-4 ml-auto text-primary-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Theme Toggle -->
            <button onclick="toggleTheme()" class="p-2 rounded-lg text-gray-500 dark:text-dark-muted hover:bg-gray-100 dark:hover:bg-dark-card2 transition-colors" title="<?= __('theme') ?>" id="theme-toggle-btn">
                <!-- Sun icon (shown in dark mode) -->
                <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <!-- Moon icon (shown in light mode) -->
                <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            </button>

            <!-- Messages -->
            <a href="<?= url('messaging', 'inbox') ?>" class="relative p-2 rounded-lg text-gray-500 dark:text-dark-muted hover:bg-gray-100 dark:hover:bg-dark-card2" title="<?= __('messages') ?>" id="header-msg-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <?php if ($unreadMsgs > 0): ?>
                <span id="header-msg-badge" class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold w-4.5 h-4.5 flex items-center justify-center rounded-full"><?= $unreadMsgs > 9 ? '9+' : $unreadMsgs ?></span>
                <?php else: ?>
                <span id="header-msg-badge" class="hidden absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold w-4.5 h-4.5 flex items-center justify-center rounded-full"></span>
                <?php endif; ?>
            </a>
            <!-- Notifications -->
            <a href="<?= url('/communication/notifications') ?>" class="relative p-2 rounded-lg text-gray-500 dark:text-dark-muted hover:bg-gray-100 dark:hover:bg-dark-card2" title="<?= __('notifications') ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <?php if ($unreadNotifs > 0): ?>
                <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold w-4.5 h-4.5 flex items-center justify-center rounded-full"><?= $unreadNotifs > 9 ? '9+' : $unreadNotifs ?></span>
                <?php endif; ?>
            </a>

            <!-- Profile dropdown -->
            <div class="relative" id="profile-dropdown">
                <button onclick="toggleProfileMenu()" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-dark-card2 transition-colors">
                    <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center flex-shrink-0">
                        <?php if ($user['avatar']): ?>
                            <img src="<?= url('/uploads/' . e($user['avatar'])) ?>" alt="" class="w-8 h-8 rounded-full object-cover">
                        <?php else: ?>
                            <span class="text-white text-sm font-semibold"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="hidden md:block text-left">
                        <p class="text-sm font-medium text-gray-700 dark:text-dark-text leading-tight"><?= e($user['name']) ?></p>
                        <p class="text-xs text-gray-500 dark:text-dark-muted leading-tight"><?= e(ucfirst($user['roles'][0] ?? 'User')) ?></p>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 dark:text-dark-muted hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <!-- Dropdown menu -->
                <div id="profile-menu" class="hidden absolute right-0 mt-1 w-56 bg-white dark:bg-dark-card rounded-lg shadow-lg border border-gray-200 dark:border-dark-border py-1 z-50">
                    <div class="px-4 py-2 border-b border-gray-100 dark:border-dark-border">
                        <p class="text-sm font-medium text-gray-800 dark:text-dark-text"><?= e($user['name']) ?></p>
                        <p class="text-xs text-gray-500 dark:text-dark-muted"><?= e($user['email']) ?></p>
                    </div>
                    <a href="<?= url('/users/profile') ?>" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-dark-text hover:bg-gray-50 dark:hover:bg-dark-card2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <?= __('my_profile') ?>
                    </a>
                    <a href="<?= url('/users/change-password') ?>" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-dark-text hover:bg-gray-50 dark:hover:bg-dark-card2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        <?= __('change_password') ?>
                    </a>
                    <?php if (auth_is_super_admin()): ?>
                    <a href="<?= url('/settings') ?>" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-dark-text hover:bg-gray-50 dark:hover:bg-dark-card2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <?= __('settings') ?>
                    </a>
                    <?php endif; ?>
                    <div class="border-t border-gray-100 dark:border-dark-border mt-1"></div>
                    <form method="POST" action="<?= url('/auth/logout') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <?= __('sign_out') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
