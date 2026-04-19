<?php
/**
 * Portal — Materials PDF Viewer
 * Mobile-optimised PDF reader for students.
 * Uses PDF.js with touch-friendly controls.
 */

$student   = portal_student();
$classId   = $student['class_id'] ?? null;
$materialId = (int) ($_GET['id'] ?? 0);

if (!$classId || !$materialId) {
    set_flash('error', 'Invalid material.');
    redirect(portal_url('materials'));
}

// Fetch material — scoped to student's class
$material = db_fetch_one(
    "SELECT m.*, s.name AS subject_name
     FROM academic_materials m
     JOIN subjects s ON s.id = m.subject_id
     WHERE m.id = ? AND m.class_id = ? AND m.deleted_at IS NULL AND m.status = 'active'",
    [$materialId, $classId]
);

if (!$material) {
    set_flash('error', 'Material not found or not available for your class.');
    redirect(portal_url('materials'));
}

$pdfUrl      = url('materials', 'serve', $materialId);
$downloadUrl = url('materials', 'serve', $materialId) . '?mode=download';
$backUrl     = portal_url('materials-subject', ['subject_id' => $material['subject_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1f2937">
    <title><?= e($material['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', system-ui, sans-serif; margin: 0; background: #374151; overflow: hidden; }
        .toolbar-btn { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 8px; transition: background .15s; cursor: pointer; border: none; background: transparent; color: white; }
        .toolbar-btn:hover { background: rgba(255,255,255,0.1); }
        .toolbar-btn:active { background: rgba(255,255,255,0.15); }
        .toolbar-btn:disabled { opacity: 0.3; cursor: default; }
    </style>
</head>
<body>

<div class="fixed inset-0 flex flex-col bg-gray-700" id="viewerRoot">
    <!-- Top toolbar -->
    <div class="flex items-center gap-2 px-3 py-2 bg-gray-800 text-white flex-shrink-0 safe-area-top"
         style="padding-top: max(0.5rem, env(safe-area-inset-top))">
        <a href="<?= e($backUrl) ?>" class="toolbar-btn">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div class="flex-1 min-w-0 px-1">
            <p class="text-xs font-bold truncate"><?= e($material['title']) ?></p>
            <p class="text-[10px] text-gray-400 truncate"><?= e($material['subject_name']) ?></p>
        </div>
        <a href="<?= e($downloadUrl) ?>" class="toolbar-btn" title="Download">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
        </a>
    </div>

    <!-- Loading -->
    <div id="pdfLoading" class="flex-1 flex items-center justify-center">
        <div class="text-center">
            <div class="w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
            <p class="text-gray-400 text-sm">Loading...</p>
        </div>
    </div>

    <!-- Canvas container -->
    <div id="pdfContainer" class="flex-1 overflow-auto hidden" style="scroll-behavior: smooth; -webkit-overflow-scrolling: touch;">
        <div class="flex justify-center py-2 min-h-full">
            <canvas id="pdfCanvas"></canvas>
        </div>
    </div>

    <!-- Bottom controls -->
    <div class="flex items-center justify-between px-3 py-2 bg-gray-800 text-white flex-shrink-0"
         style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom))">
        <!-- Page nav -->
        <div class="flex items-center gap-1">
            <button class="toolbar-btn" onclick="prevPage()" id="btnPrev">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <span class="text-xs font-medium tabular-nums px-1">
                <span id="curPage">1</span>/<span id="totalPages">-</span>
            </span>
            <button class="toolbar-btn" onclick="nextPage()" id="btnNext">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>

        <!-- Zoom -->
        <div class="flex items-center gap-1">
            <button class="toolbar-btn" onclick="zoomOut()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                </svg>
            </button>
            <span id="zoomLvl" class="text-[10px] font-medium w-10 text-center tabular-nums">100%</span>
            <button class="toolbar-btn" onclick="zoomIn()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
            <button class="toolbar-btn" onclick="fitWidth()" title="Fit width">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(function() {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    var pdfDoc = null, page = 1, scale = 1.0, rendering = false, pending = null;
    var canvas = document.getElementById('pdfCanvas');
    var ctx = canvas.getContext('2d');

    pdfjsLib.getDocument('<?= e($pdfUrl) ?>').promise.then(function(pdf) {
        pdfDoc = pdf;
        document.getElementById('totalPages').textContent = pdf.numPages;
        document.getElementById('pdfLoading').classList.add('hidden');
        document.getElementById('pdfContainer').classList.remove('hidden');
        renderPage(1);
        setTimeout(fitWidth, 300);
    }).catch(function(err) {
        document.getElementById('pdfLoading').innerHTML =
            '<div class="text-center"><p class="text-red-400 text-sm">Failed to load PDF</p></div>';
    });

    function renderPage(n) {
        if (rendering) { pending = n; return; }
        rendering = true;
        page = n;
        document.getElementById('curPage').textContent = n;
        document.getElementById('btnPrev').disabled = (n <= 1);
        document.getElementById('btnNext').disabled = (!pdfDoc || n >= pdfDoc.numPages);

        pdfDoc.getPage(n).then(function(p) {
            var vp = p.getViewport({ scale: scale });
            canvas.width = vp.width;
            canvas.height = vp.height;
            p.render({ canvasContext: ctx, viewport: vp }).promise.then(function() {
                rendering = false;
                if (pending !== null) { var t = pending; pending = null; renderPage(t); }
            });
        });
    }

    window.prevPage = function() { if (page > 1) { renderPage(page - 1); document.getElementById('pdfContainer').scrollTop = 0; } };
    window.nextPage = function() { if (pdfDoc && page < pdfDoc.numPages) { renderPage(page + 1); document.getElementById('pdfContainer').scrollTop = 0; } };
    window.zoomIn = function() { scale = Math.min(scale + 0.25, 4); document.getElementById('zoomLvl').textContent = Math.round(scale*100)+'%'; renderPage(page); };
    window.zoomOut = function() { scale = Math.max(scale - 0.25, 0.5); document.getElementById('zoomLvl').textContent = Math.round(scale*100)+'%'; renderPage(page); };
    window.fitWidth = function() {
        if (!pdfDoc) return;
        pdfDoc.getPage(page).then(function(p) {
            var vp = p.getViewport({ scale: 1 });
            var w = document.getElementById('pdfContainer').clientWidth - 16;
            scale = w / vp.width;
            document.getElementById('zoomLvl').textContent = Math.round(scale*100)+'%';
            renderPage(page);
        });
    };

    // Swipe support for mobile
    var touchStartX = 0;
    document.getElementById('pdfContainer').addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });
    document.getElementById('pdfContainer').addEventListener('touchend', function(e) {
        var diff = e.changedTouches[0].screenX - touchStartX;
        if (Math.abs(diff) > 80) { diff > 0 ? prevPage() : nextPage(); }
    }, { passive: true });

    // Keyboard
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') prevPage();
        else if (e.key === 'ArrowRight') nextPage();
        else if (e.key === '+' || e.key === '=') zoomIn();
        else if (e.key === '-') zoomOut();
    });
})();
</script>
</body>
</html>
