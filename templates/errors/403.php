<!DOCTYPE html>
<html lang="en" class="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Access Denied</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = { darkMode: 'class', theme: { extend: { colors: { dark: {"bg":"#0f172a","card":"#1e293b","text":"#e2e8f0","muted":"#94a3b8"} } } } }
    </script>
    <script>(function(){var t=document.cookie.match(/(?:^;\s*)theme=(\w+)/);if(t&&t[1]==='dark')document.documentElement.classList.add('dark');})();</script>
</head>
<body class="bg-gray-50 dark:bg-dark-bg flex items-center justify-center min-h-screen p-4 transition-colors">
    <div class="text-center max-w-md">
        <div class="text-8xl font-bold text-gray-200 dark:text-gray-800 mb-4">403</div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-dark-text mb-2">Access Denied</h1>
        <p class="text-gray-600 dark:text-dark-muted mb-6">You don't have permission to access this page. Contact your administrator if you believe this is an error.</p>
        <a href="/" class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Go to Dashboard
        </a>
    </div>
</body>
</html>
