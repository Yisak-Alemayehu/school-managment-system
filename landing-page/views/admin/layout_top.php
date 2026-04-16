<?php
/**
 * Admin Layout Template
 * Variables: $pageTitle, $currentPage
 */
$notifCount = unread_notifications_count(Auth::id());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Admin') ?> – Eduelevate Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','system-ui','sans-serif']},colors:{primary:{50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a'}}}}}</script>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="font-sans bg-gray-50 antialiased">
<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 flex flex-col">
        <div class="flex items-center gap-2.5 px-5 h-16 border-b border-gray-100 flex-shrink-0">
            <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 14l9-5-9-5-9 5 9 5z"/></svg>
            </div>
            <div>
                <span class="text-sm font-bold text-gray-900">Eduelevate</span>
                <span class="block text-[10px] text-primary-600 font-semibold -mt-0.5">ADMIN</span>
            </div>
        </div>
        <nav class="flex-1 overflow-y-auto p-3 space-y-0.5">
            <?php
            $menuItems = [
                ['url' => 'admin', 'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z', 'label' => 'Dashboard', 'key' => 'dashboard'],
                ['url' => 'admin/schools', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'label' => 'Schools', 'key' => 'schools'],
                ['url' => 'admin/demos', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z', 'label' => 'Demos', 'key' => 'demos'],
                ['url' => 'admin/agreements', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'label' => 'Agreements', 'key' => 'agreements'],
                ['url' => 'admin/payments', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'label' => 'Payments', 'key' => 'payments'],
                ['url' => 'admin/submissions', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'label' => 'Submissions', 'key' => 'submissions'],
            ];
            $cmsItems = [
                ['url' => 'admin/content', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', 'label' => 'Content', 'key' => 'content'],
                ['url' => 'admin/features', 'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', 'label' => 'Features', 'key' => 'features'],
                ['url' => 'admin/pricing', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', 'label' => 'Pricing', 'key' => 'pricing'],
                ['url' => 'admin/testimonials', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 'label' => 'Testimonials', 'key' => 'testimonials'],
                ['url' => 'admin/faqs', 'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'FAQs', 'key' => 'faqs'],
                ['url' => 'admin/seo', 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', 'label' => 'SEO', 'key' => 'seo'],
            ];
            ?>
            <?php foreach ($menuItems as $item): ?>
            <a href="<?= base_url($item['url']) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all <?= ($currentPage ?? '') === $item['key'] ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/></svg>
                <?= $item['label'] ?>
            </a>
            <?php endforeach; ?>

            <div class="pt-4 mt-4 border-t border-gray-100">
                <p class="px-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-2">CMS</p>
            </div>
            <?php foreach ($cmsItems as $item): ?>
            <a href="<?= base_url($item['url']) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all <?= ($currentPage ?? '') === $item['key'] ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/></svg>
                <?= $item['label'] ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="p-3 border-t border-gray-100 flex-shrink-0">
            <a href="<?= base_url() ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-gray-500 hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                View Website
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Bar -->
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-6 flex-shrink-0">
            <div class="flex items-center gap-3">
                <button onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')" class="lg:hidden p-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-lg font-bold text-gray-900"><?= e($pageTitle ?? 'Dashboard') ?></h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= base_url('admin/notifications') ?>" class="relative p-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <?php if ($notifCount > 0): ?>
                    <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center"><?= $notifCount ?></span>
                    <?php endif; ?>
                </a>
                <div class="flex items-center gap-2 pl-2 border-l border-gray-200">
                    <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 text-xs font-bold">
                        <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
                    </div>
                    <div class="hidden sm:block">
                        <div class="text-sm font-medium text-gray-900"><?= e($_SESSION['user_name'] ?? '') ?></div>
                        <div class="text-[10px] text-gray-500">Administrator</div>
                    </div>
                    <a href="<?= base_url('logout') ?>" class="ml-2 p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition-colors" title="Logout">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </a>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-4 lg:p-6">
            <?php if ($flash = get_flash('success')): ?>
            <div class="mb-4 p-4 rounded-xl bg-green-50 border border-green-100 text-sm text-green-700 flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <?= e($flash) ?>
            </div>
            <?php endif; ?>
            <?php if ($flash = get_flash('error')): ?>
            <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-100 text-sm text-red-700 flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= e($flash) ?>
            </div>
            <?php endif; ?>
