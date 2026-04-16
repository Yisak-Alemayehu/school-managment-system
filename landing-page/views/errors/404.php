<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found – Eduelevate</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body class="font-['Inter'] bg-gray-50 min-h-screen flex items-center justify-center p-6">
    <div class="text-center max-w-md">
        <div class="text-8xl font-extrabold text-gray-200 mb-4">404</div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Page Not Found</h1>
        <p class="text-gray-500 mb-8">The page you're looking for doesn't exist or has been moved.</p>
        <a href="<?= base_url() ?>" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Home
        </a>
    </div>
</body>
</html>
