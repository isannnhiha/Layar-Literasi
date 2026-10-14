<?php
/**
 * Book Catalog - Search, Filter, and Browse Books
 * Layar Literasi Digital Library
 */
$pageTitle = "Katalog Buku Digital";
require_once __DIR__ . '/config/app.php';

$db = getDB();

// Input Parameters
$search = trim($_GET['q'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$tier = trim($_GET['tier'] ?? '');
$stockFilter = trim($_GET['stock'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;
$offset = ($page - 1) * $perPage;

// Fetch all categories for filter
$allCategories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Build Query
$where = ["1=1"];
$params = [];

if ($search !== '') {
    $where[] = "(b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR b.publisher LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($categorySlug !== '') {
    $where[] = "c.slug = ?";
    $params[] = $categorySlug;
}

if (in_array($tier, ['free', 'basic', 'premium'])) {
    $where[] = "b.membership_tier = ?";
    $params[] = $tier;
}

if ($stockFilter === 'available') {
    $where[] = "b.stock > 0";
}

$whereClause = implode(" AND ", $where);

// Sorting
$orderBy = "b.created_at DESC";
switch ($sort) {
    case 'popular':
        $orderBy = "b.views_count DESC";
        break;
    case 'rating':
        $orderBy = "b.rating DESC";
        break;
    case 'title_asc':
        $orderBy = "b.title ASC";
        break;
    case 'newest':
    default:
        $orderBy = "b.id DESC";
        break;
}

// Count total
$countSql = "SELECT COUNT(*) FROM books b JOIN categories c ON b.category_id = c.id WHERE $whereClause";
$stmtCount = $db->prepare($countSql);
$stmtCount->execute($params);
$totalBooks = (int)$stmtCount->fetchColumn();
$totalPages = ceil($totalBooks / $perPage);

// Fetch Books
$sql = "
    SELECT b.*, c.name AS category_name, c.slug AS category_slug 
    FROM books b 
    JOIN categories c ON b.category_id = c.id 
    WHERE $whereClause 
    ORDER BY $orderBy 
    LIMIT $perPage OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Catalog Header Banner -->
<div class="py-4 bg-white border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2" style="font-size: 0.85rem;">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">Beranda</a></li>
                <li class="breadcrumb-item active" aria-current="page">Katalog Buku</li>
            </ol>
        </nav>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Katalog Buku Digital</h1>
                <p class="text-muted small mb-0">Menampilkan <?= count($books) ?> dari <?= $totalBooks ?> koleksi buku yang tersedia</p>
            </div>
            <?php if ($search || $categorySlug || $tier || $stockFilter): ?>
                <a href="<?= BASE_URL ?>/catalog.php" class="btn btn-sm btn-outline-danger mt-2 mt-md-0 rounded-pill">
                    <i class="bi bi-x-circle me-1"></i> Reset Semua Filter
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 sticky-top" style="top: 90px; z-index: 10;">
                <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-funnel text-primary"></i> Filter Pencarian
                </h5>

                <form action="<?= BASE_URL ?>/catalog.php" method="GET">
                    <!-- Search Input -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Kata Kunci</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control bg-light border-start-0" placeholder="Judul, Penulis, ISBN..." value="<?= e($search) ?>">
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Kategori</label>
                        <select name="category" class="form-select bg-light">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($allCategories as $c): ?>
                                <option value="<?= e($c['slug']) ?>" <?= ($categorySlug === $c['slug']) ? 'selected' : '' ?>>
                                    <?= e($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Membership Tier Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Akses Membership</label>
                        <select name="tier" class="form-select bg-light">
                            <option value="">Semua Tingkatan</option>
                            <option value="free" <?= ($tier === 'free') ? 'selected' : '' ?>>Free</option>
                            <option value="basic" <?= ($tier === 'basic') ? 'selected' : '' ?>>Basic</option>
                            <option value="premium" <?= ($tier === 'premium') ? 'selected' : '' ?>>Premium</option>
                        </select>
                    </div>

                    <!-- Stock Filter -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted">Ketersediaan Stok</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="stock" value="available" id="stockCheck" <?= ($stockFilter === 'available') ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="stockCheck">
                                Hanya buku yang tersedia
                            </label>
                        </div>
                    </div>

                    <!-- Submit Filter -->
                    <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-semibold mb-2">
                        Terapkan Filter
                    </button>
                    <?php if ($search || $categorySlug || $tier || $stockFilter): ?>
                        <a href="<?= BASE_URL ?>/catalog.php" class="btn btn-light w-100 py-2 rounded-pill text-muted small">
                            Hapus Filter
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Catalog Books Grid -->
        <div class="col-lg-9">
            <!-- Sort and View Toolbar -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                    <span class="text-muted small">
                        <?php if ($totalBooks > 0): ?>
                            Menampilkan <strong><?= $offset + 1 ?>-<?= min($totalBooks, $offset + $perPage) ?></strong> dari <strong><?= $totalBooks ?></strong> buku
                        <?php else: ?>
                            Tidak ada buku ditemukan
                        <?php endif; ?>
                    </span>

                    <form action="<?= BASE_URL ?>/catalog.php" method="GET" class="d-flex align-items-center gap-2">
                        <?php if ($search): ?><input type="hidden" name="q" value="<?= e($search) ?>"><?php endif; ?>
                        <?php if ($categorySlug): ?><input type="hidden" name="category" value="<?= e($categorySlug) ?>"><?php endif; ?>
                        <?php if ($tier): ?><input type="hidden" name="tier" value="<?= e($tier) ?>"><?php endif; ?>
                        <?php if ($stockFilter): ?><input type="hidden" name="stock" value="<?= e($stockFilter) ?>"><?php endif; ?>
                        
                        <label class="text-muted small text-nowrap">Urutkan:</label>
                        <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 150px;">
                            <option value="newest" <?= ($sort === 'newest') ? 'selected' : '' ?>>Terbaru</option>
                            <option value="popular" <?= ($sort === 'popular') ? 'selected' : '' ?>>Paling Populer</option>
                            <option value="rating" <?= ($sort === 'rating') ? 'selected' : '' ?>>Rating Tertinggi</option>
                            <option value="title_asc" <?= ($sort === 'title_asc') ? 'selected' : '' ?>>Judul A - Z</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Books Grid -->
            <?php if (empty($books)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                    <i class="bi bi-journal-x display-1 text-muted mb-3"></i>
                    <h4 class="fw-bold text-dark">Tidak Ada Buku Ditemukan</h4>
                    <p class="text-muted max-w-500 mx-auto mb-4">
                        Maaf, kami tidak menemukan buku yang sesuai dengan filter atau kata kunci "<?= e($search) ?>". Coba ganti kata kunci atau reset filter.
                    </p>
                    <div>
                        <a href="<?= BASE_URL ?>/catalog.php" class="btn btn-orange px-4 py-2 rounded-pill">
                            Lihat Semua Koleksi Buku
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($books as $book): ?>
                        <?php 
                            $isFav = isLoggedIn() ? isBookFavorited($_SESSION['user_id'], $book['id']) : false;
                        ?>
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="book-card">
                                <!-- Cover Wrapper -->
                                <div class="book-cover-wrap">
                                    <img src="<?= getBookCoverUrl($book['cover_image']) ?>" alt="<?= e($book['title']) ?>" class="book-cover-img" loading="lazy">
                                    
                                    <!-- Tier Badge -->
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

                                    <!-- Card Actions -->
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

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-5" aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-circle m-1" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link rounded-circle m-1" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link rounded-circle m-1" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
