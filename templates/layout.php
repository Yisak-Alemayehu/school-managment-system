<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= e($page_title ?? 'Dashboard') ?> — <?= e(get_school_name()) ?></title>
    
    <!-- PWA Meta -->
    <?= pwa_meta_tags() ?>
    <?= csrf_meta() ?>
    
    <!-- Tailwind CSS (CDN for dev; use build for production) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: {"50":"#eff6ff","100":"#dbeafe","200":"#bfdbfe","300":"#93c5fd","400":"#60a5fa","500":"#3b82f6","600":"#2563eb","700":"#1d4ed8","800":"#1e40af","900":"#1e3a8a"},
                    sidebar: {"bg":"#1e293b","hover":"#334155","active":"#0f172a","text":"#cbd5e1"}
                }
            }
        }
    }
    </script>
    
    <style>
        /* Mobile-first base styles */
        [x-cloak] { display: none !important; }
        .sidebar-transition { transition: transform 0.3s ease-in-out; }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 3px; }
        
        /* Mobile table cards */
        @media (max-width: 768px) {
            .responsive-table thead { display: none; }
            .responsive-table tr { display: block; margin-bottom: 0.75rem; background: white; border-radius: 0.5rem; padding: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
            .responsive-table td { display: flex; justify-content: space-between; padding: 0.375rem 0; border: none; }
            .responsive-table td::before { content: attr(data-label); font-weight: 600; color: #475569; margin-right: 1rem; }
        }
        
        /* Print styles */
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
        }
    </style>
</head>
<body class="h-full bg-gray-50">
    <?php if (auth_check()): ?>
    <div class="flex h-full" id="app">
        <!-- Mobile sidebar overlay -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>
        
        <!-- Sidebar -->
        <?php partial('sidebar'); ?>
        
        <!-- Main content -->
        <div class="flex-1 flex flex-col min-h-screen lg:ml-64">
            <!-- Top header -->
            <?php partial('header'); ?>
            
            <!-- Flash messages -->
            <?php partial('flash'); ?>
            
            <!-- Page content -->
            <main class="flex-1 p-4 md:p-6 max-w-7xl w-full mx-auto">
                <?= $content ?? '' ?>
            </main>
            
            <!-- Mobile bottom navigation -->
            <?php partial('mobile_nav'); ?>
        </div>
    </div>
    <?php else: ?>
        <?= $content ?? '' ?>
    <?php endif; ?>
    
    <!-- PWA offline indicator -->
    <?= pwa_status_indicator() ?>
    
    <!-- Back to top -->
    <button id="back-to-top" class="hidden fixed bottom-20 right-4 lg:bottom-6 lg:right-6 w-10 h-10 bg-primary-800 text-white rounded-full shadow-lg flex items-center justify-center z-40 hover:bg-primary-900 transition-colors no-print" aria-label="Back to top">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
    </button>
    
    <!-- Core JS -->
    <script src="<?= url('/assets/js/app.js') ?>"></script>
    
    <!-- PWA registration -->
    <?= pwa_register_script() ?>
</body>
</html>
