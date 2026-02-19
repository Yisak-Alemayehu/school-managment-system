<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
    <meta name="theme-color" content="#1e40af">
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' } } } } }
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-800 rounded-xl mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Reset Password</h1>
            <p class="text-gray-500 mt-1">Enter your new password</p>
        </div>

        <?php if ($flash = get_flash('error')): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm"><?= e($flash) ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sm:p-8">
            <form method="POST" action="<?= url('auth', 'reset-password') ?>" class="space-y-5">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token ?? '') ?>">

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                           placeholder="Min 8 chars, upper, lower, number, symbol">
                    <?php if ($err = get_validation_error('password')): ?>
                        <p class="mt-1 text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                           placeholder="Re-enter your new password">
                </div>

                <button type="submit"
                        class="w-full py-2.5 px-4 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    Reset Password
                </button>
            </form>
        </div>

        <p class="text-center mt-4">
            <a href="<?= url('auth', 'login') ?>" class="text-sm text-primary-600 hover:text-primary-700">&larr; Back to Sign In</a>
        </p>
    </div>
</body>
</html>
