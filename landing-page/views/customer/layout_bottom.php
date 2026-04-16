        </div>
    </main>

    <!-- Mobile Bottom Nav -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-gray-100 z-40 flex items-center justify-around py-2">
        <?php
        $mobileNav = [
            ['url' => 'customer', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Home', 'key' => 'dashboard'],
            ['url' => 'customer/demo', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z', 'label' => 'Demo', 'key' => 'demo'],
            ['url' => 'customer/payments', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1', 'label' => 'Pay', 'key' => 'payments'],
            ['url' => 'customer/notifications', 'icon' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0', 'label' => 'Alerts', 'key' => 'notifications'],
            ['url' => 'customer/profile', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'label' => 'Profile', 'key' => 'profile'],
        ];
        foreach ($mobileNav as $item):
            $isActive = ($currentPage ?? '') === $item['key'];
        ?>
        <a href="<?= base_url($item['url']) ?>" class="flex flex-col items-center gap-0.5 px-3 py-1 <?= $isActive ? 'text-primary-600' : 'text-gray-400' ?>">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/></svg>
            <span class="text-[10px] font-medium"><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <script src="<?= asset('js/customer.js') ?>"></script>
</body>
</html>
