<?php
/**
 * Academic Materials — Built-in PDF Viewer
 * Uses PDF.js for in-browser PDF reading with page navigation, zoom, and fullscreen.
 */

$material = db_fetch_one(
    "SELECT m.*, c.name AS class_name, s.name AS subject_name
     FROM academic_materials m
     JOIN classes c ON c.id = m.class_id
     JOIN subjects s ON s.id = m.subject_id
     WHERE m.id = ? AND m.deleted_at IS NULL AND m.status = 'active'",
    [$id]
);
if (!$material) {
    set_flash('error', 'Material not found.');
    redirect(url('materials'));
}

$pdfUrl = url('materials', 'serve', $id);
$downloadUrl = url('materials', 'serve', $id) . '?mode=download';
$backUrl = url('materials', 'view', $id);

ob_start();
?>

<div class="fixed inset-0 z-50 bg-gray-900 flex flex-col" id="pdfViewerContainer">
    <!-- Toolbar -->
    <div class="flex items-center justify-between px-4 py-2 bg-gray-800 text-white border-b border-gray-700 flex-shrink-0">
        <div class="flex items-center gap-3">
            <a href="<?= e($backUrl) ?>" class="p-1.5 rounded-lg hover:bg-gray-700 transition-colors" title="Back">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="min-w-0">
                <h1 class="text-sm font-bold truncate max-w-xs"><?= e($material['title']) ?></h1>
                <p class="text-xs text-gray-400"><?= e($material['class_name']) ?> · <?= e($material['subject_name']) ?></p>
            </div>
        </div>

        <!-- Page Navigation -->
        <div class="flex items-center gap-2">
            <button onclick="prevPage()" class="p-1.5 rounded-lg hover:bg-gray-700 transition-colors" title="Previous page" id="btnPrev">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <span class="text-sm font-medium tabular-nums">
                <input type="number" id="pageInput" min="1" value="1"
                       class="w-10 text-center bg-gray-700 border border-gray-600 rounded px-1 py-0.5 text-sm"
                       onchange="goToPage(this.value)">
                <span class="text-gray-400">/ <span id="pageCount">-</span></span>
            </span>
            <button onclick="nextPage()" class="p-1.5 rounded-lg hover:bg-gray-700 transition-colors" title="Next page" id="btnNext">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>

        <!-- Zoom & Actions -->
        <div class="flex items-center gap-1">
            <button onclick="zoomOut()" class="p-1.5 rounded-lg hover:bg-gray-700 transition-colors" title="Zoom out">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/>
                </svg>
            </button>
            <span id="zoomLevel" class="text-xs font-medium w-12 text-center tabular-nums">100%</span>
            <button onclick="zoomIn()" class="p-1.5 rounded-lg hover:bg-gray-700 transition-colors" title="Zoom in">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/>
                </svg>
            </button>
            <button onclick="fitWidth()" class="p-1.5 rounded-lg hover:bg-gray-700 transition-colors ml-1" title="Fit width">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
            </button>
            <button onclick="toggleFullscreen()" class="p-1.5 rounded-lg hover:bg-gray-700 transition-colors" title="Fullscreen" id="btnFullscreen">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
            </button>
            <a href="<?= e($downloadUrl) ?>" class="p-1.5 rounded-lg hover:bg-gray-700 transition-colors ml-2" title="Download PDF">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Loading indicator -->
    <div id="pdfLoading" class="flex-1 flex items-center justify-center">
        <div class="text-center">
            <div class="w-10 h-10 border-4 border-primary-600 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
            <p class="text-gray-400 text-sm">Loading PDF...</p>
        </div>
    </div>

    <!-- PDF Canvas Container -->
    <div id="pdfCanvasContainer" class="flex-1 overflow-auto bg-gray-700 hidden" style="scroll-behavior: smooth;">
        <div class="flex justify-center py-4 min-h-full">
            <canvas id="pdfCanvas" class="shadow-2xl"></canvas>
        </div>
    </div>
</div>

<!-- PDF.js from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(function() {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    var pdfDoc = null;
    var currentPage = 1;
    var scale = 1.0;
    var canvas = document.getElementById('pdfCanvas');
    var ctx = canvas.getContext('2d');
    var rendering = false;
    var pendingPage = null;

    // Load PDF
    pdfjsLib.getDocument('<?= e($pdfUrl) ?>').promise.then(function(pdf) {
        pdfDoc = pdf;
        document.getElementById('pageCount').textContent = pdf.numPages;
        document.getElementById('pageInput').max = pdf.numPages;
        document.getElementById('pdfLoading').classList.add('hidden');
        document.getElementById('pdfCanvasContainer').classList.remove('hidden');
        renderPage(1);
    }).catch(function(err) {
        document.getElementById('pdfLoading').innerHTML =
            '<div class="text-center"><p class="text-red-400 text-sm">Failed to load PDF.</p>' +
            '<p class="text-gray-500 text-xs mt-1">' + err.message + '</p></div>';
    });

    function renderPage(num) {
        if (rendering) {
            pendingPage = num;
            return;
        }
        rendering = true;
        currentPage = num;
        document.getElementById('pageInput').value = num;

        pdfDoc.getPage(num).then(function(page) {
            var viewport = page.getViewport({ scale: scale });
            canvas.height = viewport.height;
            canvas.width  = viewport.width;

            var renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };

            page.render(renderContext).promise.then(function() {
                rendering = false;
                if (pendingPage !== null) {
                    var p = pendingPage;
                    pendingPage = null;
                    renderPage(p);
                }
            });
        });

        // Update button states
        document.getElementById('btnPrev').disabled = (num <= 1);
        document.getElementById('btnNext').disabled = (num >= pdfDoc.numPages);
    }

    window.prevPage = function() {
        if (currentPage <= 1) return;
        renderPage(currentPage - 1);
        document.getElementById('pdfCanvasContainer').scrollTop = 0;
    };

    window.nextPage = function() {
        if (!pdfDoc || currentPage >= pdfDoc.numPages) return;
        renderPage(currentPage + 1);
        document.getElementById('pdfCanvasContainer').scrollTop = 0;
    };

    window.goToPage = function(num) {
        num = parseInt(num, 10);
        if (!pdfDoc || isNaN(num) || num < 1 || num > pdfDoc.numPages) return;
        renderPage(num);
        document.getElementById('pdfCanvasContainer').scrollTop = 0;
    };

    window.zoomIn = function() {
        scale = Math.min(scale + 0.25, 4.0);
        document.getElementById('zoomLevel').textContent = Math.round(scale * 100) + '%';
        renderPage(currentPage);
    };

    window.zoomOut = function() {
        scale = Math.max(scale - 0.25, 0.25);
        document.getElementById('zoomLevel').textContent = Math.round(scale * 100) + '%';
        renderPage(currentPage);
    };

    window.fitWidth = function() {
        if (!pdfDoc) return;
        pdfDoc.getPage(currentPage).then(function(page) {
            var viewport = page.getViewport({ scale: 1.0 });
            var container = document.getElementById('pdfCanvasContainer');
            var containerWidth = container.clientWidth - 32; // padding
            scale = containerWidth / viewport.width;
            document.getElementById('zoomLevel').textContent = Math.round(scale * 100) + '%';
            renderPage(currentPage);
        });
    };

    window.toggleFullscreen = function() {
        var el = document.getElementById('pdfViewerContainer');
        if (!document.fullscreenElement) {
            el.requestFullscreen().catch(function() {});
        } else {
            document.exitFullscreen();
        }
    };

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT') return;
        switch (e.key) {
            case 'ArrowLeft':  prevPage(); e.preventDefault(); break;
            case 'ArrowRight': nextPage(); e.preventDefault(); break;
            case '+': case '=': zoomIn();  e.preventDefault(); break;
            case '-':           zoomOut(); e.preventDefault(); break;
            case 'f':           toggleFullscreen(); e.preventDefault(); break;
        }
    });

    // Auto fit width on first load
    setTimeout(function() { fitWidth(); }, 500);
})();
</script>

<?php
// The PDF viewer uses its own full-screen layout — no need for the standard layout.php
$content = ob_get_clean();
// Output directly instead of including layout.php
echo $content;
?>
