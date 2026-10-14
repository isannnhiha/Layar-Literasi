<?php
/**
 * Online Book Reader using PDF.js
 * Layar Literasi Digital Library
 */
require_once __DIR__ . '/config/app.php';

$bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($bookId <= 0) {
    redirect('catalog.php');
}

$db = getDB();

// Fetch book
$stmt = $db->prepare("SELECT * FROM books WHERE id = ? LIMIT 1");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    setFlash('danger', 'Buku tidak ditemukan.');
    redirect('catalog.php');
}

$currentUser = currentUser();

// Permission verification
$access = canAccessBook($currentUser, $book);
if (!$access['allowed']) {
    if (!empty($access['need_login'])) {
        setFlash('warning', 'Silakan masuk terlebih dahulu untuk membaca buku digital ini.');
        redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    } else {
        setFlash('warning', $access['reason'] . ' Silakan tingkatkan paket membership Anda.');
        redirect('membership.php');
    }
}

// Locate PDF file
$pdfFileName = $book['pdf_file'] ?? '';
$pdfUrl = '';
if ($pdfFileName && file_exists(UPLOADS_PATH . '/books/' . $pdfFileName)) {
    $pdfUrl = UPLOADS_URL . '/books/' . $pdfFileName;
} else {
    // Fallback sample PDF
    $pdfUrl = UPLOADS_URL . '/books/sample_book_1.pdf';
}

// Retrieve user's initial page from saved progress
$initialPage = 1;
if ($currentUser) {
    $stmtProg = $db->prepare("SELECT current_page FROM reading_progress WHERE user_id = ? AND book_id = ? LIMIT 1");
    $stmtProg->execute([$currentUser['id'], $bookId]);
    $savedPage = (int)$stmtProg->fetchColumn();
    if ($savedPage > 0) {
        $initialPage = $savedPage;
    }
}

$pageTitle = "Membaca: " . $book['title'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(generateCsrfToken()) ?>">
    <meta name="base-url" content="<?= e(BASE_URL) ?>">
    <title><?= e($pageTitle) ?> - <?= APP_NAME ?></title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom Reader CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/reader.css">

    <!-- Mozilla PDF.js CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
</head>
<body>

<div id="pdf-viewer-app" 
     class="reader-container" 
     data-pdf-url="<?= e($pdfUrl) ?>" 
     data-book-id="<?= $book['id'] ?>" 
     data-initial-page="<?= $initialPage ?>">
    
    <!-- Top Navigation & Controls Toolbar -->
    <header class="reader-navbar">
        <!-- Left: Book Info & Back Button -->
        <div class="reader-book-meta">
            <a href="<?= BASE_URL ?>/book-detail.php?id=<?= $book['id'] ?>" class="btn btn-outline-light btn-sm rounded-pill px-3 py-1" title="Kembali ke Detail Buku">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            <div class="overflow-hidden d-none d-md-block">
                <h6 class="reader-book-title" title="<?= e($book['title']) ?>"><?= e($book['title']) ?></h6>
                <p class="reader-book-author"><?= e($book['author']) ?></p>
            </div>
        </div>

        <!-- Center: Page Controls -->
        <div class="reader-controls-center">
            <button type="button" class="reader-btn" id="prev-page-btn" title="Halaman Sebelumnya (Panah Kiri)">
                <i class="bi bi-chevron-left"></i>
            </button>
            <div class="reader-page-indicator">
                <input type="number" id="current-page-input" class="reader-page-input" value="<?= $initialPage ?>" min="1">
                <span>/</span>
                <span id="total-pages-label">-</span>
            </div>
            <button type="button" class="reader-btn" id="next-page-btn" title="Halaman Berikutnya (Panah Kanan)">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>

        <!-- Right: Zoom, Bookmarks, Fullscreen -->
        <div class="d-flex align-items-center gap-2">
            <!-- Save indicator -->
            <div id="save-indicator-badge" class="reader-badge-save d-none d-lg-inline-flex" style="opacity: 0.5;">
                <i class="bi bi-cloud-check"></i> <span id="progress-percent-label">0%</span>
            </div>

            <button type="button" class="reader-btn" id="zoom-out-btn" title="Perkecil (-)">
                <i class="bi bi-zoom-out"></i>
            </button>
            <button type="button" class="reader-btn" id="zoom-in-btn" title="Perbesar (+)">
                <i class="bi bi-zoom-in"></i>
            </button>
            <button type="button" class="reader-btn d-none d-sm-flex" id="fit-width-btn" title="Sesuaikan Lebar Layar">
                <i class="bi bi-arrows-expand"></i>
            </button>

            <!-- Add Bookmark Modal trigger -->
            <button type="button" class="reader-btn text-warning" data-bs-toggle="modal" data-bs-target="#modalAddBookmark" title="Tambah Bookmark di Halaman Ini">
                <i class="bi bi-bookmark-plus-fill"></i>
            </button>

            <!-- Toggle Bookmark Sidebar -->
            <button type="button" class="reader-btn" id="toggle-sidebar-btn" title="Daftar Bookmark">
                <i class="bi bi-layout-sidebar-reverse"></i>
            </button>

            <!-- Fullscreen -->
            <button type="button" class="reader-btn" id="fullscreen-btn" title="Mode Layar Penuh">
                <i class="bi bi-fullscreen"></i>
            </button>
        </div>
    </header>

    <!-- Reading Progress Top Line -->
    <div class="reader-progress-strip">
        <div id="reader-progress-bar" class="reader-progress-bar"></div>
    </div>

    <!-- Main Workspace: Sidebar & Canvas Viewport -->
    <div class="reader-main-area">
        <!-- Sidebar for Bookmarks -->
        <aside class="reader-sidebar" id="reader-sidebar">
            <div class="reader-sidebar-tabs">
                <button type="button" class="reader-sidebar-tab active">
                    <i class="bi bi-bookmarks-fill me-1"></i> Bookmark Saya
                </button>
            </div>
            <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center">
                <span class="small text-muted">Penanda Halaman</span>
                <button type="button" class="btn btn-sm btn-outline-warning rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAddBookmark">
                    <i class="bi bi-plus-lg me-1"></i> Tambah
                </button>
            </div>
            <div class="reader-sidebar-content" id="bookmarks-list">
                <div class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                    <p class="small mt-2">Memuat bookmark...</p>
                </div>
            </div>
        </aside>

        <!-- Viewport -->
        <main class="reader-viewport" id="reader-viewport">
            <div id="loading-spinner" class="py-5 text-center text-white">
                <div class="spinner-border text-orange" style="width: 3rem; height: 3rem;" role="status"></div>
                <h5 class="mt-3 fw-semibold">Memuat Dokumen Buku...</h5>
                <p class="text-white-50 small">Mohon tunggu sebentar selagi sistem mempersiapkan lembaran PDF.</p>
            </div>
            
            <div class="pdf-canvas-container">
                <canvas id="pdf-canvas"></canvas>
            </div>
        </main>
    </div>
</div>

<!-- Modal Tambah Bookmark -->
<div class="modal fade" id="modalAddBookmark" tabindex="-1" aria-labelledby="modalAddBookmarkLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalAddBookmarkLabel">Tambah Bookmark</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-add-bookmark">
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Catatan atau Label Penanda</label>
                        <input type="text" id="bookmark-note-input" class="form-control rounded-3" placeholder="Contoh: Bab 3 - Teori Dasar Penting" maxlength="150" required>
                        <div class="form-text">Bookmark akan ditautkan langsung ke halaman yang sedang Anda buka.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-semibold text-dark">Simpan Bookmark</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle & SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- PDF Viewer Engine Script -->
<script src="<?= ASSETS_URL ?>/js/pdf-viewer.js"></script>

</body>
</html>
