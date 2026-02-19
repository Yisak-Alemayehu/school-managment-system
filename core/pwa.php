<?php
/**
 * PWA Helpers
 * Urjiberi School Management ERP
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Get PWA meta tags for head section
 */
function pwa_meta_tags(): string {
    $themeColor = '#1e40af'; // Blue-800
    $html = '';
    $html .= '<meta name="theme-color" content="' . $themeColor . '">' . "\n";
    $html .= '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    $html .= '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
    $html .= '<meta name="apple-mobile-web-app-title" content="' . e(get_school_name()) . '">' . "\n";
    $html .= '<link rel="manifest" href="' . url('/manifest.webmanifest') . '">' . "\n";
    $html .= '<link rel="apple-touch-icon" href="' . url('/assets/icons/icon.php?s=192') . '">' . "\n";
    return $html;
}

/**
 * Get service worker registration script
 */
function pwa_register_script(): string {
    return <<<JS
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/service-worker.js')
            .then(function(reg) {
                console.log('SW registered:', reg.scope);
            })
            .catch(function(err) {
                console.log('SW registration failed:', err);
            });
    });
}

// PWA Install Prompt
var deferredInstallPrompt = null;
window.addEventListener('beforeinstallprompt', function(e) {
    e.preventDefault();
    deferredInstallPrompt = e;
    var banner = document.getElementById('pwa-install-banner');
    if (banner) banner.classList.remove('hidden');
});

function pwaInstall() {
    if (!deferredInstallPrompt) return;
    deferredInstallPrompt.prompt();
    deferredInstallPrompt.userChoice.then(function(choice) {
        if (choice.outcome === 'accepted') {
            console.log('PWA install accepted');
        }
        deferredInstallPrompt = null;
        var banner = document.getElementById('pwa-install-banner');
        if (banner) banner.classList.add('hidden');
    });
}

function pwaInstallDismiss() {
    var banner = document.getElementById('pwa-install-banner');
    if (banner) banner.classList.add('hidden');
    // Don't show again this session
    sessionStorage.setItem('pwa-install-dismissed', '1');
}

window.addEventListener('appinstalled', function() {
    console.log('PWA installed');
    deferredInstallPrompt = null;
    var banner = document.getElementById('pwa-install-banner');
    if (banner) banner.classList.add('hidden');
});
</script>
JS;
}

/**
 * Online/offline status indicator script
 */
function pwa_status_indicator(): string {
    // Install banner
    $banner = <<<HTML
<div id="pwa-install-banner" class="hidden fixed bottom-16 left-4 right-4 lg:bottom-4 lg:left-auto lg:right-4 lg:w-96 bg-white border border-gray-200 rounded-xl shadow-lg p-4 z-50">
    <div class="flex items-start gap-3">
        <div class="flex-shrink-0 w-10 h-10 bg-primary-800 rounded-lg flex items-center justify-center">
            <span class="text-white font-bold text-lg">U</span>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-gray-900 text-sm">Install Urjiberi School</p>
            <p class="text-xs text-gray-500">Add to home screen for quick access</p>
        </div>
        <button onclick="pwaInstallDismiss()" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <button onclick="pwaInstall()" class="mt-3 w-full py-2 bg-primary-800 text-white text-sm font-medium rounded-lg hover:bg-primary-900 transition-colors">
        Install App
    </button>
</div>
HTML;

    // Offline indicator
    $offline = <<<HTML
<div id="offline-indicator" class="fixed bottom-0 left-0 right-0 bg-yellow-500 text-yellow-900 text-center py-2 text-sm font-medium z-50 hidden transition-transform">
    <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414"></path>
    </svg>
    You are offline. Some features may be unavailable.
</div>
<script>
(function() {
    var indicator = document.getElementById('offline-indicator');
    function updateStatus() {
        if (navigator.onLine) {
            indicator.classList.add('hidden');
        } else {
            indicator.classList.remove('hidden');
        }
    }
    window.addEventListener('online', updateStatus);
    window.addEventListener('offline', updateStatus);
    updateStatus();
})();
</script>
HTML;

    return $banner . "\n" . $offline;
}
