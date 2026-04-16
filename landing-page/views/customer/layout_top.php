<?php
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Helpers.php';
Auth::requireSchool();
$currentUser = Auth::user();
$unreadCount = unread_notifications_count($currentUser['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Dashboard') ?> - Eduelevate</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: { extend: { colors: { primary: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' } }, fontFamily: { sans: ['Inter', 'sans-serif'] } } }
    }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="bg-gray-50 font-sans antialiased">
    <!-- Top Navigation -->
    <nav class="bg-white border-b border-gray-100 fixed top-0 inset-x-0 z-40 h-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-full flex items-center justify-between">
            <a href="<?= base_url() ?>" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-700 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-sm">E</span>
                </div>
                <span class="text-lg font-bold text-gray-900">Eduelevate</span>
            </a>
            <div class="flex items-center gap-4">
                <a href="<?= base_url('customer/notifications') ?>" class="relative p-2 text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                    <?php if ($unreadCount > 0): ?>
                    <span class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>
                <div class="flex items-center gap-2 text-sm">
                    <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold text-xs"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></div>
                    <span class="hidden sm:block font-medium text-gray-700"><?= e($currentUser['name']) ?></span>
                </div>
                <a href="<?= base_url('logout') ?>" class="text-sm text-gray-500 hover:text-gray-700">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Sidebar Navigation -->
    <aside class="fixed left-0 top-16 bottom-0 w-56 bg-white border-r border-gray-100 overflow-y-auto hidden lg:block z-30">
        <nav class="p-4 space-y-1">
            <?php
            $navItems = [
                ['url' => 'customer', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Dashboard', 'key' => 'dashboard'],
                ['url' => 'customer/demo', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z', 'label' => 'Book Demo', 'key' => 'demo'],
                ['url' => 'customer/agreement', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'label' => 'Agreement', 'key' => 'agreement'],
                ['url' => 'customer/payments', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Payments', 'key' => 'payments'],
                ['url' => 'customer/notifications', 'icon' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0', 'label' => 'Notifications', 'key' => 'notifications'],
                ['url' => 'customer/profile', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'label' => 'Profile', 'key' => 'profile'],
            ];
            foreach ($navItems as $item):
                $isActive = ($currentPage ?? '') === $item['key'];
            ?>
            <a href="<?= base_url($item['url']) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm <?= $isActive ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?> transition-colors">
                <svg class="w-5 h-5 <?= $isActive ? 'text-primary-600' : 'text-gray-400' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/></svg>
                <?= $item['label'] ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="pt-16 lg:pl-56 min-h-screen">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-xl font-bold text-gray-900"><?= e($pageTitle ?? 'Dashboard') ?></h1>
            </div>
            <?php if (has_flash('success')): ?>
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm"><?= e(get_flash('success')) ?></div>
            <?php endif; ?>
            <?php if (has_flash('error')): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm"><?= e(get_flash('error')) ?></div>
            <?php endif; ?>
