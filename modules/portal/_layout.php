<?php
/**
 * Portal — Enhanced Mobile Layout
 * Provides portal_head() and portal_foot() for all portal views.
 * Modern UI with smooth animations, better typography, and improved UX.
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
  <?= csrf_meta() ?>
  <title><?= e($title) ?> — <?= e($schoolName) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              50:  '#eef2ff', 100: '#dbe4ff', 200: '#bfcefe',
              300: '#9db5fd', 400: '#6b8cf9',
              500: '#3b5ef4', 600: '#074DD9', 700: '#0640b8', 800: '#0a3596',
              900: '#0c2d7a'
            }
          },
          boxShadow: {
            'soft': '0 2px 15px rgba(0,0,0,0.05)',
            'card': '0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04)',
            'card-hover': '0 4px 15px rgba(0,0,0,0.08)',
            'nav': '0 -1px 20px rgba(0,0,0,0.08)',
          }
        }
      }
    }
  </script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    body { font-family: 'Inter', system-ui, -apple-system, sans-serif; -webkit-font-smoothing: antialiased; }

    /* Cards */
    .card {
      background: #fff;
      border-radius: 1rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
      border: 1px solid #f3f4f6;
      padding: 1rem;
      transition: all 0.2s ease;
    }
    .card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.08); }

    /* Badges */
    .badge {
      display: inline-flex; align-items: center;
      padding: .2rem .6rem; border-radius: 9999px;
      font-size: .7rem; font-weight: 600; letter-spacing: .02em;
    }
    .badge-green  { background: #d1fae5; color: #065f46; }
    .badge-yellow { background: #fef3c7; color: #92400e; }
    .badge-red    { background: #fee2e2; color: #991b1b; }
    .badge-blue   { background: #dbeafe; color: #1e40af; }
    .badge-gray   { background: #f3f4f6; color: #374151; }
    .badge-purple { background: #ede9fe; color: #5b21b6; }

    /* Section title */
    .section-title {
      font-size: .7rem; font-weight: 700; color: #6b7280;
      text-transform: uppercase; letter-spacing: .06em; margin-bottom: .5rem;
    }

    /* Buttons */
    .btn-primary {
      display: inline-flex; align-items: center; justify-content: center;
      background: linear-gradient(135deg, #074DD9 0%, #0640b8 100%);
      color: #fff; font-weight: 600; padding: .6rem 1.25rem;
      border-radius: .75rem; font-size: .9rem;
      transition: all .2s; border: none; cursor: pointer;
      box-shadow: 0 2px 8px rgba(7,77,217,0.25);
    }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(7,77,217,0.35); }
    .btn-primary:active { transform: translateY(0); }

    .btn-secondary {
      display: inline-flex; align-items: center; justify-content: center;
      background: #f3f4f6; color: #374151; font-weight: 600;
      padding: .6rem 1.25rem; border-radius: .75rem; font-size: .9rem;
      border: none; cursor: pointer; transition: all .2s;
    }
    .btn-secondary:hover { background: #e5e7eb; }

    .btn-danger {
      display: inline-flex; align-items: center; justify-content: center;
      background: linear-gradient(135deg, #dc2626, #b91c1c);
      color: #fff; font-weight: 600; padding: .6rem 1.25rem;
      border-radius: .75rem; font-size: .9rem;
      transition: all .2s; border: none; cursor: pointer;
    }

    /* Form inputs */
    .form-input {
      width: 100%; border: 1.5px solid #e5e7eb; border-radius: .75rem;
      padding: .7rem .95rem; font-size: .9rem; outline: none;
      transition: all .2s; background: #fafbfc;
    }
    .form-input:focus {
      border-color: #074DD9; background: #fff;
      box-shadow: 0 0 0 3px rgba(7,77,217,.1);
    }
    .form-label { display: block; font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: .4rem; }

    /* Content padding for bottom nav */
    .content-area { padding-bottom: calc(5.5rem + env(safe-area-inset-bottom)); }

    /* Progress bar */
    .progress-bar { height: .45rem; border-radius: 9999px; overflow: hidden; background: #e5e7eb; }
    .progress-fill { height: 100%; border-radius: 9999px; transition: width .5s ease-out; }

    /* Animations */
    @keyframes slideUp { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
    @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
    @keyframes scaleIn { from { opacity:0; transform:scale(0.95); } to { opacity:1; transform:scale(1); } }
    .animate-slide-up { animation: slideUp 0.35s ease-out both; }
    .animate-fade-in { animation: fadeIn 0.3s ease-out both; }
    .animate-scale-in { animation: scaleIn 0.25s ease-out both; }

    /* Scrollbar hide */
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

    /* Skeleton loading */
    .skeleton { background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
      background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: .5rem; }
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

    /* Glass effect */
    .glass { background: rgba(255,255,255,0.85); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }

    /* Line clamp */
    .line-clamp-1 { overflow:hidden; display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; }
    .line-clamp-2 { overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">

<!-- Top header -->
<header class="bg-gradient-to-r from-primary-600 to-primary-700 text-white sticky top-0 z-40"
        style="box-shadow:0 2px 12px rgba(7,77,217,.3)">
  <div class="max-w-md mx-auto flex items-center h-14 px-4 gap-3">
    <?php if ($backUrl): ?>
    <a href="<?= e($backUrl) ?>" class="flex-shrink-0 p-2 rounded-xl hover:bg-white/10 transition-colors active:bg-white/20">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
      </svg>
    </a>
    <?php endif; ?>
    <h1 class="font-bold text-base flex-1 truncate tracking-tight"><?= e($title) ?></h1>
    <!-- Install PWA button -->
    <button id="pwa-install-btn"
            onclick="portalInstall()"
            title="Install app"
            class="hidden flex-shrink-0 p-2 rounded-xl hover:bg-white/10 transition-colors">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
      </svg>
    </button>
    <!-- User avatar -->
    <?php
    $portalPhoto = null;
    $portalInitials = mb_substr($schoolName, 0, 2);
    if (portal_check()) {
        if (portal_role() === 'student') {
            $ps = portal_student();
            $portalPhoto = $ps['photo'] ?? null;
            $portalInitials = mb_substr($ps['full_name'] ?? $schoolName, 0, 1);
        } else {
            $pg = portal_guardian();
            $portalPhoto = $pg['photo'] ?? null;
            $portalInitials = mb_substr($pg['name'] ?? $schoolName, 0, 1);
        }
    }
    ?>
    <?php if ($portalPhoto): ?>
    <img src="<?= upload_url($portalPhoto) ?>" alt="" class="w-9 h-9 rounded-xl object-cover flex-shrink-0 border border-white/20">
    <?php else: ?>
    <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center text-xs font-bold flex-shrink-0 backdrop-blur-sm">
      <?= e($portalInitials) ?>
    </div>
    <?php endif; ?>
  </div>
</header>

<!-- Flash messages -->
<?php
$flashTypes = ['success' => ['bg-green-50 border-green-200 text-green-800', '✓'],
               'error'   => ['bg-red-50 border-red-200 text-red-700',   '✕'],
               'warning' => ['bg-yellow-50 border-yellow-200 text-yellow-800', '⚠']];
foreach ($flashTypes as $type => [$cls, $icon]):
    $msg = get_flash($type);
    if ($msg): ?>
<div class="max-w-md mx-auto px-4 pt-3 animate-slide-up" data-flash="1">
  <div class="flex items-start gap-2.5 border rounded-xl p-3.5 text-sm <?= $cls ?> shadow-sm">
    <span class="font-bold flex-shrink-0 text-lg leading-none"><?= $icon ?></span>
    <span class="flex-1"><?= e($msg) ?></span>
    <button onclick="this.parentElement.parentElement.remove()" class="flex-shrink-0 opacity-50 hover:opacity-100">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>
</div>
<?php endif; endforeach; ?>

<main class="max-w-md mx-auto px-4 py-5 content-area">
<?php
}

function portal_foot(string $activeNav = ''): void
{
    $role = portal_role();
    ?>
</main>

<?php if ($role): ?>
<!-- Bottom navigation -->
<nav class="fixed bottom-0 left-0 right-0 glass border-t border-gray-200/50 z-40"
     style="padding-bottom:env(safe-area-inset-bottom); box-shadow: 0 -1px 20px rgba(0,0,0,0.06)">
  <div class="max-w-md mx-auto flex">
    <?php
    if ($role === 'student') {
        $navItems = [
            ['action' => 'dashboard',  'label' => 'Home',       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
            ['action' => 'results',    'label' => 'Results',    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'],
            ['action' => 'attendance', 'label' => 'Attendance', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>'],
            ['action' => 'messages',   'label' => 'Messages',   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>'],
            ['action' => 'profile',    'label' => 'Profile',    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>'],
        ];
    } else {
        $navItems = [
            ['action' => 'dashboard',  'label' => 'Home',     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
            ['action' => 'results',    'label' => 'Results',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'],
            ['action' => 'fees',       'label' => 'Fees',     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
            ['action' => 'messages',   'label' => 'Messages', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>'],
            ['action' => 'profile',    'label' => 'Profile',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>'],
        ];
    }
    foreach ($navItems as $item):
        $isActive = $activeNav === $item['action'];
    ?>
    <a href="<?= portal_url($item['action']) ?>"
       class="flex-1 flex flex-col items-center justify-center py-2.5 gap-1 transition-all duration-200
              <?= $isActive ? 'text-primary-600' : 'text-gray-400 hover:text-gray-600' ?>">
      <div class="relative">
        <?php if ($isActive): ?>
        <div class="absolute -inset-1.5 bg-primary-100 rounded-xl"></div>
        <?php endif; ?>
        <svg class="w-5 h-5 relative" fill="none" stroke="currentColor" viewBox="0 0 24 24"
             stroke-width="<?= $isActive ? '2.5' : '1.5' ?>">
          <?= $item['icon'] ?>
        </svg>
      </div>
      <span class="text-[10px] leading-tight font-<?= $isActive ? 'bold' : 'medium' ?>"><?= e($item['label']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</nav>
<?php endif; ?>

<script>
// ── Service Worker ───────────────────────────────────────────────────────────
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/portal-sw.js', { scope: '/portal/' })
        .catch(err => console.warn('Portal SW failed:', err));
}

// ── Install prompt ───────────────────────────────────────────────────────────
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

// ── Page transition animation ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('main').classList.add('animate-fade-in');
});

// ── Auto-dismiss flash messages ──────────────────────────────────────────────
document.querySelectorAll('[data-flash]').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'all 0.3s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-10px)';
        setTimeout(() => el.remove(), 300);
    }, 5000);
});
</script>

</body>
</html>
<?php
}
