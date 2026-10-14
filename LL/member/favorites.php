<?php
/**
 * Member's Favorite Books
 * Layar Literasi Digital Library
 */
$pageTitle = "Buku Favorit Saya";
require_once __DIR__ . '/../config/app.php';

requireMember();
$user = currentUser();
$db = getDB();
$userId = (int)$user['id'];

$stmt = $db->prepare("
    SELECT f.id AS fav_id, f.created_at AS fav_date, bk.*, c.name AS category_name, c.slug AS category_slug 
    FROM favorites f
    JOIN books bk ON f.book_id = bk.id
    JOIN categories c ON bk.category_id = c.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$userId]);
$favorites = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size: 0.85rem;">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/member/index.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Buku Favorit</li>
                </ol>
            </nav>
            <h2 class="h3 fw-bold text-dark mb-0">Koleksi Buku Favorit</h2>
        </div>
        <a href="<?= BASE_URL ?>/catalog.php" class="btn btn-outline-primary rounded-pill px-4 mt-3 mt-md-0 fw-semibold">
            <i class="bi bi-search me-1"></i> Cari Buku Lainnya
        </a>
    </div>

    <?php if (empty($favorites)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <i class="bi bi-heart display-1 text-danger opacity-50 d-block mb-3"></i>
            <h4 class="fw-bold text-dark">Belum Ada Buku Favorit</h4>
            <p class="text-muted max-w-500 mx-auto mb-4">
                Anda belum menandai buku mana pun sebagai favorit. Klik ikon hati pada buku yang Anda sukai di katalog untuk menyimpannya ke daftar ini.
            </p>
            <div>
                <a href="<?= BASE_URL ?>/catalog.php" class="btn btn-orange px-4 py-2 rounded-pill fw-semibold shadow">
                    Jelajahi Katalog Buku
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($favorites as $book): ?>
                <div class="col-xl-3 col-lg-4 col-md-6 favorite-card-col">
                    <div class="book-card">
                        <div class="book-cover-wrap">
                            <img src="<?= getBookCoverUrl($book['cover_image']) ?>" alt="<?= e($book['title']) ?>" class="book-cover-img" loading="lazy">
                            
                            <div class="book-badge-tier">
                                <?= getTierBadge($book['membership_tier']) ?>
                            </div>

                            <button type="button" class="btn-fav-toggle active" data-book-id="<?= $book['id'] ?>" title="Hapus dari Favorit">
                                <i class="bi bi-heart-fill"></i>
                            </button>
                        </div>

                        <div class="book-card-body">
                            <a href="<?= BASE_URL ?>/catalog.php?category=<?= urlencode($book['category_slug']) ?>" class="book-category-tag">
                                <?= e($book['category_name']) ?>
                            </a>
                            <a href="<?= BASE_URL ?>/book-detail.php?id=<?= $book['id'] ?>" class="book-title" title="<?= e($book['title']) ?>">
                                <?= e($book['title']) ?>
                            </a>
                            <p class="book-author">oleh <?= e($book['author']) ?></p>

                            <div class="book-meta-footer">
                                <div class="book-rating">
                                    <i class="bi bi-star-fill"></i>
                                    <span><?= number_format($book['rating'], 1) ?></span>
                                </div>
                                <div class="book-stock <?= $book['stock'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <i class="bi <?= $book['stock'] > 0 ? 'bi-check2' : 'bi-x' ?>"></i>
                                    <?= $book['stock'] > 0 ? 'Stok: ' . $book['stock'] : 'Habis' ?>
                                </div>
                            </div>

                            <div class="book-actions-row">
                                <a href="<?= BASE_URL ?>/book-detail.php?id=<?= $book['id'] ?>" class="btn-book-action btn btn-outline-secondary">
                                    Detail
                                </a>
                                <?php if (!empty($book['pdf_file'])): ?>
                                    <a href="<?= BASE_URL ?>/read.php?id=<?= $book['id'] ?>" class="btn-book-action btn btn-orange">
                                        <i class="bi bi-book me-1"></i> Baca
                                    </a>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/book-detail.php?id=<?= $book['id'] ?>" class="btn-book-action btn btn-primary">
                                        Pinjam
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
