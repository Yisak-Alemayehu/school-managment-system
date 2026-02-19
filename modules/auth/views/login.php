<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
    <link rel="manifest" href="<?= url('manifest.webmanifest') ?>">
    <meta name="theme-color" content="#1e40af">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo / School Name -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-800 rounded-xl mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900"><?= e(APP_NAME) ?></h1>
            <p class="text-gray-500 mt-1">Sign in to your account</p>
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
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sm:p-8">
            <form method="POST" action="<?= url('auth', 'login') ?>" class="space-y-5">
                <?= csrf_field() ?>

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username or Email</label>
                    <input type="text" id="username" name="username" value="<?= e(old('username')) ?>"
                           required autofocus autocomplete="username"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                           placeholder="Enter your username or email">
                    <?php if ($err = get_validation_error('username')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password"
                               required autocomplete="current-password"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm pr-10"
                               placeholder="Enter your password">
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
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
        <p class="text-center text-xs text-gray-400 mt-6">
            &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.
        </p>
    </div>

    <script>
        function togglePassword() {
            var input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
