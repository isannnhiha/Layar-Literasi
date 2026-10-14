<?php
/**
 * Member Reading Progress & Bookmarks Overview
 * Layar Literasi Digital Library
 */
$pageTitle = "Progress Membaca & Bookmark";
require_once __DIR__ . '/../config/app.php';

requireMember();
$user = currentUser();
$db = getDB();
$userId = (int)$user['id'];

// Fetch all reading progresses
$stmtProg = $db->prepare("
    SELECT rp.*, bk.title, bk.author, bk.cover_image, bk.pdf_file, c.name AS category_name 
    FROM reading_progress rp
    JOIN books bk ON rp.book_id = bk.id
    JOIN categories c ON bk.category_id = c.id
    WHERE rp.user_id = ?
    ORDER BY rp.last_read_at DESC
");
$stmtProg->execute([$userId]);
$progressList = $stmtProg->fetchAll();

// Fetch all bookmarks
$stmtBm = $db->prepare("
    SELECT bm.*, bk.title, bk.cover_image 
    FROM bookmarks bm
    JOIN books bk ON bm.book_id = bk.id
    WHERE bm.user_id = ?
    ORDER BY bm.created_at DESC
");
$stmtBm->execute([$userId]);
$bookmarkList = $stmtBm->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size: 0.85rem;">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/member/index.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Progress Membaca</li>
                </ol>
            </nav>
            <h2 class="h3 fw-bold text-dark mb-0">Progress Membaca & Bookmark</h2>
        </div>
        <a href="<?= BASE_URL ?>/catalog.php" class="btn btn-orange rounded-pill px-4 mt-3 mt-md-0 fw-semibold shadow-sm">
            <i class="bi bi-book me-1"></i> Mulai Baca Buku Baru
        </a>
    </div>

    <!-- Nav Tabs -->
    <ul class="nav nav-pills mb-4 gap-2" id="progressTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4 fw-semibold" id="progress-tab" data-bs-toggle="pill" data-bs-target="#tab-progress" type="button" role="tab">
                <i class="bi bi-hourglass-split me-1"></i> Sedang Dibaca (<?= count($progressList) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 fw-semibold" id="bookmarks-tab" data-bs-toggle="pill" data-bs-target="#tab-bookmarks" type="button" role="tab">
                <i class="bi bi-bookmarks-fill me-1"></i> Bookmark Tersimpan (<?= count($bookmarkList) ?>)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="progressTabsContent">
        <!-- Tab 1: Reading Progresses -->
        <div class="tab-pane fade show active" id="tab-progress" role="tabpanel">
            <?php if (empty($progressList)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                    <i class="bi bi-book-half display-1 text-muted d-block mb-3"></i>
                    <h5 class="fw-bold text-dark">Belum Ada Riwayat Membaca</h5>
                    <p class="text-muted small max-w-500 mx-auto mb-4">
                        Saat Anda membaca buku online dengan PDF.js reader, progres halaman yang Anda buka akan tersimpan secara otomatis di sini.
                    </p>
                    <div>
                        <a href="<?= BASE_URL ?>/catalog.php" class="btn btn-primary rounded-pill px-4">
                            Pilih Buku untuk Dibaca
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($progressList as $prog): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 d-flex flex-column">
                                <div class="d-flex gap-3 mb-3">
                                    <img src="<?= getBookCoverUrl($prog['cover_image']) ?>" alt="<?= e($prog['title']) ?>" style="width: 60px; height: 85px; object-fit: cover; border-radius: 8px;">
                                    <div class="overflow-hidden">
                                        <span class="badge bg-light text-primary border small mb-1"><?= e($prog['category_name']) ?></span>
                                        <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?= e($prog['title']) ?>">
                                            <?= e($prog['title']) ?>
                                        </h6>
                                        <small class="text-muted d-block mb-1">oleh <?= e($prog['author']) ?></small>
                                        <small class="text-muted" style="font-size: 0.75rem;">
                                            Terakhir: <?= formatDate($prog['last_read_at']) ?>
                                        </small>
                                    </div>
                                </div>

                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-1 small">
                                        <span class="fw-semibold text-dark">Hal. <?= $prog['current_page'] ?> dari <?= $prog['total_pages'] ?></span>
                                        <span class="fw-bold text-orange"><?= (float)$prog['percentage'] ?>%</span>
                                    </div>
                                    <div class="progress mb-3" style="height: 8px;">
                                        <div class="progress-bar bg-orange" style="width: <?= (float)$prog['percentage'] ?>%;"></div>
                                    </div>

                                    <a href="<?= BASE_URL ?>/read.php?id=<?= $prog['book_id'] ?>" class="btn btn-orange w-100 py-2 rounded-pill fw-semibold shadow-sm">
                                        Lanjut Baca (Hal. <?= $prog['current_page'] ?>) <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 2: Bookmarks -->
        <div class="tab-pane fade" id="tab-bookmarks" role="tabpanel">
            <?php if (empty($bookmarkList)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                    <i class="bi bi-bookmarks display-1 text-muted d-block mb-3"></i>
                    <h5 class="fw-bold text-dark">Belum Ada Bookmark</h5>
                    <p class="text-muted small max-w-500 mx-auto mb-0">
                        Saat membaca buku dengan PDF reader, gunakan ikon bookmark di toolbar atas untuk menandai kutipan atau materi penting.
                    </p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($bookmarkList as $bm): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill">
                                        <i class="bi bi-bookmark-fill me-1"></i> Halaman <?= $bm['page_number'] ?>
                                    </span>
                                    <small class="text-muted" style="font-size: 0.75rem;"><?= formatDate($bm['created_at']) ?></small>
                                </div>
                                <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?= e($bm['title']) ?>"><?= e($bm['title']) ?></h6>
                                <p class="text-secondary small mb-3 p-2 bg-light rounded-3">
                                    "<?= e($bm['note']) ?>"
                                </p>
                                <div class="mt-auto d-flex gap-2">
                                    <a href="<?= BASE_URL ?>/read.php?id=<?= $bm['book_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill w-100">
                                        Buka di Halaman <?= $bm['page_number'] ?> &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
