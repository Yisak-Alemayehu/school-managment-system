<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>" class="h-full <?= isset($_COOKIE['theme']) ? ($_COOKIE['theme'] === 'dark' ? 'dark' : '') : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title><?= e($page_title ?? 'Dashboard') ?> — <?= e(get_school_name()) ?></title>
    
    <!-- PWA Meta -->
    <?= pwa_meta_tags() ?>
    <?= csrf_meta() ?>
    
    <!-- Tailwind CSS (CDN for dev; use build for production) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    primary: {"50":"#eef2ff","100":"#dbe4ff","200":"#bfcefe","300":"#93adfc","400":"#6080f8","500":"#3b5ef4","600":"#074DD9","700":"#0640b8","800":"#0a3596","900":"#0d2d76","950":"#091c4d"},
                    sidebar: {"bg":"#091c4d","hover":"#0d2d76","active":"#074DD9","text":"#bfcefe"},
                    dark: {"bg":"#0f172a","card":"#1e293b","card2":"#334155","border":"#475569","text":"#e2e8f0","muted":"#94a3b8"}
                }
            }
        }
    }
    </script>
    <!-- Prevent flash of wrong theme + theme toggle (must be in head so onclick works immediately) -->
    <script>
    (function(){
        var t = document.cookie.match(/(?:^|;\s*)theme=(\w+)/);
        if(t) {
            if(t[1]==='dark') document.documentElement.classList.add('dark');
            else document.documentElement.classList.remove('dark');
        } else if(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    })();
    function toggleTheme() {
        var html = document.documentElement;
        html.classList.add('theme-transition');
        var isDark = html.classList.contains('dark');
        if (isDark) {
            html.classList.remove('dark');
            document.cookie = 'theme=light;path=/;max-age=' + (365*86400) + ';SameSite=Lax';
        } else {
            html.classList.add('dark');
            document.cookie = 'theme=dark;path=/;max-age=' + (365*86400) + ';SameSite=Lax';
        }
        var meta = document.querySelector('meta[name="theme-color"]');
        if (meta) meta.content = isDark ? '#0f172a' : '#074DD9';
        setTimeout(function(){ html.classList.remove('theme-transition'); }, 350);
    }
    function toggleLangMenu() {
        var menu = document.getElementById('lang-menu');
        if (menu) menu.classList.toggle('hidden');
    }
    function toggleSidebar() {
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        if (!sidebar) return;
        var isOpen = !sidebar.classList.contains('-translate-x-full');
        if (isOpen) {
            sidebar.classList.add('-translate-x-full');
            if (overlay) overlay.classList.add('hidden');
            document.body.style.overflow = '';
        } else {
            sidebar.classList.remove('-translate-x-full');
            if (overlay) overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }
    function toggleProfileMenu() {
        var menu = document.getElementById('profile-menu');
        if (menu) menu.classList.toggle('hidden');
    }
    </script>
    
    <style>
        /* Mobile-first base styles */
        [x-cloak] { display: none !important; }
        .sidebar-transition { transition: transform 0.3s ease-in-out; }
        
        /* Theme transition */
        html.theme-transition, html.theme-transition *, html.theme-transition *::before, html.theme-transition *::after {
            transition: background-color 0.3s ease, color 0.2s ease, border-color 0.2s ease !important;
        }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 3px; }
        .dark ::-webkit-scrollbar-track { background: #1e293b; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
        
        /* Print styles */
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
        }

        /* Dark mode form inputs (inline — guaranteed to load) */
        html.dark input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="file"]):not([type="range"]):not([type="hidden"]),
        html.dark select,
        html.dark textarea {
            background-color: #1e293b !important;
            color: #e2e8f0 !important;
            border-color: #475569 !important;
            color-scheme: dark;
        }
        html.dark input::placeholder,
        html.dark textarea::placeholder {
            color: #94a3b8 !important;
        }
        html.dark input:focus:not([type="checkbox"]):not([type="radio"]),
        html.dark select:focus,
        html.dark textarea:focus {
            background-color: #334155 !important;
            border-color: #6080f8 !important;
            color: #f1f5f9 !important;
        }
        html.dark option {
            background-color: #1e293b !important;
            color: #e2e8f0 !important;
        }
        html.dark input[type="file"] {
            background-color: #1e293b !important;
            color: #94a3b8 !important;
        }
        html.dark input[type="file"]::file-selector-button {
            background-color: #334155 !important;
            color: #e2e8f0 !important;
            border-color: #475569 !important;
        }
        html.dark input[type="date"],
        html.dark input[type="time"],
        html.dark input[type="datetime-local"] {
            background-color: #1e293b !important;
            color: #e2e8f0 !important;
            color-scheme: dark;
        }
        html.dark input[type="date"]::-webkit-calendar-picker-indicator,
        html.dark input[type="time"]::-webkit-calendar-picker-indicator,
        html.dark input[type="datetime-local"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }
    </style>
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>?v=<?= filemtime(APP_ROOT . '/public/assets/css/app.css') ?>">
</head>
<body class="min-h-screen flex flex-col bg-gray-50 dark:bg-dark-bg text-gray-900 dark:text-dark-text transition-colors">
    <div class="flex-1">
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
                <main class="flex-1 p-4 md:p-6 max-w-7xl w-full mx-auto animate-fade-in">
                    <?= $content ?? '' ?>
                </main>
                
                <!-- Mobile bottom navigation -->
                <?php partial('mobile_nav'); ?>
            </div>
        </div>
        <?php else: ?>
            <?= $content ?? '' ?>
        <?php endif; ?>
    </div>

    <!-- Copyright footer -->
    <footer class="w-full border-t border-gray-200 dark:border-dark-border py-3 text-center text-xs text-gray-500 dark:text-dark-muted">
        &copy; <?= date('Y') ?> Developed by <a href="https://yisak.dev" target="_blank" rel="noopener noreferrer" class="text-primary-600 dark:text-primary-300 hover:underline">Yisak A. Alemayehu</a>.
    </footer>

    <!-- PWA offline indicator -->
    <?= pwa_status_indicator() ?>
    
    <!-- Back to top -->
    <button id="back-to-top" class="hidden fixed bottom-20 right-4 lg:bottom-6 lg:right-6 w-10 h-10 bg-primary-800 text-white rounded-full shadow-lg flex items-center justify-center z-40 hover:bg-primary-900 transition-colors no-print" aria-label="Back to top">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
    </button>
    
    <!-- Alpine.js (used by date pickers and modals) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Core JS -->
    <script src="<?= url('/assets/js/app.js') ?>"></script>

    <!-- ── Shared AJAX helpers (class → sections / subjects) ───────── -->
    <script>
    (function () {
        var BASE = '<?= rtrim(APP_URL, '/') ?>';

        /**
         * Populate a <select> with sections that belong to the given class.
         *
         * @param {string|number} classId       - selected class_id value
         * @param {string}        selectId       - id of the section <select> element
         * @param {string|number} [preselected]  - section_id to pre-select
         * @param {string}        [defaultLabel] - first empty-value option label
         */
        window.ajaxLoadSections = function (classId, selectId, preselected, defaultLabel) {
            var sel = document.getElementById(selectId);
            if (!sel) return;
            preselected  = preselected  || 0;
            defaultLabel = defaultLabel || 'All Sections';

            if (!classId) {
                sel.innerHTML = '<option value="">' + defaultLabel + '</option>';
                sel.disabled = true;
                return;
            }

            sel.disabled = true;
            sel.innerHTML = '<option value="">Loading…</option>';

            fetch(BASE + '/api/sections?' + new URLSearchParams({ class_id: classId }))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    sel.innerHTML = '<option value="">' + defaultLabel + '</option>';
                    data.forEach(function (s) {
                        var o = document.createElement('option');
                        o.value       = s.id;
                        o.textContent = s.name;
                        if (String(s.id) === String(preselected)) o.selected = true;
                        sel.appendChild(o);
                    });
                    sel.disabled = false;
                })
                .catch(function () {
                    sel.innerHTML = '<option value="">' + defaultLabel + '</option>';
                    sel.disabled = false;
                });
        };

        /**
         * Populate a <select> with subjects for the given class + session.
         *
         * @param {string|number} classId    - class_id
         * @param {string|number} sessionId  - session_id
         * @param {string}        selectId   - id of the subject <select> element
         * @param {string|number} [preselected] - subject_id to pre-select
         */
        window.ajaxLoadSubjects = function (classId, sessionId, selectId, preselected) {
            var sel = document.getElementById(selectId);
            if (!sel) return;

            if (!classId || !sessionId) {
                sel.innerHTML = '<option value="">— Select Class First —</option>';
                sel.disabled = true;
                return;
            }

            sel.disabled = true;
            sel.innerHTML = '<option value="">Loading…</option>';

            fetch(BASE + '/api/subjects?' + new URLSearchParams({ class_id: classId, session_id: sessionId }))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    sel.innerHTML = '<option value="">Select Subject</option>';
                    data.forEach(function (s) {
                        var o = document.createElement('option');
                        o.value       = s.id;
                        o.textContent = s.name;
                        if (String(s.id) === String(preselected)) o.selected = true;
                        sel.appendChild(o);
                    });
                    sel.disabled = data.length === 0;
                })
                .catch(function () {
                    sel.innerHTML = '<option value="">— Select Class First —</option>';
                    sel.disabled = false;
                });
        };
    })();
    </script>

    <?php if (auth_check()): ?>
    <!-- Messaging unread badge polling -->
    <script>
    (function () {
        var POLL_URL = '<?= url('messaging', 'api-unread-count') ?>';
        function updateMsgBadges() {
            fetch(POLL_URL).then(function(r){ return r.ok ? r.json() : null; })
            .then(function(data) {
                if (!data) return;
                var n = parseInt(data.unread_count, 10) || 0;
                var label = n > 99 ? '99+' : (n > 9 ? '9+' : String(n));
                var shortLabel = n > 9 ? '9+' : String(n);
                // Header badge
                var hb = document.getElementById('header-msg-badge');
                if (hb) { hb.textContent = shortLabel; hb.classList.toggle('hidden', n === 0); }
                // Sidebar tree badge
                var tb = document.getElementById('msg-tree-badge');
                if (tb) { tb.textContent = label; tb.classList.toggle('hidden', n === 0); }
                else if (n > 0) {
                    // Create badge if it doesn't exist yet
                    var btn = document.querySelector('#messaging-submenu')?.closest('div')?.querySelector('button');
                    if (btn) {
                        var arrow = btn.querySelector('svg');
                        var badge = document.createElement('span');
                        badge.id = 'msg-tree-badge';
                        badge.className = 'ml-auto bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full';
                        badge.textContent = label;
                        if (arrow) { arrow.classList.remove('ml-auto'); btn.insertBefore(badge, arrow); }
                    }
                }
                // Sidebar inbox badge
                var ib = document.getElementById('msg-inbox-badge');
                if (ib) { ib.textContent = label; ib.classList.toggle('hidden', n === 0); }
                // Mobile badge
                var mb = document.getElementById('mobile-msg-badge');
                if (mb) { mb.textContent = shortLabel; mb.classList.toggle('hidden', n === 0); }
            }).catch(function(){});
        }
        setInterval(updateMsgBadges, 30000);
    })();
    </script>
    <?php endif; ?>

    <!-- PWA registration -->
    <?= pwa_register_script() ?>
</body>
</html>
