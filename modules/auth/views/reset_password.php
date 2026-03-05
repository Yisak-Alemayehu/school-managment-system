<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>" class="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('reset_password') ?> — <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
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

    <!-- Theme toggle (top-right corner) -->
    <div class="fixed top-4 right-4 z-50 flex items-center gap-2">
        <button onclick="toggleTheme()" class="p-2 rounded-lg bg-white dark:bg-dark-card border border-gray-200 dark:border-dark-border shadow-sm hover:bg-gray-50 dark:hover:bg-dark-card2 transition" title="Toggle theme">
            <svg class="w-5 h-5 text-gray-600 dark:text-dark-muted hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <svg class="w-5 h-5 text-gray-600 dark:text-dark-muted block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
    </div>

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-800 rounded-xl mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-dark-text"><?= __('reset_password') ?></h1>
            <p class="text-gray-500 dark:text-dark-muted mt-1"><?= __('reset_password_text') ?></p>
        </div>

        <?php if ($flash = get_flash('error')): ?>
            <div class="mb-4 p-3 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg text-sm"><?= e($flash) ?></div>
        <?php endif; ?>

        <div class="bg-white dark:bg-dark-card rounded-xl shadow-sm border border-gray-200 dark:border-dark-border p-6 sm:p-8">
            <form method="POST" action="<?= url('auth', 'reset-password') ?>" class="space-y-5">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token ?? '') ?>">

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('new_password') ?></label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-dark-border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm bg-white dark:bg-dark-card2 text-gray-900 dark:text-dark-text"
                           placeholder="Min 8 chars, upper, lower, number, symbol">
                    <?php if ($err = get_validation_error('password')): ?>
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('confirm_password') ?></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-dark-border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm bg-white dark:bg-dark-card2 text-gray-900 dark:text-dark-text"
                           placeholder="Re-enter your new password">
                </div>

                <button type="submit"
                        class="w-full py-2.5 px-4 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <?= __('reset_password') ?>
                </button>
            </form>
        </div>

        <p class="text-center mt-4">
            <a href="<?= url('auth', 'login') ?>" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">&larr; <?= __('back_to_sign_in') ?></a>
        </p>
    </div>

    <script>
    function toggleTheme(){
        var d=document.documentElement;
        d.classList.toggle('dark');
        var theme=d.classList.contains('dark')?'dark':'light';
        document.cookie='theme='+theme+';path=/;max-age=31536000;SameSite=Lax';
        var m=document.querySelector('meta[name="theme-color"]');
        if(m)m.setAttribute('content',theme==='dark'?'#0f172a':'#1e40af');
    }
    </script>
</body>
</html>