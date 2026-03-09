<?php
/**
 * PDF Preview Modal — Reusable Alpine.js Component Partial
 *
 * Usage: Include once in layout or specific views.
 *   <?php partial('pdf_preview_modal'); ?>
 *
 * Then trigger from any button:
 *   <button @click="$dispatch('open-pdf', { url: '/hr/print-tax/5', title: 'Tax Form' })">Preview</button>
 */
?>
<div x-data="pdfPreviewModal()" x-show="open" x-cloak
     @open-pdf.window="openModal($event.detail)"
     class="fixed inset-0 z-50 flex items-center justify-center"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/60" @click="close()"></div>

    <!-- Modal -->
    <div class="relative bg-white dark:bg-dark-card rounded-xl shadow-2xl w-full max-w-5xl mx-4 flex flex-col"
         style="max-height: 90vh;"
         @click.stop>
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-dark-border">
            <h3 class="text-base font-semibold text-gray-900 dark:text-dark-text" x-text="title"></h3>
            <div class="flex items-center gap-2">
                <a :href="downloadUrl" class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary-600 text-white text-xs rounded-lg hover:bg-primary-700 font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download
                </a>
                <button @click="printPdf()" class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-700 text-white text-xs rounded-lg hover:bg-gray-800 font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </button>
                <button @click="close()" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-dark-bg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <!-- Body -->
        <div class="flex-1 overflow-hidden">
            <iframe :src="pdfUrl" class="w-full" style="height: 75vh; border: none;" x-ref="pdfFrame"></iframe>
        </div>
    </div>
</div>

<script>
function pdfPreviewModal() {
    return {
        open: false,
        title: '',
        pdfUrl: '',
        downloadUrl: '',

        openModal(detail) {
            this.title = detail.title || 'PDF Preview';
            this.pdfUrl = detail.url;
            // Convert print URL to download URL
            this.downloadUrl = detail.downloadUrl || detail.url.replace('/print-', '/download-');
            this.open = true;
            document.body.style.overflow = 'hidden';
        },

        close() {
            this.open = false;
            this.pdfUrl = '';
            document.body.style.overflow = '';
        },

        printPdf() {
            var frame = this.$refs.pdfFrame;
            if (frame && frame.contentWindow) {
                frame.contentWindow.print();
            } else {
                window.open(this.pdfUrl, '_blank');
            }
        }
    };
}
</script>
