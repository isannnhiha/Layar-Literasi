<?php
/**
 * Book Detail Page
 * Layar Literasi Digital Library
 */
require_once __DIR__ . '/config/app.php';

$bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($bookId <= 0) {
    redirect('catalog.php');
}

$db = getDB();

// Increment views count
$db->prepare("UPDATE books SET views_count = views_count + 1 WHERE id = ?")->execute([$bookId]);

// Fetch book details
$stmt = $db->prepare("
    SELECT b.*, c.name AS category_name, c.slug AS category_slug 
    FROM books b 
    JOIN categories c ON b.category_id = c.id 
    WHERE b.id = ? 
    LIMIT 1
");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    setFlash('danger', 'Buku tidak ditemukan.');
    redirect('catalog.php');
}

$pageTitle = $book['title'];
$currentUser = currentUser();

// Access check
$access = canAccessBook($currentUser, $book);

// Check if favorited
$isFav = isLoggedIn() ? isBookFavorited($currentUser['id'], $book['id']) : false;

// Check user's current reading progress
$readingProgress = isLoggedIn() ? getUserReadingProgress($currentUser['id'], $book['id']) : null;

// Check if user is currently borrowing this book
$currentBorrowing = null;
if (isLoggedIn()) {
    $stmtBorrow = $db->prepare("
        SELECT * FROM borrowings 
        WHERE user_id = ? AND book_id = ? AND status IN ('borrowed', 'overdue') 
        LIMIT 1
    ");
    $stmtBorrow->execute([$currentUser['id'], $book['id']]);
    $currentBorrowing = $stmtBorrow->fetch();
}

// User active borrowing count vs membership max limit
$userBorrowCount = isLoggedIn() ? getUserActiveBorrowingsCount($currentUser['id']) : 0;
$userMaxBorrow = $currentUser['max_borrow_count'] ?? 2;
$canBorrowMore = ($userBorrowCount < $userMaxBorrow);

// Related books in the same category
$stmtRelated = $db->prepare("
    SELECT b.*, c.name AS category_name, c.slug AS category_slug 
    FROM books b 
    JOIN categories c ON b.category_id = c.id 
    WHERE b.category_id = ? AND b.id != ? 
    ORDER BY b.views_count DESC 
    LIMIT 4
");
$stmtRelated->execute([$book['category_id'], $book['id']]);
$relatedBooks = $stmtRelated->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<div class="py-3 bg-white border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0" style="font-size: 0.85rem;">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">Beranda</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/catalog.php" class="text-decoration-none">Katalog Buku</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/catalog.php?category=<?= urlencode($book['category_slug']) ?>" class="text-decoration-none"><?= e($book['category_name']) ?></a></li>
                <li class="breadcrumb-item active text-truncate" aria-current="page" style="max-width: 250px;"><?= e($book['title']) ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <!-- Left: Book Cover and Actions Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center sticky-top" style="top: 90px; z-index: 10;">
                <div class="position-relative mx-auto mb-4" style="max-width: 280px;">
                    <img src="<?= getBookCoverUrl($book['cover_image']) ?>" alt="<?= e($book['title']) ?>" class="img-fluid rounded-4 shadow" style="width: 100%; aspect-ratio: 3/4; object-fit: cover;">
                    <div class="position-absolute top-0 end-0 m-3">
                        <?= getTierBadge($book['membership_tier']) ?>
                    </div>
                </div>

                <!-- Favorite & Share Buttons -->
                <div class="d-flex justify-content-center gap-2 mb-4">
                    <button type="button" class="btn btn-outline-danger rounded-pill px-3 py-2 btn-fav-toggle <?= $isFav ? 'active' : '' ?>" data-book-id="<?= $book['id'] ?>">
                        <i class="bi <?= $isFav ? 'bi-heart-fill' : 'bi-heart' ?> me-1"></i>
                        <?= $isFav ? 'Tersimpan di Favorit' : 'Tambah ke Favorit' ?>
                    </button>
                </div>

                <!-- Primary Action Buttons -->
                <div class="d-grid gap-2">
                    <?php if ($currentBorrowing): ?>
                        <div class="alert alert-info text-start small mb-2 border-0 rounded-3">
                            <i class="bi bi-info-circle-fill me-1"></i> Anda sedang meminjam buku ini sampai <strong><?= formatDate($currentBorrowing['due_date']) ?></strong>.
                        </div>
                        <?php if (!empty($book['pdf_file'])): ?>
                            <a href="<?= BASE_URL ?>/read.php?id=<?= $book['id'] ?>" class="btn btn-orange py-2.5 rounded-pill fw-semibold shadow">
                                <i class="bi bi-book-half me-1"></i> Lanjut Baca Online (PDF)
                            </a>
                        <?php endif; ?>
                        <form action="<?= BASE_URL ?>/return.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengembalikan buku ini?');">
                            <?= getCsrfField() ?>
                            <input type="hidden" name="borrowing_id" value="<?= $currentBorrowing['id'] ?>">
                            <button type="submit" class="btn btn-outline-success w-100 py-2 rounded-pill fw-semibold">
                                <i class="bi bi-arrow-return-left me-1"></i> Kembalikan Buku
                            </button>
                        </form>

                    <?php elseif (!$access['allowed']): ?>
                        <?php if (!empty($access['need_login'])): ?>
                            <a href="<?= BASE_URL ?>/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary py-2.5 rounded-pill fw-semibold">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk untuk Membaca
                            </a>
                        <?php elseif (!empty($access['need_upgrade'])): ?>
                            <div class="alert alert-warning text-start small mb-2 border-0 rounded-3">
                                <i class="bi bi-gem me-1"></i> <?= e($access['reason']) ?>
                            </div>
                            <a href="<?= BASE_URL ?>/membership.php" class="btn btn-warning py-2.5 rounded-pill fw-bold text-dark shadow-sm">
                                <i class="bi bi-stars me-1"></i> Upgrade ke <?= e($access['required_tier']) ?>
                            </a>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- User has access -->
                        <?php if (!empty($book['pdf_file'])): ?>
                            <a href="<?= BASE_URL ?>/read.php?id=<?= $book['id'] ?>" class="btn btn-orange py-2.5 rounded-pill fw-semibold shadow">
                                <i class="bi bi-book-half me-1"></i> Baca Online (PDF.js)
                            </a>
                        <?php endif; ?>

                        <?php if (isLoggedIn()): ?>
                            <?php if ($book['stock'] > 0): ?>
                                <?php if ($canBorrowMore): ?>
                                    <button type="button" class="btn btn-primary py-2.5 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalBorrow">
                                        <i class="bi bi-journal-plus me-1"></i> Pinjam Buku Ini
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-secondary py-2.5 rounded-pill" disabled title="Batas kuota peminjaman tercapai">
                                        Kuota Peminjaman Penuh (<?= $userBorrowCount ?>/<?= $userMaxBorrow ?>)
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary py-2.5 rounded-pill" disabled>
                                    Stok Habis Dipinjam
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-outline-primary py-2 rounded-pill">
                                Masuk untuk Meminjam
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Reading Progress if exists -->
                <?php if ($readingProgress): ?>
                    <div class="mt-4 pt-3 border-top text-start">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-semibold text-dark">Progres Membaca Anda</span>
                            <span class="badge bg-light text-primary border"><?= (float)$readingProgress['percentage'] ?>%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= (float)$readingProgress['percentage'] ?>%;"></div>
                        </div>
                        <small class="text-muted d-block mt-1">Halaman <?= $readingProgress['current_page'] ?> dari <?= $readingProgress['total_pages'] ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Book Synopsis and Specifications -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
                <a href="<?= BASE_URL ?>/catalog.php?category=<?= urlencode($book['category_slug']) ?>" class="badge bg-light text-primary border px-3 py-1.5 rounded-pill fw-semibold text-decoration-none mb-2 d-inline-block">
                    <?= e($book['category_name']) ?>
                </a>
                
                <h1 class="h2 fw-bold text-dark mb-2"><?= e($book['title']) ?></h1>
                
                <p class="fs-5 text-muted mb-4">
                    Penulis: <strong class="text-dark"><?= e($book['author']) ?></strong>
                </p>

                <!-- Quick Stats Badges -->
                <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded-4 mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-star-fill text-warning fs-5"></i>
                        <div>
                            <span class="d-block fw-bold lh-1"><?= number_format($book['rating'], 1) ?></span>
                            <small class="text-muted" style="font-size: 0.75rem;">Rating Pembaca</small>
                        </div>
                    </div>
                    <div class="border-end mx-2 d-none d-sm-block"></div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-eye text-primary fs-5"></i>
                        <div>
                            <span class="d-block fw-bold lh-1"><?= number_format($book['views_count']) ?></span>
                            <small class="text-muted" style="font-size: 0.75rem;">Kali Dilihat</small>
                        </div>
                    </div>
                    <div class="border-end mx-2 d-none d-sm-block"></div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam text-success fs-5"></i>
                        <div>
                            <span class="d-block fw-bold lh-1"><?= $book['stock'] ?> Buku</span>
                            <small class="text-muted" style="font-size: 0.75rem;">Stok Fisik/Sistem</small>
                        </div>
                    </div>
                    <div class="border-end mx-2 d-none d-sm-block"></div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-text text-secondary fs-5"></i>
                        <div>
                            <span class="d-block fw-bold lh-1"><?= $book['pages'] ?> Hal</span>
                            <small class="text-muted" style="font-size: 0.75rem;">Jumlah Halaman</small>
                        </div>
                    </div>
                </div>

                <!-- Synopsis -->
                <h4 class="fw-bold text-dark mb-3">Sinopsis & Ringkasan Buku</h4>
                <div class="text-secondary lh-lg mb-5" style="white-space: pre-line;">
                    <?= e($book['description'] ?: 'Belum ada deskripsi lengkap untuk buku ini.') ?>
                </div>

                <!-- Book Information Table -->
                <h5 class="fw-bold text-dark mb-3">Informasi Detail Buku</h5>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th class="bg-light text-muted w-25">Judul Buku</th>
                                <td><?= e($book['title']) ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Penulis</th>
                                <td><?= e($book['author']) ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Penerbit</th>
                                <td><?= e($book['publisher']) ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Tahun Terbit</th>
                                <td><?= e($book['publication_year']) ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Nomor ISBN</th>
                                <td><code><?= e($book['isbn'] ?: 'N/A') ?></code></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Kategori</th>
                                <td><?= e($book['category_name']) ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Format Tersedia</th>
                                <td>
                                    <span class="badge bg-danger me-1"><i class="bi bi-filetype-pdf me-1"></i>PDF Online</span>
                                    <span class="badge bg-secondary"><i class="bi bi-book me-1"></i>Peminjaman Digital</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Related Books Carousel / Grid -->
            <?php if (!empty($relatedBooks)): ?>
                <div class="mt-5">
                    <h4 class="fw-bold text-dark mb-4">Buku Terkait dalam Kategori Ini</h4>
                    <div class="row g-4">
                        <?php foreach ($relatedBooks as $rb): ?>
                            <div class="col-md-6 col-xl-3">
                                <div class="book-card">
                                    <div class="book-cover-wrap">
                                        <img src="<?= getBookCoverUrl($rb['cover_image']) ?>" alt="<?= e($rb['title']) ?>" class="book-cover-img" loading="lazy">
                                    </div>
                                    <div class="book-card-body">
                                        <a href="<?= BASE_URL ?>/book-detail.php?id=<?= $rb['id'] ?>" class="book-title" title="<?= e($rb['title']) ?>">
                                            <?= e($rb['title']) ?>
                                        </a>
                                        <p class="book-author"><?= e($rb['author']) ?></p>
                                        <div class="book-actions-row">
                                            <a href="<?= BASE_URL ?>/book-detail.php?id=<?= $rb['id'] ?>" class="btn btn-sm btn-outline-secondary w-100">
                                                Detail
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Peminjaman Buku -->
<?php if (isLoggedIn()): ?>
<div class="modal fade" id="modalBorrow" tabindex="-1" aria-labelledby="modalBorrowLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalBorrowLabel">Konfirmasi Peminjaman Buku</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/borrow.php" method="POST">
                <?= getCsrfField() ?>
                <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                
                <div class="modal-body py-4">
                    <div class="d-flex gap-3 align-items-center bg-light p-3 rounded-3 mb-3">
                        <img src="<?= getBookCoverUrl($book['cover_image']) ?>" alt="<?= e($book['title']) ?>" style="width: 50px; height: 70px; object-fit: cover; border-radius: 6px;">
                        <div>
                            <h6 class="fw-bold mb-1 text-dark"><?= e($book['title']) ?></h6>
                            <p class="text-muted small mb-0">oleh <?= e($book['author']) ?></p>
                        </div>
                    </div>

                    <?php 
                        $durationDays = $currentUser['max_borrow_days'] ?? 3;
                        $calcDueDate = date('Y-m-d', strtotime("+$durationDays days"));
                    ?>

                    <ul class="list-group list-group-flush mb-3 small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Paket Membership:</span>
                            <strong><?= e($currentUser['membership_name']) ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Tanggal Pinjam:</span>
                            <strong><?= formatDate(date('Y-m-d')) ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Durasi Pinjam:</span>
                            <strong><?= $durationDays ?> Hari</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 text-primary">
                            <span class="fw-semibold">Jatuh Tempo Pengembalian:</span>
                            <strong class="text-danger"><?= formatDate($calcDueDate) ?></strong>
                        </li>
                    </ul>

                    <div class="alert alert-warning py-2 px-3 small border-0 rounded-3 mb-0">
                        <i class="bi bi-exclamation-circle-fill me-1"></i> Keterlambatan pengembalian buku akan dikenakan denda sebesar <strong>Rp 1.000 / hari</strong>.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Konfirmasi & Pinjam</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
