/**
 * PDF.js Viewer Engine & Reading Tracker
 * Layar Literasi Digital Library
 */

let pdfDoc = null;
let pageNum = 1;
let pageRendering = false;
let pageNumPending = null;
let scale = 1.2;
const canvas = document.getElementById('pdf-canvas');
const ctx = canvas.getContext('2d');

const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// Load initial parameters from data attributes
const viewerEl = document.getElementById('pdf-viewer-app');
const pdfUrl = viewerEl.getAttribute('data-pdf-url');
const bookId = parseInt(viewerEl.getAttribute('data-book-id'), 10);
const startPage = parseInt(viewerEl.getAttribute('data-initial-page'), 10) || 1;

// Configure PDF.js Worker
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

/**
 * Render specific page
 */
function renderPage(num) {
    pageRendering = true;
    pdfDoc.getPage(num).then((page) => {
        const viewport = page.getViewport({ scale: scale });
        
        // Output device pixel ratio support for sharp rendering
        const outputScale = window.devicePixelRatio || 1;
        canvas.width = Math.floor(viewport.width * outputScale);
        canvas.height = Math.floor(viewport.height * outputScale);
        canvas.style.width = Math.floor(viewport.width) + "px";
        canvas.style.height = Math.floor(viewport.height) + "px";

        const transform = outputScale !== 1
            ? [outputScale, 0, 0, outputScale, 0, 0]
            : null;

        const renderContext = {
            canvasContext: ctx,
            transform: transform,
            viewport: viewport
        };
        
        const renderTask = page.render(renderContext);

        renderTask.promise.then(() => {
            pageRendering = false;
            if (pageNumPending !== null) {
                renderPage(pageNumPending);
                pageNumPending = null;
            }
        });
    });

    // Update UI elements
    document.getElementById('current-page-input').value = num;
    updateProgressIndicator(num, pdfDoc ? pdfDoc.numPages : 1);
    
    // Save progress to database
    saveReadingProgress(num, pdfDoc ? pdfDoc.numPages : 1);
}

/**
 * Queue rendering if busy
 */
function queueRenderPage(num) {
    if (pageRendering) {
        pageNumPending = num;
    } else {
        renderPage(num);
    }
}

/**
 * Navigation handlers
 */
function onPrevPage() {
    if (pageNum <= 1) return;
    pageNum--;
    queueRenderPage(pageNum);
}

function onNextPage() {
    if (pageNum >= pdfDoc.numPages) return;
    pageNum++;
    queueRenderPage(pageNum);
}

/**
 * Zoom handlers
 */
function onZoomIn() {
    if (scale >= 3.0) return;
    scale += 0.2;
    queueRenderPage(pageNum);
}

function onZoomOut() {
    if (scale <= 0.6) return;
    scale -= 0.2;
    queueRenderPage(pageNum);
}

function onFitWidth() {
    const container = document.querySelector('.reader-viewport');
    if (!container || !pdfDoc) return;
    pdfDoc.getPage(pageNum).then((page) => {
        const unscaledViewport = page.getViewport({ scale: 1.0 });
        scale = (container.clientWidth - 60) / unscaledViewport.width;
        queueRenderPage(pageNum);
    });
}

/**
 * Update Progress Bar and save indicator
 */
function updateProgressIndicator(current, total) {
    const percent = Math.min(100, Math.round((current / total) * 100));
    const progressBar = document.getElementById('reader-progress-bar');
    if (progressBar) {
        progressBar.style.width = percent + '%';
    }
    const percentLabel = document.getElementById('progress-percent-label');
    if (percentLabel) {
        percentLabel.textContent = percent + '%';
    }
}

/**
 * Save Reading Progress via AJAX
 */
let saveTimeout = null;
function saveReadingProgress(current, total) {
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(async () => {
        try {
            const res = await fetch(`${baseUrl}/api/save-progress.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    book_id: bookId,
                    current_page: current,
                    total_pages: total
                })
            });
            const data = await res.json();
            if (data.success) {
                const saveBadge = document.getElementById('save-indicator-badge');
                if (saveBadge) {
                    saveBadge.style.opacity = '1';
                    setTimeout(() => {
                        saveBadge.style.opacity = '0.5';
                    }, 2000);
                }
            }
        } catch (e) {
            console.error('Save progress error:', e);
        }
    }, 800);
}

/**
 * Load Bookmarks
 */
async function loadBookmarks() {
    const listEl = document.getElementById('bookmarks-list');
    if (!listEl) return;

    try {
        const res = await fetch(`${baseUrl}/api/get-bookmarks.php?book_id=${bookId}`);
        const data = await res.json();

        if (data.success && data.bookmarks.length > 0) {
            listEl.innerHTML = '';
            data.bookmarks.forEach(bm => {
                const item = document.createElement('div');
                item.className = 'bookmark-item';
                item.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="bookmark-page"><i class="bi bi-bookmark-fill me-1"></i>Halaman ${bm.page_number}</div>
                        <button class="btn btn-sm btn-link text-danger p-0 delete-bm-btn" data-id="${bm.id}" title="Hapus Bookmark">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <p class="bookmark-note">${escapeHtml(bm.note || 'Bookmark')}</p>
                `;
                item.addEventListener('click', (e) => {
                    if (e.target.closest('.delete-bm-btn')) return;
                    pageNum = parseInt(bm.page_number, 10);
                    queueRenderPage(pageNum);
                });
                listEl.appendChild(item);
            });

            // Attach delete handlers
            listEl.querySelectorAll('.delete-bm-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    const bmId = btn.getAttribute('data-id');
                    try {
                        const delRes = await fetch(`${baseUrl}/api/delete-bookmark.php`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ bookmark_id: bmId })
                        });
                        const delData = await delRes.json();
                        if (delData.success) {
                            loadBookmarks();
                        }
                    } catch (err) {
                        console.error(err);
                    }
                });
            });
        } else {
            listEl.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-bookmarks display-6 d-block mb-2"></i>Belum ada bookmark pada buku ini.</div>';
        }
    } catch (e) {
        console.error('Bookmarks load error:', e);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Initialize PDF Reader
 */
document.addEventListener('DOMContentLoaded', () => {
    // Bind buttons
    document.getElementById('prev-page-btn')?.addEventListener('click', onPrevPage);
    document.getElementById('next-page-btn')?.addEventListener('click', onNextPage);
    document.getElementById('zoom-in-btn')?.addEventListener('click', onZoomIn);
    document.getElementById('zoom-out-btn')?.addEventListener('click', onZoomOut);
    document.getElementById('fit-width-btn')?.addEventListener('click', onFitWidth);

    // Sidebar toggle
    document.getElementById('toggle-sidebar-btn')?.addEventListener('click', () => {
        document.getElementById('reader-sidebar')?.classList.toggle('collapsed');
    });

    // Fullscreen toggle
    document.getElementById('fullscreen-btn')?.addEventListener('click', () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(err => {
                console.warn(err);
            });
        } else {
            document.exitFullscreen();
        }
    });

    // Jump to page on Enter
    const pageInput = document.getElementById('current-page-input');
    pageInput?.addEventListener('change', (e) => {
        let val = parseInt(e.target.value, 10);
        if (isNaN(val) || val < 1) val = 1;
        if (pdfDoc && val > pdfDoc.numPages) val = pdfDoc.numPages;
        pageNum = val;
        queueRenderPage(pageNum);
    });

    // Keyboard shortcuts (ArrowLeft / ArrowRight)
    window.addEventListener('keydown', (e) => {
        if (['input', 'textarea'].includes(document.activeElement.tagName.toLowerCase())) return;
        if (e.key === 'ArrowRight' || e.key === 'PageDown') onNextPage();
        if (e.key === 'ArrowLeft' || e.key === 'PageUp') onPrevPage();
    });

    // Add Bookmark Modal Form Submit
    document.getElementById('form-add-bookmark')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const noteInput = document.getElementById('bookmark-note-input');
        const note = noteInput.value.trim() || `Penanda Halaman ${pageNum}`;

        try {
            const res = await fetch(`${baseUrl}/api/add-bookmark.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    book_id: bookId,
                    page_number: pageNum,
                    note: note
                })
            });
            const data = await res.json();
            if (data.success) {
                noteInput.value = '';
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalAddBookmark'));
                if (modal) modal.hide();
                loadBookmarks();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Bookmark halaman ${pageNum} tersimpan!`,
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        } catch (err) {
            console.error(err);
        }
    });

    // Load PDF Document
    const loadingTask = pdfjsLib.getDocument(pdfUrl);
    loadingTask.promise.then((pdf) => {
        pdfDoc = pdf;
        document.getElementById('total-pages-label').textContent = pdf.numPages;
        
        // Initial page determination
        pageNum = (startPage > 0 && startPage <= pdf.numPages) ? startPage : 1;
        renderPage(pageNum);
        loadBookmarks();
    }).catch((reason) => {
        console.error('Error loading PDF: ' + reason);
        document.getElementById('loading-spinner').innerHTML = `
            <div class="alert alert-danger text-center">
                <h5>Gagal Memuat Dokumen PDF</h5>
                <p>${escapeHtml(reason.message || 'File PDF tidak dapat dibaca atau rusak.')}</p>
                <a href="${baseUrl}/catalog.php" class="btn btn-secondary mt-2">Kembali ke Katalog</a>
            </div>
        `;
    });
});
