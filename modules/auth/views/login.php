<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>" class="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('sign_in') ?> — <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
    <link rel="manifest" href="<?= url('manifest.webmanifest') ?>">
    <meta name="theme-color" content="#1e40af">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' },
                        dark: {"bg":"#0f172a","card":"#1e293b","card2":"#334155","border":"#475569","text":"#e2e8f0","muted":"#94a3b8"}
                    }
                }
            }
        }
    </script>
    <script>
    (function(){var t=document.cookie.match(/(?:^|;\s*)theme=(\w+)/);if(t&&t[1]==='dark')document.documentElement.classList.add('dark');else document.documentElement.classList.remove('dark');})();
    </script>
</head>
<body class="bg-gray-100 dark:bg-dark-bg min-h-screen flex items-center justify-center p-4">

    <!-- Theme + Language toggle (top-right corner) -->
    <div class="fixed top-4 right-4 flex items-center gap-2 z-50">
        <a href="?lang=<?= get_lang() === 'en' ? 'am' : 'en' ?>" class="px-2 py-1.5 rounded-lg text-xs font-medium text-gray-600 dark:text-dark-muted bg-white dark:bg-dark-card hover:bg-gray-100 dark:hover:bg-dark-card2 border border-gray-200 dark:border-dark-border transition-colors">
            <?= get_lang() === 'en' ? '🇪🇹 አማ' : '🇬🇧 EN' ?>
        </a>
        <button onclick="toggleTheme()" class="p-1.5 rounded-lg text-gray-600 dark:text-dark-muted bg-white dark:bg-dark-card hover:bg-gray-100 dark:hover:bg-dark-card2 border border-gray-200 dark:border-dark-border transition-colors">
            <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <svg class="w-4 h-4 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
    </div>

    <div class="w-full max-w-md">
        <!-- Logo / School Name -->
        <div class="text-center mb-8">
            <img src="<?= url('/img/Logo.png') ?>" alt="Urji Beri School" class="w-16 h-16 mx-auto rounded-xl mb-4 object-contain">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-dark-text"><?= e(APP_NAME) ?></h1>
            <p class="text-gray-500 dark:text-dark-muted mt-1">Sign in to your account</p>
        </div>

        <!-- Flash Messages -->
        <?php if ($flash = get_flash('success')): ?>
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm"><?= e($flash) ?></div>
        <?php endif; ?>
        <?php if ($flash = get_flash('error')): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm"><?= e($flash) ?></div>
        <?php endif; ?>
        <?php if ($flash = get_flash('warning')): ?>
            <div class="mb-4 p-3 bg-yellow-100 text-yellow-700 rounded-lg text-sm"><?= e($flash) ?></div>
        <?php endif; ?>

        <!-- Login Card -->
        <div class="bg-white dark:bg-dark-card rounded-xl shadow-sm border border-gray-200 dark:border-dark-border p-6 sm:p-8">
            <form method="POST" action="<?= url('auth', 'login') ?>" class="space-y-5">
                <?= csrf_field() ?>

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username or Email</label>
                    <input type="text" id="username" name="username" value="<?= e(old('username')) ?>"
                           required autofocus autocomplete="username"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-dark-border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                           placeholder="Enter your username or email">
                    <?php if ($err = get_validation_error('username')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password"
                               required autocomplete="current-password"
                               class="w-full px-3 py-2.5 border border-gray-300 dark:border-dark-border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm pr-10"
                               placeholder="Enter your password">
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:text-dark-muted">
                            <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    <?php if ($err = get_validation_error('password')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Remember Me + Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center text-sm">
                        <input type="checkbox" name="remember" class="rounded text-primary-600 focus:ring-primary-500 mr-2">
                        Remember me
                    </label>
                    <a href="<?= url('auth', 'forgot-password') ?>" class="text-sm text-primary-600 hover:text-primary-700">Forgot password?</a>
                </div>

                <!-- Submit -->
                <button type="submit"
                        class="w-full py-2.5 px-4 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    Sign In
                </button>
            </form>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-6">
            &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.
        </p>
    </div>

    <script>
        function togglePassword() {
            var input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
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
