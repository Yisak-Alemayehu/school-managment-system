<?php
/**
 * Portal — Offline Fallback Page
 * Served by the portal service worker when the network is unavailable.
 */
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
  <title>Offline — <?= htmlspecialchars($schoolName) ?></title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #f9fafb;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem 1.5rem;
      text-align: center;
    }
    .icon {
      font-size: 4rem;
      margin-bottom: 1.5rem;
      opacity: .75;
    }
    h1 { font-size: 1.4rem; font-weight: 700; color: #111827; margin-bottom: .5rem; }
    p  { font-size: .95rem; color: #6b7280; line-height: 1.6; max-width: 300px; margin: 0 auto 1.75rem; }
    .btn {
      display: inline-block;
      background: #074DD9;
      color: #fff;
      font-weight: 600;
      font-size: .9rem;
      padding: .7rem 1.75rem;
      border-radius: .625rem;
      border: none;
      cursor: pointer;
      text-decoration: none;
    }
    .btn:active { background: #0640b8; }
  </style>
</head>
<body>
  <div class="icon">📡</div>
  <h1>You're offline</h1>
  <p>No internet connection detected. Please check your connection and try again.</p>
  <button class="btn" onclick="window.location.reload()">Try again</button>
</body>
</html>
