<?php
/**
 * Landing Page - Layar Literasi
 * Matching Figma UI/UX Design
 */
$pageTitle = "Perpustakaan Digital Modern";
require_once __DIR__ . '/config/app.php';

$db = getDB();

// Fetch statistics
$totalBooks = $db->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'member'")->fetchColumn();
$totalCategories = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Fetch Categories with book count
$stmtCats = $db->query("
    SELECT c.*, COUNT(b.id) AS book_count 
    FROM categories c 
    LEFT JOIN books b ON c.id = b.category_id 
    GROUP BY c.id 
    ORDER BY book_count DESC 
    LIMIT 6
");
$categories = $stmtCats->fetchAll();

// Fetch Featured & Popular Books
$stmtBooks = $db->query("
    SELECT b.*, c.name AS category_name, c.slug AS category_slug 
    FROM books b 
    JOIN categories c ON b.category_id = c.id 
    ORDER BY b.is_featured DESC, b.views_count DESC 
    LIMIT 8
");
$featuredBooks = $stmtBooks->fetchAll();

// Check if user has active reading progress
$lastReadProgress = null;
if (isLoggedIn()) {
    $stmtProg = $db->prepare("
        SELECT rp.*, b.title, b.cover_image, b.slug AS book_slug, b.pages 
        FROM reading_progress rp 
        JOIN books b ON rp.book_id = b.id 
        WHERE rp.user_id = ? 
        ORDER BY rp.last_read_at DESC 
        LIMIT 1
    ");
    $stmtProg->execute([$_SESSION['user_id']]);
    $lastReadProgress = $stmtProg->fetch();
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- HERO SECTION (Deep Slate Navy as in Figma) -->
<section class="hero-section">
    <div class="container position-relative" style="z-index: 2;">
        <div class="row align-items-center">
            <!-- Left Hero Content -->
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="hero-pill-badge">
                    <span class="badge-dot"></span>
                    Perpustakaan Digital Modern
                </div>
                
                <h1 class="hero-title">
                    Temukan Dunia Baru Melalui <span class="text-orange">Literasi</span>
                </h1>
                
                <p class="hero-subtitle">
                    Akses ribuan buku digital, baca langsung secara interaktif di browser Anda, catat bookmark, dan jelajahi wawasan tak terbatas kapan saja dan di mana saja.
                </p>

                <!-- Search Input Bar -->
                <form action="<?= BASE_URL ?>/catalog.php" method="GET" class="hero-search-wrapper">
                    <i class="bi bi-search text-muted ms-2 me-1"></i>
                    <input type="text" name="q" class="hero-search-input" placeholder="Cari judul buku, penulis, atau topik..." required>
                    <button type="submit" class="btn-hero-search">
                        Cari Buku
                    </button>
                </form>

                <!-- Action Buttons -->
                <div class="hero-action-buttons">
                    <a href="<?= BASE_URL ?>/catalog.php" class="btn-orange">
                        Mulai Membaca <i class="bi bi-arrow-right"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/catalog.php?filter=available" class="btn-hero-outline">
                        <i class="bi bi-journal-bookmark me-1"></i> Pinjam Buku
                    </a>
                </div>

                <!-- Stats Counter -->
                <div class="hero-stats-row">
                    <div class="stat-item">
                        <h4><?= max(1200, $totalBooks * 120) ?>+</h4>
                        <p>Koleksi Buku</p>
                    </div>
                    <div class="stat-item">
                        <h4><?= max(5400, $totalUsers * 1350) ?>+</h4>
                        <p>Pembaca Aktif</p>
                    </div>
                    <div class="stat-item">
                        <h4>24/7</h4>
                        <p>Akses Digital</p>
                    </div>
                </div>
            </div>

            <!-- Right Hero Graphic with Floating Reading Card -->
            <div class="col-lg-6">
                <div class="hero-visual-card position-relative">
                    <img src="<?= ASSETS_URL ?>/images/hero-reading.jpg" alt="Membaca Buku Digital Layar Literasi" class="hero-main-img">

                    <!-- Floating Card Matching Figma Thumbnail -->
                    <div class="hero-floating-progress">
                        <?php if ($lastReadProgress): ?>
                            <div class="progress-card-header">
                                <div class="progress-icon">
                                    <i class="bi bi-book-half"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <p class="progress-card-title">Sedang Dibaca</p>
                                    <h6 class="progress-card-book text-truncate" title="<?= e($lastReadProgress['title']) ?>">
                                        <?= e($lastReadProgress['title']) ?>
                                    </h6>
                                </div>
                            </div>
                            <div class="custom-progress-bar">
                                <div class="custom-progress-fill" style="width: <?= (float)$lastReadProgress['percentage'] ?>%;"></div>
                            </div>
                            <div class="progress-card-footer">
                                <span>Halaman <?= e($lastReadProgress['current_page']) ?> dari <?= e($lastReadProgress['total_pages']) ?></span>
                                <a href="<?= BASE_URL ?>/read.php?id=<?= $lastReadProgress['book_id'] ?>" class="text-orange fw-bold text-decoration-none">
                                    Lanjut Baca &rarr;
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="progress-card-header">
                                <div class="progress-icon">
                                    <i class="bi bi-book-half"></i>
                                </div>
                                <div>
                                    <p class="progress-card-title">Sedang Dibaca</p>
                                    <h6 class="progress-card-book">Filosofi Teras</h6>
                                </div>
                            </div>
                            <div class="custom-progress-bar">
                                <div class="custom-progress-fill" style="width: 68%;"></div>
                            </div>
                            <div class="progress-card-footer">
                                <span>Halaman 142 dari 210</span>
                                <span class="text-orange fw-bold">68% Selesai</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CATEGORIES SECTION (Matching Figma Cards) -->
<section class="py-5" id="kategori">
    <div class="container py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <span class="section-badge-orange">Kategori Pilihan</span>
                <h2 class="section-heading mb-0">Temukan buku berdasarkan minatmu</h2>
            </div>
            <a href="<?= BASE_URL ?>/catalog.php" class="btn btn-outline-primary rounded-pill px-4 mt-3 mt-md-0 fw-semibold">
                Lihat Semua Kategori &rarr;
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($categories as $cat): ?>
                <div class="col-lg-4 col-md-6">
                    <a href="<?= BASE_URL ?>/catalog.php?category=<?= urlencode($cat['slug']) ?>" class="category-card">
                        <div class="category-card-left">
                            <div class="category-icon-box">
                                <i class="bi <?= e($cat['icon']) ?>"></i>
                            </div>
                            <div class="category-info">
                                <h5><?= e($cat['name']) ?></h5>
                                <p><?= (int)$cat['book_count'] ?> Buku Tersedia</p>
                            </div>
                        </div>
                        <div class="category-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FEATURED BOOKS SECTION -->
<section class="py-5 bg-white border-top border-bottom">
    <div class="container py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <span class="section-badge-orange">Katalog Pilihan</span>
                <h2 class="section-heading mb-0">Buku Populer & Rekomendasi</h2>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="<?= BASE_URL ?>/catalog.php?tier=free" class="btn btn-sm btn-light border rounded-pill px-3">Free</a>
                <a href="<?= BASE_URL ?>/catalog.php?tier=basic" class="btn btn-sm btn-light border rounded-pill px-3">Basic</a>
                <a href="<?= BASE_URL ?>/catalog.php?tier=premium" class="btn btn-sm btn-light border rounded-pill px-3">Premium</a>
                <a href="<?= BASE_URL ?>/catalog.php" class="btn btn-sm btn-primary rounded-pill px-3">Semua Buku</a>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredBooks as $book): ?>
                <?php 
                    $isFav = isLoggedIn() ? isBookFavorited($_SESSION['user_id'], $book['id']) : false;
                ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="book-card">
                        <!-- Cover Wrapper -->
                        <div class="book-cover-wrap">
                            <img src="<?= getBookCoverUrl($book['cover_image']) ?>" alt="<?= e($book['title']) ?>" class="book-cover-img" loading="lazy">
                            
                            <!-- Membership Tier Badge -->
                            <div class="book-badge-tier">
                                <?= getTierBadge($book['membership_tier']) ?>
                            </div>

                            <!-- Favorite Toggle Button -->
                            <button type="button" class="btn-fav-toggle <?= $isFav ? 'active' : '' ?>" data-book-id="<?= $book['id'] ?>" title="Simpan ke Favorit">
                                <i class="bi <?= $isFav ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                            </button>
                        </div>

                        <!-- Card Body -->
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

                            <!-- Card Action Buttons -->
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
    </div>
</section>

<!-- MEMBERSHIP PLANS OVERVIEW -->
<section class="py-5" id="membership">
    <div class="container py-3">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="section-badge-orange">Paket Membership</span>
            <h2 class="section-heading">Tingkatkan Pengalaman Membacamu</h2>
            <p class="section-desc mx-auto">
                Pilih paket membership yang sesuai dengan gaya belajarmu. Akses buku eksklusif, batas peminjaman lebih banyak, dan dukungan prioritas.
            </p>
        </div>

        <div class="row g-4 justify-content-center">
            <!-- Free Plan -->
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card">
                    <h4 class="fw-bold mb-1 text-dark">Free</h4>
                    <p class="text-muted small">Untuk pembaca pemula</p>
                    <div class="pricing-price">
                        Rp 0 <span>/ selamanya</span>
                    </div>
                    <ul class="pricing-features-list">
                        <li><i class="bi bi-check-circle-fill"></i> Akses katalog buku Free</li>
                        <li><i class="bi bi-check-circle-fill"></i> Pinjam maks 2 buku sekaligus</li>
                        <li><i class="bi bi-check-circle-fill"></i> Durasi pinjam 3 hari</li>
                        <li><i class="bi bi-check-circle-fill"></i> Baca online PDF di browser</li>
                        <li class="text-muted text-decoration-line-through"><i class="bi bi-x-circle text-muted"></i> Akses koleksi Premium</li>
                        <li class="text-muted text-decoration-line-through"><i class="bi bi-x-circle text-muted"></i> Simpan bookmark tak terbatas</li>
                    </ul>
                    <a href="<?= BASE_URL ?>/register.php" class="btn btn-outline-secondary w-100 py-2.5 rounded-pill fw-semibold">
                        Mulai Gratis
                    </a>
                </div>
            </div>

            <!-- Basic Plan -->
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card">
                    <h4 class="fw-bold mb-1 text-dark">Basic</h4>
                    <p class="text-muted small">Untuk pembaca reguler & mahasiswa</p>
                    <div class="pricing-price">
                        Rp 29.000 <span>/ bulan</span>
                    </div>
                    <ul class="pricing-features-list">
                        <li><i class="bi bi-check-circle-fill"></i> Akses seluruh buku Free & Basic</li>
                        <li><i class="bi bi-check-circle-fill"></i> Pinjam maks 5 buku sekaligus</li>
                        <li><i class="bi bi-check-circle-fill"></i> Durasi pinjam 7 hari</li>
                        <li><i class="bi bi-check-circle-fill"></i> Simpan bookmark & resume progress</li>
                        <li><i class="bi bi-check-circle-fill"></i> Dukungan layanan anggota</li>
                        <li class="text-muted text-decoration-line-through"><i class="bi bi-x-circle text-muted"></i> Akses buku edisi Premium</li>
                    </ul>
                    <a href="<?= BASE_URL ?>/membership.php" class="btn btn-primary w-100 py-2.5 rounded-pill fw-semibold">
                        Pilih Basic
                    </a>
                </div>
            </div>

            <!-- Premium Plan (Featured) -->
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card featured">
                    <div class="pricing-featured-badge">PALING POPULER</div>
                    <h4 class="fw-bold mb-1 text-dark">Premium</h4>
                    <p class="text-muted small">Akses tanpa batas ke seluruh perpustakaan</p>
                    <div class="pricing-price text-orange">
                        Rp 49.000 <span>/ bulan</span>
                    </div>
                    <ul class="pricing-features-list">
                        <li><i class="bi bi-check-circle-fill text-warning"></i> <strong>Akses SEMUA buku</strong> termasuk Premium</li>
                        <li><i class="bi bi-check-circle-fill text-warning"></i> Pinjam maks 10 buku sekaligus</li>
                        <li><i class="bi bi-check-circle-fill text-warning"></i> Durasi peminjaman 14 hari</li>
                        <li><i class="bi bi-check-circle-fill text-warning"></i> Resume otomatis di setiap perangkat</li>
                        <li><i class="bi bi-check-circle-fill text-warning"></i> Prioritas antrean buku terbaru</li>
                        <li><i class="bi bi-check-circle-fill text-warning"></i> Bookmark tak terbatas + catatan</li>
                    </ul>
                    <a href="<?= BASE_URL ?>/membership.php" class="btn btn-orange w-100 py-2.5 rounded-pill fw-semibold shadow">
                        Langganan Premium
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS & FEATURES HIGHLIGHT -->
<section class="py-5 bg-white border-top">
    <div class="container py-3">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="section-badge-orange">Fitur Unggulan</span>
            <h2 class="section-heading">Membaca Jadi Lebih Mudah & Nyaman</h2>
            <p class="section-desc mx-auto">
                Layar Literasi dirancang dengan teknologi web mutakhir untuk memberikan kenyamanan membaca terbaik bagi seluruh kalangan masyarakat.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="p-4 rounded-4 border bg-light h-100 text-center">
                    <div class="brand-icon-box mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem; background: #2563eb;">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Baca Online PDF.js</h5>
                    <p class="text-muted small mb-0">
                        Buka dokumen langsung di browser tanpa perlu download. Dilengkapi fitur zoom, navigasi halaman cepat, dan mode layar penuh.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-4 rounded-4 border bg-light h-100 text-center">
                    <div class="brand-icon-box mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem; background: #f97316;">
                        <i class="bi bi-bookmark-star"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Progres & Bookmark Otomatis</h5>
                    <p class="text-muted small mb-0">
                        Sistem mencatat halaman terakhir yang Anda baca secara otomatis. Tambahkan penanda halaman pribadi dengan catatan kustom.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-4 rounded-4 border bg-light h-100 text-center">
                    <div class="brand-icon-box mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem; background: #101c34;">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Peminjaman Aman & Rapi</h5>
                    <p class="text-muted small mb-0">
                        Pantau tenggat waktu peminjaman dengan notifikasi visual countdown dan penghitungan denda otomatis yang transparan.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CALL TO ACTION (CTA) -->
<section class="py-5" style="background: linear-gradient(135deg, #101c34 0%, #1e293b 100%);">
    <div class="container text-center py-4">
        <h2 class="text-white fw-bold mb-3">Siap Menjelajahi Samudra Pengetahuan?</h2>
        <p class="text-white-50 max-w-600 mx-auto mb-4" style="font-size: 1.05rem;">
            Bergabunglah bersama ribuan anggota lainnya di Layar Literasi. Daftar dalam hitungan detik dan nikmati kemudahan membaca buku berkualitas.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="<?= BASE_URL ?>/register.php" class="btn btn-orange px-4 py-2.5 rounded-pill fw-semibold shadow">
                Daftar Akun Sekarang <i class="bi bi-arrow-right ms-1"></i>
            </a>
            <a href="<?= BASE_URL ?>/catalog.php" class="btn btn-hero-outline px-4 py-2.5 rounded-pill fw-semibold">
                Jelajahi Katalog
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
