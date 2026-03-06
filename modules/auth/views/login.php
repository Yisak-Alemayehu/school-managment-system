<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>" class="<?= isset($_COOKIE['theme']) ? ($_COOKIE['theme'] === 'dark' ? 'dark' : '') : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('sign_in') ?> — <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
    <link rel="manifest" href="<?= url('manifest.webmanifest') ?>">
    <meta name="theme-color" content="#074DD9">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#eef2ff',100:'#dbe4ff',200:'#bfcefe',300:'#93adfc',400:'#6080f8',500:'#3b5ef4',600:'#074DD9',700:'#0640b8',800:'#0a3596',900:'#0d2d76',950:'#091c4d' },
                        accent: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb' },
                        dark: {"bg":"#0f172a","card":"#1e293b","card2":"#334155","border":"#475569","text":"#e2e8f0","muted":"#94a3b8"}
                    }
                }
            }
        }
    </script>
    <script>
    (function(){var t=document.cookie.match(/(?:^|;\s*)theme=(\w+)/);if(t){if(t[1]==='dark')document.documentElement.classList.add('dark');else document.documentElement.classList.remove('dark');}else if(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches){document.documentElement.classList.add('dark');}})();
    </script>
    <style>
        .blob-1 { position: absolute; top: -60px; right: -60px; width: 200px; height: 200px; background: linear-gradient(135deg, #074DD9 0%, #3b82f6 100%); border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; opacity: 0.15; animation: blob-float 8s ease-in-out infinite; }
        .blob-2 { position: absolute; bottom: -40px; left: -40px; width: 160px; height: 160px; background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); border-radius: 40% 60% 70% 30% / 40% 70% 30% 60%; opacity: 0.12; animation: blob-float 10s ease-in-out infinite reverse; }
        .blob-3 { position: absolute; top: 40%; left: -30px; width: 100px; height: 100px; background: linear-gradient(135deg, #3b5ef4 0%, #93c5fd 100%); border-radius: 50% 50% 40% 60% / 60% 40% 50% 50%; opacity: 0.08; animation: blob-float 12s ease-in-out infinite; }
        @keyframes blob-float { 0%, 100% { transform: translate(0, 0) scale(1); } 33% { transform: translate(10px, -15px) scale(1.05); } 66% { transform: translate(-5px, 10px) scale(0.95); } }
        .login-input { transition: all 0.2s ease; }
        .login-input:focus { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(7, 77, 217, 0.15); }
        .login-btn { background: linear-gradient(135deg, #074DD9 0%, #0640b8 100%); transition: all 0.3s ease; }
        .login-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(7, 77, 217, 0.3); }
        .login-btn:active { transform: translateY(0); }
        .dark .blob-1, .dark .blob-2, .dark .blob-3 { opacity: 0.06; }
    </style>
</head>
<body class="bg-white dark:bg-dark-bg min-h-screen flex items-center justify-center p-4 overflow-hidden relative">

    <!-- Decorative Blobs -->
    <div class="blob-1"></div>
    <div class="blob-2"></div>
    <div class="blob-3"></div>

    <!-- Theme + Language toggle (top-right corner) -->
    <div class="fixed top-4 right-4 flex items-center gap-2 z-50">
        <a href="?lang=<?= get_lang() === 'en' ? 'am' : 'en' ?>" class="px-2.5 py-1.5 rounded-full text-xs font-medium text-gray-600 dark:text-dark-muted bg-white/80 dark:bg-dark-card/80 backdrop-blur-sm hover:bg-white dark:hover:bg-dark-card border border-gray-200/60 dark:border-dark-border/60 transition-all">
            <?= get_lang() === 'en' ? '🇪🇹 አማ' : '🇬🇧 EN' ?>
        </a>
        <button onclick="toggleTheme()" class="p-2 rounded-full text-gray-600 dark:text-dark-muted bg-white/80 dark:bg-dark-card/80 backdrop-blur-sm hover:bg-white dark:hover:bg-dark-card border border-gray-200/60 dark:border-dark-border/60 transition-all">
            <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <svg class="w-4 h-4 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
    </div>

    <div class="w-full max-w-sm relative z-10">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto mb-5 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 p-0.5 shadow-lg shadow-primary-500/20">
                <img src="<?= url('/img/Logo.png') ?>" alt="<?= e(APP_NAME) ?>" class="w-full h-full rounded-2xl object-contain bg-white dark:bg-dark-card p-1">
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-dark-text">Welcome Back!</h1>
            <p class="text-gray-500 dark:text-dark-muted mt-1 text-sm">Sign in to continue to your portal</p>
        </div>

        <!-- Flash Messages -->
        <?php if ($flash = get_flash('success')): ?>
            <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-xl text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= e($flash) ?>
            </div>
        <?php endif; ?>
        <?php if ($flash = get_flash('error')): ?>
            <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-xl text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= e($flash) ?>
            </div>
        <?php endif; ?>
        <?php if ($flash = get_flash('warning')): ?>
            <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 rounded-xl text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                <?= e($flash) ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="<?= url('auth', 'login') ?>" class="space-y-5">
            <?= csrf_field() ?>

            <!-- Username / Email -->
            <div>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 dark:text-dark-muted">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                    </span>
                    <input type="text" id="username" name="username" value="<?= e(old('username')) ?>"
                           required autofocus autocomplete="username"
                           class="login-input w-full pl-11 pr-4 py-3.5 bg-gray-50 dark:bg-dark-card border border-gray-200 dark:border-dark-border rounded-xl focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 text-sm text-gray-900 dark:text-dark-text placeholder-gray-400 dark:placeholder-dark-muted outline-none"
                           placeholder="Username or Email">
                </div>
                <?php if ($err = get_validation_error('username')): ?>
                    <p class="mt-1.5 text-xs text-red-500"><?= e($err) ?></p>
                <?php endif; ?>
            </div>

            <!-- Password -->
            <div>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 dark:text-dark-muted">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </span>
                    <input type="password" id="password" name="password"
                           required autocomplete="current-password"
                           class="login-input w-full pl-11 pr-12 py-3.5 bg-gray-50 dark:bg-dark-card border border-gray-200 dark:border-dark-border rounded-xl focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 text-sm text-gray-900 dark:text-dark-text placeholder-gray-400 dark:placeholder-dark-muted outline-none"
                           placeholder="Password">
                    <button type="button" onclick="togglePassword()" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 dark:text-dark-muted hover:text-gray-600 transition-colors">
                        <svg id="eye-open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg id="eye-closed" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
                <?php if ($err = get_validation_error('password')): ?>
                    <p class="mt-1.5 text-xs text-red-500"><?= e($err) ?></p>
                <?php endif; ?>
            </div>

            <!-- Remember + Forgot -->
            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 dark:border-dark-border text-primary-600 focus:ring-primary-500 focus:ring-offset-0">
                    <span class="text-gray-600 dark:text-dark-muted">Remember me</span>
                </label>
                <a href="<?= url('auth', 'forgot-password') ?>" class="text-primary-600 hover:text-primary-700 font-medium">Forgot password?</a>
            </div>

            <!-- Sign In Button -->
            <button type="submit" class="login-btn w-full py-3.5 px-4 text-white font-semibold rounded-xl text-sm focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Sign In
            </button>
        </form>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-xs text-gray-400 dark:text-gray-500">
                &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>
            </p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                Urji Beri School Management System
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            var input = document.getElementById('password');
            var eyeOpen = document.getElementById('eye-open');
            var eyeClosed = document.getElementById('eye-closed');
            if (input.type === 'password') {
                input.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        }
        function toggleTheme() {
            var html = document.documentElement;
            var isDark = html.classList.contains('dark');
            if (isDark) { html.classList.remove('dark'); document.cookie='theme=light;path=/;max-age='+(365*86400)+';SameSite=Lax'; }
            else { html.classList.add('dark'); document.cookie='theme=dark;path=/;max-age='+(365*86400)+';SameSite=Lax'; }
        }
    </script>
</body>
</html>
