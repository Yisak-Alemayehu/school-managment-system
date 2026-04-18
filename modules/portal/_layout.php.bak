<?php
/**
 * Portal — Mobile Layout
 * Provides portal_head() and portal_foot() for all portal views.
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

function portal_head(string $title, string $backUrl = ''): void
{
    $schoolName = function_exists('get_school_name') ? get_school_name() : 'School';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#074DD9">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Portal">
  <link rel="manifest" href="/portal-manifest.webmanifest">
  <link rel="apple-touch-icon" href="/img/Logo.png">
  <title><?= e($title) ?> — <?= e($schoolName) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              50:  '#eef2ff', 100: '#dbe4ff', 200: '#bfcefe',
              500: '#3b5ef4', 600: '#074DD9', 700: '#0640b8', 800: '#0a3596'
            }
          }
        }
      }
    }
  </script>
  <style>
    body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    .card  { background:#fff; border-radius:.75rem; box-shadow:0 1px 3px rgba(0,0,0,.08); border:1px solid #f3f4f6; padding:1rem; }
    .badge { display:inline-flex; align-items:center; padding:.15rem .55rem; border-radius:9999px; font-size:.7rem; font-weight:600; letter-spacing:.02em; }
    .badge-green  { background:#d1fae5; color:#065f46; }
    .badge-yellow { background:#fef3c7; color:#92400e; }
    .badge-red    { background:#fee2e2; color:#991b1b; }
    .badge-blue   { background:#dbeafe; color:#1e40af; }
    .badge-gray   { background:#f3f4f6; color:#374151; }
    .section-title { font-size:.7rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.06em; margin-bottom:.5rem; }
    .btn-primary   { display:inline-flex; align-items:center; justify-content:center; background:#074DD9; color:#fff; font-weight:600; padding:.6rem 1.25rem; border-radius:.625rem; font-size:.9rem; transition:background .15s; border:none; cursor:pointer; }
    .btn-primary:hover { background:#0640b8; }
    .btn-secondary { display:inline-flex; align-items:center; justify-content:center; background:#f3f4f6; color:#374151; font-weight:600; padding:.6rem 1.25rem; border-radius:.625rem; font-size:.9rem; border:none; cursor:pointer; }
    .btn-secondary:hover { background:#e5e7eb; }
    .btn-danger    { display:inline-flex; align-items:center; justify-content:center; background:#dc2626; color:#fff; font-weight:600; padding:.6rem 1.25rem; border-radius:.625rem; font-size:.9rem; transition:background .15s; border:none; cursor:pointer; }
    .btn-danger:hover { background:#b91c1c; }
    .form-input { width:100%; border:1px solid #d1d5db; border-radius:.625rem; padding:.65rem .875rem; font-size:.9rem; outline:none; transition:border-color .15s; }
    .form-input:focus { border-color:#074DD9; box-shadow:0 0 0 3px rgba(7,77,217,.12); }
    .form-label { display:block; font-size:.85rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
    /* Safe area bottom padding for bottom-nav space */
    .content-area { padding-bottom: calc(5rem + env(safe-area-inset-bottom)); }
    /* Progress bar */
    .progress-bar { height:.5rem; border-radius:9999px; overflow:hidden; background:#e5e7eb; }
    .progress-fill { height:100%; border-radius:9999px; transition:width .3s; }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">

<!-- Top header -->
<header class="bg-primary-600 text-white sticky top-0 z-40" style="box-shadow:0 2px 8px rgba(7,77,217,.25)">
  <div class="max-w-md mx-auto flex items-center h-14 px-4 gap-3">
    <?php if ($backUrl): ?>
    <a href="<?= e($backUrl) ?>" class="flex-shrink-0 p-1.5 rounded-lg hover:bg-primary-700 transition-colors">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
      </svg>
    </a>
    <?php endif; ?>
    <h1 class="font-semibold text-base flex-1 truncate"><?= e($title) ?></h1>
    <!-- Install PWA button (shown only when installable) -->
    <button id="pwa-install-btn"
            onclick="portalInstall()"
            title="Install app"
            class="hidden flex-shrink-0 p-1.5 rounded-lg hover:bg-primary-700 transition-colors">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
      </svg>
    </button>
    <!-- School logo placeholder -->
    <span class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-xs font-bold flex-shrink-0">
      <?= e(mb_substr($schoolName, 0, 2)) ?>
    </span>
  </div>
</header>

<!-- Flash messages -->
<?php
$flashTypes = ['success' => ['bg-green-50 border-green-200 text-green-800', '✓'],
               'error'   => ['bg-red-50 border-red-200 text-red-700',   '✕'],
               'warning' => ['bg-yellow-50 border-yellow-200 text-yellow-800', '!']];
foreach ($flashTypes as $type => [$cls, $icon]):
    $msg = get_flash($type);
    if ($msg): ?>
<div class="max-w-md mx-auto px-4 pt-3">
  <div class="flex items-start gap-2 border rounded-xl p-3 text-sm <?= $cls ?>">
    <span class="font-bold flex-shrink-0"><?= $icon ?></span>
    <span><?= e($msg) ?></span>
  </div>
</div>
<?php endif; endforeach; ?>

<main class="max-w-md mx-auto px-4 py-4 content-area">
<?php
}

function portal_foot(string $activeNav = ''): void
{
    $role = portal_role();
    ?>
</main>

<?php if ($role): ?>
<!-- Bottom navigation -->
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-40"
     style="padding-bottom:env(safe-area-inset-bottom)">
  <div class="max-w-md mx-auto flex">
    <?php
    if ($role === 'student') {
        $navItems = [
            ['action' => 'dashboard',  'label' => 'Home',       'emoji' => '🏠'],
            ['action' => 'results',    'label' => 'Results',    'emoji' => '📊'],
            ['action' => 'attendance', 'label' => 'Attendance', 'emoji' => '📅'],
            ['action' => 'messages',   'label' => 'Messages',  'emoji' => '💬'],
            ['action' => 'profile',    'label' => 'Profile',    'emoji' => '👤'],
        ];
    } else {
        $navItems = [
            ['action' => 'dashboard',  'label' => 'Home',     'emoji' => '🏠'],
            ['action' => 'results',    'label' => 'Results',  'emoji' => '📊'],
            ['action' => 'fees',       'label' => 'Fees',     'emoji' => '💰'],
            ['action' => 'messages',   'label' => 'Messages', 'emoji' => '💬'],
            ['action' => 'profile',    'label' => 'Profile',  'emoji' => '👤'],
        ];
    }
    foreach ($navItems as $item):
        $isActive = $activeNav === $item['action'];
        $cls = $isActive ? 'text-primary-600 font-semibold' : 'text-gray-500';
    ?>
    <a href="<?= portal_url($item['action']) ?>"
       class="flex-1 flex flex-col items-center justify-center py-2 gap-0.5 <?= $cls ?> transition-colors hover:text-primary-600">
      <span class="text-xl leading-none"><?= $item['emoji'] ?></span>
      <span class="text-[10px] leading-tight"><?= e($item['label']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</nav>
<?php endif; ?>

<script>
// ── Service Worker registration ───────────────────────────────────────────────
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/portal-sw.js', { scope: '/portal/' })
        .catch(err => console.warn('Portal SW registration failed:', err));
}

// ── Install prompt ────────────────────────────────────────────────────────────
let _deferredPrompt = null;
window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault();
    _deferredPrompt = e;
    const btn = document.getElementById('pwa-install-btn');
    if (btn) btn.classList.remove('hidden');
});
window.addEventListener('appinstalled', () => {
    _deferredPrompt = null;
    const btn = document.getElementById('pwa-install-btn');
    if (btn) btn.classList.add('hidden');
});
function portalInstall() {
    if (!_deferredPrompt) return;
    _deferredPrompt.prompt();
    _deferredPrompt.userChoice.then(() => { _deferredPrompt = null; });
}
</script>

</body>
</html>
<?php
}
