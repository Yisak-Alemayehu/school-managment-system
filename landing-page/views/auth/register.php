<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Started – Eduelevate</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','system-ui','sans-serif']},colors:{primary:{50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a'}}}}}</script>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="font-sans bg-gray-50 antialiased min-h-screen flex">
    <!-- Left Panel -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary-600 via-primary-700 to-indigo-800 relative overflow-hidden items-center justify-center p-12">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSA2MCAwIEwgMCAwIDAgNjAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMC41IiBvcGFjaXR5PSIwLjA1Ii8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2dyaWQpIi8+PC9zdmc+')] opacity-30"></div>
        <div class="relative text-white max-w-md">
            <div class="flex items-center gap-2.5 mb-8">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                </div>
                <span class="text-xl font-bold">Eduelevate</span>
            </div>
            <h1 class="text-3xl font-extrabold leading-tight mb-4">Start your school's<br>digital transformation</h1>
            <p class="text-primary-100 leading-relaxed">Join hundreds of forward-thinking schools using Eduelevate to streamline operations and improve student outcomes.</p>
            <div class="mt-10 grid grid-cols-2 gap-4">
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                    <div class="text-2xl font-bold">500+</div>
                    <div class="text-xs text-primary-200 mt-1">Schools Trust Us</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                    <div class="text-2xl font-bold">20hrs</div>
                    <div class="text-xs text-primary-200 mt-1">Saved Per Week</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                    <div class="text-2xl font-bold">99.9%</div>
                    <div class="text-xs text-primary-200 mt-1">Uptime</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                    <div class="text-2xl font-bold">24/7</div>
                    <div class="text-xs text-primary-200 mt-1">Support</div>
                </div>
            </div>
        </div>
    </div>
    <!-- Right Panel -->
    <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
        <div class="w-full max-w-md">
            <div class="lg:hidden flex items-center gap-2.5 mb-8">
                <div class="w-9 h-9 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center shadow-lg shadow-primary-500/25">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                </div>
                <span class="text-xl font-bold bg-gradient-to-r from-primary-600 to-primary-800 bg-clip-text text-transparent">Eduelevate</span>
            </div>
            <h2 class="text-2xl font-extrabold text-gray-900 mb-1">Create your account</h2>
            <p class="text-sm text-gray-500 mb-8">Already have an account? <a href="<?= base_url('login') ?>" class="text-primary-600 hover:text-primary-700 font-semibold">Sign in</a></p>

            <?php if ($err = get_flash('error')): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100 text-sm text-red-700 flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= e($err) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= base_url('register') ?>" class="space-y-4">
                <?= csrf_field() ?>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" name="name" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all" placeholder="Your name">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" name="phone" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all" placeholder="+251...">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" required autocomplete="email" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all" placeholder="you@school.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">School Name</label>
                    <input type="text" name="school_name" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all" placeholder="Your school name">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Number of Students</label>
                    <select name="student_count" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all bg-white">
                        <option value="250">Less than 500</option>
                        <option value="750">500 – 1,000</option>
                        <option value="1500">More than 1,000</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" id="regPassword" required minlength="8" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all" placeholder="Min 8 chars">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm</label>
                        <input type="password" name="password_confirm" required minlength="8" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all" placeholder="Repeat">
                    </div>
                </div>
                <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 rounded-xl shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 transition-all text-sm mt-2">
                    Request Access
                </button>
                <p class="text-xs text-gray-400 text-center mt-2">By signing up, you agree to our Terms of Service and Privacy Policy.</p>
            </form>

            <p class="mt-6 text-center text-xs text-gray-400">
                <a href="<?= base_url() ?>" class="hover:text-gray-600 transition-colors">&larr; Back to homepage</a>
            </p>
        </div>
    </div>
</body>
</html>
