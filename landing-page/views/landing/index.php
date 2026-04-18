<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($seo['meta_title'] ?? 'Eduelevate') ?></title>
    <meta name="description" content="<?= e($seo['meta_description'] ?? '') ?>">
    <meta name="keywords" content="<?= e($seo['keywords'] ?? '') ?>">
    <meta property="og:title" content="<?= e($seo['og_title'] ?? $seo['meta_title'] ?? '') ?>">
    <meta property="og:description" content="<?= e($seo['og_description'] ?? '') ?>">
    <?php if (!empty($seo['og_image'])): ?>
    <meta property="og:image" content="<?= e($seo['og_image']) ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        primary: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' },
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="font-sans text-gray-800 bg-white antialiased">

<!-- ═══════════════════ NAVBAR ═══════════════════ -->
<nav id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-white/80 backdrop-blur-lg border-b border-gray-100/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20">
            <a href="<?= base_url() ?>" class="flex items-center gap-2.5 group">
                <div class="w-9 h-9 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center shadow-lg shadow-primary-500/25 group-hover:shadow-primary-500/40 transition-shadow">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                </div>
                <span class="text-xl font-bold bg-gradient-to-r from-primary-600 to-primary-800 bg-clip-text text-transparent">Eduelevate</span>
            </a>
            <div class="hidden lg:flex items-center gap-8">
                <a href="#features" class="text-sm font-medium text-gray-600 hover:text-primary-600 transition-colors">Features</a>
                <a href="#showcase" class="text-sm font-medium text-gray-600 hover:text-primary-600 transition-colors">Product</a>
                <a href="#mobile-app" class="text-sm font-medium text-gray-600 hover:text-primary-600 transition-colors">Mobile App</a>
                <a href="#pricing" class="text-sm font-medium text-gray-600 hover:text-primary-600 transition-colors">Pricing</a>
                <a href="#testimonials" class="text-sm font-medium text-gray-600 hover:text-primary-600 transition-colors">Testimonials</a>
                <a href="#faq" class="text-sm font-medium text-gray-600 hover:text-primary-600 transition-colors">FAQ</a>
            </div>
            <div class="hidden lg:flex items-center gap-3">
                <?php if (Auth::check()): ?>
                    <a href="<?= base_url(Auth::isAdmin() ? 'admin' : 'customer') ?>" class="text-sm font-medium text-gray-600 hover:text-primary-600 transition-colors">Dashboard</a>
                <?php else: ?>
                    <a href="<?= base_url('login') ?>" class="text-sm font-medium text-gray-600 hover:text-primary-600 transition-colors px-4 py-2">Sign In</a>
                    <a href="<?= base_url('register') ?>" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 transition-all">
                        Get Started
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                <?php endif; ?>
            </div>
            <button id="mobile-menu-btn" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>
    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden lg:hidden border-t border-gray-100 bg-white/95 backdrop-blur-lg">
        <div class="px-4 py-4 space-y-2">
            <a href="#features" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-primary-50 hover:text-primary-600">Features</a>
            <a href="#showcase" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-primary-50 hover:text-primary-600">Product</a>
            <a href="#mobile-app" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-primary-50 hover:text-primary-600">Mobile App</a>
            <a href="#pricing" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-primary-50 hover:text-primary-600">Pricing</a>
            <a href="#testimonials" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-primary-50 hover:text-primary-600">Testimonials</a>
            <a href="#faq" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-primary-50 hover:text-primary-600">FAQ</a>
            <div class="pt-3 border-t border-gray-100 flex gap-2">
                <?php if (Auth::check()): ?>
                    <a href="<?= base_url(Auth::isAdmin() ? 'admin' : 'customer') ?>" class="flex-1 text-center bg-primary-600 text-white text-sm font-semibold py-2.5 rounded-xl">Dashboard</a>
                <?php else: ?>
                    <a href="<?= base_url('login') ?>" class="flex-1 text-center text-sm font-medium text-gray-700 py-2.5 rounded-xl border border-gray-200">Sign In</a>
                    <a href="<?= base_url('register') ?>" class="flex-1 text-center bg-primary-600 text-white text-sm font-semibold py-2.5 rounded-xl">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- ═══════════════════ HERO ═══════════════════ -->
<section class="relative pt-24 lg:pt-32 pb-16 lg:pb-24 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-primary-50/80 via-white to-blue-50/50"></div>
    <div class="absolute top-20 right-0 w-[600px] h-[600px] bg-primary-200/20 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-blue-100/30 rounded-full blur-3xl"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <div class="animate-on-scroll">
                <div class="inline-flex items-center gap-2 bg-primary-50 border border-primary-100 text-primary-700 text-xs font-semibold px-3 py-1.5 rounded-full mb-6">
                    <span class="w-1.5 h-1.5 bg-primary-500 rounded-full animate-pulse"></span>
                    Trusted by 500+ Schools
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tight">
                    <?= e($hero['title'] ?? 'Elevate Your School with Smarter Management') ?>
                </h1>
                <p class="mt-6 text-lg lg:text-xl text-gray-600 leading-relaxed max-w-lg">
                    <?= e($hero['subtitle'] ?? 'All-in-one platform to manage students, finances, communication, and performance — effortlessly.') ?>
                </p>
                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <a href="#contact" class="inline-flex items-center justify-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold px-7 py-3.5 rounded-xl shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 transition-all text-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <?= e($hero['extra_data']['cta_primary'] ?? 'Request Demo') ?>
                    </a>
                    <a href="<?= base_url('register') ?>" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-gray-50 text-gray-800 font-semibold px-7 py-3.5 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-all text-sm">
                        <?= e($hero['extra_data']['cta_secondary'] ?? 'Get Started') ?>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                </div>
                <?php if (!empty($hero['extra_data']['stats'])): ?>
                <div class="mt-10 flex items-center gap-8 pt-8 border-t border-gray-200/60">
                    <?php foreach ($hero['extra_data']['stats'] as $stat): ?>
                    <div>
                        <div class="text-2xl font-bold text-gray-900"><?= e($stat['value']) ?></div>
                        <div class="text-xs text-gray-500 mt-0.5"><?= e($stat['label']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="animate-on-scroll relative">
                <div class="relative rounded-2xl overflow-hidden shadow-2xl shadow-gray-300/50 border border-gray-200/50 bg-white">
                    <div class="flex items-center gap-1.5 px-4 py-2.5 bg-gray-50 border-b border-gray-100">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-green-400"></span>
                        <span class="ml-3 text-xs text-gray-400">eduelevate.com/dashboard</span>
                    </div>
                    <!-- Hero Screenshot -->
                    <div class="relative bg-gradient-to-br from-blue-500 to-indigo-600">
                        <img
                            src="<?= asset('screenshots/dashboard.png') ?>"
                            alt="Eduelevate Admin Dashboard"
                            class="w-full block"
                            onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex';"
                        >
                        <!-- Fallback if no screenshot -->
                        <div class="hidden aspect-[16/10] items-center justify-center p-8" style="display:none;">
                            <div class="text-center text-white/90">
                                <div class="w-16 h-16 rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <p class="text-base font-bold mb-1">Dashboard Screenshot</p>
                                <p class="text-sm text-white/60">Place your screenshot at: assets/screenshots/dashboard.png</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="absolute -bottom-4 -left-4 bg-white rounded-xl shadow-lg border border-gray-100 p-3 animate-float">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-900">98% Attendance</div>
                            <div class="text-[10px] text-gray-500">This week</div>
                        </div>
                    </div>
                </div>
                <div class="absolute -top-3 -right-3 bg-white rounded-xl shadow-lg border border-gray-100 p-3 animate-float-delayed">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-900">1,247 Students</div>
                            <div class="text-[10px] text-gray-500">Active</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ SOCIAL PROOF ═══════════════════ -->
<section class="py-12 bg-gray-50/50 border-y border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-center text-sm font-medium text-gray-500 mb-6"><?= e($socialProof['title'] ?? 'Trusted by Forward-Thinking Schools') ?></p>
        <div class="flex flex-wrap items-center justify-center gap-8 lg:gap-16 opacity-60">
            <?php for ($i = 1; $i <= 6; $i++): ?>
            <div class="flex items-center gap-2 text-gray-400">
                <div class="w-8 h-8 rounded-lg bg-gray-200 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/></svg>
                </div>
                <span class="text-sm font-semibold">School <?= $i ?></span>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════ FEATURES ═══════════════════ -->
<section id="features" class="py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 animate-on-scroll">
            <div class="inline-flex items-center gap-2 bg-primary-50 border border-primary-100 text-primary-700 text-xs font-semibold px-3 py-1.5 rounded-full mb-4">Features</div>
            <h2 class="text-3xl lg:text-4xl font-extrabold tracking-tight"><?= e($featuresIntro['title'] ?? 'Everything Your School Needs in One Platform') ?></h2>
            <p class="mt-4 text-lg text-gray-600"><?= e($featuresIntro['subtitle'] ?? 'Powerful tools designed to simplify every aspect of school management') ?></p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            $featureIcons = [
                'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
                'clipboard-check' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
                'chart-bar' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
                'currency-dollar' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'chat-alt-2' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>',
                'view-grid' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>',
                'document-report' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                'calendar' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
                'star' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>',
            ];
            $colors = ['primary','indigo','violet','emerald','amber','rose','cyan','teal'];
            foreach ($features as $i => $feature):
                $color = $colors[$i % count($colors)];
                $iconPath = $featureIcons[$feature['icon']] ?? $featureIcons['star'];
            ?>
            <div class="group p-6 rounded-2xl border border-gray-100 bg-white hover:shadow-xl hover:shadow-gray-200/50 hover:border-gray-200 transition-all duration-300 animate-on-scroll">
                <div class="w-12 h-12 rounded-xl bg-<?= $color ?>-50 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-<?= $color ?>-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><?= $iconPath ?></svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-2"><?= e($feature['title']) ?></h3>
                <p class="text-sm text-gray-600 leading-relaxed"><?= e($feature['description']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════ PRODUCT SHOWCASE ═══════════════════ -->
<section id="showcase" class="py-20 lg:py-28 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12 animate-on-scroll">
            <div class="inline-flex items-center gap-2 bg-primary-50 border border-primary-100 text-primary-700 text-xs font-semibold px-3 py-1.5 rounded-full mb-4">Live Product</div>
            <h2 class="text-3xl lg:text-4xl font-extrabold tracking-tight"><?= e($showcase['title'] ?? 'See Eduelevate in Action') ?></h2>
            <p class="mt-4 text-lg text-gray-600"><?= e($showcase['subtitle'] ?? 'Real dashboards. Real results.') ?></p>
        </div>

        <!-- Tab Navigation -->
        <div class="flex justify-center mb-10 animate-on-scroll">
            <div class="inline-flex flex-wrap justify-center gap-2 p-1.5 bg-white rounded-2xl border border-gray-200 shadow-sm">
                <?php
                $showcaseTabs = [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z'],
                    ['key' => 'attendance', 'label' => 'Attendance', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                    ['key' => 'results', 'label' => 'Results', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ['key' => 'payments', 'label' => 'Payments', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['key' => 'communication', 'label' => 'Communication', 'icon' => 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z'],
                    ['key' => 'students', 'label' => 'Students', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                ];
                foreach ($showcaseTabs as $i => $tab):
                ?>
                <button class="showcase-tab <?= $i === 0 ? 'active' : '' ?> flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all" data-tab="<?= $tab['key'] ?>">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $tab['icon'] ?>"/></svg>
                    <?= $tab['label'] ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Showcase Content -->
        <div class="animate-on-scroll">
            <div class="relative max-w-5xl mx-auto">
                <div class="rounded-2xl shadow-2xl shadow-gray-300/40 border border-gray-200/60 bg-white overflow-hidden">
                    <!-- Browser Chrome -->
                    <div class="flex items-center gap-1.5 px-4 py-2.5 bg-gray-50 border-b border-gray-100">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-green-400"></span>
                        <div class="ml-3 flex-1 bg-white rounded-md px-3 py-1 text-xs text-gray-400 border border-gray-200">eduelevate.com/<span id="showcase-url-path">dashboard</span></div>
                    </div>

                    <!-- Screenshot Panels -->
                    <?php
                    $showcaseScreenshots = [
                        'dashboard'     => ['file' => 'screenshots/dashboard.png', 'alt' => 'Admin Dashboard — Overview with stat cards, enrollment trends, and notifications', 'gradient' => 'from-blue-500 to-indigo-600'],
                        'attendance'    => ['file' => 'screenshots/attendance.png', 'alt' => 'Attendance tracking with donut chart, present/absent/late stats', 'gradient' => 'from-emerald-500 to-teal-600'],
                        'results'       => ['file' => 'screenshots/results.png', 'alt' => 'Result management with class averages, pass rates, and subject breakdowns', 'gradient' => 'from-violet-500 to-purple-600'],
                        'payments'      => ['file' => 'screenshots/payments.png', 'alt' => 'Fee management dashboard with collection tracking and payment history', 'gradient' => 'from-amber-500 to-orange-600'],
                        'communication' => ['file' => 'screenshots/communication.png', 'alt' => 'Announcements and messaging system for parents and teachers', 'gradient' => 'from-pink-500 to-rose-600'],
                        'students'      => ['file' => 'screenshots/students.png', 'alt' => 'Student directory with grades, sections, and GPA tracking', 'gradient' => 'from-cyan-500 to-blue-600'],
                    ];
                    foreach ($showcaseScreenshots as $key => $ss):
                        $isFirst = ($key === 'dashboard');
                    ?>
                    <div class="showcase-panel <?= $isFirst ? 'active' : '' ?>" data-panel="<?= $key ?>" <?= $isFirst ? '' : 'style="display:none"' ?>>
                        <div class="showcase-img-wrapper relative bg-gradient-to-br <?= $ss['gradient'] ?>">
                            <img
                                src="<?= asset($ss['file']) ?>"
                                alt="<?= e($ss['alt']) ?>"
                                loading="<?= $isFirst ? 'eager' : 'lazy' ?>"
                                class="showcase-img w-full block"
                                onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex';"
                            >
                            <!-- Fallback shown only if image fails -->
                            <div class="showcase-placeholder hidden aspect-[16/9] items-center justify-center p-8" style="display:none;">
                                <div class="text-center text-white/90">
                                    <div class="w-16 h-16 rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <p class="text-base font-bold mb-1"><?= e(ucfirst($key)) ?> Screenshot</p>
                                    <p class="text-sm text-white/60 max-w-sm mx-auto"><?= e($ss['alt']) ?></p>
                                    <p class="mt-4 text-xs text-white/40">Place your screenshot at: assets/<?= e($ss['file']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Floating Badges (per tab) -->
                <?php
                $showcaseBadges = [
                    'dashboard' => [
                        ['icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197', 'bg' => 'bg-primary-100', 'color' => 'text-primary-600', 'title' => '1,247 Students', 'sub' => 'Active', 'pos' => '-top-4 -right-4'],
                        ['icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', 'bg' => 'bg-green-100', 'color' => 'text-green-600', 'title' => '2.4M ETB Revenue', 'sub' => 'This term', 'pos' => '-bottom-4 -left-4'],
                    ],
                    'attendance' => [
                        ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'bg' => 'bg-emerald-100', 'color' => 'text-emerald-600', 'title' => '98.2% Present', 'sub' => 'Today', 'pos' => '-top-4 -right-4'],
                        ['icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'bg' => 'bg-amber-100', 'color' => 'text-amber-600', 'title' => '10 Late Arrivals', 'sub' => 'This week', 'pos' => '-bottom-4 -left-4'],
                    ],
                    'results' => [
                        ['icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10', 'bg' => 'bg-violet-100', 'color' => 'text-violet-600', 'title' => '78.5% Average', 'sub' => 'Class GPA', 'pos' => '-top-4 -right-4'],
                        ['icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', 'bg' => 'bg-pink-100', 'color' => 'text-pink-600', 'title' => '94% Pass Rate', 'sub' => 'Semester 1', 'pos' => '-bottom-4 -left-4'],
                    ],
                    'payments' => [
                        ['icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1', 'bg' => 'bg-emerald-100', 'color' => 'text-emerald-600', 'title' => '1.8M Collected', 'sub' => 'ETB this year', 'pos' => '-top-4 -right-4'],
                        ['icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', 'bg' => 'bg-red-100', 'color' => 'text-red-600', 'title' => '87K Overdue', 'sub' => '15 students', 'pos' => '-bottom-4 -left-4'],
                    ],
                    'communication' => [
                        ['icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'bg' => 'bg-pink-100', 'color' => 'text-pink-600', 'title' => '3 Announcements', 'sub' => 'This week', 'pos' => '-top-4 -right-4'],
                        ['icon' => 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z', 'bg' => 'bg-blue-100', 'color' => 'text-blue-600', 'title' => '89% Read Rate', 'sub' => 'Parent messages', 'pos' => '-bottom-4 -left-4'],
                    ],
                    'students' => [
                        ['icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197', 'bg' => 'bg-cyan-100', 'color' => 'text-cyan-600', 'title' => '1,247 Enrolled', 'sub' => 'All grades', 'pos' => '-top-4 -right-4'],
                        ['icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z', 'bg' => 'bg-amber-100', 'color' => 'text-amber-600', 'title' => '3.4 Avg GPA', 'sub' => 'This semester', 'pos' => '-bottom-4 -left-4'],
                    ],
                ];
                foreach ($showcaseBadges as $tabKey => $badges):
                    foreach ($badges as $bi => $badge):
                        $isFirst = ($tabKey === 'dashboard');
                        $animClass = $bi === 0 ? 'animate-float-delayed' : 'animate-float';
                ?>
                <div class="showcase-badge absolute <?= $badge['pos'] ?> z-10 bg-white rounded-xl shadow-lg border border-gray-100 p-3 <?= $animClass ?> hidden lg:<?= $isFirst ? 'block' : 'hidden' ?>" data-badge-for="<?= $tabKey ?>">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 <?= $badge['bg'] ?> rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 <?= $badge['color'] ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $badge['icon'] ?>"/></svg>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-900"><?= $badge['title'] ?></div>
                            <div class="text-[10px] text-gray-500"><?= $badge['sub'] ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; endforeach; ?>

                <!-- Caption below -->
                <div class="text-center mt-8">
                    <p class="text-xs text-gray-500">Click the tabs above to explore different modules</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ PWA MOBILE SHOWCASE ═══════════════════ -->
<section id="mobile-app" class="py-20 lg:py-28 bg-gradient-to-b from-white via-gray-50/80 to-white overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center max-w-2xl mx-auto mb-14 animate-on-scroll">
            <div class="inline-flex items-center gap-2 bg-violet-50 border border-violet-100 text-violet-700 text-xs font-semibold px-3 py-1.5 rounded-full mb-4">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Mobile Experience
            </div>
            <h2 class="text-3xl lg:text-4xl font-extrabold tracking-tight">Everything in Your Pocket</h2>
            <p class="mt-4 text-lg text-gray-600">Our Progressive Web App delivers a native-like experience on any device — no app store needed.</p>
        </div>

        <!-- Role Tabs -->
        <div class="flex justify-center mb-12 animate-on-scroll">
            <div class="inline-flex flex-wrap justify-center gap-2 p-1.5 bg-white rounded-2xl border border-gray-200 shadow-sm">
                <button class="pwa-tab active flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium transition-all" data-pwa-tab="student">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                    Student
                </button>
                <button class="pwa-tab flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium transition-all" data-pwa-tab="parent">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Parent
                </button>
                <button class="pwa-tab flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium transition-all" data-pwa-tab="teacher">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                    Teacher
                    <span class="px-1.5 py-0.5 bg-amber-100 text-amber-700 text-[9px] font-bold rounded-md uppercase tracking-wider">Soon</span>
                </button>
            </div>
        </div>

        <!-- ===== STUDENT PANEL ===== -->
        <div class="pwa-role-panel" data-pwa-panel="student">
            <div class="flex flex-col lg:flex-row items-center gap-10 lg:gap-16">
                <!-- Left: text -->
                <div class="lg:w-2/5 text-center lg:text-left animate-on-scroll">
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Student Dashboard</h3>
                    <p class="text-gray-600 mb-6">Students get instant access to their results, class schedules, messages from teachers, and school announcements — all from their phone.</p>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 lg:justify-start justify-center"><div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10"/></svg></div><span class="text-sm font-medium text-gray-700">View results & report cards</span></div>
                        <div class="flex items-center gap-3 lg:justify-start justify-center"><div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg></div><span class="text-sm font-medium text-gray-700">Message teachers directly</span></div>
                        <div class="flex items-center gap-3 lg:justify-start justify-center"><div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg></div><span class="text-sm font-medium text-gray-700">Get instant notifications</span></div>
                        <div class="flex items-center gap-3 lg:justify-start justify-center"><div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div><span class="text-sm font-medium text-gray-700">Check attendance records</span></div>
                    </div>
                </div>
                <!-- Right: phone carousel -->
                <div class="lg:w-3/5 flex justify-center animate-on-scroll">
                    <div class="relative">
                        <!-- Decorative blobs -->
                        <div class="absolute -top-10 -left-10 w-40 h-40 bg-blue-200/30 rounded-full blur-3xl"></div>
                        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-violet-200/30 rounded-full blur-3xl"></div>
                        <!-- Phone slider -->
                        <div class="pwa-phone-slider flex items-end gap-6" data-pwa-slider="student">
                            <?php
                            $studentScreens = [
                                ['file' => 'screenshots/mobile/student-dashboard.png', 'label' => 'Dashboard', 'gradient' => 'from-blue-500 to-indigo-600'],
                                ['file' => 'screenshots/mobile/student-results.png', 'label' => 'Results', 'gradient' => 'from-violet-500 to-purple-600'],
                                ['file' => 'screenshots/mobile/student-messages.png', 'label' => 'Messages', 'gradient' => 'from-emerald-500 to-teal-600'],
                                ['file' => 'screenshots/mobile/student-notifications.png', 'label' => 'Notifications', 'gradient' => 'from-amber-500 to-orange-600'],
                            ];
                            foreach ($studentScreens as $si => $screen):
                                $isCenter = ($si === 0);
                            ?>
                            <div class="pwa-phone-frame <?= $isCenter ? 'pwa-phone-active' : 'pwa-phone-side' ?>" data-screen-index="<?= $si ?>">
                                <div class="pwa-phone-body">
                                    <!-- Dynamic Island -->
                                    <div class="pwa-phone-island"></div>
                                    <!-- Screen -->
                                    <div class="pwa-phone-screen bg-gradient-to-br <?= $screen['gradient'] ?>">
                                        <img src="<?= asset($screen['file']) ?>" alt="<?= e($screen['label']) ?>" class="w-full h-full object-cover object-top" loading="lazy" onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex';">
                                        <!-- Fallback -->
                                        <div class="pwa-phone-fallback hidden items-center justify-center h-full" style="display:none;">
                                            <div class="text-center text-white/90 p-4">
                                                <div class="w-12 h-12 rounded-xl bg-white/15 flex items-center justify-center mx-auto mb-3">
                                                    <svg class="w-6 h-6 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                </div>
                                                <p class="text-xs font-bold"><?= e($screen['label']) ?></p>
                                                <p class="text-[10px] text-white/50 mt-1">Student PWA</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Home indicator -->
                                    <div class="pwa-phone-home"></div>
                                </div>
                                <div class="text-center mt-3"><span class="text-[11px] font-medium text-gray-500"><?= e($screen['label']) ?></span></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Slider dots -->
                        <div class="flex justify-center gap-2 mt-6">
                            <?php foreach ($studentScreens as $si => $screen): ?>
                            <button class="pwa-dot <?= $si === 0 ? 'active' : '' ?> w-2 h-2 rounded-full transition-all" data-slider="student" data-dot="<?= $si ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== PARENT PANEL ===== -->
        <div class="pwa-role-panel" data-pwa-panel="parent" style="display:none">
            <div class="flex flex-col lg:flex-row-reverse items-center gap-10 lg:gap-16">
                <!-- Left: text (reversed for visual variety) -->
                <div class="lg:w-2/5 text-center lg:text-left animate-on-scroll">
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Parent Dashboard</h3>
                    <p class="text-gray-600 mb-6">Parents stay connected to their child's education — track grades, communicate with teachers, receive payment reminders, and never miss an announcement.</p>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 lg:justify-start justify-center"><div class="w-8 h-8 bg-pink-100 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10"/></svg></div><span class="text-sm font-medium text-gray-700">Track child's academic results</span></div>
                        <div class="flex items-center gap-3 lg:justify-start justify-center"><div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg></div><span class="text-sm font-medium text-gray-700">Monitor academic progress</span></div>
                        <div class="flex items-center gap-3 lg:justify-start justify-center"><div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div><span class="text-sm font-medium text-gray-700">Communicate with teachers</span></div>
                        <div class="flex items-center gap-3 lg:justify-start justify-center"><div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/></svg></div><span class="text-sm font-medium text-gray-700">Fee payment alerts & history</span></div>
                    </div>
                </div>
                <!-- Right: phone carousel -->
                <div class="lg:w-3/5 flex justify-center animate-on-scroll">
                    <div class="relative">
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-pink-200/30 rounded-full blur-3xl"></div>
                        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-emerald-200/30 rounded-full blur-3xl"></div>
                        <div class="pwa-phone-slider flex items-end gap-6" data-pwa-slider="parent">
                            <?php
                            $parentScreens = [
                                ['file' => 'screenshots/mobile/parent-dashboard.png', 'label' => 'Dashboard', 'gradient' => 'from-pink-500 to-rose-600'],
                                ['file' => 'screenshots/mobile/parent-results.png', 'label' => 'Child Results', 'gradient' => 'from-blue-500 to-indigo-600'],
                                ['file' => 'screenshots/mobile/parent-progress.png', 'label' => 'Progress', 'gradient' => 'from-emerald-500 to-teal-600'],
                                ['file' => 'screenshots/mobile/parent-messages.png', 'label' => 'Messages', 'gradient' => 'from-violet-500 to-purple-600'],
                            ];
                            foreach ($parentScreens as $si => $screen):
                                $isCenter = ($si === 0);
                            ?>
                            <div class="pwa-phone-frame <?= $isCenter ? 'pwa-phone-active' : 'pwa-phone-side' ?>" data-screen-index="<?= $si ?>">
                                <div class="pwa-phone-body">
                                    <div class="pwa-phone-island"></div>
                                    <div class="pwa-phone-screen bg-gradient-to-br <?= $screen['gradient'] ?>">
                                        <img src="<?= asset($screen['file']) ?>" alt="<?= e($screen['label']) ?>" class="w-full h-full object-cover object-top" loading="lazy" onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex';">
                                        <div class="pwa-phone-fallback hidden items-center justify-center h-full" style="display:none;">
                                            <div class="text-center text-white/90 p-4">
                                                <div class="w-12 h-12 rounded-xl bg-white/15 flex items-center justify-center mx-auto mb-3">
                                                    <svg class="w-6 h-6 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                </div>
                                                <p class="text-xs font-bold"><?= e($screen['label']) ?></p>
                                                <p class="text-[10px] text-white/50 mt-1">Parent PWA</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pwa-phone-home"></div>
                                </div>
                                <div class="text-center mt-3"><span class="text-[11px] font-medium text-gray-500"><?= e($screen['label']) ?></span></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="flex justify-center gap-2 mt-6">
                            <?php foreach ($parentScreens as $si => $screen): ?>
                            <button class="pwa-dot <?= $si === 0 ? 'active' : '' ?> w-2 h-2 rounded-full transition-all" data-slider="parent" data-dot="<?= $si ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== TEACHER PANEL (Coming Soon) ===== -->
        <div class="pwa-role-panel" data-pwa-panel="teacher" style="display:none">
            <div class="flex flex-col lg:flex-row items-center gap-10 lg:gap-16">
                <div class="lg:w-2/5 text-center lg:text-left animate-on-scroll">
                    <div class="inline-flex items-center gap-1.5 bg-amber-50 border border-amber-200 text-amber-700 text-[10px] font-bold px-2.5 py-1 rounded-full mb-4 uppercase tracking-wider">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Coming Soon
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Teacher Dashboard</h3>
                    <p class="text-gray-600 mb-6">Mark attendance, enter grades, send messages to parents, and manage your classes — all from your phone. Full release coming soon.</p>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 lg:justify-start justify-center"><div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></div><span class="text-sm font-medium text-gray-700">One-tap attendance marking</span></div>
                        <div class="flex items-center gap-3 lg:justify-start justify-center"><div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></div><span class="text-sm font-medium text-gray-700">Enter & manage grades</span></div>
                        <div class="flex items-center gap-3 lg:justify-start justify-center"><div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg></div><span class="text-sm font-medium text-gray-700">Direct parent communication</span></div>
                        <div class="flex items-center gap-3 lg:justify-start justify-center"><div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></div><span class="text-sm font-medium text-gray-700">Class management tools</span></div>
                    </div>
                </div>
                <!-- Right: phone carousel with Coming Soon overlay -->
                <div class="lg:w-3/5 flex justify-center animate-on-scroll">
                    <div class="relative">
                        <div class="absolute -top-10 -left-10 w-40 h-40 bg-amber-200/30 rounded-full blur-3xl"></div>
                        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-blue-200/30 rounded-full blur-3xl"></div>
                        <div class="pwa-phone-slider flex items-end gap-6" data-pwa-slider="teacher">
                            <?php
                            $teacherScreens = [
                                ['file' => 'screenshots/mobile/teacher-dashboard.png', 'label' => 'Dashboard', 'gradient' => 'from-blue-500 to-indigo-600'],
                                ['file' => 'screenshots/mobile/teacher-attendance.png', 'label' => 'Attendance', 'gradient' => 'from-emerald-500 to-teal-600'],
                                ['file' => 'screenshots/mobile/teacher-grades.png', 'label' => 'Grades', 'gradient' => 'from-violet-500 to-purple-600'],
                                ['file' => 'screenshots/mobile/teacher-messages.png', 'label' => 'Messages', 'gradient' => 'from-amber-500 to-orange-600'],
                            ];
                            foreach ($teacherScreens as $si => $screen):
                                $isCenter = ($si === 0);
                            ?>
                            <div class="pwa-phone-frame <?= $isCenter ? 'pwa-phone-active' : 'pwa-phone-side' ?>" data-screen-index="<?= $si ?>">
                                <div class="pwa-phone-body">
                                    <div class="pwa-phone-island"></div>
                                    <div class="pwa-phone-screen bg-gradient-to-br <?= $screen['gradient'] ?>">
                                        <img src="<?= asset($screen['file']) ?>" alt="<?= e($screen['label']) ?>" class="w-full h-full object-cover object-top" loading="lazy" onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex';">
                                        <div class="pwa-phone-fallback hidden items-center justify-center h-full" style="display:none;">
                                            <div class="text-center text-white/90 p-4">
                                                <div class="w-12 h-12 rounded-xl bg-white/15 flex items-center justify-center mx-auto mb-3">
                                                    <svg class="w-6 h-6 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                </div>
                                                <p class="text-xs font-bold"><?= e($screen['label']) ?></p>
                                                <p class="text-[10px] text-white/50 mt-1">Teacher PWA</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pwa-phone-home"></div>
                                </div>
                                <div class="text-center mt-3"><span class="text-[11px] font-medium text-gray-500"><?= e($screen['label']) ?></span></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Coming Soon overlay -->
                        <div class="absolute inset-0 bg-white/40 backdrop-blur-[2px] rounded-2xl flex items-center justify-center z-10">
                            <div class="bg-white shadow-xl border border-gray-200 rounded-2xl px-6 py-4 text-center">
                                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div class="text-sm font-bold text-gray-900">Live Preview</div>
                                <div class="text-xs text-gray-500 mt-0.5">Full Release Coming Soon</div>
                            </div>
                        </div>
                        <div class="flex justify-center gap-2 mt-6">
                            <?php foreach ($teacherScreens as $si => $screen): ?>
                            <button class="pwa-dot <?= $si === 0 ? 'active' : '' ?> w-2 h-2 rounded-full transition-all" data-slider="teacher" data-dot="<?= $si ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Install CTA -->
        <div class="mt-16 text-center animate-on-scroll">
            <div class="inline-flex flex-col sm:flex-row items-center gap-4 bg-gradient-to-r from-primary-50 to-violet-50 border border-primary-100 rounded-2xl px-8 py-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="text-left">
                        <div class="text-sm font-bold text-gray-900">Install as App</div>
                        <div class="text-xs text-gray-500">No app store needed — works on any device</div>
                    </div>
                </div>
                <a href="#contact" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold px-5 py-2.5 rounded-xl text-sm shadow-md shadow-primary-500/20 transition-all">
                    Get Started
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ HOW IT WORKS ═══════════════════ -->
<section id="how-it-works" class="py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 animate-on-scroll">
            <div class="inline-flex items-center gap-2 bg-primary-50 border border-primary-100 text-primary-700 text-xs font-semibold px-3 py-1.5 rounded-full mb-4">How It Works</div>
            <h2 class="text-3xl lg:text-4xl font-extrabold tracking-tight"><?= e($howItWorks['title'] ?? 'Your Journey to Smarter School Management') ?></h2>
            <p class="mt-4 text-lg text-gray-600"><?= e($howItWorks['subtitle'] ?? 'A simple, guided process from discovery to activation') ?></p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            <?php
            $steps = [
                ['num' => '01', 'title' => 'Request Access', 'desc' => 'Fill out a quick form or sign up. Our team reviews your request within 24 hours.', 'icon' => 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z'],
                ['num' => '02', 'title' => 'Book a Demo', 'desc' => 'Schedule a personalized demo at a time that works for you. See every feature in action.', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
                ['num' => '03', 'title' => 'Experience Eduelevate', 'desc' => 'Get hands-on with the platform. Explore dashboards, reports, and management tools.', 'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                ['num' => '04', 'title' => 'Review Agreement', 'desc' => 'Receive a clear service agreement outlining everything included in your package.', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['num' => '05', 'title' => 'Flexible Payment', 'desc' => 'Pay with our flexible installment plan — 50% upfront, rest within 1-2 months.', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                ['num' => '06', 'title' => 'Go Live!', 'desc' => 'Full installation, configuration, training, and your school is live within 1-2 weeks.', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
            ];
            foreach ($steps as $step):
            ?>
            <div class="relative p-6 rounded-2xl border border-gray-100 bg-white hover:shadow-lg transition-all duration-300 animate-on-scroll group">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-primary-50 rounded-xl flex items-center justify-center group-hover:bg-primary-100 transition-colors">
                        <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $step['icon'] ?>"/></svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-primary-500">STEP <?= $step['num'] ?></span>
                        <h3 class="text-lg font-bold text-gray-900 mt-1"><?= e($step['title']) ?></h3>
                        <p class="text-sm text-gray-600 mt-2 leading-relaxed"><?= e($step['desc']) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════ PRICING ═══════════════════ -->
<section id="pricing" class="py-20 lg:py-28 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-6 animate-on-scroll">
            <div class="inline-flex items-center gap-2 bg-primary-50 border border-primary-100 text-primary-700 text-xs font-semibold px-3 py-1.5 rounded-full mb-4">Pricing</div>
            <h2 class="text-3xl lg:text-4xl font-extrabold tracking-tight"><?= e($pricingIntro['title'] ?? 'Simple, Transparent Pricing') ?></h2>
            <p class="mt-4 text-lg text-gray-600"><?= e($pricingIntro['subtitle'] ?? 'Packages starting from 60,000 ETB') ?></p>
        </div>

        <!-- Installment Banner -->
        <div class="max-w-lg mx-auto mb-12 animate-on-scroll">
            <div class="bg-gradient-to-r from-primary-500 to-primary-700 rounded-2xl p-5 text-center text-white shadow-xl shadow-primary-500/20">
                <div class="flex items-center justify-center gap-2 mb-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="font-bold text-sm">Flexible Payment Available</span>
                </div>
                <p class="text-sm text-primary-100"><?= e($pricingIntro['extra_data']['installment_text'] ?? 'Start now, pay in parts — 50% upfront, 50% after 1-2 months') ?></p>
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-6 lg:gap-8 max-w-6xl mx-auto">
            <?php foreach ($packages as $pkg):
                $isPopular = $pkg['is_popular'];
            ?>
            <div class="relative rounded-2xl border <?= $isPopular ? 'border-primary-300 shadow-xl shadow-primary-100/50 scale-[1.02]' : 'border-gray-200 bg-white' ?> overflow-hidden animate-on-scroll transition-all hover:shadow-xl <?= $isPopular ? 'bg-white' : '' ?>">
                <?php if ($pkg['badge_text']): ?>
                <div class="absolute top-0 right-0">
                    <div class="bg-primary-600 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl"><?= e($pkg['badge_text']) ?></div>
                </div>
                <?php endif; ?>
                <div class="p-6 lg:p-8">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-gray-900"><?= e($pkg['name']) ?></h3>
                        <p class="text-sm text-gray-500 mt-1"><?= e($pkg['school_size']) ?> · <?= e($pkg['student_range']) ?></p>
                    </div>
                    <div class="mb-6">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">One-Time Setup</div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-extrabold text-gray-900"><?= format_etb($pkg['setup_fee_min']) ?></span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">to <?= format_etb($pkg['setup_fee_max']) ?></p>
                    </div>
                    <div class="mb-6 pb-6 border-b border-gray-100">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Monthly</div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-2xl font-bold text-gray-900"><?= format_etb($pkg['monthly_fee_min']) ?></span>
                            <span class="text-sm text-gray-500">/ month</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">to <?= format_etb($pkg['monthly_fee_max']) ?> / month</p>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <?php foreach ($pkg['features_list'] as $feat): ?>
                        <li class="flex items-start gap-2.5 text-sm text-gray-700">
                            <svg class="w-5 h-5 text-primary-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <?= e($feat) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= base_url('register') ?>" class="block w-full text-center py-3 rounded-xl font-semibold text-sm transition-all <?= $isPopular ? 'bg-primary-600 hover:bg-primary-700 text-white shadow-lg shadow-primary-500/25' : 'bg-gray-100 hover:bg-gray-200 text-gray-800' ?>">
                        Get Started
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Trust Badges -->
        <div class="mt-12 flex flex-wrap justify-center gap-6 text-sm text-gray-500 animate-on-scroll">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Affordable for all sizes
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Flexible payment options
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Guided onboarding included
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ TESTIMONIALS ═══════════════════ -->
<section id="testimonials" class="py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 animate-on-scroll">
            <div class="inline-flex items-center gap-2 bg-primary-50 border border-primary-100 text-primary-700 text-xs font-semibold px-3 py-1.5 rounded-full mb-4">Testimonials</div>
            <h2 class="text-3xl lg:text-4xl font-extrabold tracking-tight"><?= e($testimonialsIntro['title'] ?? 'What Schools Are Saying') ?></h2>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($testimonials as $t): ?>
            <div class="p-6 rounded-2xl border border-gray-100 bg-white hover:shadow-lg transition-all duration-300 animate-on-scroll">
                <div class="flex gap-1 mb-4">
                    <?php for ($s = 0; $s < 5; $s++): ?>
                    <svg class="w-4 h-4 <?= $s < $t['rating'] ? 'text-amber-400' : 'text-gray-200' ?>" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <?php endfor; ?>
                </div>
                <p class="text-sm text-gray-700 leading-relaxed mb-5">"<?= e($t['content']) ?>"</p>
                <div class="flex items-center gap-3 pt-4 border-t border-gray-50">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white text-sm font-bold">
                        <?= strtoupper(substr($t['person_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-gray-900"><?= e($t['person_name']) ?></div>
                        <div class="text-xs text-gray-500"><?= e($t['person_role']) ?> · <?= e($t['school_name']) ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════ FAQ ═══════════════════ -->
<section id="faq" class="py-20 lg:py-28 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 animate-on-scroll">
            <div class="inline-flex items-center gap-2 bg-primary-50 border border-primary-100 text-primary-700 text-xs font-semibold px-3 py-1.5 rounded-full mb-4">FAQ</div>
            <h2 class="text-3xl lg:text-4xl font-extrabold tracking-tight"><?= e($faqIntro['title'] ?? 'Frequently Asked Questions') ?></h2>
        </div>
        <div class="space-y-3">
            <?php foreach ($faqs as $faq): ?>
            <div class="faq-item border border-gray-100 rounded-xl bg-white overflow-hidden transition-all hover:border-gray-200 animate-on-scroll">
                <button class="faq-toggle w-full flex items-center justify-between p-5 text-left">
                    <span class="font-semibold text-gray-900 text-sm pr-4"><?= e($faq['question']) ?></span>
                    <svg class="faq-chevron w-5 h-5 text-gray-400 flex-shrink-0 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="faq-content overflow-hidden max-h-0 transition-all duration-300">
                    <div class="px-5 pb-5 text-sm text-gray-600 leading-relaxed"><?= e($faq['answer']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════ FINAL CTA ═══════════════════ -->
<section id="contact" class="py-20 lg:py-28 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-primary-600 via-primary-700 to-indigo-800"></div>
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSA2MCAwIEwgMCAwIDAgNjAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMC41IiBvcGFjaXR5PSIwLjA1Ii8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2dyaWQpIi8+PC9zdmc+')] opacity-30"></div>
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div class="text-white animate-on-scroll">
                <h2 class="text-3xl lg:text-4xl font-extrabold tracking-tight"><?= e($finalCta['title'] ?? 'Start Your School\'s Digital Transformation') ?></h2>
                <p class="mt-4 text-lg text-primary-100 leading-relaxed"><?= e($finalCta['subtitle'] ?? 'Join hundreds of schools already using Eduelevate.') ?></p>
                <div class="mt-6 space-y-3">
                    <div class="flex items-center gap-3 text-primary-100">
                        <svg class="w-5 h-5 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-sm">Free personalized demo</span>
                    </div>
                    <div class="flex items-center gap-3 text-primary-100">
                        <svg class="w-5 h-5 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-sm">No commitment required</span>
                    </div>
                    <div class="flex items-center gap-3 text-primary-100">
                        <svg class="w-5 h-5 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-sm">Flexible payment plans</span>
                    </div>
                </div>
            </div>
            <div class="animate-on-scroll">
                <form id="contactForm" action="<?= base_url('contact') ?>" method="POST" class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                    <?= csrf_field() ?>
                    <input type="hidden" name="type" value="demo_request">
                    <div class="space-y-4">
                        <div>
                            <input type="text" name="name" placeholder="Your Full Name" required class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 text-sm focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-transparent">
                        </div>
                        <div>
                            <input type="email" name="email" placeholder="Email Address" required class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 text-sm focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-transparent">
                        </div>
                        <div>
                            <input type="text" name="school_name" placeholder="School Name" class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 text-sm focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-transparent">
                        </div>
                        <div>
                            <input type="tel" name="phone" placeholder="Phone Number" class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 text-sm focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-transparent">
                        </div>
                        <button type="submit" class="w-full bg-white text-primary-700 font-bold py-3.5 rounded-xl hover:bg-primary-50 transition-colors text-sm shadow-lg">
                            Request Demo
                        </button>
                    </div>
                    <div id="contactFormMessage" class="hidden mt-3 text-center text-sm"></div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ FOOTER ═══════════════════ -->
<footer class="bg-gray-900 text-gray-400 pt-16 pb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-4 gap-8 mb-12">
            <div class="md:col-span-1">
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                    </div>
                    <span class="text-lg font-bold text-white">Eduelevate</span>
                </div>
                <p class="text-sm leading-relaxed"><?= e($footer['subtitle'] ?? 'Smart school management for the modern age.') ?></p>
                <?php $footerData = $footer['extra_data'] ?? []; ?>
                <?php if (!empty($footerData['email'])): ?>
                <p class="text-sm mt-4"><?= e($footerData['email']) ?></p>
                <?php endif; ?>
                <?php if (!empty($footerData['phone'])): ?>
                <p class="text-sm"><?= e($footerData['phone']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-white mb-4">Product</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="#features" class="hover:text-white transition-colors">Features</a></li>
                    <li><a href="#pricing" class="hover:text-white transition-colors">Pricing</a></li>
                    <li><a href="#showcase" class="hover:text-white transition-colors">Product Tour</a></li>
                    <li><a href="#testimonials" class="hover:text-white transition-colors">Testimonials</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-white mb-4">Company</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="#" class="hover:text-white transition-colors">About Us</a></li>
                    <li><a href="#contact" class="hover:text-white transition-colors">Contact</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Blog</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Careers</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-white mb-4">Support</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="#faq" class="hover:text-white transition-colors">FAQ</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Documentation</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        <div class="pt-8 border-t border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs">&copy; <?= date('Y') ?> Eduelevate. All rights reserved.</p>
            <div class="flex items-center gap-4">
                <span class="text-xs">Made with ❤️ in Ethiopia</span>
            </div>
        </div>
    </div>
</footer>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
