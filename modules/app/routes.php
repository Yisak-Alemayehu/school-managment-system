<?php
/**
 * PWA App Module — SPA Shell
 * Serves the React PWA for Students & Parents at /app/*
 *
 * The React app handles all sub-routing internally via React Router.
 * This module just delivers the compiled index.html.
 */

$spaIndex = PUBLIC_PATH . '/app/index.html';

if (!file_exists($spaIndex)) {
    // Dev/build-not-ready fallback — show setup instructions
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Student & Parent App — Setup Required</title>
<style>
  body{font-family:sans-serif;max-width:600px;margin:4rem auto;padding:1rem;color:#1e293b}
  code{background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:.95em}
  pre{background:#f1f5f9;padding:1rem;border-radius:8px;overflow:auto}
  h1{color:#0f172a}a{color:#3b82f6}
</style>
</head>
<body>
<h1>📱 Student &amp; Parent PWA</h1>
<p>The PWA frontend has not been built yet. Run the following commands:</p>
<pre>cd pwa
npm install
npm run build</pre>
<p>After the build completes, visit <a href="/app">/app</a> again.</p>
<p><a href="/">← Back to main system</a></p>
</body>
</html>
HTML;
    exit;
}

// Serve the compiled SPA — inject runtime config so React knows the API base
$html = file_get_contents($spaIndex);

// Detect the actual protocol from the request instead of relying on APP_URL
// This prevents mixed-content errors when APP_URL is http:// but the site runs over HTTPS.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
           || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
$requestProtocol = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? parse_url(APP_URL, PHP_URL_HOST);
$apiBase = $requestProtocol . '://' . $host;

$appName = defined('APP_NAME') ? APP_NAME : 'School';

$configScript = '<script>window.__APP_CONFIG__=' . json_encode([
    'apiBase' => $apiBase,
    'appName' => $appName,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';</script>';

// Inject right before </head>
$html = str_replace('</head>', $configScript . "\n</head>", $html);

header('Content-Type: text/html; charset=utf-8');
// Prevent browsers from caching the SPA shell (assets are versioned by Vite)
header('Cache-Control: no-cache, no-store, must-revalidate');

echo $html;
exit;
